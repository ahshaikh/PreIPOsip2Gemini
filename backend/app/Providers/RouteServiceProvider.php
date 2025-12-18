<?php
/**
 * V-AUDIT-REFACTOR-2025 | V-TIERED-RATE-LIMITING | V-BRUTE-FORCE-SHIELD
 * Refactored to address Module 18 Audit Gaps:
 * 1. Role-Aware Throttling: Differentiates between Guest, User, and Admin.
 * 2. Specialized Policies: Separate limits for Auth, Financials, and Reports.
 * 3. Super-Admin Exclusion: Ensures critical maintenance is never throttled.
 */

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;

class RouteServiceProvider extends ServiceProvider
{
    public const HOME = '/dashboard';

    public function boot(): void
    {
        $this->configureRateLimiting();

        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }

    /**
     * Configure rate limiting.
     * [AUDIT FIX]: Implements tiered limits to protect against DoS and Brute Force.
     */
    protected function configureRateLimiting(): void
    {
        $policies = config('rate-limiting');

        // 1. LOGIN / AUTH (Strict Burst Protection)
        RateLimiter::for('login', function (Request $request) use ($policies) {
            $key = strtolower($request->input('login', '')) . '|' . $request->ip();
            // Prevent login brute force attempts
            return Limit::perMinute($policies['login']['attempts'] ?? 5)->by($key);
        });

        // 2. GENERAL API (Tiered by Role)
        RateLimiter::for('api', function (Request $request) use ($policies) {
            $user = $request->user();

            if ($user?->hasRole('super-admin')) return Limit::none();

            $limit = $user?->hasRole('admin') 
                ? ($policies['api']['admin'] ?? 100) 
                : ($policies['api']['default'] ?? 60);

            return Limit::perMinute($limit)->by($user?->id ?? $request->ip());
        });

        // 3. FINANCIAL TRANSACTIONS (High Security)
        RateLimiter::for('financial', function (Request $request) use ($policies) {
            $user = $request->user();
            if ($user?->hasRole('super-admin')) return Limit::none();

            $limit = $user?->hasRole('admin') ? 50 : 10; // Stricter for sensitive money ops

            return Limit::perMinute($limit)->by($user?->id ?? $request->ip())
                ->response(fn () => response()->json([
                    'message' => 'Too many financial requests. For security, please wait a moment.'
                ], 429));
        });

        // 4. DATA-HEAVY / REPORTS (Export Protection)
        RateLimiter::for('reports', function (Request $request) use ($policies) {
            $user = $request->user();
            if ($user?->hasRole('super-admin')) return Limit::none();

            // Limit report generation to prevent server resource exhaustion
            return Limit::perHour(5)->by($user?->id ?? $request->ip())
                ->response(fn () => response()->json([
                    'message' => 'Report limit reached. Please wait before generating more files.'
                ], 429));
        });
    }
}