<?php

namespace App\Policies;

use App\Models\{CompanyShareListing, User};

/**
 * FIX 9 (P2): CompanyShareListing Authorization Policy
 *
 * Provides resource-level authorization for share listings
 * Integrates with FIX 5 (P1) company freeze workflow
 */
class ShareListingPolicy
{
    /**
     * Determine if user can view any share listings
     */
    public function viewAny(User $user): bool
    {
        // Admins can view all listings
        if ($user->can('products.view')) {
            return true;
        }

        // Company users can view their own company's listings
        if ($user->hasRole('company')) {
            return $user->companyUser !== null;
        }

        return false;
    }

    /**
     * Determine if user can view a specific share listing
     */
    public function view(User $user, CompanyShareListing $listing): bool
    {
        // Check if user is a company user
        if ($user->hasRole('company')) {
            $companyUser = $user->companyUser;
            if ($companyUser) {
                return $listing->company_id === $companyUser->company_id;
            }
            return false;
        }

        // Admin can view all
        return $user->can('products.view');
    }

    /**
     * Determine if user can create share listings
     */
    public function create(User $user): bool
    {
        // Company users can create listings if company is verified
        if ($user->hasRole('company')) {
            $companyUser = $user->companyUser;
            if ($companyUser) {
                return $companyUser->company->is_verified &&
                       $companyUser->status === 'active';
            }
            return false;
        }

        // Admins can also create listings
        return $user->can('products.create');
    }

    /**
     * Determine if user can update a share listing
     */
    public function update(User $user, CompanyShareListing $listing): bool
    {
        // Cannot edit approved or rejected listings
        if (in_array($listing->status, ['approved', 'rejected'])) {
            return false;
        }

        // Company users can edit own listings if pending or under_review
        if ($user->hasRole('company')) {
            $companyUser = $user->companyUser;
            if ($companyUser) {
                return $listing->company_id === $companyUser->company_id &&
                       in_array($listing->status, ['pending', 'under_review']);
            }
            return false;
        }

        // Admin can edit any listing
        return $user->can('products.edit');
    }

    /**
     * Determine if user can delete a share listing
     */
    public function delete(User $user, CompanyShareListing $listing): bool
    {
        // Cannot delete approved listings (linked to BulkPurchase)
        if ($listing->status === 'approved' && $listing->bulk_purchase_id) {
            return false;
        }

        // Company users can delete own draft/pending listings
        if ($user->hasRole('company')) {
            $companyUser = $user->companyUser;
            if ($companyUser) {
                return $listing->company_id === $companyUser->company_id &&
                       in_array($listing->status, ['pending', 'draft']);
            }
            return false;
        }

        // Admin can delete non-approved listings
        return $user->can('products.delete') && $listing->status !== 'approved';
    }

    /**
     * Determine if user can approve a share listing
     * FIX 5 (P1) Note: Approval will freeze company data
     */
    public function approve(User $user, CompanyShareListing $listing): bool
    {
        return $user->can('products.edit') &&
               in_array($listing->status, ['pending', 'under_review']);
    }

    /**
     * Determine if user can reject a share listing
     */
    public function reject(User $user, CompanyShareListing $listing): bool
    {
        return $user->can('products.edit') &&
               in_array($listing->status, ['pending', 'under_review']);
    }

    /**
     * Determine if user can start review of a share listing
     */
    public function startReview(User $user, CompanyShareListing $listing): bool
    {
        return $user->can('products.view') && $listing->status === 'pending';
    }
}
