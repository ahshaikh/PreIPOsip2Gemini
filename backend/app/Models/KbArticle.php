<?php

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
        'summary',        // <--- Added
        'content',
        'status',
        'views',
        'last_updated',   // <--- Added
        'published_at',
        'seo_meta',
    ];

    protected $casts = [
        'seo_meta' => 'array',
        'published_at' => 'datetime',
        'last_updated' => 'date', // <--- Casts to Carbon instance
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(KbCategory::class, 'kb_category_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    // ... relations for views/feedback
}