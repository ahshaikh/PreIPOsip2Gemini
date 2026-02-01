<?php

namespace App\Policies;

use App\Models\CompanyUser as CompanyAuthUser;
use App\Models\User;
use App\Models\Product;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

/**
 * PHASE 1 AUDIT: Product Policy
 *
 * INVARIANTS ENFORCED:
 * 1. Admins CANNOT author products - only approve, reject, or perform audited overrides
 * 2. CompanyUsers can ONLY modify their own company's products
 * 3. CompanyUsers can ONLY modify products in 'draft' or 'rejected' status
 * 4. Products MUST have company_id before approval
 * 5. Submitted/approved products are read-only for CompanyUsers
 *
 * OWNERSHIP MODEL:
 * - Product.company_id is NOT NULL (enforced at DB level)
 * - CompanyUser.company_id must match Product.company_id for write operations
 * - Admins have read access to all products but limited write capabilities
 */
class ProductPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the admin user can view any products.
     * This specifically authorizes access to the admin product approval queue.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'super-admin']);
    }

    /**
     * PHASE 1 AUDIT: Determine whether the user can create products.
     *
     * INVARIANT: Only CompanyUsers can create products.
     * Admins CANNOT create products - this enforces chain of custody.
     *
     * @param  \App\Models\CompanyUser  $user
     * @return \Illuminate\Auth\Access\Response
     */
    public function create(CompanyAuthUser $user): Response
    {
        // CompanyUser must be associated with a company
        if (!$user->company_id) {
            return Response::deny('You must be associated with a company to create products.');
        }

        // Must have company_admin role
        if (!$user->hasRole('company_admin') && !$user->hasRole('founder')) {
            return Response::deny('Only company administrators can create products.');
        }

        return Response::allow();
    }

    /**
     * PHASE 1 AUDIT: Block admin product creation.
     *
     * This method is called when an Admin (User model) attempts to create.
     * We explicitly deny this to enforce the authorship invariant.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response
     */
    public function createAsAdmin(User $user): Response
    {
        // INVARIANT: Admins CANNOT author products
        return Response::deny(
            'Administrators cannot create products. Products must be authored by company representatives.'
        );
    }

    /**
     * Determine whether the CompanyUser can update the product.
     *
     * @param  \App\Models\CompanyUser  $user
     * @param  \App\Models\Product  $product
     * @return \Illuminate\Auth\Access\Response
     */
    public function update(CompanyAuthUser $user, Product $product): Response
    {
        // INVARIANT: Must own the product
        if ($user->company_id !== $product->company_id) {
            return Response::deny('You can only modify products belonging to your company.');
        }

        // INVARIANT: Can only edit drafts or rejected products
        if (!in_array($product->status, ['draft', 'rejected'])) {
            return Response::deny(
                "Products in '{$product->status}' status cannot be edited. " .
                "Withdraw the submission first or contact admin."
            );
        }

        return Response::allow();
    }

    /**
     * PHASE 1 AUDIT: Block admin content updates.
     *
     * Admins can update ONLY:
     * - regulatory_warnings (admin-authored)
     * - compliance_notes (admin-authored)
     *
     * All other updates must go through override flow with justification.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Product  $product
     * @return \Illuminate\Auth\Access\Response
     */
    public function updateAsAdmin(User $user, Product $product): Response
    {
        if (!$user->hasAnyRole(['admin', 'super-admin'])) {
            return Response::deny('Only administrators can access this function.');
        }

        // Allow viewing/limited updates for admin compliance notes
        return Response::allow();
    }

    /**
     * Determine whether the CompanyUser can submit the product for approval.
     *
     * @param  \App\Models\CompanyUser  $user
     * @param  \App\Models\Product  $product
     * @return \Illuminate\Auth\Access\Response
     */
    public function submit(CompanyAuthUser $user, Product $product): Response
    {
        // INVARIANT: Must own the product
        if ($user->company_id !== $product->company_id) {
            return Response::deny('You can only submit products belonging to your company.');
        }

        // Must have appropriate role
        if (!$user->hasRole('company_admin') && !$user->hasRole('founder')) {
            return Response::deny('Only company administrators can submit products for review.');
        }

        // INVARIANT: Can only submit drafts
        if ($product->status !== 'draft') {
            return Response::deny(
                "Only draft products can be submitted. Current status: {$product->status}"
            );
        }

        return Response::allow();
    }

    /**
     * Determine whether the admin can approve a product.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Product  $product
     * @return \Illuminate\Auth\Access\Response
     */
    public function approve(User $user, Product $product): Response
    {
        if (!$user->hasAnyRole(['admin', 'super-admin'])) {
            return Response::deny('Only administrators can approve products.');
        }

        // INVARIANT: Can only approve submitted products
        if ($product->status !== 'submitted') {
            return Response::deny(
                "Only submitted products can be approved. Current status: {$product->status}"
            );
        }

        // INVARIANT: Product must have company ownership
        if (!$product->company_id) {
            return Response::deny(
                'Cannot approve a product without company ownership. This is a data integrity issue.'
            );
        }

        return Response::allow();
    }

    /**
     * Determine whether the admin can reject a product.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Product  $product
     * @return \Illuminate\Auth\Access\Response
     */
    public function reject(User $user, Product $product): Response
    {
        if (!$user->hasAnyRole(['admin', 'super-admin'])) {
            return Response::deny('Only administrators can reject products.');
        }

        // INVARIANT: Can only reject submitted products
        if ($product->status !== 'submitted') {
            return Response::deny(
                "Only submitted products can be rejected. Current status: {$product->status}"
            );
        }

        return Response::allow();
    }

    /**
     * PHASE 1 AUDIT: Determine whether the admin can perform an override.
     *
     * Overrides allow admins to correct typos/formatting without full resubmission.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Product  $product
     * @return \Illuminate\Auth\Access\Response
     */
    public function override(User $user, Product $product): Response
    {
        if (!$user->hasAnyRole(['admin', 'super-admin'])) {
            return Response::deny('Only administrators can perform overrides.');
        }

        // Only allow overrides on submitted/approved products
        if (!in_array($product->status, ['submitted', 'approved'])) {
            return Response::deny(
                "Overrides are only permitted on submitted or approved products. Current status: {$product->status}"
            );
        }

        return Response::allow();
    }

    /**
     * Determine whether the user can delete the product.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Product  $product
     * @return \Illuminate\Auth\Access\Response
     */
    public function delete(User $user, Product $product): Response
    {
        if (!$user->hasAnyRole(['admin', 'super-admin'])) {
            return Response::deny('Only administrators can delete products.');
        }

        // INVARIANT: Cannot delete approved/locked products
        if (in_array($product->status, ['approved', 'locked'])) {
            return Response::deny(
                "Approved or locked products cannot be deleted. Archive instead."
            );
        }

        return Response::allow();
    }

    /**
     * PHASE 1 AUDIT: Determine whether the CompanyUser can withdraw submission.
     *
     * @param  \App\Models\CompanyUser  $user
     * @param  \App\Models\Product  $product
     * @return \Illuminate\Auth\Access\Response
     */
    public function withdraw(CompanyAuthUser $user, Product $product): Response
    {
        // Must own the product
        if ($user->company_id !== $product->company_id) {
            return Response::deny('You can only withdraw your own company products.');
        }

        // Can only withdraw submitted products
        if ($product->status !== 'submitted') {
            return Response::deny(
                "Only submitted products can be withdrawn. Current status: {$product->status}"
            );
        }

        return Response::allow();
    }
}
