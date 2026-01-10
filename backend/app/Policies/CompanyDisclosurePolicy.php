<?php

namespace App\Policies;

use App\Models\Company;
use App\Models\CompanyDisclosure;
use App\Models\CompanyUserRole;
use App\Models\DisclosureClarification;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * PHASE 3 - POLICY: CompanyDisclosurePolicy
 *
 * PURPOSE:
 * Role-based access control for company disclosure operations.
 *
 * ENFORCEMENT:
 * - Founder: Full access to all disclosures
 * - Finance: Access to financial disclosures (Tier 2)
 * - Legal: Access to legal/compliance disclosures
 * - Viewer: Read-only access
 *
 * SAFEGUARDS:
 * - Cannot edit approved disclosures (use reportError instead)
 * - Cannot edit disclosures from modules outside role scope
 * - All denials logged for audit
 */
class CompanyDisclosurePolicy
{
    /**
     * Get user's active role for company
     *
     * @param User $user
     * @param Company $company
     * @return CompanyUserRole|null
     */
    protected function getUserRole(User $user, Company $company): ?CompanyUserRole
    {
        return CompanyUserRole::where('user_id', $user->id)
            ->where('company_id', $company->id)
            ->where('is_active', true)
            ->whereNull('revoked_at')
            ->first();
    }

    /**
     * Determine if user can view disclosure
     *
     * @param User $user
     * @param CompanyDisclosure $disclosure
     * @return Response
     */
    public function view(User $user, CompanyDisclosure $disclosure): Response
    {
        $role = $this->getUserRole($user, $disclosure->company);

        if (!$role) {
            \Log::warning('Disclosure view denied - no role', [
                'user_id' => $user->id,
                'company_id' => $disclosure->company_id,
                'disclosure_id' => $disclosure->id,
            ]);

            return Response::deny('You do not have access to this company');
        }

        // All roles can view disclosures
        return Response::allow();
    }

    /**
     * Determine if user can create disclosure
     *
     * @param User $user
     * @param Company $company
     * @return Response
     */
    public function create(User $user, Company $company): Response
    {
        $role = $this->getUserRole($user, $company);

        if (!$role) {
            return Response::deny('You do not have access to this company');
        }

        if (!$role->canEdit()) {
            \Log::warning('Disclosure create denied - insufficient role', [
                'user_id' => $user->id,
                'company_id' => $company->id,
                'role' => $role->role,
            ]);

            return Response::deny('Viewers cannot create disclosures. Contact your company founder.');
        }

        return Response::allow();
    }

    /**
     * Determine if user can update disclosure
     *
     * CRITICAL SAFEGUARD:
     * - Cannot update approved disclosures (must use reportError)
     * - Must have role access to disclosure module
     * - Must have edit permission
     *
     * @param User $user
     * @param CompanyDisclosure $disclosure
     * @return Response
     */
    public function update(User $user, CompanyDisclosure $disclosure): Response
    {
        $role = $this->getUserRole($user, $disclosure->company);

        if (!$role) {
            return Response::deny('You do not have access to this company');
        }

        // SAFEGUARD: Cannot edit approved disclosures
        if ($disclosure->status === 'approved') {
            \Log::warning('Disclosure edit denied - approved', [
                'user_id' => $user->id,
                'disclosure_id' => $disclosure->id,
                'status' => $disclosure->status,
            ]);

            return Response::deny(
                'Cannot edit approved disclosure. Use "Report Error" to submit corrections.'
            );
        }

        // SAFEGUARD: Cannot edit locked disclosures
        if ($disclosure->is_locked) {
            return Response::deny('Cannot edit locked disclosure');
        }

        // Check if role can edit disclosures
        if (!$role->canEdit()) {
            \Log::warning('Disclosure edit denied - viewer role', [
                'user_id' => $user->id,
                'disclosure_id' => $disclosure->id,
                'role' => $role->role,
            ]);

            return Response::deny('Viewers cannot edit disclosures');
        }

        // Check if role has access to this module
        if (!$role->canAccessModule($disclosure->module)) {
            \Log::warning('Disclosure edit denied - module access', [
                'user_id' => $user->id,
                'disclosure_id' => $disclosure->id,
                'module_code' => $disclosure->module->code,
                'role' => $role->role,
            ]);

            return Response::deny(
                "Your {$role->getRoleDisplayName()} role cannot edit {$disclosure->module->name}. " .
                "Contact your company founder if you need access."
            );
        }

        // Check if disclosure is in editable state
        if (!in_array($disclosure->status, ['draft', 'rejected', 'clarification_required'])) {
            return Response::deny(
                "Cannot edit disclosure in '{$disclosure->status}' status"
            );
        }

        return Response::allow();
    }

