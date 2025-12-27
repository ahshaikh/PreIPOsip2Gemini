<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Offer extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'subtitle',
        'code',
        'scope', // NEW: global, products, deals, plans
        'description',
        'long_description',
        'status',
        'discount_type', // percentage, fixed_amount
        'discount_percent',
        'discount_amount',
        'min_investment',
        'max_discount',
        'usage_limit',
        'usage_count',
        'user_usage_limit',
        'auto_apply', // NEW: Auto-apply to eligible transactions
        'eligible_user_segments', // NEW: User segments
        'expiry',
        'image_url',
        'hero_image',
        'video_url',
        'features',
        'terms',
        'is_featured',
    ];

    protected $casts = [
        'discount_percent' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'min_investment' => 'decimal:2',
        'max_discount' => 'decimal:2',
        'usage_limit' => 'integer',
        'usage_count' => 'integer',
        'user_usage_limit' => 'integer',
        'auto_apply' => 'boolean',
        'eligible_user_segments' => 'array',
        'expiry' => 'datetime',
        'features' => 'array',
        'terms' => 'array',
        'is_featured' => 'boolean',
    ];

    // --- RELATIONSHIPS ---

    /**
     * Products this offer applies to.
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'offer_products')
            ->withPivot(['custom_discount_percent', 'custom_discount_amount', 'is_featured', 'priority'])
            ->withTimestamps()
            ->orderByPivot('priority', 'desc');
    }

    /**
     * Deals this offer applies to.
     */
    public function deals(): BelongsToMany
    {
        return $this->belongsToMany(Deal::class, 'offer_deals')
            ->withPivot(['custom_discount_percent', 'custom_discount_amount',
                         'min_investment_override', 'is_featured', 'priority'])
            ->withTimestamps()
            ->orderByPivot('priority', 'desc');
    }

    /**
     * Plans eligible for this offer.
     */
    public function plans(): BelongsToMany
    {
        return $this->belongsToMany(Plan::class, 'offer_plans')
            ->withPivot(['additional_discount_percent', 'is_exclusive', 'priority'])
            ->withTimestamps()
            ->orderByPivot('priority', 'desc');
    }

    /**
     * Usage history for this offer.
     */
    public function usages(): HasMany
    {
        return $this->hasMany(OfferUsage::class);
    }

    /**
     * Statistics for this offer.
     */
    public function statistics(): HasMany
    {
        return $this->hasMany(OfferStatistic::class);
    }

    /**
     * Scope for active offers
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('expiry')
                  ->orWhere('expiry', '>=', now());
            });
    }

    /**
     * Scope for featured offers
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Check if offer is valid
     */
    public function isValid(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        if ($this->expiry && $this->expiry->isPast()) {
            return false;
        }

        if ($this->usage_limit && $this->usage_count >= $this->usage_limit) {
            return false;
        }

        return true;
    }

    /**
     * Increment usage count
     */
    public function incrementUsage(): void
    {
        $this->increment('usage_count');
    }

    // --- CAMPAIGN INTEGRATION HELPERS ---

    /**
     * Check if offer is applicable to a specific product.
     */
    public function isApplicableToProduct(Product $product): bool
    {
        if ($this->scope === 'global') {
            return true;
        }

        if ($this->scope === 'products') {
            return $this->products()->where('product_id', $product->id)->exists();
        }

        return false;
    }

    /**
     * Check if offer is applicable to a specific deal.
     */
    public function isApplicableToDeal(Deal $deal): bool
    {
        if ($this->scope === 'global') {
            return true;
        }

        if ($this->scope === 'deals') {
            return $this->deals()->where('deal_id', $deal->id)->exists();
        }

        // Check if deal's product is eligible
        if ($this->scope === 'products' && $deal->product_id) {
            return $this->products()->where('product_id', $deal->product_id)->exists();
        }

        return false;
    }

    /**
     * Check if offer is applicable to a user's plan.
     */
    public function isApplicableToPlan(?Plan $plan): bool
    {
        if ($this->scope === 'global') {
            return true;
        }

        if ($this->scope === 'plans' && $plan) {
            return $this->plans()->where('plan_id', $plan->id)->exists();
        }

        return false;
    }

    /**
     * Calculate discount for a given amount.
     *
     * @param float $amount Investment amount
     * @param Product|null $product Optional product for custom discount
     * @param Deal|null $deal Optional deal for custom discount
     * @return array ['discount_amount', 'final_amount', 'discount_type']
     */
    public function calculateDiscount(float $amount, ?Product $product = null, ?Deal $deal = null): array
    {
        // Get custom discount if product/deal specific
        $discountPercent = $this->discount_percent;
        $discountAmount = $this->discount_amount;

        if ($product && $this->scope === 'products') {
            $pivot = $this->products()->where('product_id', $product->id)->first()?->pivot;
            if ($pivot) {
                $discountPercent = $pivot->custom_discount_percent ?? $discountPercent;
                $discountAmount = $pivot->custom_discount_amount ?? $discountAmount;
            }
        } elseif ($deal && $this->scope === 'deals') {
            $pivot = $this->deals()->where('deal_id', $deal->id)->first()?->pivot;
            if ($pivot) {
                $discountPercent = $pivot->custom_discount_percent ?? $discountPercent;
                $discountAmount = $pivot->custom_discount_amount ?? $discountAmount;
            }
        }

        // Calculate discount
        $calculatedDiscount = 0;
        if ($this->discount_type === 'percentage' && $discountPercent) {
            $calculatedDiscount = ($amount * $discountPercent) / 100;
        } elseif ($this->discount_type === 'fixed_amount' && $discountAmount) {
            $calculatedDiscount = $discountAmount;
        }

        // Apply max discount cap
        if ($this->max_discount && $calculatedDiscount > $this->max_discount) {
            $calculatedDiscount = $this->max_discount;
        }

        // Ensure discount doesn't exceed amount
        if ($calculatedDiscount > $amount) {
            $calculatedDiscount = $amount;
        }

        return [
            'discount_amount' => round($calculatedDiscount, 2),
            'final_amount' => round($amount - $calculatedDiscount, 2),
            'discount_type' => $this->discount_type,
            'discount_percent' => $discountPercent,
            'discount_fixed' => $discountAmount,
        ];
    }

    /**
     * Check if user has exceeded usage limit for this offer.
     */
    public function hasUserExceededLimit(int $userId): bool
    {
        if (!$this->user_usage_limit) {
            return false;
        }

        $userUsageCount = $this->usages()->where('user_id', $userId)->count();
        return $userUsageCount >= $this->user_usage_limit;
    }

    /**
     * Record offer usage.
     */
    public function recordUsage(int $userId, float $discountApplied, float $investmentAmount, array $meta = []): void
    {
        OfferUsage::create([
            'offer_id' => $this->id,
            'user_id' => $userId,
            'investment_id' => $meta['investment_id'] ?? null,
            'product_id' => $meta['product_id'] ?? null,
            'deal_id' => $meta['deal_id'] ?? null,
            'discount_applied' => $discountApplied,
            'investment_amount' => $investmentAmount,
            'code_used' => $this->code,
            'used_at' => now(),
        ]);

        $this->incrementUsage();
    }
}
