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
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Register a new user.
     * Automatically captures 'ref_code' from Cookie if not provided in body.
     */
    public function register(RegisterRequest $request, RegisterUserAction $action): JsonResponse
    {
        try {
            $data = $request->validated();

            // FEATURE UPGRADE: Auto-detect referral from Cookie if missing in input
            if (empty($data['referral_code']) && $request->hasCookie('ref_code')) {
                $data['referral_code'] = $request->cookie('ref_code');
            }

            $user = $action->execute($data);
            
            return response()->json([
                'message' => 'Registration successful. Please verify your Email and Mobile.',
                'user_id' => $user->id
            ], 201);
        } catch (\Exception $e) {
            $code = $e->getCode();
            $status = ($code >= 400 && $code < 600) ? $code : 400;
            return response()->json(['message' => $e->getMessage()], $status);
        }
    }
    
    /**
     * User Login.
     * Issues a secure HttpOnly cookie upon successful login.
     */
    public function login(LoginRequest $request, LoginUserAction $action): JsonResponse
    {
        $request->authenticate(); // Throttling + Credential check
        
        $user = $request->user() ?? User::where('email', $request->email)->first();

        try {
            $result = $action->execute($user, $request->ip());

            // FIX: Generate Cookie directly in Controller (Transport Layer)
            // instead of calling undefined service method.
            $cookie = cookie(
                'auth_token',
                $result['token'],
                60 * 24 * 30, // 30 days
                null,
                null,
                true, // Secure
                true  // HttpOnly
            );

            return response()
                ->json($result)
                ->withCookie($cookie);

        } catch (\Exception $e) {
            $status = $e->getCode() === 403 ? 403 : 422;
            
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
     * Verify 2FA and issue Auth Cookie.
     */
    public function verifyTwoFactor(Request $request, LoginUserAction $loginAction): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'code' => 'required|string',
        ]);
        
        $user = User::findOrFail($request->user_id);
        
        if (!$user->verifyTwoFactorCode($request->code)) {
            // Check recovery codes
            $recoveryCode = collect($user->two_factor_recovery_codes)
                ->first(fn ($rc) => hash_equals($rc, $request->code));

            if (!$recoveryCode) {
                return response()->json(['message' => 'Invalid 2FA or recovery code.'], 422);
            }
            $user->replaceRecoveryCode($recoveryCode);
        }

        $result = $loginAction->issueToken($user, $request->ip());
        
        $cookie = cookie(
            'auth_token',
            $result['token'],
            60 * 24 * 30,
            null,
            null,
            true,
            true
        );

        return response()
            ->json($result)
            ->withCookie($cookie);
    }

    /**
     * Verify OTP for new account.
     */
    public function verifyOtp(Request $request, VerifyOtpAction $action): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|integer', 
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
    public function logout(Request $request): JsonResponse
    {
        if ($request->user()) {
            $request->user()->currentAccessToken()->delete();
        }

        $cookie = cookie()->forget('auth_token');

        return response()
            ->json(['message' => 'Logged out successfully.'])
            ->withCookie($cookie);
    }
}