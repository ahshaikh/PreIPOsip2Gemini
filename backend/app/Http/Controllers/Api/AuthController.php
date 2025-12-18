<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use App\Domains\Identity\Actions\RegisterUserAction;
use App\Domains\Identity\Actions\LoginUserAction;
use App\Domains\Identity\Actions\VerifyOtpAction;
use App\Services\Auth\CoreAuthService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(protected CoreAuthService $authService) {}

    /**
     * Register a new user.
     */
    public function register(RegisterRequest $request, RegisterUserAction $action): JsonResponse
    {
        try {
            $user = $action->execute($request->validated());
            
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
     * Now issues a secure HttpOnly cookie upon successful login.
     */
    public function login(LoginRequest $request, LoginUserAction $action): JsonResponse
    {
        $request->authenticate(); 
        $user = $request->user() ?? User::where('email', $request->email)->first();

        try {
            $result = $action->execute($user, $request->ip());

            // If login results in a token immediately (no 2FA required)
            if (isset($result['token'])) {
                $cookie = $this->authService->issueAuthCookie($result['token']);
                return response()
                    ->json($result)
                    ->withCookie($cookie);
            }

            return response()->json($result);
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
     * 2FA Login Flow (Step 2).
     */
    public function verifyTwoFactor(Request $request, LoginUserAction $loginAction): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'code' => 'required|string',
        ]);
        
        $user = User::findOrFail($request->user_id);
        
        if (!$this->authService->verifyTwoFactor($user, $request->code)) {
            return response()->json(['message' => 'Invalid 2FA or recovery code.'], 422);
        }

        $result = $loginAction->issueToken($user, $request->ip());
        
        // Attach secure cookie for 2FA success
        $cookie = $this->authService->issueAuthCookie($result['token']);

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
     * Clears token and removes the cookie.
     */
    public function logout(Request $request): JsonResponse
    {
        if ($request->user()) {
            $request->user()->currentAccessToken()->delete();
        }

        return response()
            ->json(['message' => 'Logged out successfully.'])
            ->withCookie($this->authService->getLogoutCookie());
    }
}