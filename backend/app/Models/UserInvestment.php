<?php
// V-PHASE3-1730-075 (Created) | V-FINAL-1730-350 (Financial Logic Added)

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;

class UserInvestment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'product_id',
        'payment_id',
        'bulk_purchase_id',
        'units_allocated',
        'value_allocated', // This is the COST BASIS (Face Value)
        'source', // 'investment' or 'bonus'
    ];

    protected $casts = [
        'units_allocated' => 'decimal:4',
        'value_allocated' => 'decimal:2',
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

    // --- ACCESSORS (CALCULATED VALUES) ---

    /**
     * Calculates the current market value of this investment.
     */
    protected function currentValue(): Attribute
    {
        return Attribute::make(
            get: function ($value, $attributes) {
                // We must 'load' the product relationship first
                $product = $this->product; 
                
                if (!$product) return 0.00;

                // Use current_market_price if available, otherwise fall back to face value
                $currentPrice = $product->current_market_price ?? $product->face_value_per_unit;
                
                return (float)$attributes['units_allocated'] * (float)$currentPrice;
            }
        );
    }

    /**
     * Calculates the profit or loss in Rupees.
     */
    protected function profitLoss(): Attribute
    {
        return Attribute::make(
            get: function ($value, $attributes) {
                // currentValue (accessor) - value_allocated (db column)
                return $this->current_value - (float)$attributes['value_allocated'];
            }
        );
    }

    /**
     * Calculates the Return on Investment (ROI) percentage.
     */
    protected function roiPercentage(): Attribute
    {
        return Attribute::make(
            get: function ($value, $attributes) {
                $costBasis = (float)$attributes['value_allocated'];
                if ($costBasis == 0) {
                    return 0.00;
                }
                
                $profit = $this->profit_loss; // Uses the profit_loss accessor
                
                return ($profit / $costBasis) * 100;
            }
        );
    }
}