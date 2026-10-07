<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\WhatsAppMessageService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class MonitorSubscriptionExpiry extends Command
{
    protected $signature = 'subscriptions:monitor {--dry-run : Show what would happen without making changes}';

    protected $description = 'Send WhatsApp alerts at exactly 3 days, 1 day, and expiry day milestones, and suspend after grace period';

    public function handle(WhatsAppMessageService $wa)
    {
        $dryRun = (bool) $this->option('dry-run');
        $now = Carbon::now();

        $this->info("Subscription Monitor — " . $now->toDateTimeString());
        $this->info("Dry run: " . ($dryRun ? 'YES' : 'NO'));
        $this->newLine();

        // 1. Alert at 3 days remaining
        $sent3day = $this->handleMilestone($wa, $now, 3, $dryRun);

        // 2. Alert at 1 day remaining
        $sent1day = $this->handleMilestone($wa, $now, 1, $dryRun);

        // 3. Alert on expiry day (0 days = today is the expiry date)
        $sentExpiry = $this->handleExpiryDay($wa, $now, $dryRun);

        // 4. Suspend after 3-day grace period
        $suspended = $this->suspendExpired($wa, $now, $dryRun);

        $this->newLine();
        $this->info("Summary: {$sent3day} (3-day), {$sent1day} (1-day), {$sentExpiry} (expiry-day) alerts sent, {$suspended} suspended.");
        return self::SUCCESS;
    }

    /**
     * Send alert to tenants expiring in exactly $daysLeft days (±12h window to avoid duplicates).
     */
    protected function handleMilestone(WhatsAppMessageService $wa, Carbon $now, int $daysLeft, bool $dryRun): int
    {
        $windowStart = $now->copy()->addDays($daysLeft)->startOfDay();
        $windowEnd   = $now->copy()->addDays($daysLeft)->endOfDay();

        $tenants = Tenant::where('status', 'active')
            ->whereNotNull('subscription_expires_at')
            ->whereBetween('subscription_expires_at', [$windowStart, $windowEnd])
            ->whereNotNull('whatsapp_number')
            ->with('subscriptionPlan')
            ->get();

        $this->info("⏰ Expiring in {$daysLeft} day(s) — {$tenants->count()} tenant(s):");

        foreach ($tenants as $tenant) {
            $this->line("  → {$tenant->name} (#{$tenant->id}) expires {$tenant->subscription_expires_at->toDateString()}");
            if (!$dryRun) {
                $wa->notifyMerchantSubscriptionExpiringSoon($tenant, $daysLeft);
            }
        }

        return $tenants->count();
    }

    /**
     * Send expiry-day alert to tenants whose subscription expires today.
     */
    protected function handleExpiryDay(WhatsAppMessageService $wa, Carbon $now, bool $dryRun): int
    {
        $tenants = Tenant::where('status', 'active')
            ->whereNotNull('subscription_expires_at')
            ->whereBetween('subscription_expires_at', [$now->copy()->startOfDay(), $now->copy()->endOfDay()])
            ->whereNotNull('whatsapp_number')
            ->get();

        $this->info("🔴 Expiring today — {$tenants->count()} tenant(s):");

        foreach ($tenants as $tenant) {
            $this->line("  → {$tenant->name} (#{$tenant->id})");
            if (!$dryRun) {
                $wa->notifyMerchantSubscriptionExpiringSoon($tenant, 0);
            }
        }

        return $tenants->count();
    }

    /**
     * Suspend tenants that expired more than 3 days ago (grace period).
     */
    protected function suspendExpired(WhatsAppMessageService $wa, Carbon $now, bool $dryRun): int
    {
        $cutoff = $now->copy()->subDays(3); // 3-day grace period

        $tenants = Tenant::where('status', 'active')
            ->whereNotNull('subscription_expires_at')
            ->where('subscription_expires_at', '<', $cutoff)
            ->get();

        $this->info("🚫 Suspending expired (grace period over) — {$tenants->count()} tenant(s):");

        foreach ($tenants as $tenant) {
            $expiredDaysAgo = $tenant->subscription_expires_at->diffInDays($now);
            $this->line("  → {$tenant->name} (#{$tenant->id}) — expired {$expiredDaysAgo} days ago");

            if (!$dryRun) {
                $tenant->update(['status' => 'suspended']);

                // Send suspension WhatsApp if they have a number
                if ($tenant->whatsapp_number) {
                    $message = "🚨 *Store Suspended*\n\n";
                    $message .= "Hi *{$tenant->name}*! Your AssanDukan store has been *suspended* due to subscription expiry.\n\n";
                    $message .= "Renew your plan to restore full access:\n" . url('/merchant/billing');
                    $wa->send($tenant->whatsapp_number, $message, [
                        'tenant_id' => $tenant->id,
                        'type' => 'suspension_notice',
                    ]);
                }
            }
        }

        return $tenants->count();
    }
}
