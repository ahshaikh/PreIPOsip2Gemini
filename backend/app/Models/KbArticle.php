<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KbArticle extends Model
{
    use HasFactory;
    
    protected $table = 'kb_articles';

    // 1. Ensure ALL fields are here. If it's not here, it won't save.
    protected $fillable = [
        'kb_category_id',
        'author_id',
        'title',
        'slug',
        'summary',
        'content',
        'status',
        'views',
        'seo_meta',
        'last_updated',
        'published_at'
    ];

    // 2. Casts ensure you get JSON/Objects, not strings.
    protected $casts = [
        'seo_meta' => 'array', 
        'published_at' => 'datetime',
        'last_updated' => 'datetime',
        'views' => 'integer',
        'kb_category_id' => 'integer',
    ];

    // 3. Explicitly define Foreign Keys to prevent "Constraint Violation" errors
    public function views(): HasMany
    {
        // Assuming the column in kb_article_views is 'kb_article_id'
        return $this->hasMany(KbArticleView::class, 'kb_article_id');
    }

    public function feedback(): HasMany
    {
        // Assuming the column in article_feedback is 'article_id'
        return $this->hasMany(ArticleFeedback::class, 'article_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(KbCategory::class, 'kb_category_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}