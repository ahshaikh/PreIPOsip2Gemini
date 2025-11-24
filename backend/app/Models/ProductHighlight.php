<?php
// V-FINAL-1730-500 (Created) | V-REL-FIX (Added inverse relationship)

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductHighlight extends Model
{
    use HasFactory;

    protected $fillable = ['product_id', 'content', 'display_order'];

    /**
     * Get the product this highlight belongs to.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}