<?php
// V-FINAL-1730-349 (Financial Logic Added)

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;

class BulkPurchase extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'admin_id',
        'face_value_purchased',
        'actual_cost_paid',
        'discount_percentage',
        'extra_allocation_percentage',
        'total_value_received',
        'value_remaining',
        'seller_name',
        'purchase_date',
        'notes',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'face_value_purchased' => 'decimal:2',
        'actual_cost_paid' => 'decimal:2',
        'discount_percentage' => 'decimal:2',
        'extra_allocation_percentage' => 'decimal:2',
        'total_value_received' => 'decimal:2',
        'value_remaining' => 'decimal:2',
    ];

    /**
     * Boot logic to auto-calculate fields on creation.
     */
    protected static function booted()
    {
        static::creating(function ($purchase) {
            // Validation
            if ($purchase->face_value_purchased <= 0) {
                throw new \InvalidArgumentException("Face value must be positive.");
            }
            if ($purchase->actual_cost_paid < 0) {
                throw new \InvalidArgumentException("Actual cost cannot be negative.");
            }
            
            // Auto-calculate
            $purchase->total_value_received = $purchase->face_value_purchased * (1 + ($purchase->extra_allocation_percentage / 100));
            $purchase->discount_percentage = (($purchase->face_value_purchased - $purchase->actual_cost_paid) / $purchase->face_value_purchased) * 100;
            
            // On creation, remaining is the total received
            $purchase->value_remaining = $purchase->total_value_received;
        });
    }

    // --- RELATIONSHIPS ---

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    // --- ACCESSORS (CALCULATIONS) ---

    /**
     * Calculates the total value allocated from this purchase.
     */
    protected function allocatedAmount(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->total_value_received - $this->value_remaining
        );
    }
    
    /**
     * Alias for value_remaining.
     */
    protected function availableAmount(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->value_remaining
        );
    }

    /**
     * Calculates the total profit margin in Rupees.
     * (Total Value - What we Paid)
     */
    protected function grossMargin(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->total_value_received - $this->actual_cost_paid
        );
    }

    /**
     * Calculates the profit margin as a percentage.
     * (Margin / Cost)
     */
    protected function grossMarginPercentage(): Attribute
    {
        return Attribute::make(
            get: fn () => {
                if ($this->actual_cost_paid == 0) return 0;
                return ($this->gross_margin / $this->actual_cost_paid) * 100;
            }
        );
    }
}