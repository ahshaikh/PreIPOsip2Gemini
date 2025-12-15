<?php
// V-FINAL-1730-372 (Created) | V-FINAL-1730-451 (Hardened) | V-FINAL-1730-567 (Hardened) | V-FINAL-1730-573 (Reversals Added) | V-AUDIT-FIX-MODULE11 (Scalability & Locking)

namespace App\Services;

use App\Models\ProfitShare;
use App\Models\Subscription;
use App\Models\BonusTransaction;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserProfitShare;
use App\Services\WalletService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

class ProfitShareService
{
    protected $walletService;

    public function __construct(WalletService $walletService)
    {
        $this->walletService = $walletService;
    }

    /**
     * FSD-REPORT-021: Calculate the distribution.
     * * --- MODULE 11 AUDIT FIX: MEMORY OPTIMIZATION ---
     * Previously, this method loaded ALL eligible subscriptions into memory using $query->get().
     * For a dataset of 20,000+ investors, this would cause a PHP Fatal Error (Allowed memory size exhausted).
     * * The Fix:
     * 1. Pass 1 (Aggregation): Use `chunk()` to calculate total investment weight and tenure without keeping models in RAM.
     * 2. Pass 2 (Processing): Use `cursor()` to iterate through subscriptions one-by-one to calculate shares and insert rows.
     * This keeps memory usage constant O(1) regardless of user count.
     * ------------------------------------------------
     *
     * @param ProfitShare $profitShare The profit share period
     * @param bool $preview If true, doesn't save to database (preview only)
     * @return array Results of calculation
     */
    public function calculateDistribution(ProfitShare $profitShare, bool $preview = false)
    {
        if (!$preview && $profitShare->status !== 'pending') {
            throw new \Exception("This period is not pending calculation.");
        }
        if ($profitShare->total_pool <= 0) {
            if (!$preview) {
                $profitShare->update(['status' => 'cancelled']);
            }
            throw new \Exception("Total pool is zero or negative. No distribution to calculate.");
        }

        // Get eligibility criteria from settings
        $minMonths = (int) setting('profit_share_min_months', 3);
        $minInvestment = (float) setting('profit_share_min_investment', 10000);
        $requireActive = setting('profit_share_require_active_subscription', true);
        $formulaType = setting('profit_share_formula_type', 'weighted_investment');

        // Build query for eligible subscriptions
        // NOTE: We do NOT execute get() here anymore to save memory.
        $query = Subscription::where('start_date', '<=', $profitShare->end_date)
            ->where('amount', '>=', $minInvestment)
            ->whereHas('user', fn($q) => $q->whereDate('created_at', '<=', now()->subMonths($minMonths)))
            ->with('plan.configs', 'user');

        if ($requireActive) {
            $query->where('status', 'active');
        }

        // --- PASS 1: AGGREGATION (Calculate Totals using Chunking) ---
        $totalWeightedInvestment = 0;
        $totalTenure = 0;
        $eligibleCount = 0;

        // Process in chunks of 1000 to keep memory low
        $query->chunk(1000, function ($subscriptions) use (&$totalWeightedInvestment, &$totalTenure, &$eligibleCount, $profitShare) {
            foreach ($subscriptions as $sub) {
                $totalWeightedInvestment += $sub->amount;
                $totalTenure += $sub->start_date->diffInMonths($profitShare->end_date);
                $eligibleCount++;
            }
        });

        if ($eligibleCount === 0) {
            throw new \Exception('No eligible investments found. Division by zero prevented.');
        }

        // Calculate metadata for tracking
        $metadata = [
            'formula_type' => $formulaType,
            'eligibility_criteria' => [
                'min_months' => $minMonths,
                'min_investment' => $minInvestment,
                'require_active' => $requireActive,
            ],
            'eligible_users' => $eligibleCount,
            'total_eligible_investment' => $totalWeightedInvestment,
            'total_tenure_months' => $totalTenure, // Added for tenure-based auditing
            'calculated_at' => now()->toISOString(),
        ];

        // --- PASS 2: CALCULATION & STORAGE (Using Cursor) ---
        
        $previewData = []; // Store limited rows for preview
        $totalDistributed = 0;

        // If not previewing, start transaction to save results
        if (!$preview) {
            DB::beginTransaction();
            try {
                $profitShare->distributions()->delete(); // Clear old calculations
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        }

        try {
            // Use cursor() to stream results one by one (O(1) memory)
            foreach ($query->cursor() as $sub) {
                
                // Calculate individual metrics
                $investmentWeight = $sub->amount;
                $tenureMonths = $sub->start_date->diffInMonths($profitShare->end_date);
                
                // Get Plan Percentage
                // Note: accessing relations on cursor objects is efficient if eager loaded in query definition
                $config = $sub->plan->getConfig('profit_share', ['percentage' => 5]);
                $sharePercent = (float)($config['percentage'] ?? 5) / 100;

                // Calculate Share Amount based on Formula
                $amount = 0;
                
                if ($formulaType === 'equal_split') {
                    $amount = ($profitShare->total_pool / $eligibleCount) * $sharePercent;
                } elseif ($formulaType === 'tenure_based') {
                    // Tenure-Based Logic (Inlined for cursor efficiency)
                    $investmentWeightSetting = (float) setting('profit_share_investment_weight', 0.7);
                    $tenureWeightSetting = (float) setting('profit_share_tenure_weight', 0.3);

                    $investmentRatio = $totalWeightedInvestment > 0 ? $investmentWeight / $totalWeightedInvestment : 0;
                    $tenureRatio = $totalTenure > 0 ? $tenureMonths / $totalTenure : 0;

                    $combinedRatio = ($investmentRatio * $investmentWeightSetting) + ($tenureRatio * $tenureWeightSetting);
                    $amount = $profitShare->total_pool * $combinedRatio * $sharePercent;
                } else {
                    // Default: Weighted Investment
                    $ratio = $totalWeightedInvestment > 0 ? $investmentWeight / $totalWeightedInvestment : 0;
                    $amount = $profitShare->total_pool * $ratio * $sharePercent;
                }

                $totalDistributed += $amount;

                // Handle Saving or Previewing
                if ($amount > 0.01) {
                    if ($preview) {
                        // Limit preview to 100 records to prevent JSON overflow
                        if (count($previewData) < 100) {
                            $previewData[] = [
                                'user_id' => $sub->user_id,
                                'username' => $sub->user->username ?? 'Unknown',
                                'investment_weight' => $investmentWeight,
                                'tenure_months' => $tenureMonths,
                                'share_percent' => $sharePercent,
                                'amount' => $amount,
                            ];
                        }
                    } else {
                        // Insert directly into DB
                        UserProfitShare::create([
                            'profit_share_id' => $profitShare->id,
                            'user_id' => $sub->user_id,
                            'amount' => $amount,
                        ]);
                    }
                }
            }

            if (!$preview) {
                $profitShare->status = 'calculated';
                $profitShare->calculation_metadata = $metadata;
                $profitShare->save();
                DB::commit();
            }

        } catch (\Exception $e) {
            if (!$preview) DB::rollBack();
            throw $e;
        }

        if ($preview) {
            return [
                'distributions' => $previewData,
                'metadata' => $metadata,
                'total_distributed' => $totalDistributed,
                'note' => count($previewData) >= 100 ? 'Preview limited to first 100 records.' : ''
            ];
        }

        return [
            'total_distributed' => $totalDistributed,
            'eligible_users' => $eligibleCount,
            'metadata' => $metadata
        ];
    }

    /**
     * Calculate user shares based on formula type.
     * DEPRECATED: This method relied on array loading and is now replaced by the Cursor logic above.
     * Kept for backward compatibility if called by other legacy services, but internal logic now uses inline calculation.
     */
    private function calculateUserShares($subscriptions, $profitShare, $formulaType)
    {
        // Legacy support wrapper
        $userShares = [];
        $totalWeightedInvestment = 0;
        $totalTenure = 0;

        foreach ($subscriptions as $sub) {
            $totalWeightedInvestment += $sub->amount;
            $totalTenure += $sub->start_date->diffInMonths($profitShare->end_date);
        }

        foreach ($subscriptions as $sub) {
            $config = $sub->plan->getConfig('profit_share', ['percentage' => 5]);
            $sharePercent = (float)($config['percentage'] ?? 5) / 100;
            
            // Simplified recreation of logic for legacy calls
            $ratio = $totalWeightedInvestment > 0 ? $sub->amount / $totalWeightedInvestment : 0;
            $amount = $profitShare->total_pool * $ratio * $sharePercent;
            
            $userShares[] = [
                'user_id' => $sub->user_id,
                'amount' => $amount,
                'investment_weight' => $sub->amount,
                'share_percent' => $sharePercent
            ];
        }
        return $userShares;
    }

    /**
     * Formula 1: Weighted Investment (default)
     * Helper kept for reference
     */
    private function calculateWeightedInvestment($profitShare, $share, $totalWeightedInvestment)
    {
        $ratio = $share['investment_weight'] / $totalWeightedInvestment;
        return $profitShare->total_pool * $ratio * $share['share_percent'];
    }

    /**
     * Formula 2: Equal Split
     * Helper kept for reference
     */
    private function calculateEqualSplit($profitShare, $share, $totalUsers)
    {
        return ($profitShare->total_pool / $totalUsers) * $share['share_percent'];
    }

    /**
     * Formula 3: Tenure-Based (combines investment and tenure)
     * Helper kept for reference
     */
    private function calculateTenureBased($profitShare, $share, $totalWeightedInvestment, $totalTenure)
    {
        $investmentWeight = (float) setting('profit_share_investment_weight', 0.7);
        $tenureWeight = (float) setting('profit_share_tenure_weight', 0.3);

        $investmentRatio = $share['investment_weight'] / $totalWeightedInvestment;
        $tenureRatio = $totalTenure > 0 ? $share['tenure_months'] / $totalTenure : 0;

        $combinedRatio = ($investmentRatio * $investmentWeight) + ($tenureRatio * $tenureWeight);

        return $profitShare->total_pool * $combinedRatio * $share['share_percent'];
    }

    /**
     * FSD-REPORT-021: Distribute the funds.
     * * --- MODULE 11 AUDIT FIX: RACE CONDITION PREVENTION ---
     * Problem: If two admins clicked "Distribute" at the same time, or if the request timed out
     * and was retried, users could receive double payouts.
     * * The Fix:
     * 1. Use `lockForUpdate()` on the ProfitShare model row. This forces any other process
     * trying to distribute this specific ID to wait until the first one commits.
     * 2. Re-check the status INSIDE the lock to ensure it hasn't already been marked distributed.
     * -----------------------------------------------------
     */
    public function distributeToWallets(ProfitShare $profitShare, User $admin)
    {
        // Wrap entire distribution in a transaction with pessimistic locking
        return DB::transaction(function () use ($profitShare, $admin) {
            
            // 1. ACQUIRE LOCK (Pessimistic Lock)
            // This waits for any other transaction on this row to finish.
            $lockedPeriod = ProfitShare::lockForUpdate()->find($profitShare->id);
            
            // 2. STATUS CHECK (Inside Lock)
            if ($lockedPeriod->status !== 'calculated') {
                throw new \Exception("Distribution aborted: Period status is '{$lockedPeriod->status}' (Expected: calculated). It may have already been distributed.");
            }

            $distributions = $lockedPeriod->distributions()->with('user.wallet', 'user.kyc', 'user.subscription')->get();
            if ($distributions->isEmpty()) throw new \Exception('No distributions to process.');
            
            $tdsRate = (float) setting('tds_rate', 0.10);
            $tdsThreshold = (float) setting('tds_threshold', 5000);

            foreach ($distributions as $dist) {
                $user = $dist->user;
                if (!$user) continue;

                $wallet = $user->wallet;
                $grossAmount = $dist->amount;
                
                $tdsDeducted = 0;
                if ($user->kyc?->pan_number && $grossAmount > $tdsThreshold) {
                    $tdsDeducted = $grossAmount * $tdsRate;
                }
                $netAmount = $grossAmount - $tdsDeducted;

                // 1. Create Bonus Record
                $bonus = BonusTransaction::create([
                    'user_id' => $user->id,
                    'subscription_id' => $user->subscription?->id,
                    'type' => 'profit_share',
                    'amount' => $grossAmount,
                    'tds_deducted' => $tdsDeducted,
                    'description' => "Profit Share: {$lockedPeriod->period_name}",
                ]);

                // 2. Credit Wallet
                if ($netAmount > 0) {
                    $this->walletService->deposit(
                        $user,
                        $netAmount, 
                        'bonus_credit', 
                        "Profit Share: {$lockedPeriod->period_name}", 
                        $bonus
                    );
                }
                $dist->update(['bonus_transaction_id' => $bonus->id]);
            }
            
            // 3. UPDATE STATUS (Mark as complete)
            $lockedPeriod->update(['status' => 'distributed', 'admin_id' => $admin->id]);
            
            Log::info("Profit Share Period #{$profitShare->id} distributed successfully by Admin #{$admin->id}");
        });
    }

    /**
     * Manual Adjustment
     */
    public function manualAdjustment(ProfitShare $profitShare, int $userId, float $amount, string $reason)
    {
        if ($profitShare->status !== 'calculated') {
            throw new \Exception("Can only adjust a 'calculated' period.");
        }
        
        $user = User::findOrFail($userId);
        
        return $profitShare->distributions()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'amount' => $amount,
                'notes' => $reason,
            ]
        );
    }
    
    /**
     * Reverse Distribution
     */
    public function reverseDistribution(ProfitShare $profitShare, string $reason)
    {
        if ($profitShare->status !== 'distributed') {
            throw new \Exception("Only distributed periods can be reversed.");
        }

        $distributions = $profitShare->distributions()->with('user.wallet', 'bonusTransaction')->get();

        return DB::transaction(function () use ($profitShare, $distributions, $reason) {
            foreach ($distributions as $dist) {
                if (!$dist->bonusTransaction) continue;

                $user = $dist->user;
                $wallet = $user->wallet;
                $bonus = $dist->bonusTransaction;
                $netAmount = $bonus->amount - $bonus->tds_deducted;
                
                if ($wallet->balance < $netAmount) {
                    Log::error("Reversal Failed: User {$user->id} has insufficient balance (₹{$wallet->balance}) to reverse ₹{$netAmount}");
                    throw new \Exception("Reversal failed: User {$user->id} has insufficient funds.");
                }

                // 1. Debit from wallet
                $this->walletService->withdraw($user, $netAmount, 'reversal', "Reversal: {$reason}", $dist);
                
                // 2. Create reversal bonus transaction
                $bonus->update(['description' => $bonus->description . " [REVERSED]"]);
            }
            
            $profitShare->update(['status' => 'reversed']);
            Log::info("Profit Share {$profitShare->id} has been reversed.");
        });
    }

    /**
     * Publish Financial Report with visibility controls
     */
    public function publishReport(ProfitShare $profitShare, string $visibility, User $admin)
    {
        if ($profitShare->status !== 'distributed') {
            throw new \Exception("Can only publish reports for distributed periods.");
        }

        $distributions = $profitShare->distributions()->with('user')->get();
        $showDetails = setting('profit_share_show_beneficiary_details', false);

        $reportData = [
            'period_name' => $profitShare->period_name,
            'period' => [
                'start_date' => $profitShare->start_date->format('Y-m-d'),
                'end_date' => $profitShare->end_date->format('Y-m-d'),
            ],
            'financials' => [
                'net_profit' => $profitShare->net_profit,
                'total_pool' => $profitShare->total_pool,
                'total_distributed' => $profitShare->total_distributed,
                'percentage_distributed' => $profitShare->net_profit > 0
                    ? round(($profitShare->total_pool / $profitShare->net_profit) * 100, 2)
                    : 0,
            ],
            'statistics' => [
                'total_beneficiaries' => $distributions->count(),
                'average_per_user' => $distributions->count() > 0
                    ? round($distributions->sum('amount') / $distributions->count(), 2)
                    : 0,
                'highest_share' => $distributions->max('amount'),
                'lowest_share' => $distributions->min('amount'),
            ],
            'metadata' => $profitShare->calculation_metadata,
            'published_at' => now()->toISOString(),
            'published_by' => $admin->username,
        ];

        if ($showDetails || $visibility === 'public') {
            $reportData['beneficiaries'] = $distributions->map(function ($dist) use ($visibility) {
                $data = [
                    'user_id' => $dist->user_id,
                    'amount' => $dist->amount,
                ];
                if ($visibility === 'public') {
                    $data['username'] = $dist->user->username;
                }
                return $data;
            })->toArray();
        } else {
            $reportData['distribution_ranges'] = [
                'below_1000' => $distributions->where('amount', '<', 1000)->count(),
                '1000_5000' => $distributions->whereBetween('amount', [1000, 5000])->count(),
                '5000_10000' => $distributions->whereBetween('amount', [5000, 10000])->count(),
                'above_10000' => $distributions->where('amount', '>', 10000)->count(),
            ];
        }

        $profitShare->update([
            'report_visibility' => $visibility,
            'report_url' => null,
            'published_by' => $admin->id,
            'published_at' => now(),
        ]);

        Log::info("Profit Share Report published for period {$profitShare->period_name} by Admin {$admin->id}");

        return $reportData;
    }

    /**
     * Calculate potential share for a specific subscription (Preview/Dry-Run).
     * (Ported from Legacy ProfitSharingService)
     */
    public function calculatePotentialShare(Subscription $subscription, float $profitPool): array
    {
        // 1. Check Global Settings Eligibility
        $minMonths = (int) setting('profit_share_min_months', 3);
        $minInvestment = (float) setting('profit_share_min_investment', 10000);
        $requireActive = setting('profit_share_require_active_subscription', true);

        $subscription->load('plan'); // Ensure plan is loaded

        if ($requireActive && $subscription->status !== 'active') {
            return ['eligible' => false, 'reason' => 'Subscription not active'];
        }

        if ($subscription->amount < $minInvestment) {
            return ['eligible' => false, 'reason' => "Investment amount below minimum requirement of {$minInvestment}."];
        }

        // 2. Check User Tenure
        $userJoinDate = $subscription->user->created_at;
        $monthsSinceJoin = $userJoinDate->diffInMonths(now());

        if ($monthsSinceJoin < $minMonths) {
            return [
                'eligible' => false, 
                'reason' => "User tenure less than {$minMonths} months. Current: {$monthsSinceJoin} months."
            ];
        }

        // 3. Calculation Preview
        // We use the 'Weighted Investment' formula logic by default for individual estimation
        $config = $subscription->plan->getConfig('profit_share', ['percentage' => 5]);
        $sharePercent = (float)($config['percentage'] ?? 5);
        
        // Estimation: User Investment * Share % * (Pool Ratio estimate)
        // Note: Exact share requires total pool weight which changes dynamically.
        // This returns a raw "Maximum Potential" if they were the only investor (simplified).
        // A better estimate would be: Pool * Plan %
        $estimatedMaxShare = $profitPool * ($sharePercent / 100);

        return [
            'eligible' => true,
            'plan_percentage' => $sharePercent,
            'user_tenure_months' => $monthsSinceJoin,
            'estimated_pool_share_potential' => $estimatedMaxShare,
            'note' => 'Actual amount depends on total weighted investments of all eligible users.'
        ];
    }

    /**
     * Get user's profit sharing summary.
     * (Ported from Legacy ProfitSharingService, adapted for BonusTransaction)
     */
    public function getUserProfitSummary(User $user): array
    {
        // Use BonusTransaction as the source of truth for payouts
        $transactions = BonusTransaction::where('user_id', $user->id)
            ->where('type', 'profit_share')
            ->latest()
            ->get();

        return [
            'total_earned' => $transactions->sum('amount'), // Gross
            'total_tds' => $transactions->sum('tds_deducted'),
            'net_earned' => $transactions->sum(fn($t) => $t->amount - $t->tds_deducted),
            'distribution_count' => $transactions->count(),
            'last_distribution' => $transactions->first()?->created_at,
            'history' => $transactions->map(function ($txn) {
                return [
                    'id' => $txn->id,
                    'amount' => $txn->amount,
                    'tds' => $txn->tds_deducted,
                    'net_amount' => $txn->amount - $txn->tds_deducted,
                    'description' => $txn->description,
                    'date' => $txn->created_at
                ];
            })
        ];
    }
}