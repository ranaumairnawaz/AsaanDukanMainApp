<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Services\WhatsAppMessageService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;

class BroadcastController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/WhatsApp/Broadcast');
    }

    public function send(Request $request, WhatsAppMessageService $waService)
    {
        $request->validate([
            'filter'  => 'required|in:all,trial,active,suspended,expiring_5_days',
            'message' => 'required|string|min:5',
        ]);

        $filter = $request->filter;
        $message = $request->message;

        $query = Tenant::query()->whereNotNull('whatsapp_number');

        if ($filter === 'trial') {
            $query->where('status', 'trial');
        } elseif ($filter === 'active') {
            $query->where('status', 'active');
        } elseif ($filter === 'suspended') {
            $query->where('status', 'suspended');
        } elseif ($filter === 'expiring_5_days') {
            $query->where('status', 'active')
                  ->whereBetween('subscription_expires_at', [now(), now()->addDays(5)]);
        }

        $tenants = $query->get();

        if ($tenants->isEmpty()) {
            return back()->with('error', 'No stores match the selected filter.');
        }

        $count = 0;
        foreach ($tenants as $tenant) {
            try {
                // Dispatch message
                $waService->send($tenant->whatsapp_number, $message, [
                    'tenant_id' => $tenant->id,
                    'type'      => 'admin_broadcast'
                ]);
                $count++;
            } catch (\Exception $e) {
                Log::error("Broadcast failed for Tenant {$tenant->id}: " . $e->getMessage());
            }
        }

        return back()->with('success', "Broadcast queued for {$count} store(s).");
    }
}
