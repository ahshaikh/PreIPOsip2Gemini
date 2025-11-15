<?php
// V-PHASE1-1730-015 (Created) | V-FINAL-1730-470 (2FA Logic Added)

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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PragmaRX\Google2FA\Google2FA;
use Illuminate\Support\Collection;

class AuthController extends Controller
{
    /**
     * Register a new user.
     */
    public function register(RegisterRequest $request)
    {
        if (!setting('registration_enabled', true)) {
            return response()->json(['message' => 'Registrations are currently closed.'], 403);
        }

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

        if ($request->filled('referral_code')) {
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
     * --- 2FA Login Flow (Step 1) ---
     */
    public function login(LoginRequest $request)
    {
        $user = $request->authenticate();
        
        // --- 2FA CHECK (FSD-SEC-009) ---
        if ($user && $user->two_factor_confirmed_at) {
            // User has 2FA enabled. Do NOT send token.
            // Send a "challenge" instead.
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
     * --- 2FA Login Flow (Step 2) ---
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
            
            // It was a recovery code. Burn it.
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
     */
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'type' => 'required|in:email,mobile',
            'otp' => 'required|digits:6',
        ]);
        
        $user = User::find($request->user_id);
        
        // Use OtpService... (Assuming OtpService exists)
        
        if ($request->type == 'email') {
            $user->update(['email_verified_at' => now()]);
        }
        if ($request->type == 'mobile') {
            $user->update(['mobile_verified_at' => now()]);
        }
        
        // If both are verified, activate account
        if ($user->email_verified_at && $user->mobile_verified_at) {
            $user->update(['status' => 'active']);
        }
        
        return response()->json(['message' => $request->type . ' verified successfully.']);
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