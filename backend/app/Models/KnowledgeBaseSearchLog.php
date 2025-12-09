<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KnowledgeBaseSearchLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'query',
        'results_count',
        'user_id',
        'ip_address',
        'clicked_article_id',
    ];

    protected $casts = [
        'results_count' => 'integer',
    ];

    /**
     * Get the user
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the clicked article
     */
    public function clickedArticle(): BelongsTo
    {
        return $this->belongsTo(KnowledgeBaseArticle::class, 'clicked_article_id');
    }

    /**
     * Get popular searches
     */
    public static function popularSearches($limit = 10, $days = 30)
    {
        return static::where('created_at', '>=', now()->subDays($days))
            ->selectRaw('query, COUNT(*) as search_count')
            ->groupBy('query')
            ->orderByRaw('search_count DESC')
            ->limit($limit)
            ->get();
    }

    /**
     * Get searches with no results
     */
    public static function searchesWithNoResults($limit = 10, $days = 30)
    {
        return static::where('created_at', '>=', now()->subDays($days))
            ->where('results_count', 0)
            ->selectRaw('query, COUNT(*) as search_count')
            ->groupBy('query')
            ->orderByRaw('search_count DESC')
            ->limit($limit)
            ->get();
    }
}
