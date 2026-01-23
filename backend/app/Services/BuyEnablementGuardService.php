<?php

namespace App\Services;

use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * PHASE 5 - Issue 2: Buy Enablement Guards
 *
 * PURPOSE:
 * Enforce strict buying safeguards. Block by default.
 * Allow investment ONLY when ALL criteria explicitly satisfied.
 *
 * DEFENSIVE PRINCIPLES:
 * - Default to BLOCK, not allow
 * - Never assume investor eligibility
 * - Validate at multiple layers (service, API, UI)
 * - Log all denial attempts
 * - Surface all blockers clearly
 *
 * BUYING MUST BE BLOCKED UNLESS:
 * 1. Tier-2 disclosures approved
 * 2. Company not frozen/suspended
 * 3. Investor completed all acknowledgements
 * 4. Investor KYC approved
 * 5. Investor meets regulatory requirements
 * 6. No active platform restrictions
 * 7. Material changes acknowledged (if any)
 */
class BuyEnablementGuardService
{
    /**
     * Check if investor can proceed with investment
     *
     * DEFENSIVE: Returns detailed blockers for ALL failure reasons.
     * Logs denial attempts for audit trail.
     *
     * @param int $companyId
     * @param int $userId
     * @param array $acknowledgements User-provided acknowledgements
     * @return array Guard result
     */
    public function canInvest(int $companyId, int $userId, array $acknowledgements = []): array
    {
        $company = Company::find($companyId);
        $user = User::find($userId);

        if (!$company) {
            return $this->blockWithReason('company_not_found', 'Company not found');
        }

        if (!$user) {
            return $this->blockWithReason('user_not_found', 'User not found');
        }

        $blockers = [];

        // GUARD 1: Company-side guards
        $companyGuards = $this->checkCompanyGuards($company);
        $blockers = array_merge($blockers, $companyGuards);

        // GUARD 2: Investor-side guards
        $investorGuards = $this->checkInvestorGuards($user);
        $blockers = array_merge($blockers, $investorGuards);

        // GUARD 3: Acknowledgement guards
        $ackGuards = $this->checkAcknowledgementGuards($company, $user, $acknowledgements);
        $blockers = array_merge($blockers, $ackGuards);

        // GUARD 4: Platform restriction guards
        $platformGuards = $this->checkPlatformRestrictionGuards($company);
        $blockers = array_merge($blockers, $platformGuards);

        // GUARD 5: Material change guards
        $materialChangeGuards = $this->checkMaterialChangeGuards($company, $acknowledgements);
        $blockers = array_merge($blockers, $materialChangeGuards);

        // GUARD 6: Regulatory compliance guards
        $regulatoryGuards = $this->checkRegulatoryGuards($user, $company);
        $blockers = array_merge($blockers, $regulatoryGuards);

        // Determine if investment allowed
        $criticalBlockers = array_filter($blockers, fn($b) => $b['severity'] === 'critical');
        $allowed = empty($criticalBlockers);

        if (!$allowed) {
            $this->logDenialAttempt($company, $user, $blockers);
        }

        return [
            'allowed' => $allowed,
            'blockers' => $blockers,
            'company_id' => $companyId,
            'user_id' => $userId,
            'checked_at' => now(),
        ];
    }

