<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\UserAggregateServiceInterface;
use App\Exceptions\Domain\IneligibleActionException;
use App\Exceptions\Domain\SubscriptionNotFoundException;
use App\Models\Plan;
use App\Models\User;
use App\Services\Compliance\UserComplianceResolver;
use App\ValueObjects\UserAggregate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * UserAggregateService Implementation
 *
 * This service provides a domain-level API for all user-related operations.
 * It enforces compliance rules and prevents illegal state transitions.
 *
 * KEY PRINCIPLES:
 * 1. Controllers MUST NOT access model relationships directly
 * 2. All eligibility checks go through assertCan()
 * 3. All state changes wrapped in DB transactions
 * 4. Business logic delegated to existing services (SubscriptionService, etc.)
 * 5. Domain exceptions thrown (not HTTP exceptions)
 *
 * @package App\Services
 */
final class UserAggregateServiceImpl implements UserAggregateServiceInterface
{
    /**
     * Create service with dependencies
     *
     * @param SubscriptionService $subscriptionService Existing subscription service
     */
    public function __construct(
        private readonly SubscriptionService $subscriptionService,
    ) {
    }

    /**
     * Load user aggregate with all related data
     *
     * @param int $userId User ID
     * @return UserAggregate Complete user aggregate
     */
    public function load(int $userId): UserAggregate
    {
        $user = User::with([
            'profile',
            'kyc',
            'wallet',
            'subscription', // Latest subscription (hasOne relationship)
        ])->findOrFail($userId);

        return $this->loadFromUser($user);
    }

    /**
     * Load user aggregate from User model instance
     *
     * This method ensures relationships are loaded and builds the aggregate.
     *
     * @param User $user User model
     * @return UserAggregate Complete user aggregate
     */
    public function loadFromUser(User $user): UserAggregate
    {
        // Ensure critical relationships are loaded
        $user->loadMissing([
            'profile',
            'kyc',
            'wallet',
            'subscription',
        ]);

        // Derive compliance snapshot
        $compliance = UserComplianceResolver::from($user);

        // Load additional collections (avoid N+1)
        $allSubscriptions = $user->subscriptions()
            ->latest()
            ->limit(10)
            ->get();

        $recentPayments = $user->payments()
            ->latest()
            ->limit(10)
            ->get();

        $recentTransactions = $user->wallet?->transactions()
            ->latest()
            ->limit(10)
            ->get() ?? collect();

        return new UserAggregate(
            user: $user,
            compliance: $compliance,
            profile: $user->profile,
            kyc: $user->kyc,
            wallet: $user->wallet,
            subscription: $user->subscription,
            allSubscriptions: $allSubscriptions,
            recentPayments: $recentPayments,
            recentTransactions: $recentTransactions,
        );
    }

    /**
     * Assert that user can perform a specific action
     *
     * This is the SINGLE place where eligibility is checked.
     * Controllers must call this before attempting operations.
     *
     * @param string $action Action name
     * @param int $userId User ID
     * @throws IneligibleActionException If user cannot perform action
     * @return void
     */
    public function assertCan(string $action, int $userId): void
    {
        $aggregate = $this->load($userId);
        $compliance = $aggregate->compliance;

        $canPerform = match ($action) {
            'create subscription' => $compliance->canCreateSubscription(),
            'change plan' => $compliance->canChangeSubscriptionPlan(),
            'pause subscription' => $compliance->canPauseSubscription(),
            'resume subscription' => $compliance->canResumeSubscription(),
            'cancel subscription' => $compliance->canCancelSubscription(),
            'deposit' => $compliance->canDeposit(),
            'withdraw' => $compliance->canWithdraw(0), // General check, amount verified separately
            default => throw new \InvalidArgumentException("Unknown action: {$action}"),
        };

        if (!$canPerform) {
            throw new IneligibleActionException(
                attemptedAction: $action,
                complianceSnapshot: $compliance,
                reasons: $compliance->getBlockers()
            );
        }
    }

    /**
     * Change subscription plan (upgrade or downgrade)
     *
     * @param int $userId User ID
     * @param int $newPlanId New plan ID
     * @return UserAggregate Updated user aggregate
     * @throws IneligibleActionException If user cannot change plan
     * @throws SubscriptionNotFoundException If user has no subscription
     */
    public function changeSubscriptionPlan(int $userId, int $newPlanId): UserAggregate
    {
        // Assert eligibility first
        $this->assertCan('change plan', $userId);

        return DB::transaction(function () use ($userId, $newPlanId) {
            $aggregate = $this->load($userId);

            // Verify subscription exists
            if ($aggregate->subscription === null) {
                throw new SubscriptionNotFoundException($userId);
            }

            $subscription = $aggregate->subscription;
            $newPlan = Plan::findOrFail($newPlanId);

            // Determine if upgrade or downgrade
            $isUpgrade = $newPlan->amount > $subscription->amount;

            // Delegate to existing service
            if ($isUpgrade) {
                $this->subscriptionService->upgradePlan($subscription, $newPlan);
                $operation = 'upgrade';
            } else {
                $this->subscriptionService->downgradePlan($subscription, $newPlan);
                $operation = 'downgrade';
            }

            // Log operation for audit
            Log::info("User {$userId} {$operation} subscription plan", [
                'subscription_id' => $subscription->id,
                'old_plan_id' => $subscription->plan_id,
                'new_plan_id' => $newPlanId,
                'old_amount' => $subscription->amount,
                'new_amount' => $newPlan->amount,
            ]);

            // Reload aggregate with fresh data
            return $this->load($userId);
        });
    }

