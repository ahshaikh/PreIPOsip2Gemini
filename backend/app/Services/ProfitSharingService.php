<?php
// V-PHASE5-PROFIT-1208 (Created)

namespace App\Services;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ProfitSharingService
{
    /**
     * Distribute profits to eligible subscribers of a plan.
     *
     * @param Plan $plan
     * @param float $profitPool Total profit amount to distribute
     * @param string $period Period identifier (e.g., '2024-12', 'Q4-2024')
     * @return array Distribution summary
     */
    public function distributeProfits(Plan $plan, float $profitPool, string $period): array
    {
        $config = $plan->getConfig('profit_sharing_config', []);

        if (empty($config) || !($config['enabled'] ?? false)) {
            throw new \Exception('Profit sharing is not enabled for this plan.');
        }

        // Calculate distributable amount
        $sharingPercentage = (float) ($config['percentage'] ?? 0);
        if ($sharingPercentage <= 0 || $sharingPercentage > 100) {
            throw new \Exception('Invalid profit sharing percentage.');
        }

        $distributableAmount = $profitPool * ($sharingPercentage / 100);

        // Get eligible subscriptions
        $eligibleSubscriptions = $this->getEligibleSubscriptions($plan, $config);

        if ($eligibleSubscriptions->isEmpty()) {
            return [
                'distributed' => false,
                'reason' => 'No eligible subscriptions found',
                'eligible_count' => 0,
                'total_distributed' => 0
            ];
        }

        // Calculate individual shares based on allocation method
        $shares = $this->calculateShares($eligibleSubscriptions, $distributableAmount, $config);

        // Distribute to each subscriber
        $distributions = [];
        DB::beginTransaction();
        try {
            foreach ($shares as $subscriptionId => $amount) {
                $subscription = $eligibleSubscriptions->firstWhere('id', $subscriptionId);
                $distribution = $this->distributeProfitToSubscriber($subscription, $amount, $period);
                $distributions[] = $distribution;
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        return [
            'distributed' => true,
            'eligible_count' => $eligibleSubscriptions->count(),
            'total_distributed' => array_sum($shares),
            'distributions' => $distributions,
            'period' => $period,
            'allocation_method' => $config['allocation_method'] ?? 'equal'
        ];
    }

    /**
     * Get subscriptions eligible for profit sharing.
     */
    protected function getEligibleSubscriptions(Plan $plan, array $config): Collection
    {
        $minMonths = (int) ($config['min_subscription_months'] ?? 0);

        return Subscription::where('plan_id', $plan->id)
            ->where('status', 'active')
            ->whereRaw('TIMESTAMPDIFF(MONTH, created_at, NOW()) >= ?', [$minMonths])
            ->with('user')
            ->get();
    }

    /**
     * Calculate individual shares for each subscription.
     */
    protected function calculateShares(Collection $subscriptions, float $totalAmount, array $config): array
    {
        $method = $config['allocation_method'] ?? 'equal';
        $shares = [];

        switch ($method) {
            case 'equal':
                $sharePerSubscription = $totalAmount / $subscriptions->count();
                foreach ($subscriptions as $subscription) {
                    $shares[$subscription->id] = $sharePerSubscription;
                }
                break;

            case 'proportional_to_investment':
                $totalInvestment = $subscriptions->sum('investment_amount');
                if ($totalInvestment > 0) {
                    foreach ($subscriptions as $subscription) {
                        $proportion = $subscription->investment_amount / $totalInvestment;
                        $shares[$subscription->id] = $totalAmount * $proportion;
                    }
                } else {
                    // Fallback to equal distribution
                    $sharePerSubscription = $totalAmount / $subscriptions->count();
                    foreach ($subscriptions as $subscription) {
                        $shares[$subscription->id] = $sharePerSubscription;
                    }
                }
                break;

            case 'proportional_to_tenure':
                // Weight by months active
                $totalMonths = 0;
                $tenureMap = [];

                foreach ($subscriptions as $subscription) {
                    $months = max(1, $subscription->created_at->diffInMonths(now()));
                    $tenureMap[$subscription->id] = $months;
                    $totalMonths += $months;
                }

                if ($totalMonths > 0) {
                    foreach ($subscriptions as $subscription) {
                        $proportion = $tenureMap[$subscription->id] / $totalMonths;
                        $shares[$subscription->id] = $totalAmount * $proportion;
                    }
                }
                break;

            case 'weighted':
                // Combine investment (70%) and tenure (30%)
                $totalInvestment = $subscriptions->sum('investment_amount');
                $totalMonths = 0;
                $tenureMap = [];

                foreach ($subscriptions as $subscription) {
                    $months = max(1, $subscription->created_at->diffInMonths(now()));
                    $tenureMap[$subscription->id] = $months;
                    $totalMonths += $months;
                }

                foreach ($subscriptions as $subscription) {
                    $investmentWeight = $totalInvestment > 0
                        ? ($subscription->investment_amount / $totalInvestment) * 0.7
                        : 0;

                    $tenureWeight = $totalMonths > 0
                        ? ($tenureMap[$subscription->id] / $totalMonths) * 0.3
                        : 0;

                    $combinedWeight = $investmentWeight + $tenureWeight;
                    $shares[$subscription->id] = $totalAmount * $combinedWeight;
                }
                break;

            default:
                throw new \Exception("Invalid allocation method: {$method}");
        }

        return $shares;
    }

    /**
     * Distribute profit to a single subscriber.
     */
    protected function distributeProfitToSubscriber(Subscription $subscription, float $amount, string $period): array
    {
        $user = $subscription->user;

        // Credit to wallet
        $wallet = $user->wallet;
        $wallet->balance += $amount;
        $wallet->save();

        // Create transaction record
        Transaction::create([
            'user_id' => $user->id,
            'subscription_id' => $subscription->id,
            'type' => 'profit_sharing',
            'amount' => $amount,
            'status' => 'completed',
            'description' => "Profit sharing for {$period} - {$subscription->plan->name}",
            'metadata' => json_encode([
                'period' => $period,
                'plan_id' => $subscription->plan_id,
                'plan_name' => $subscription->plan->name,
                'allocation_method' => $subscription->plan->getConfig('profit_sharing_config.allocation_method', 'equal')
            ])
        ]);

        // Create wallet transaction (double-entry bookkeeping)
        DB::table('wallet_transactions')->insert([
            'wallet_id' => $wallet->id,
            'transaction_id' => DB::getPdo()->lastInsertId(),
            'type' => 'credit',
            'amount' => $amount,
            'balance_after' => $wallet->balance,
            'description' => "Profit sharing - {$period}",
            'created_at' => now(),
            'updated_at' => now()
        ]);

        return [
            'user_id' => $user->id,
            'subscription_id' => $subscription->id,
            'amount' => $amount,
            'user_email' => $user->email
        ];
    }

    /**
     * Calculate potential share for a specific subscription (preview).
     */
    public function calculatePotentialShare(Subscription $subscription, float $profitPool): array
    {
        $plan = $subscription->plan;
        $config = $plan->getConfig('profit_sharing_config', []);

        if (empty($config) || !($config['enabled'] ?? false)) {
            return [
                'eligible' => false,
                'reason' => 'Profit sharing not enabled'
            ];
        }

        // Check eligibility
        $minMonths = (int) ($config['min_subscription_months'] ?? 0);
        $actualMonths = $subscription->created_at->diffInMonths(now());

        if ($subscription->status !== 'active') {
            return [
                'eligible' => false,
                'reason' => 'Subscription not active'
            ];
        }

        if ($actualMonths < $minMonths) {
            return [
                'eligible' => false,
                'reason' => "Minimum {$minMonths} months required. Current: {$actualMonths} months."
            ];
        }

        // Calculate distributable amount
        $sharingPercentage = (float) ($config['percentage'] ?? 0);
        $distributableAmount = $profitPool * ($sharingPercentage / 100);

        // Get all eligible subscriptions for calculation
        $eligibleSubscriptions = $this->getEligibleSubscriptions($plan, $config);

        if ($eligibleSubscriptions->isEmpty() || !$eligibleSubscriptions->contains('id', $subscription->id)) {
            return [
                'eligible' => false,
                'reason' => 'Not eligible'
            ];
        }

        $shares = $this->calculateShares($eligibleSubscriptions, $distributableAmount, $config);

        return [
            'eligible' => true,
            'estimated_share' => $shares[$subscription->id] ?? 0,
            'total_eligible_subscriptions' => $eligibleSubscriptions->count(),
            'allocation_method' => $config['allocation_method'] ?? 'equal',
            'months_active' => $actualMonths
        ];
    }

    /**
     * Get profit distribution history for a plan.
     */
    public function getDistributionHistory(Plan $plan, int $limit = 10): Collection
    {
        return Transaction::where('type', 'profit_sharing')
            ->whereHas('subscription', function ($query) use ($plan) {
                $query->where('plan_id', $plan->id);
            })
            ->with('user', 'subscription')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get user's profit sharing summary.
     */
    public function getUserProfitSummary(User $user, ?Plan $plan = null): array
    {
        $query = Transaction::where('user_id', $user->id)
            ->where('type', 'profit_sharing')
            ->where('status', 'completed');

        if ($plan) {
            $query->whereHas('subscription', function ($q) use ($plan) {
                $q->where('plan_id', $plan->id);
            });
        }

        $transactions = $query->get();

        return [
            'total_earned' => $transactions->sum('amount'),
            'distribution_count' => $transactions->count(),
            'last_distribution' => $transactions->first()?->created_at,
            'distributions' => $transactions->map(function ($txn) {
                return [
                    'amount' => $txn->amount,
                    'period' => $txn->metadata['period'] ?? 'N/A',
                    'plan_name' => $txn->metadata['plan_name'] ?? 'Unknown',
                    'date' => $txn->created_at
                ];
            })
        ];
    }
}