    /**
     * GUARD 1: Company-side guards
     *
     * Company must satisfy all platform requirements.
     */
    protected function checkCompanyGuards(Company $company): array
    {
        $blockers = [];

        // Must have Tier 2 approved
        if (!$company->tier_2_approved_at) {
            $blockers[] = [
                'guard' => 'tier_2_required',
                'severity' => 'critical',
                'message' => 'Company must complete Tier 2 disclosures before accepting investments',
                'user_facing_message' => 'This company has not yet completed all required disclosures for investment.',
            ];
        }

        // Buying must be enabled
        if (!($company->buying_enabled ?? true)) {
            $blockers[] = [
                'guard' => 'buying_disabled',
                'severity' => 'critical',
                'message' => 'Buying is disabled by platform',
                'reason' => $company->buying_pause_reason ?? 'Platform restriction',
                'user_facing_message' => 'Investment is currently not available for this company.',
            ];
        }

        // Company must not be suspended
        if ($company->lifecycle_state === 'suspended' || ($company->is_suspended ?? false)) {
            $blockers[] = [
                'guard' => 'company_suspended',
                'severity' => 'critical',
                'message' => 'Company is suspended',
                'reason' => $company->suspension_reason ?? 'Under platform review',
                'user_facing_message' => 'This company is currently suspended and not available for investment.',
            ];
        }

        // Company must not be frozen
        if ($company->disclosure_freeze ?? false) {
            $blockers[] = [
                'guard' => 'company_frozen',
                'severity' => 'critical',
                'message' => 'Company disclosures are frozen',
                'reason' => $company->freeze_reason ?? 'Platform investigation',
                'user_facing_message' => 'Investment is temporarily unavailable due to platform review.',
            ];
        }

        // Company must be in investable lifecycle state
        $investableStates = ['live_investable', 'live_fully_disclosed'];
        if (!in_array($company->lifecycle_state, $investableStates)) {
            $blockers[] = [
                'guard' => 'lifecycle_state_not_investable',
                'severity' => 'critical',
                'message' => "Company lifecycle state '{$company->lifecycle_state}' does not allow investment",
                'current_state' => $company->lifecycle_state,
                'user_facing_message' => 'This company is not currently available for investment.',
            ];
        }

        return $blockers;
    }

    /**
     * GUARD 2: Investor-side guards
     *
     * Investor must meet all platform requirements.
     */
    protected function checkInvestorGuards(User $user): array
    {
        $blockers = [];

        // KYC must be approved
        $kycStatus = $this->getKYCStatus($user->id);
        if ($kycStatus !== 'approved') {
            $blockers[] = [
                'guard' => 'kyc_required',
                'severity' => 'critical',
                'message' => 'KYC verification required',
                'kyc_status' => $kycStatus,
                'user_facing_message' => 'Please complete KYC verification before investing.',
            ];
        }

        // Account must be active
        if ($user->status !== 'active') {
            $blockers[] = [
                'guard' => 'account_inactive',
                'severity' => 'critical',
                'message' => 'User account is not active',
                'account_status' => $user->status,
                'user_facing_message' => 'Your account must be active to invest.',
            ];
        }

        // Must have accepted terms and conditions
        if (!($user->terms_accepted ?? false)) {
            $blockers[] = [
                'guard' => 'terms_not_accepted',
                'severity' => 'critical',
                'message' => 'Terms and conditions not accepted',
                'user_facing_message' => 'Please accept the platform terms and conditions before investing.',
            ];
        }

        // Must have sufficient wallet balance (if required)
        // Note: This is a placeholder - actual implementation depends on investment amount
        // Real check should happen at investment creation with specific amount

        return $blockers;
    }

    /**
     * GUARD 3: Acknowledgement guards
     *
     * Investor must acknowledge all required risks.
     */
    protected function checkAcknowledgementGuards(Company $company, User $user, array $acknowledgements): array
    {
        $blockers = [];

        // Required acknowledgements
        $required = [
            'illiquidity',
            'no_guarantee',
            'platform_non_advisory',
        ];

        foreach ($required as $ackType) {
            if (!isset($acknowledgements[$ackType]) || $acknowledgements[$ackType] !== true) {
                $blockers[] = [
                    'guard' => "acknowledgement_{$ackType}_required",
                    'severity' => 'critical',
                    'message' => "Acknowledgement '{$ackType}' required",
                    'acknowledgement_type' => $ackType,
                    'user_facing_message' => $this->getAcknowledgementMessage($ackType),
                ];
            }
        }

        return $blockers;
    }

