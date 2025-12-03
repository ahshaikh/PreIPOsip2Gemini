<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Offer extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'subtitle',
        'code',
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
        'expiry' => 'datetime',
        'features' => 'array',
        'terms' => 'array',
        'is_featured' => 'boolean',
    ];

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
}
