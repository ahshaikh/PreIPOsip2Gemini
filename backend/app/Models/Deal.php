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
        'product_id', 'company_id', 'title', 'slug', 'description',
        'sector', 'deal_type', 'min_investment',
        'max_investment', 'valuation', 'valuation_currency',
        'share_price', 'deal_opens_at', 'deal_closes_at', 'days_remaining',
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

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function investments()
    {
        return $this->hasMany(Investment::class);
    }

    /**
     * [PROTOCOL-1 ENFORCEMENT]: Renamed from offers() to campaigns()
     *
     * Campaigns applicable to this deal.
     *
     * WHY: Method name must match domain model to prevent semantic drift.
     * Preserving "offers()" allows future developers to infer "Offer" still exists,
     * increasing chance of re-introducing parallel promotion primitives.
     *
     * INVARIANT: Campaign is the sole promotional construct.
     *
     * [P0.2 FIX]: Uses Campaign model (not Offer).
     * Pivot table renamed: offer_deals → campaign_deals
     */
    public function campaigns()
    {
        return $this->belongsToMany(Campaign::class, 'campaign_deals')
                    ->withPivot([
                        'custom_discount_percent',
                        'custom_discount_amount',
                        'min_investment_override',
                        'is_featured',
                        'priority'
                    ])
                    ->withTimestamps()
                    ->orderByPivot('priority', 'desc');
    }

    /**
     * [PROTOCOL-1 ENFORCEMENT]: Renamed from getActiveOffers() to getActiveCampaigns()
     *
     * Get active campaigns for this deal.
     */
    public function getActiveCampaigns()
    {
        return $this->campaigns()
                    ->active()
                    ->get();
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

    public function scopeUpcoming($query)
    {
        return $query->where('deal_type', 'upcoming')
                    ->where('status', 'active');
    }

    // --- ACCESSORS ---

    /**
     * Calculate total shares from BulkPurchase inventory.
     * Single source of truth: BulkPurchase.total_value_received
     */
    public function getTotalSharesAttribute()
    {
        if (!$this->product_id || !$this->share_price || $this->share_price == 0) {
            return 0;
        }

        $totalValue = $this->product->bulkPurchases()->sum('total_value_received');
        return floor($totalValue / $this->share_price);
    }

    /**
     * Calculate available shares from BulkPurchase inventory.
     * Single source of truth: BulkPurchase.value_remaining
     *
     * WHY THIS CANNOT DIVERGE:
     * - AllocationService (line 102) updates BulkPurchase.value_remaining
     * - This accessor reads BulkPurchase.value_remaining directly
     * - No stored field to become stale
     * - Calculation happens at query time
     */
    public function getAvailableSharesAttribute()
    {
        if (!$this->product_id || !$this->share_price || $this->share_price == 0) {
            return 0;
        }

        $availableValue = $this->product->bulkPurchases()->sum('value_remaining');
        return floor($availableValue / $this->share_price);
    }

    /**
     * Calculate remaining shares available for investment.
     *
     * CRITICAL: This is now an ALIAS to available_shares
     * Previously it calculated from stored field + Investment allocations
     * Now it reads directly from BulkPurchase (single source of truth)
     */
    public function getRemainingSharesAttribute()
    {
        return $this->available_shares;
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