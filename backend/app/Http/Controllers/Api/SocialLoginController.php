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
use App\Models\Referral;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
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
            Log::error('Google OAuth failed', ['error' => $e->getMessage()]);
            return redirect(env('FRONTEND_URL') . '/login?error=google_failed');
        }

<<<<<<< HEAD
        // Extract referral code from state parameter
        $referralCode = null;
        if ($request->has('state')) {
            try {
                $stateData = json_decode(base64_decode($request->get('state')), true);
                $referralCode = $stateData['referral_code'] ?? null;
            } catch (\Exception $e) {
                Log::warning('Failed to decode referral state', ['error' => $e->getMessage()]);
=======
        // Extract referral code from state if present
        $referralCode = null;
        if ($request->filled('state')) {
            try {
                $stateData = json_decode(base64_decode($request->state), true);
                $referralCode = $stateData['referral_code'] ?? null;
            } catch (\Exception $e) {
                // Invalid state format, continue without referral
>>>>>>> 5a046271830c8a9f8526dde5fea7b414a73819b6
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
<<<<<<< HEAD
            $this->processReferralCode($user, $referralCode);
        }
    }

    /**
     * Process referral code for new user.
     */
    private function processReferralCode($user, $referralCode)
    {
        try {
            // Find the referrer by referral code
            $referrer = User::where('referral_code', $referralCode)
                ->where('id', '!=', $user->id) // Prevent self-referral
                ->first();

            if (!$referrer) {
                Log::warning('Invalid referral code used', [
                    'user_id' => $user->id,
                    'referral_code' => $referralCode
                ]);
                return;
            }

            // Update user's referred_by field
            $user->update(['referred_by' => $referrer->id]);

            // Create referral record
            Referral::create([
                'referrer_id' => $referrer->id,
                'referred_id' => $user->id,
                'status' => 'pending', // Will be completed when referred user makes first payment
            ]);

            Log::info('Referral code processed successfully', [
                'referrer_id' => $referrer->id,
                'referred_id' => $user->id,
                'referral_code' => $referralCode
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to process referral code', [
                'user_id' => $user->id,
                'referral_code' => $referralCode,
                'error' => $e->getMessage()
            ]);
=======
            $referrer = User::where('referral_code', $referralCode)->first();
            if ($referrer) {
                $user->update(['referred_by' => $referrer->id]);

                // Use ReferralService to process the referral
                $referralService = app(\App\Services\ReferralService::class);
                $referralService->processReferral($user->id, $referrer->id);
            }
>>>>>>> 5a046271830c8a9f8526dde5fea7b414a73819b6
        }
    }
}