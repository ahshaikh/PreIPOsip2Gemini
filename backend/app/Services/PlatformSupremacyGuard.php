<?php

namespace App\Services;

use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * PHASE 3 HARDENING - Issue 4: Role Supremacy Guard
 *
 * PURPOSE:
 * Platform state (freeze, investigation, suspension) supersedes issuer roles.
 * Even if user has Founder role, platform can block actions.
 *
 * PLATFORM STATES THAT OVERRIDE ROLES:
 * - suspended: Blocks ALL issuer actions except viewing
 * - disclosure_freeze: Blocks editing, allows viewing
 * - under_investigation: Adds review delays, extra scrutiny
 * - buying_paused: Blocks go-live, allows other actions
 *
 * GUARD ORDER:
 * 1. Check platform state first (this guard)
 * 2. Then check role permissions (existing policy)
 * 3. Then check business logic (existing service)
 *
 * USAGE IN POLICY:
 * ```php
 * public function update(User $user, CompanyDisclosure $disclosure): Response
 * {
 *     // CRITICAL: Check platform state first
 *     $platformGuard = new PlatformSupremacyGuard();
 *     $platformCheck = $platformGuard->canPerformAction(
 *         $disclosure->company,
 *         'edit_disclosure'
 *     );
 *
 *     if (!$platformCheck['allowed']) {
 *         return Response::deny($platformCheck['reason']);
 *     }
 *
 *     // Then check role permissions...
 * }
 * ```
 */
class PlatformSupremacyGuard
{
    /**
     * Actions and their platform restrictions
     *
     * Each action maps to which platform states block it
     */
    protected const ACTION_RESTRICTIONS = [
        'view_disclosure' => [],                             // Never blocked
        'edit_disclosure' => ['suspended', 'frozen'],        // Blocked if suspended or frozen
        'submit_disclosure' => ['suspended'],                 // Blocked if suspended
        'answer_clarification' => ['suspended', 'frozen'],   // Blocked if suspended or frozen
        'report_error' => ['suspended'],                     // Blocked only if suspended (transparency)
        'attach_documents' => ['suspended', 'frozen'],       // Blocked if suspended or frozen
        'delete_disclosure' => ['suspended', 'frozen'],      // Blocked if suspended or frozen
        'manage_users' => ['suspended'],                     // Blocked if suspended
        'view_dashboard' => [],                              // Never blocked
    ];

    /**
     * Check if user can perform action given platform state
     *
     * RETURNS:
     * - allowed: bool
     * - reason: string (why blocked, if blocked)
     * - platform_state: array (active restrictions)
     * - override_applied: bool (true if platform overrode role permission)
     *
     * @param Company $company
     * @param string $action Action identifier (e.g., 'edit_disclosure')
     * @param User|null $user Optional user context
     * @return array Guard result
     */
    public function canPerformAction(Company $company, string $action, ?User $user = null): array
    {
        // Get current platform state
        $platformState = $this->getPlatformState($company);

        // Check if action is restricted by current platform state
        $blockingStates = self::ACTION_RESTRICTIONS[$action] ?? [];

        foreach ($blockingStates as $state) {
            if ($platformState[$state]) {
                $reason = $this->getBlockReason($state, $action);

                Log::warning('PLATFORM SUPREMACY: Action blocked by platform state', [
                    'company_id' => $company->id,
                    'user_id' => $user?->id,
                    'action' => $action,
                    'blocking_state' => $state,
                    'reason' => $reason,
                ]);

                return [
                    'allowed' => false,
                    'reason' => $reason,
                    'platform_state' => $platformState,
                    'override_applied' => true,
                    'blocking_state' => $state,
                ];
            }
        }

        // Platform does not block this action
        return [
            'allowed' => true,
            'reason' => null,
            'platform_state' => $platformState,
            'override_applied' => false,
        ];
    }

    /**
     * Check if company has any active platform restrictions
     *
     * @param Company $company
     * @return bool True if any restrictions active
     */
    public function hasActiveRestrictions(Company $company): bool
    {
        $state = $this->getPlatformState($company);
        return $state['suspended'] || $state['frozen'] || $state['under_investigation'] || $state['buying_paused'];
    }

    /**
     * Get all active platform restrictions for company
     *
     * @param Company $company
     * @return array List of active restrictions with details
     */
    public function getActiveRestrictions(Company $company): array
    {
        $state = $this->getPlatformState($company);
        $restrictions = [];

        if ($state['suspended']) {
            $restrictions[] = [
                'type' => 'suspension',
                'severity' => 'critical',
                'message' => 'Company is suspended',
                'reason' => $company->suspension_reason ?? 'Under platform review',
                'since' => $company->suspended_at,
                'blocks' => ['All editing', 'All submissions', 'User management'],
            ];
        }

        if ($state['frozen'] && !$state['suspended']) {
            $restrictions[] = [
                'type' => 'disclosure_freeze',
                'severity' => 'high',
                'message' => 'Disclosures are frozen',
                'reason' => $company->freeze_reason ?? 'Platform investigation',
                'since' => $company->frozen_at ?? null,
                'blocks' => ['Editing disclosures', 'Answering clarifications'],
            ];
        }

        if ($state['under_investigation'] && !$state['suspended'] && !$state['frozen']) {
            $restrictions[] = [
                'type' => 'under_investigation',
                'severity' => 'medium',
                'message' => 'Under platform investigation',
                'reason' => $company->investigation_reason ?? 'Compliance review',
                'since' => $company->investigation_started_at ?? null,
                'blocks' => [],
                'adds_delays' => true,
            ];
        }

        if ($state['buying_paused'] && !$state['suspended']) {
            $restrictions[] = [
                'type' => 'buying_paused',
                'severity' => 'high',
                'message' => 'Investment buying is paused',
                'reason' => $company->buying_pause_reason ?? 'Platform risk assessment',
                'since' => $company->buying_paused_at ?? null,
                'blocks' => [],
                'affects_go_live' => true,
            ];
        }

        return $restrictions;
    }

