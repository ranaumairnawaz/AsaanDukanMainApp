<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\ThemeRegistry;
use Illuminate\Http\Request;
use Inertia\Inertia;

class OrderTrackingController extends Controller
{
    /**
     * Show the order tracking form.
     */
    public function index(Request $request)
    {
        $tenant = app('tenant');
        $theme  = ThemeRegistry::forTenant($tenant);

        return Inertia::render('Storefront/Shared/TrackOrder', [
            'tenant' => $tenant,
            'theme'  => [
                'slug'     => $theme['slug'],
                'accent'   => $theme['settings']?->accent_color ?? $theme['preview_css_vars']['--color-accent'],
                'css_vars' => $theme['preview_css_vars'],
            ],
            'order'  => null,
            'error'  => null,
        ]);
    }

    /**
     * Look up an order by order_number + customer phone (security check).
     */
    public function track(Request $request)
    {
        $tenant = app('tenant');
        $theme  = ThemeRegistry::forTenant($tenant);

        $validated = $request->validate([
            'order_number' => 'required|string',
            'phone'        => 'required|string',
        ]);

        // Normalize phone
        $phone = $validated['phone'];
        if (str_starts_with($phone, '03')) {
            $phone = '923' . substr($phone, 2);
        }

        $order = Order::where('tenant_id', $tenant->id)
            ->where('order_number', strtoupper($validated['order_number']))
            ->with(['items.product', 'items.variant', 'customer'])
            ->first();

        $error = null;
        if (! $order) {
            $error = 'No order found with that Order ID.';
        } elseif (
            $order->customer->phone !== $validated['phone'] &&
            $order->customer->phone !== $phone
        ) {
            $error = 'The phone number does not match our records for this order.';
            $order = null;
        }

        return Inertia::render('Storefront/Shared/TrackOrder', [
            'tenant' => $tenant,
            'theme'  => [
                'slug'     => $theme['slug'],
                'accent'   => $theme['settings']?->accent_color ?? $theme['preview_css_vars']['--color-accent'],
                'css_vars' => $theme['preview_css_vars'],
            ],
            'order' => $order,
            'error' => $error,
        ]);
    }
}
