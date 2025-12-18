<?php

namespace App\Traits;

use App\Models\Company;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * BelongsToCompany Trait
 * * [AUDIT FIX]: Automatically enforces multi-tenant isolation.
 * * Ensures that no data is leaked between different companies.
 */
trait BelongsToCompany
{
    protected static function bootBelongsToCompany()
    {
        static::addGlobalScope('company_scope', function (Builder $builder) {
            // If a company admin is logged in, restrict all queries to their company
            if (auth()->check() && auth()->user()->is_company_admin) {
                $builder->where('company_id', auth()->user()->company_id);
            }
        });

        static::creating(function ($model) {
            if (auth()->check() && auth()->user()->is_company_admin) {
                $model->company_id = auth()->user()->company_id;
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}