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
     * Get the redirect URL for a provider.
     */
    public function redirectToProvider(Request $request, $provider)
    {
        if ($provider !== 'google') {
            return response()->json(['message' => 'Provider not supported'], 400);
        }
        return $this->redirectToGoogle($request);
    }

    /**
     * Handle the callback from a provider.
     */
    public function handleProviderCallback(Request $request, $provider)
    {
        if ($provider !== 'google') {
            return response()->json(['message' => 'Provider not supported'], 400);
        }
        return $this->handleGoogleCallback($request);
    }

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

        // Extract referral code from state parameter
        $referralCode = null;
        if ($request->has('state')) {
            try {
                $stateData = json_decode(base64_decode($request->get('state')), true);
                $referralCode = $stateData['referral_code'] ?? null;
            } catch (\Exception $e) {
                Log::warning('Failed to decode referral state', ['error' => $e->getMessage()]);
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
            $this->processReferralCode($user, $referralCode);
        }
    }

    /**
     * V-AUDIT-MODULE9-002 (HIGH): Lock campaign at signup, not at payment completion.
     *
     * Process referral code for new user.
     *
     * Previous Issue:
     * - Campaign was assigned when referral completed (payment time)
     * - If user signed up during "Double Bonus Week" but paid after it ended, they got standard bonus
     * - "Bait and Switch" behavior caused user complaints
     *
     * Fix:
     * - Lock the active campaign_id at signup time
     * - This guarantees the user gets the campaign bonus they saw when they signed up
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

            // V-AUDIT-MODULE9-002: Lock the active campaign at signup time
            // This ensures the user gets the campaign bonus they saw when signing up,
            // even if they complete payment after the campaign ends
            $activeCampaign = \App\Models\ReferralCampaign::running()->first();

            // Create referral record with locked campaign_id
            Referral::create([
                'referrer_id' => $referrer->id,
                'referred_id' => $user->id,
                'status' => 'pending', // Will be completed when referred user makes first payment
                'referral_campaign_id' => $activeCampaign?->id, // V-AUDIT-MODULE9-002: Lock campaign at signup
            ]);

            Log::info('Referral code processed successfully', [
                'referrer_id' => $referrer->id,
                'referred_id' => $user->id,
                'referral_code' => $referralCode,
                'campaign_locked' => $activeCampaign?->name ?? 'None' // V-AUDIT-MODULE9-002: Log locked campaign
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to process referral code', [
                'user_id' => $user->id,
                'referral_code' => $referralCode,
                'error' => $e->getMessage()
            ]);
        }
    }
}