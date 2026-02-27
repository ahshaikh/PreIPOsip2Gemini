<?php
// V-FINAL-1730-272 | V-SECURITY-FIX (Added multiplier and bonus caps)

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Carbon;

// Models (Kept for type hinting, but logic now uses DB facade for safety)
use App\Models\Referral;
use App\Models\ReferralCampaign;
use App\Models\BonusTransaction;

class ReferralController extends Controller
{
    private const MAX_MULTIPLIER = 5.0;
    private const MAX_BONUS_AMOUNT = 10000;

    // =======================================================================
    // PART 1: DASHBOARD STATS (Fixed: DB Facade & Safe Date Grouping)
    // =======================================================================

    public function stats(): JsonResponse
    {
        try {
            // A. Total Referrals
            $totalReferrals = 0;
            if (Schema::hasTable('referrals')) {
                $totalReferrals = DB::table('referrals')->count();
            }

            // B. Total Payouts
            $totalPayout = 0;
            if (Schema::hasTable('bonus_transactions')) {
                $totalPayout = DB::table('bonus_transactions')->where('type', 'referral')->sum('amount');
            }

            // C. Active Campaigns
            $activeCampaigns = 0;
            if (Schema::hasTable('referral_campaigns')) {
                $activeCampaigns = DB::table('referral_campaigns')->where('is_active', true)->count();
            }

            // D. Pending Conversions
            $pendingConversions = 0;
            if (Schema::hasTable('referrals')) {
                $pendingConversions = DB::table('referrals')->where('status', 'pending')->count();
            }

            // V-AUDIT-MODULE9-003 (HIGH): Optimize monthly trend with SQL aggregation
            // Previous Issue:
            // - Used get() to load all referrals from last 6 months into memory
            // - With 50,000 referrals, this created a massive Collection object causing memory spikes
            // - PHP-side groupBy was slow and inefficient
            //
            // Fix:
            // - Use SQL-level aggregation with selectRaw and groupBy
            // - Only returns aggregated counts, not full records
            // - Scalable to millions of referrals
            $trend = [];
            if (Schema::hasTable('referrals')) {
                $rawTrend = DB::table('referrals')
                    ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, COUNT(*) as count')
                    ->where('created_at', '>=', Carbon::now()->subMonths(6))
                    ->groupBy('month')
                    ->orderBy('month', 'asc')
                    ->get();

                // Format for frontend (only formatting, not grouping)
                $trend = $rawTrend->map(function ($item) {
                    return [
                        'month' => Carbon::parse($item->month . '-01')->format('M Y'),
                        'count' => (int) $item->count
                    ];
                })->all();
            }

            return response()->json([
                'total_referrals' => $totalReferrals,
                'total_payout' => (float) $totalPayout,
                'active_campaigns' => $activeCampaigns,
                'pending_conversions' => $pendingConversions,
                'conversion_rate' => $totalReferrals > 0 ? round((($totalReferrals - $pendingConversions) / $totalReferrals) * 100, 1) : 0,
                'trend' => $trend
            ]);

        } catch (\Throwable $e) {
            Log::error("Referral Stats Failed: " . $e->getMessage());
            // Return safe fallback
            return response()->json([
                'total_referrals' => 0,
                'total_payout' => 0,
                'active_campaigns' => 0,
                'pending_conversions' => 0,
                'conversion_rate' => 0,
                'trend' => []
            ]);
        }
    }

    // =======================================================================
    // PART 2: CAMPAIGN MANAGEMENT (Returns Array for Frontend)
    // =======================================================================

    public function index(Request $request): JsonResponse
    {
        try {
            $query = DB::table('referral_campaigns'); // Use DB facade for consistency

            if ($request->filled('status')) {
                $status = $request->status === 'active' ? 1 : 0;
                $query->where('is_active', $status);
            }

            // FIX: Return flat array using get() instead of paginate()
            $campaigns = $query->latest()->get();

            $data = $campaigns->map(function ($campaign) {
                // Calculate referrals count safely via subquery logic since we used DB facade
                $referralCount = 0;
                if (Schema::hasTable('referrals')) {
                    $referralCount = DB::table('referrals')
                        ->where('referral_campaign_id', $campaign->id)
                        ->count();
                }

                return [
                    'id' => $campaign->id,
                    'name' => $campaign->name,
                    'reward_amount' => (float) ($campaign->bonus_amount ?? 0),
                    'multiplier' => (float) ($campaign->multiplier ?? 1.0),
                    'start_date' => $this->safeDate($campaign->start_date),
                    'end_date' => $this->safeDate($campaign->end_date),
                    'is_active' => (bool) $campaign->is_active,
                    'total_referrals' => $referralCount,
                ];
            });

            return response()->json($data);

        } catch (\Throwable $e) {
            return response()->json([], 200); // Return empty array on error
        }
    }

