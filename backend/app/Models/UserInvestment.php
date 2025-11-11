// V-PHASE3-1730-075
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserInvestment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'product_id',
        'payment_id',
        'bulk_purchase_id',
        'units_allocated',
        'value_allocated',
        'source',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }
}