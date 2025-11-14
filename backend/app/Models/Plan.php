<?php
// V-FINAL-1730-331 (Upgraded Logic)

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Plan extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'monthly_amount',
        'duration_months',
        'description',
        'is_active',
        'is_featured',
        'display_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'monthly_amount' => 'decimal:2',
    ];

    /**
     * Boot logic to enforce data integrity.
     */
    protected static function booted()
    {
        static::saving(function ($plan) {
            if ($plan->monthly_amount < 0) {
                throw new \InvalidArgumentException("Monthly amount cannot be negative.");
            }
            if ($plan->duration_months < 1) {
                throw new \InvalidArgumentException("Duration must be at least 1 month.");
            }
        });
    }

    // --- RELATIONSHIPS ---

    public function configs(): HasMany
    {
        return $this->hasMany(PlanConfig::class);
    }

    public function features(): HasMany
    {
        return $this->hasMany(PlanFeature::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    // --- SCOPES ---

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    // --- ACCESSORS & HELPERS ---

    /**
     * Calculate total investment required for this plan.
     */
    protected function totalInvestment(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->monthly_amount * $this->duration_months
        );
    }

    /**
     * Retrieve a specific config value (e.g., 'progressive_rate').
     */
    public function getConfig(string $key, $default = null)
    {
        // Use the relation if loaded to avoid N+1 queries
        $config = $this->relationLoaded('configs')
            ? $this->configs->firstWhere('config_key', $key)
            : $this->configs()->where('config_key', $key)->first();

        return $config ? $config->value : $default;
    }

    /**
     * Archive (deactivate) the plan.
     */
    public function archive(): void
    {
        $this->update(['is_active' => false]);
    }
}