    public function store(Request $request): JsonResponse
    {
        $maxMultiplier = function_exists('setting') ? (float) setting('max_referral_multiplier', self::MAX_MULTIPLIER) : self::MAX_MULTIPLIER;
        $maxBonus = function_exists('setting') ? (float) setting('max_referral_bonus', self::MAX_BONUS_AMOUNT) : self::MAX_BONUS_AMOUNT;

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'multiplier' => "required|numeric|min:1|max:$maxMultiplier",
            'bonus_amount' => "required|numeric|min:0|max:$maxBonus",
            'is_active' => 'boolean'
        ]);

        try {
            // Using DB facade for insert
            $id = DB::table('referral_campaigns')->insertGetId(array_merge($validated, [
                'created_at' => now(),
                'updated_at' => now()
            ]));
            
            $campaign = DB::table('referral_campaigns')->find($id);
            return response()->json(['message' => 'Campaign created', 'data' => $campaign], 201);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id): JsonResponse
    {
        $campaign = DB::table('referral_campaigns')->where('id', $id)->first();
        if (!$campaign) return response()->json(['message' => 'Campaign not found'], 404);

        $maxMultiplier = function_exists('setting') ? (float) setting('max_referral_multiplier', self::MAX_MULTIPLIER) : self::MAX_MULTIPLIER;
        $maxBonus = function_exists('setting') ? (float) setting('max_referral_bonus', self::MAX_BONUS_AMOUNT) : self::MAX_BONUS_AMOUNT;

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'start_date' => 'sometimes|required|date',
            'end_date' => 'sometimes|required|date|after:start_date',
            'multiplier' => "sometimes|required|numeric|min:1|max:$maxMultiplier",
            'bonus_amount' => "sometimes|required|numeric|min:0|max:$maxBonus",
            'is_active' => 'sometimes|boolean'
        ]);

        DB::table('referral_campaigns')->where('id', $id)->update(array_merge($validated, [
            'updated_at' => now()
        ]));

        $updatedCampaign = DB::table('referral_campaigns')->where('id', $id)->first();
        return response()->json(['message' => 'Campaign updated', 'data' => $updatedCampaign]);
    }

    public function destroy($id): JsonResponse
    {
        $deleted = DB::table('referral_campaigns')->where('id', $id)->delete();
        if ($deleted) {
            return response()->json(['message' => 'Campaign deleted']);
        }
        return response()->json(['message' => 'Campaign not found'], 404);
    }

    // =======================================================================
    // PART 3: INDIVIDUAL REFERRALS
    // =======================================================================

    public function listReferrals(Request $request): JsonResponse
    {
        try {
            $query = DB::table('referrals')
                ->join('users as referrer', 'referrals.referrer_id', '=', 'referrer.id')
                ->join('users as referred', 'referrals.referred_id', '=', 'referred.id')
                ->leftJoin('referral_campaigns', 'referrals.referral_campaign_id', '=', 'referral_campaigns.id')
                ->select(
                    'referrals.*',
                    'referrer.username as referrer_name',
                    'referrer.email as referrer_email',
                    'referred.username as referred_name',
                    'referral_campaigns.name as campaign_name'
                );

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('referrer.username', 'like', "%{$search}%")
                      ->orWhere('referrer.email', 'like', "%{$search}%");
                });
            }

            $referrals = $query->latest('referrals.created_at')->paginate(15);

            $data = $referrals->through(function ($ref) {
                return [
                    'id' => $ref->id,
                    'referrer' => $ref->referrer_name ?? 'Unknown',
                    'referred_user' => $ref->referred_name ?? 'Unknown',
                    'status' => $ref->status,
                    'campaign' => $ref->campaign_name ?? 'Default',
                    'created_at' => $this->safeDate($ref->created_at),
                    'completed_at' => $this->safeDate($ref->completed_at),
                ];
            });

            return response()->json($data);

        } catch (\Throwable $e) {
            Log::error("Referral List Failed: " . $e->getMessage());
            return response()->json(['data' => []]);
        }
    }

    private function safeDate($date)
    {
        if (empty($date)) return '-';
        try {
            return Carbon::parse($date)->format('d M Y');
        } catch (\Throwable $e) {
            return '-';
        }
    }
}
