<?php
// V-PHASE2-1730-040 (Created) | V-FINAL-1730-411 (Logic Upgraded) | V-FINAL-1730-497 (Price Fields Added) | V-FINAL-1730-504 (Company Info Relations) | V-FINAL-1730-509 (Risk Relation Added) | V-FINAL-1730-513 (Compliance Fields)

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
        'eligibility_mode', // Plan eligibility control
        'is_featured',
        'display_order',
        'description',
        // FSD-PROD-012: Compliance Fields
        'sebi_approval_number',
        'sebi_approval_date',
        'compliance_notes',
        'regulatory_warnings',
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

    /**
     * Plans that can access this product (many-to-many).
     */
    public function plans()
    {
        return $this->belongsToMany(Plan::class, 'plan_products')
                    ->withPivot([
                        'discount_percentage',
                        'min_investment_override',
                        'max_investment_override',
                        'is_featured',
                        'priority'
                    ])
                    ->withTimestamps();
    }

    /**
     * Check if a plan can access this product.
     */
    public function isAccessibleByPlan(Plan $plan): bool
    {
        if ($this->eligibility_mode === 'all_plans') {
            return true;
        }

        return $this->plans()->where('plan_id', $plan->id)->exists();
    }
    
    // --- ACCESSORS ---
    protected function totalAllocated(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->investments()->sum('value_allocated')
        );
    }
}