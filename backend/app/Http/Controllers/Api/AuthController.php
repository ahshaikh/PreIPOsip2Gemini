<?php
// V-PHASE1-1730-015

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\UserKyc;
use App\Models\Wallet; // We will create this in Phase 3
use App\Models\Otp;
use App\Jobs\SendOtpJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Register a new user.
     */
    public function register(RegisterRequest $request)
    {

	// --- REMEDIATION (SEC-2) ---
        // Check if registration is enabled before doing anything.
        if (!setting('registration_enabled', true)) {
            return response()->json(['message' => 'Registrations are currently closed by the administrator.'], 403);
        }
        // --- END REMEDIATION ---

        $referralCode = strtoupper(Str::random(8));
        // Ensure it's unique
        while (User::where('referral_code', $referralCode)->exists()) {
            $referralCode = strtoupper(Str::random(8));
        }

        $referrer = null;
        if ($request->referral_code) {
            $referrer = User::where('referral_code', $request->referral_code)->first();
        }

        $user = User::create([
            'username' => $request->username,
            'email' => $request->email,
            'mobile' => $request->mobile,
            'password' => Hash::make($request->password),
            'referral_code' => $referralCode,
            'referred_by' => $referrer?->id,
            'status' => 'pending',
        ]);

        // Create associated records
        UserProfile::create(['user_id' => $user->id]);
        UserKyc::create(['user_id' => $user->id]);
        // Wallet::create(['user_id' => $user->id]); // Add in Phase 3

        // Assign default 'user' role
        $user->assignRole('user'); 

        // Send OTPs
        dispatch(new SendOtpJob($user, 'email'));
        dispatch(new SendOtpJob($user, 'mobile'));

        return response()->json([
            'message' => 'Registration successful. Please verify your Email and Mobile.',
            'user_id' => $user->id,
        ], 201);
    }

    /**
     * Verify OTP for email or mobile.
     */
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'type' => 'required|in:email,mobile',
            'otp' => 'required|string|min:6|max:6',
        ]);

        $user = User::find($request->user_id);

        $otpRecord = Otp::where('user_id', $user->id)
            ->where('type', $request->type)
            ->where('otp_code', $request->otp)
            ->where('expires_at', '>', now())
            ->first();

        if (!$otpRecord) {
            return response()->json(['message' => 'Invalid or expired OTP.'], 400);
        }

        if ($request->type == 'email') {
            $user->email_verified_at = now();
        } elseif ($request->type == 'mobile') {
            $user->mobile_verified_at = now();
        }
        
        // Activate user if both are verified
        if ($user->email_verified_at && $user->mobile_verified_at) {
            $user->status = 'active';
        }
        
        $user->save();
        $otpRecord->delete(); // OTP is used

        return response()->json([
            'message' => ucfirst($request->type) . ' verified successfully.',
            'status' => $user->status
        ]);
    }

    /**
     * Authenticate the user and return a token.
     */
    public function login(LoginRequest $request)
    {
        $user = User::where('email', $request->login)
                    ->orWhere('username', $request->login)
                    ->orWhere('mobile', $request->login)
                    ->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'login' => ['Invalid credentials.'],
            ]);
        }

        if ($user->status !== 'active') {
            throw ValidationException::withMessages([
                'login' => ['Your account is not active. Please verify or contact support.'],
            ]);
        }
        
        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip()
        ]);

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful.',
            'token' => $token,
            'user' => $user->load('profile', 'kyc', 'roles:name'),
        ]);
    }

    /**
     * Log the user out (Revoke the token).
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out successfully.']);
    }
}