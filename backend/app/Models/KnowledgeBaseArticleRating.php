<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KnowledgeBaseArticleRating extends Model
{
    use HasFactory;

    protected $fillable = [
        'article_id',
        'user_id',
        'is_helpful',
        'feedback',
        'ip_address',
    ];

    protected $casts = [
        'is_helpful' => 'boolean',
    ];

    /**
     * Get the article
     */
    public function article(): BelongsTo
    {
        return $this->belongsTo(KnowledgeBaseArticle::class, 'article_id');
    }

    /**
     * Get the user
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::created(function ($rating) {
            $article = $rating->article;
            if ($rating->is_helpful) {
                $article->increment('helpful_count');
            } else {
                $article->increment('not_helpful_count');
            }
        });

        static::updated(function ($rating) {
            if ($rating->isDirty('is_helpful')) {
                $article = $rating->article;
                if ($rating->is_helpful) {
                    $article->increment('helpful_count');
                    $article->decrement('not_helpful_count');
                } else {
                    $article->decrement('helpful_count');
                    $article->increment('not_helpful_count');
                }
            }
        });

        static::deleted(function ($rating) {
            $article = $rating->article;
            if ($rating->is_helpful) {
                $article->decrement('helpful_count');
            } else {
                $article->decrement('not_helpful_count');
            }
        });
    }
}
