<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveTenant
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $host = $request->getHost();
        $appUrl = config('app.url', 'http://localhost');
        if (!str_starts_with($appUrl, 'http://') && !str_starts_with($appUrl, 'https://')) {
            $appUrl = 'http://' . $appUrl;
        }
        $baseDomain = parse_url($appUrl, PHP_URL_HOST) ?? 'localhost';
        
        $tenant = null;

        // 1. Try Custom Domain Check
        if ($host !== $baseDomain && !str_ends_with($host, '.' . $baseDomain)) {
            $tenant = Tenant::where('custom_domain', $host)
                ->where('status', '!=', 'suspended')
                ->first();
        }

        // 2. Try Subdomain Check
        if (!$tenant && str_ends_with($host, '.' . $baseDomain)) {
            $subdomain = str_replace('.' . $baseDomain, '', $host);
            $tenant = Tenant::where('subdomain', $subdomain)
                ->where('subdomain_status', 'active')
                ->where('status', '!=', 'suspended')
                ->first();
        }

        // 3. Try Path Prefix Check
        if (!$tenant) {
            // Path structure: /store/{store_slug}/...
            $segments = $request->segments();
            if (count($segments) >= 2 && $segments[0] === 'store') {
                $storeSlug = $segments[1];
                $tenant = Tenant::where('store_slug', $storeSlug)
                    ->where('status', '!=', 'suspended')
                    ->first();
            }
        }

        if ($tenant) {
            // Bind the tenant model instance to the container
            app()->instance('tenant', $tenant);
            
            // Share the tenant globally with all views
            view()->share('tenant', $tenant);
        }

        return $next($request);
    }
}
