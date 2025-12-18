<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * EnsureMfaVerified
 * * Middleware to protect high-sensitivity fintech routes (e.g. Withdrawals).
 */
class EnsureMfaVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // 1. Check if user has MFA enabled but session is unverified
        // We track 'mfa_verified_at' in the session during the 2FA login flow
        if ($user->two_factor_enabled && !$request->session()->has('mfa_verified_at')) {
            return response()->json([
                'message' => 'Additional verification required.',
                'requires_mfa' => true
            ], 403);
        }

        return $next($request);
    }
}