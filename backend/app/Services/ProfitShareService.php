<?php
// V-FINAL-1730-372 (Created) | V-FINAL-1730-451 (Hardened) | V-FINAL-1730-567 (Hardened) | V-FINAL-1730-573 (Reversals Added)

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

        // Build query for eligible subscriptions
        $query = Subscription::where('start_date', '<=', $profitShare->end_date)
            ->where('amount', '>=', $minInvestment)
            ->whereHas('user', fn($q) => $q->whereDate('created_at', '<=', now()->subMonths($minMonths)))
            ->with('plan.configs', 'user');

        if ($requireActive) {
            $query->where('status', 'active');
        }

        $subscriptions = $query->get();

        // Get formula configuration
        $formulaType = setting('profit_share_formula_type', 'weighted_investment');

        // Calculate shares based on formula
        $userShares = $this->calculateUserShares($subscriptions, $profitShare, $formulaType);

        if (empty($userShares)) {
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
            'eligible_users' => count($userShares),
            'total_eligible_investment' => array_sum(array_column($userShares, 'investment_weight')),
            'calculated_at' => now()->toISOString(),
        ];

        // Preview mode: return data without saving
        if ($preview) {
            return [
                'distributions' => $userShares,
                'metadata' => $metadata,
                'total_distributed' => array_sum(array_column($userShares, 'amount')),
            ];
        }

        // Save to database
        return DB::transaction(function () use ($profitShare, $userShares, $metadata) {
            $profitShare->distributions()->delete(); // Clear old calculations
            $totalDistributed = 0;

            foreach ($userShares as $share) {
                if ($share['amount'] > 0.01) {
                    $profitShare->distributions()->create([
                        'user_id' => $share['user_id'],
                        'amount' => $share['amount'],
                    ]);
                    $totalDistributed += $share['amount'];
                }
            }

            $profitShare->status = 'calculated';
            $profitShare->calculation_metadata = $metadata;
            $profitShare->save();

            return [
                'total_distributed' => $totalDistributed,
                'eligible_users' => count($userShares),
                'metadata' => $metadata
            ];
        });
    }

    /**
     * Calculate user shares based on formula type.
     */
    private function calculateUserShares($subscriptions, $profitShare, $formulaType)
    {
        $userShares = [];
        $totalWeightedInvestment = 0;
        $totalTenure = 0;

        // First pass: Calculate totals
        foreach ($subscriptions as $sub) {
            $investmentWeight = $sub->amount;
            $tenureMonths = $sub->start_date->diffInMonths($profitShare->end_date);

            $totalWeightedInvestment += $investmentWeight;
            $totalTenure += $tenureMonths;

            $config = $sub->plan->getConfig('profit_share', ['percentage' => 5]);
            $sharePercent = (float)($config['percentage'] ?? 5) / 100;

            $userShares[] = [
                'user_id' => $sub->user_id,
                'investment_weight' => $investmentWeight,
                'tenure_months' => $tenureMonths,
                'share_percent' => $sharePercent,
                'subscription' => $sub,
            ];
        }

        if ($totalWeightedInvestment == 0) {
            return [];
        }

        // Second pass: Calculate amounts based on formula
        foreach ($userShares as &$share) {
            $amount = match ($formulaType) {
                'equal_split' => $this->calculateEqualSplit($profitShare, $share, count($userShares)),
                'tenure_based' => $this->calculateTenureBased($profitShare, $share, $totalWeightedInvestment, $totalTenure),
                default => $this->calculateWeightedInvestment($profitShare, $share, $totalWeightedInvestment),
            };

            $share['amount'] = $amount;
        }

        return $userShares;
    }

    /**
     * Formula 1: Weighted Investment (default)
     */
    private function calculateWeightedInvestment($profitShare, $share, $totalWeightedInvestment)
    {
        $ratio = $share['investment_weight'] / $totalWeightedInvestment;
        return $profitShare->total_pool * $ratio * $share['share_percent'];
    }

    /**
     * Formula 2: Equal Split
     */
    private function calculateEqualSplit($profitShare, $share, $totalUsers)
    {
        return ($profitShare->total_pool / $totalUsers) * $share['share_percent'];
    }

    /**
     * Formula 3: Tenure-Based (combines investment and tenure)
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
     */
    public function distributeToWallets(ProfitShare $profitShare, User $admin)
    {
        if ($profitShare->status !== 'calculated') {
            throw new \Exception("This period is not ready for distribution.");
        }

        $distributions = $profitShare->distributions()->with('user.wallet', 'user.kyc', 'user.subscription')->get();
        if ($distributions->isEmpty()) throw new \Exception('No distributions to process.');
        
        $tdsRate = (float) setting('tds_rate', 0.10);
        $tdsThreshold = (float) setting('tds_threshold', 5000);

        return DB::transaction(function () use ($profitShare, $distributions, $admin, $tdsRate, $tdsThreshold) {
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
                    'description' => "Profit Share: {$profitShare->period_name}",
                ]);

                // 2. Credit Wallet
                if ($netAmount > 0) {
                    $this->walletService->deposit(
                        $user,
                        $netAmount, 
                        'bonus_credit', 
                        "Profit Share: {$profitShare->period_name}", 
                        $bonus
                    );
                }
                $dist->update(['bonus_transaction_id' => $bonus->id]);
            }
            $profitShare->update(['status' => 'distributed', 'admin_id' => $admin->id]);
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