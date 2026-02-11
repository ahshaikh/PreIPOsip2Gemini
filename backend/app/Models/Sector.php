<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * @mixin IdeHelperSector
 */
class Sector extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'slug', 'description', 'icon', 'color',
        'companies_count', 'deals_count', 'sort_order', 'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'companies_count' => 'integer',
        'deals_count' => 'integer'
    ];

    protected static function booted()
    {
        static::creating(function ($sector) {
            if (empty($sector->slug)) {
                $sector->slug = Str::slug($sector->name);
            }
        });

        // FIX 27: Prevent deletion if in use by companies, deals, or products
        static::deleting(function ($sector) {
            $companiesCount = $sector->companies()->count();
            $dealsCount = $sector->deals()->count();
            $productsCount = $sector->products()->count();

            if ($companiesCount > 0 || $dealsCount > 0 || $productsCount > 0) {
                $details = [];
                if ($companiesCount > 0) $details[] = "{$companiesCount} companies";
                if ($dealsCount > 0) $details[] = "{$dealsCount} deals";
                if ($productsCount > 0) $details[] = "{$productsCount} products";

                throw new \RuntimeException(
                    "Cannot delete sector '{$sector->name}': Currently in use by " . implode(', ', $details) . "."
                );
            }

            \Log::info('Sector deleted', [
                'sector_id' => $sector->id,
                'sector_name' => $sector->name,
                'deleted_by' => auth()->id(),
            ]);
        });
    }

    public function companies(): HasMany
    {
        return $this->hasMany(Company::class);
    }

    public function deals(): HasMany
    {
        return $this->hasMany(Deal::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
