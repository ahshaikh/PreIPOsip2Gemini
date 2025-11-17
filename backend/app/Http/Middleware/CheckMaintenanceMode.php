<?php
// V-FINAL-1730-266 (Created) | V-FINAL-1730-422 (Logic Upgraded) | V-FINAL-1730-626 (Boot-loader Fix)

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Setting; // <-- IMPORT
use Illuminate\Support\Facades\Cache; // <-- IMPORT

class CheckMaintenanceMode
{
    /**
     * A local, safe version of the setting() helper to avoid boot-loading issues.
     * This queries the model directly.
     */
    private function getSetting($key, $default = null)
    {
        try {
            // Use cache to avoid hitting the DB on every single request
            $setting = Cache::rememberForever('setting.' . $key, function () use ($key) {
                return Setting::where('key', $key)->first();
            });

            if (!$setting) {
                return $default; // Not found
            }
            
            // Manually cast boolean types
            if ($setting->type === 'boolean') {
                return in_array($setting->value, ['true', '1', 1, true], true);
            }
            
            return $setting->value;

        } catch (\Exception $e) {
            // Failsafe if DB isn't ready during boot
            return $default;
        }
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Is Maintenance Mode OFF? If so, let everyone in.
        // --- USE THE LOCAL getSetting() METHOD ---
        if (!$this->getSetting('maintenance_mode', false)) {
            return $next($request);
        }
        
        // --- Maintenance Mode is ON ---
        
        $user = $request->user();
        
        // 2. Check for Admin Exemption
        if ($user && $user->hasRole(['admin', 'super-admin'])) {
            return $next($request);
        }
        
        // 3. Check for IP Whitelist Exemption
        $whitelistStr = $this->getSetting('allowed_ips', '');
        if (!empty($whitelistStr)) {
            $allowedIps = array_map('trim', explode(',', $whitelistStr));
            if (in_array($request->ip(), $allowedIps)) {
                return $next($request);
            }
        }
        
        // 4. Check for Login/Admin Route Exemption
        if ($request->is('api/v1/admin*') || 
            $request->is('api/v1/login') ||
            $request->is('sanctum/*')) {
            return $next($request);
        }

        // 5. If none of the above, block the user.
        return response()->json([
            'message' => $this->getSetting('maintenance_message', 'System is down for maintenance. Please try again later.'),
            'maintenance' => true
        ], 503); // 503 Service Unavailable
    }
}