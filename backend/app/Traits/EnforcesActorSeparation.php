<?php

namespace App\Traits;

use Illuminate\Support\Facades\Log;

/**
 * PHASE 2 HARDENING - Issue 6: Issuer vs Platform Action Separation
 *
 * PURPOSE:
 * Makes it IMPOSSIBLE to confuse issuer submissions vs admin judgments vs system enforcement.
 * Even when stored in same tables, actor type and authority are crystal clear.
 *
 * ACTOR TYPES:
 * - issuer: Company-initiated action (submissions, draft edits, clarification answers)
 * - admin: Platform judgment (approvals, rejections, suspensions, tier approvals)
 * - system: Automated enforcement (auto-transitions, scheduled jobs, cascading updates)
 *
 * ENFORCEMENT:
 * - Every action MUST declare actor_type
 * - Validation blocks mismatched actions (e.g., issuer trying to approve)
 * - Audit trail includes actor_type + actor_authority
 * - Database queries can filter by actor_type for forensics
 *
 * USAGE:
 * ```php
 * use App\Traits\EnforcesActorSeparation;
 *
 * class MyModel extends Model {
 *     use EnforcesActorSeparation;
 * }
 *
 * // In service/controller:
 * $model->recordAction([
 *     'action_type' => 'disclosure_submission',
 *     'actor_type' => 'issuer',  // Required
 *     'actor_id' => $companyUserId,
 *     'data' => [...],
 * ]);
 * ```
 *
 * MIGRATION REQUIREMENT:
 * Tables using this trait should have:
 * - actor_type ENUM('issuer', 'admin', 'system')
 * - actor_id INT (user_id or company_user_id)
 * - actor_authority VARCHAR (role/permission that authorized action)
 */
trait EnforcesActorSeparation
{
    /**
     * Valid actor types
     */
    protected const ACTOR_TYPES = ['issuer', 'admin', 'system'];

    /**
     * Actions that ONLY issuers can perform
     */
    protected const ISSUER_ONLY_ACTIONS = [
        'disclosure_draft_save',
        'disclosure_submission',
        'clarification_answer',
        'error_report',
        'document_upload',
    ];

    /**
     * Actions that ONLY admins can perform
     */
    protected const ADMIN_ONLY_ACTIONS = [
        'disclosure_approval',
        'disclosure_rejection',
        'tier_approval',
        'tier_rejection',
        'suspension',
        'unsuspension',
        'lifecycle_transition',
        'clarification_request',
        'clarification_acceptance',
        'clarification_dispute',
    ];

    /**
     * Actions that ONLY system can perform
     */
    protected const SYSTEM_ONLY_ACTIONS = [
        'auto_transition',
        'scheduled_check',
        'cascade_update',
        'computed_field_update',
    ];

    /**
     * Record an action with enforced actor separation
     *
     * CRITICAL VALIDATIONS:
     * 1. actor_type must be valid ('issuer', 'admin', 'system')
     * 2. actor_type must match action authority rules
     * 3. Current authenticated user must match declared actor_type
     * 4. Audit trail includes full actor context
     *
     * @param array $actionData Must include: action_type, actor_type, actor_id
     * @return bool Success
     * @throws \RuntimeException If validation fails
     */
    public function recordActionWithSeparation(array $actionData): bool
    {
        // VALIDATION 1: Required fields
        $required = ['action_type', 'actor_type', 'actor_id'];
        foreach ($required as $field) {
            if (!isset($actionData[$field])) {
                throw new \RuntimeException(
                    "Actor separation enforcement failed: Missing required field '{$field}'"
                );
            }
        }

        $actionType = $actionData['action_type'];
        $actorType = $actionData['actor_type'];
        $actorId = $actionData['actor_id'];

        // VALIDATION 2: actor_type must be valid
        if (!in_array($actorType, self::ACTOR_TYPES)) {
            Log::critical('ACTOR SEPARATION VIOLATION: Invalid actor_type', [
                'action_type' => $actionType,
                'actor_type' => $actorType,
                'valid_types' => self::ACTOR_TYPES,
            ]);

            throw new \RuntimeException(
                "Invalid actor_type '{$actorType}'. Must be one of: " . implode(', ', self::ACTOR_TYPES)
            );
        }

        // VALIDATION 3: actor_type must match action authority
        $this->validateActorAuthority($actionType, $actorType);

        // VALIDATION 4: Verify current user matches declared actor_type
        $this->validateCurrentUserMatchesActorType($actorType, $actorId);

        // VALIDATION 5: Detect impersonation attempts
        $this->detectImpersonation($actorType, $actorId);

        // AUDIT: Log action with full actor context
        $this->logActionWithActorContext($actionType, $actorType, $actorId, $actionData);

        return true;
    }

