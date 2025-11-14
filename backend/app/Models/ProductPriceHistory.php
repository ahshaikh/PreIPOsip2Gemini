<?php
// V-FINAL-1730-287 (Created) | V-FINAL-1730-412 (Logic Upgraded)

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductPriceHistory extends Model
{
    use HasFactory;

    protected $fillable = ['product_id', 'price', 'recorded_at'];
    
    protected $casts = [
        'recorded_at' => 'date',
        'price' => 'decimal:2',
    ];

    /**
     * Boot logic to enforce validation.
     */
    protected static function booted()
    {
        static::saving(function ($history) {
            // Test: test_price_history_validates_price_positive
            if ($history->price <= 0) {
                throw new \InvalidArgumentException("Price must be positive.");
            }
        });
    }

    // --- RELATIONSHIPS ---

    /**
     * Test: test_price_history_belongs_to_product
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}