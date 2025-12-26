<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Services\CampaignService;
use App\Http\Requests\Admin\StoreCampaignRequest;
use App\Http\Requests\Admin\UpdateCampaignRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CampaignController extends Controller
{
    protected $campaignService;

    public function __construct(CampaignService $campaignService)
    {
        $this->campaignService = $campaignService;
    }

    /**
     * Display a listing of campaigns
     */
    public function index(Request $request)
    {
        $query = Campaign::with(['creator', 'approver'])
            ->withCount('usages');

        // Filter by state
        if ($request->has('state')) {
            $state = $request->input('state');
            switch ($state) {
                case 'draft':
                    $query->pending();
                    break;
                case 'approved':
                    $query->approved();
                    break;
                case 'active':
                    $query->active();
                    break;
                case 'scheduled':
                    $query->scheduled();
                    break;
                case 'expired':
                    $query->expired();
                    break;
            }
        }

        // Filter by code
        if ($request->has('code')) {
            $query->where('code', 'like', '%' . $request->input('code') . '%');
        }

        // Filter by featured
        if ($request->has('is_featured')) {
            $query->where('is_featured', $request->boolean('is_featured'));
        }

        // Sort
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $campaigns = $query->paginate($request->input('per_page', 15));

        // Append computed attributes
        $campaigns->getCollection()->transform(function ($campaign) {
            return array_merge($campaign->toArray(), [
                'state' => $campaign->state,
                'is_live' => $campaign->is_live,
                'is_expired' => $campaign->is_expired,
                'is_scheduled' => $campaign->is_scheduled,
                'is_draft' => $campaign->is_draft,
                'remaining_usage' => $campaign->remaining_usage,
                'usage_percentage' => $campaign->usage_percentage,
                'can_be_edited' => $campaign->canBeEdited(),
                'can_be_approved' => $campaign->canBeApproved(),
                'can_be_activated' => $campaign->canBeActivated(),
                'can_be_paused' => $campaign->canBePaused(),
            ]);
        });

        return response()->json($campaigns);
    }

    /**
     * Store a newly created campaign
     */
    public function store(StoreCampaignRequest $request)
    {
        try {
            $validated = $request->validated();

            $campaign = DB::transaction(function () use ($validated, $request) {
                // Set creator
                $validated['created_by'] = $request->user()->id;

                // Create campaign
                $campaign = Campaign::create($validated);

                Log::info('Campaign created', [
                    'campaign_id' => $campaign->id,
                    'campaign_code' => $campaign->code,
                    'created_by' => $request->user()->id,
                ]);

                return $campaign;
            });

            return response()->json([
                'message' => 'Campaign created successfully',
                'campaign' => $campaign->load('creator'),
            ], 201);
        } catch (\Exception $e) {
            Log::error('Failed to create campaign', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Failed to create campaign',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified campaign
     */
    public function show(Campaign $campaign)
    {
        $campaign->load(['creator', 'approver']);
        $stats = $this->campaignService->getCampaignStats($campaign);

        return response()->json([
            'campaign' => array_merge($campaign->toArray(), [
                'state' => $campaign->state,
                'is_live' => $campaign->is_live,
                'is_expired' => $campaign->is_expired,
                'is_scheduled' => $campaign->is_scheduled,
                'is_draft' => $campaign->is_draft,
                'remaining_usage' => $campaign->remaining_usage,
                'usage_percentage' => $campaign->usage_percentage,
                'can_be_edited' => $campaign->canBeEdited(),
                'can_be_approved' => $campaign->canBeApproved(),
                'can_be_activated' => $campaign->canBeActivated(),
                'can_be_paused' => $campaign->canBePaused(),
            ]),
            'stats' => $stats,
        ]);
    }

    /**
     * Update the specified campaign
     */
    public function update(UpdateCampaignRequest $request, Campaign $campaign)
    {
        // Check if campaign can be edited
        if (!$campaign->canBeEdited()) {
            return response()->json([
                'message' => 'This campaign cannot be edited. Only draft campaigns or campaigns with no usage can be edited.',
            ], 403);
        }

        try {
            $validated = $request->validated();

            $campaign->update($validated);

            Log::info('Campaign updated', [
                'campaign_id' => $campaign->id,
                'campaign_code' => $campaign->code,
                'updated_by' => $request->user()->id,
            ]);

            return response()->json([
                'message' => 'Campaign updated successfully',
                'campaign' => $campaign->load('creator', 'approver'),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update campaign', [
                'campaign_id' => $campaign->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Failed to update campaign',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Approve a campaign
     */
    public function approve(Request $request, Campaign $campaign)
    {
        if (!$campaign->canBeApproved()) {
            return response()->json([
                'message' => 'This campaign cannot be approved. Only draft campaigns can be approved.',
            ], 403);
        }

        $success = $this->campaignService->approveCampaign($campaign, $request->user());

        if ($success) {
            return response()->json([
                'message' => 'Campaign approved successfully',
                'campaign' => $campaign->fresh(['creator', 'approver']),
            ]);
        }

        return response()->json([
            'message' => 'Failed to approve campaign',
        ], 500);
    }

    /**
     * Activate a campaign
     */
    public function activate(Campaign $campaign)
    {
        if (!$campaign->canBeActivated()) {
            return response()->json([
                'message' => 'This campaign cannot be activated. Campaign must be approved and not expired.',
            ], 403);
        }

        $success = $this->campaignService->activateCampaign($campaign);

        if ($success) {
            return response()->json([
                'message' => 'Campaign activated successfully',
                'campaign' => $campaign->fresh(['creator', 'approver']),
            ]);
        }

        return response()->json([
            'message' => 'Failed to activate campaign',
        ], 500);
    }

    /**
     * Pause a campaign
     */
    public function pause(Campaign $campaign)
    {
        if (!$campaign->canBePaused()) {
            return response()->json([
                'message' => 'This campaign cannot be paused. Campaign must be active.',
            ], 403);
        }

        $success = $this->campaignService->pauseCampaign($campaign);

        if ($success) {
            return response()->json([
                'message' => 'Campaign paused successfully',
                'campaign' => $campaign->fresh(['creator', 'approver']),
            ]);
        }

        return response()->json([
            'message' => 'Failed to pause campaign',
        ], 500);
    }

    /**
     * Get campaign usage analytics
     */
    public function analytics(Campaign $campaign)
    {
        $stats = $this->campaignService->getCampaignStats($campaign);

        // Get usage breakdown by day
        $usageByDay = DB::table('campaign_usages')
            ->where('campaign_id', $campaign->id)
            ->selectRaw('DATE(used_at) as date, COUNT(*) as count, SUM(discount_applied) as total_discount')
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->limit(30)
            ->get();

        // Get top users
        $topUsers = DB::table('campaign_usages')
            ->where('campaign_id', $campaign->id)
            ->join('users', 'campaign_usages.user_id', '=', 'users.id')
            ->selectRaw('users.id, users.username, users.email, COUNT(*) as usage_count, SUM(discount_applied) as total_discount')
            ->groupBy('users.id', 'users.username', 'users.email')
            ->orderByDesc('usage_count')
            ->limit(10)
            ->get();

        return response()->json([
            'stats' => $stats,
            'usage_by_day' => $usageByDay,
            'top_users' => $topUsers,
        ]);
    }

    /**
     * Get all campaign usages with pagination
     */
    public function usages(Request $request, Campaign $campaign)
    {
        $usages = $campaign->usages()
            ->with(['user', 'applicable'])
            ->latest('used_at')
            ->paginate($request->input('per_page', 20));

        return response()->json($usages);
    }
}
