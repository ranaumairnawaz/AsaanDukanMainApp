<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WhatsAppMessageLog;
use App\Models\WhatsAppNumberPool;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WhatsAppWebhookController extends Controller
{
    /**
     * Handle incoming webhooks from the Node.js WhatsApp Gateway.
     */
    public function handle(Request $request)
    {
        $event = $request->input('event');
        $data = $request->input('data', []);

        Log::info("WhatsApp Webhook Received: {$event}", $data);

        switch ($event) {
            case 'session.connected':
                $this->handleSessionConnected($data);
                break;
            case 'session.disconnected':
                $this->handleSessionDisconnected($data);
                break;
            case 'message.sent':
                $this->handleMessageSent($data);
                break;
            case 'message.failed':
                $this->handleMessageFailed($data);
                break;
        }

        return response()->json(['status' => 'success']);
    }

    protected function handleSessionConnected(array $data)
    {
        WhatsAppNumberPool::where('phone_number', $data['sessionId'])
            ->update([
                'status' => 'active',
                'last_active_at' => now(),
            ]);
    }

    protected function handleSessionDisconnected(array $data)
    {
        WhatsAppNumberPool::where('phone_number', $data['sessionId'])
            ->update([
                'status' => 'disconnected',
            ]);
    }

    protected function handleMessageSent(array $data)
    {
        if (isset($data['metadata']['tenant_id'])) {
            WhatsAppMessageLog::create([
                'tenant_id'       => $data['metadata']['tenant_id'],
                'order_id'        => $data['metadata']['order_id'] ?? null,
                'direction'       => 'outbound',
                'recipient_phone' => $data['recipient'],
                'template_used'   => $data['metadata']['type'] ?? 'custom',
                'status'          => 'sent',
            ]);
        }
    }

    protected function handleMessageFailed(array $data)
    {
        if (isset($data['metadata']['tenant_id'])) {
            WhatsAppMessageLog::create([
                'tenant_id'       => $data['metadata']['tenant_id'],
                'order_id'        => $data['metadata']['order_id'] ?? null,
                'direction'       => 'outbound',
                'recipient_phone' => $data['recipient'],
                'template_used'   => $data['metadata']['type'] ?? 'custom',
                'status'          => 'failed',
                'error_message'   => $data['error'] ?? 'Unknown error',
            ]);
        }
    }
}
