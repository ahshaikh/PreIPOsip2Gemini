<?php
// V-FINAL-1730-283

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BlogPost extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title', 'slug', 'content', 'featured_image', 'author_id', 'status'
    ];

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}