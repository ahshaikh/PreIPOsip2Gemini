<?php

namespace App\Policies;

use App\Models\CompanyUser as CompanyAuthUser;
use App\Models\User;
use App\Models\Product;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProductPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     * This specifically authorizes access to the admin product approval queue.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function viewAny(User $user)
    {
        return $user->hasAnyRole(['admin', 'super-admin']);
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\CompanyUser  $user
     * @param  \App\Models\Product  $product
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(CompanyAuthUser $user, Product $product)
    {
        return $user->company_id === $product->company_id && $product->status === 'draft';
    }

    /**
     * Determine whether the user can submit the model for approval.
     *
     * @param  \App\Models\CompanyUser  $user
     * @param  \App\Models\Product  $product
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function submit(CompanyAuthUser $user, Product $product)
    {
        return $user->company_id === $product->company_id
            && $user->hasRole('company_admin')
            && $product->status === 'draft';
    }

    /**
     * Determine whether the admin can approve a product.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Product  $product
     * @return bool
     */
    public function approve(User $user, Product $product): bool
    {
        return $user->hasAnyRole(['admin', 'super-admin']) && $product->status === 'submitted';
    }

    /**
     * Determine whether the admin can reject a product.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Product  $product
     * @return bool
     */
    public function reject(User $user, Product $product): bool
    {
        return $user->hasAnyRole(['admin', 'super-admin']) && $product->status === 'submitted';
    }
}

