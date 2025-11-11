// V-PHASE2-1730-041
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}