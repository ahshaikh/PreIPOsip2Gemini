<?php
// V-PHASE1-1730-015 (Created) | V-FINAL-1730-470 (2FA Logic Added) | V-FINAL-1730-658 (Setting Helper Fix)

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\UserKyc;
use App\Models\Wallet;
use App\Models\Otp;
use App\Jobs\SendOtpJob;
use App\Models\Setting; // <-- IMPORT
use App\Services\OtpService; // <-- V-SECURITY-FIX
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PragmaRX\Google2FA\Google2FA;

class AuthController extends Controller
{
    /**
     * Get a setting safely without relying on the helper file being loaded.
     */
    private function getSettingSafely(string $key, $default = null)
    {
        try {
            // Find the setting and return its raw value (no casting here)
            return Setting::where('key', $key)->value('value') ?? $default;
        } catch (\Exception $e) {
            return $default;
        }
    }
    
    /**
     * Register a new user.
     */
    public function register(RegisterRequest $request)
    {
        // --- FIXED: Use direct model access ---
        if ($this->getSettingSafely('registration_enabled', 'true') === 'false') {
            return response()->json(['message' => 'Registrations are currently closed.'], 403);
        }
        // ------------------------------------

        $user = User::create([
            'username' => $request->username,
            'email' => $request->email,
            'mobile' => $request->mobile,
            'password' => Hash::make($request->password),
            'status' => 'pending', // Must verify first
        ]);
        
        UserProfile::create(['user_id' => $user->id]);
        UserKyc::create(['user_id' => $user->id, 'status' => 'pending']);
        Wallet::create(['user_id' => $user->id]);
        $user->assignRole('user');

        // Process referral code if provided and referral module is enabled
        if ($request->filled('referral_code') && $this->getSettingSafely('referral_enabled', 'true') === 'true') {
            $referrer = User::where('referral_code', $request->referral_code)->first();
            if ($referrer) {
                $user->update(['referred_by' => $referrer->id]);
                // Create pending referral record
            }
        }
        
        SendOtpJob::dispatch($user, 'email');
        SendOtpJob::dispatch($user, 'mobile');

        return response()->json([
            'message' => 'Registration successful. Please verify your Email and Mobile.',
            'user_id' => $user->id
        ], 201);
    }
    
    /**
     * User Login (Step 1 or final step if no 2FA).
     */
    public function login(LoginRequest $request)
    {
        // Check if login is enabled
        if ($this->getSettingSafely('login_enabled', 'true') === 'false') {
            return response()->json([
                'message' => 'Login is currently disabled. Please contact support for assistance.'
            ], 503);
        }

        $user = $request->authenticate();

        // --- V-SECURITY-FIX: User Status Validation ---
        if ($user->status === 'suspended') {
            return response()->json([
                'message' => 'Your account has been suspended. Please contact support.'
            ], 403);
        }

        if ($user->status === 'banned') {
            return response()->json([
                'message' => 'Your account has been permanently banned.'
            ], 403);
        }

        if ($user->status === 'pending') {
            return response()->json([
                'message' => 'Please verify your email and mobile to activate your account.',
                'user_id' => $user->id,
                'verification_required' => true,
            ], 403);
        }
        // --- END Status Validation ---

        // --- 2FA CHECK ---
        if ($user && $user->two_factor_confirmed_at) {
            // User has 2FA enabled. Do NOT send token.
            return response()->json([
                'two_factor_required' => true,
                'user_id' => $user->id,
            ]);
        }
        // --- END 2FA CHECK ---

        // No 2FA, log in normally
        $user->last_login_at = now();
        $user->last_login_ip = $request->ip();
        $user->save();
        
        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful.',
            'token' => $token,
            'user' => $user->load('profile', 'kyc', 'roles:name'),
        ]);
    }
    
    /**
     * 2FA Login Flow (Step 2).
     */
    public function verifyTwoFactor(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'code' => 'required|string',
        ]);
        
        $user = User::findOrFail($request->user_id);
        $code = $request->code;
        
        // Check standard 6-digit code
        if (!$user->verifyTwoFactorCode($code)) {
            
            // Check recovery codes
            $recoveryCode = collect($user->two_factor_recovery_codes)
                ->first(fn ($rc) => hash_equals($rc, $code));

            if (!$recoveryCode) {
                return response()->json(['message' => 'Invalid 2FA or recovery code.'], 422);
            }
            
            $user->replaceRecoveryCode($recoveryCode);
        }

        // --- SUCCESS ---
        $user->last_login_at = now();
        $user->last_login_ip = $request->ip();
        $user->save();
        
        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful.',
            'token' => $token,
            'user' => $user->load('profile', 'kyc', 'roles:name'),
        ]);
    }

    /**
     * Verify OTP for new account.
     * V-SECURITY-FIX: Now actually validates the OTP code using OtpService
     */
    public function verifyOtp(Request $request, OtpService $otpService)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'type' => 'required|in:email,mobile',
            'otp' => 'required|digits:6',
        ]);

        $user = User::find($request->user_id);

        // V-SECURITY-FIX: Actually verify the OTP using OtpService
        try {
            $isValid = $otpService->verify($user, $request->type, $request->otp);

            if (!$isValid) {
                return response()->json([
                    'message' => 'Invalid OTP code. Please try again.'
                ], 422);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 422);
        }

        // OTP verified successfully - update the appropriate field
        if ($request->type == 'email') {
            $user->update(['email_verified_at' => now()]);
        }
        if ($request->type == 'mobile') {
            $user->update(['mobile_verified_at' => now()]);
        }

        // If both are verified, activate account
        $user->refresh();
        if ($user->email_verified_at && $user->mobile_verified_at) {
            $user->update(['status' => 'active']);
        }

        return response()->json([
            'message' => ucfirst($request->type) . ' verified successfully.',
            'account_activated' => $user->status === 'active'
        ]);
    }

    /**
     * Log the user out.
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out successfully.']);
    }
}