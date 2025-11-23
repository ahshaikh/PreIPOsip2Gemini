<?php
// V-FINAL-1730-272

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ReferralCampaign;
use Illuminate\Http\Request;

class ReferralCampaignController extends Controller
{
    public function index()
    {
        return ReferralCampaign::latest()->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'multiplier' => 'required|numeric|min:1',
            'bonus_amount' => 'required|numeric|min:0',
            'is_active' => 'boolean'
        ]);

        $campaign = ReferralCampaign::create($validated);
        return response()->json($campaign, 201);
    }

    public function update(Request $request, ReferralCampaign $referralCampaign)
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'start_date' => 'sometimes|required|date',
            'end_date' => 'sometimes|required|date|after:start_date',
            'multiplier' => 'sometimes|required|numeric|min:1',
            'bonus_amount' => 'sometimes|required|numeric|min:0',
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