<?php

namespace App\Services;

use App\Models\Company;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * PHASE 2 HARDENING - Issue 5: Tier-2 Risk Controls (Platform-Level)
 *
 * PURPOSE:
 * Enforces explicit platform-level risk controls for Tier 2 companies.
 * These are NOT issuer workflow rules - they are platform governance safeguards.
 *
 * TIER 2 RISK CONTROLS:
 * 1. DUAL APPROVAL: Requires 2 distinct admin approvals for Tier 2 go-live
 * 2. MANDATORY REVIEW DELAY: Minimum 48-hour cooling-off period between submission and approval
 * 3. EARLY INVESTMENT VELOCITY CAPS: Rate limits for newly approved Tier 2 companies
 *
 * WHY TIER 2 SPECIFICALLY:
 * - Tier 1: Basic info only, low risk
 * - Tier 2: FINANCIAL DATA + BUYING ENABLED = High risk exposure
 * - Tier 3: Already vetted through Tier 2, incremental risk only
 *
 * ARCHITECTURE:
 * - Separate from issuer disclosure workflow
 * - Enforced at platform level (not company level)
 * - Immutable audit trail in platform_governance_log
 * - Cannot be bypassed without explicit override + dual approval
 */
class Tier2RiskControlService
{
    /**
     * Check if Tier 2 approval requirements are met
     *
     * VALIDATES:
     * - Dual approval (2 distinct admins)
     * - Mandatory review delay (48 hours)
     * - No blocking issues
     *
     * @param Company $company
     * @return array Validation result with status and blockers
     */
    public function validateTier2Approval(Company $company): array
    {
        $blockers = [];
        $warnings = [];

        // Check if already approved
        if ($company->tier_2_approved_at !== null) {
            return [
                'can_approve' => false,
                'is_approved' => true,
                'reason' => 'Tier 2 already approved',
                'approved_at' => $company->tier_2_approved_at,
            ];
        }

        // CONTROL 1: Verify Tier 1 is complete
        if ($company->tier_1_approved_at === null) {
            $blockers[] = [
                'control' => 'tier_1_prerequisite',
                'severity' => 'critical',
                'message' => 'Tier 1 must be approved before Tier 2',
            ];
        }

        // CONTROL 2: Check dual approval requirement
        $approvalCount = $this->getTier2ApprovalCount($company->id);
        if ($approvalCount < 2) {
            $blockers[] = [
                'control' => 'dual_approval',
                'severity' => 'critical',
                'message' => "Tier 2 requires 2 distinct admin approvals. Current: {$approvalCount}",
                'current_count' => $approvalCount,
                'required_count' => 2,
            ];
        }

        // CONTROL 3: Mandatory review delay (48 hours from first approval)
        $firstApproval = $this->getFirstTier2Approval($company->id);
        if ($firstApproval) {
            $hoursSinceFirstApproval = Carbon::parse($firstApproval->created_at)->diffInHours(now());
            $requiredHours = 48;

            if ($hoursSinceFirstApproval < $requiredHours) {
                $remainingHours = $requiredHours - $hoursSinceFirstApproval;
                $blockers[] = [
                    'control' => 'mandatory_review_delay',
                    'severity' => 'critical',
                    'message' => "Tier 2 requires {$requiredHours}-hour review delay. Remaining: {$remainingHours} hours",
                    'hours_elapsed' => $hoursSinceFirstApproval,
                    'hours_required' => $requiredHours,
                    'hours_remaining' => $remainingHours,
                    'can_approve_at' => Carbon::parse($firstApproval->created_at)->addHours($requiredHours),
                ];
            }
        }

        // CONTROL 4: Verify all Tier 2 modules approved
        $tier2ModulesComplete = $this->areTier2ModulesComplete($company);
        if (!$tier2ModulesComplete) {
            $blockers[] = [
                'control' => 'tier_2_modules_incomplete',
                'severity' => 'critical',
                'message' => 'All Tier 2 disclosure modules must be approved',
            ];
        }

        // CONTROL 5: Check if company is suspended
        if ($company->lifecycle_state === 'suspended' || $company->is_suspended) {
            $blockers[] = [
                'control' => 'suspension_block',
                'severity' => 'critical',
                'message' => 'Cannot approve Tier 2 while company is suspended',
            ];
        }

        $canApprove = empty($blockers);

        return [
            'can_approve' => $canApprove,
            'is_approved' => false,
            'blockers' => $blockers,
            'warnings' => $warnings,
            'approvals' => [
                'count' => $approvalCount,
                'required' => 2,
                'approvers' => $this->getTier2Approvers($company->id),
            ],
            'review_delay' => $firstApproval ? [
                'first_approval_at' => $firstApproval->created_at,
                'hours_elapsed' => Carbon::parse($firstApproval->created_at)->diffInHours(now()),
                'hours_required' => 48,
                'delay_satisfied' => Carbon::parse($firstApproval->created_at)->diffInHours(now()) >= 48,
            ] : null,
        ];
    }

