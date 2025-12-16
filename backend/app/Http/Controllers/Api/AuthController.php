<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use App\Domains\Identity\Actions\RegisterUserAction;
use App\Domains\Identity\Actions\LoginUserAction;
use App\Domains\Identity\Actions\VerifyOtpAction;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Register a new user.
     * Delegates to Identity Domain.
     */
    public function register(RegisterRequest $request, RegisterUserAction $action)
    {
        try {
            $user = $action->execute($request->validated());
            
            return response()->json([
                'message' => 'Registration successful. Please verify your Email and Mobile.',
                'user_id' => $user->id
            ], 201);
        } catch (\Exception $e) {
            $code = $e->getCode();
            // Map common exception codes to valid HTTP status
            $status = ($code >= 400 && $code < 600) ? $code : 400;
            return response()->json(['message' => $e->getMessage()], $status);
        }
    }
    
    /**
     * User Login (Step 1 or final step if no 2FA).
     * Delegates to Identity Domain.
     */
    public function login(LoginRequest $request, LoginUserAction $action)
    {
        // Authenticate credentials first (Throttle via Laravel middleware)
        $request->authenticate(); 
        
        // Retrieve user
        $user = $request->user() ?? User::where('email', $request->email)->first();

        try {
            $result = $action->execute($user, $request->ip());
            return response()->json($result);
        } catch (\Exception $e) {
            // Handle pending verification explicitly with 403
            $status = $e->getCode() === 403 ? 403 : 422;
            
            // If explicit response data was passed in exception (custom logic), use it
            if ($status === 403 && str_contains($e->getMessage(), 'verify')) {
                 return response()->json([
                    'message' => $e->getMessage(),
                    'user_id' => $user->id,
                    'verification_required' => true,
                ], 403);
            }

            return response()->json(['message' => $e->getMessage()], $status);
        }
    }
    
    /**
     * 2FA Login Flow (Step 2).
     */
    public function verifyTwoFactor(Request $request, LoginUserAction $loginAction)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'code' => 'required|string',
        ]);
        
        $user = User::findOrFail($request->user_id);
        
        // Verify code (Standard or Recovery)
        if (!$user->verifyTwoFactorCode($request->code)) {
            // Check recovery codes
            $recoveryCode = collect($user->two_factor_recovery_codes)
                ->first(fn ($rc) => hash_equals($rc, $request->code));

            if (!$recoveryCode) {
                return response()->json(['message' => 'Invalid 2FA or recovery code.'], 422);
            }
            
            $user->replaceRecoveryCode($recoveryCode);
        }

        // Reuse Login Action logic for token issuance
        // This ensures consistent behavior (logging login time, IP, etc.)
        $result = $loginAction->issueToken($user, $request->ip());

        return response()->json($result);
    }

    /**
     * Verify OTP for new account.
     * Fixed Security Risk: Logic moved to VerifyOtpAction which includes Rate Limiting.
     */
    public function verifyOtp(Request $request, VerifyOtpAction $action)
    {
        $request->validate([
            'user_id' => 'required|integer', // 'exists' check handled in Action securely
            'type' => 'required|in:email,mobile',
            'otp' => 'required|digits:6',
        ]);

        try {
            $result = $action->execute($request->user_id, $request->type, $request->otp);
            return response()->json($result);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * Log the user out.
     */
    public function logout(Request $request)
    {
        if ($request->user()) {
            $request->user()->currentAccessToken()->delete();
        }
        return response()->json(['message' => 'Logged out successfully.']);
    }
}