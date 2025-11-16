<?php
// V-FINAL-1730-265 (Created) | V-FINAL-1730-446 | V-FINAL-1730-541 (Upgraded)

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Models\IpWhitelist;

class AdminIpRestriction
{
    /**
     * Handle an incoming request for Admin IP Whitelisting (SEC-3).
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Get the allowed IPs from cache (or DB)
        $allowedIps = Cache::rememberForever('ip_whitelist.active', function () {
            return IpWhitelist::where('is_active', true)->pluck('ip_address')->all();
        });
        
        // 2. If the list is empty, the feature is disabled. Allow request.
        if (empty($allowedIps)) {
            return $next($request);
        }

        $clientIp = $request->ip();

        // 3. Check if client IP is in the list (supports CIDR ranges)
        if (IpWhitelist::isIpAllowed($clientIp, $allowedIps)) {
            return $next($request);
        }

        // 4. If in local dev, allow loopback
        if (app()->environment('local') && in_array($clientIp, ['127.0.0.1', '::1'])) {
            return $next($request);
        }

        // 5. BLOCK the request
        Log::warning("Admin Access Blocked: IP {$clientIp} not in whitelist.", [
            'route' => $request->path()
        ]);
        
        return response()->json([
            'message' => 'Access Denied. Your IP address is not whitelisted for admin access.',
            'your_ip' => $clientIp
        ], 403);
    }
}