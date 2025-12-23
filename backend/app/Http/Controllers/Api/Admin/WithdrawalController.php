<?php
// V-PHASE3-1730-095 (Created) | V-FINAL-1730-361 (Refactored)

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Withdrawal;
use App\Services\WithdrawalService; // <-- IMPORT
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WithdrawalController extends Controller
{
    // Inject the service
    protected $withdrawalService;
    public function __construct(WithdrawalService $withdrawalService)
    {
        $this->withdrawalService = $withdrawalService;
    }
    
    public function index(Request $request)
    {
        $status = $request->query('status', 'pending');
        $query = Withdrawal::where('status', $status)
            ->with('user:id,username');

        $perPage = (int) setting('records_per_page', 15);

        return response()->json(
            $query->latest()->paginate($perPage)
        );
    }
    
    public function approve(Request $request, Withdrawal $withdrawal)
    {
        try {
            $this->withdrawalService->approveWithdrawal($withdrawal, $request->user());
            return response()->json(['message' => 'Withdrawal approved. Ready for processing.']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function reject(Request $request, Withdrawal $withdrawal)
    {
        $validated = $request->validate(['reason' => 'required|string|min:5']);
        
        try {
            $this->withdrawalService->rejectWithdrawal($withdrawal, $request->user(), $validated['reason']);
            return response()->json(['message' => 'Withdrawal rejected and funds returned to user.']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }
    
    public function complete(Request $request, Withdrawal $withdrawal)
    {
        $validated = $request->validate(['utr_number' => 'required|string|max:100']);

        try {
            $this->withdrawalService->completeWithdrawal($withdrawal, $request->user(), $validated['utr_number']);
            return response()->json(['message' => 'Withdrawal marked as complete.']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * Get withdrawal details
     * GET /api/v1/admin/withdrawal-queue/{withdrawal}
     */
    public function show(Withdrawal $withdrawal)
    {
        $withdrawal->load([
            'user:id,username,email,mobile',
            'wallet',
            'admin:id,username,email',
        ]);

        // Get related transactions
        $transactions = \App\Models\Transaction::where('reference_type', Withdrawal::class)
            ->where('reference_id', $withdrawal->id)
            ->get();

        return response()->json([
            'withdrawal' => $withdrawal,
            'transactions' => $transactions,
        ]);
    }

    /**
     * Get withdrawal settings
     * GET /api/v1/admin/withdrawal-settings
     */
    public function getSettings()
    {
        $settings = [
            'enabled' => setting('withdrawal_enabled', true),
            'min_amount' => setting('min_withdrawal_amount', 1000),
            'auto_approval_max_amount' => setting('auto_approval_max_amount', 5000),
            'tds_rate' => setting('tds_rate', 0.10),
            'tds_threshold' => setting('tds_threshold', 5000),
            'processing_days' => setting('withdrawal_processing_days', 3),
            'priority_processing_enabled' => setting('withdrawal_priority_processing_enabled', true),
            'priority_threshold' => setting('withdrawal_priority_threshold', 50000),
            'bulk_processing_limit' => setting('withdrawal_bulk_processing_limit', 50),
        ];

        return response()->json(['settings' => $settings]);
    }

    /**
     * Update withdrawal settings
     * PUT /api/v1/admin/withdrawal-settings
     */
    public function updateSettings(Request $request)
    {
        $validated = $request->validate([
            'enabled' => 'required|boolean',
            'min_amount' => 'required|numeric|min:100',
            'auto_approval_max_amount' => 'required|numeric|min:0',
            'tds_rate' => 'required|numeric|min:0|max:1',
            'tds_threshold' => 'required|numeric|min:0',
            'processing_days' => 'required|integer|min:1|max:30',
            'priority_processing_enabled' => 'required|boolean',
            'priority_threshold' => 'required|numeric|min:0',
            'bulk_processing_limit' => 'required|integer|min:1|max:100',
        ]);

        $settingMap = [
            'enabled' => 'withdrawal_enabled',
            'min_amount' => 'min_withdrawal_amount',
            'auto_approval_max_amount' => 'auto_approval_max_amount',
            'tds_rate' => 'tds_rate',
            'tds_threshold' => 'tds_threshold',
            'processing_days' => 'withdrawal_processing_days',
            'priority_processing_enabled' => 'withdrawal_priority_processing_enabled',
            'priority_threshold' => 'withdrawal_priority_threshold',
            'bulk_processing_limit' => 'withdrawal_bulk_processing_limit',
        ];

        foreach ($validated as $key => $value) {
            $settingKey = $settingMap[$key];
            \App\Models\Setting::updateOrCreate(
                ['key' => $settingKey],
                ['value' => $value, 'group' => $key === 'enabled' ? 'system' : 'withdrawal_config']
            );
        }

        \Illuminate\Support\Facades\Cache::forget('settings');

        return response()->json(['message' => 'Withdrawal settings updated successfully']);
    }

    /**
     * Get withdrawal fee tiers
     * GET /api/v1/admin/withdrawal-fee-tiers
     */
    public function getFeeTiers()
    {
        $tiers = [
            [
                'tier' => 1,
                'max_amount' => setting('withdrawal_fee_tier_1_max', 5000),
                'flat_fee' => setting('withdrawal_fee_tier_1_flat', 0),
                'percent_fee' => setting('withdrawal_fee_tier_1_percent', 0),
            ],
            [
                'tier' => 2,
                'max_amount' => setting('withdrawal_fee_tier_2_max', 25000),
                'flat_fee' => setting('withdrawal_fee_tier_2_flat', 10),
                'percent_fee' => setting('withdrawal_fee_tier_2_percent', 0.5),
            ],
            [
                'tier' => 3,
                'max_amount' => setting('withdrawal_fee_tier_3_max', 100000),
                'flat_fee' => setting('withdrawal_fee_tier_3_flat', 25),
                'percent_fee' => setting('withdrawal_fee_tier_3_percent', 1),
            ],
            [
                'tier' => 4,
                'max_amount' => null, // Above tier 3
                'flat_fee' => setting('withdrawal_fee_tier_4_flat', 50),
                'percent_fee' => setting('withdrawal_fee_tier_4_percent', 1.5),
            ],
        ];

        return response()->json(['tiers' => $tiers]);
    }

    /**
     * Update withdrawal fee tier
     * PUT /api/v1/admin/withdrawal-fee-tiers/{tier}
     */
    public function updateFeeTier(Request $request, int $tier)
    {
        if ($tier < 1 || $tier > 4) {
            return response()->json(['message' => 'Invalid tier number. Must be 1-4.'], 400);
        }

        $validated = $request->validate([
            'max_amount' => $tier < 4 ? 'required|numeric|min:0' : 'nullable',
            'flat_fee' => 'required|numeric|min:0',
            'percent_fee' => 'required|numeric|min:0|max:100',
        ]);

        if ($tier < 4) {
            \App\Models\Setting::updateOrCreate(
                ['key' => "withdrawal_fee_tier_{$tier}_max"],
                ['value' => $validated['max_amount'], 'group' => 'withdrawal_fees']
            );
        }

        \App\Models\Setting::updateOrCreate(
            ['key' => "withdrawal_fee_tier_{$tier}_flat"],
            ['value' => $validated['flat_fee'], 'group' => 'withdrawal_fees']
        );

        \App\Models\Setting::updateOrCreate(
            ['key' => "withdrawal_fee_tier_{$tier}_percent"],
            ['value' => $validated['percent_fee'], 'group' => 'withdrawal_fees']
        );

        \Illuminate\Support\Facades\Cache::forget('settings');

        return response()->json(['message' => "Tier {$tier} settings updated successfully"]);
    }

    /**
     * Bulk approve withdrawals
     * POST /api/v1/admin/withdrawal-queue/bulk-approve
     */
    public function bulkApprove(Request $request)
    {
        $validated = $request->validate([
            'withdrawal_ids' => 'required|array|max:' . setting('withdrawal_bulk_processing_limit', 50),
            'withdrawal_ids.*' => 'required|exists:withdrawals,id',
        ]);

        $results = ['success' => [], 'failed' => []];

        DB::beginTransaction();
        try {
            foreach ($validated['withdrawal_ids'] as $id) {
                $withdrawal = Withdrawal::find($id);

                if ($withdrawal->status !== 'pending') {
                    $results['failed'][] = ['id' => $id, 'reason' => 'Not in pending status'];
                    continue;
                }

                try {
                    $this->withdrawalService->approveWithdrawal($withdrawal, $request->user());
                    $results['success'][] = $id;
                } catch (\Exception $e) {
                    $results['failed'][] = ['id' => $id, 'reason' => $e->getMessage()];
                }
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Bulk operation failed: ' . $e->getMessage()], 500);
        }

        return response()->json([
            'message' => 'Bulk approval completed',
            'results' => $results,
        ]);
    }

    /**
     * Bulk reject withdrawals
     * POST /api/v1/admin/withdrawal-queue/bulk-reject
     */
    public function bulkReject(Request $request)
    {
        $validated = $request->validate([
            'withdrawal_ids' => 'required|array|max:' . setting('withdrawal_bulk_processing_limit', 50),
            'withdrawal_ids.*' => 'required|exists:withdrawals,id',
            'reason' => 'required|string|min:5',
        ]);

        $results = ['success' => [], 'failed' => []];

        DB::beginTransaction();
        try {
            foreach ($validated['withdrawal_ids'] as $id) {
                $withdrawal = Withdrawal::find($id);

                if ($withdrawal->status !== 'pending') {
                    $results['failed'][] = ['id' => $id, 'reason' => 'Not in pending status'];
                    continue;
                }

                try {
                    $this->withdrawalService->rejectWithdrawal($withdrawal, $request->user(), $validated['reason']);
                    $results['success'][] = $id;
                } catch (\Exception $e) {
                    $results['failed'][] = ['id' => $id, 'reason' => $e->getMessage()];
                }
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Bulk operation failed: ' . $e->getMessage()], 500);
        }

        return response()->json([
            'message' => 'Bulk rejection completed',
            'results' => $results,
        ]);
    }

    /**
     * Bulk complete withdrawals
     * POST /api/v1/admin/withdrawal-queue/bulk-complete
     */
    public function bulkComplete(Request $request)
    {
        $validated = $request->validate([
            'withdrawals' => 'required|array|max:' . setting('withdrawal_bulk_processing_limit', 50),
            'withdrawals.*.id' => 'required|exists:withdrawals,id',
            'withdrawals.*.utr_number' => 'required|string|max:100',
        ]);

        $results = ['success' => [], 'failed' => []];

        DB::beginTransaction();
        try {
            foreach ($validated['withdrawals'] as $item) {
                $withdrawal = Withdrawal::find($item['id']);

                if ($withdrawal->status !== 'approved') {
                    $results['failed'][] = ['id' => $item['id'], 'reason' => 'Not in approved status'];
                    continue;
                }

                try {
                    $this->withdrawalService->completeWithdrawal($withdrawal, $request->user(), $item['utr_number']);
                    $results['success'][] = $item['id'];
                } catch (\Exception $e) {
                    $results['failed'][] = ['id' => $item['id'], 'reason' => $e->getMessage()];
                }
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Bulk operation failed: ' . $e->getMessage()], 500);
        }

        return response()->json([
            'message' => 'Bulk completion processed',
            'results' => $results,
        ]);
    }

    /**
     * Withdrawal analytics
     * GET /api/v1/admin/withdrawal-analytics
     */
    public function analytics(Request $request)
    {
        $validated = $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $startDate = $validated['start_date'] ?? now()->subDays(30);
        $endDate = $validated['end_date'] ?? now();

        $query = Withdrawal::whereBetween('created_at', [$startDate, $endDate]);

        $analytics = [
            'total_withdrawals' => $query->count(),
            'total_amount' => $query->where('status', 'completed')->sum('amount'),
            'total_fees' => $query->where('status', 'completed')->sum('fee'),
            'total_tds' => $query->where('status', 'completed')->sum('tds_deducted'),
            'net_disbursed' => $query->where('status', 'completed')->sum('net_amount'),
            'by_status' => Withdrawal::whereBetween('created_at', [$startDate, $endDate])
                ->selectRaw('status, count(*) as count, sum(amount) as total_amount, sum(net_amount) as total_net')
                ->groupBy('status')
                ->get(),
            'by_priority' => Withdrawal::whereBetween('created_at', [$startDate, $endDate])
                ->selectRaw('priority, count(*) as count, sum(amount) as total')
                ->groupBy('priority')
                ->get(),
            'daily_trend' => Withdrawal::whereBetween('created_at', [$startDate, $endDate])
                ->selectRaw('DATE(created_at) as date, count(*) as count, sum(amount) as total_requested, sum(net_amount) as total_disbursed')
                ->groupBy('date')
                ->orderBy('date')
                ->get(),
            'avg_processing_time' => Withdrawal::whereBetween('created_at', [$startDate, $endDate])
                ->where('status', 'completed')
                ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, created_at, processed_at)) as hours')
                ->value('hours'),
            'pending_queue' => [
                'count' => Withdrawal::where('status', 'pending')->count(),
                'total_amount' => Withdrawal::where('status', 'pending')->sum('amount'),
            ],
            'approved_queue' => [
                'count' => Withdrawal::where('status', 'approved')->count(),
                'total_amount' => Withdrawal::where('status', 'approved')->sum('amount'),
            ],
            'completion_rate' => [
                'total_requests' => Withdrawal::whereBetween('created_at', [$startDate, $endDate])->count(),
                'completed' => Withdrawal::whereBetween('created_at', [$startDate, $endDate])
                    ->where('status', 'completed')->count(),
                'rejected' => Withdrawal::whereBetween('created_at', [$startDate, $endDate])
                    ->where('status', 'rejected')->count(),
            ],
        ];

        return response()->json(['analytics' => $analytics]);
    }

    /**
     * Export withdrawals to CSV
     * GET /api/v1/admin/withdrawal-queue/export
     */
    public function export(Request $request)
    {
        $validated = $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'status' => 'nullable|in:pending,approved,completed,rejected,cancelled',
        ]);

        $query = Withdrawal::with(['user:id,username,email']);

        if (isset($validated['start_date'])) {
            $query->whereDate('created_at', '>=', $validated['start_date']);
        }

        if (isset($validated['end_date'])) {
            $query->whereDate('created_at', '<=', $validated['end_date']);
        }

        if (isset($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        $withdrawals = $query->latest()->get();

        $csv = "ID,User,Email,Amount,Fee,TDS,Net Amount,Status,Priority,UTR,Created At,Processed At\n";
        foreach ($withdrawals as $withdrawal) {
            $csv .= implode(',', [
                $withdrawal->id,
                $withdrawal->user->username ?? 'N/A',
                $withdrawal->user->email ?? 'N/A',
                $withdrawal->amount,
                $withdrawal->fee,
                $withdrawal->tds_deducted,
                $withdrawal->net_amount,
                $withdrawal->status,
                $withdrawal->priority ?? 'normal',
                $withdrawal->utr_number ?? 'N/A',
                $withdrawal->created_at->format('Y-m-d H:i:s'),
                $withdrawal->processed_at?->format('Y-m-d H:i:s') ?? 'N/A',
            ]) . "\n";
        }

        return response($csv)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="withdrawals_export_' . now()->format('Y-m-d') . '.csv"');
    }
}