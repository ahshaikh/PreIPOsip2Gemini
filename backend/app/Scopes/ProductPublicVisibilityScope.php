<?php
/**
 * STORY 3.1 GAP 2: Product Public Visibility Global Scope
 *
 * GOVERNANCE INVARIANT:
 * Public-facing queries CANNOT return products from companies with disclosure_tier < tier_2_live.
 *
 * Uses same context detection as PublicVisibilityScope.
 */

namespace App\Scopes;

use App\Enums\DisclosureTier;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class ProductPublicVisibilityScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        if (PublicVisibilityScope::shouldEnforcePublicVisibility()) {
            $builder->whereHas('company', function (Builder $query) {
                $query->whereIn('disclosure_tier', [
                    DisclosureTier::TIER_2_LIVE->value,
                    DisclosureTier::TIER_3_FEATURED->value,
                ]);
            });
        }
    }
}