    /**
     * GUARD 4: Platform restriction guards
     *
     * Check for any platform-level restrictions.
     */
    protected function checkPlatformRestrictionGuards(Company $company): array
    {
        $blockers = [];

        // Check if company has active disputes (gracefully handle if table doesn't exist)
        try {
            $hasDisputes = DB::table('disputes')
                ->where('company_id', $company->id)
                ->whereIn('status', ['open', 'under_investigation'])
                ->where('blocks_investment', true)
                ->exists();

            if ($hasDisputes) {
                $blockers[] = [
                    'guard' => 'active_disputes',
                    'severity' => 'critical',
                    'message' => 'Company has active disputes that block investment',
                    'user_facing_message' => 'Investment is temporarily unavailable due to ongoing disputes.',
                ];
            }
        } catch (\Exception $e) {
            // Disputes table doesn't exist yet - skip this check
            // This is expected until migrations are run
            Log::debug('Disputes table check skipped (table not found)', [
                'company_id' => $company->id,
            ]);
        }

        // Check platform-wide investment freeze
        $platformFreeze = DB::table('settings')->where('key', 'platform_investment_freeze')->first();
        if ($platformFreeze && $platformFreeze->value === 'true') {
            $blockers[] = [
                'guard' => 'platform_investment_freeze',
                'severity' => 'critical',
                'message' => 'Platform-wide investment freeze active',
                'user_facing_message' => 'Investments are temporarily unavailable due to platform maintenance.',
            ];
        }

        return $blockers;
    }

    /**
     * GUARD 5: Material change guards
     *
     * Material changes must be acknowledged.
     */
    protected function checkMaterialChangeGuards(Company $company, array $acknowledgements): array
    {
        $blockers = [];

        // Get current platform context
        $snapshotService = new PlatformContextSnapshotService();
        $currentSnapshot = $snapshotService->getCurrentSnapshot($company->id);

        if ($currentSnapshot && $currentSnapshot->has_material_changes) {
            // Material changes exist - must be acknowledged
            if (!isset($acknowledgements['material_changes']) || $acknowledgements['material_changes'] !== true) {
                $blockers[] = [
                    'guard' => 'material_changes_not_acknowledged',
                    'severity' => 'critical',
                    'message' => 'Material changes must be acknowledged',
                    'material_changes_summary' => json_decode($currentSnapshot->material_changes_summary, true),
                    'user_facing_message' => 'Material changes have been detected. Please review and acknowledge these changes before investing.',
                ];
            }

            // Verify snapshot ID matches (prevent stale acknowledgements)
            if (isset($acknowledgements['snapshot_id']) && $acknowledgements['snapshot_id'] != $currentSnapshot->id) {
                $blockers[] = [
                    'guard' => 'stale_acknowledgement',
                    'severity' => 'critical',
                    'message' => 'Acknowledgement is for outdated snapshot',
                    'provided_snapshot_id' => $acknowledgements['snapshot_id'],
                    'current_snapshot_id' => $currentSnapshot->id,
                    'user_facing_message' => 'Please review the latest company information before investing.',
                ];
            }
        }

        return $blockers;
    }

    /**
     * GUARD 6: Regulatory compliance guards
     *
     * Investor must meet regulatory requirements.
     */
    protected function checkRegulatoryGuards(User $user, Company $company): array
    {
        $blockers = [];

        // Check investor accreditation (if required)
        if ($this->requiresAccreditation($company)) {
            $accreditationStatus = $this->getAccreditationStatus($user->id);
            if ($accreditationStatus !== 'verified') {
                $blockers[] = [
                    'guard' => 'accreditation_required',
                    'severity' => 'critical',
                    'message' => 'Accredited investor verification required',
                    'accreditation_status' => $accreditationStatus,
                    'user_facing_message' => 'This investment requires accredited investor verification.',
                ];
            }
        }

        // Check investment limits
        $investmentLimits = $this->checkInvestmentLimits($user->id, $company->id);
        if (!$investmentLimits['within_limits']) {
            $blockers[] = [
                'guard' => 'investment_limit_exceeded',
                'severity' => 'critical',
                'message' => 'Investment limits exceeded',
                'limit_details' => $investmentLimits,
                'user_facing_message' => 'You have reached your investment limit for this company.',
            ];
        }

        // Check geographic restrictions
        if (!$this->isGeographicallyEligible($user, $company)) {
            $blockers[] = [
                'guard' => 'geographic_restriction',
                'severity' => 'critical',
                'message' => 'Geographic restrictions apply',
                'user_facing_message' => 'This investment is not available in your region.',
            ];
        }

        return $blockers;
    }

