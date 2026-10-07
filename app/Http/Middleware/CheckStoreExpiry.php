<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckStoreExpiry
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = app('tenant');

        if ($tenant) {
            // 1. Check if the status is valid
            $hasValidStatus = $tenant->status === 'active' || $tenant->status === 'trial';

            // 2. Check if the subscription has not expired yet
            // (Returns true if subscription_expires_at is null OR is a future date)
            $isNotExpired = is_null($tenant->subscription_expires_at) || $tenant->subscription_expires_at->isFuture();

            // If both conditions are met, let them through
            if ($hasValidStatus && $isNotExpired) {
                return $next($request);
            }

            // If they fail the checks, send them to the expired view
            return response()->view('storefront.expired', ['tenant' => $tenant], 403);
        }

        return $next($request);
    }
}