<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPlan;
use App\Models\PlanFeature;
use App\Services\FeatureRegistry;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PlanController extends Controller
{
    public function index()
    {
        $plans = SubscriptionPlan::with('features')
            ->orderBy('sort_order')
            ->get();

        return Inertia::render('Admin/Plans/Index', [
            'plans' => $plans,
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Plans/Form', [
            'feature_registry' => FeatureRegistry::all(),
            'plan' => null,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'slug' => 'required|string|unique:subscription_plans,slug|max:50',
            'price_monthly' => 'required|numeric|min:0',
            'price_yearly' => 'required|numeric|min:0',
            'sort_order' => 'integer|min:0',
            'is_active' => 'boolean',
            'features' => 'array',
        ]);

        $plan = SubscriptionPlan::create($request->only([
            'name', 'slug', 'price_monthly', 'price_yearly', 'sort_order', 'is_active',
        ]));

        foreach ($request->features ?? [] as $key => $value) {
            PlanFeature::create([
                'plan_id' => $plan->id,
                'feature_key' => $key,
                'feature_value' => is_bool($value) ? ($value ? '1' : '0') : (string) $value,
            ]);
        }

        return redirect()->route('admin.plans.index')->with('success', 'Plan created successfully.');
    }

    public function edit(SubscriptionPlan $plan)
    {
        $plan->load('features');

        // Build a key=>value map for easy access in the form
        $featureMap = $plan->features->pluck('feature_value', 'feature_key');

        return Inertia::render('Admin/Plans/Form', [
            'feature_registry' => FeatureRegistry::all(),
            'plan' => $plan,
            'feature_values' => $featureMap,
        ]);
    }

    public function update(Request $request, SubscriptionPlan $plan)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'price_monthly' => 'required|numeric|min:0',
            'price_yearly' => 'required|numeric|min:0',
            'sort_order' => 'integer|min:0',
            'is_active' => 'boolean',
            'features' => 'array',
        ]);

        $plan->update($request->only([
            'name', 'price_monthly', 'price_yearly', 'sort_order', 'is_active',
        ]));

        foreach ($request->features ?? [] as $key => $value) {
            PlanFeature::updateOrCreate(
                ['plan_id' => $plan->id, 'feature_key' => $key],
                ['feature_value' => is_bool($value) ? ($value ? '1' : '0') : (string) $value]
            );
        }

        return redirect()->route('admin.plans.index')->with('success', 'Plan updated successfully.');
    }

    public function destroy(SubscriptionPlan $plan)
    {
        if ($plan->tenants()->exists()) {
            return back()->withErrors(['error' => 'Cannot delete a plan that has active tenants.']);
        }

        $plan->delete();

        return redirect()->route('admin.plans.index')->with('success', 'Plan deleted.');
    }
}
