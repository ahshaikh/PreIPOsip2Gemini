<?php
// V-PHASE5-CELEBRATION-1208 (Created) | V-FIX-1730-603 (WalletService Integration)

namespace App\Services;

use App\Models\Subscription;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CelebrationBonusService
{
    protected $walletService;

    public function __construct(WalletService $walletService)
    {
        $this->walletService = $walletService;
    }

    /**
     * Check and award celebration bonuses for a subscription based on an event.
     *
     * @param Subscription $subscription
     * @param string $eventType ('payment', 'tenure', 'investment', 'referral')
     * @param array $eventData Additional event data
     * @return array Bonuses awarded
     */
    public function checkAndAwardBonuses(Subscription $subscription, string $eventType, array $eventData = []): array
    {
        $config = $subscription->plan->getConfig('celebration_bonus_config', []);

        if (empty($config) || !($config['enabled'] ?? false)) {
            return ['awarded' => false, 'bonuses' => []];
        }

        $milestones = $config['milestones'] ?? [];
        if (empty($milestones)) {
            return ['awarded' => false, 'bonuses' => []];
        }

        $awardedBonuses = [];

        foreach ($milestones as $milestone) {
            // Check if milestone matches event type
            if (($milestone['type'] ?? '') !== $eventType) {
                continue;
            }

            // Check if milestone is reached
            $reached = $this->checkMilestoneReached($subscription, $milestone, $eventData);

            if ($reached) {
                // Check if already awarded (for one-time bonuses)
                if (($milestone['one_time'] ?? true) && $this->hasReceivedMilestoneBonus($subscription, $milestone)) {
                    continue;
                }

                // Award the bonus
                $bonus = $this->awardMilestoneBonus($subscription, $milestone);
                $awardedBonuses[] = $bonus;
            }
        }

        return [
            'awarded' => !empty($awardedBonuses),
            'bonuses' => $awardedBonuses
        ];
    }

    /**
     * Check if a specific milestone has been reached.
     */
    protected function checkMilestoneReached(Subscription $subscription, array $milestone, array $eventData): bool
    {
        $type = $milestone['type'] ?? '';
        $threshold = (float) ($milestone['threshold'] ?? 0);

        switch ($type) {
            case 'payment_count':
                $paymentCount = $subscription->payments()
                    ->where('status', 'completed')
                    ->count();
                return $paymentCount >= $threshold;

            case 'tenure_months':
                $monthsActive = $subscription->created_at->diffInMonths(now());
                return $monthsActive >= $threshold;

            case 'total_invested':
                $totalInvested = $subscription->payments()
                    ->where('status', 'completed')
                    ->sum('amount');
                return $totalInvested >= $threshold;

            case 'referral_count':
                $referralCount = \App\Models\Referral::where('referrer_id', $subscription->user_id)
                    ->where('status', 'completed')
                    ->count();
                return $referralCount >= $threshold;

            case 'streak_months':
                // Check for consecutive on-time payments
                $streak = $this->calculatePaymentStreak($subscription);
                return $streak >= $threshold;

            case 'zero_missed_payments':
                // Award if user has made X payments with zero late/missed
                $totalPayments = $subscription->payments()
                    ->where('status', 'completed')
                    ->count();
                $latePayments = $subscription->payments()
                    ->where('status', 'completed')
                    ->where('is_late', true)
                    ->count();
                return $totalPayments >= $threshold && $latePayments === 0;

            default:
                return false;
        }
    }

    /**
     * Award a milestone bonus to a subscriber.
     * Uses WalletService to ensure transactional integrity and lock balances.
     */
    protected function awardMilestoneBonus(Subscription $subscription, array $milestone): array
    {
        $user = $subscription->user;
        $bonusType = $milestone['bonus_type'] ?? 'fixed';
        $bonusValue = (float) ($milestone['bonus_amount'] ?? 0);
        $milestoneName = $milestone['name'] ?? 'Milestone Bonus';

        // Calculate actual bonus amount
        if ($bonusType === 'percentage') {
            // Percentage of investment amount
            $bonusAmount = $subscription->investment_amount * ($bonusValue / 100);
        } else {
            // Fixed amount
            $bonusAmount = $bonusValue;
        }

        try {
            // 1. Delegate financial operation to WalletService
            // This handles DB transactions, row locking, and ledger creation
            $transaction = $this->walletService->deposit(
                $user,
                $bonusAmount,
                'celebration_bonus',
                $milestoneName,
                $subscription // Reference model
            );

            // 2. Attach specific metadata to the transaction created by WalletService
            $transaction->update([
                'subscription_id' => $subscription->id, // Ensure direct link if column exists
                'metadata' => array_merge($transaction->metadata ?? [], [
                    'milestone_type' => $milestone['type'],
                    'milestone_threshold' => $milestone['threshold'],
                    'milestone_name' => $milestoneName,
                    'bonus_type' => $bonusType,
                    'plan_id' => $subscription->plan_id,
                    'plan_name' => $subscription->plan->name
                ])
            ]);

            Log::info("Celebration Bonus Awarded: User {$user->id}, Amount {$bonusAmount}, Milestone {$milestoneName}");

            return [
                'milestone_name' => $milestoneName,
                'milestone_type' => $milestone['type'],
                'amount' => $bonusAmount,
                'transaction_id' => $transaction->id
            ];

        } catch (\Exception $e) {
            Log::error("Failed to award celebration bonus: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Check if user has already received a specific milestone bonus.
     */
    protected function hasReceivedMilestoneBonus(Subscription $subscription, array $milestone): bool
    {
        $milestoneName = $milestone['name'] ?? '';
        $milestoneType = $milestone['type'] ?? '';

        // Check using JSON queries on metadata or direct transaction properties
        return Transaction::where('user_id', $subscription->user_id)
            ->where('type', 'celebration_bonus')
            ->where('status', 'completed')
            ->where(function ($query) use ($milestoneName, $milestoneType, $subscription) {
                // Check subscription link
                $query->where('subscription_id', $subscription->id)
                      ->orWhere(function($q) use ($subscription) {
                          $q->where('reference_type', get_class($subscription))
                            ->where('reference_id', $subscription->id);
                      });
            })
            ->where(function ($query) use ($milestoneName, $milestoneType) {
                $query->whereRaw("JSON_EXTRACT(metadata, '$.milestone_name') = ?", [$milestoneName])
                    ->orWhereRaw("JSON_EXTRACT(metadata, '$.milestone_type') = ?", [$milestoneType]);
            })
            ->exists();
    }

    /**
     * Calculate consecutive on-time payment streak.
     */
    protected function calculatePaymentStreak(Subscription $subscription): int
    {
        $payments = $subscription->payments()
            ->where('status', 'completed')
            ->orderBy('due_date', 'desc')
            ->get();

        $streak = 0;
        foreach ($payments as $payment) {
            if ($payment->is_late ?? false) {
                break;
            }
            $streak++;
        }

        return $streak;
    }

    /**
     * Get milestone progress for a subscription.
     */
    public function getMilestoneProgress(Subscription $subscription): array
    {
        $config = $subscription->plan->getConfig('celebration_bonus_config', []);

        if (empty($config) || !($config['enabled'] ?? false)) {
            return ['enabled' => false, 'milestones' => []];
        }

        $milestones = $config['milestones'] ?? [];
        $progress = [];

        foreach ($milestones as $milestone) {
            $type = $milestone['type'] ?? '';
            $threshold = (float) ($milestone['threshold'] ?? 0);
            $current = $this->getCurrentValue($subscription, $type);
            $achieved = $this->hasReceivedMilestoneBonus($subscription, $milestone);

            $progress[] = [
                'name' => $milestone['name'] ?? 'Unnamed Milestone',
                'type' => $type,
                'threshold' => $threshold,
                'current' => $current,
                'progress_percentage' => min(100, ($current / max(1, $threshold)) * 100),
                'achieved' => $achieved,
                'bonus_amount' => $milestone['bonus_amount'] ?? 0,
                'bonus_type' => $milestone['bonus_type'] ?? 'fixed',
                'one_time' => $milestone['one_time'] ?? true
            ];
        }

        return [
            'enabled' => true,
            'milestones' => $progress
        ];
    }

    /**
     * Get current value for a milestone type.
     */
    protected function getCurrentValue(Subscription $subscription, string $type): float
    {
        switch ($type) {
            case 'payment_count':
                return $subscription->payments()
                    ->where('status', 'completed')
                    ->count();

            case 'tenure_months':
                return $subscription->created_at->diffInMonths(now());

            case 'total_invested':
                return $subscription->payments()
                    ->where('status', 'completed')
                    ->sum('amount');

            case 'referral_count':
                return \App\Models\Referral::where('referrer_id', $subscription->user_id)
                    ->where('status', 'completed')
                    ->count();

            case 'streak_months':
                return $this->calculatePaymentStreak($subscription);

            case 'zero_missed_payments':
                return $subscription->payments()
                    ->where('status', 'completed')
                    ->count();

            default:
                return 0;
        }
    }

    /**
     * Get user's celebration bonus summary.
     */
    public function getUserCelebrationSummary(User $user, ?Subscription $subscription = null): array
    {
        $query = Transaction::where('user_id', $user->id)
            ->where('type', 'celebration_bonus')
            ->where('status', 'completed');

        if ($subscription) {
            $query->where(function($q) use ($subscription) {
                $q->where('subscription_id', $subscription->id)
                  ->orWhere(function($subQ) use ($subscription) {
                      $subQ->where('reference_type', get_class($subscription))
                           ->where('reference_id', $subscription->id);
                  });
            });
        }

        $transactions = $query->get();

        return [
            'total_earned' => $transactions->sum('amount'),
            'milestone_count' => $transactions->count(),
            'last_milestone' => $transactions->first()?->created_at,
            'milestones' => $transactions->map(function ($txn) {
                $metadata = $txn->metadata;
                return [
                    'name' => $metadata['milestone_name'] ?? 'Unknown',
                    'type' => $metadata['milestone_type'] ?? 'unknown',
                    'amount' => $txn->amount,
                    'date' => $txn->created_at
                ];
            })
        ];
    }

    /**
     * Trigger milestone checks after a payment (to be called from PaymentService).
     */
    public function checkPaymentMilestones(Subscription $subscription): array
    {
        return $this->checkAndAwardBonuses($subscription, 'payment_count', []);
    }

    /**
     * Trigger milestone checks for tenure (to be called from a scheduler/cron).
     */
    public function checkTenuremilestones(Subscription $subscription): array
    {
        return $this->checkAndAwardBonuses($subscription, 'tenure_months', []);
    }

    /**
     * Trigger milestone checks for total investment.
     */
    public function checkInvestmentMilestones(Subscription $subscription): array
    {
        return $this->checkAndAwardBonuses($subscription, 'total_invested', []);
    }
}