<?php

namespace App\Policies;

use App\Models\{BulkPurchase, User};

/**
 * FIX 9 (P2): BulkPurchase Authorization Policy
 *
 * Provides resource-level authorization for inventory purchases
 * Integrates with FIX 2 (P0) immutability enforcement
 */
class BulkPurchasePolicy
{
    /**
     * Determine if user can view any bulk purchases
     */
    public function viewAny(User $user): bool
    {
        // Only admins can view inventory
        return $user->can('products.view');
    }

    /**
     * Determine if user can view a specific bulk purchase
     */
    public function view(User $user, BulkPurchase $bulkPurchase): bool
    {
        // Check if user is a company user
        if ($user->hasRole('company')) {
            $companyUser = $user->companyUser;
            if ($companyUser) {
                // Company can view their own inventory
                return $bulkPurchase->company_id === $companyUser->company_id;
            }
            return false;
        }

        // Admin can view all
        return $user->can('products.view');
    }

    /**
     * Determine if user can create bulk purchases
     */
    public function create(User $user): bool
    {
        // Only admins can create inventory purchases
        // Company users submit share listings which admins approve to create BulkPurchase
        return $user->can('products.create');
    }

    /**
     * Determine if user can update a bulk purchase
     * FIX 2 (P0) Note: Observer enforces immutability of financial fields
     */
    public function update(User $user, BulkPurchase $bulkPurchase): bool
    {
        // Only admins can update
        // FIX 2 (P0): Observer will prevent modifications to immutable fields
        return $user->can('products.edit');
    }

    /**
     * Determine if user can delete a bulk purchase
     */
    public function delete(User $user, BulkPurchase $bulkPurchase): bool
    {
        // Check if any allocations exist
        if ($bulkPurchase->userInvestments()->where('is_reversed', false)->exists()) {
            // Cannot delete bulk purchase with active allocations
            return false;
        }

        // Only super-admin can delete inventory (financial audit requirement)
        return $user->hasRole('super-admin');
    }

    /**
     * Determine if user can restore a soft-deleted bulk purchase
     */
    public function restore(User $user, BulkPurchase $bulkPurchase): bool
    {
        return $user->hasRole('super-admin');
    }

    /**
     * Determine if user can permanently delete a bulk purchase
     */
    public function forceDelete(User $user, BulkPurchase $bulkPurchase): bool
    {
        // Financial records must never be permanently deleted (regulatory requirement)
        return false;
    }
}
