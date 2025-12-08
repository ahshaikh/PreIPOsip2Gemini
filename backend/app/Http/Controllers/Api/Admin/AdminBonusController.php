<?php

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

class AdminBonusController extends Controller
{
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

        // Create reversal transaction
        $reversal = $bonus->reverse($validated['reason']);

        Log::info("Admin reversed bonus #{$bonus->id}. Reason: {$validated['reason']}");

        return response()->json([
            'success' => true,
            'message' => "Bonus of ₹{$bonus->amount} reversed successfully",
            'reversal' => $reversal,
        ]);
    }

    /**
     * Test/calculate bonus for a hypothetical scenario
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

        $plan = \App\Models\Plan::with('configs')->findOrFail($validated['plan_id']);
        $amount = (float) $validated['payment_amount'];
        $month = (int) $validated['payment_month'];
        $multiplier = (float) ($validated['bonus_multiplier'] ?? 1.0);
        $consecutivePayments = (int) ($validated['consecutive_payments'] ?? $month);

        $bonuses = [];
        $totalBonus = 0;

        // Apply multiplier cap
        $maxMultiplier = (float) setting('max_bonus_multiplier', 10.0);
        $multiplier = min($multiplier, $maxMultiplier);

        // 1. Progressive Bonus
        if (setting('progressive_bonus_enabled', true)) {
            $config = $plan->getConfig('progressive_config', [
                'rate' => 0.5, 'start_month' => 4, 'max_percentage' => 20, 'overrides' => []
            ]);

            $startMonth = (int) $config['start_month'];

            if ($month >= $startMonth) {
                $overrides = $config['overrides'] ?? [];
                $baseRate = 0;

                if (isset($overrides[$month])) {
                    $baseRate = (float) $overrides[$month];
                } else {
                    $growthFactor = $month - $startMonth + 1;
                    $baseRate = $growthFactor * ((float) $config['rate']);
                }

                $maxPercent = $config['max_percentage'] ?? 100;
                if ($baseRate > $maxPercent) $baseRate = $maxPercent;

                $progressiveBonus = ($baseRate / 100) * $amount * $multiplier;
                $progressiveBonus = $this->applyRounding($progressiveBonus);

                if ($progressiveBonus > 0) {
                    $bonuses[] = [
                        'type' => 'progressive',
                        'amount' => $progressiveBonus,
                        'calculation' => "{$baseRate}% × ₹{$amount} × {$multiplier}x = ₹{$progressiveBonus}",
                    ];
                    $totalBonus += $progressiveBonus;
                }
            }
        }

        // 2. Milestone Bonus
        if (setting('milestone_bonus_enabled', true)) {
            $config = $plan->getConfig('milestone_config', []);

            foreach ($config as $milestone) {
                if ($month === (int)$milestone['month']) {
                    if ($consecutivePayments >= $month) {
                        $milestoneBonus = ((float)$milestone['amount']) * $multiplier;
                        $milestoneBonus = $this->applyRounding($milestoneBonus);

                        $bonuses[] = [
                            'type' => 'milestone',
                            'amount' => $milestoneBonus,
                            'calculation' => "₹{$milestone['amount']} × {$multiplier}x = ₹{$milestoneBonus}",
                        ];
                        $totalBonus += $milestoneBonus;
                    }
                }
            }
        }

        // 3. Consistency Bonus
        if (setting('consistency_bonus_enabled', true) && $validated['is_on_time']) {
            $config = $plan->getConfig('consistency_config', ['amount_per_payment' => 0]);
            $consistencyBonus = (float) $config['amount_per_payment'];

            if (isset($config['streaks']) && is_array($config['streaks'])) {
                foreach ($config['streaks'] as $streakRule) {
                    if ($consecutivePayments === (int)$streakRule['months']) {
                        $consistencyBonus *= (float)$streakRule['multiplier'];
                        break;
                    }
                }
            }

            $consistencyBonus = $this->applyRounding($consistencyBonus);

            if ($consistencyBonus > 0) {
                $bonuses[] = [
                    'type' => 'consistency',
                    'amount' => $consistencyBonus,
                    'calculation' => "₹{$config['amount_per_payment']} (with streak multiplier) = ₹{$consistencyBonus}",
                ];
                $totalBonus += $consistencyBonus;
            }
        }

        return response()->json([
            'total_bonus' => $totalBonus,
            'bonuses' => $bonuses,
            'settings' => [
                'multiplier_applied' => $multiplier,
                'max_multiplier_cap' => $maxMultiplier,
                'rounding_decimals' => setting('bonus_rounding_decimals', 2),
                'rounding_mode' => setting('bonus_rounding_mode', 'round'),
            ],
        ]);
    }

    /**
     * Apply rounding based on settings
     */
    private function applyRounding(float $amount): float
    {
        $decimals = (int) setting('bonus_rounding_decimals', 2);
        $mode = setting('bonus_rounding_mode', 'round');

        return match ($mode) {
            'floor' => floor($amount * pow(10, $decimals)) / pow(10, $decimals),
            'ceil' => ceil($amount * pow(10, $decimals)) / pow(10, $decimals),
            default => round($amount, $decimals),
        };
    }
    /**
     * Award a special bonus to a user (Admin only)
     *
     * POST /api/v1/admin/bonuses/award-special
     */
    public function awardSpecialBonus(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'amount' => 'required|numeric|min:1',
            'reason' => 'required|string|max:255',
        ]);

        $user = User::findOrFail($validated['user_id']);
        $amount = (float) $validated['amount'];
        $reason = $validated['reason'];

        // Create special bonus transaction
        $bonus = BonusTransaction::create([
            'user_id' => $user->id,
            'subscription_id' => $user->subscriptions()->latest()->first()?->id,
            'payment_id' => null, // No specific payment associated
            'type' => 'special_bonus',
            'amount' => $amount,
            'multiplier_applied' => 1.0,
            'base_amount' => $amount,
            'description' => "Special Bonus: {$reason}"
        ]);

        // Send notification to user
        $user->notify(new BonusCredited($amount, 'Special'));

        Log::info("Admin awarded special bonus: ₹{$amount} to User {$user->id}. Reason: {$reason}");

        return response()->json([
            'success' => true,
            'message' => "Special bonus of ₹{$amount} awarded to {$user->username}",
            'bonus' => $bonus,
        ]);
    }

    /**
     * Award bulk special bonuses to multiple users
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
        $successCount = 0;

        foreach ($userIds as $userId) {
            $user = User::find($userId);
            if (!$user) continue;

            try {
                $bonus = BonusTransaction::create([
                    'user_id' => $user->id,
                    'subscription_id' => $user->subscriptions()->latest()->first()?->id,
                    'payment_id' => null,
                    'type' => 'special_bonus',
                    'amount' => $amount,
                    'multiplier_applied' => 1.0,
                    'base_amount' => $amount,
                    'description' => "Special Bonus: {$reason}"
                ]);

                $user->notify(new BonusCredited($amount, 'Special'));
                $successCount++;
            } catch (\Exception $e) {
                Log::error("Failed to award bonus to User {$userId}: " . $e->getMessage());
            }
        }

        Log::info("Admin awarded bulk bonuses: ₹{$amount} to {$successCount} users. Reason: {$reason}");

        return response()->json([
            'success' => true,
            'message' => "Special bonus of ₹{$amount} awarded to {$successCount} users",
            'awarded_count' => $successCount,
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
                    BonusTransaction::create([
                        'user_id' => $user->id,
                        'subscription_id' => $user->subscriptions()->latest()->first()?->id,
                        'payment_id' => null,
                        'type' => 'special_bonus',
                        'amount' => (float) $amount,
                        'multiplier_applied' => 1.0,
                        'base_amount' => (float) $amount,
                        'description' => "Bulk Bonus (CSV): {$reason}"
                    ]);

                    $user->notify(new BonusCredited((float) $amount, 'Special'));
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
