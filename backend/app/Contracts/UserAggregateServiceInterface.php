<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Exceptions\Domain\IneligibleActionException;
use App\Exceptions\Domain\SubscriptionNotFoundException;
use App\Models\Plan;
use App\Models\User;
use App\ValueObjects\UserAggregate;

/**
 * UserAggregateService Interface
 *
 * Contract for the User Aggregate Service.
 * This is the domain-level interface for all user-related operations.
 *
 * Controllers MUST use this service instead of directly accessing models.
 *
 * @package App\Contracts
 */
interface UserAggregateServiceInterface
{
    /**
     * Load user aggregate with all related data
     *
     * @param int $userId User ID
     * @return UserAggregate Complete user aggregate
     */
    public function load(int $userId): UserAggregate;

    /**
     * Load user aggregate from User model instance
     *
     * @param User $user User model
     * @return UserAggregate Complete user aggregate
     */
    public function loadFromUser(User $user): UserAggregate;

    /**
     * Assert that user can perform a specific action
     *
     * @param string $action Action name (e.g., 'create subscription', 'pause subscription')
     * @param int $userId User ID
     * @throws IneligibleActionException If user cannot perform action
     * @return void
     */
    public function assertCan(string $action, int $userId): void;

    /**
     * Change subscription plan (upgrade or downgrade)
     *
     * This method encapsulates all business rules for plan changes:
     * - Eligibility checks
     * - Pro-rata calculations
     * - Status updates
     * - Audit logging
     *
     * @param int $userId User ID
     * @param int $newPlanId New plan ID
     * @return UserAggregate Updated user aggregate
     * @throws IneligibleActionException If user cannot change plan
     * @throws SubscriptionNotFoundException If user has no subscription
     */
    public function changeSubscriptionPlan(int $userId, int $newPlanId): UserAggregate;

    /**
     * Pause subscription
     *
     * @param int $userId User ID
     * @param int $pauseMonths Number of months to pause (1-3)
     * @return UserAggregate Updated user aggregate
     * @throws IneligibleActionException If user cannot pause subscription
     * @throws SubscriptionNotFoundException If user has no subscription
     */
    public function pauseSubscription(int $userId, int $pauseMonths): UserAggregate;

    /**
     * Resume subscription from paused state
     *
     * @param int $userId User ID
     * @return UserAggregate Updated user aggregate
     * @throws IneligibleActionException If user cannot resume subscription
     * @throws SubscriptionNotFoundException If user has no subscription
     */
    public function resumeSubscription(int $userId): UserAggregate;

    /**
     * Cancel subscription
     *
     * @param int $userId User ID
     * @param string $reason Cancellation reason
     * @return UserAggregate Updated user aggregate
     * @throws IneligibleActionException If user cannot cancel subscription
     * @throws SubscriptionNotFoundException If user has no subscription
     */
    public function cancelSubscription(int $userId, string $reason): UserAggregate;

    /**
     * Export user data for GDPR compliance
     *
     * Returns all user data in a portable format.
     *
     * @param int $userId User ID
     * @return array<string, mixed> Complete user data export
     */
    public function exportUserData(int $userId): array;
}
