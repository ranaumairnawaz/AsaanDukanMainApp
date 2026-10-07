<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\Order;
use App\Models\SubscriptionPlan;
use Inertia\Inertia;

class AdminDashboardController extends Controller
{
    public function index()
    {
        $dates = collect();
        for ($i = 6; $i >= 0; $i--) {
            $dates->push(now()->subDays($i)->format('Y-m-d'));
        }
        $registrations = Tenant::where('created_at', '>=', now()->subDays(7)->startOfDay())
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->pluck('count', 'date');

        $chartData = [];
        foreach ($dates as $date) {
            $chartData[] = [
                'date' => \Carbon\Carbon::parse($date)->format('M d'),
                'tenants' => $registrations[$date] ?? 0
            ];
        }

        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'total_tenants'   => Tenant::count(),
                'active_tenants'  => Tenant::where('status', 'active')->count(),
                'trial_tenants'   => Tenant::where('status', 'trial')->count(),
                'suspended'       => Tenant::where('status', 'suspended')->count(),
                'pending_subdomains' => Tenant::where('subdomain_status', 'pending')->count(),
                'active_numbers'  => 0,
                'total_numbers'   => 0,
            ],
            'chart_data' => $chartData,
        ]);
    }
}
