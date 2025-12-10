<?php
// V-PHASE2-1730-040 (Created) | V-FINAL-1730-411 (Logic Upgraded) | V-FINAL-1730-497 (Price Fields Added) | V-FINAL-1730-504 (Company Info Relations) | V-FINAL-1730-509 (Risk Relation Added) | V-FINAL-1730-513 (Compliance Fields) | V-PRODUCT-EXTENDED-1210 (Media, Docs, News, Allocation)

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'sector',
        'face_value_per_unit',
        'current_market_price',
        'last_price_update',
        'auto_update_price',
        'price_api_endpoint',
        'min_investment',
        'expected_ipo_date',
        'status',
        'is_featured',
        'display_order',
        'description',
        // FSD-PROD-012: Compliance Fields
        'sebi_approval_number',
        'sebi_approval_date',
        'compliance_notes',
        'regulatory_warnings',
        // V-PRODUCT-ALLOCATION-1210: Allocation Fields
        'allocation_method',
        'allocation_rules',
        'max_allocation_per_user',
        'total_units_available',
        'units_allocated',
        'enable_waitlist',
    ];

    protected $casts = [
        'is_featured' => 'boolean',
        'face_value_per_unit' => 'decimal:2',
        'current_market_price' => 'decimal:2',
        'min_investment' => 'decimal:2',
        'expected_ipo_date' => 'date',
        'description' => 'array',
        'auto_update_price' => 'boolean',
        'last_price_update' => 'datetime',
        // FSD-PROD-012: Compliance Casts
        'sebi_approval_date' => 'date',
        // V-PRODUCT-ALLOCATION-1210: Allocation Casts
        'allocation_rules' => 'array',
        'max_allocation_per_user' => 'decimal:2',
        'total_units_available' => 'decimal:2',
        'units_allocated' => 'decimal:2',
        'enable_waitlist' => 'boolean',
    ];

    /**
     * Boot logic to enforce validation.
     */
    protected static function booted()
    {
        static::saving(function ($product) {
            if ($product->face_value_per_unit <= 0) {
                throw new \InvalidArgumentException("Face value must be positive.");
            }
        });
    }

    // --- RELATIONSHIPS ---
    public function bulkPurchases(): HasMany { return $this->hasMany(BulkPurchase::class); }
    public function investments(): HasMany { return $this->hasMany(UserInvestment::class); }
    public function priceHistory(): HasMany { return $this->hasMany(ProductPriceHistory::class)->orderBy('recorded_at', 'asc'); }
    public function highlights(): HasMany { return $this->hasMany(ProductHighlight::class)->orderBy('display_order'); }
    public function founders(): HasMany { return $this->hasMany(ProductFounder::class)->orderBy('display_order'); }
    public function fundingRounds(): HasMany { return $this->hasMany(ProductFundingRound::class)->orderBy('date'); }
    public function keyMetrics(): HasMany { return $this->hasMany(ProductKeyMetric::class); }
    public function riskDisclosures(): HasMany { return $this->hasMany(ProductRiskDisclosure::class)->orderBy('display_order'); }
    // V-PRODUCT-EXTENDED-1210: New Relationships
    public function media(): HasMany { return $this->hasMany(ProductMedia::class)->orderBy('display_order'); }
    public function documents(): HasMany { return $this->hasMany(ProductDocument::class)->orderBy('display_order'); }
    public function news(): HasMany { return $this->hasMany(ProductNews::class)->orderBy('published_date', 'desc'); }
    
    // --- ACCESSORS ---
    protected function totalAllocated(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->investments()->sum('value_allocated')
        );
    }
}