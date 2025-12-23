<?php
/**
 * V-AUDIT-REFACTOR-2025 | V-QUERY-CACHING | V-DB-PERFORMANCE
 * Refactored to address Module 19 Audit Gaps:
 * 1. Intelligent Caching: Caches frequent public queries to minimize DB hits.
 * 2. Cache Invalidation: Automatically clears stale data on Save/Delete/Update.
 * 3. Scope Optimization: Refined 'Live' logic for better indexing performance.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache; // [AUDIT FIX]: Import Cache facade

class Deal extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'product_id', 'title', 'slug', 'description', 'company_name',
        'company_logo', 'sector', 'deal_type', 'min_investment',
        'max_investment', 'valuation', 'valuation_currency',
        'share_price', 'total_shares', 'available_shares',
        'deal_opens_at', 'deal_closes_at', 'days_remaining',
        'highlights', 'documents', 'video_url', 'status',
        'is_featured', 'sort_order',
    ];

    protected $casts = [
        'min_investment' => 'decimal:2',
        'max_investment' => 'decimal:2',
        'valuation' => 'decimal:2',
        'share_price' => 'decimal:2',
        'deal_opens_at' => 'datetime',
        'deal_closes_at' => 'datetime',
        'highlights' => 'array',
        'documents' => 'array',
        'is_featured' => 'boolean',
    ];

    /**
     * [AUDIT FIX]: Automatic Cache Invalidation.
     * When any deal is modified, we must clear the cached lists
     * to prevent investors from seeing outdated availability or pricing.
     */
    protected static function booted()
    {
        $clearCache = function () {
            Cache::forget('deals_live_list');
            Cache::forget('deals_featured_list');
        };

        static::saved($clearCache);
        static::deleted($clearCache);
    }

    // --- CACHED DATA ACCESSORS ---

    /**
     * Get live deals from cache.
     * [AUDIT FIX]: Prevents repeated heavy date-math queries on every page load.
     */
    public static function getCachedLive()
    {
        return Cache::remember('deals_live_list', 3600, function () {
            return self::live()->orderBy('sort_order', 'asc')->get();
        });
    }

    // --- RELATIONSHIPS & SCOPES ---

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function investments()
    {
        return $this->hasMany(Investment::class);
    }

    public function scopeLive($query)
    {
        return $query->where('deal_type', 'live')
                    ->where('status', 'active')
                    ->where('deal_opens_at', '<=', now())
                    ->where(function($q) {
                        $q->whereNull('deal_closes_at')
                          ->orWhere('deal_closes_at', '>', now());
                    });
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true)
                    ->where('status', 'active');
    }

    // --- ACCESSORS ---

    /**
     * Calculate remaining shares available for investment
     */
    public function getRemainingSharesAttribute()
    {
        $allocated = $this->investments()
            ->whereIn('status', ['active', 'pending'])
            ->sum('shares_allocated');

        return max(0, $this->available_shares - $allocated);
    }

    /**
     * Check if deal is available for new investments
     */
    public function getIsAvailableAttribute()
    {
        return $this->remaining_shares > 0 &&
               $this->status === 'active' &&
               $this->deal_opens_at <= now() &&
               ($this->deal_closes_at === null || $this->deal_closes_at > now());
    }
}