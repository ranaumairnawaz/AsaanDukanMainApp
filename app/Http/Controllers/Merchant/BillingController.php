<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\SubscriptionPlan;
use App\Models\TenantMessageUsage;
use App\Services\PlanFeatureService;
use Inertia\Inertia;

class BillingController extends Controller
{
    public function index()
    {
        $tenant = auth()->user()->tenant;
        $plan   = $tenant->subscriptionPlan;

        // Get or create current usage period
        $usage = TenantMessageUsage::where('tenant_id', $tenant->id)
            ->orderBy('created_at', 'desc')
            ->first();

        $planFeatureService = new PlanFeatureService();
        $quotaLimit     = $planFeatureService->getValue($tenant, 'whatsapp_messages_per_month') ?? 100;
        $overagePrice   = $planFeatureService->getValue($tenant, 'whatsapp_overage_price_per_message') ?? 2.00;

        $messagesSent  = $usage?->messages_sent_count ?? 0;
        $overageCount  = $usage?->overage_count ?? 0;
        $overageCost   = $overageCount * $overagePrice;
        $usagePct      = $quotaLimit > 0 ? min(100, round(($messagesSent / $quotaLimit) * 100)) : 0;

        // Order stats
        $totalOrders    = Order::where('tenant_id', $tenant->id)->count();
        $pendingOrders  = Order::where('tenant_id', $tenant->id)->where('status', 'pending')->count();
        $totalRevenue   = Order::where('tenant_id', $tenant->id)->whereIn('status', ['confirmed', 'processing', 'shipped', 'delivered'])->sum('total');

        $allPlans = SubscriptionPlan::with('features')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return Inertia::render('Merchant/Billing/Index', [
            'tenant'    => $tenant,
            'plan'      => $plan,
            'all_plans' => $allPlans,
            'billing' => [
                'quota_limit'            => $quotaLimit,
                'messages_sent'          => $messagesSent,
                'overage_count'          => $overageCount,
                'overage_price'          => $overagePrice,
                'overage_cost'           => $overageCost,
                'usage_pct'              => $usagePct,
                'period_start'           => $usage?->billing_period_start,
                'period_end'             => $usage?->billing_period_end,
                'plan_price_monthly'     => $plan?->price_monthly ?? 0,
                'renewal_total'          => ($plan?->price_monthly ?? 0) + $overageCost,
                'subscription_status'    => $tenant->status,
                'subscription_expires_at'=> $tenant->subscription_expires_at?->toDateString(),
            ],
            'stats' => [
                'total_orders'    => $totalOrders,
                'pending_orders'  => $pendingOrders,
                'total_revenue'   => $totalRevenue,
            ],
        ]);
    }
}
