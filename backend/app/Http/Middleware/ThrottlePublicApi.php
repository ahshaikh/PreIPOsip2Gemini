<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Routing\Middleware\ThrottleRequests;

/**
 * FIX 16 (P3): Rate Limiting for Public Endpoints
 *
 * Protects public API endpoints from abuse
 * Prevents DDoS and brute-force attacks
 */
class ThrottlePublicApi extends ThrottleRequests
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  int|string  $maxAttempts
     * @param  float|int  $decayMinutes
     * @param  string  $prefix
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Illuminate\Http\Exceptions\ThrottleRequestsException
     */
    public function handle($request, Closure $next, $maxAttempts = 60, $decayMinutes = 1, $prefix = '')
    {
        // Apply stricter limits based on endpoint
        if ($this->isAuthEndpoint($request)) {
            // Login/Register: 5 requests per minute
            $maxAttempts = 5;
            $decayMinutes = 1;
        } elseif ($this->isPasswordResetEndpoint($request)) {
            // Password reset: 3 requests per 15 minutes
            $maxAttempts = 3;
            $decayMinutes = 15;
        } elseif ($this->isOtpEndpoint($request)) {
            // OTP generation: 10 requests per hour
            $maxAttempts = 10;
            $decayMinutes = 60;
        }

        return parent::handle($request, $next, $maxAttempts, $decayMinutes, $prefix);
    }

    /**
     * Check if request is to auth endpoint
     */
    protected function isAuthEndpoint($request): bool
    {
        return in_array($request->path(), [
            'api/v1/auth/login',
            'api/v1/auth/register',
            'api/v1/company/login',
            'api/v1/company/register',
        ]);
    }

    /**
     * Check if request is to password reset endpoint
     */
    protected function isPasswordResetEndpoint($request): bool
    {
        return str_contains($request->path(), 'password/reset') ||
               str_contains($request->path(), 'password/forgot');
    }

    /**
     * Check if request is to OTP endpoint
     */
    protected function isOtpEndpoint($request): bool
    {
        return str_contains($request->path(), 'otp/send') ||
               str_contains($request->path(), 'otp/generate');
    }

    /**
     * Resolve the number of attempts if the user is authenticated.
     */
    protected function resolveMaxAttempts($request, $maxAttempts)
    {
        // Authenticated users get higher limits
        if ($request->user()) {
            return $maxAttempts * 3;
        }

        return $maxAttempts;
    }

        protected function resolveRequestSignature($request)
    {
        // CRITICAL: Never touch auth on public routes
        return sha1($request->ip().'|'.$request->path());
    }
}
