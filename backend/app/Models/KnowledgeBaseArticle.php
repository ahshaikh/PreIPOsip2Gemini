<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class KnowledgeBaseArticle extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'category_id',
        'author_id',
        'title',
        'slug',
        'excerpt',
        'content',
        'tags',
        'sort_order',
        'is_published',
        'is_featured',
        'views_count',
        'helpful_count',
        'not_helpful_count',
        'published_at',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'is_featured' => 'boolean',
        'tags' => 'array',
        'published_at' => 'datetime',
        'views_count' => 'integer',
        'helpful_count' => 'integer',
        'not_helpful_count' => 'integer',
    ];

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($article) {
            if (empty($article->slug)) {
                $article->slug = Str::slug($article->title);
            }
            if ($article->is_published && !$article->published_at) {
                $article->published_at = now();
            }
        });

        static::updating(function ($article) {
            if ($article->isDirty('title') && empty($article->slug)) {
                $article->slug = Str::slug($article->title);
            }
            if ($article->is_published && !$article->published_at) {
                $article->published_at = now();
            }
        });
    }

    /**
     * Get the category
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(KnowledgeBaseCategory::class, 'category_id');
    }

    /**
     * Get the author
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /**
     * Get article views
     */
    public function views(): HasMany
    {
        return $this->hasMany(KnowledgeBaseArticleView::class, 'article_id');
    }

    /**
     * Get article ratings
     */
    public function ratings(): HasMany
    {
        return $this->hasMany(KnowledgeBaseArticleRating::class, 'article_id');
    }

    /**
     * Scope to get published articles
     */
    public function scopePublished($query)
    {
        return $query->where('is_published', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    /**
     * Scope to get featured articles
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope to search articles
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('title', 'like', "%{$search}%")
              ->orWhere('excerpt', 'like', "%{$search}%")
              ->orWhere('content', 'like', "%{$search}%")
              ->orWhereJsonContains('tags', $search);
        });
    }

    /**
     * Scope to get popular articles
     */
    public function scopePopular($query, $limit = 10)
    {
        return $query->orderBy('views_count', 'desc')->limit($limit);
    }

    /**
     * Scope to get helpful articles
     */
    public function scopeHelpful($query)
    {
        return $query->orderByRaw('(helpful_count - not_helpful_count) DESC');
    }

    /**
     * Increment views count
     */
    public function incrementViews()
    {
        $this->increment('views_count');
    }

    /**
     * Get helpfulness score
     */
    public function getHelpfulnessScoreAttribute()
    {
        $total = $this->helpful_count + $this->not_helpful_count;
        if ($total === 0) {
            return 0;
        }
        return round(($this->helpful_count / $total) * 100, 1);
    }

    /**
     * Get reading time in minutes
     */
    public function getReadingTimeAttribute()
    {
        $wordCount = str_word_count(strip_tags($this->content));
        return ceil($wordCount / 200); // Average reading speed: 200 words/min
    }
}
