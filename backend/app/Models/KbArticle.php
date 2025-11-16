<?php
// V-FINAL-1730-551 (Created)

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KbArticle extends Model
{
    use HasFactory;
    
    protected $table = 'kb_articles';

    protected $fillable = [
        'kb_category_id',
        'author_id',
        'title',
        'slug',
        'content',
        'status',
        'views',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(KbCategory::class, 'kb_category_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}