<?php

namespace App\Policies;

use App\Models\CompanyUser;

use App\Models\Product;

use Illuminate\Auth\Access\HandlesAuthorization;



class ProductPolicy

{

    use HandlesAuthorization;



    /**

     * Determine whether the user can update the model.

     *

     * @param  \App\Models\CompanyUser  $user

     * @param  \App\Models\Product  $product

     * @return \Illuminate\Auth\Access\Response|bool

     */

    public function update(CompanyUser $user, Product $product)

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

    public function submit(CompanyUser $user, Product $product)

    {

        return $user->company_id === $product->company_id

            && $user->hasRole('company_admin')

            && $product->status === 'draft';

    }

}
