<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\KnowledgeBaseArticle;
use App\Models\KnowledgeBaseArticleRating;
use App\Models\KnowledgeBaseArticleView;
use App\Models\KnowledgeBaseCategory;
use App\Models\KnowledgeBaseSearchLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class KnowledgeBaseController extends Controller
{
    /**
     * Get all categories with article counts
     * GET /api/v1/knowledge-base/categories
     */
    public function categories()
    {
        $categories = KnowledgeBaseCategory::active()
            ->with(['children' => function ($query) {
                $query->active()->orderBy('sort_order');
            }])
            ->whereNull('parent_id')
            ->withCount(['publishedArticles'])
            ->orderBy('sort_order')
            ->get();

        return response()->json(['data' => $categories]);
    }

    /**
     * Get articles by category
     * GET /api/v1/knowledge-base/categories/{slug}/articles
     */
    public function articlesByCategory($slug)
    {
        $category = KnowledgeBaseCategory::where('slug', $slug)
            ->active()
            ->firstOrFail();

        $articles = KnowledgeBaseArticle::where('category_id', $category->id)
            ->published()
            ->with('category')
            ->orderBy('sort_order')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($article) {
                return [
                    'id' => $article->id,
                    'title' => $article->title,
                    'slug' => $article->slug,
                    'excerpt' => $article->excerpt,
                    'views_count' => $article->views_count,
                    'helpfulness_score' => $article->helpfulness_score,
                    'reading_time' => $article->reading_time,
                    'published_at' => $article->published_at,
                ];
            });

        return response()->json([
            'category' => $category,
            'articles' => $articles,
        ]);
    }

    /**
     * Get single article
     * GET /api/v1/knowledge-base/articles/{slug}
     */
    public function showArticle($slug)
    {
        $article = KnowledgeBaseArticle::where('slug', $slug)
            ->published()
            ->with(['category', 'author'])
            ->firstOrFail();

        // Track view
        if (setting('kb_article_views_tracking', true)) {
            $this->trackView($article);
        }

        // Get related articles
        $relatedArticles = KnowledgeBaseArticle::where('category_id', $article->category_id)
            ->where('id', '!=', $article->id)
            ->published()
            ->orderBy('views_count', 'desc')
            ->limit(setting('kb_related_articles_count', 5))
            ->get(['id', 'title', 'slug', 'excerpt', 'views_count']);

        // Get user's rating if logged in
        $userRating = null;
        if (Auth::check()) {
            $userRating = KnowledgeBaseArticleRating::where('article_id', $article->id)
                ->where('user_id', Auth::id())
                ->first();
        }

        return response()->json([
            'article' => $article,
            'related_articles' => $relatedArticles,
            'user_rating' => $userRating,
        ]);
    }

    /**
     * Search articles
     * GET /api/v1/knowledge-base/search
     */
    public function search(Request $request)
    {
        $validated = $request->validate([
            'q' => 'required|string|min:2',
            'category_id' => 'nullable|exists:knowledge_base_categories,id',
        ]);

        $query = KnowledgeBaseArticle::published()
            ->with('category')
            ->search($validated['q']);

        if (isset($validated['category_id'])) {
            $query->where('category_id', $validated['category_id']);
        }

        $articles = $query->orderByRaw('CASE
            WHEN title LIKE ? THEN 1
            WHEN excerpt LIKE ? THEN 2
            ELSE 3
        END', ["%{$validated['q']}%", "%{$validated['q']}%"])
            ->limit(20)
            ->get();

        // Log search
        if (setting('kb_search_analytics', true)) {
            KnowledgeBaseSearchLog::create([
                'query' => $validated['q'],
                'results_count' => $articles->count(),
                'user_id' => Auth::id(),
                'ip_address' => $request->ip(),
            ]);
        }

        return response()->json([
            'query' => $validated['q'],
            'results_count' => $articles->count(),
            'articles' => $articles,
        ]);
    }

    /**
     * Get popular articles
     * GET /api/v1/knowledge-base/popular
     */
    public function popular()
    {
        $articles = KnowledgeBaseArticle::published()
            ->with('category')
            ->popular(setting('kb_popular_articles_count', 10))
            ->get(['id', 'title', 'slug', 'excerpt', 'views_count', 'category_id']);

        return response()->json(['articles' => $articles]);
    }

    /**
     * Get recent articles
     * GET /api/v1/knowledge-base/recent
     */
    public function recent()
    {
        $articles = KnowledgeBaseArticle::published()
            ->with('category')
            ->latest('published_at')
            ->limit(setting('kb_recent_articles_count', 5))
            ->get(['id', 'title', 'slug', 'excerpt', 'published_at', 'category_id']);

        return response()->json(['articles' => $articles]);
    }

    /**
     * Rate article
     * POST /api/v1/knowledge-base/articles/{slug}/rate
     */
    public function rateArticle(Request $request, $slug)
    {
        if (!setting('kb_article_rating_enabled', true)) {
            return response()->json([
                'message' => 'Article rating is disabled',
            ], 403);
        }

        $validated = $request->validate([
            'is_helpful' => 'required|boolean',
            'feedback' => 'nullable|string|max:1000',
        ]);

        $article = KnowledgeBaseArticle::where('slug', $slug)
            ->published()
            ->firstOrFail();

        $rating = KnowledgeBaseArticleRating::updateOrCreate(
            [
                'article_id' => $article->id,
                'user_id' => Auth::id() ?? null,
                'ip_address' => $request->ip(),
            ],
            $validated
        );

        return response()->json([
            'message' => 'Thank you for your feedback!',
            'rating' => $rating,
        ]);
    }

    /**
     * Track article view
     */
    protected function trackView(KnowledgeBaseArticle $article)
    {
        KnowledgeBaseArticleView::create([
            'article_id' => $article->id,
            'user_id' => Auth::id(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'viewed_at' => now(),
        ]);

        $article->incrementViews();
    }

    /**
     * Log search click
     * POST /api/v1/knowledge-base/search-click
     */
    public function searchClick(Request $request)
    {
        $validated = $request->validate([
            'search_log_id' => 'required|exists:knowledge_base_search_logs,id',
            'article_id' => 'required|exists:knowledge_base_articles,id',
        ]);

        $searchLog = KnowledgeBaseSearchLog::find($validated['search_log_id']);
        $searchLog->update(['clicked_article_id' => $validated['article_id']]);

        return response()->json(['message' => 'Click tracked']);
    }
}
