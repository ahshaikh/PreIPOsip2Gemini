<?php
// V-PHASE2-1730-038 (Created) | V-FINAL-1730-411 (Logic Upgraded)

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes; // <-- IMPORT
use Illuminate\Database\Eloquent\Casts\Attribute;

class Product extends Model
{
    use HasFactory, SoftDeletes; // <-- USE TRAIT

    protected $fillable = [
        'name',
        'slug',
        'sector',
        'face_value_per_unit',
        'current_market_price',
        'min_investment',
        'expected_ipo_date',
        'status',
        'is_featured',
        'display_order',
        'description',
    ];

    protected $casts = [
        'is_featured' => 'boolean',
        'face_value_per_unit' => 'decimal:2',
        'current_market_price' => 'decimal:2',
        'min_investment' => 'decimal:2',
        'expected_ipo_date' => 'date',
        'description' => 'array',
    ];

    /**
     * Boot logic to enforce validation.
     */
    protected static function booted()
    {
        static::saving(function ($product) {
            // Test: test_product_validates_face_value_positive
            if ($product->face_value_per_unit <= 0) {
                throw new \InvalidArgumentException("Face value must be positive.");
            }
        });
    }

    // --- RELATIONSHIPS ---

    /**
     * Test: test_product_has_bulk_purchases_relationship
     */
    public function bulkPurchases(): HasMany
    {
        return $this->hasMany(BulkPurchase::class);
    }

    /**
     * Test: test_product_has_investments_relationship
     */
    public function investments(): HasMany
    {
        return $this->hasMany(UserInvestment::class);
    }

    /**
     * Test: test_product_has_price_history_relationship
     */
    public function priceHistory(): HasMany
    {
        return $this->hasMany(ProductPriceHistory::class)->orderBy('recorded_at', 'asc');
    }

    // --- ACCESSORS ---

    /**
     * Test: test_product_calculates_total_allocated
     * Sums up all value allocated from user investments.
     */
    protected function totalAllocated(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->investments()->sum('value_allocated')
        );
    }
}