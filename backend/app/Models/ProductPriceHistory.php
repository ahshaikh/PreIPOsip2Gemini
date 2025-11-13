<?php
// V-FINAL-1730-287

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductPriceHistory extends Model
{
    protected $fillable = ['product_id', 'price', 'recorded_at'];
    
    protected $casts = [
        'recorded_at' => 'date'
    ];
}