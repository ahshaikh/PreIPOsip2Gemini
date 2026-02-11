<?php
/**
 * V-AUDIT-REFACTOR-2025 | V-QUERY-CACHING | V-DB-PERFORMANCE
 * Refactored to address Module 19 Audit Gaps:
 * 1. Intelligent Caching: Caches frequent public queries to minimize DB hits.
 * 2. Cache Invalidation: Automatically clears stale data on Save/Delete/Update.
 * 3. Scope Optimization: Refined 'Live' logic for better indexing performance.
 */

namespace App\Models;

use App\Enums\DisclosureTier;
use App\Exceptions\DealTierGateException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache; // [AUDIT FIX]: Import Cache facade

/**
 * @mixin IdeHelperDeal
 */
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
     * FIX 26: Added date overlap validation
     * STORY 4.1: Added tier gate enforcement
     */
    protected static function booted()
    {
        $clearCache = function () {
            Cache::forget('deals_live_list');
            Cache::forget('deals_featured_list');
        };

        // STORY 4.1: Enforce Tier Gates on Deal Operations
        // INVARIANT: Deal operations require minimum disclosure tiers
        // - Creation: tier_1_upcoming (company has submitted disclosures)
        // - Activation: tier_2_live (company disclosures approved for investment)
        // - Featured: tier_3_featured (company has premium visibility)
        static::saving(function ($deal) {
            // Skip tier gate check if no company_id (validation will fail elsewhere)
            if (!$deal->company_id) {
                return;
            }

            // Load company with disclosure_tier
            // Use withoutGlobalScopes to ensure we can load any company
            $company = \App\Models\Company::withoutGlobalScopes()
                ->find($deal->company_id);

            if (!$company) {
                return; // Company validation handled by other saving hook
            }

            $tier = $company->disclosure_tier ?? DisclosureTier::TIER_0_PENDING;

            // GATE 1: Deal creation requires tier_1_upcoming or higher
            // If this is a new deal (not exists), check creation gate
            if (!$deal->exists && $tier->rank() < DisclosureTier::TIER_1_UPCOMING->rank()) {
                throw DealTierGateException::creationRequiresTier1($company->id, $tier);
            }

            // GATE 2: Deal activation requires tier_2_live or higher
            // If status is being set to 'active', check activation gate
            if ($deal->status === 'active' && $tier->rank() < DisclosureTier::TIER_2_LIVE->rank()) {
                throw DealTierGateException::activationRequiresTier2($company->id, $tier);
            }

            // GATE 3: Deal featuring requires tier_3_featured
            // If is_featured is being set to true, check featured gate
            if ($deal->is_featured && $tier->rank() < DisclosureTier::TIER_3_FEATURED->rank()) {
                throw DealTierGateException::featuredRequiresTier3($company->id, $tier);
            }
        });

        // FIX 41: Validate product-company relationship
        static::saving(function ($deal) {
            if ($deal->product_id && $deal->company_id) {
                // Ensure product and company are properly linked
                // Since products are platform-wide, validate business logic constraints

                $product = \App\Models\Product::find($deal->product_id);
                $company = \App\Models\Company::find($deal->company_id);

                if (!$product || !$company) {
                    throw new \RuntimeException(
                        "Invalid product or company reference in deal."
                    );
                }

                // FIX 41: Validate sector consistency (if both have sectors)
                if ($product->sector && $company->sector_id) {
                    $companySector = \App\Models\Sector::find($company->sector_id);
                    if ($companySector && $product->sector !== $companySector->slug) {
                        \Log::warning('Deal created with sector mismatch', [
                            'deal_id' => $deal->id ?? 'new',
                            'product_id' => $product->id,
                            'product_sector' => $product->sector,
                            'company_id' => $company->id,
                            'company_sector' => $companySector->name,
                        ]);

                        // Optional: Enforce strict matching
                        // Uncomment to make this a hard requirement:
                        // throw new \DomainException(
                        //     "Product sector ({$product->sector}) does not match company sector ({$companySector->name}). " .
                        //     "Deals must be created for products in the same sector as the company."
                        // );
                    }
                }

                // FIX 41: Check for duplicate active deals by same company for same product
                $duplicateCheck = self::where('product_id', $deal->product_id)
                    ->where('company_id', $deal->company_id)
                    ->where('status', 'active');

                if ($deal->exists) {
                    $duplicateCheck->where('id', '!=', $deal->id);
                }

                if ($duplicateCheck->exists()) {
                    throw new \DomainException(
                        "Company '{$company->name}' already has an active deal for product '{$product->name}'. " .
                        "Please close or complete the existing deal before creating a new one."
                    );
                }
            }
        });

        // FIX 26: Validate no date overlap with existing deals for same product
        static::saving(function ($deal) {
            if ($deal->deal_opens_at && $deal->deal_closes_at && $deal->product_id) {
                $query = self::where('product_id', $deal->product_id)
                    ->where('status', 'active')
                    ->where(function ($q) use ($deal) {
                        // Check for any overlap
                        $q->whereBetween('deal_opens_at', [$deal->deal_opens_at, $deal->deal_closes_at])
                          ->orWhereBetween('deal_closes_at', [$deal->deal_opens_at, $deal->deal_closes_at])
                          ->orWhere(function ($q2) use ($deal) {
                              // Check if new deal is completely within existing deal
                              $q2->where('deal_opens_at', '<=', $deal->deal_opens_at)
                                 ->where('deal_closes_at', '>=', $deal->deal_closes_at);
                          });
                    });

                // Exclude self when updating
                if ($deal->exists) {
                    $query->where('id', '!=', $deal->id);
                }

                $overlappingDeals = $query->get();

                if ($overlappingDeals->isNotEmpty()) {
                    $overlappingTitles = $overlappingDeals->pluck('title')->implode(', ');
                    throw new \RuntimeException(
                        "Deal dates overlap with existing active deals for this product: {$overlappingTitles}. " .
                        "Please choose different dates or deactivate conflicting deals."
                    );
                }
            }
        });

        // EPIC 4 - GAP 3 FIX: Inventory Sufficiency Enforcement at Model Level
        //
        // INVARIANT: No Deal can be created or activated if inventory is insufficient.
        // This MUST be enforced at the model level, not controller level.
        //
        // WHY: Controller-only enforcement means:
        // - Direct DB inserts bypass the check
        // - Console commands bypass the check
        // - Other code paths bypass the check
        //
        // Model-level enforcement ensures the invariant holds regardless of entry path.
        static::saving(function ($deal) {
            // Skip if no product_id (validation will fail elsewhere)
            if (!$deal->product_id) {
                return;
            }

            // Load product to check inventory
            $product = \App\Models\Product::find($deal->product_id);
            if (!$product) {
                return; // Product validation handled elsewhere
            }

            // Calculate available inventory value from BulkPurchases
            $availableInventory = $product->bulkPurchases()
                ->where('value_remaining', '>', 0)
                ->sum('value_remaining');

            // GATE 1: New deal creation requires inventory to exist
            // This prevents deals from being created without backing inventory
            if (!$deal->exists) {
                if ($availableInventory <= 0) {
                    throw new \DomainException(
                        "Cannot create deal: No inventory available for product '{$product->name}' (ID: {$product->id}). " .
                        "Create a BulkPurchase with inventory first. " .
                        "INVARIANT: Deals must have backing inventory."
                    );
                }
            }

            // GATE 2: Deal activation requires sufficient inventory
            // This prevents deals from going active without sufficient inventory
            if ($deal->status === 'active') {
                if ($availableInventory <= 0) {
                    throw new \DomainException(
                        "Cannot activate deal: No inventory available for product '{$product->name}' (ID: {$product->id}). " .
                        "Available: ₹0.00. " .
                        "INVARIANT: Active deals must have available inventory."
                    );
                }

                // GATE 2a: If max_investment is set, it cannot exceed available inventory
                if ($deal->max_investment && $deal->max_investment > $availableInventory) {
                    throw new \DomainException(
                        "Cannot activate deal: max_investment (₹" . number_format($deal->max_investment, 2) . ") " .
                        "exceeds available inventory (₹" . number_format($availableInventory, 2) . "). " .
                        "Either reduce max_investment or add more inventory. " .
                        "INVARIANT: Deal cannot promise more than inventory can fulfill."
                    );
                }
            }

            // Log inventory check for audit trail
            \Illuminate\Support\Facades\Log::debug('Deal inventory sufficiency check passed', [
                'deal_id' => $deal->id ?? 'new',
                'product_id' => $deal->product_id,
                'status' => $deal->status,
                'available_inventory' => $availableInventory,
                'max_investment' => $deal->max_investment,
            ]);
        });

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

    /**
     * [P1 FIX]: Get UserInvestments for this deal.
     *
     * Traverses: Deal → Product → BulkPurchase → UserInvestment
     *
     * BEFORE (BROKEN):
     * return $this->hasMany(Investment::class);
     * // Investment model is deprecated, never written to
     *
     * AFTER (FIXED):
     * Queries UserInvestment through product's bulk purchases
     *
     * WHY: Investment model was deprecated in favor of UserInvestment.
     * AllocationService only creates UserInvestment records.
     * This method now queries the actual allocation records.
     */
    public function investments()
    {
        return UserInvestment::query()
            ->whereIn('bulk_purchase_id', function($query) {
                $query->select('id')
                    ->from('bulk_purchases')
                    ->where('product_id', $this->product_id);
            })
            ->where('is_reversed', false);
    }

    /**
     * [P1 FIX]: Get count of investments for this deal.
     */
    public function investmentsCount(): int
    {
        return $this->investments()->count();
    }

    /**
     * [P1 FIX]: Get total value invested in this deal.
     */
    public function totalInvestedAmount(): float
    {
        return $this->investments()->sum('value_allocated') ?? 0;
    }

    /**
     * [P1 FIX]: Get count of unique investors in this deal.
     */
    public function uniqueInvestorsCount(): int
    {
        return $this->investments()->distinct('user_id')->count('user_id');
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

    /**
     * FIX 49: Deal approvals relationship
     */
    public function approvals()
    {
        return $this->hasMany(DealApproval::class)->orderBy('created_at', 'desc');
    }

    /**
     * FIX 49: Get current approval record
     */
    public function currentApproval()
    {
        return $this->hasOne(DealApproval::class)->latest();
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