<?php

namespace App\Jobs;

use App\Models\WhatsAppMessageLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendWhatsAppMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $phone;
    public $message;
    public $context;

    /**
     * Create a new job instance.
     */
    public function __construct(string $phone, string $message, array $context = [])
    {
        $this->phone = $phone;
        $this->message = $message;
        $this->context = $context;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $url = config('services.whatsapp_gateway.url');
        $token = config('services.whatsapp_gateway.token');

        if (!$url || !$token) {
            Log::warning('WhatsApp gateway not configured (WHATSAPP_GATEWAY_URL / WHATSAPP_GATEWAY_TOKEN missing).');
            $this->logAttempt($this->phone, $this->context, 'failed', 'Gateway URL/token not configured.');
            return;
        }

        try {
            $response = Http::withToken($token)
                ->timeout(10)
                ->post($url, [
                    'to' => $this->phone,
                    'message' => $this->message,
                ]);

            if ($response->successful()) {
                $this->logAttempt($this->phone, $this->context, 'sent', null);
            } else {
                $error = "HTTP {$response->status()}: " . $response->body();
                Log::error("WhatsApp gateway send failed: {$error}");
                $this->logAttempt($this->phone, $this->context, 'failed', $error);
            }
        } catch (\Throwable $e) {
            Log::error("WhatsApp gateway send exception: " . $e->getMessage());
            $this->logAttempt($this->phone, $this->context, 'failed', $e->getMessage());
        }
    }

    protected function logAttempt(string $phone, array $context, string $status, ?string $errorMessage): void
    {
        try {
            WhatsAppMessageLog::create([
                'tenant_id' => $context['tenant_id'] ?? null,
                'order_id' => $context['order_id'] ?? null,
                'direction' => 'outbound',
                'recipient_phone' => $phone,
                'template_used' => $context['type'] ?? null,
                'status' => $status,
                'error_message' => $errorMessage,
            ]);

            // Track usage if successful and tenant exists
            if ($status === 'sent' && !empty($context['tenant_id'])) {
                $tenantId = $context['tenant_id'];
                $usage = \App\Models\TenantMessageUsage::firstOrCreate(
                    [
                        'tenant_id' => $tenantId,
                        'billing_period_start' => now()->startOfMonth(),
                        'billing_period_end' => now()->endOfMonth(),
                    ]
                );

                $planFeatureService = app(\App\Services\PlanFeatureService::class);
                $tenant = \App\Models\Tenant::find($tenantId);
                
                $quota = $tenant ? (int) $planFeatureService->getValue($tenant, 'message_quota_monthly', 100) : 100;

                if ($usage->messages_sent_count < $quota || $quota === 0) {
                    $usage->increment('messages_sent_count');
                } else {
                    $usage->increment('overage_count');
                }
            }
        } catch (\Throwable $e) {
            Log::error("Failed to write whatsapp_message_log or update usage: " . $e->getMessage());
        }
    }
}
