<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireTenant
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!app()->bound('tenant')) {
            abort(404, 'Store not found.');
        }

        $request->route()->forgetParameter('store_slug');
        $request->route()->forgetParameter('tenant_domain');

        return $next($request);
    }
}
