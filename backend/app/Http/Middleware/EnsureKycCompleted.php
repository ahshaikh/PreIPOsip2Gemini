<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class EnsureKycCompleted
{
    /**
     * Handle an incoming request.
     *
     * WHY: Prevent non‑KYC users from accessing protected routes,
     *      while allowing admins/superadmins to bypass KYC checks.
     * WHAT: Checks user role before enforcing KYC.
     * IMPACT: Ensures correct UX flow — admins go straight to admin dashboard,
     *         users are redirected to complete KYC.
     * SECURITY: Maintains KYC enforcement for investors, but exempts privileged roles.
     */
    public function handle($request, Closure $next)
    {
        $user = Auth::user();

        // ✅ Bypass KYC for privileged roles
        if ($user && in_array($user->role, ['admin', 'superadmin'])) {
            // Commentary: Admins and superadmins are trusted roles.
            // They must not be blocked by KYC middleware.
            return $next($request);
        }

        // ✅ Enforce KYC for regular users
        if ($user && !$user->kyc_completed) {
            // Commentary: Redirect non‑KYC users to KYC form.
            // This ensures compliance before accessing user dashboard.
            return redirect()->route('kyc.form');
        }

        // ✅ Default: allow request to continue
        return $next($request);
    }
}