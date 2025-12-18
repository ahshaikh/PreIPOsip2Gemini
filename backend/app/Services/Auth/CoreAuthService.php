<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Cookie;

/**
 * CoreAuthService
 * * Centralized service to handle authentication logic, token issuance,
 * and secure cookie formatting for the PreIPOsip platform.
 */
class CoreAuthService
{
    /**
     * Authenticate a user and return the user object along with a secure cookie.
     *
     * @param array $credentials ['email', 'password']
     * @param string $role The capability scope for the token (user|company|admin)
     * @return array ['user' => User, 'cookie' => Cookie]
     * @throws ValidationException
     */
    public function authenticate(array $credentials, string $role = 'user'): array
    {
        $user = User::where('email', $credentials['email'])->first();

        // 1. Verify user exists and password is correct
        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials do not match our records.'],
            ]);
        }

        // 2. Role-specific gating logic
        if ($role === 'company' && !$user->is_company_admin) {
            throw ValidationException::withMessages([
                'email' => ['Access denied. This account is not authorized for company management.'],
            ]);
        }

        // 3. Create the Sanctum Token
        // We use the role as the 'ability' scope for the token
        $token = $user->createToken('auth_token', [$role])->plainTextToken;

        // 4. Format the HttpOnly Secure Cookie
        // This cookie is not accessible via JavaScript (XSS Protection)
        $cookie = cookie(
            'auth_token',          // Cookie Name
            $token,                // Value
            60 * 24,               // Expiry (24 hours)
            '/',                   // Path
            config('session.domain'), // Domain (ensure this is set in .env)
            config('session.secure'), // Secure (True in production)
            true,                  // HttpOnly (CRITICAL: prevents JS access)
            false,                 // Raw
            'Lax'                  // SameSite (Prevents CSRF)
        );

        return [
            'user' => $user,
            'cookie' => $cookie
        ];
    }

    /**
     * Generate a logout cookie that clears the auth_token.
     *
     * @return Cookie
     */
    public function getLogoutCookie(): Cookie
    {
        return cookie()->forget('auth_token');
    }
}