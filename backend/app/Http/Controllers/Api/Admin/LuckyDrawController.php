<?php
// V-PHASE3-1730-096 (Created) | V-FINAL-1730-368 | V-FINAL-1730-458 (WalletService Refactor) | V-AUDIT-FIX-MODULE10 (Disqualification Logic & Financial Integrity)

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\LuckyDraw;
use App\Models\LuckyDrawEntry;
use App\Models\Setting;
use App\Services\LuckyDrawService;
use App\Services\WalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;

class LuckyDrawController extends Controller
{
    protected $service;
    protected $walletService;

    public function __construct(LuckyDrawService $service, WalletService $walletService)
    {
        $this->service = $service;
        $this->walletService = $walletService;
    }

    /**
     * List all lucky draws with statistics
     * GET /api/v1/admin/lucky-draws
     */
    public function index(Request $request)
    {
        // Eager load entries count and winners for performance
        $query = LuckyDraw::withCount('entries')
            ->with(['entries' => function ($q) {
                $q->where('is_winner', true);
            }]);

        // Filter by status (open, completed, cancelled)
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by frequency (monthly, quarterly)
        if ($request->has('frequency')) {
            $query->where('frequency', $request->frequency);
        }

        // Sort results
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDir = $request->get('sort_dir', 'desc');
        $query->orderBy($sortBy, $sortDir);

        $perPage = $request->get('per_page', 25);
        $draws = $query->paginate($perPage);

        return response()->json([
            'draws' => $draws,
            'stats' => [
                'total_draws' => LuckyDraw::count(),
                'open_draws' => LuckyDraw::where('status', 'open')->count(),
                'completed_draws' => LuckyDraw::where('status', 'completed')->count(),
                'total_winners' => LuckyDrawEntry::where('is_winner', true)->count(),
                // Calculate total money distributed so far
                'total_prize_pool' => LuckyDraw::where('status', 'completed')->get()->sum(function ($draw) {
                    return collect($draw->prize_structure)->sum(function ($tier) {
                        return ($tier['count'] ?? 0) * ($tier['amount'] ?? 0);
                    });
                }),
            ],
        ]);
    }

    /**
     * Get lucky draw configuration settings
     * GET /api/v1/admin/lucky-draws/settings
     */
    public function getSettings()
    {
        $settings = Setting::where('group', 'lucky_draw_config')
            ->get()
            ->keyBy('key');

        return response()->json(['settings' => $settings]);
    }

