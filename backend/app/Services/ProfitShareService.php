<?php
// V-FINAL-1730-372 (Created) | V-FINAL-1730-451 (Hardened) | V-FINAL-1730-567 (Hardened) | V-FINAL-1730-573 (Reversals Added)

namespace App\Services;

use App\Models\ProfitShare;
use App\Models\Subscription;
use App\Models\BonusTransaction;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserProfitShare; // <-- IMPORT
use App\Services\WalletService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProfitShareService
{
    protected $walletService;
    public function __construct(WalletService $walletService)
    {
        $this->walletService = $walletService;
    }

    /**
     * FSD-REPORT-021: Calculate the distribution.
     */
    public function calculateDistribution(ProfitShare $profitShare)
    {
        if ($profitShare->status !== 'pending') {
            throw new \Exception("This period is not pending calculation.");
        }
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
            $investmentWeight = $sub->amount;
            $totalWeightedInvestment += $investmentWeight;
            $config = $sub->plan->getConfig('profit_share', ['percentage' => 5]);
            $sharePercent = (float)$config['percentage'] / 100;
            $userShares[] = [
                'user_id' => $sub->user_id,
                'investment_weight' => $investmentWeight,
                'share_percent' => $sharePercent
            ];
        }

        if ($totalWeightedInvestment == 0) {
            throw new \Exception('No eligible investments found. Division by zero prevented.');
        }

        return DB::transaction(function () use ($profitShare, $userShares, $totalWeightedInvestment) {
            $profitShare->distributions()->delete(); // Clear old calculations
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
     * FSD-REPORT-021: Distribute the funds.
     */
    public function distributeToWallets(ProfitShare $profitShare, User $admin)
    {
        if ($profitShare->status !== 'calculated') {
            throw new \Exception("This period is not ready for distribution.");
        }

        $distributions = $profitShare->distributions()->with('user.wallet', 'user.kyc')->get();
        if ($distributions->isEmpty()) throw new \Exception('No distributions to process.');
        
        $tdsRate = (float) setting('tds_rate', 0.10);
        $tdsThreshold = (float) setting('tds_threshold', 5000);

        return DB::transaction(function () use ($profitShare, $distributions, $admin, $tdsRate, $tdsThreshold) {
            foreach ($distributions as $dist) {
                $user = $dist->user;
                $wallet = $user->wallet;
                $grossAmount = $dist->amount;
                
                $tdsDeducted = 0;
                if ($user->kyc?->pan_number && $grossAmount > $tdsThreshold) {
                    $tdsDeducted = $grossAmount * $tdsRate;
                }
                $netAmount = $grossAmount - $tdsDeducted;

                $bonus = BonusTransaction::create([
                    'user_id' => $user->id,
                    'subscription_id' => $user->subscription->id,
                    'type' => 'profit_share',
                    'amount' => $grossAmount,
                    'tds_deducted' => $tdsDeducted,
                    'description' => "Profit Share: {$profitShare->period_name}",
                ]);

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
     * NEW: Manual Adjustment
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
                'notes' => $reason, // Assumes 'notes' column exists
            ]
        );
    }
    
    /**
     * NEW: Reverse Distribution
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
                
                if ($wallet->balance < $netAmount) {
                    Log::error("Reversal Failed: User {$user->id} has insufficient balance (₹{$wallet->balance}) to reverse ₹{$netAmount}");
                    throw new \Exception("Reversal failed: User {$user->id} has insufficient funds.");
                }

                // 1. Debit from wallet
                $this->walletService->withdraw($user, $netAmount, 'reversal', "Reversal: {$reason}", $dist);
                
                // 2. Create reversal bonus transaction
                $bonus->reverse("Reversal: {$reason}");
            }
            
            $profitShare->update(['status' => 'reversed']);
            Log::info("Profit Share {$profitShare->id} has been reversed.");
        });
    }
}