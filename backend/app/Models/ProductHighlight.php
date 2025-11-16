<?php
// V-FINAL-1730-500 (Created)

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductHighlight extends Model
{
    use HasFactory;
    protected $fillable = ['product_id', 'content', 'display_order'];
}