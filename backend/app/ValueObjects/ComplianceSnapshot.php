<?php

declare(strict_types=1);

namespace App\ValueObjects;

use App\Enums\KycComplianceState;
use App\Enums\SubscriptionComplianceState;
use App\Enums\WalletComplianceState;

/**
 * ComplianceSnapshot Value Object
 *
 * Immutable snapshot of a user's compliance state across all domains.
 * This is DERIVED data, never persisted directly.
 *
 * Represents ONE authoritative place to understand cross-domain compliance.
 *
 * @package App\ValueObjects
 */
final readonly class ComplianceSnapshot
{
    /**
     * Create a new ComplianceSnapshot
     *
     * @param KycComplianceState $kycState
     * @param WalletComplianceState $walletState
     * @param SubscriptionComplianceState $subscriptionState
     * @param int $balancePaise Current wallet balance in paise
     * @param int $lockedBalancePaise Locked wallet balance in paise
     * @param bool $isBlacklisted User blacklist status
     * @param bool $isAnonymized User anonymization status
     * @param string $userStatus User account status (active/suspended/blocked)
     */
    public function __construct(
        public KycComplianceState $kycState,
        public WalletComplianceState $walletState,
        public SubscriptionComplianceState $subscriptionState,
        public int $balancePaise,
        public int $lockedBalancePaise,
        public bool $isBlacklisted,
        public bool $isAnonymized,
        public string $userStatus,
    ) {
    }

    /**
     * Check if user can make deposits
     *
     * Requirements:
     * - KYC must be approved (compliance gate)
     * - Wallet must be active
     * - User must not be blacklisted
     * - User must be active
     *
     * @return bool
     */
    public function canDeposit(): bool
    {
        return $this->kycState->isApproved()
            && $this->walletState->isActive()
            && !$this->isBlacklisted
            && $this->userStatus === 'active';
    }

    /**
     * Check if user can create a new subscription
     *
     * Requirements:
     * - KYC must be approved (for paid plans)
     * - Wallet must be active
     * - User must not be blacklisted
     * - User must be active
     * - No existing active/paused subscription (business rule)
     *
     * @return bool
     */
    public function canCreateSubscription(): bool
    {
        return $this->kycState->isApproved()
            && $this->walletState->isActive()
            && !$this->isBlacklisted
            && $this->userStatus === 'active'
            && !$this->subscriptionState->isActiveOrPaused();
    }

    /**
     * Check if user can change/upgrade/downgrade subscription plan
     *
     * Requirements:
     * - Must have modifiable subscription (active or paused)
     * - User must be active
     * - User must not be blacklisted
     *
     * @return bool
     */
    public function canChangeSubscriptionPlan(): bool
    {
        return $this->subscriptionState->canBeModified()
            && !$this->isBlacklisted
            && $this->userStatus === 'active';
    }

    /**
     * Check if user can pause subscription
     *
     * Requirements:
     * - Subscription must be active (can't pause if already paused)
     * - User must be active
     * - User must not be blacklisted
     *
     * @return bool
     */
    public function canPauseSubscription(): bool
    {
        return $this->subscriptionState === SubscriptionComplianceState::ACTIVE
            && !$this->isBlacklisted
            && $this->userStatus === 'active';
    }

    /**
     * Check if user can resume subscription
     *
     * Requirements:
     * - Subscription must be paused
     * - User must be active
     * - User must not be blacklisted
     *
     * @return bool
     */
    public function canResumeSubscription(): bool
    {
        return $this->subscriptionState === SubscriptionComplianceState::PAUSED
            && !$this->isBlacklisted
            && $this->userStatus === 'active';
    }

    /**
     * Check if user can cancel subscription
     *
     * Requirements:
     * - Must have modifiable subscription
     * - User must be active (even blacklisted users can cancel)
     *
     * @return bool
     */
    public function canCancelSubscription(): bool
    {
        return $this->subscriptionState->canBeModified()
            && $this->userStatus === 'active';
    }

    /**
     * Check if user can withdraw funds
     *
     * Requirements:
     * - KYC must be approved
     * - Wallet must be active
     * - Must have sufficient available balance
     * - User must not be blacklisted
     * - User must be active
     *
     * @param int $amountPaise Amount to withdraw in paise
     * @return bool
     */
    public function canWithdraw(int $amountPaise): bool
    {
        $availableBalancePaise = $this->balancePaise - $this->lockedBalancePaise;

        return $this->kycState->isApproved()
            && $this->walletState->isActive()
            && $availableBalancePaise >= $amountPaise
            && !$this->isBlacklisted
            && $this->userStatus === 'active';
    }

    /**
     * Get available balance in paise
     *
     * @return int
     */
    public function getAvailableBalancePaise(): int
    {
        return max(0, $this->balancePaise - $this->lockedBalancePaise);
    }

    /**
     * Get available balance in rupees
     *
     * @return float
     */
    public function getAvailableBalance(): float
    {
        return $this->getAvailableBalancePaise() / 100;
    }

    /**
     * Check if user account is in good standing
     *
     * Good standing means:
     * - User is active
     * - Not blacklisted
     * - Not anonymized
     *
     * @return bool
     */
    public function isInGoodStanding(): bool
    {
        return $this->userStatus === 'active'
            && !$this->isBlacklisted
            && !$this->isAnonymized;
    }

    /**
     * Get a summary of compliance blockers
     *
     * Returns an array of human-readable reasons why certain actions are blocked
     *
     * @return array<string>
     */
    public function getBlockers(): array
    {
        $blockers = [];

        if (!$this->kycState->isApproved()) {
            $blockers[] = "KYC is {$this->kycState->label()}";
        }

        if (!$this->walletState->isActive()) {
            $blockers[] = 'Wallet is inactive';
        }

        if ($this->isBlacklisted) {
            $blockers[] = 'User is blacklisted';
        }

        if ($this->isAnonymized) {
            $blockers[] = 'User is anonymized';
        }

        if ($this->userStatus !== 'active') {
            $blockers[] = "User account is {$this->userStatus}";
        }

        return $blockers;
    }

    /**
     * Convert to array for logging/debugging
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'kyc_state' => $this->kycState->value,
            'wallet_state' => $this->walletState->value,
            'subscription_state' => $this->subscriptionState->value,
            'balance_paise' => $this->balancePaise,
            'locked_balance_paise' => $this->lockedBalancePaise,
            'available_balance_paise' => $this->getAvailableBalancePaise(),
            'is_blacklisted' => $this->isBlacklisted,
            'is_anonymized' => $this->isAnonymized,
            'user_status' => $this->userStatus,
            'in_good_standing' => $this->isInGoodStanding(),
        ];
    }
}
