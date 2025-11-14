<?php
// V-FINAL-1730-372 (Created) | V-FINAL-1730-451 (Hardened)

namespace App\Services;

use App\Models\ProfitShare;
use App\Models\Subscription;
use App\Models\BonusTransaction;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProfitShareService
{
    /**
     * Test: test_calculate_profit_uses_correct_formula
     */
    public function calculateDistribution(ProfitShare $profitShare)
    {
        if ($profitShare->status !== 'pending') {
            throw new \Exception("This period is not pending calculation.");
        }

        // --- V2.0 Safety Check ---
        // Test: test_profit_share_zero_profit_no_distribution
        if ($profitShare->total_pool <= 0) {
            $profitShare->update(['status' => 'cancelled']);
            throw new \Exception("Total pool is zero or negative. No distribution to calculate.");
        }

        $minMonths = (int) setting('profit_share_min_months', 3);

        $subscriptions = Subscription::where('status', 'active')
            ->where('start_date', '<=', $profitShare->end_date)
            ->whereHas('user', fn($q) => $q->whereDate('created_at', '<=', now()->subMonths($minMonths)))
            ->with('plan.configs', 'user')
            ->get();

        $totalWeightedInvestment = 0;
        $userShares = [];

        foreach ($subscriptions as $sub) {
            $investmentWeight = $sub->plan->monthly_amount;
            $totalWeightedInvestment += $investmentWeight;
            
            $config = $sub->plan->getConfig('profit_share', ['percentage' => 5]);
            $sharePercent = (float)$config['percentage'] / 100;
            
            $userShares[] = [
                'user_id' => $sub->user_id,
                'investment_weight' => $investmentWeight,
                'share_percent' => $sharePercent
            ];
        }

        // --- V2.0 Safety Check ---
        // Test: test_profit_share_calculation_formula_division_by_zero
        if ($totalWeightedInvestment == 0) {
            throw new \Exception('No eligible investments found. Division by zero prevented.');
        }

        return DB::transaction(function () use ($profitShare, $userShares, $totalWeightedInvestment) {
            $profitShare->distributions()->delete();
            $totalDistributed = 0;

            foreach ($userShares as $share) {
                $ratio = $share['investment_weight'] / $totalWeightedInvestment;
                $amount = $profitShare->total_pool * $ratio * $share['share_percent'];
                
                if ($amount > 0.01) {
                    $profitShare->distributions()->create([
                        'user_id' => $share['user_id'],
                        'amount' => $amount,
                    ]);
                    $totalDistributed += $amount;
                }
            }

            $profitShare->status = 'calculated';
            $profitShare->save();

            return ['total_distributed' => $totalDistributed, 'eligible_users' => count($userShares)];
        });
    }

    /**
     * Test: test_distribute_to_wallets_credits_correctly
     * Test: test_profit_share_tax_deduction_tds
     */
    public function distributeToWallets(ProfitShare $profitShare, User $admin)
    {
        if ($profitShare->status !== 'calculated') {
            throw new \Exception("This period is not ready for distribution.");
        }

        $distributions = $profitShare->distributions()->with('user.wallet', 'user.kyc')->get();
        if ($distributions->isEmpty()) throw new \Exception('No distributions to process.');
        
        $tdsRate = (float) setting('tds_rate', 0.10); // 10%

        return DB::transaction(function () use ($profitShare, $distributions, $admin, $tdsRate) {
            
            foreach ($distributions as $dist) {
                $user = $dist->user;
                $wallet = $user->wallet;
                $grossAmount = $dist->amount;
                
                // --- V2.0 Tax Logic ---
                // FSD-REPORT-017: TDS on bonuses
                // Apply TDS only if user is PAN verified (required for TDS)
                $tdsDeducted = 0;
                if ($user->kyc?->pan_number && $grossAmount > setting('tds_threshold', 5000)) {
                    $tdsDeducted = $grossAmount * $tdsRate;
                }
                $netAmount = $grossAmount - $tdsDeducted;
                // ---------------------

                // 1. Create Bonus Transaction (Ledger)
                $bonus = BonusTransaction::create([
                    'user_id' => $user->id,
                    'subscription_id' => $user->subscription->id,
                    'type' => 'profit_share',
                    'amount' => $grossAmount,
                    'tds_deducted' => $tdsDeducted,
                    'description' => "Profit Share: {$profitShare->period_name}",
                ]);

                // 2. Credit their wallet (Net Amount)
                if ($netAmount > 0) {
                    $wallet->deposit(
                        $netAmount, 
                        'bonus_credit', 
                        "Profit Share: {$profitShare->period_name}", 
                        $bonus
                    );
                }
                
                $dist->update(['bonus_transaction_id' => $bonus->id]);
            }

            $profitShare->update(['status' => 'distributed', 'admin_id' => $admin->id]);
            Log::info("Profit share {$profitShare->id} distributed by Admin {$admin->id}");
        });
    }

    /**
     * Test: test_profit_share_reversal_full
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
                $netAmount = $bonus->net_amount; // Get net amount (Amount - TDS)
                
                // --- V2.0 Reversal Logic ---
                if ($wallet->balance < $netAmount) {
                    Log::error("Reversal Failed: User {$user->id} has insufficient balance (₹{$wallet->balance}) to reverse ₹{$netAmount}");
                    throw new \Exception("Reversal failed: User {$user->id} has insufficient funds.");
                }

                // 1. Debit from wallet
                $wallet->withdraw($netAmount, 'reversal', "Reversal: {$reason}", $dist);
                
                // 2. Create reversal bonus transaction
                $bonus->reverse("Reversal: {$reason}");
            }
            
            $profitShare->update(['status' => 'reversed']);
            Log::info("Profit Share {$profitShare->id} has been reversed.");
        });
    }
}