<?php
// V-FINAL-1730-503 (Created)

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductKeyMetric extends Model
{
    use HasFactory;
    protected $fillable = ['product_id', 'metric_name', 'value', 'unit'];
}