<?php
// V-PHASE3-1730-093

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\Referral;
use Illuminate\Http\Request;

class ReferralController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user()->load('subscription');
        
        $referrals = Referral::where('referrer_id', $user->id)
            ->with('referred:id,username')
            ->latest()
            ->paginate(20);
            
        $stats = [
            'referral_code' => $user->referral_code,
            'current_multiplier' => $user->subscription?->bonus_multiplier ?? 1.0,
            'total_referrals' => $referrals->total(),
            'completed_referrals' => Referral::where('referrer_id', $user->id)->where('status', 'completed')->count(),
        ];

        return response()->json([
            'stats' => $stats,
            'referrals' => $referrals,
        ]);
    }
}