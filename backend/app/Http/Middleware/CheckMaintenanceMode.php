<?php
// V-FINAL-1730-266 (Created) | V-FINAL-1730-422 (Logic Upgraded) | 
// V-FINAL-1730-626 (Boot-loader Fix) | STABLE-PATCH-2025-11

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class CheckMaintenanceMode
{
    /**
     * Local safe version of the setting() helper to avoid bootstrapping issues.
     * This ensures maintenance mode check works even when global helpers fail.
     */
    private function getSetting($key, $default = null)
    {
        try {
            $setting = Cache::rememberForever('setting.' . $key, function () use ($key) {
                return Setting::where('key', $key)->first();
            });

            if (!$setting) {
                return $default;
            }

            // Cast boolean types manually
            if ($setting->type === 'boolean') {
                return in_array($setting->value, ['true', '1', 1, true], true);
            }

            return $setting->value;
        } catch (\Exception $e) {
            return $default;
        }
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Maintenance mode OFF → allow everything
        if (!$this->getSetting('maintenance_mode', false)) {
            return $next($request);
        }

        // 2. Admin users bypass maintenance
        $user = $request->user();
        if ($user && $user->hasRole(['admin', 'super-admin'])) {
            return $next($request);
        }

        // 3. IP whitelist support
        $whitelistStr = $this->getSetting('allowed_ips', '');
        if (!empty($whitelistStr)) {
            $allowedIps = array_map('trim', explode(',', $whitelistStr));
            if (in_array($request->ip(), $allowedIps)) {
                return $next($request);
            }
        }

        // 4. Minimal required API exemptions (SAFE)
        if (
            $request->is('api/v1/login')      ||
            $request->is('api/v1/register')   ||
            $request->is('api/v1/verify-otp') ||
            $request->is('api/v1/admin*')     ||
            $request->is('sanctum/*')
        ) {
            return $next($request);
        }

        // 5. Everything else blocked during maintenance
        return response()->json([
            'message' => $this->getSetting('maintenance_message', 'System is down for maintenance. Please try again later.'),
            'maintenance' => true
        ], 503);
    }
}
