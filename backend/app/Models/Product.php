<?php
// V-PHASE2-1730-040


namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'company_logo',
        'sector',
        'face_value_per_unit',
        'min_investment',
        'expected_ipo_date',
        'description',
        'status',
        'is_featured',
    ];

    protected $casts = [
        'description' => 'json',
        'expected_ipo_date' => 'date',
    ];
    
    public function priceHistory()
    {
        return $this->hasMany(ProductPriceHistory::class)->orderBy('recorded_at', 'asc');
    }

    public function bulkPurchases(): HasMany
    {
        return $this->hasMany(BulkPurchase::class);
    }
}