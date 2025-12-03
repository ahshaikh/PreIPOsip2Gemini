<?php
// V-FINAL-1730-434 (Created) | V-FINAL-1730-442 (SEC-8 Hardened)
// V-FINAL-1730-460 (Centralized Rate Limiting Integration)
// RouteServiceProvider with SEC-8 hardened flows and centralized policy map, compliance and role-aware overrides

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to your application's "home" route.
     *
     * @var string
     */
    public const HOME = '/dashboard';

    /**
     * Bootstraps route bindings, filters, and configuration.
     */
    public function boot(): void
    {
        // --- Centralized Rate Limiting Loader ---
        $this->configureRateLimiting();

        // --- Route Definitions ---
        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }

    /**
     * Configure rate limiting by loading policies from config.
     *
     * Each policy is defined in `config/rate-limiting.php` as plain values.
     * Closures are registered here using those values, ensuring config
     * remains serializable for `php artisan config:cache`.
     */
    protected function configureRateLimiting(): void
    {
        $policies = config('rate-limiting');

        // LOGIN
        RateLimiter::for('login', function ($request) use ($policies) {
            $key = strtolower($request->input('login', '')) . '|' . $request->ip();
            return Limit::perMinute($policies['login']['attempts'])->by($key);
        });

        // API
        RateLimiter::for('api', function ($request) use ($policies) {
            $user = $request->user();
            if ($user && $user->hasRole('Super Admin')) {
                return Limit::none();
            }
            $limit = $user && $user->hasRole(['Admin', 'KYC Officer', 'Support Agent', 'Content Manager', 'Finance Manager'])
                ? $policies['api']['admin']
                : $policies['api']['default'];
            return Limit::perMinute($limit)->by($user?->id ?? $request->ip());
        });

        // FINANCIAL
        RateLimiter::for('financial', function ($request) use ($policies) {
            $user = $request->user();
            if ($user && $user->hasRole('Super Admin')) {
                return Limit::none();
            }
            $limit = $user && $user->hasRole(['Admin', 'Finance Manager'])
                ? $policies['financial']['admin']
                : $policies['financial']['default'];
            return Limit::perMinute($limit)->by($user?->id ?? $request->ip())
                ->response(fn () => response()->json([
                    'message' => 'Too many financial requests. Please wait before trying again.'
                ], 429));
        });

        // REPORTS
        RateLimiter::for('reports', function ($request) use ($policies) {
            $user = $request->user();
            if ($user && $user->hasRole('Super Admin')) {
                return Limit::none();
            }
            $limit = $user && $user->hasRole(['Admin', 'Finance Manager'])
                ? $policies['reports']['admin']
                : $policies['reports']['default'];
            return Limit::perHour($limit)->by($user?->id ?? $request->ip())
                ->response(fn () => response()->json([
                    'message' => 'Too many report requests. Please wait before generating more reports.'
                ], 429));
        });

        // DATA-HEAVY
        RateLimiter::for('data-heavy', function ($request) use ($policies) {
            $user = $request->user();
            if ($user && $user->hasRole('Super Admin')) {
                return Limit::none();
            }
            $limit = $user && $user->hasRole(['Admin', 'KYC Officer', 'Support Agent', 'Content Manager', 'Finance Manager'])
                ? $policies['data-heavy']['admin']
                : $policies['data-heavy']['default'];
            return Limit::perMinute($limit)->by($user?->id ?? $request->ip())
                ->response(fn () => response()->json([
                    'message' => 'Too many requests. Please wait before trying again.'
                ], 429));
        });

        // ADMIN-ACTIONS
        RateLimiter::for('admin-actions', function ($request) use ($policies) {
            $user = $request->user();
            if ($user && $user->hasRole('Super Admin')) {
                return Limit::none();
            }
            $limit = $user && $user->hasRole(['Admin', 'KYC Officer', 'Support Agent', 'Content Manager', 'Finance Manager'])
                ? $policies['admin-actions']['admin']
                : $policies['admin-actions']['default'];
            return Limit::perMinute($limit)->by($user?->id ?? $request->ip())
                ->response(fn () => response()->json([
                    'message' => 'Too many admin actions. Please wait before trying again.'
                ], 429));
        });
    }
}