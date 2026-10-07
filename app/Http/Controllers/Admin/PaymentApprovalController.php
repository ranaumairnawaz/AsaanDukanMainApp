<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPayment;
use App\Models\Tenant;
use App\Models\TenantMessageUsage;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PaymentApprovalController extends Controller
{
    /**
     * Show all pending payments for admin approval.
     */
    public function index()
    {
        $payments = SubscriptionPayment::with(['tenant', 'plan'])
            ->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")
            ->orderBy('created_at', 'desc')
            ->paginate(25);

        return Inertia::render('Admin/Payments/Index', [
            'payments' => $payments,
        ]);
    }

    /**
     * Approve a payment and extend the tenant's subscription.
     */
    public function approve(Request $request, SubscriptionPayment $payment)
    {
        if ($payment->status !== 'pending') {
            return back()->with('error', 'This payment has already been processed.');
        }

        $payment->update([
            'status' => 'approved',
        ]);

        // Extend the tenant's subscription — 30 days from today (approval date)
        $tenant = $payment->tenant;
        $newExpiry = now()->addDays(30);

        $tenant->update([
            'status' => 'active',
            'subscription_plan_id' => $payment->plan_id,
            'subscription_expires_at' => $newExpiry,
            'billing_cycle_start' => now(), // Reset cycle start so next period counts from today
        ]);

        // Reset WhatsApp message overage counters for the new billing period
        TenantMessageUsage::where('tenant_id', $tenant->id)->update([
            'overage_count' => 0,
            'messages_sent_count' => 0,
            'billing_period_start' => now(),
            'billing_period_end' => $newExpiry,
        ]);

        return back()->with('success', "Payment approved. {$tenant->name} is now active until {$newExpiry->format('M d, Y')}.");
    }

    /**
     * Reject a payment.
     */
    public function reject(Request $request, SubscriptionPayment $payment)
    {
        $validated = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        $payment->update([
            'status' => 'rejected',
            'admin_notes' => $validated['reason'] ?? 'Rejected by admin.',
        ]);

        return back()->with('success', "Payment from {$payment->tenant->name} has been rejected.");
    }
}
