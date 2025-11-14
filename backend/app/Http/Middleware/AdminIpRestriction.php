<?php
// V-FINAL-1730-265 (Created) | V-FINAL-1730-446 

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

class AdminIpRestriction
{
    /**
     * Handle an incoming request for Admin IP Whitelisting (SEC-3).
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Get the whitelist from settings.
        $whitelistStr = setting('admin_ip_whitelist');
        
        // 2. If the list is empty, the feature is disabled. Allow request.
        if (empty($whitelistStr)) {
            return $next($request);
        }

        // 3. Parse the list
        $allowedIps = array_map('trim', explode(',', $whitelistStr));
        $clientIp = $request->ip();

        // 4. Check if client IP is in the list
        if (in_array($clientIp, $allowedIps)) {
            return $next($request);
        }

        // 5. If in local dev, allow loopback
        if (app()->environment('local') && in_array($clientIp, ['127.0.0.1', '::1'])) {
            return $next($request);
        }

        // 6. BLOCK the request
        Log::warning("Admin Access Blocked: IP {$clientIp} not in whitelist.", [
            'route' => $request->path()
        ]);
        
        return response()->json([
            'message' => 'Access Denied. Your IP address is not whitelisted for admin access.',
            'your_ip' => $clientIp
        ], 403);
    }
}