<?php
// V-FINAL-1730-503 (Created) | V-REL-FIX (Added inverse relationship)

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductKeyMetric extends Model
{
    use HasFactory;

    protected $fillable = ['product_id', 'metric_name', 'value', 'unit'];

    /**
     * Get the product this metric belongs to.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}