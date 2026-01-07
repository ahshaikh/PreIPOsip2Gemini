<?php

namespace App\Http\Controllers\Api\Company;

use App\Http\Controllers\Controller;
use App\Models\CompanyUser;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Auth\Events\Verified;

/**
 * FIX 19: Email Verification for Company Users
 *
 * Handles email verification for company users
 */
class EmailVerificationController extends Controller
{
    /**
     * Send email verification notification
     */
    public function sendVerificationEmail(Request $request)
    {
        $user = $request->user('sanctum');

        if (!$user instanceof CompanyUser) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 401);
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Email already verified',
            ], 200);
        }

        $user->sendEmailVerificationNotification();

        return response()->json([
            'message' => 'Verification email sent successfully',
        ], 200);
    }

    /**
     * Verify email
     */
    public function verify(Request $request, $id, $hash)
    {
        $user = CompanyUser::findOrFail($id);

        if (!hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
            return response()->json([
                'message' => 'Invalid verification link',
            ], 403);
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Email already verified',
            ], 200);
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        \Log::info('Company user email verified', [
            'company_user_id' => $user->id,
            'email' => $user->email,
        ]);

        return response()->json([
            'message' => 'Email verified successfully! Your account is now pending admin approval.',
        ], 200);
    }

    /**
     * Check verification status
     */
    public function checkStatus(Request $request)
    {
        $user = $request->user('sanctum');

        if (!$user instanceof CompanyUser) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 401);
        }

        return response()->json([
            'email_verified' => $user->hasVerifiedEmail(),
            'email_verified_at' => $user->email_verified_at,
            'status' => $user->status,
            'is_verified' => $user->is_verified,
        ]);
    }
}
