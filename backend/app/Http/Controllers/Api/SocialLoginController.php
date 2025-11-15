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
    public function redirectToGoogle()
    {
        // We must use stateless() for an API
        $redirectUrl = Socialite::driver('google')->stateless()->redirect()->getTargetUrl();
        
        return response()->json([
            'redirect_url' => $redirectUrl,
        ]);
    }

    /**
     * Handle the callback from Google.
     */
    public function handleGoogleCallback()
    {
        try {
            $socialUser = Socialite::driver('google')->stateless()->user();
        } catch (\Exception $e) {
            // Invalid state or code
            return redirect(env('FRONTEND_URL') . '/login?error=google_failed');
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
            $this->createNewUserBootstrap($user, $socialUser);
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
    private function createNewUserBootstrap($user, $socialUser)
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
        
        // TODO: Check for referral code in state/cookie
    }
}