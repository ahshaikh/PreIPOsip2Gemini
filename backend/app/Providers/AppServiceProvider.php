<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Event;
use App\Events\KycVerified;
use App\Listeners\ProcessPendingReferralsOnKycVerify;

// [FIX] Imports for Payment Gateway Binding
// [AUDIT FIX]: Strictly use the Contract namespace
use App\Contracts\PaymentGatewayInterface;
use App\Services\Payments\Gateways\RazorpayGateway;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // [FIX] DYNAMIC GATEWAY BINDING
        // Binds App\Contracts\PaymentGatewayInterface to RazorpayGateway
        $this->app->bind(PaymentGatewayInterface::class, function ($app) {
            
            // Check settings (wrapped in try-catch to prevent crash during migrations)
            try {
                if (function_exists('setting') && setting('payment_gateway_razorpay_enabled')) {
                    return new RazorpayGateway();
                }
                
            } catch (\Exception $e) {
                // Squelch errors if database is not yet ready
            }

            // Default Fallback: Razorpay
            return new RazorpayGateway();
        });
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
        // OBSERVER REGISTRATION (Model Event Listeners)
        // =================================================================

        // [E.16]: Enforce transaction immutability via observer
        \App\Models\Transaction::observe(\App\Observers\TransactionObserver::class);

        // [F.21]: Enforce audit log immutability via observer
        \App\Models\AuditLog::observe(\App\Observers\AuditLogObserver::class);

        // [FIX 5 - P1]: Enforce company data immutability after freeze
        \App\Models\Company::observe(\App\Observers\CompanyObserver::class);

        // [PHASE 1]: Enforce disclosure version immutability for regulatory compliance
        \App\Models\DisclosureVersion::observe(\App\Observers\DisclosureVersionObserver::class);

        // =================================================================
        // EVENT LISTENER REGISTRATION
        // =================================================================

        Event::listen(
            KycVerified::class,
            ProcessPendingReferralsOnKycVerify::class
        );

        // =================================================================
        // RATE LIMITER DEFINITIONS
        // =================================================================

        // 1. Financial Transactions
        RateLimiter::for('financial', function (Request $request) {
            return Limit::perMinute(10)->by($request->user()?->id ?: $request->ip());
        });
        RateLimiter::for('App\Models\User::financial', function ($target) {
            return Limit::perMinute(10)->by($this->extractUserId($target) ?: request()->ip());
        });

        // 2. Data Heavy Endpoints
        RateLimiter::for('data-heavy', function (Request $request) {
            return Limit::perMinute(20)->by($request->user()?->id ?: $request->ip());
        });
        RateLimiter::for('App\Models\User::data-heavy', function ($target) {
            return Limit::perMinute(20)->by($this->extractUserId($target) ?: request()->ip());
        });

        // 3. Admin Actions
        RateLimiter::for('admin-actions', function (Request $request) {
            return Limit::perMinute(30)->by($request->user()?->id ?: $request->ip());
        });

        // 4. Reporting Module
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