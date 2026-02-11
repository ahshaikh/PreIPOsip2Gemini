<?php
/**
 * Compliance Gate Service
 *
 * PROTOCOL ENFORCEMENT: "Compliance must gate money BEFORE, not after"
 *
 * Provides centralized compliance checks that BLOCK financial operations
 * unless all regulatory requirements are met.
 *
 * DESIGN PRINCIPLE:
 * - Fail-safe: Returns false unless explicitly proven compliant
 * - Single source: All compliance gates route through this service
 * - Auditable: All blocks are logged for regulatory review
 */

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log;

class ComplianceGateService
{
    /**
     * Check if user can perform ANY cash ingress operation
     *
     * BEFORE (BROKEN):
     * ```php
     * $walletService->deposit($user, $amount); // ❌ No KYC check
     * ```
     *
     * AFTER (ENFORCED):
     * ```php
     * if (!$complianceGate->canReceiveFunds($user)) {
     *     throw new ComplianceBlockedException('KYC required');
     * }
     * $walletService->deposit($user, $amount); // ✓ KYC verified
     * ```
     *
     * @param User $user
     * @return array ['allowed' => bool, 'reason' => ?string, 'requirements' => array]
     */
    public function canReceiveFunds(User $user): array
    {
        // Check 1: KYC must be complete and approved
        if (!$this->isKycComplete($user)) {
            // ARCH-FIX: Read KYC status from canonical source for logging
            $kycStatus = $this->getKycStatusValue($user);

            Log::warning("COMPLIANCE GATE: Cash ingress blocked - KYC incomplete", [
                'user_id' => $user->id,
                'email' => $user->email,
                'kyc_status' => $kycStatus,
            ]);

            return [
                'allowed' => false,
                'reason' => 'KYC verification required before receiving funds',
                'requirements' => [
                    'kyc_complete' => false,
                    'kyc_status' => $kycStatus,
                    'action_required' => 'Complete KYC verification',
                ],
            ];
        }

        // Check 2: Account must not be suspended/blocked
        if ($user->status === 'suspended' || $user->status === 'blocked') {
            Log::warning("COMPLIANCE GATE: Cash ingress blocked - Account suspended", [
                'user_id' => $user->id,
                'account_status' => $user->status,
            ]);

            return [
                'allowed' => false,
                'reason' => 'Account suspended - contact support',
                'requirements' => [
                    'account_active' => false,
                    'account_status' => $user->status,
                ],
            ];
        }

        // Check 3: AML/CFT checks (if enabled)
        if (setting('enable_aml_checks', false)) {
            $amlResult = $this->checkAmlCompliance($user);
            if (!$amlResult['passed']) {
                return [
                    'allowed' => false,
                    'reason' => 'AML verification required',
                    'requirements' => $amlResult,
                ];
            }
        }

        // All checks passed
        return [
            'allowed' => true,
            'reason' => null,
            'requirements' => [
                'kyc_complete' => true,
                'account_active' => true,
            ],
        ];
    }

    /**
     * Check if user can invest (stricter than wallet deposits)
     *
     * @param User $user
     * @param float $amount Investment amount
     * @return array
     */
    public function canInvest(User $user, float $amount): array
    {
        // First check: Must pass basic fund receiving requirements
        $fundCheckResult = $this->canReceiveFunds($user);
        if (!$fundCheckResult['allowed']) {
            return $fundCheckResult;
        }

        // Additional check: Age verification (18+ for investments)
        $ageRequirement = $this->checkAgeRequirement($user);
        if (!$ageRequirement['met']) {
            return [
                'allowed' => false,
                'reason' => 'Age verification required for investments',
                'requirements' => $ageRequirement,
            ];
        }

        // Additional check: Investment limits based on KYC level
        $limitCheck = $this->checkInvestmentLimits($user, $amount);
        if (!$limitCheck['allowed']) {
            return $limitCheck;
        }

        return [
            'allowed' => true,
            'reason' => null,
            'requirements' => [
                'kyc_complete' => true,
                'account_active' => true,
                'age_verified' => true,
                'within_limits' => true,
            ],
        ];
    }

    /**
     * Check if user can withdraw funds
     *
     * @param User $user
     * @param float $amount Withdrawal amount
     * @return array
     */
    public function canWithdraw(User $user, float $amount): array
    {
        // Same base requirements as receiving funds
        $fundCheckResult = $this->canReceiveFunds($user);
        if (!$fundCheckResult['allowed']) {
            return $fundCheckResult;
        }

        // Additional check: Bank account must be verified
        if (!$this->isBankAccountVerified($user)) {
            return [
                'allowed' => false,
                'reason' => 'Bank account verification required for withdrawals',
                'requirements' => [
                    'bank_verified' => false,
                    'action_required' => 'Add and verify bank account',
                ],
            ];
        }

        // Additional check: Minimum holding period (if applicable)
        $holdingPeriodCheck = $this->checkMinimumHoldingPeriod($user);
        if (!$holdingPeriodCheck['met']) {
            return [
                'allowed' => false,
                'reason' => $holdingPeriodCheck['reason'],
                'requirements' => $holdingPeriodCheck,
            ];
        }

        return [
            'allowed' => true,
            'reason' => null,
            'requirements' => [
                'kyc_complete' => true,
                'account_active' => true,
                'bank_verified' => true,
                'holding_period_met' => true,
            ],
        ];
    }

