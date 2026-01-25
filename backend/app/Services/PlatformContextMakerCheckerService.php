<?php

namespace App\Services;

use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * P0 FIX (GAP 13): Maker-Checker Approval for Platform Context
 *
 * PURPOSE:
 * Enforce dual-control (4-eyes principle) for platform context changes.
 * Critical platform decisions require two separate administrators:
 * - MAKER: Initiates the change (proposes)
 * - CHECKER: Reviews and approves/rejects the change
 *
 * ACTIONS REQUIRING MAKER-CHECKER:
 * - Suspension of company
 * - Freeze of disclosures
 * - Investigation status changes
 * - Buying enablement changes
 * - Tier approval overrides
 * - Platform risk score overrides
 *
 * WORKFLOW:
 * 1. Maker initiates change (status: pending_approval)
 * 2. Checker reviews (cannot be same as maker)
 * 3. Checker approves → change applied
 * 4. Checker rejects → change discarded
 *
 * AUDIT TRAIL:
 * All actions logged with full attribution, timestamps, IP addresses.
 */
class PlatformContextMakerCheckerService
{
    /**
     * Actions that MUST go through maker-checker workflow
     */
    const CRITICAL_ACTIONS = [
        'suspend_company',
        'unsuspend_company',
        'freeze_disclosures',
        'unfreeze_disclosures',
        'start_investigation',
        'end_investigation',
        'disable_buying',
        'enable_buying',
        'override_tier_approval',
        'override_risk_score',
        'revoke_tier_approval',
    ];

    /**
     * Actions that can be done by single admin (lower risk)
     */
    const SINGLE_ADMIN_ACTIONS = [
        'update_admin_notes',
        'request_clarification',
        'update_internal_tags',
    ];

