<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CouponController extends Controller
{
    public function index()
    {
        $tenant = auth()->user()->tenant;
        $coupons = Coupon::where('tenant_id', $tenant->id)->latest()->get();

        return Inertia::render('Merchant/Coupons/Index', [
            'coupons' => $coupons,
        ]);
    }

    public function store(Request $request)
    {
        $tenant = auth()->user()->tenant;

        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:coupons,code,NULL,id,tenant_id,' . $tenant->id,
            'type' => 'required|in:percentage,fixed',
            'value' => 'required|numeric|min:0',
            'usage_limit' => 'nullable|integer|min:1',
            'expires_at' => 'nullable|date',
            'is_active' => 'boolean',
        ]);

        $validated['tenant_id'] = $tenant->id;
        $validated['code'] = strtoupper($validated['code']);

        Coupon::create($validated);

        return back()->with('success', 'Coupon created successfully.');
    }

    public function update(Request $request, Coupon $coupon)
    {
        $tenant = auth()->user()->tenant;

        if ($coupon->tenant_id !== $tenant->id) {
            abort(403);
        }

        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:coupons,code,' . $coupon->id . ',id,tenant_id,' . $tenant->id,
            'type' => 'required|in:percentage,fixed',
            'value' => 'required|numeric|min:0',
            'usage_limit' => 'nullable|integer|min:1',
            'expires_at' => 'nullable|date',
            'is_active' => 'boolean',
        ]);

        $validated['code'] = strtoupper($validated['code']);

        $coupon->update($validated);

        return back()->with('success', 'Coupon updated successfully.');
    }

    public function destroy(Coupon $coupon)
    {
        $tenant = auth()->user()->tenant;

        if ($coupon->tenant_id !== $tenant->id) {
            abort(403);
        }

        $coupon->delete();

        return back()->with('success', 'Coupon deleted.');
    }
}