    /**
     * Get KYC status value from canonical source.
     *
     * ARCH-FIX: Helper to read KYC status without triggering accessor N+1 concerns.
     *
     * @param User $user
     * @return string
     */
    private function getKycStatusValue(User $user): string
    {
        $status = $user->relationLoaded('kyc')
            ? $user->kyc?->status
            : $user->kyc()->value('status');

        if ($status === null) {
            return 'not_submitted';
        }

        return is_object($status) && method_exists($status, 'value')
            ? $status->value
            : $status;
    }

    /**
     * Check if KYC is complete and approved
     * V-FIX-WALLET-NOT-REFLECTING: Fixed to check for 'verified' (actual KycStatus enum value)
     *
     * ARCH-FIX: Reads directly from user_kyc table (single source of truth).
     * Does not rely on accessor to avoid N+1 concerns in compliance gates.
     *
     * @param User $user
     * @return bool
     */
    private function isKycComplete(User $user): bool
    {
        return $this->getKycStatusValue($user) === 'verified';
    }

    /**
     * Check AML/CFT compliance
     *
     * @param User $user
     * @return array
     */
    private function checkAmlCompliance(User $user): array
    {
        // Placeholder for AML checks
        // In production: Integrate with AML screening service

        // For now: Check if user is flagged
        if ($user->is_flagged ?? false) {
            return [
                'passed' => false,
                'reason' => 'Account flagged for AML review',
            ];
        }

        return ['passed' => true];
    }

    /**
     * Check age requirement for investments
     *
     * @param User $user
     * @return array
     */
    private function checkAgeRequirement(User $user): array
    {
        $minimumAge = (int) setting('minimum_investment_age', 18);

        if (!$user->date_of_birth) {
            return [
                'met' => false,
                'reason' => 'Date of birth not provided',
                'action_required' => 'Update profile with date of birth',
            ];
        }

        $age = now()->diffInYears($user->date_of_birth);

        if ($age < $minimumAge) {
            return [
                'met' => false,
                'reason' => "Minimum age requirement not met (Required: {$minimumAge}+)",
                'current_age' => $age,
                'required_age' => $minimumAge,
            ];
        }

        return [
            'met' => true,
            'current_age' => $age,
        ];
    }

    /**
     * Check investment limits based on KYC level
     *
     * @param User $user
     * @param float $amount
     * @return array
     */
    private function checkInvestmentLimits(User $user, float $amount): array
    {
        // Get KYC level (basic, full, etc.)
        $kycLevel = $user->kyc_level ?? 'basic';

        // Get limits from settings
        $limits = [
            'basic' => (float) setting('kyc_basic_max_investment', 50000),
            'full' => (float) setting('kyc_full_max_investment', 500000),
            'premium' => (float) setting('kyc_premium_max_investment', 5000000),
        ];

        $maxAllowed = $limits[$kycLevel] ?? $limits['basic'];

        if ($amount > $maxAllowed) {
            return [
                'allowed' => false,
                'reason' => "Investment amount exceeds KYC limit",
                'kyc_level' => $kycLevel,
                'max_allowed' => $maxAllowed,
                'requested' => $amount,
                'action_required' => 'Upgrade KYC level for higher limits',
            ];
        }

        return [
            'allowed' => true,
            'kyc_level' => $kycLevel,
            'max_allowed' => $maxAllowed,
            'utilized' => $amount,
        ];
    }

    /**
     * Check if bank account is verified
     *
     * @param User $user
     * @return bool
     */
    private function isBankAccountVerified(User $user): bool
    {
        // Check if user has at least one verified bank account
        return $user->bankAccounts()->where('is_verified', true)->exists();
    }

    /**
     * Check minimum holding period for first withdrawal
     *
     * @param User $user
     * @return array
     */
    private function checkMinimumHoldingPeriod(User $user): array
    {
        $minimumDays = (int) setting('minimum_holding_period_days', 0);

        if ($minimumDays === 0) {
            return ['met' => true];
        }

        $firstInvestmentDate = $user->investments()
            ->where('status', 'completed')
            ->min('created_at');

        if (!$firstInvestmentDate) {
            // No investments yet - allow withdrawal of wallet balance
            return ['met' => true];
        }

        $daysSinceFirstInvestment = now()->diffInDays($firstInvestmentDate);

        if ($daysSinceFirstInvestment < $minimumDays) {
            return [
                'met' => false,
                'reason' => "Minimum holding period not met",
                'days_remaining' => $minimumDays - $daysSinceFirstInvestment,
                'minimum_days' => $minimumDays,
                'first_investment_date' => $firstInvestmentDate,
            ];
        }

        return [
            'met' => true,
            'days_held' => $daysSinceFirstInvestment,
        ];
    }

    /**
     * Log compliance block for audit trail
     *
     * @param User $user
     * @param string $operation
     * @param array $blockReason
     * @return void
     */
    public function logComplianceBlock(User $user, string $operation, array $blockReason): void
    {
        Log::channel('compliance')->warning("COMPLIANCE BLOCK", [
            'user_id' => $user->id,
            'email' => $user->email,
            'operation' => $operation,
            'blocked_at' => now()->toDateTimeString(),
            'reason' => $blockReason['reason'],
            'requirements' => $blockReason['requirements'] ?? [],
        ]);

        // Store in audit_trails table for compliance reporting
        \App\Models\AuditTrail::create([
            'user_id' => $user->id,
            'action' => 'compliance_block',
            'description' => "Operation blocked: {$operation}",
            'metadata' => json_encode($blockReason),
            'ip_address' => request()->ip(),
        ]);
    }
}
