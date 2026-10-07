<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckMaintenance
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        // Check if registration is disabled
        if ($request->is('api/merchant/register') && \App\Models\GlobalSetting::getVal('maintenance_disable_register', '0') === '1') {
            return response()->json([
                'success' => false,
                'message' => 'New registrations are currently disabled for maintenance. Please check back later.'
            ], 503);
        }

        // Check if login is disabled
        if ($request->is('api/merchant/login') && \App\Models\GlobalSetting::getVal('maintenance_mode_enabled', '0') === '1') {
            return response()->json([
                'success' => false,
                'message' => 'The application is currently under maintenance. Login is temporarily disabled.'
            ], 503);
        }

        return $next($request);
    }
}
