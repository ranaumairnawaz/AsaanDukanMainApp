<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Tenant;
use Carbon\Carbon;

class DeactivateExpiredStores extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stores:deactivate-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically deactivate stores whose subscription or trial has expired';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $now = Carbon::now();

        $expiredTenants = Tenant::whereIn('status', ['active', 'trial'])
            ->whereNotNull('subscription_expires_at')
            ->where('subscription_expires_at', '<=', $now)
            ->get();

        if ($expiredTenants->isEmpty()) {
            $this->info('No expired stores found.');
            return 0;
        }

        $count = 0;
        foreach ($expiredTenants as $tenant) {
            $tenant->update([
                'status' => 'suspended'
            ]);
            $this->info("Suspended store: {$tenant->name} (Expired on: {$tenant->subscription_expires_at})");
            $count++;
        }

        $this->info("Successfully suspended {$count} expired store(s).");
        return 0;
    }
}