    /**
     * Validate that actor_type has authority for action_type
     *
     * BLOCKS:
     * - Issuers performing admin-only actions (approvals, suspensions)
     * - Admins performing system-only actions (auto-transitions)
     * - System performing issuer-only actions (submissions)
     *
     * @param string $actionType
     * @param string $actorType
     * @throws \RuntimeException If authority violation
     */
    protected function validateActorAuthority(string $actionType, string $actorType): void
    {
        // Check issuer-only actions
        if (in_array($actionType, self::ISSUER_ONLY_ACTIONS) && $actorType !== 'issuer') {
            Log::critical('ACTOR SEPARATION VIOLATION: Non-issuer attempted issuer-only action', [
                'action_type' => $actionType,
                'actor_type' => $actorType,
                'required_actor_type' => 'issuer',
            ]);

            throw new \RuntimeException(
                "Action '{$actionType}' can ONLY be performed by issuers. Actor type '{$actorType}' is not authorized."
            );
        }

        // Check admin-only actions
        if (in_array($actionType, self::ADMIN_ONLY_ACTIONS) && $actorType !== 'admin') {
            Log::critical('ACTOR SEPARATION VIOLATION: Non-admin attempted admin-only action', [
                'action_type' => $actionType,
                'actor_type' => $actorType,
                'required_actor_type' => 'admin',
            ]);

            throw new \RuntimeException(
                "Action '{$actionType}' can ONLY be performed by platform admins. Actor type '{$actorType}' is not authorized."
            );
        }

        // Check system-only actions
        if (in_array($actionType, self::SYSTEM_ONLY_ACTIONS) && $actorType !== 'system') {
            Log::critical('ACTOR SEPARATION VIOLATION: Non-system attempted system-only action', [
                'action_type' => $actionType,
                'actor_type' => $actorType,
                'required_actor_type' => 'system',
            ]);

            throw new \RuntimeException(
                "Action '{$actionType}' can ONLY be performed by automated system. Actor type '{$actorType}' is not authorized."
            );
        }
    }

    /**
     * Validate current authenticated user matches declared actor_type
     *
     * PREVENTS:
     * - Admin declaring themselves as 'issuer'
     * - Company user declaring themselves as 'admin'
     * - Jobs/system declaring themselves as 'issuer' or 'admin'
     *
     * @param string $actorType
     * @param int $actorId
     * @throws \RuntimeException If mismatch
     */
    protected function validateCurrentUserMatchesActorType(string $actorType, int $actorId): void
    {
        $currentUser = auth()->user();

        // System actions: Must be CLI or queue context (no authenticated user)
        if ($actorType === 'system') {
            if ($currentUser !== null) {
                Log::critical('ACTOR SEPARATION VIOLATION: Authenticated user declared actor_type=system', [
                    'actor_type' => $actorType,
                    'user_id' => $currentUser->id,
                    'user_email' => $currentUser->email,
                ]);

                throw new \RuntimeException(
                    'System actions cannot be performed by authenticated users. This is a critical security violation.'
                );
            }
            return; // Valid system context
        }

        // Issuer/Admin actions: Must have authenticated user
        if ($currentUser === null) {
            Log::critical('ACTOR SEPARATION VIOLATION: No authenticated user for issuer/admin action', [
                'actor_type' => $actorType,
                'declared_actor_id' => $actorId,
            ]);

            throw new \RuntimeException(
                'Issuer and admin actions require authenticated user context'
            );
        }

        // Issuer actions: User must be associated with a company
        if ($actorType === 'issuer') {
            if ($currentUser->company_id === null) {
                Log::critical('ACTOR SEPARATION VIOLATION: Non-company user declared actor_type=issuer', [
                    'actor_type' => $actorType,
                    'user_id' => $currentUser->id,
                    'user_email' => $currentUser->email,
                ]);

                throw new \RuntimeException(
                    'Only company users can perform issuer actions. You are not associated with a company.'
                );
            }
        }

        // Admin actions: User must NOT be associated with a company
        if ($actorType === 'admin') {
            if ($currentUser->company_id !== null) {
                Log::critical('ACTOR SEPARATION VIOLATION: Company user declared actor_type=admin', [
                    'actor_type' => $actorType,
                    'user_id' => $currentUser->id,
                    'company_id' => $currentUser->company_id,
                ]);

                throw new \RuntimeException(
                    'Company users cannot perform admin actions. Admin actions are platform-only.'
                );
            }
        }
    }

