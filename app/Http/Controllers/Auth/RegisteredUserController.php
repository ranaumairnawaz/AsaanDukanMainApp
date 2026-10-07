<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Theme;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(Request $request): Response
    {
        $planSlug = $request->query('plan', 'basic');
        $plan = \App\Models\SubscriptionPlan::with('features')->where('slug', $planSlug)->first();

        // Build a map of feature_key => value for the frontend
        $planFeatures = [];
        if ($plan) {
            foreach ($plan->features as $f) {
                $planFeatures[$f->feature_key] = in_array($f->feature_value, ['1', 'true', 'yes']);
            }
        }

        $themes = Theme::all();
        return Inertia::render('Auth/Register', [
            'plan_slug'     => $planSlug,
            'plan_features' => $planFeatures,
            'themes'        => $themes,
        ]);
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            // User validation
            'name'     => 'required|string|max:255',
            'email'    => 'required|string|lowercase|email|max:255|unique:' . User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'phone'    => 'required|string|max:30',

            // Tenant validation
            'business_name' => 'required|string|max:100',
            'store_slug'    => 'required|string|lowercase|max:50|unique:tenants,store_slug',
            'category'      => 'required|string|max:50',

            // Theme selection — validated further below based on plan
            'theme_slug' => 'required|string',

            // Optional selected plan
            'plan_slug' => 'nullable|string|exists:subscription_plans,slug',
        ]);

        $planSlug = $request->plan_slug ?? 'basic';
        $plan     = \App\Models\SubscriptionPlan::with('features')->where('slug', $planSlug)->first();

        // Resolve plan feature flags
        $planFeatureMap = [];
        if ($plan) {
            foreach ($plan->features as $f) {
                $planFeatureMap[$f->feature_key] = in_array($f->feature_value, ['1', 'true', 'yes']);
            }
        }

        $premiumThemesAllowed = $planFeatureMap['premium_themes_allowed'] ?? false;
        $subdomainAllowed     = $planFeatureMap['subdomain_allowed'] ?? false;

        $premiumSlugs = ['fashion', 'cosmetics', 'electronics', 'decor'];
        $allowedSlugs = $premiumThemesAllowed
            ? array_merge(['basic'], $premiumSlugs)
            : ['basic'];

        if (!in_array($request->theme_slug, array_merge(['basic'], $premiumSlugs))) {
            throw ValidationException::withMessages(['theme_slug' => 'Invalid theme selected.']);
        }

        // If the user picked a premium theme but the plan doesn't allow it, silently fall back to basic
        if (!in_array($request->theme_slug, $allowedSlugs)) {
            $request->merge(['theme_slug' => 'basic']);
        }

        // 1. Create Tenant
        $tenant = \App\Models\Tenant::create([
            'name'                    => $request->business_name,
            'store_slug'              => $request->store_slug,
            'subdomain'               => $subdomainAllowed ? $request->store_slug : null,
            'subdomain_status'        => $subdomainAllowed ? 'pending' : 'none',
            'category'                => $request->category,
            'status'                  => 'trial',
            'subscription_plan_id'    => $plan?->id,
            'subscription_expires_at' => now()->addDays(7),
        ]);

        // 2. Create User linked to Tenant
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone' => $request->phone,
            'tenant_id' => $tenant->id,
            'is_admin' => false,
        ]);

        // 3. Create default Theme Settings
        $defaultAccents = [
            'fashion' => '#10b981', // emerald
            'cosmetics' => '#ec4899', // pink
            'electronics' => '#3b82f6', // blue
            'decor' => '#f59e0b', // amber
        ];

        \App\Models\TenantThemeSettings::create([
            'tenant_id' => $tenant->id,
            'theme_slug' => $request->theme_slug,
            'accent_color' => $defaultAccents[$request->theme_slug] ?? '#10b981',
            'custom_settings' => [
                'banner_text' => 'Welcome to ' . $tenant->name,
                'store_description' => 'Browse our catalog and order directly on WhatsApp!',
            ],
            'theme_last_changed_at' => now(),
        ]);

        // 4. Create initial Message Usage
        \App\Models\TenantMessageUsage::create([
            'tenant_id' => $tenant->id,
            'billing_period_start' => now(),
            'billing_period_end' => now()->addDays(7),
            'messages_sent_count' => 0,
            'quota_limit' => 100, // standard trial limit
            'quota_exceeded_action' => 'charge_overage',
            'overage_count' => 0,
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('merchant.dashboard', absolute: false));
    }

    /**
     * Check if a store slug is available.
     */
    public function checkSlug(Request $request): \Illuminate\Http\JsonResponse
    {
        $slug = strtolower(trim($request->slug));

        $reserved = ['admin', 'api', 'store', 'dashboard', 'merchant', 'login', 'register', 'logout', 'plans', 'whatsapp'];

        if (in_array($slug, $reserved)) {
            return response()->json(['available' => false, 'message' => 'This store name is reserved.']);
        }

        if (!preg_match('/^[a-z0-9\-]+$/', $slug)) {
            return response()->json(['available' => false, 'message' => 'Use only letters, numbers, and dashes.']);
        }

        $exists = \App\Models\Tenant::where('store_slug', $slug)->exists();

        return response()->json([
            'available' => !$exists,
            'message' => $exists ? 'This store name is already taken.' : 'Store name is available!',
        ]);
    }
}
