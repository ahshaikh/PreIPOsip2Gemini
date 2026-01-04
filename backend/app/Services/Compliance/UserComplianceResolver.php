<?php

declare(strict_types=1);

namespace App\Services\Compliance;

use App\Enums\KycComplianceState;
use App\Enums\KycStatus;
use App\Enums\SubscriptionComplianceState;
use App\Enums\WalletComplianceState;
use App\Models\User;
use App\ValueObjects\ComplianceSnapshot;

/**
 * UserComplianceResolver Service
 *
 * Derives a user's compliance state from their database relationships.
 * This is the SINGLE authoritative source for compliance state resolution.
 *
 * NO database writes occur here - this is purely read/derivation logic.
 *
 * @package App\Services\Compliance
 */
final class UserComplianceResolver
{
    /**
     * Resolve compliance snapshot from a User model
     *
     * This method safely handles null relationships and derives the current
     * compliance state across all domains (KYC, Wallet, Subscription).
     *
     * @param User $user User model (may or may not have relationships loaded)
     * @return ComplianceSnapshot Immutable compliance state snapshot
     */
    public static function from(User $user): ComplianceSnapshot
    {
        // Safely load relationships if not already loaded
        $user->loadMissing(['kyc', 'wallet', 'subscription']);

        // Derive KYC compliance state
        $kycState = self::resolveKycState($user);

        // Derive Wallet compliance state
        $walletState = self::resolveWalletState($user);

        // Derive Subscription compliance state
        $subscriptionState = self::resolveSubscriptionState($user);

        // Get wallet balances (default to 0 if no wallet)
        $balancePaise = $user->wallet?->balance_paise ?? 0;
        $lockedBalancePaise = $user->wallet?->locked_balance_paise ?? 0;

        // Get user flags
        $isBlacklisted = $user->is_blacklisted ?? false;
        $isAnonymized = $user->is_anonymized ?? false;
        $userStatus = $user->status ?? 'active';

        return new ComplianceSnapshot(
            kycState: $kycState,
            walletState: $walletState,
            subscriptionState: $subscriptionState,
            balancePaise: $balancePaise,
            lockedBalancePaise: $lockedBalancePaise,
            isBlacklisted: $isBlacklisted,
            isAnonymized: $isAnonymized,
            userStatus: $userStatus,
        );
    }

    /**
     * Resolve KYC compliance state from user
     *
     * @param User $user
     * @return KycComplianceState
     */
    private static function resolveKycState(User $user): KycComplianceState
    {
        // If no KYC record exists, user is unverified
        if ($user->kyc === null) {
            return KycComplianceState::UNVERIFIED;
        }

        // Convert database status string to KycStatus enum
        $kycStatus = KycStatus::tryFrom($user->kyc->status);

        // Derive compliance state from KycStatus
        return KycComplianceState::fromKycStatus($kycStatus);
    }

    /**
     * Resolve Wallet compliance state from user
     *
     * Wallet state is binary: either exists (active) or doesn't (inactive)
     *
     * @param User $user
     * @return WalletComplianceState
     */
    private static function resolveWalletState(User $user): WalletComplianceState
    {
        return $user->wallet !== null
            ? WalletComplianceState::ACTIVE
            : WalletComplianceState::INACTIVE;
    }

    /**
     * Resolve Subscription compliance state from user
     *
     * Uses the latest subscription relationship (defined in User model)
     *
     * @param User $user
     * @return SubscriptionComplianceState
     */
    private static function resolveSubscriptionState(User $user): SubscriptionComplianceState
    {
        // If no subscription exists, state is NONE
        if ($user->subscription === null) {
            return SubscriptionComplianceState::NONE;
        }

        // Derive from subscription status
        return SubscriptionComplianceState::fromSubscriptionStatus(
            $user->subscription->status
        );
    }

    /**
     * Quick check if user can perform KYC-gated actions
     *
     * This is a convenience method for the most common compliance check.
     *
     * @param User $user
     * @return bool
     */
    public static function isKycApproved(User $user): bool
    {
        return self::from($user)->kycState->isApproved();
    }

    /**
     * Quick check if user is in good standing
     *
     * @param User $user
     * @return bool
     */
    public static function isInGoodStanding(User $user): bool
    {
        return self::from($user)->isInGoodStanding();
    }
}
