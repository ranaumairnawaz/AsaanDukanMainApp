<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\AdminCoupon;
use App\Models\SubscriptionPayment;
use App\Services\PlanFeatureService;
use App\Services\WhatsAppMessageService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class PaymentController extends Controller
{
    /**
     * Show payment form for subscription renewal.
     */
    public function create(Request $request)
    {
        $tenant = auth()->user()->tenant;

        // Allow upgrading to a different plan via ?plan_id=
        $plan = $request->filled('plan_id')
            ? \App\Models\SubscriptionPlan::findOrFail($request->plan_id)
            : $tenant->subscriptionPlan;

        $planFeatureService = new PlanFeatureService();

        $overagePrice = $planFeatureService->getValue($tenant, 'whatsapp_overage_price_per_message') ?? 2.00;
        $usage = $tenant->messageUsages()->orderBy('created_at', 'desc')->first();
        $overageCost = ($usage?->overage_count ?? 0) * $overagePrice;
        $totalDue = ($plan?->price_monthly ?? 0) + $overageCost;

        $bankAccounts = \App\Models\GlobalSetting::getVal('payment_accounts', []);

        return Inertia::render('Merchant/Billing/Pay', [
            'tenant'        => $tenant,
            'plan'          => $plan,
            'total_due'     => $totalDue,
            'overage_cost'  => $overageCost,
            'plan_price'    => $plan?->price_monthly ?? 0,
            'bank_accounts' => $bankAccounts,
            'is_upgrade'    => $plan?->id !== $tenant->subscriptionPlan?->id,
        ]);
    }

    public function validateCoupon(Request $request)
    {
        $request->validate(['code' => 'required|string']);
        $coupon = AdminCoupon::where('code', strtoupper($request->code))->first();

        if (!$coupon || !$coupon->isValid()) {
            return response()->json(['error' => 'Invalid or expired coupon code'], 400);
        }

        return response()->json([
            'id' => $coupon->id,
            'code' => $coupon->code,
            'discount_type' => $coupon->discount_type,
            'discount_amount' => $coupon->discount_amount
        ]);
    }

    /**
     * Submit a bank transfer payment for admin approval.
     */
    public function storeBankTransfer(Request $request)
    {
        $tenant = auth()->user()->tenant;

        $validated = $request->validate([
            'proof_image' => 'required|image|max:5120',
            'notes'       => 'nullable|string|max:1000',
            'plan_id'     => 'nullable|integer|exists:subscription_plans,id',
            'coupon_code' => 'nullable|string',
        ]);

        // Support upgrading to a different plan
        $plan = $validated['plan_id']
            ? \App\Models\SubscriptionPlan::findOrFail($validated['plan_id'])
            : $tenant->subscriptionPlan;

        $proofPath = null;
        if ($request->hasFile('proof_image')) {
            $proofPath = $request->file('proof_image')->store("payments/{$tenant->id}", 'public');
        }

        $planFeatureService = new PlanFeatureService();
        $overagePrice = $planFeatureService->getValue($tenant, 'whatsapp_overage_price_per_message') ?? 2.00;
        $usage = $tenant->messageUsages()->orderBy('created_at', 'desc')->first();
        $overageCost = ($usage?->overage_count ?? 0) * $overagePrice;
        $totalDue = ($plan?->price_monthly ?? 0) + $overageCost;

        $discount = 0;
        $adminCouponId = null;
        if ($validated['coupon_code']) {
            $coupon = AdminCoupon::where('code', strtoupper($validated['coupon_code']))->first();
            if ($coupon && $coupon->isValid()) {
                $discount = $coupon->calculateDiscount($totalDue);
                $totalDue = max(0, $totalDue - $discount);
                $adminCouponId = $coupon->id;
                $coupon->increment('used_count');
            }
        }

        SubscriptionPayment::create([
            'tenant_id'            => $tenant->id,
            'plan_id'              => $plan->id,
            'amount'               => $totalDue,
            'admin_coupon_id'      => $adminCouponId,
            'discount_applied'     => $discount,
            'payment_method'       => 'bank_transfer',
            'status'               => 'pending',
            'gateway_reference'    => 'BT-' . strtoupper(Str::random(8)),
            'billing_period_start' => now(),
            'billing_period_end'   => now()->addDays(30),
            'proof_image_path'     => $proofPath,
        ]);

        // Notify admin via WhatsApp
        try {
            $payment = SubscriptionPayment::where('tenant_id', $tenant->id)->latest()->first();
            if ($payment) {
                (new WhatsAppMessageService())->notifyAdminNewPayment($payment->load(['tenant', 'plan']));
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Admin WA notification failed: ' . $e->getMessage());
        }

        return redirect()->route('merchant.billing')
            ->with('success', 'Payment proof submitted. Waiting for admin approval.');
    }

    /**
     * Process JazzCash / Easypaisa payment (simulation for now).
     */
    public function processMobileWallet(Request $request)
    {
        $tenant = auth()->user()->tenant;
        $plan = $tenant->subscriptionPlan;

        $validated = $request->validate([
            'wallet_type' => 'required|in:jazzcash,easypaisa',
            'phone'       => 'required|string|max:20',
            'plan_id'     => 'nullable|integer|exists:subscription_plans,id',
            'coupon_code' => 'nullable|string',
        ]);

        $plan = $validated['plan_id']
            ? \App\Models\SubscriptionPlan::findOrFail($validated['plan_id'])
            : $tenant->subscriptionPlan;

        $planFeatureService = new PlanFeatureService();
        $overagePrice = $planFeatureService->getValue($tenant, 'whatsapp_overage_price_per_message') ?? 2.00;
        $usage = $tenant->messageUsages()->orderBy('created_at', 'desc')->first();
        $overageCost = ($usage?->overage_count ?? 0) * $overagePrice;
        $totalDue = ($plan?->price_monthly ?? 0) + $overageCost;

        $discount = 0;
        $adminCouponId = null;
        if ($validated['coupon_code']) {
            $coupon = AdminCoupon::where('code', strtoupper($validated['coupon_code']))->first();
            if ($coupon && $coupon->isValid()) {
                $discount = $coupon->calculateDiscount($totalDue);
                $totalDue = max(0, $totalDue - $discount);
                $adminCouponId = $coupon->id;
                $coupon->increment('used_count');
            }
        }

        // In production, you'd call the JazzCash/Easypaisa API here.
        // For now, we simulate a "pending" payment that the admin verifies.
        $payment = SubscriptionPayment::create([
            'tenant_id'            => $tenant->id,
            'plan_id'              => $plan->id,
            'amount'               => $totalDue,
            'admin_coupon_id'      => $adminCouponId,
            'discount_applied'     => $discount,
            'payment_method'       => $validated['wallet_type'],
            'status'               => 'pending',
            'gateway_reference'    => strtoupper($validated['wallet_type']) . '-' . strtoupper(Str::random(8)),
            'billing_period_start' => now(),
            'billing_period_end'   => now()->addDays(30),
        ]);

        return redirect()->route('merchant.billing')
            ->with('success', "Payment request submitted via {$validated['wallet_type']}. Reference: {$payment->gateway_reference}");
    }
}
