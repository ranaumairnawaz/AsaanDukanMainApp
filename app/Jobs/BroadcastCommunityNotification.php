<?php

namespace App\Jobs;

use App\Models\Tenant;
use App\Services\WhatsAppMessageService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class BroadcastCommunityNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $title;
    public $body;
    public $contentType;

    /**
     * Create a new job instance.
     */
    public function __construct(string $title, string $body, string $contentType)
    {
        $this->title = $title;
        $this->body = $body;
        $this->contentType = $contentType;
    }

    /**
     * Execute the job.
     */
    public function handle(WhatsAppMessageService $waService): void
    {
        $message = "📢 *New Asaan Community Post*\n\n";
        $message .= "*" . $this->title . "*\n\n";
        
        $bodyExcerpt = substr(strip_tags($this->body), 0, 150);
        if (strlen(strip_tags($this->body)) > 150) {
            $bodyExcerpt .= "...";
        }
        $message .= $bodyExcerpt . "\n\n";
        $message .= "Open Asaan Dukan App:\n" . config('app.url') . "/merchant";

        Tenant::whereNotNull('whatsapp_number')
            ->where('status', 'active')
            ->chunk(100, function ($tenants) use ($message) {
                foreach ($tenants as $tenant) {
                    \App\Jobs\SendWhatsAppMessage::dispatch($tenant->whatsapp_number, $message, [
                        'type' => 'community_broadcast',
                        // Note: tenant_id intentionally NOT set so quota is not tracked
                    ]);
                }
            });
    }
}
