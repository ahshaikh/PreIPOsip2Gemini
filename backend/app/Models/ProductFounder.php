<?php
// V-FINAL-1730-501 (Created) | V-REL-FIX (Added inverse relationship)

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductFounder extends Model
{
    use HasFactory;

    protected $fillable = ['product_id', 'name', 'title', 'photo_url', 'linkedin_url', 'bio', 'display_order'];

    /**
     * Get the product this founder belongs to.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}