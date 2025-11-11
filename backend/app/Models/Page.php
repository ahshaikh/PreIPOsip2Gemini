// V-PHASE2-1730-042
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'content',
        'seo_meta',
        'status',
    ];

    protected $casts = [
        'content' => 'json',
        'seo_meta' => 'json',
    ];
}