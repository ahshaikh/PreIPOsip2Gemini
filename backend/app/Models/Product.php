<?php
// V-PHASE2-1730-040 (Created) | V-FINAL-1730-411 (Logic Upgraded) | V-FINAL-1730-497 (Price Fields Added) | V-FINAL-1730-504 (Company Info Relations) | V-FINAL-1730-509 (Risk Relation Added) | V-FINAL-1730-513 (Compliance Fields)

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use App\Traits\HasDeletionProtection;
use App\Traits\HasWorkflowActions;

class Product extends Model
{
    use HasFactory, SoftDeletes, HasDeletionProtection, HasWorkflowActions;

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
     * Deletion protection rules.
     * Prevents deletion if product has active dependencies.
     */
    protected $deletionProtectionRules = [
        'investments' => 'active investments',
        'bulkPurchases' => 'bulk purchase inventory',
        'deals' => 'active deals',
    ];

    /**
     * Boot logic to enforce validation.
     * FIX 20: Added inventory validation before activation
     */
    protected static function booted()
    {
        static::saving(function ($product) {
            if ($product->face_value_per_unit <= 0) {
                throw new \InvalidArgumentException("Face value must be positive.");
            }

            // FIX 20: Prevent activation without inventory
            if ($product->isDirty('status') && $product->status === 'active') {
                $hasInventory = $product->bulkPurchases()
                    ->where('value_remaining', '>', 0)
                    ->exists();

                if (!$hasInventory) {
                    throw new \RuntimeException(
                        "Cannot activate product '{$product->name}': No available inventory. " .
                        "Please add bulk purchase inventory before activating this product."
                    );
                }

                \Log::info('Product activated with inventory check', [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'total_inventory' => $product->bulkPurchases()->sum('value_remaining'),
                ]);
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
     * [PROTOCOL-1 ENFORCEMENT]: Renamed from offers() to campaigns()
     *
     * Campaigns applicable to this product.
     *
     * WHY: Method name must match domain model to prevent semantic drift.
     * Preserving "offers()" allows future developers to infer "Offer" still exists,
     * increasing chance of re-introducing parallel promotion primitives.
     *
     * INVARIANT: Campaign is the sole promotional construct.
     *
     * [P0.2 FIX]: Uses Campaign model (not Offer).
     * Pivot table renamed: offer_products → campaign_products
     */
    public function campaigns()
    {
        return $this->belongsToMany(Campaign::class, 'campaign_products')
                    ->withPivot([
                        'custom_discount_percent',
                        'custom_discount_amount',
                        'is_featured',
                        'priority'
                    ])
                    ->withTimestamps()
                    ->orderByPivot('priority', 'desc');
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

    /**
     * Get active campaigns for this product.
     */
    public function getActiveOffers()
    {
        return $this->offers()
                    ->active()
                    ->get();
    }

    // --- ACCESSORS ---
    protected function totalAllocated(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->investments()->sum('value_allocated')
        );
    }

    // --- WORKFLOW ACTIONS ---

    /**
     * Get workflow actions for this product.
     */
    public function getWorkflowActions(): array
    {
        $hasInventory = $this->bulkPurchases()->where('value_remaining', '>', 0)->exists();
        $hasDeals = $this->deals()->where('status', 'active')->exists();
        $investmentCount = $this->investments()->count();

        return [
            [
                'key' => 'add_inventory',
                'label' => 'Add Inventory',
                'action' => "/admin/bulk-purchases/create?product_id={$this->id}",
                'type' => 'primary',
                'icon' => 'shopping-cart',
                'condition' => true,
                'suggested' => !$hasInventory,
                'description' => 'Purchase shares for this product',
            ],
            [
                'key' => 'create_deal',
                'label' => 'Create Deal',
                'action' => "/admin/deals/create?product_id={$this->id}",
                'type' => 'success',
                'icon' => 'plus-circle',
                'condition' => $hasInventory,
                'suggested' => $hasInventory && !$hasDeals,
                'description' => 'Launch investment deal for this product',
            ],
            [
                'key' => 'link_plans',
                'label' => 'Link to Plans',
                'action' => "/admin/products/{$this->id}/plans",
                'type' => 'secondary',
                'icon' => 'link',
                'condition' => true,
                'suggested' => $this->eligibility_mode === 'all_plans',
                'description' => 'Configure plan-based access',
            ],
            [
                'key' => 'create_campaign',
                'label' => 'Create Campaign',
                'action' => "/admin/offers/create?product_id={$this->id}",
                'type' => 'warning',
                'icon' => 'tag',
                'condition' => $hasDeals,
                'suggested' => $investmentCount > 0 && !$this->offers()->active()->exists(),
                'description' => 'Launch promotional campaign',
            ],
            [
                'key' => 'view_analytics',
                'label' => 'View Analytics',
                'action' => "/admin/products/{$this->id}/analytics",
                'type' => 'secondary',
                'icon' => 'chart-bar',
                'condition' => $investmentCount > 0,
                'suggested' => false,
                'description' => 'View performance metrics',
            ],
        ];
    }

    protected function getCurrentState(): string
    {
        $hasInventory = $this->bulkPurchases()->where('value_remaining', '>', 0)->exists();
        $hasDeals = $this->deals()->where('status', 'active')->exists();

        if (!$hasInventory) {
            return 'no_inventory';
        } elseif (!$hasDeals) {
            return 'inventory_available';
        } else {
            return 'active_deals';
        }
    }

    protected function getBlockingIssues(): array
    {
        $issues = [];

        if (!$this->bulkPurchases()->exists()) {
            $issues[] = [
                'severity' => 'high',
                'message' => 'No inventory available. Add bulk purchase first.',
                'action' => 'add_inventory',
            ];
        }

        if ($this->face_value_per_unit <= 0) {
            $issues[] = [
                'severity' => 'critical',
                'message' => 'Invalid face value. Must be greater than zero.',
                'action' => 'edit',
            ];
        }

        return $issues;
    }
}