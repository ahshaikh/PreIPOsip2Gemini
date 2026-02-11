<?php
// V-FINAL-1730-500 (Created) | V-REL-FIX (Added inverse relationship) | V-AUDIT-MODULE6-006 (Soft Deletes)

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes; // V-AUDIT-MODULE6-006: Add soft delete support

/**
 * @mixin IdeHelperProductHighlight
 */
class ProductHighlight extends Model
{
    use HasFactory, SoftDeletes; // V-AUDIT-MODULE6-006: Enable soft deletes for safety

    protected $fillable = ['product_id', 'content', 'display_order'];

    /**
     * Get the product this highlight belongs to.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}