<?php
// V-FINAL-1730-464 (Created)

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ReferralController extends Controller
{
    /**
     * FSD-REF-001: Get referral dashboard data.
     */
    public function index(Request $request)
    {
        $user = $request->user()->load('subscription');

        // 1. Get Referral Code
        $referralCode = $user->referral_code;

        // 2. Get Multiplier
        $multiplier = $user->subscription ? $user->subscription->bonus_multiplier : 1.0;

        // 3. Get Tiers (from plan config, default if no plan)
        $tiers = $user->subscription
            ? $user->subscription->plan->getConfig('referral_tiers', [])
            : [];
            
        // 4. Get List of Referrals (paginated)
        $referrals = $user->referrals()
            ->with('referred:id,username,status') // Get referred user's info
            ->latest()
            ->paginate(20);

        return response()->json([
            'referral_code' => $referralCode,
            'bonus_multiplier' => (float) $multiplier,
            'referral_tiers' => $tiers,
            'referrals' => $referrals,
        ]);
    }
}