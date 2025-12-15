<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Event; // <-- Added Facade
use App\Events\KycVerified; // <-- Added Event
use App\Listeners\ProcessPendingReferralsOnKycVerify; // <-- Added Listener

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
        // 1. Safe Helper Loading
        try {
            $helperPath = app_path('Helpers/SettingsHelper.php');
            if (file_exists($helperPath)) {
                include_once $helperPath;
            }
        } catch (\Throwable $e) {
            Log::error('AppServiceProvider: Helper load failed - ' . $e->getMessage());
        }

        // =================================================================
        // EVENT LISTENER REGISTRATION (Module 9 Fix)
        // =================================================================
        // Since EventServiceProvider doesn't exist in Laravel 11, we register here.
        // This ensures pending referrals are processed immediately when KYC is verified.
        
        Event::listen(
            KycVerified::class,
            ProcessPendingReferralsOnKycVerify::class
        );

        // =================================================================
        // RATE LIMITER DEFINITIONS (Matching routes/api.php)
        // =================================================================

        // 1. Financial Transactions (Money Movement)
        // Route: throttle:financial
        RateLimiter::for('financial', function (Request $request) {
            return Limit::perMinute(10)->by($request->user()?->id ?: $request->ip());
        });
        // Legacy support for Job-based limiting
        RateLimiter::for('App\Models\User::financial', function ($target) {
            return Limit::perMinute(10)->by($this->extractUserId($target) ?: request()->ip());
        });

        // 2. Data Heavy Endpoints (Portfolio, Bonuses, Referrals)
        // Route: throttle:data-heavy
        // FIX: This was missing, causing the 500 Error on Portfolio/Bonuses
        RateLimiter::for('data-heavy', function (Request $request) {
            return Limit::perMinute(20)->by($request->user()?->id ?: $request->ip());
        });
        // Catch specific model-based calls if any
        RateLimiter::for('App\Models\User::data-heavy', function ($target) {
            return Limit::perMinute(20)->by($this->extractUserId($target) ?: request()->ip());
        });

        // 3. Admin Actions (Bulk updates, Approvals)
        // Route: throttle:admin-actions
        RateLimiter::for('admin-actions', function (Request $request) {
            return Limit::perMinute(30)->by($request->user()?->id ?: $request->ip());
        });

        // 4. Reporting Module
        // Route: throttle:reports
        RateLimiter::for('reports', function (Request $request) {
            return Limit::perMinute(5)->by($request->user()?->id ?: $request->ip());
        });
        RateLimiter::for('App\Models\User::reports', function ($target) {
            return Limit::perMinute(5)->by($this->extractUserId($target) ?: request()->ip());
        });

        // 5. Authentication
        RateLimiter::for('login', function (Request $request) {
            $input = $request->input('login') ?? $request->input('email') ?? '';
            $key = strtolower($input) . '|' . $request->ip();
            return Limit::perMinute(5)->by($key);
        });

        // 6. Global API
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });
    }

    /**
     * Helper to extract User ID from Job or Request safely.
     */
    private function extractUserId($target)
    {
        $userId = null;
        if (is_object($target)) {
            $userId = $target->user_id ?? $target->user?->id;
        }
        return $userId ?? optional(request()->user())->id;
    }
}