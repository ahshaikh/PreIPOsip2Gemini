<?php
// V-PHASE4-LEDGER (Ledger Integration + TDS Compliance)

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\BonusTransaction;
use App\Models\Setting;
use App\Notifications\BonusCredited;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Services\WalletService;
use App\Services\TdsCalculationService;
use App\Services\DoubleEntryLedgerService;
use App\Services\Accounting\AdminLedger; // FIX 24: Admin ledger for bonus reversal tracking (DEPRECATED)

class AdminBonusController extends Controller
{
    protected WalletService $walletService;
    protected TdsCalculationService $tdsService;
    protected DoubleEntryLedgerService $ledgerService;

    // FIX 24: Property for AdminLedger (DEPRECATED - use ledgerService instead)
    protected $adminLedger;

    public function __construct(
        WalletService $walletService,
        TdsCalculationService $tdsService,
        DoubleEntryLedgerService $ledgerService,
        AdminLedger $adminLedger
    ) {
        $this->walletService = $walletService;
        $this->tdsService = $tdsService;
        $this->ledgerService = $ledgerService;
        $this->adminLedger = $adminLedger; // FIX 24 (DEPRECATED)
    }

    /**
     * Get all bonus transactions with advanced filtering
     *
     * GET /api/v1/admin/bonuses
     */
    public function index(Request $request)
    {
        $query = BonusTransaction::with(['user', 'subscription', 'payment']);

        // Filter by type
        if ($request->has('type') && $request->type !== 'all') {
            $query->where('type', $request->type);
        }

        // Filter by user
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filter by date range
        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Filter by amount range
        if ($request->has('amount_min')) {
            $query->where('amount', '>=', $request->amount_min);
        }
        if ($request->has('amount_max')) {
            $query->where('amount', '<=', $request->amount_max);
        }

        // Search by description
        if ($request->has('search')) {
            $query->where('description', 'like', '%' . $request->search . '%');
        }

        // Sort
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDir = $request->get('sort_dir', 'desc');
        $query->orderBy($sortBy, $sortDir);

        // Pagination
        $perPage = $request->get('per_page', 25);
        $bonuses = $query->paginate($perPage);

        // Calculate summary statistics
        $totalQuery = clone $query;
        $stats = [
            'total_count' => $totalQuery->count(),
            'total_amount' => $totalQuery->sum('amount'),
            'total_tds' => $totalQuery->sum('tds_deducted'),
            'net_amount' => $totalQuery->sum('amount') - $totalQuery->sum('tds_deducted'),
        ];

        return response()->json([
            'bonuses' => $bonuses,
            'stats' => $stats,
        ]);
    }

    /**
     * Get bonus configuration settings
     *
     * GET /api/v1/admin/bonuses/settings
     */
    public function getSettings()
    {
        $settings = Setting::whereIn('group', ['bonus_controls', 'bonus_config', 'referral_config', 'bonus_processing', 'bonus_formulas'])
            ->get()
            ->groupBy('group');

        return response()->json(['settings' => $settings]);
    }

