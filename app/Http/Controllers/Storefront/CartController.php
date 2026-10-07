<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\Request;

class CartController extends Controller
{
    /**
     * View the cart JSON data (used by Vue storefront via Inertia or Axios).
     */
    public function index(Request $request)
    {
        $tenant = app('tenant');
        $cart = session()->get("cart_{$tenant->id}", []);

        return response()->json([
            'cart' => $cart,
            'subtotal' => collect($cart)->sum('subtotal')
        ]);
    }

    /**
     * Add an item to the cart.
     */
    public function add(Request $request)
    {
        $tenant = app('tenant');
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'variant_id' => 'nullable|exists:product_variants,id',
            'quantity'   => 'required|integer|min:1|max:100',
        ]);

        $product = Product::where('tenant_id', $tenant->id)->findOrFail($validated['product_id']);
        $variant = null;
        $price = $product->price;
        $variantName = null;

        if (!empty($validated['variant_id'])) {
            $variant = ProductVariant::where('product_id', $product->id)->findOrFail($validated['variant_id']);
            $price = $variant->effectivePrice();
            $variantName = $variant->option_value;
        }

        $cart = session()->get("cart_{$tenant->id}", []);
        $cartKey = $product->id . '_' . ($variant ? $variant->id : '0');

        if (isset($cart[$cartKey])) {
            $cart[$cartKey]['quantity'] += $validated['quantity'];
            $cart[$cartKey]['subtotal'] = $cart[$cartKey]['quantity'] * $price;
        } else {
            $cart[$cartKey] = [
                'product_id'   => $product->id,
                'variant_id'   => $variant ? $variant->id : null,
                'name'         => $product->name,
                'variant_name' => $variantName,
                'image'        => $product->main_image,
                'price'        => $price,
                'quantity'     => $validated['quantity'],
                'subtotal'     => $validated['quantity'] * $price,
            ];
        }

        session()->put("cart_{$tenant->id}", $cart);

        return back()->with('success', 'Item added to cart!');
    }

    /**
     * Remove an item from the cart.
     */
    public function remove(Request $request, string $key)
    {
        $tenant = app('tenant');
        $cart = session()->get("cart_{$tenant->id}", []);

        if (isset($cart[$key])) {
            unset($cart[$key]);
            session()->put("cart_{$tenant->id}", $cart);
        }

        return back();
    }

    /**
     * Apply a coupon to the cart.
     */
    public function applyCoupon(Request $request)
    {
        $tenant = app('tenant');
        $validated = $request->validate([
            'code' => 'required|string',
        ]);

        $coupon = \App\Models\Coupon::where('tenant_id', $tenant->id)
            ->where('code', strtoupper($validated['code']))
            ->first();

        if (!$coupon || !$coupon->isValid()) {
            return back()->with('error', 'Invalid or expired coupon code.');
        }

        session()->put("coupon_{$tenant->id}", $coupon->code);

        return back()->with('success', 'Coupon applied successfully!');
    }

    /**
     * Remove the coupon from the cart.
     */
    public function removeCoupon(Request $request)
    {
        $tenant = app('tenant');
        session()->forget("coupon_{$tenant->id}");

        return back()->with('success', 'Coupon removed.');
    }
}
