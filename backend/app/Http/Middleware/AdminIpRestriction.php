<?php
// V-FINAL-1730-265

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminIpRestriction
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Check if whitelist is enabled/populated
        $whitelistStr = setting('admin_ip_whitelist'); // stored as comma-separated string
        
        if (empty($whitelistStr)) {
            return $next($request); // No restriction if empty
        }

        // 2. Parse IPs (handle spaces/newlines)
        $allowedIps = array_map('trim', explode(',', $whitelistStr));
        $clientIp = $request->ip();

        // 3. Check IP
        if (!in_array($clientIp, $allowedIps)) {
            // Allow local dev to bypass if needed, or handle strict
            if (app()->environment('local') && $clientIp === '127.0.0.1') {
                return $next($request);
            }

            return response()->json([
                'message' => 'Access Denied. Your IP is not whitelisted for Admin access.',
                'your_ip' => $clientIp
            ], 403);
        }

        return $next($request);
    }
}