    /**
     * Pause subscription
     *
     * @param int $userId User ID
     * @param int $pauseMonths Number of months to pause (1-3)
     * @return UserAggregate Updated user aggregate
     * @throws IneligibleActionException If user cannot pause subscription
     * @throws SubscriptionNotFoundException If user has no subscription
     */
    public function pauseSubscription(int $userId, int $pauseMonths): UserAggregate
    {
        // Assert eligibility first
        $this->assertCan('pause subscription', $userId);

        return DB::transaction(function () use ($userId, $pauseMonths) {
            $aggregate = $this->load($userId);

            // Verify subscription exists
            if ($aggregate->subscription === null) {
                throw new SubscriptionNotFoundException($userId);
            }

            // Delegate to existing service
            $this->subscriptionService->pauseSubscription(
                $aggregate->subscription,
                $pauseMonths
            );

            // Log operation for audit
            Log::info("User {$userId} paused subscription", [
                'subscription_id' => $aggregate->subscription->id,
                'pause_months' => $pauseMonths,
            ]);

            // Reload aggregate with fresh data
            return $this->load($userId);
        });
    }

    /**
     * Resume subscription from paused state
     *
     * @param int $userId User ID
     * @return UserAggregate Updated user aggregate
     * @throws IneligibleActionException If user cannot resume subscription
     * @throws SubscriptionNotFoundException If user has no subscription
     */
    public function resumeSubscription(int $userId): UserAggregate
    {
        // Assert eligibility first
        $this->assertCan('resume subscription', $userId);

        return DB::transaction(function () use ($userId) {
            $aggregate = $this->load($userId);

            // Verify subscription exists
            if ($aggregate->subscription === null) {
                throw new SubscriptionNotFoundException($userId);
            }

            // Delegate to existing service
            $this->subscriptionService->resumeSubscription($aggregate->subscription);

            // Log operation for audit
            Log::info("User {$userId} resumed subscription", [
                'subscription_id' => $aggregate->subscription->id,
            ]);

            // Reload aggregate with fresh data
            return $this->load($userId);
        });
    }

    /**
     * Cancel subscription
     *
     * @param int $userId User ID
     * @param string $reason Cancellation reason
     * @return UserAggregate Updated user aggregate
     * @throws IneligibleActionException If user cannot cancel subscription
     * @throws SubscriptionNotFoundException If user has no subscription
     */
    public function cancelSubscription(int $userId, string $reason): UserAggregate
    {
        // Assert eligibility first
        $this->assertCan('cancel subscription', $userId);

        return DB::transaction(function () use ($userId, $reason) {
            $aggregate = $this->load($userId);

            // Verify subscription exists
            if ($aggregate->subscription === null) {
                throw new SubscriptionNotFoundException($userId);
            }

            // Delegate to existing service
            $this->subscriptionService->cancelSubscription(
                $aggregate->subscription,
                $reason
            );

            // Log operation for audit
            Log::info("User {$userId} cancelled subscription", [
                'subscription_id' => $aggregate->subscription->id,
                'reason' => $reason,
            ]);

            // Reload aggregate with fresh data
            return $this->load($userId);
        });
    }

    /**
     * Export user data for GDPR compliance
     *
     * @param int $userId User ID
     * @return array<string, mixed> Complete user data export
     */
    public function exportUserData(int $userId): array
    {
        $aggregate = $this->load($userId);

        // Load complete data (not just recent)
        $user = $aggregate->user;

        return [
            'user' => $user->toArray(),
            'profile' => $aggregate->profile?->toArray(),
            'kyc' => $aggregate->kyc?->toArray(),
            'wallet' => $aggregate->wallet?->toArray(),
            'subscriptions' => $user->subscriptions()->get()->toArray(),
            'payments' => $user->payments()->get()->toArray(),
            'transactions' => $user->wallet?->transactions()->get()->toArray() ?? [],
            'bonuses' => $user->bonuses()->get()->toArray(),
            'withdrawals' => $user->withdrawals()->get()->toArray(),
            'referrals' => $user->referrals()->get()->toArray(),
            'activity_logs' => $user->activityLogs()->get()->toArray(),
            'tickets' => $user->tickets()->get()->toArray(),
            'compliance_snapshot' => $aggregate->compliance->toArray(),
            'exported_at' => now()->toIso8601String(),
        ];
    }
}
