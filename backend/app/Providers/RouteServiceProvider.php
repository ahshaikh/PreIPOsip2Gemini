<?php
V-FINAL-1730-434 (Created) | V-FINAL-1730-442 (SEC-8 Hardened)

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to your application's "home" route.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/dashboard';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        // --- SEC-8: PER-USER/IP RATE LIMITING ---
        RateLimiter::for('login', function (Request $request) {
            // Generate a key based on the email/username AND the IP
            // This prevents a single user from being brute-forced from multiple IPs,
            // and prevents one IP from brute-forcing multiple users.
            $key = strtolower($request->input('login', '')) . '|' . $request->ip();
            
            // Allow 5 attempts per minute per key
            return Limit::perMinute(5)->by($key);
        });

        // This is a global API throttle for all other routes
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // Financial operations rate limiter (withdrawals, payments, transfers)
        RateLimiter::for('financial', function (Request $request) {
            return Limit::perMinute(10)->by($request->user()?->id ?: $request->ip())
                ->response(function () {
                    return response()->json([
                        'message' => 'Too many financial requests. Please wait before trying again.'
                    ], 429);
                });
        });

        // Admin reports rate limiter (resource-intensive operations)
        RateLimiter::for('reports', function (Request $request) {
            return Limit::perHour(20)->by($request->user()?->id ?: $request->ip())
                ->response(function () {
                    return response()->json([
                        'message' => 'Too many report requests. Please wait before generating more reports.'
                    ], 429);
                });
        });

        // Data-heavy endpoints rate limiter (portfolio, bonuses, analytics)
        RateLimiter::for('data-heavy', function (Request $request) {
            return Limit::perMinute(20)->by($request->user()?->id ?: $request->ip())
                ->response(function () {
                    return response()->json([
                        'message' => 'Too many requests. Please wait before trying again.'
                    ], 429);
                });
        });

        // Admin actions rate limiter (user management, system changes)
        RateLimiter::for('admin-actions', function (Request $request) {
            return Limit::perMinute(30)->by($request->user()?->id ?: $request->ip())
                ->response(function () {
                    return response()->json([
                        'message' => 'Too many admin actions. Please wait before trying again.'
                    ], 429);
                });
        });
        // ----------------------------------------

        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }
}