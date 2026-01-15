<?php

namespace App\Services;

use App\Models\Company;
use App\Models\CompanyLifecycleLog;
use App\Models\DisclosureModule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * PHASE 2 - SERVICE: CompanyLifecycleService
 *
 * PURPOSE:
 * Manages company lifecycle state transitions based on disclosure tier approvals.
 * Enforces state machine rules and buying permissions.
 *
 * STATE TRANSITIONS:
 * draft → live_limited (Tier 1 complete)
 * live_limited → live_investable (Tier 2 complete)
 * live_investable → live_fully_disclosed (Tier 3 complete)
 * any → suspended (admin action)
 *
 * BUYING RULES:
 * - draft: NO buying
 * - live_limited: NO buying (profile visible only)
 * - live_investable: YES buying
 * - live_fully_disclosed: YES buying
 * - suspended: NO buying (hard block)
 *
 * RESPONSIBILITIES:
 * - Check tier completion and auto-transition
 * - Validate state transitions
 * - Update buying_enabled flag
 * - Log all state changes
 * - Handle suspensions
 */
class CompanyLifecycleService
{
    /**
     * Check if company should transition to next lifecycle state
     * based on disclosure tier approvals.
     *
     * AUTO-TRANSITIONS:
     * - All Tier 1 modules approved → live_limited
     * - All Tier 2 modules approved → live_investable
     * - All Tier 3 modules approved → live_fully_disclosed
     *
     * @param Company $company
     * @return bool Whether state changed
     */
    public function checkAndTransition(Company $company): bool
    {
        // PHASE 2 HARDENING: Suspend-Aware Auto-Transition Guard
        // CRITICAL: Prevent any auto-transition while company is suspended
        if ($company->lifecycle_state === 'suspended' || $company->is_suspended) {
            Log::warning('Auto-transition blocked: Company is suspended', [
                'company_id' => $company->id,
                'lifecycle_state' => $company->lifecycle_state,
            ]);
            return false;
        }

        DB::beginTransaction();

        try {
            $currentState = $company->lifecycle_state;
            $newState = null;
            $trigger = 'tier_approval';
            $metadata = [];

            // Check Tier 1 completion (draft → live_limited)
            if ($currentState === 'draft' && $this->isTierComplete($company, 1)) {
                $newState = 'live_limited';
                $metadata = [
                    'tier_completed' => 1,
                    'modules_approved' => $this->getApprovedModulesByTier($company, 1)->pluck('code')->toArray(),
                ];

                $company->tier_1_approved_at = now();
                $company->buying_enabled = false; // Tier 1 doesn't enable buying
            }

            // Check Tier 2 completion (live_limited → live_investable)
            elseif ($currentState === 'live_limited' && $this->isTierComplete($company, 2)) {
                $newState = 'live_investable';
                $metadata = [
                    'tier_completed' => 2,
                    'modules_approved' => $this->getApprovedModulesByTier($company, 2)->pluck('code')->toArray(),
                ];

                $company->tier_2_approved_at = now();
                $company->buying_enabled = true; // TIER 2 ENABLES BUYING
            }

            // Check Tier 3 completion (live_investable → live_fully_disclosed)
            elseif ($currentState === 'live_investable' && $this->isTierComplete($company, 3)) {
                $newState = 'live_fully_disclosed';
                $metadata = [
                    'tier_completed' => 3,
                    'modules_approved' => $this->getApprovedModulesByTier($company, 3)->pluck('code')->toArray(),
                ];

                $company->tier_3_approved_at = now();
                $company->buying_enabled = true; // Maintain buying
            }

            // No transition needed
            if (!$newState) {
                DB::rollBack();
                return false;
            }

            // Perform transition
            $this->transitionTo($company, $newState, $trigger, null, null, $metadata);

            DB::commit();
            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Lifecycle transition failed', [
                'company_id' => $company->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Transition company to new lifecycle state
     *
     * @param Company $company
     * @param string $newState
     * @param string $trigger tier_approval|admin_action|system
     * @param int|null $triggeredBy User ID
     * @param string|null $reason
     * @param array $metadata
     * @throws \RuntimeException if transition invalid
     */
    public function transitionTo(
        Company $company,
        string $newState,
        string $trigger = 'admin_action',
        ?int $triggeredBy = null,
        ?string $reason = null,
        array $metadata = []
    ): void {
        $currentState = $company->lifecycle_state;

        // Validate transition
        if (!$this->isValidTransition($currentState, $newState, $trigger)) {
            throw new \RuntimeException(
                "Invalid lifecycle transition: {$currentState} → {$newState}"
            );
        }

        // Update company state
        $company->lifecycle_state = $newState;
        $company->lifecycle_state_changed_at = now();
        $company->lifecycle_state_changed_by = $triggeredBy ?? auth()->id();
        $company->lifecycle_state_change_reason = $reason;

        // Update buying permission based on new state
        $company->buying_enabled = $this->isBuyingAllowed($newState);

        $company->save();

        // Log transition
        $this->logTransition(
            $company,
            $currentState,
            $newState,
            $trigger,
            $triggeredBy,
            $reason,
            $metadata
        );

        Log::info('Company lifecycle state changed', [
            'company_id' => $company->id,
            'from' => $currentState,
            'to' => $newState,
            'trigger' => $trigger,
            'buying_enabled' => $company->buying_enabled,
        ]);
    }

    /**
     * Suspend company (admin action)
     *
     * CRITICAL: Hard blocks buying, shows warning banner
     *
     * @param Company $company
     * @param int $adminId
     * @param string $publicReason Shown to investors
     * @param string|null $internalNotes Admin-only notes
     */
    public function suspend(
        Company $company,
        int $adminId,
        string $publicReason,
        ?string $internalNotes = null
    ): void {
        DB::beginTransaction();

        try {
            $previousState = $company->lifecycle_state;

            // Update suspension fields
            $company->suspended_at = now();
            $company->suspended_by = $adminId;
            $company->suspension_reason = $publicReason;
            $company->suspension_internal_notes = $internalNotes;
            $company->show_warning_banner = true;
            $company->warning_banner_message = $publicReason;

            // Transition to suspended state
            $this->transitionTo(
                $company,
                'suspended',
                'admin_action',
                $adminId,
                $publicReason,
                [
                    'previous_state' => $previousState,
                    'has_internal_notes' => !empty($internalNotes),
                ]
            );

            // CRITICAL: buying_enabled set to false by transitionTo()
            // Verify it's disabled
            if ($company->fresh()->buying_enabled) {
                throw new \RuntimeException('CRITICAL: Buying still enabled after suspension');
            }

            DB::commit();

            Log::critical('Company suspended', [
                'company_id' => $company->id,
                'admin_id' => $adminId,
                'previous_state' => $previousState,
                'public_reason' => $publicReason,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Suspension failed', [
                'company_id' => $company->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Unsuspend company (restore to previous state)
     *
     * @param Company $company
     * @param int $adminId
     * @param string $targetState State to restore to
     * @param string|null $reason
     */
    public function unsuspend(
        Company $company,
        int $adminId,
        string $targetState,
        ?string $reason = null
    ): void {
        if ($company->lifecycle_state !== 'suspended') {
            throw new \RuntimeException('Company is not suspended');
        }

        DB::beginTransaction();

        try {
            // Clear suspension fields
            $company->suspended_at = null;
            $company->suspended_by = null;
            $company->suspension_reason = null;
            $company->suspension_internal_notes = null;
            $company->show_warning_banner = false;
            $company->warning_banner_message = null;

            // Transition to target state
            $this->transitionTo(
                $company,
                $targetState,
                'admin_action',
                $adminId,
                $reason ?? 'Company unsuspended',
                ['unsuspended' => true]
            );

            DB::commit();

            Log::info('Company unsuspended', [
                'company_id' => $company->id,
                'admin_id' => $adminId,
                'restored_to' => $targetState,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    // =========================================================================
    // VALIDATION & HELPER METHODS
    // =========================================================================

    /**
     * Check if all required modules for a tier are approved
     *
     * @param Company $company
     * @param int $tier 1, 2, or 3
     * @return bool
     */
    public function isTierComplete(Company $company, int $tier): bool
    {
        // Get all required modules for this tier
        $requiredModules = DisclosureModule::where('tier', $tier)
            ->where('is_required', true)
            ->where('is_active', true)
            ->pluck('id');

        if ($requiredModules->isEmpty()) {
            return false;
        }

        // Check if company has approved disclosures for all required modules
        $approvedCount = $company->disclosures()
            ->whereIn('disclosure_module_id', $requiredModules)
            ->where('status', 'approved')
            ->distinct('disclosure_module_id')
            ->count('disclosure_module_id');

        return $approvedCount === $requiredModules->count();
    }

    /**
     * Get approved modules for a tier
     */
    protected function getApprovedModulesByTier(Company $company, int $tier)
    {
        return DisclosureModule::whereIn('id', function ($query) use ($company) {
            $query->select('disclosure_module_id')
                ->from('company_disclosures')
                ->where('company_id', $company->id)
                ->where('status', 'approved');
        })
        ->where('tier', $tier)
        ->get();
    }

    /**
     * Validate if state transition is allowed
     */
    protected function isValidTransition(string $from, string $to, string $trigger): bool
    {
        // Suspension can happen from any state (admin action)
        if ($to === 'suspended' && $trigger === 'admin_action') {
            return true;
        }

        // Unsuspension (suspended → any)
        if ($from === 'suspended' && $trigger === 'admin_action') {
            return in_array($to, ['draft', 'live_limited', 'live_investable', 'live_fully_disclosed']);
        }

        // Normal progression (tier approvals)
        $validTransitions = [
            'draft' => ['live_limited'],
            'live_limited' => ['live_investable'],
            'live_investable' => ['live_fully_disclosed'],
            'live_fully_disclosed' => [], // Terminal state (except suspension)
        ];

        return in_array($to, $validTransitions[$from] ?? []);
    }

    /**
     * Determine if buying is allowed in this state
     */
    protected function isBuyingAllowed(string $state): bool
    {
        return in_array($state, ['live_investable', 'live_fully_disclosed']);
    }

    /**
     * Log state transition to audit trail
     */
    protected function logTransition(
        Company $company,
        string $fromState,
        string $toState,
        string $trigger,
        ?int $triggeredBy,
        ?string $reason,
        array $metadata
    ): void {
        CompanyLifecycleLog::create([
            'company_id' => $company->id,
            'from_state' => $fromState,
            'to_state' => $toState,
            'trigger' => $trigger,
            'triggered_by' => $triggeredBy ?? auth()->id(),
            'reason' => $reason,
            'metadata' => $metadata,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Get current tier level for company
     *
     * @param Company $company
     * @return int 0-3
     */
    public function getCurrentTier(Company $company): int
    {
        return match ($company->lifecycle_state) {
            'draft' => 0,
            'live_limited' => 1,
            'live_investable' => 2,
            'live_fully_disclosed' => 3,
            'suspended' => -1,
            default => 0,
        };
    }

    /**
     * Check if company can accept investments
     *
     * GUARD: Use this before any buy operation
     *
     * @param Company $company
     * @return bool
     */
    public function canAcceptInvestments(Company $company): bool
    {
        return $company->buying_enabled === true
            && $company->lifecycle_state !== 'suspended'
            && in_array($company->lifecycle_state, ['live_investable', 'live_fully_disclosed']);
    }

    /**
     * Get reason why buying is blocked
     *
     * @param Company $company
     * @return string|null
     */
    public function getBuyingBlockedReason(Company $company): ?string
    {
        if ($company->lifecycle_state === 'suspended') {
            return 'Company is suspended: ' . $company->suspension_reason;
        }

        if ($company->lifecycle_state === 'draft') {
            return 'Company profile is not yet public';
        }

        if ($company->lifecycle_state === 'live_limited') {
            return 'Company has not completed financial disclosures required for investment';
        }

        if (!$company->buying_enabled) {
            return 'Buying is currently disabled for this company';
        }

        return null; // Buying allowed
    }
}
