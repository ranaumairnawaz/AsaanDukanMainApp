<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ensure the authenticated user belongs to a tenant (is a merchant).
 * This also binds the user's tenant into the app container so controllers
 * can call app('tenant') without needing the path-based ResolveTenant.
 */
class MerchantAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        if ($user->is_admin) {
            return redirect()->route('admin.dashboard');
        }

        if (!$user->tenant_id) {
            abort(403, 'No store linked to this account.');
        }

        // Bind tenant for controllers to use via app('tenant')
        if (!app()->bound('tenant')) {
            $tenant = $user->tenant()->with('subscriptionPlan', 'themeSettings')->first();
            app()->instance('tenant', $tenant);
        } else {
            $tenant = app('tenant');
        }

        // Subscription check
        $isExpired = $tenant->status === 'suspended' || ($tenant->subscription_expires_at && $tenant->subscription_expires_at->isPast());
        
        if ($isExpired) {
            $allowedRoutes = [
                'merchant.billing',
                'merchant.payment.create',
                'merchant.payment.bank',
                'merchant.payment.wallet',
                'logout',
            ];

            $currentRoute = $request->route() ? $request->route()->getName() : null;

            if (!in_array($currentRoute, $allowedRoutes)) {
                return redirect()->route('merchant.billing')
                    ->with('error', 'Your subscription has expired. Please make a payment to renew your access.');
            }
        }

        return $next($request);
    }
}
