<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\SubscriptionPlan;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class SubscriptionController extends Controller
{
    public function index(Request $request)
    {
        $query = Tenant::with(['subscriptionPlan', 'users'])
            ->withCount('orders')
            ->orderBy('subscription_expires_at', 'asc');

        // Filter
        if ($request->filled('plan')) {
            $query->where('subscription_plan_id', $request->plan);
        }
        if ($request->filled('status')) {
            if ($request->status === 'expiring_soon') {
                $query->whereBetween('subscription_expires_at', [now(), now()->addDays(7)]);
            } elseif ($request->status === 'expired') {
                $query->where('subscription_expires_at', '<', now());
            } elseif ($request->status === 'active') {
                $query->where('subscription_expires_at', '>=', now());
            }
        }
        if ($request->filled('search')) {
            $query->where(fn($q) => $q
                ->where('name', 'like', "%{$request->search}%")
                ->orWhere('store_slug', 'like', "%{$request->search}%")
            );
        }

        $tenants = $query->paginate(20)->withQueryString();

        $stats = [
            'total'          => Tenant::count(),
            'active'         => Tenant::where('subscription_expires_at', '>=', now())->count(),
            'expiring_soon'  => Tenant::whereBetween('subscription_expires_at', [now(), now()->addDays(7)])->count(),
            'expired'        => Tenant::where('subscription_expires_at', '<', now())->count(),
        ];

        return Inertia::render('Admin/Subscriptions/Index', [
            'tenants'    => $tenants,
            'plans'      => SubscriptionPlan::orderBy('sort_order')->get(['id', 'name', 'slug']),
            'stats'      => $stats,
            'filters'    => $request->only(['plan', 'status', 'search']),
        ]);
    }

    public function extend(Request $request, Tenant $tenant)
    {
        $validated = $request->validate([
            'days'    => 'required|integer|min:1|max:365',
            'plan_id' => 'nullable|exists:subscription_plans,id',
        ]);

        $base = $tenant->subscription_expires_at && $tenant->subscription_expires_at->isFuture()
            ? $tenant->subscription_expires_at
            : now();

        $tenant->subscription_expires_at = $base->addDays($validated['days']);

        if (!empty($validated['plan_id'])) {
            $tenant->subscription_plan_id = $validated['plan_id'];
        }

        $tenant->save();

        return back()->with('success', "Subscription extended by {$validated['days']} days for {$tenant->name}.");
    }
}
