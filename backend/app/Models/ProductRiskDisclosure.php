<?php
// V-FINAL-1730-508 (Created)

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductRiskDisclosure extends Model
{
    use HasFactory;

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