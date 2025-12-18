<?php
/**
 * V-AUDIT-REFACTOR-2025 | V-NUMERIC-PRECISION | V-ANALYTICS-ENGINE
 * Refactored to address Module 5 Audit Gaps:
 * 1. Casts: Enforces high-precision decimal casting for units and currency.
 * 2. Relationships: Fully hydrated belongsTo methods for deep analytics.
 * 3. Server-Side Calculations: ROI and ProfitLoss are now calculated on the server
 * using BCMath logic to ensure 100% parity across web and mobile apps.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;

class UserInvestment extends Model
{
    protected $fillable = [
        'user_id', 'product_id', 'payment_id', 'subscription_id',
        'bulk_purchase_id', 'units_allocated', 'value_allocated',
        'status', 'is_reversed', 'reversed_at', 'reversal_reason', 'source'
    ];

    /**
     * [AUDIT FIX]: Standardized casting to prevent floating point drift.
     */
    protected $casts = [
        'units_allocated' => 'decimal:4',
        'value_allocated' => 'decimal:2',
        'is_reversed' => 'boolean',
        'reversed_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    // --- RELATIONSHIPS ---

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function bulkPurchase(): BelongsTo
    {
        return $this->belongsTo(BulkPurchase::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    // --- ANALYTICS ACCESSORS (Backend-Driven Valuation) ---

    /**
     * [AUDIT FIX]: Current Market Value calculated on server.
     * Ensures parity across frontend devices.
     */
    protected function currentValue(): Attribute
    {
        return Attribute::make(
            get: function ($value, $attributes) {
                $product = $this->product; 
                if (!$product) return 0.00;

                $currentPrice = $product->current_market_price ?? $product->face_value_per_unit;
                return (float) bcmul($attributes['units_allocated'], $currentPrice, 2);
            }
        );
    }

    /**
     * [AUDIT FIX]: Accurate Profit/Loss calculation using BCMath.
     */
    protected function profitLoss(): Attribute
    {
        return Attribute::make(
            get: function ($value, $attributes) {
                $costBasis = (float) $attributes['value_allocated'];
                return (float) bcsub($this->current_value, $costBasis, 2);
            }
        );
    }

    /**
     * [AUDIT FIX]: Standardized ROI percentage.
     */
    protected function roiPercentage(): Attribute
    {
        return Attribute::make(
            get: function ($value, $attributes) {
                $costBasis = (float) $attributes['value_allocated'];
                if ($costBasis == 0) return 0.00;
                
                // (Profit / Cost) * 100
                return (float) bcmul(bcdiv($this->profit_loss, $costBasis, 4), '100', 2);
            }
        );
    }
}