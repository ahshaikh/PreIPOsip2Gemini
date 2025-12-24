<?php
// V-PHASE2-1730-037 (Created) | V-FINAL-1730-331 | V-FINAL-1730-482 (Scheduling Added)

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
        'available_from',
        'available_until',
        'max_subscriptions_per_user',
        'allow_pause',
        'max_pause_count',
        'max_pause_duration_months',
        'min_investment',
        'max_investment',
        'billing_cycle',
        'trial_period_days',
        'metadata',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'monthly_amount' => 'decimal:2',
        'available_from' => 'datetime',
        'available_until' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Append accessor attributes to JSON serialization
     */
    protected $appends = ['subscribers_count'];

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

    /**
     * NEW: Scope for public-facing plan lists.
     * FSD-PLAN-010: Enforce availability dates.
     */
    public function scopePubliclyAvailable(Builder $query): void
    {
        $now = now();
        $query->where('is_active', true)
              // 1. Either available_from is null OR it's in the past
              ->where(function ($q) use ($now) {
                  $q->whereNull('available_from')->orWhere('available_from', '<=', $now);
              })
              // 2. Either available_until is null OR it's in the future
              ->where(function ($q) use ($now) {
                  $q->whereNull('available_until')->orWhere('available_until', '>=', $now);
              });
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
     * Alias for subscriptions_count (created by withCount).
     * Provides backwards compatibility for frontend.
     */
    public function getSubscribersCountAttribute()
    {
        // Check if withCount was used (creates dynamic property)
        if (isset($this->subscriptions_count)) {
            return $this->subscriptions_count;
        }
        // Otherwise try to get from attributes array
        return $this->attributes['subscriptions_count'] ?? 0;
    }

    /**
     * Retrieve a specific config value (e.g., 'progressive_rate').
     */
    public function getConfig(string $key, $default = null)
    {
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