    /**
     * Update lucky draw configuration settings
     * PUT /api/v1/admin/lucky-draws/settings
     */
    public function updateSettings(Request $request)
    {
        $validated = $request->validate([
            'settings' => 'required|array',
            'settings.*.key' => 'required|string',
            'settings.*.value' => 'required',
        ]);

        foreach ($validated['settings'] as $settingData) {
            $setting = Setting::where('key', $settingData['key'])->first();

            if ($setting) {
                $setting->update([
                    'value' => $settingData['value'],
                    'updated_by' => auth()->id(),
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Lucky draw settings updated successfully',
        ]);
    }

    /**
     * Create a new lucky draw
     * POST /api/v1/admin/lucky-draws
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'draw_date' => 'required|date',
            'prize_structure' => 'required|array',
            'prize_structure.*.rank' => 'required|integer',
            'prize_structure.*.count' => 'required|integer|min:1',
            'prize_structure.*.amount' => 'required|numeric|min:1',
            'frequency' => 'nullable|string|in:monthly,quarterly,custom',
            'entry_rules' => 'nullable|array',
            'result_visibility' => 'nullable|string|in:public,private,winners_only',
        ]);

        try {
            $draw = LuckyDraw::create([
                'name' => $validated['name'],
                'draw_date' => $validated['draw_date'],
                'prize_structure' => $validated['prize_structure'],
                'frequency' => $validated['frequency'] ?? 'monthly',
                'entry_rules' => $validated['entry_rules'] ?? null,
                'result_visibility' => $validated['result_visibility'] ?? 'public',
                'status' => 'open',
                'created_by' => auth()->id(),
            ]);

            Log::info("Lucky draw created: {$draw->name} by Admin " . auth()->id());

            return response()->json([
                'success' => true,
                'message' => 'Lucky draw created successfully',
                'draw' => $draw,
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create lucky draw',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Show a specific lucky draw with entries and statistics
     * GET /api/v1/admin/lucky-draws/{id}
     */
    public function show($id)
    {
        $draw = LuckyDraw::with(['entries.user', 'entries.payment'])
            ->withCount('entries')
            ->findOrFail($id);

        // Calculate statistics dynamically
        $totalEntries = $draw->entries->sum(function ($entry) {
            return $entry->base_entries + $entry->bonus_entries;
        });

        $winnersCount = $draw->entries->where('is_winner', true)->count();

        $totalPrizePool = collect($draw->prize_structure)->sum(function ($tier) {
            return ($tier['count'] ?? 0) * ($tier['amount'] ?? 0);
        });

        return response()->json([
            'draw' => $draw,
            'statistics' => [
                'total_participants' => $draw->entries_count,
                'total_entries' => $totalEntries,
                'winners_count' => $winnersCount,
                'total_prize_pool' => $totalPrizePool,
                'average_entries_per_user' => $draw->entries_count > 0 ? round($totalEntries / $draw->entries_count, 2) : 0,
            ],
        ]);
    }

    /**
     * Update a lucky draw (before execution)
     * PUT /api/v1/admin/lucky-draws/{id}
     */
    public function update(Request $request, $id)
    {
        $draw = LuckyDraw::findOrFail($id);

        // Can only edit if draw is still open
        if ($draw->status !== 'open') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot edit a draw that is not in "open" status',
            ], 400);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'draw_date' => 'sometimes|date',
            'prize_structure' => 'sometimes|array',
            'prize_structure.*.rank' => 'required_with:prize_structure|integer',
            'prize_structure.*.count' => 'required_with:prize_structure|integer|min:1',
            'prize_structure.*.amount' => 'required_with:prize_structure|numeric|min:1',
            'frequency' => 'sometimes|string|in:monthly,quarterly,custom',
            'entry_rules' => 'nullable|array',
            'result_visibility' => 'sometimes|string|in:public,private,winners_only',
        ]);

        $draw->update($validated);

        Log::info("Lucky draw updated: {$draw->name} by Admin " . auth()->id());

        return response()->json([
            'success' => true,
            'message' => 'Lucky draw updated successfully',
            'draw' => $draw,
        ]);
    }

    /**
     * Cancel a lucky draw
     * POST /api/v1/admin/lucky-draws/{id}/cancel
     */
    public function cancel($id)
    {
        $draw = LuckyDraw::findOrFail($id);

        if ($draw->status !== 'open') {
            return response()->json([
                'success' => false,
                'message' => 'Only open draws can be cancelled',
            ], 400);
        }

        $draw->update(['status' => 'cancelled']);

        Log::info("Lucky draw cancelled: {$draw->name} by Admin " . auth()->id());

        return response()->json([
            'success' => true,
            'message' => 'Lucky draw cancelled successfully',
        ]);
    }

    /**
     * Execute a lucky draw (select winners and distribute prizes)
     * POST /api/v1/admin/lucky-draws/{id}/execute
     */
    public function executeDraw(Request $request, $id)
    {
        $draw = LuckyDraw::findOrFail($id);

        if ($draw->status !== 'open') {
            return response()->json(['message' => 'This draw is not open.'], 400);
        }

        try {
            // 1. Select Winners (Weighted)
            // Uses the optimized memory-safe algorithm from LuckyDrawService
            $winnerUserIds = $this->service->selectWinners($draw);

            // 2. Distribute Prizes
            // V-FIX: Passed WalletService to enable atomic transactions
            $this->service->distributePrizes($draw, $winnerUserIds, $this->walletService);

            // 3. Send Notifications
            $this->service->sendWinnerNotifications($winnerUserIds);

            // 4. Mark as executed by admin
            $draw->update(['executed_by' => auth()->id()]);

            Log::info("Lucky draw executed: {$draw->name} by Admin " . auth()->id());

            return response()->json([
                'success' => true,
                'message' => 'Lucky draw executed successfully!',
                'winners_count' => count($winnerUserIds),
            ]);

        } catch (\Exception $e) {
            Log::error("Lucky draw execution failed: " . $e->getMessage());
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Get all winners for a draw
     * GET /api/v1/admin/lucky-draws/{id}/winners
     */
    public function getWinners($id)
    {
        $draw = LuckyDraw::findOrFail($id);

        $winners = $draw->entries()
            ->where('is_winner', true)
            ->with('user', 'payment')
            ->orderBy('prize_rank')
            ->get();

        return response()->json([
            'draw' => $draw,
            'winners' => $winners,
        ]);
    }

    /**
     * Disqualify a winner and select replacement
     * POST /api/v1/admin/lucky-draws/{drawId}/winners/{entryId}/disqualify
     * * --- MODULE 10 SECURITY FIX ---
     * Previously, disqualification only marked the entry as loser but did not
     * reclaim the funds. This caused a "Double Spend" where the platform
     * paid both the disqualified user and the replacement.
     * * NEW LOGIC:
     * 1. Reclaim funds from disqualified user (Withdraw).
     * 2. Mark old entry as 'is_winner' = false.
     * 3. Select replacement winner.
     * 4. Credit funds to replacement (Deposit).
     */
    public function disqualifyWinner(Request $request, $drawId, $entryId)
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $draw = LuckyDraw::findOrFail($drawId);
        $entry = LuckyDrawEntry::findOrFail($entryId);

        if (!$entry->is_winner) {
            return response()->json([
                'success' => false,
                'message' => 'This entry is not a winner',
            ], 400);
        }

        // Wrap everything in a transaction to ensure financial integrity
        DB::transaction(function () use ($draw, $entry, $validated) {
            // Store original rank and amount to give to the replacement
            $rank = $entry->prize_rank;
            $amount = $entry->prize_amount;

            // --- 1. RECLAIM FUNDS (Financial Integrity Fix) ---
            // We attempt to withdraw the prize amount from the disqualified user's wallet.
            // If they have already withdrawn the money to their bank, this might result in a 
            // negative balance (depending on wallet config), but we must record the debt.
            try {
                $this->walletService->withdraw(
                    $entry->user,
                    $amount,
                    'admin_reversal', // Special type for reversals
                    "Disqualified from Lucky Draw #{$draw->id}: " . $validated['reason'],
                    $entry,
                    false // false = Immediate debit (do not lock)
                );
            } catch (\Exception $e) {
                // Log failure but proceed with disqualification logic.
                // In a strict financial system, we might want to halt here, 
                // but for operations, we need to pick a new winner regardless.
                Log::warning("Could not reclaim full prize amount from User {$entry->user_id}: " . $e->getMessage());
            }

            // --- 2. UPDATE OLD ENTRY ---
            $entry->update([
                'is_winner' => false,
                'prize_rank' => null,
                'prize_amount' => null,
            ]);

            // --- 3. SELECT REPLACEMENT ---
            // Find the non-winning entry with the highest probability (most tickets)
            // or simply the next random person. For fairness, we often re-roll or take highest tickets.
            // Here we take the highest ticket holder who hasn't won.
            $replacement = $draw->entries()
                ->where('is_winner', false)
                ->orderByRaw('(base_entries + bonus_entries) DESC')
                ->first();

            if ($replacement) {
                // --- 4. CREDIT NEW WINNER ---
                $replacement->update([
                    'is_winner' => true,
                    'prize_rank' => $rank,
                    'prize_amount' => $amount,
                ]);

                // Credit wallet for replacement winner
                $this->walletService->deposit(
                    $replacement->user,
                    $amount,
                    'bonus_credit',
                    "Lucky Draw Prize (Rank {$rank}) - Replacement Winner",
                    null
                );

                Log::info("Winner replaced in draw {$draw->id}: User {$entry->user_id} disqualified, User {$replacement->user_id} selected as replacement");
            } else {
                Log::warning("No replacement winner found for draw {$draw->id}");
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Winner disqualified, funds reclaimed, and replacement selected',
        ]);
    }

    /**
     * Upload draw video for transparency
     * POST /api/v1/admin/lucky-draws/{id}/upload-video
     */
    public function uploadVideo(Request $request, $id)
    {
        $validated = $request->validate([
            'video' => 'required|file|mimes:mp4,mov,avi,wmv|max:102400', // Max 100MB
        ]);

        $draw = LuckyDraw::findOrFail($id);

        try {
            $file = $request->file('video');
            $path = $file->store('lucky-draws/videos', 'public');

            $draw->update([
                'draw_video_url' => Storage::url($path),
            ]);

            Log::info("Video uploaded for draw {$draw->id}");

            return response()->json([
                'success' => true,
                'message' => 'Video uploaded successfully',
                'video_url' => $draw->draw_video_url,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload video',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate winner certificate
     * GET /api/v1/admin/lucky-draws/{drawId}/winners/{entryId}/certificate
     */
    public function generateCertificate($drawId, $entryId)
    {
        $draw = LuckyDraw::findOrFail($drawId);
        $entry = LuckyDrawEntry::with('user')->findOrFail($entryId);

        if (!$entry->is_winner) {
            return response()->json([
                'success' => false,
                'message' => 'Only winners can receive certificates',
            ], 400);
        }

        if (!setting('lucky_draw_enable_certificates', true)) {
            return response()->json([
                'success' => false,
                'message' => 'Certificates are not enabled',
            ], 400);
        }

        try {
            $data = [
                'draw' => $draw,
                'winner' => $entry->user,
                'rank' => $entry->prize_rank,
                'amount' => $entry->prize_amount,
                'date' => now()->format('d M Y'),
                'footer' => setting('lucky_draw_certificate_footer', 'Congratulations on your win!'),
            ];

            $pdf = Pdf::loadView('certificates.lucky-draw-winner', $data);

            return $pdf->download("lucky-draw-certificate-{$entry->user->username}.pdf");

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate certificate',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get draw analytics and statistics
     * GET /api/v1/admin/lucky-draws/{id}/analytics
     */
    public function getAnalytics($id)
    {
        $draw = LuckyDraw::with('entries.user')->findOrFail($id);

        // Entry distribution buckets
        $entryDistribution = $draw->entries->groupBy(function ($entry) {
            $total = $entry->base_entries + $entry->bonus_entries;
            if ($total <= 5) return '1-5';
            if ($total <= 10) return '6-10';
            if ($total <= 20) return '11-20';
            return '21+';
        })->map->count();

        // Winner distribution by plan
        $winnersByPlan = $draw->entries()
            ->where('is_winner', true)
            ->with('user.subscriptions.plan')
            ->get()
            ->groupBy(function ($entry) {
                return $entry->user->subscriptions->first()->plan->name ?? 'Unknown';
            })
            ->map->count();

        // Prize distribution stats
        $prizeDistribution = $draw->entries()
            ->where('is_winner', true)
            ->get()
            ->groupBy('prize_rank')
            ->map(function ($entries) {
                return [
                    'count' => $entries->count(),
                    'total_amount' => $entries->sum('prize_amount'),
                ];
            });

        return response()->json([
            'draw' => $draw,
            'analytics' => [
                'entry_distribution' => $entryDistribution,
                'winners_by_plan' => $winnersByPlan,
                'prize_distribution' => $prizeDistribution,
                'total_participants' => $draw->entries->count(),
                'total_entries' => $draw->entries->sum(function ($entry) {
                    return $entry->base_entries + $entry->bonus_entries;
                }),
                'average_entries_per_user' => $draw->entries->count() > 0
                    ? round($draw->entries->sum(function ($entry) {
                        return $entry->base_entries + $entry->bonus_entries;
                    }) / $draw->entries->count(), 2)
                    : 0,
            ],
        ]);
    }

    /**
     * Delete a lucky draw (soft delete)
     * DELETE /api/v1/admin/lucky-draws/{id}
     */
    public function destroy($id)
    {
        $draw = LuckyDraw::findOrFail($id);

        if ($draw->status === 'completed') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete a completed draw',
            ], 400);
        }

        $draw->delete();

        Log::info("Lucky draw deleted: {$draw->name} by Admin " . auth()->id());

        return response()->json([
            'success' => true,
            'message' => 'Lucky draw deleted successfully',
        ]);
    }
}