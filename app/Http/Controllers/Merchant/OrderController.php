<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\PlanFeatureService;
use App\Services\WhatsAppMessageService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class OrderController extends Controller
{
    public function __construct(private PlanFeatureService $planFeature) {}

    /**
     * Display a listing of orders.
     */
    public function index(Request $request)
    {
        $tenant = auth()->user()->tenant;
        
        $query = Order::where('tenant_id', $tenant->id)->with('customer');

        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        $orders = $query->orderBy('created_at', 'desc')->paginate(20)->withQueryString();

        return Inertia::render('Merchant/Orders/Index', [
            'orders' => $orders,
            'filters' => $request->only(['status']),
        ]);
    }

    /**
     * Display the specified order.
     */
    public function show(Order $order)
    {
        $tenant = auth()->user()->tenant;
        
        if ($order->tenant_id !== $tenant->id) {
            abort(403);
        }

        $order->load(['customer', 'items.product', 'items.variant']);

        return Inertia::render('Merchant/Orders/Show', [
            'order' => $order,
        ]);
    }

    /**
     * Update the order status and tracking info.
     */
    public function update(Request $request, Order $order)
    {
        $tenant = auth()->user()->tenant;
        
        if ($order->tenant_id !== $tenant->id) {
            abort(403);
        }

        $validated = $request->validate([
            'status' => 'required|in:pending,confirmed,processing,shipped,delivered,cancelled',
            'courier_name' => 'nullable|string|max:100',
            'tracking_number' => 'nullable|string|max:100',
        ]);

        $oldStatus = $order->status;
        $order->update($validated);

        // If status changed to confirmed, notify customer via WhatsApp
        if ($validated['status'] === 'confirmed' && $oldStatus !== 'confirmed') {
            $this->notifyCustomerConfirmed($order);
        }

        // If status changed to shipped, notify customer via WhatsApp
        if ($validated['status'] === 'shipped' && $oldStatus !== 'shipped') {
            $this->notifyCustomerShipped($order);
        }

        return back()->with('success', 'Order updated successfully.');
    }

    /**
     * Send WhatsApp notifications when an order is confirmed: always alert
     * the merchant, and additionally notify the customer only if their plan
     * includes the 'customer_whatsapp_notifications' feature.
     */
    protected function notifyCustomerConfirmed(Order $order)
    {
        $waService = new WhatsAppMessageService();

        try {
            $waService->sendMerchantOrderConfirmedAlert($order);
        } catch (\Exception $e) {
            \Log::error("Failed to send merchant order-confirmed alert: " . $e->getMessage());
        }

        if ($this->planFeature->isEnabled($order->tenant, 'customer_whatsapp_notifications')) {
            try {
                $waService->sendOrderConfirmedNotification($order);
            } catch (\Exception $e) {
                \Log::error("Failed to send customer order-confirmed notification: " . $e->getMessage());
            }
        }
    }

    /**
     * Send a WhatsApp notification when an order is shipped.
     */
    protected function notifyCustomerShipped(Order $order)
    {
        $customer = $order->customer;
        if (!$customer || !$customer->phone) {
            return;
        }

        $trackingUrl = url("/store/{$order->tenant->store_slug}/track");
        
        $message = "🚚 Great news! Your order *{$order->order_number}* from *{$order->tenant->name}* has been shipped.\n\n";
        
        if ($order->courier_name && $order->tracking_number) {
            $message .= "Courier: {$order->courier_name}\n";
            $message .= "Tracking #: {$order->tracking_number}\n\n";
        }
        
        $message .= "Track your order here:\n{$trackingUrl}";

        try {
            $waService = new WhatsAppMessageService();
            $waService->send(
                $customer->phone,
                $message,
                [
                    'tenant_id' => $order->tenant_id,
                    'order_id' => $order->id,
                    'type' => 'order_shipped'
                ]
            );
        } catch (\Exception $e) {
            \Log::error("Failed to send shipping notification: " . $e->getMessage());
        }
    }
}
