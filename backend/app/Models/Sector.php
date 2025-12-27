<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

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

        // Prevent deletion if in use
        static::deleting(function ($sector) {
            if ($sector->companies()->exists() || $sector->deals()->exists()) {
                throw new \Exception('Cannot delete sector that is in use by companies or deals.');
            }
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
