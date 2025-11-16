<?php
// V-FINAL-1730-502 (Created)

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductFundingRound extends Model
{
    use HasFactory;
    protected $fillable = ['product_id', 'round_name', 'date', 'amount', 'valuation', 'investors'];
    protected $casts = ['date' => 'date'];
}