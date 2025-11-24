<?php
// V-FINAL-1730-272 | V-SECURITY-FIX (Added multiplier and bonus caps)

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ReferralCampaign;
use Illuminate\Http\Request;

class ReferralCampaignController extends Controller
{
    // Maximum allowed values to prevent fraud
    private const MAX_MULTIPLIER = 5.0;
    private const MAX_BONUS_AMOUNT = 10000;

    public function index()
    {
        return ReferralCampaign::latest()->get();
    }

    public function store(Request $request)
    {
        $maxMultiplier = (float) setting('max_referral_multiplier', self::MAX_MULTIPLIER);
        $maxBonus = (float) setting('max_referral_bonus', self::MAX_BONUS_AMOUNT);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'multiplier' => "required|numeric|min:1|max:$maxMultiplier",
            'bonus_amount' => "required|numeric|min:0|max:$maxBonus",
            'is_active' => 'boolean'
        ]);

        $campaign = ReferralCampaign::create($validated);
        return response()->json($campaign, 201);
    }

    public function update(Request $request, ReferralCampaign $referralCampaign)
    {
        $maxMultiplier = (float) setting('max_referral_multiplier', self::MAX_MULTIPLIER);
        $maxBonus = (float) setting('max_referral_bonus', self::MAX_BONUS_AMOUNT);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'start_date' => 'sometimes|required|date',
            'end_date' => 'sometimes|required|date|after:start_date',
            'multiplier' => "sometimes|required|numeric|min:1|max:$maxMultiplier",
            'bonus_amount' => "sometimes|required|numeric|min:0|max:$maxBonus",
            'is_active' => 'sometimes|boolean'
        ]);

        $referralCampaign->update($validated);
        return response()->json($referralCampaign);
    }

    public function destroy(ReferralCampaign $referralCampaign)
    {
        $referralCampaign->delete();
        return response()->json(['message' => 'Campaign deleted']);
    }
}