    /**
     * Update bonus configuration settings
     *
     * PUT /api/v1/admin/bonuses/settings
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

                // Clear cache
                Cache::forget('setting.' . $setting->key);
            }
        }

        Cache::forget('settings');

        return response()->json([
            'success' => true,
            'message' => 'Bonus settings updated successfully',
        ]);
    }

    /**
     * Reverse/cancel a bonus transaction
     *
     * POST /api/v1/admin/bonuses/{id}/reverse
     */
    public function reverseBonus(Request $request, $id)
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:255',
        ]);

        $bonus = BonusTransaction::findOrFail($id);

        // Check if already reversed
        if ($bonus->type === 'reversal') {
            return response()->json([
                'success' => false,
                'message' => 'This transaction is already a reversal',
            ], 400);
        }

        // Check if this bonus has already been reversed
        $existingReversal = BonusTransaction::where('type', 'reversal')
            ->where('description', 'like', "%Reversal of Bonus #{$bonus->id}%")
            ->first();

        if ($existingReversal) {
            return response()->json([
                'success' => false,
                'message' => 'This bonus has already been reversed',
            ], 400);
        }

        // [MODIFIED] Added DB::transaction to ensure we reverse the log AND withdraw the money.
        // Previously, this function only created a log entry (Phantom Reversal).
        try {
            $reversal = DB::transaction(function () use ($bonus, $validated) {

                // 1. Create reversal transaction log (using Model logic if available, or manual create)
                // Assuming $bonus->reverse() returns the new BonusTransaction model
                $reversalTxn = $bonus->reverse($validated['reason']);

                // 2. [ADDED] Actually withdraw the money from the user's wallet
                // This is critical. Without this, the user keeps the money.
                $this->walletService->withdraw(
                    $bonus->user,
                    $bonus->amount, // Amount to remove
                    'bonus_reversal',
                    "Reversal of Bonus #{$bonus->id}: " . $validated['reason'],
                    $reversalTxn // Link this withdrawal to the reversal transaction
                );

                // 3. FIX 24: Record in AdminLedger for complete audit trail
                // When bonus is reversed, admin gets cash back and expense is reversed
                $this->adminLedger->recordBonusReversal(
                    $bonus->amount,
                    $bonus->id,
                    $reversalTxn->id,
                    "Bonus reversal: {$validated['reason']}"
                );

                return $reversalTxn;
            });

            Log::info("Admin reversed bonus #{$bonus->id}. Reason: {$validated['reason']}", [
                'bonus_id' => $bonus->id,
                'reversal_id' => $reversal->id,
                'amount' => $bonus->amount,
                'admin_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => "Bonus of ₹{$bonus->amount} reversed successfully (Funds withdrawn, AdminLedger updated)",
                'reversal' => $reversal,
            ]);

        } catch (\Exception $e) {
            Log::error("Failed to reverse bonus #{$id}: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to reverse bonus: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * V-AUDIT-MODULE8-004 (HIGH): Test/calculate bonus for a hypothetical scenario.
     *
     * REFACTORED: Now delegates to BonusCalculatorService::calculateTestBonuses()
     * instead of duplicating 100+ lines of bonus calculation logic.
     *
     * Benefits:
     * - Eliminates code duplication (DRY principle)
     * - Single source of truth for bonus calculations
     * - Updates to bonus logic automatically reflected in admin test tool
     *
     * POST /api/v1/admin/bonuses/calculate-test
     */
    public function calculateTest(Request $request)
    {
        $validated = $request->validate([
            'payment_amount' => 'required|numeric|min:1',
            'payment_month' => 'required|integer|min:1',
            'is_on_time' => 'required|boolean',
            'plan_id' => 'required|exists:plans,id',
            'bonus_multiplier' => 'nullable|numeric|min:0',
            'consecutive_payments' => 'nullable|integer|min:0',
        ]);

        // V-AUDIT-MODULE8-004: Delegate to BonusCalculatorService instead of duplicating logic
        $bonusService = app(\App\Services\BonusCalculatorService::class);
        $result = $bonusService->calculateTestBonuses($validated);

        return response()->json($result);
    }

    /**
     * Award a special bonus to a user (Admin only)
     *
     * POST /api/v1/admin/bonuses/award-special
     *
     * PHASE 4 LEDGER INTEGRATION:
     * Uses two-step flow for proper bonus accounting:
     *   Step 1: recordBonusWithTds() - DEBIT MARKETING_EXPENSE, CREDIT BONUS_LIABILITY, CREDIT TDS_PAYABLE
     *   Step 2: deposit('bonus_credit') - DEBIT BONUS_LIABILITY, CREDIT USER_WALLET_LIABILITY
     *
     * TDS COMPLIANCE:
     * - Special bonuses are taxable income
     * - TDS is calculated and deducted before crediting to wallet
     */
    public function awardSpecialBonus(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'amount' => 'required|numeric|min:1',
            'reason' => 'required|string|max:255',
        ]);

        $user = User::findOrFail($validated['user_id']);
        $grossAmount = (float) $validated['amount'];
        $reason = $validated['reason'];

        try {
            $bonus = DB::transaction(function () use ($user, $grossAmount, $reason) {

                // 1. Calculate TDS on the bonus amount
                $tdsResult = $this->tdsService->calculate($grossAmount, 'special_bonus');

                // 2. Create special bonus transaction record (with TDS tracking)
                $bonusTransaction = BonusTransaction::create([
                    'user_id' => $user->id,
                    'subscription_id' => $user->subscriptions()->latest()->first()?->id,
                    'payment_id' => null, // No specific payment associated
                    'type' => 'special_bonus',
                    'amount' => $tdsResult->grossAmount, // Gross amount
                    'tds_deducted' => $tdsResult->tdsAmount, // TDS for compliance
                    'multiplier_applied' => 1.0,
                    'base_amount' => $grossAmount,
                    'description' => "Special Bonus: {$reason}"
                ]);

                // 3. PHASE 4: Record bonus accrual in ledger FIRST (Step 1)
                // DEBIT MARKETING_EXPENSE (gross), CREDIT BONUS_LIABILITY (net), CREDIT TDS_PAYABLE (tds)
                $this->ledgerService->recordBonusWithTds(
                    $bonusTransaction,
                    $tdsResult->grossAmount,
                    $tdsResult->tdsAmount
                );

                // 4. Transfer to wallet (Step 2)
                // This triggers recordBonusToWallet(): DEBIT BONUS_LIABILITY, CREDIT USER_WALLET_LIABILITY
                $this->walletService->deposit(
                    $user,
                    $tdsResult->netAmount, // Net amount after TDS
                    'bonus_credit',
                    $tdsResult->getDescription("Special Bonus: {$reason}"),
                    $bonusTransaction
                );

                return $bonusTransaction;
            });

            // Send notification to user (gross amount shown, TDS info in details)
            $user->notify(new BonusCredited($grossAmount, 'Special'));

            Log::info("Admin awarded special bonus with TDS", [
                'user_id' => $user->id,
                'gross_amount' => $grossAmount,
                'reason' => $reason,
                'bonus_id' => $bonus->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => "Special bonus of ₹{$grossAmount} awarded to {$user->username} (Net after TDS: ₹" . ($bonus->amount - $bonus->tds_deducted) . ")",
                'bonus' => $bonus,
            ]);

        } catch (\Exception $e) {
            Log::error("Failed to award special bonus: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to award bonus: ' . $e->getMessage()], 500);
        }
    }


    /**
     * V-AUDIT-MODULE8-005 (MEDIUM): Award bulk special bonuses to multiple users.
     *
     * REFACTORED: Now dispatches AwardBulkBonusJob for each user instead of
     * processing synchronously. Enables parallel processing for 5,000+ users.
     *
     * Benefits:
     * - Immediate response to admin (jobs queued successfully)
     * - Queue workers process in parallel
     * - No request timeout issues
     * - Failed awards don't block others
     * - Progress trackable via queue monitoring
     *
     * POST /api/v1/admin/bonuses/award-bulk
     */
    public function awardBulkBonus(Request $request)
    {
        $validated = $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id',
            'amount' => 'required|numeric|min:1',
            'reason' => 'required|string|max:255',
        ]);

        $userIds = $validated['user_ids'];
        $amount = (float) $validated['amount'];
        $reason = $validated['reason'];
        $count = count($userIds);

        // V-AUDIT-MODULE8-005: Dispatch a job for EACH user instead of processing inline
        // Queue workers will process these in parallel
        foreach ($userIds as $userId) {
            \App\Jobs\AwardBulkBonusJob::dispatch($userId, $amount, $reason);
        }

        Log::info("Admin dispatched {$count} bulk bonus jobs: ₹{$amount} per user. Reason: {$reason}");

        return response()->json([
            'success' => true,
            'message' => "Dispatched {$count} bonus award jobs to queue. Processing in background.",
            'dispatched_count' => $count,
            'note' => 'Check queue monitor or logs for individual award progress.',
        ]);
    }

    /**
     * Upload CSV file for bulk bonus processing
     *
     * POST /api/v1/admin/bonuses/upload-csv
     *
     * CSV format: user_id,amount,reason
     */
    public function uploadCsv(Request $request)
    {
        $validated = $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:10240', // Max 10MB
        ]);

        $file = $request->file('csv_file');
        $path = $file->getRealPath();

        $successCount = 0;
        $failedRows = [];
        $rowNumber = 0;

        if (($handle = fopen($path, 'r')) !== false) {
            // Skip header row
            $header = fgetcsv($handle);

            while (($row = fgetcsv($handle)) !== false) {
                $rowNumber++;

                // Expected format: user_id, amount, reason
                if (count($row) < 3) {
                    $failedRows[] = [
                        'row' => $rowNumber,
                        'reason' => 'Invalid CSV format (expected: user_id, amount, reason)',
                    ];
                    continue;
                }

                $userId = trim($row[0]);
                $amount = trim($row[1]);
                $reason = trim($row[2]);

                // Validate data
                if (!is_numeric($userId) || !is_numeric($amount) || empty($reason)) {
                    $failedRows[] = [
                        'row' => $rowNumber,
                        'reason' => 'Invalid data format',
                        'data' => "user_id: {$userId}, amount: {$amount}, reason: {$reason}",
                    ];
                    continue;
                }

                $user = User::find($userId);
                if (!$user) {
                    $failedRows[] = [
                        'row' => $rowNumber,
                        'reason' => "User ID {$userId} not found",
                    ];
                    continue;
                }

                try {
                    $grossAmount = (float) $amount;

                    // PHASE 4: Added DB::transaction with TDS and ledger integration
                    DB::transaction(function () use ($user, $grossAmount, $reason) {

                        // 1. Calculate TDS on the bonus amount
                        $tdsResult = $this->tdsService->calculate($grossAmount, 'special_bonus');

                        // 2. Create Record (with TDS tracking)
                        $bonusTransaction = BonusTransaction::create([
                            'user_id' => $user->id,
                            'subscription_id' => $user->subscriptions()->latest()->first()?->id,
                            'payment_id' => null,
                            'type' => 'special_bonus',
                            'amount' => $tdsResult->grossAmount,
                            'tds_deducted' => $tdsResult->tdsAmount,
                            'multiplier_applied' => 1.0,
                            'base_amount' => $grossAmount,
                            'description' => "Bulk Bonus (CSV): {$reason}"
                        ]);

                        // 3. PHASE 4: Record bonus accrual in ledger FIRST
                        // DEBIT MARKETING_EXPENSE (gross), CREDIT BONUS_LIABILITY (net), CREDIT TDS_PAYABLE (tds)
                        $this->ledgerService->recordBonusWithTds(
                            $bonusTransaction,
                            $tdsResult->grossAmount,
                            $tdsResult->tdsAmount
                        );

                        // 4. Transfer to wallet
                        // This triggers recordBonusToWallet(): DEBIT BONUS_LIABILITY, CREDIT USER_WALLET_LIABILITY
                        $this->walletService->deposit(
                            $user,
                            $tdsResult->netAmount,
                            'bonus_credit',
                            $tdsResult->getDescription("Bulk Bonus (CSV): {$reason}"),
                            $bonusTransaction
                        );
                    });

                    $user->notify(new BonusCredited($grossAmount, 'Special'));
                    $successCount++;
                } catch (\Exception $e) {
                    $failedRows[] = [
                        'row' => $rowNumber,
                        'reason' => 'Database error: ' . $e->getMessage(),
                    ];
                }
            }

            fclose($handle);
        }

        Log::info("Admin uploaded CSV bulk bonuses: {$successCount} successful, " . count($failedRows) . " failed");

        return response()->json([
            'success' => true,
            'message' => "Bulk bonus processing completed",
            'awarded_count' => $successCount,
            'failed_count' => count($failedRows),
            'failed_rows' => $failedRows,
        ]);
    }
}