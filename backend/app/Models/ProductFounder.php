<?php
// V-FINAL-1730-501 (Created)

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductFounder extends Model
{
    use HasFactory;
    protected $fillable = ['product_id', 'name', 'title', 'photo_url', 'linkedin_url', 'bio', 'display_order'];
}