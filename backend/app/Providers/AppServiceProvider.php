<?php
// V-FINAL-1730-641 (Rate Limiters Added)

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // --- SEC-8: PER-USER/IP RATE LIMITING ---
        // This fixes the "Rate limiter [login] is not defined" error
        RateLimiter::for('login', function (Request $request) {
            // Limit based on email/username AND IP address
            $key = strtolower($request->input('login', $request->input('email', ''))) . '|' . $request->ip();
            
            // Allow 5 attempts per minute
            return Limit::perMinute(5)->by($key);
        });

        // Global API Throttle
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });
    }
}