    /**
     * Determine if user can submit disclosure for review
     *
     * @param User $user
     * @param CompanyDisclosure $disclosure
     * @return Response
     */
    public function submit(User $user, CompanyDisclosure $disclosure): Response
    {
        $role = $this->getUserRole($user, $disclosure->company);

        if (!$role) {
            return Response::deny('You do not have access to this company');
        }

        if (!$role->canSubmit()) {
            \Log::warning('Disclosure submit denied - viewer role', [
                'user_id' => $user->id,
                'disclosure_id' => $disclosure->id,
                'role' => $role->role,
            ]);

            return Response::deny('Viewers cannot submit disclosures for review');
        }

        if (!$role->canAccessModule($disclosure->module)) {
            return Response::deny(
                "Your {$role->getRoleDisplayName()} role cannot submit {$disclosure->module->name}"
            );
        }

        if ($disclosure->status !== 'draft') {
            return Response::deny("Can only submit disclosures in 'draft' status");
        }

        if ($disclosure->completion_percentage < 100) {
            return Response::deny(
                "Disclosure is only {$disclosure->completion_percentage}% complete. " .
                "Complete all required fields before submitting."
            );
        }

        return Response::allow();
    }

    /**
     * Determine if user can answer clarification
     *
     * @param User $user
     * @param DisclosureClarification $clarification
     * @return Response
     */
    public function answerClarification(User $user, DisclosureClarification $clarification): Response
    {
        $role = $this->getUserRole($user, $clarification->company);

        if (!$role) {
            return Response::deny('You do not have access to this company');
        }

        if (!$role->canEdit()) {
            return Response::deny('Viewers cannot answer clarifications');
        }

        if (!$role->canAccessModule($clarification->disclosure->module)) {
            return Response::deny(
                "Your {$role->getRoleDisplayName()} role cannot answer clarifications for {$clarification->disclosure->module->name}"
            );
        }

        if (!in_array($clarification->status, ['open', 'disputed'])) {
            return Response::deny("Clarification is in '{$clarification->status}' status and cannot be answered");
        }

        return Response::allow();
    }

    /**
     * Determine if user can report error in approved disclosure
     *
     * @param User $user
     * @param CompanyDisclosure $disclosure
     * @return Response
     */
    public function reportError(User $user, CompanyDisclosure $disclosure): Response
    {
        $role = $this->getUserRole($user, $disclosure->company);

        if (!$role) {
            return Response::deny('You do not have access to this company');
        }

        if (!$role->canEdit()) {
            return Response::deny('Viewers cannot report errors');
        }

        if ($disclosure->status !== 'approved') {
            return Response::deny('Can only report errors in approved disclosures');
        }

        if (!$role->canAccessModule($disclosure->module)) {
            return Response::deny(
                "Your {$role->getRoleDisplayName()} role cannot report errors for {$disclosure->module->name}"
            );
        }

        return Response::allow();
    }

    /**
     * Determine if user can attach documents to disclosure
     *
     * @param User $user
     * @param CompanyDisclosure $disclosure
     * @return Response
     */
    public function attachDocuments(User $user, CompanyDisclosure $disclosure): Response
    {
        $role = $this->getUserRole($user, $disclosure->company);

        if (!$role) {
            return Response::deny('You do not have access to this company');
        }

        if (!$role->canEdit()) {
            return Response::deny('Viewers cannot attach documents');
        }

        if ($disclosure->is_locked) {
            return Response::deny('Cannot attach documents to locked disclosure');
        }

        if (!$role->canAccessModule($disclosure->module)) {
            return Response::deny(
                "Your {$role->getRoleDisplayName()} role cannot attach documents to {$disclosure->module->name}"
            );
        }

        return Response::allow();
    }

    /**
     * Determine if user can delete disclosure
     *
     * SAFEGUARD: Only drafts can be deleted, and only by founder
     *
     * @param User $user
     * @param CompanyDisclosure $disclosure
     * @return Response
     */
    public function delete(User $user, CompanyDisclosure $disclosure): Response
    {
        $role = $this->getUserRole($user, $disclosure->company);

        if (!$role) {
            return Response::deny('You do not have access to this company');
        }

        // Only founder can delete
        if ($role->role !== CompanyUserRole::ROLE_FOUNDER) {
            \Log::warning('Disclosure delete denied - not founder', [
                'user_id' => $user->id,
                'disclosure_id' => $disclosure->id,
                'role' => $role->role,
            ]);

            return Response::deny('Only company founders can delete disclosures');
        }

        // Can only delete drafts
        if ($disclosure->status !== 'draft') {
            return Response::deny("Cannot delete disclosure in '{$disclosure->status}' status. Only drafts can be deleted.");
        }

        return Response::allow();
    }

    /**
     * Determine if user can manage company users/roles
     *
     * @param User $user
     * @param Company $company
     * @return Response
     */
    public function manageUsers(User $user, Company $company): Response
    {
        $role = $this->getUserRole($user, $company);

        if (!$role) {
            return Response::deny('You do not have access to this company');
        }

        if (!$role->canManageUsers()) {
            \Log::warning('User management denied - not founder', [
                'user_id' => $user->id,
                'company_id' => $company->id,
                'role' => $role->role,
            ]);

            return Response::deny('Only company founders can manage users');
        }

        return Response::allow();
    }
}
