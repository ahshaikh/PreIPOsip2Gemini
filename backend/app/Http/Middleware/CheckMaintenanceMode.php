<?php
// V-FINAL-1730-266

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckMaintenanceMode
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Check if maintenance is ON
        if (setting('maintenance_mode', false)) {
            
            // 2. Define Exemptions
            // Admins must be able to login and access admin panel to turn it OFF
            if ($request->is('api/v1/admin*') || 
                $request->is('api/v1/login') || 
                $request->is('api/v1/logout') ||
                $request->is('sanctum/*')) {
                return $next($request);
            }

            // 3. Block everyone else
            return response()->json([
                'message' => 'The system is currently under maintenance. Please try again later.',
                'maintenance' => true
            ], 503);
        }

        return $next($request);
    }
}