    /**
     * Initiate a platform context change (MAKER step)
     *
     * @param Company $company
     * @param User $maker Admin initiating the change
     * @param string $actionType Type of action from CRITICAL_ACTIONS
     * @param array $proposedChanges Changes to apply
     * @param string $reason Why this change is needed (REQUIRED)
     * @param array $supportingData Evidence/documentation
     * @return array{success: bool, approval_request_id: int|null, message: string}
     */
    public function initiateChange(
        Company $company,
        User $maker,
        string $actionType,
        array $proposedChanges,
        string $reason,
        array $supportingData = []
    ): array {
        // Validate action type
        if (!in_array($actionType, self::CRITICAL_ACTIONS)) {
            if (in_array($actionType, self::SINGLE_ADMIN_ACTIONS)) {
                // Single admin action - apply immediately
                return $this->applySingleAdminAction($company, $maker, $actionType, $proposedChanges, $reason);
            }

            return [
                'success' => false,
                'approval_request_id' => null,
                'message' => "Unknown action type: {$actionType}",
            ];
        }

        // Validate maker has admin role
        if (!$this->isAdmin($maker)) {
            return [
                'success' => false,
                'approval_request_id' => null,
                'message' => 'User is not authorized to initiate platform context changes',
            ];
        }

        // Check for pending requests for same action on same company
        $existingPending = $this->getPendingRequest($company->id, $actionType);
        if ($existingPending) {
            return [
                'success' => false,
                'approval_request_id' => $existingPending->id,
                'message' => 'A pending request for this action already exists',
            ];
        }

        // Capture current state for audit trail
        $currentState = $this->captureCurrentState($company);

        DB::beginTransaction();

        try {
            // Create approval request
            $requestId = DB::table('platform_context_approval_requests')->insertGetId([
                'company_id' => $company->id,
                'action_type' => $actionType,
                'status' => 'pending_approval',

                // Maker information
                'maker_user_id' => $maker->id,
                'maker_role' => $this->getUserRole($maker),
                'initiated_at' => now(),
                'maker_reason' => $reason,
                'maker_ip' => request()?->ip(),
                'maker_user_agent' => request()?->userAgent(),

                // Proposed changes
                'proposed_changes' => json_encode($proposedChanges),
                'current_state' => json_encode($currentState),
                'supporting_data' => json_encode($supportingData),

                // Checker fields (null until reviewed)
                'checker_user_id' => null,
                'checker_decision' => null,
                'checker_reason' => null,
                'reviewed_at' => null,

                // Expiry (requests expire after 72 hours)
                'expires_at' => now()->addHours(72),
                'is_expired' => false,

                // Audit
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Log the initiation
            $this->logMakerCheckerAction($requestId, 'initiated', $maker, [
                'company_id' => $company->id,
                'action_type' => $actionType,
                'reason' => $reason,
            ]);

            DB::commit();

            Log::info('MAKER-CHECKER: Change initiated', [
                'request_id' => $requestId,
                'company_id' => $company->id,
                'action_type' => $actionType,
                'maker_id' => $maker->id,
            ]);

            return [
                'success' => true,
                'approval_request_id' => $requestId,
                'message' => 'Change request created. Awaiting checker approval.',
                'expires_at' => now()->addHours(72)->toIso8601String(),
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('MAKER-CHECKER: Failed to initiate change', [
                'company_id' => $company->id,
                'action_type' => $actionType,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'approval_request_id' => null,
                'message' => 'Failed to create change request: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Approve a pending change request (CHECKER step)
     *
     * @param int $requestId Approval request ID
     * @param User $checker Admin approving the change
     * @param string $reason Approval reason
     * @return array{success: bool, message: string, applied: bool}
     */
    public function approveChange(int $requestId, User $checker, string $reason): array
    {
        $request = $this->getRequest($requestId);

        if (!$request) {
            return ['success' => false, 'message' => 'Request not found', 'applied' => false];
        }

        // Validate request status
        if ($request->status !== 'pending_approval') {
            return ['success' => false, 'message' => "Request is not pending (status: {$request->status})", 'applied' => false];
        }

        // Check expiry
        if ($request->is_expired || now()->gt($request->expires_at)) {
            $this->expireRequest($requestId);
            return ['success' => false, 'message' => 'Request has expired', 'applied' => false];
        }

        // CRITICAL: Checker cannot be same as maker (4-eyes principle)
        if ($request->maker_user_id === $checker->id) {
            return [
                'success' => false,
                'message' => 'VIOLATION: Checker cannot be the same person as maker (4-eyes principle)',
                'applied' => false,
            ];
        }

        // Validate checker has admin role
        if (!$this->isAdmin($checker)) {
            return ['success' => false, 'message' => 'User is not authorized to approve changes', 'applied' => false];
        }

        // Validate checker has sufficient permission level
        if (!$this->canCheckAction($checker, $request->action_type)) {
            return ['success' => false, 'message' => 'User does not have permission to approve this action type', 'applied' => false];
        }

        DB::beginTransaction();

        try {
            // Update request with approval
            DB::table('platform_context_approval_requests')
                ->where('id', $requestId)
                ->update([
                    'status' => 'approved',
                    'checker_user_id' => $checker->id,
                    'checker_role' => $this->getUserRole($checker),
                    'checker_decision' => 'approved',
                    'checker_reason' => $reason,
                    'reviewed_at' => now(),
                    'checker_ip' => request()?->ip(),
                    'checker_user_agent' => request()?->userAgent(),
                    'updated_at' => now(),
                ]);

            // Apply the changes
            $company = Company::findOrFail($request->company_id);
            $proposedChanges = json_decode($request->proposed_changes, true);

            $applyResult = $this->applyChanges($company, $request->action_type, $proposedChanges, $requestId);

            if (!$applyResult['success']) {
                DB::rollBack();
                return [
                    'success' => false,
                    'message' => 'Approved but failed to apply: ' . $applyResult['message'],
                    'applied' => false,
                ];
            }

            // Log the approval
            $this->logMakerCheckerAction($requestId, 'approved', $checker, [
                'maker_id' => $request->maker_user_id,
                'action_type' => $request->action_type,
                'reason' => $reason,
            ]);

            DB::commit();

            Log::info('MAKER-CHECKER: Change approved and applied', [
                'request_id' => $requestId,
                'company_id' => $request->company_id,
                'action_type' => $request->action_type,
                'maker_id' => $request->maker_user_id,
                'checker_id' => $checker->id,
            ]);

            return [
                'success' => true,
                'message' => 'Change approved and applied successfully',
                'applied' => true,
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('MAKER-CHECKER: Failed to approve change', [
                'request_id' => $requestId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to approve: ' . $e->getMessage(),
                'applied' => false,
            ];
        }
    }

    /**
     * Reject a pending change request (CHECKER step)
     *
     * @param int $requestId Approval request ID
     * @param User $checker Admin rejecting the change
     * @param string $reason Rejection reason (REQUIRED)
     * @return array{success: bool, message: string}
     */
    public function rejectChange(int $requestId, User $checker, string $reason): array
    {
        if (empty($reason)) {
            return ['success' => false, 'message' => 'Rejection reason is required'];
        }

        $request = $this->getRequest($requestId);

        if (!$request) {
            return ['success' => false, 'message' => 'Request not found'];
        }

        if ($request->status !== 'pending_approval') {
            return ['success' => false, 'message' => "Request is not pending (status: {$request->status})"];
        }

        // Checker cannot be same as maker
        if ($request->maker_user_id === $checker->id) {
            return ['success' => false, 'message' => 'Checker cannot be the same person as maker'];
        }

        if (!$this->isAdmin($checker)) {
            return ['success' => false, 'message' => 'User is not authorized to reject changes'];
        }

        DB::table('platform_context_approval_requests')
            ->where('id', $requestId)
            ->update([
                'status' => 'rejected',
                'checker_user_id' => $checker->id,
                'checker_role' => $this->getUserRole($checker),
                'checker_decision' => 'rejected',
                'checker_reason' => $reason,
                'reviewed_at' => now(),
                'checker_ip' => request()?->ip(),
                'checker_user_agent' => request()?->userAgent(),
                'updated_at' => now(),
            ]);

        $this->logMakerCheckerAction($requestId, 'rejected', $checker, [
            'maker_id' => $request->maker_user_id,
            'action_type' => $request->action_type,
            'reason' => $reason,
        ]);

        Log::info('MAKER-CHECKER: Change rejected', [
            'request_id' => $requestId,
            'company_id' => $request->company_id,
            'action_type' => $request->action_type,
            'checker_id' => $checker->id,
            'reason' => $reason,
        ]);

        return ['success' => true, 'message' => 'Change request rejected'];
    }

    /**
     * Get pending approval requests for a company
     *
     * @param int $companyId
     * @return \Illuminate\Support\Collection
     */
    public function getPendingRequests(int $companyId): \Illuminate\Support\Collection
    {
        return DB::table('platform_context_approval_requests')
            ->where('company_id', $companyId)
            ->where('status', 'pending_approval')
            ->where('is_expired', false)
            ->where('expires_at', '>', now())
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get all requests for a company (for audit trail)
     *
     * @param int $companyId
     * @param int $limit
     * @return \Illuminate\Support\Collection
     */
    public function getRequestHistory(int $companyId, int $limit = 50): \Illuminate\Support\Collection
    {
        return DB::table('platform_context_approval_requests')
            ->where('company_id', $companyId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($request) {
                return [
                    'id' => $request->id,
                    'action_type' => $request->action_type,
                    'status' => $request->status,
                    'maker' => [
                        'user_id' => $request->maker_user_id,
                        'role' => $request->maker_role,
                        'initiated_at' => $request->initiated_at,
                        'reason' => $request->maker_reason,
                    ],
                    'checker' => $request->checker_user_id ? [
                        'user_id' => $request->checker_user_id,
                        'role' => $request->checker_role,
                        'reviewed_at' => $request->reviewed_at,
                        'decision' => $request->checker_decision,
                        'reason' => $request->checker_reason,
                    ] : null,
                    'proposed_changes' => json_decode($request->proposed_changes, true),
                    'current_state' => json_decode($request->current_state, true),
                    'created_at' => $request->created_at,
                ];
            });
    }

    /**
     * Get investor-visible approval history (for transparency)
     *
     * Returns only approved/rejected decisions without internal notes
     *
     * @param int $companyId
     * @param int $limit
     * @return array
     */
    public function getInvestorVisibleHistory(int $companyId, int $limit = 20): array
    {
        $requests = DB::table('platform_context_approval_requests')
            ->where('company_id', $companyId)
            ->whereIn('status', ['approved', 'rejected'])
            ->orderBy('reviewed_at', 'desc')
            ->limit($limit)
            ->get();

        return $requests->map(function ($request) {
            return [
                'action_type' => $this->getActionTypeLabel($request->action_type),
                'status' => $request->status,
                'initiated_at' => $request->initiated_at,
                'reviewed_at' => $request->reviewed_at,
                'decision' => $request->checker_decision,
                'was_multi_admin_approved' => $request->maker_user_id !== $request->checker_user_id,
                // Hide internal reasons from investors, show category only
                'action_category' => $this->getActionCategory($request->action_type),
            ];
        })->toArray();
    }

    // =========================================================================
    // HELPER METHODS
    // =========================================================================

    protected function getRequest(int $requestId): ?object
    {
        return DB::table('platform_context_approval_requests')
            ->where('id', $requestId)
            ->first();
    }

    protected function getPendingRequest(int $companyId, string $actionType): ?object
    {
        return DB::table('platform_context_approval_requests')
            ->where('company_id', $companyId)
            ->where('action_type', $actionType)
            ->where('status', 'pending_approval')
            ->where('is_expired', false)
            ->where('expires_at', '>', now())
            ->first();
    }

    protected function expireRequest(int $requestId): void
    {
        DB::table('platform_context_approval_requests')
            ->where('id', $requestId)
            ->update([
                'status' => 'expired',
                'is_expired' => true,
                'updated_at' => now(),
            ]);
    }

    protected function isAdmin(User $user): bool
    {
        // Check if user has admin role
        return $user->is_admin ||
               $user->hasRole('admin') ||
               $user->hasRole('super_admin') ||
               $user->hasRole('compliance_officer');
    }

    protected function canCheckAction(User $checker, string $actionType): bool
    {
        // Super admins can check all actions
        if ($checker->hasRole('super_admin')) {
            return true;
        }

        // Critical actions require super_admin or compliance_officer
        $criticalActions = ['suspend_company', 'unsuspend_company', 'revoke_tier_approval'];
        if (in_array($actionType, $criticalActions)) {
            return $checker->hasRole('compliance_officer');
        }

        // Regular admins can check other actions
        return true;
    }

    protected function getUserRole(User $user): string
    {
        if ($user->hasRole('super_admin')) return 'super_admin';
        if ($user->hasRole('compliance_officer')) return 'compliance_officer';
        if ($user->hasRole('admin')) return 'admin';
        return 'unknown';
    }

    protected function captureCurrentState(Company $company): array
    {
        return [
            'lifecycle_state' => $company->lifecycle_state,
            'buying_enabled' => $company->buying_enabled ?? true,
            'is_suspended' => $company->is_suspended ?? false,
            'disclosure_freeze' => $company->disclosure_freeze ?? false,
            'under_investigation' => $company->under_investigation ?? false,
            'tier_1_approved_at' => $company->tier_1_approved_at,
            'tier_2_approved_at' => $company->tier_2_approved_at,
            'tier_3_approved_at' => $company->tier_3_approved_at,
            'captured_at' => now()->toIso8601String(),
        ];
    }

    protected function applyChanges(Company $company, string $actionType, array $changes, int $requestId): array
    {
        try {
            switch ($actionType) {
                case 'suspend_company':
                    $company->update([
                        'lifecycle_state' => 'suspended',
                        'is_suspended' => true,
                        'suspension_reason' => $changes['reason'] ?? 'Suspended via maker-checker workflow',
                        'buying_enabled' => false,
                    ]);
                    break;

                case 'unsuspend_company':
                    $company->update([
                        'lifecycle_state' => $changes['restore_to_state'] ?? 'active',
                        'is_suspended' => false,
                        'suspension_reason' => null,
                        'buying_enabled' => $changes['enable_buying'] ?? true,
                    ]);
                    break;

                case 'freeze_disclosures':
                    $company->update([
                        'disclosure_freeze' => true,
                        'freeze_reason' => $changes['reason'] ?? 'Frozen via maker-checker workflow',
                    ]);
                    break;

                case 'unfreeze_disclosures':
                    $company->update([
                        'disclosure_freeze' => false,
                        'freeze_reason' => null,
                    ]);
                    break;

                case 'start_investigation':
                    $company->update([
                        'under_investigation' => true,
                        'investigation_reason' => $changes['reason'] ?? 'Under investigation',
                    ]);
                    break;

                case 'end_investigation':
                    $company->update([
                        'under_investigation' => false,
                        'investigation_reason' => null,
                    ]);
                    break;

                case 'disable_buying':
                    $company->update(['buying_enabled' => false]);
                    break;

                case 'enable_buying':
                    $company->update(['buying_enabled' => true]);
                    break;

                case 'override_tier_approval':
                    $tier = $changes['tier'] ?? null;
                    if ($tier === 2) {
                        $company->update(['tier_2_approved_at' => now()]);
                    } elseif ($tier === 3) {
                        $company->update(['tier_3_approved_at' => now()]);
                    }
                    break;

                case 'revoke_tier_approval':
                    $tier = $changes['tier'] ?? null;
                    if ($tier === 2) {
                        $company->update(['tier_2_approved_at' => null]);
                    } elseif ($tier === 3) {
                        $company->update(['tier_3_approved_at' => null]);
                    }
                    break;

                default:
                    return ['success' => false, 'message' => "Unknown action type: {$actionType}"];
            }

            // Record in platform governance log
            DB::table('platform_governance_log')->insert([
                'company_id' => $company->id,
                'action_type' => $actionType,
                'from_state' => json_encode($this->captureCurrentState($company)),
                'to_state' => json_encode($changes),
                'decision_reason' => "Applied via maker-checker request #{$requestId}",
                'admin_user_id' => null, // Both maker and checker involved
                'is_automated' => false,
                'approval_request_id' => $requestId,
                'created_at' => now(),
            ]);

            return ['success' => true, 'message' => 'Changes applied'];

        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    protected function applySingleAdminAction(Company $company, User $admin, string $actionType, array $changes, string $reason): array
    {
        try {
            switch ($actionType) {
                case 'update_admin_notes':
                    $company->update(['admin_notes' => $changes['notes'] ?? null]);
                    break;

                case 'update_internal_tags':
                    $company->update(['internal_tags' => json_encode($changes['tags'] ?? [])]);
                    break;

                default:
                    return ['success' => false, 'approval_request_id' => null, 'message' => 'Unknown single-admin action'];
            }

            // Log even single-admin actions
            DB::table('platform_governance_log')->insert([
                'company_id' => $company->id,
                'action_type' => $actionType,
                'from_state' => null,
                'to_state' => json_encode($changes),
                'decision_reason' => $reason,
                'admin_user_id' => $admin->id,
                'is_automated' => false,
                'created_at' => now(),
            ]);

            return [
                'success' => true,
                'approval_request_id' => null,
                'message' => 'Action applied (single-admin approval)',
            ];

        } catch (\Exception $e) {
            return ['success' => false, 'approval_request_id' => null, 'message' => $e->getMessage()];
        }
    }

    protected function logMakerCheckerAction(int $requestId, string $action, User $actor, array $context): void
    {
        DB::table('platform_context_approval_logs')->insert([
            'approval_request_id' => $requestId,
            'action' => $action,
            'actor_user_id' => $actor->id,
            'actor_role' => $this->getUserRole($actor),
            'context' => json_encode($context),
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
            'created_at' => now(),
        ]);
    }

    protected function getActionTypeLabel(string $actionType): string
    {
        return match($actionType) {
            'suspend_company' => 'Company Suspended',
            'unsuspend_company' => 'Suspension Lifted',
            'freeze_disclosures' => 'Disclosures Frozen',
            'unfreeze_disclosures' => 'Disclosures Unfrozen',
            'start_investigation' => 'Investigation Started',
            'end_investigation' => 'Investigation Ended',
            'disable_buying' => 'Buying Disabled',
            'enable_buying' => 'Buying Enabled',
            'override_tier_approval' => 'Tier Approval Override',
            'revoke_tier_approval' => 'Tier Approval Revoked',
            default => ucwords(str_replace('_', ' ', $actionType)),
        };
    }

    protected function getActionCategory(string $actionType): string
    {
        $categories = [
            'suspend_company' => 'compliance',
            'unsuspend_company' => 'compliance',
            'freeze_disclosures' => 'compliance',
            'unfreeze_disclosures' => 'compliance',
            'start_investigation' => 'investigation',
            'end_investigation' => 'investigation',
            'disable_buying' => 'trading',
            'enable_buying' => 'trading',
            'override_tier_approval' => 'governance',
            'revoke_tier_approval' => 'governance',
        ];

        return $categories[$actionType] ?? 'other';
    }
}
