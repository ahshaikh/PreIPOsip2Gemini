<?php

namespace App\Policies;

use App\Models\{Deal, User};

/**
 * FIX 9 (P2): Deal Authorization Policy
 *
 * Provides resource-level authorization for deals
 * Replaces manual permission checks in controllers
 */
class DealPolicy
{
    /**
     * Determine if user can view any deals
     */
    public function viewAny(User $user): bool
    {
        return true; // Public deals visible to all authenticated users
    }

    /**
     * Determine if user can view a specific deal
     */
    public function view(User $user, Deal $deal): bool
    {
        // Check if user is a company user
        if ($user->hasRole('company')) {
            // Company users can only view their own company's deals
            $companyUser = $user->companyUser; // Assuming relationship exists
            if ($companyUser) {
                return $deal->company_id === $companyUser->company_id;
            }
            return false;
        }

        // Admins and regular users can view all deals
        return true;
    }

    /**
     * Determine if user can create deals
     */
    public function create(User $user): bool
    {
        // Check if user is a company user
        if ($user->hasRole('company')) {
            $companyUser = $user->companyUser;
            if ($companyUser) {
                return $companyUser->company->is_verified && $companyUser->status === 'active';
            }
            return false;
        }

        // Admin permission check
        return $user->can('products.create');
    }

    /**
     * Determine if user can update a deal
     */
    public function update(User $user, Deal $deal): bool
    {
        // Company users can only edit own deals, and only if draft
        if ($user->hasRole('company')) {
            $companyUser = $user->companyUser;
            if ($companyUser) {
                return $deal->company_id === $companyUser->company_id && $deal->status === 'draft';
            }
            return false;
        }

        // Admin can edit any deal
        return $user->can('products.edit');
    }

    /**
     * Determine if user can delete a deal
     */
    public function delete(User $user, Deal $deal): bool
    {
        // Company users can only delete own draft deals
        if ($user->hasRole('company')) {
            $companyUser = $user->companyUser;
            if ($companyUser) {
                return $deal->company_id === $companyUser->company_id && $deal->status === 'draft';
            }
            return false;
        }

        // Admin can delete any deal
        return $user->can('products.delete');
    }

    /**
     * Determine if user can approve a deal
     * FIX 6 (P1) integration - approval workflow
     */
    public function approve(User $user, Deal $deal): bool
    {
        return $user->can('products.edit') && $deal->status === 'draft';
    }

    /**
     * Determine if user can reject a deal
     * FIX 6 (P1) integration - approval workflow
     */
    public function reject(User $user, Deal $deal): bool
    {
        return $user->can('products.edit') && $deal->status === 'draft';
    }
}