    /**
     * Record Tier 2 approval vote (first or second admin)
     *
     * DUAL APPROVAL WORKFLOW:
     * 1. First admin approves → Recorded, waiting for second
     * 2. Second admin approves → Tier 2 go-live (if delay satisfied)
     *
     * @param Company $company
     * @param int $adminId
     * @param string $approvalNotes
     * @return array Result with approval status
     */
    public function recordTier2Approval(Company $company, int $adminId, string $approvalNotes): array
    {
        DB::beginTransaction();

        try {
            // Verify admin hasn't already approved
            $existingApproval = DB::table('platform_governance_log')
                ->where('company_id', $company->id)
                ->where('action_type', 'tier_2_approval_vote')
                ->where('admin_user_id', $adminId)
                ->first();

            if ($existingApproval) {
                throw new \RuntimeException('You have already approved Tier 2 for this company');
            }

            // Record approval vote
            $logId = DB::table('platform_governance_log')->insertGetId([
                'company_id' => $company->id,
                'action_type' => 'tier_2_approval_vote',
                'from_state' => $company->lifecycle_state,
                'to_state' => null, // Not yet transitioned
                'decision_reason' => $approvalNotes,
                'admin_user_id' => $adminId,
                'is_automated' => false,
                'is_immutable' => true,
                'metadata' => json_encode([
                    'approval_number' => $this->getTier2ApprovalCount($company->id) + 1,
                    'admin_name' => auth()->user()->name ?? 'Unknown',
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Check if requirements met for final approval
            $validation = $this->validateTier2Approval($company->fresh());

            if ($validation['can_approve']) {
                // FINAL APPROVAL: Mark Tier 2 as approved
                $company->tier_2_approved_at = now();
                $company->tier_2_approved_by = $adminId; // Last approver
                $company->save();

                // Log final approval
                DB::table('platform_governance_log')->insert([
                    'company_id' => $company->id,
                    'action_type' => 'tier_2_final_approval',
                    'from_state' => $company->lifecycle_state,
                    'to_state' => 'live_investable',
                    'decision_reason' => 'Dual approval + review delay satisfied',
                    'admin_user_id' => $adminId,
                    'is_automated' => false,
                    'is_immutable' => true,
                    'metadata' => json_encode([
                        'approvers' => $this->getTier2Approvers($company->id),
                        'final_approver_id' => $adminId,
                    ]),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::commit();

                Log::info('TIER 2 FINAL APPROVAL: All risk controls satisfied', [
                    'company_id' => $company->id,
                    'admin_id' => $adminId,
                    'approvers' => $this->getTier2Approvers($company->id),
                ]);

                return [
                    'status' => 'approved',
                    'message' => 'Tier 2 approved - Company can now accept investments',
                    'approval_type' => 'final',
                    'tier_2_approved_at' => $company->tier_2_approved_at,
                ];
            } else {
                // PARTIAL APPROVAL: Waiting for second admin or delay
                DB::commit();

                Log::info('TIER 2 PARTIAL APPROVAL: Waiting for second approval or delay', [
                    'company_id' => $company->id,
                    'admin_id' => $adminId,
                    'approval_count' => $this->getTier2ApprovalCount($company->id),
                    'blockers' => $validation['blockers'],
                ]);

                return [
                    'status' => 'pending',
                    'message' => 'Tier 2 approval vote recorded. Waiting for second approval and/or review delay.',
                    'approval_type' => 'partial',
                    'validation' => $validation,
                ];
            }

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to record Tier 2 approval', [
                'company_id' => $company->id,
                'admin_id' => $adminId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * CONTROL 3: Check early investment velocity caps for newly approved Tier 2
     *
     * RATE LIMITS (First 30 days after Tier 2 approval):
     * - Max 100 investments per day
     * - Max 500 investments total in first 30 days
     * - Max ₹50 lakhs total investment in first 30 days
     *
     * @param Company $company
     * @return array Velocity check result
     */
    public function checkInvestmentVelocityCap(Company $company): array
    {
        // Only applies to recently approved Tier 2 companies (within 30 days)
        if (!$company->tier_2_approved_at) {
            return [
                'applies' => false,
                'reason' => 'Tier 2 not yet approved',
            ];
        }

        $daysSinceApproval = Carbon::parse($company->tier_2_approved_at)->diffInDays(now());
        if ($daysSinceApproval > 30) {
            return [
                'applies' => false,
                'reason' => 'Velocity caps expired (>30 days since Tier 2 approval)',
                'days_since_approval' => $daysSinceApproval,
            ];
        }

        // Check daily investment count
        $todayInvestmentCount = DB::table('investments')
            ->where('company_id', $company->id)
            ->whereDate('created_at', today())
            ->count();

        $dailyCapExceeded = $todayInvestmentCount >= 100;

        // Check 30-day totals
        $thirtyDayStats = DB::table('investments')
            ->where('company_id', $company->id)
            ->where('created_at', '>=', $company->tier_2_approved_at)
            ->selectRaw('COUNT(*) as total_count, SUM(amount) as total_amount')
            ->first();

        $totalCapExceeded = $thirtyDayStats->total_count >= 500;
        $amountCapExceeded = $thirtyDayStats->total_amount >= 5000000; // ₹50 lakhs

        $isBlocked = $dailyCapExceeded || $totalCapExceeded || $amountCapExceeded;

        return [
            'applies' => true,
            'is_blocked' => $isBlocked,
            'days_since_approval' => $daysSinceApproval,
            'caps' => [
                'daily_count' => [
                    'current' => $todayInvestmentCount,
                    'limit' => 100,
                    'exceeded' => $dailyCapExceeded,
                ],
                'thirty_day_count' => [
                    'current' => $thirtyDayStats->total_count,
                    'limit' => 500,
                    'exceeded' => $totalCapExceeded,
                ],
                'thirty_day_amount' => [
                    'current' => $thirtyDayStats->total_amount,
                    'limit' => 5000000,
                    'exceeded' => $amountCapExceeded,
                ],
            ],
            'message' => $isBlocked
                ? 'Investment velocity cap exceeded for early-stage Tier 2 company'
                : 'Investment allowed - within velocity caps',
        ];
    }

    // =========================================================================
    // HELPER METHODS
    // =========================================================================

    /**
     * Get count of Tier 2 approval votes
     */
    protected function getTier2ApprovalCount(int $companyId): int
    {
        return DB::table('platform_governance_log')
            ->where('company_id', $companyId)
            ->where('action_type', 'tier_2_approval_vote')
            ->distinct('admin_user_id')
            ->count('admin_user_id');
    }

    /**
     * Get first Tier 2 approval record
     */
    protected function getFirstTier2Approval(int $companyId)
    {
        return DB::table('platform_governance_log')
            ->where('company_id', $companyId)
            ->where('action_type', 'tier_2_approval_vote')
            ->orderBy('created_at', 'asc')
            ->first();
    }

    /**
     * Get list of admins who approved Tier 2
     */
    protected function getTier2Approvers(int $companyId): array
    {
        return DB::table('platform_governance_log')
            ->where('company_id', $companyId)
            ->where('action_type', 'tier_2_approval_vote')
            ->distinct()
            ->pluck('admin_user_id')
            ->toArray();
    }

    /**
     * Check if all Tier 2 modules are approved
     */
    protected function areTier2ModulesComplete(Company $company): bool
    {
        $requiredModules = DB::table('disclosure_modules')
            ->where('tier', 2)
            ->where('is_required', true)
            ->where('is_active', true)
            ->pluck('id');

        $approvedModules = DB::table('company_disclosures')
            ->where('company_id', $company->id)
            ->whereIn('disclosure_module_id', $requiredModules)
            ->where('status', 'approved')
            ->pluck('disclosure_module_id');

        return $requiredModules->count() === $approvedModules->count();
    }
}
