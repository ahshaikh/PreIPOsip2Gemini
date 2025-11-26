<?php
// V-FINAL-1730-473 (Created)

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\UserKyc;
use App\Models\Wallet;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SocialLoginController extends Controller
{
    /**
     * Get the redirect URL for Google.
     */
    public function redirectToGoogle(Request $request)
    {
        // We must use stateless() for an API
        $driver = Socialite::driver('google')->stateless();

        // Pass referral code through state parameter if provided
        if ($request->filled('referral_code')) {
            $state = base64_encode(json_encode([
                'referral_code' => $request->referral_code
            ]));
            $driver->with(['state' => $state]);
        }

        $redirectUrl = $driver->redirect()->getTargetUrl();

        return response()->json([
            'redirect_url' => $redirectUrl,
        ]);
    }

    /**
     * Handle the callback from Google.
     */
    public function handleGoogleCallback(Request $request)
    {
        try {
            $socialUser = Socialite::driver('google')->stateless()->user();
        } catch (\Exception $e) {
            // Invalid state or code
            return redirect(env('FRONTEND_URL') . '/login?error=google_failed');
        }

        // Extract referral code from state if present
        $referralCode = null;
        if ($request->filled('state')) {
            try {
                $stateData = json_decode(base64_decode($request->state), true);
                $referralCode = $stateData['referral_code'] ?? null;
            } catch (\Exception $e) {
                // Invalid state format, continue without referral
            }
        }

        // 1. Find or Create User
        $user = User::updateOrCreate(
            ['google_id' => $socialUser->getId()],
            [
                'email' => $socialUser->getEmail(),
                'username' => $socialUser->getNickname() ?? explode('@', $socialUser->getEmail())[0],
                'status' => 'active', // Auto-activate Google users
                'email_verified_at' => now(), // Google handles this
            ]
        );

        // 2. Onboarding (if new user)
        if ($user->wasRecentlyCreated) {
            $this->createNewUserBootstrap($user, $socialUser, $referralCode);
        }

        // 3. Log them in
        $token = $user->createToken('auth-token')->plainTextToken;

        // 4. Redirect to frontend with token
        // This is a common pattern for API-based social auth
        return redirect(env('FRONTEND_URL') . '/login/social-callback?token=' . $token);
    }

    /**
     * Create the associated records for a new social user.
     */
    private function createNewUserBootstrap($user, $socialUser, $referralCode = null)
    {
        // Get name parts
        $nameParts = explode(' ', $socialUser->getName());
        $firstName = $nameParts[0] ?? $user->username;
        $lastName = $nameParts[1] ?? '';

        UserProfile::create([
            'user_id' => $user->id,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'avatar_url' => $socialUser->getAvatar(),
        ]);

        UserKyc::create(['user_id' => $user->id, 'status' => 'pending']);
        Wallet::create(['user_id' => $user->id]);
        $user->assignRole('user');

        // Process referral code if provided
        if ($referralCode) {
            $referrer = User::where('referral_code', $referralCode)->first();
            if ($referrer) {
                $user->update(['referred_by' => $referrer->id]);

                // Use ReferralService to process the referral
                $referralService = app(\App\Services\ReferralService::class);
                $referralService->processReferral($user->id, $referrer->id);
            }
        }
    }
}