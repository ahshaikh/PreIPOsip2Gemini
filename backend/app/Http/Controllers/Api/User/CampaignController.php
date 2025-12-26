<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Services\CampaignService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CampaignController extends Controller
{
    protected $campaignService;

    public function __construct(CampaignService $campaignService)
    {
        $this->campaignService = $campaignService;
    }

    /**
     * Get all active campaigns
     */
    public function index(Request $request): JsonResponse
    {
        $campaigns = Campaign::active()
            ->orderBy('is_featured', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        // Add computed state for each campaign
        $campaigns->transform(function ($campaign) {
            return array_merge($campaign->toArray(), [
                'state' => $campaign->state,
                'remaining_usage' => $campaign->remaining_usage,
            ]);
        });

        return response()->json($campaigns);
    }

    /**
     * Get all campaigns applicable to the authenticated user
     */
    public function applicable(Request $request): JsonResponse
    {
        $user = $request->user();
        $amount = $request->input('amount');

        $campaigns = $this->campaignService->getApplicableCampaigns($user, $amount);

        // Add computed state for each campaign
        $campaigns->transform(function ($campaign) {
            return array_merge($campaign->toArray(), [
                'state' => $campaign->state,
                'remaining_usage' => $campaign->remaining_usage,
            ]);
        });

        return response()->json($campaigns);
    }

    /**
     * Get a specific campaign by ID
     */
    public function show($id): JsonResponse
    {
        $campaign = Campaign::find($id);

        if (!$campaign) {
            return response()->json([
                'message' => 'Campaign not found',
            ], 404);
        }

        return response()->json([
            'data' => array_merge($campaign->toArray(), [
                'state' => $campaign->state,
                'is_live' => $campaign->is_live,
                'remaining_usage' => $campaign->remaining_usage,
            ]),
        ]);
    }

    /**
     * Validate a campaign code
     */
    public function validateCode(Request $request): JsonResponse
    {
        $request->validate([
            'code' => 'required|string',
            'amount' => 'nullable|numeric|min:0',
        ]);

        $user = $request->user();
        $campaign = $this->campaignService->validateCampaignCode($request->code);

        if (!$campaign) {
            return response()->json([
                'valid' => false,
                'message' => 'Invalid campaign code',
            ], 404);
        }

        // Check if campaign is applicable
        $amount = $request->input('amount', 0);
        $applicabilityCheck = $this->campaignService->isApplicable($campaign, $user, $amount);

        if (!$applicabilityCheck['applicable']) {
            return response()->json([
                'valid' => false,
                'message' => $applicabilityCheck['reason'],
                'campaign' => array_merge($campaign->toArray(), [
                    'state' => $campaign->state,
                ]),
            ], 400);
        }

        // Calculate discount
        $discount = 0;
        if ($amount > 0) {
            $discount = $this->campaignService->calculateDiscount($campaign, $amount);
        }

        return response()->json([
            'valid' => true,
            'message' => 'Campaign code is valid',
            'campaign' => array_merge($campaign->toArray(), [
                'state' => $campaign->state,
                'remaining_usage' => $campaign->remaining_usage,
            ]),
            'discount' => $discount,
            'final_amount' => $amount - $discount,
        ]);
    }

    /**
     * Get campaign usage history for the authenticated user
     */
    public function myUsages(Request $request): JsonResponse
    {
        $user = $request->user();

        $usages = $user->campaignUsages()
            ->with(['campaign', 'applicable'])
            ->latest('used_at')
            ->paginate($request->input('per_page', 20));

        return response()->json($usages);
    }
}
