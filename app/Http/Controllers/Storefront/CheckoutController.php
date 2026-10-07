<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\ThemeRegistry;
use App\Services\WhatsAppMessageService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class CheckoutController extends Controller
{
    /**
     * Show the checkout page.
     */
    public function index(Request $request)
    {
        $tenant = app('tenant');
        $theme = ThemeRegistry::forTenant($tenant);
        $cart = session()->get("cart_{$tenant->id}", []);

        if (empty($cart)) {
            return redirect()->route('store.home', $request->route()->parameters())
                ->with('error', 'Your cart is empty.');
        }

        $subtotal = collect($cart)->sum('subtotal');
        $deliveryFee = $tenant->delivery_charge ?? 0;
        $discount = 0;
        $couponData = null;

        $couponCode = session()->get("coupon_{$tenant->id}");
        if ($couponCode) {
            $coupon = \App\Models\Coupon::where('tenant_id', $tenant->id)
                ->where('code', $couponCode)
                ->first();

            if ($coupon && $coupon->isValid()) {
                $discount = collect($cart)->sum(function ($item) use ($coupon) {
                    return $coupon->calculateDiscount($item['subtotal']); // For now, discount whole subtotal, can adjust per-item logic later
                });
                // Recalculate based on total subtotal to be simpler
                $discount = $coupon->calculateDiscount($subtotal);

                $couponData = [
                    'code' => $coupon->code,
                    'type' => $coupon->type,
                    'value' => $coupon->value,
                    'discount_amount' => $discount,
                ];
            } else {
                session()->forget("coupon_{$tenant->id}");
            }
        }

        $total = $subtotal - $discount + $deliveryFee;

        $checkoutPage = $theme['slug'] === 'basic'
            ? 'Storefront/basic/Checkout'
            : 'Storefront/Shared/Checkout';

        return Inertia::render($checkoutPage, [
            'tenant' => $tenant,
            'cart' => array_values($cart),
            'totals' => [
                'subtotal' => $subtotal,
                'discount' => $discount,
                'delivery' => $deliveryFee,
                'total' => max(0, $total),
            ],
            'coupon' => $couponData,
            'theme' => [
                'slug' => $theme['slug'],
                'accent' => $theme['accent'] ?? $theme['preview_css_vars']['--color-accent'],
                'css_vars' => $theme['preview_css_vars'],
                'settings' => $theme['settings'],
                'logo_path' => $theme['logo_path'] ?? null,
            ],
        ]);
    }

    /**
     * Process the checkout form.
     */
    public function store(Request $request)
    {
        $tenant = app('tenant');
        $cart = session()->get("cart_{$tenant->id}", []);

        if (empty($cart)) {
            return back()->with('error', 'Your cart is empty.');
        }

        $validated = $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'required|string|max:20',
            'delivery_address' => 'required|string|max:1000',
            'notes' => 'nullable|string|max:1000',
            'payment_method' => 'required|in:cod,bank_transfer',
        ]);

        // Check if store is over monthly order limit — we allow orders through (soft limit)
        // and track overages for billing at renewal time.
        // (No blocking here — merchant is charged per extra order on renewal)

        // Create or update customer
        $customer = Customer::firstOrCreate(
            ['tenant_id' => $tenant->id, 'phone' => $validated['customer_phone']],
            ['name' => $validated['customer_name'], 'address' => $validated['delivery_address']]
        );

        if ($customer->address !== $validated['delivery_address'] || $customer->name !== $validated['customer_name']) {
            $customer->update([
                'name' => $validated['customer_name'],
                'address' => $validated['delivery_address'],
            ]);
        }

        $subtotal = collect($cart)->sum('subtotal');
        $deliveryFee = $tenant->delivery_charge ?? 0;
        $discount = 0;
        $appliedCoupon = null;

        $couponCode = session()->get("coupon_{$tenant->id}");
        if ($couponCode) {
            $coupon = \App\Models\Coupon::where('tenant_id', $tenant->id)
                ->where('code', $couponCode)
                ->first();

            if ($coupon && $coupon->isValid()) {
                $discount = $coupon->calculateDiscount($subtotal);
                $appliedCoupon = $coupon;
            }
        }

        $total = max(0, $subtotal - $discount + $deliveryFee);

        // Generate Order Number
        $orderNumber = 'ORD-' . strtoupper(Str::random(8));

        // Create Order
        $order = Order::create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'order_number' => $orderNumber,
            'status' => 'pending',
            'subtotal' => $subtotal,
            'delivery_fee' => $deliveryFee,
            // 'discount' field should be added to orders table eventually, for now subtracting it implicitly
            // We should add discount to orders table migration but I will recalculate here for now.
            'total' => $total,
            'payment_method' => $validated['payment_method'],
            'payment_status' => 'pending',
            'delivery_address' => $validated['delivery_address'],
            'notes' => $validated['notes'],
        ]);

        // Record Coupon Usage
        if ($appliedCoupon) {
            \App\Models\CouponUsage::create([
                'coupon_id' => $appliedCoupon->id,
                'customer_id' => $customer->id,
                'order_id' => $order->id,
            ]);
            $appliedCoupon->increment('times_used');
        }

        // Create Order Items and decrease stock
        foreach ($cart as $item) {
            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $item['product_id'],
                'product_variant_id' => $item['variant_id'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['price'],
                'subtotal' => $item['subtotal'],
            ]);

            $product = \App\Models\Product::find($item['product_id']);
            if ($product)
                $product->decrement('stock_quantity', $item['quantity']);

            if ($item['variant_id']) {
                $variant = \App\Models\ProductVariant::find($item['variant_id']);
                if ($variant)
                    $variant->decrement('stock_quantity', $item['quantity']);
            }
        }

        // Clear cart & coupon
        session()->forget("cart_{$tenant->id}");
        session()->forget("coupon_{$tenant->id}");

        // Notify the merchant of the new order via the WhatsApp API gateway
        // (no more wa.me deep-links — this is sent server-side).
        if ($tenant->whatsapp_number) {
            $msg = "🛍️ *New Order #{$orderNumber}*\n\n";
            $msg .= "*Items:*\n";

            $i = 0;
            foreach ($cart as $item) {
                $product = \App\Models\Product::find($item['product_id']);
                $name = $product ? $product->name : 'Product';
                $i++;
                $price = number_format($item['price']);
                $st = number_format($item['subtotal']);
                $msg .= "{$i}. {$name} — Qty: {$item['quantity']} × Rs. {$price} = Rs. {$st}\n";
            }

            $msg .= "\n*Subtotal:* Rs. " . number_format($subtotal) . "\n";
            if ($discount > 0) {
                $msg .= "*Discount:* -Rs. " . number_format($discount) . "\n";
            }
            $msg .= "*Delivery Charges:* Rs. " . number_format($deliveryFee) . "\n";
            $msg .= "*Total:* Rs. " . number_format($total) . "\n\n";

            $msg .= "👤 *Customer Details*\n";
            $msg .= "Name: {$customer->name}\n";
            $msg .= "Phone: {$customer->phone}\n";
            $msg .= "Address: {$customer->address}\n\n";

            if ($validated['payment_method'] === 'bank_transfer') {
                $msg .= "💳 *Payment Method:* Bank Transfer\n";
                $msg .= "Please share payment screenshot after transfer.";
            } else {
                $msg .= "💵 *Payment Method:* Cash on Delivery";
            }

            app(WhatsAppMessageService::class)->sendMerchantNewOrderAlert($order, $msg);
        }

        // Build the confirmation URL
        $confirmationUrl = rtrim($tenant->store_url, '/') . '/order/' . $orderNumber;

        return Inertia::location($confirmationUrl);
    }

    /**
     * Show order confirmation page.
     */
    public function confirmation(Request $request)
    {
        // Pull order_number by name since parameter position differs between
        // path-based routes (store/{store_slug}/order/{order_number})
        // and domain-based routes ({tenant_domain}/order/{order_number})
        $order_number = $request->route('order_number');

        $tenant = app('tenant');
        $order = Order::where('tenant_id', $tenant->id)
            ->where('order_number', $order_number)
            ->with('items.product', 'items.variant')
            ->firstOrFail();

        $theme = ThemeRegistry::forTenant($tenant);

        return Inertia::render('Storefront/Shared/Confirmation', [
            'tenant' => $tenant,
            'order' => $order,
            'theme' => [
                'slug' => $theme['slug'],
                'accent' => $theme['accent'] ?? $theme['preview_css_vars']['--color-accent'],
                'css_vars' => $theme['preview_css_vars'],
                'settings' => $theme['settings'],
                'logo_path' => $theme['logo_path'] ?? null,
            ],
        ]);
    }
}
