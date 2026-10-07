<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Order;
use App\Services\ThemeRegistry;
use App\Services\PlanFeatureService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request, PlanFeatureService $planService)
    {
        $tenant = auth()->user()->tenant;
        $analyticsEnabled = $planService->isEnabled($tenant, 'analytics_enabled');

        // Ensure we scope products to the current tenant
        $stats = [
            'total_products'  => Product::where('tenant_id', $tenant->id)->count(),
            'active_products' => Product::where('tenant_id', $tenant->id)->where('is_active', true)->count(),
            'total_orders'    => Order::where('tenant_id', $tenant->id)->count(),
            'pending_orders'  => Order::where('tenant_id', $tenant->id)->where('status', 'pending')->count(),
            'today_orders'    => $analyticsEnabled ? Order::where('tenant_id', $tenant->id)->whereDate('created_at', today())->count() : null,
            'total_revenue'   => $analyticsEnabled ? Order::where('tenant_id', $tenant->id)->whereIn('status', ['confirmed', 'processing', 'shipped', 'delivered'])->sum('total') : null,
            'today_revenue'   => $analyticsEnabled ? Order::where('tenant_id', $tenant->id)->whereDate('created_at', today())->whereIn('status', ['confirmed', 'processing', 'shipped', 'delivered'])->sum('total') : null,
        ];

        $recentOrders = Order::where('tenant_id', $tenant->id)
            ->with('customer')
            ->latest()
            ->take(5)
            ->get();

        // Top 5 products by quantity sold
        $topProducts = [];
        $chartData = [];
        if ($analyticsEnabled) {
            $topProducts = \App\Models\OrderItem::selectRaw('product_id, SUM(quantity) as total_sold')
                ->whereHas('order', function($q) use ($tenant) {
                    $q->where('tenant_id', $tenant->id)->whereIn('status', ['confirmed', 'processing', 'shipped', 'delivered']);
                })
                ->with('product:id,name,images')
                ->groupBy('product_id')
                ->orderByDesc('total_sold')
                ->take(5)
                ->get();

            // Chart Data (Last 7 days revenue)
            $dates = collect();
            for ($i = 6; $i >= 0; $i--) {
                $dates->push(now()->subDays($i)->format('Y-m-d'));
            }
            $sales = Order::where('tenant_id', $tenant->id)
                ->where('created_at', '>=', now()->subDays(7)->startOfDay())
                ->whereIn('status', ['confirmed', 'processing', 'shipped', 'delivered'])
                ->selectRaw('DATE(created_at) as date, SUM(total) as revenue')
                ->groupBy('date')
                ->pluck('revenue', 'date');
            foreach ($dates as $date) {
                $chartData[] = [
                    'date' => \Carbon\Carbon::parse($date)->format('M d'),
                    'revenue' => (float) ($sales[$date] ?? 0)
                ];
            }
        }

        $theme = ThemeRegistry::forTenant($tenant);

        return Inertia::render('Merchant/Dashboard', [
            'tenant'        => $tenant->load('subscriptionPlan', 'themeSettings'),
            'stats'         => $stats,
            'recent_orders' => $recentOrders,
            'top_products'  => $topProducts,
            'chart_data'    => $chartData ?? [],
            'theme'         => $theme,
            'analytics_enabled' => $analyticsEnabled,
        ]);
    }
}
