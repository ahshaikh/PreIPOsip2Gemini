<?php
// V-FINAL-1730-508 (Created) | V-AUDIT-MODULE6-006 (Soft Deletes)

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes; // V-AUDIT-MODULE6-006: Add soft delete support

class ProductRiskDisclosure extends Model
{
    use HasFactory, SoftDeletes; // V-AUDIT-MODULE6-006: Enable soft deletes for safety

    protected $fillable = [
        'product_id',
        'risk_category',
        'severity',
        'risk_title',
        'risk_description',
        'display_order',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}