    /**
     * Get human-readable block reason
     *
     * @param string $state Platform state type
     * @param string $action Action attempted
     * @return string Reason message
     */
    protected function getBlockReason(string $state, string $action): string
    {
        $messages = [
            'suspended' => 'Company is suspended by platform. This action is not allowed until suspension is lifted.',
            'frozen' => 'Disclosures are frozen by platform. No edits allowed until freeze is lifted.',
            'under_investigation' => 'Company is under platform investigation. This action requires additional review.',
            'buying_paused' => 'Investment buying is paused by platform. Cannot proceed with go-live actions.',
        ];

        return $messages[$state] ?? "Action blocked by platform state: {$state}";
    }

    /**
     * Get current platform state for company
     *
     * @param Company $company
     * @return array Platform state flags
     */
    protected function getPlatformState(Company $company): array
    {
        return [
            'suspended' => $company->lifecycle_state === 'suspended' || ($company->is_suspended ?? false),
            'frozen' => $company->disclosure_freeze ?? false,
            'under_investigation' => $company->under_investigation ?? false,
            'buying_paused' => !($company->buying_enabled ?? true),
            'lifecycle_state' => $company->lifecycle_state,
            'governance_state_version' => $company->governance_state_version ?? 1,
        ];
    }

    /**
     * Check if action requires platform override consent
     *
     * Some actions (like error reporting during investigation)
     * may be allowed but require extra platform review.
     *
     * @param Company $company
     * @param string $action
     * @return array Override requirement details
     */
    public function requiresPlatformOverride(Company $company, string $action): array
    {
        $state = $this->getPlatformState($company);

        // Actions that require extra review during investigation
        $reviewRequired = $state['under_investigation'] && in_array($action, [
            'submit_disclosure',
            'report_error',
        ]);

        if ($reviewRequired) {
            return [
                'required' => true,
                'reason' => 'Company is under investigation - submission requires additional platform review',
                'expected_delay' => '1-3 additional business days',
                'platform_state' => $state,
            ];
        }

        return [
            'required' => false,
            'reason' => null,
            'expected_delay' => null,
            'platform_state' => $state,
        ];
    }

    /**
     * Log platform override denial for audit trail
     *
     * @param Company $company
     * @param User|null $user
     * @param string $action
     * @param string $blockingState
     * @return void
     */
    public function logPlatformDenial(
        Company $company,
        ?User $user,
        string $action,
        string $blockingState
    ): void {
        Log::warning('PLATFORM SUPREMACY DENIAL', [
            'company_id' => $company->id,
            'company_name' => $company->name,
            'user_id' => $user?->id,
            'user_email' => $user?->email,
            'action_attempted' => $action,
            'blocking_state' => $blockingState,
            'lifecycle_state' => $company->lifecycle_state,
            'timestamp' => now(),
            'ip_address' => request()?->ip(),
        ]);
    }

    /**
     * Check if platform state should show warning to issuer
     *
     * @param Company $company
     * @return array|null Warning details if should show, null otherwise
     */
    public function getPlatformWarning(Company $company): ?array
    {
        $state = $this->getPlatformState($company);

        if ($state['suspended']) {
            return [
                'severity' => 'critical',
                'title' => 'Company Suspended',
                'message' => $company->suspension_reason ?? 'Your company is suspended. All actions are restricted.',
                'show_banner' => $company->show_warning_banner ?? true,
                'banner_message' => $company->warning_banner_message,
            ];
        }

        if ($state['frozen']) {
            return [
                'severity' => 'high',
                'title' => 'Disclosures Frozen',
                'message' => $company->freeze_reason ?? 'Platform has frozen your disclosures. No edits allowed.',
                'show_banner' => true,
            ];
        }

        if ($state['under_investigation']) {
            return [
                'severity' => 'medium',
                'title' => 'Under Platform Review',
                'message' => 'Your company is under platform investigation. Submissions will have additional review time.',
                'show_banner' => false, // Don't show banner, just notice
            ];
        }

        if ($state['buying_paused']) {
            return [
                'severity' => 'high',
                'title' => 'Investment Buying Paused',
                'message' => $company->buying_pause_reason ?? 'Platform has paused investment buying for your company.',
                'show_banner' => false,
            ];
        }

        return null;
    }
}
