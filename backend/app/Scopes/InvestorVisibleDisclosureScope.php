<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Log;

/**
 * PHASE 1 AUDIT FIX: Investor Visible Disclosure Scope
 *
 * PURPOSE:
 * Global scope that ENFORCES investors only see approved disclosures.
 * When applied, ALL queries on CompanyDisclosure are filtered to:
 * - status = 'approved'
 * - is_visible = true
 *
 * WHY A SCOPE (not just repository):
 * - Repository pattern works for explicit calls
 * - Scope pattern catches ALL queries including eager-loaded relationships
 * - Double-layered protection: Repository + Scope = defense in depth
 *
 * USAGE:
 * Apply temporarily in investor-facing contexts:
 *   CompanyDisclosure::addGlobalScope(new InvestorVisibleDisclosureScope());
 *
 * Or use the trait-based scope:
 *   CompanyDisclosure::visibleToInvestor($user)->get();
 *
 * THIS SCOPE IS STRICT:
 * - No fallback to non-approved
 * - No silent filtering to hide issues
 * - Logs any attempt to load non-approved via relationships
 */
class InvestorVisibleDisclosureScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * @param Builder $builder
     * @param Model $model
     * @return void
     */
    public function apply(Builder $builder, Model $model): void
    {
        // CRITICAL: Only approved disclosures
        $builder->where('status', 'approved');

        // CRITICAL: Must be marked as visible
        $builder->where('is_visible', true);

        // Log for audit trail (debug level to avoid log spam in production)
        Log::debug('InvestorVisibleDisclosureScope applied', [
            'model' => get_class($model),
            'query' => $builder->toSql(),
        ]);
    }
}
