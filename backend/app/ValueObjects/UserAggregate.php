<?php

declare(strict_types=1);

namespace App\ValueObjects;

use App\Models\Subscription;
use App\Models\User;
use App\Models\UserKyc;
use App\Models\UserProfile;
use App\Models\Wallet;
use Illuminate\Support\Collection;

/**
 * UserAggregate Read Model
 *
 * Immutable aggregate that contains all user-related data hydrated
 * from the database in a single read operation.
 *
 * This is the SINGLE SOURCE OF TRUTH for user state in the domain layer.
 *
 * Controllers should NEVER access User model relationships directly.
 * Instead, they should use UserAggregateService to load this aggregate.
 *
 * @package App\ValueObjects
 */
final readonly class UserAggregate
{
    /**
     * Create a new UserAggregate
     *
     * @param User $user User entity
     * @param ComplianceSnapshot $compliance Derived compliance state
     * @param UserProfile|null $profile User profile (may not exist)
     * @param UserKyc|null $kyc KYC record (may not exist)
     * @param Wallet|null $wallet Wallet (may not exist)
     * @param Subscription|null $subscription Latest subscription (may not exist)
     * @param Collection $allSubscriptions All subscriptions (paginated separately if needed)
     * @param Collection $recentPayments Recent payments (last N)
     * @param Collection $recentTransactions Recent wallet transactions (last N)
     */
    public function __construct(
        public User $user,
        public ComplianceSnapshot $compliance,
        public ?UserProfile $profile,
        public ?UserKyc $kyc,
        public ?Wallet $wallet,
        public ?Subscription $subscription,
        public Collection $allSubscriptions,
        public Collection $recentPayments,
        public Collection $recentTransactions,
    ) {
    }

    /**
     * Get user ID
     *
     * @return int
     */
    public function getUserId(): int
    {
        return $this->user->id;
    }

    /**
     * Get user email
     *
     * @return string
     */
    public function getEmail(): string
    {
        return $this->user->email;
    }

    /**
     * Get user mobile
     *
     * @return string|null
     */
    public function getMobile(): ?string
    {
        return $this->user->mobile;
    }

    /**
     * Get full name from profile
     *
     * @return string|null
     */
    public function getFullName(): ?string
    {
        if ($this->profile === null) {
            return null;
        }

        return trim(implode(' ', array_filter([
            $this->profile->first_name,
            $this->profile->middle_name,
            $this->profile->last_name,
        ]))) ?: null;
    }

    /**
     * Check if user has an active subscription
     *
     * @return bool
     */
    public function hasActiveSubscription(): bool
    {
        return $this->subscription !== null
            && in_array($this->subscription->status, ['active', 'paused'], true);
    }

    /**
     * Check if user has any subscription (including pending/cancelled)
     *
     * @return bool
     */
    public function hasAnySubscription(): bool
    {
        return $this->subscription !== null;
    }

    /**
     * Get available wallet balance in rupees
     *
     * @return float
     */
    public function getAvailableBalance(): float
    {
        return $this->compliance->getAvailableBalance();
    }

    /**
     * Get available wallet balance in paise
     *
     * @return int
     */
    public function getAvailableBalancePaise(): int
    {
        return $this->compliance->getAvailableBalancePaise();
    }

    /**
     * Check if KYC is approved
     *
     * @return bool
     */
    public function isKycApproved(): bool
    {
        return $this->compliance->kycState->isApproved();
    }

    /**
     * Check if user is in good standing
     *
     * @return bool
     */
    public function isInGoodStanding(): bool
    {
        return $this->compliance->isInGoodStanding();
    }

    /**
     * Export to array for API responses
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'user' => [
                'id' => $this->user->id,
                'email' => $this->user->email,
                'mobile' => $this->user->mobile,
                'status' => $this->user->status,
                'referral_code' => $this->user->referral_code,
            ],
            'profile' => $this->profile?->toArray(),
            'kyc' => $this->kyc ? [
                'status' => $this->kyc->status,
                'verified_at' => $this->kyc->verified_at?->toIso8601String(),
            ] : null,
            'wallet' => $this->wallet ? [
                'balance' => $this->wallet->balance,
                'locked_balance' => $this->wallet->locked_balance,
                'available_balance' => $this->getAvailableBalance(),
            ] : null,
            'subscription' => $this->subscription?->toArray(),
            'compliance' => $this->compliance->toArray(),
        ];
    }
}
