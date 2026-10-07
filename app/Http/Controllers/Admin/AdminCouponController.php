<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminCoupon;
use Illuminate\Http\Request;

class AdminCouponController extends Controller
{
    public function index()
    {
        $coupons = AdminCoupon::latest()->get();
        return inertia('Admin/Coupons/Index', [
            'coupons' => $coupons
        ]);
    }

    public function create()
    {
        return inertia('Admin/Coupons/Form');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|unique:admin_coupons,code',
            'discount_type' => 'required|in:fixed,percentage',
            'discount_amount' => 'required|numeric|min:0.01',
            'max_uses' => 'nullable|integer|min:1',
            'expires_at' => 'nullable|date|after:now',
            'is_active' => 'boolean',
        ]);

        $validated['code'] = strtoupper(trim($validated['code']));

        AdminCoupon::create($validated);

        return redirect()->route('admin.coupons.index')->with('success', 'Coupon created successfully.');
    }

    public function edit(AdminCoupon $coupon)
    {
        return inertia('Admin/Coupons/Form', [
            'coupon' => $coupon
        ]);
    }

    public function update(Request $request, AdminCoupon $coupon)
    {
        $validated = $request->validate([
            'code' => 'required|string|unique:admin_coupons,code,' . $coupon->id,
            'discount_type' => 'required|in:fixed,percentage',
            'discount_amount' => 'required|numeric|min:0.01',
            'max_uses' => 'nullable|integer|min:1',
            'expires_at' => 'nullable|date',
            'is_active' => 'boolean',
        ]);

        $validated['code'] = strtoupper(trim($validated['code']));

        $coupon->update($validated);

        return redirect()->route('admin.coupons.index')->with('success', 'Coupon updated successfully.');
    }

    public function destroy(AdminCoupon $coupon)
    {
        $coupon->delete();
        return redirect()->route('admin.coupons.index')->with('success', 'Coupon deleted successfully.');
    }
}