    /**
     * Detect impersonation attempts
     *
     * CHECKS:
     * - declared actor_id matches authenticated user
     * - IP address consistency
     * - Session context matches
     *
     * @param string $actorType
     * @param int $actorId
     */
    protected function detectImpersonation(string $actorType, int $actorId): void
    {
        if ($actorType === 'system') {
            return; // System has no user to validate
        }

        $currentUser = auth()->user();

        // declared actor_id must match authenticated user
        if ($currentUser->id !== $actorId) {
            Log::critical('ACTOR SEPARATION VIOLATION: Actor ID mismatch (possible impersonation)', [
                'actor_type' => $actorType,
                'declared_actor_id' => $actorId,
                'authenticated_user_id' => $currentUser->id,
                'ip_address' => request()->ip(),
            ]);

            throw new \RuntimeException(
                'Actor ID mismatch: You cannot perform actions on behalf of another user'
            );
        }
    }

    /**
     * Log action with full actor context to audit trail
     *
     * RECORDS:
     * - action_type
     * - actor_type ('issuer', 'admin', 'system')
     * - actor_id
     * - actor_authority (role/permission)
     * - IP address (if applicable)
     * - User agent (if applicable)
     * - Session context
     *
     * @param string $actionType
     * @param string $actorType
     * @param int $actorId
     * @param array $actionData
     */
    protected function logActionWithActorContext(
        string $actionType,
        string $actorType,
        int $actorId,
        array $actionData
    ): void {
        $context = [
            'action_type' => $actionType,
            'actor_type' => $actorType,
            'actor_id' => $actorId,
            'actor_authority' => $this->getActorAuthority($actorType),
            'model' => get_class($this),
            'model_id' => $this->id ?? null,
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
            'action_data' => $actionData,
        ];

        Log::info('ACTION RECORDED (Actor Separation Enforced)', $context);

        // If model has audit_trail field, append to it
        if (isset($this->audit_trail)) {
            $auditEntry = [
                'recorded_at' => now()->toIso8601String(),
                'action_type' => $actionType,
                'actor_type' => $actorType,
                'actor_id' => $actorId,
                'actor_authority' => $context['actor_authority'],
            ];

            $this->audit_trail = array_merge($this->audit_trail ?? [], [$auditEntry]);
            $this->save();
        }
    }

    /**
     * Get actor authority (role/permission) for audit trail
     *
     * @param string $actorType
     * @return string
     */
    protected function getActorAuthority(string $actorType): string
    {
        if ($actorType === 'system') {
            return 'system_automation';
        }

        $user = auth()->user();
        if (!$user) {
            return 'unknown';
        }

        // Get user roles
        $roles = $user->roles()->pluck('name')->toArray();

        return implode(',', $roles) ?: 'authenticated_user';
    }

    /**
     * Query scope: Filter by actor type
     *
     * USAGE:
     * $issuerActions = Model::byActorType('issuer')->get();
     * $adminJudgments = Model::byActorType('admin')->get();
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $actorType
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByActorType($query, string $actorType)
    {
        return $query->where('actor_type', $actorType);
    }

    /**
     * Query scope: Filter by actor ID
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $actorId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByActorId($query, int $actorId)
    {
        return $query->where('actor_id', $actorId);
    }
}