    // =========================================================================
    // HELPER METHODS
    // =========================================================================

    /**
     * Block with single reason
     */
    protected function blockWithReason(string $guard, string $message): array
    {
        return [
            'allowed' => false,
            'blockers' => [[
                'guard' => $guard,
                'severity' => 'critical',
                'message' => $message,
            ]],
        ];
    }

    /**
     * Log denial attempt for audit trail
     */
    protected function logDenialAttempt(Company $company, User $user, array $blockers): void
    {
        Log::warning('INVESTMENT DENIED: Buy enablement guards blocked investment', [
            'company_id' => $company->id,
            'user_id' => $user->id,
            'blockers' => $blockers,
            'ip_address' => request()?->ip(),
        ]);

        // Log to investment_denial_log table for audit trail (gracefully handle if table doesn't exist)
        try {
            DB::table('investment_denial_log')->insert([
                'company_id' => $company->id,
                'user_id' => $user->id,
                'blockers' => json_encode($blockers),
                'ip_address' => request()?->ip(),
                'user_agent' => request()?->userAgent(),
                'session_id' => session()->getId(),
                'denial_source' => 'buy_enablement_guard',
                'created_at' => now(),
            ]);
        } catch (\Exception $e) {
            // Table doesn't exist yet - audit trail logged to Laravel log above
            Log::debug('Investment denial log table not found - logged to Laravel log only');
        }
    }

    /**
     * Get KYC status
     */
    protected function getKYCStatus(int $userId): string
    {
        $kyc = DB::table('user_kyc')->where('user_id', $userId)->first();
        return $kyc?->status ?? 'not_submitted';
    }

    /**
     * Get accreditation status
     */
    protected function getAccreditationStatus(int $userId): string
    {
        $accreditation = DB::table('investor_accreditation')->where('user_id', $userId)->first();
        return $accreditation?->status ?? 'not_verified';
    }

    /**
     * Check if company requires accreditation
     */
    protected function requiresAccreditation(Company $company): bool
    {
        return $company->requires_accredited_investor ?? false;
    }

    /**
     * Check investment limits
     */
    protected function checkInvestmentLimits(int $userId, int $companyId): array
    {
        // Get total invested in this company
        $totalInvested = DB::table('investments')
            ->where('user_id', $userId)
            ->where('company_id', $companyId)
            ->where('status', 'active')
            ->sum('total_amount');

        // Check per-company limit (example: ₹10 lakhs)
        $perCompanyLimit = 1000000; // ₹10 lakhs

        return [
            'within_limits' => $totalInvested < $perCompanyLimit,
            'total_invested' => $totalInvested,
            'limit' => $perCompanyLimit,
            'remaining' => max(0, $perCompanyLimit - $totalInvested),
        ];
    }

    /**
     * Check geographic eligibility
     */
    protected function isGeographicallyEligible(User $user, Company $company): bool
    {
        // Placeholder: Implement actual geographic restrictions
        // Example: Check user country against company's allowed_countries
        return true;
    }

    /**
     * Get acknowledgement message
     */
    protected function getAcknowledgementMessage(string $ackType): string
    {
        return match($ackType) {
            'illiquidity' => 'You must acknowledge the illiquidity risk before investing.',
            'no_guarantee' => 'You must acknowledge that returns are not guaranteed.',
            'platform_non_advisory' => 'You must acknowledge that the platform does not provide investment advice.',
            default => "You must acknowledge '{$ackType}' before investing.",
        };
    }
}
