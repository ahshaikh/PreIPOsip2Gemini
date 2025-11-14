<?php
// V-FINAL-1730-266 (Created) | V-FINAL-1730-422 (Logic Upgraded)

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
        // 1. Is Maintenance Mode OFF? If so, let everyone in.
        if (!setting('maintenance_mode', false)) {
            return $next($request);
        }
        
        // --- Maintenance Mode is ON ---
        
        $user = $request->user();
        
        // 2. Check for Admin Exemption
        // If the user is logged in AND has an admin-level role.
        if ($user && $user->hasRole(['admin', 'super-admin'])) {
            return $next($request);
        }
        
        // 3. Check for IP Whitelist Exemption
        // FSD-SYS-103: allow specific IPs to bypass
        $whitelistStr = setting('allowed_ips', '');
        if (!empty($whitelistStr)) {
            $allowedIps = array_map('trim', explode(',', $whitelistStr));
            if (in_array($request->ip(), $allowedIps)) {
                return $next($request);
            }
        }
        
        // 4. Check for Login/Admin Route Exemption
        // (Allows admins to *reach* the login page to sign in)
        if ($request->is('api/v1/admin*') || 
            $request->is('api/v1/login') ||
            $request->is('sanctum/*')) {
            return $next($request);
        }

        // 5. If none of the above, block the user.
        return response()->json([
            'message' => setting('maintenance_message', 'System is down for maintenance. Please try again later.'),
            'maintenance' => true
        ], 503); // 503 Service Unavailable
    }
}