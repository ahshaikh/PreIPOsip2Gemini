<?php
// V-REFACTOR-1730-KB-CONSOLIDATION (Gemini)
// Fixed: Split Brain Architecture (Now uses KbArticle/KbCategory)
// Fixed: Inefficient Search (Now uses Full-Text Search)

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\KbArticle;
use App\Models\KbCategory;
use App\Models\ArticleFeedback;
use App\Models\KnowledgeBaseSearchLog; 
use App\Jobs\ProcessKbView; // Use the new Job for async tracking
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class KnowledgeBaseController extends Controller
{
    /**
     * Get all categories with article counts
     * GET /api/v1/knowledge-base/categories
     */
    public function categories()
    {
        // FIX: Switched to KbCategory model (System A)
        // Logic: Only parents, active, with active children and published article counts
        $categories = KbCategory::where('is_active', true)
            ->whereNull('parent_id')
            ->with(['children' => function ($query) {
                $query->where('is_active', true)->orderBy('display_order');
            }])
            ->withCount(['articles' => function ($query) {
                $query->where('status', 'published');
            }])
            ->orderBy('display_order')
            ->get();

        return response()->json(['data' => $categories]);
    }

    /**
     * Get articles by category
     * GET /api/v1/knowledge-base/categories/{slug}/articles
     */
    public function articlesByCategory($slug)
    {
        // FIX: Switched to KbCategory model
        $category = KbCategory::where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        // FIX: Switched to KbArticle model
        // Note: KbArticle uses 'kb_category_id', not 'category_id'
        $articles = KbArticle::where('kb_category_id', $category->id)
            ->where('status', 'published')
            ->with('category') // Relationship in KbArticle is 'category'
            ->orderBy('published_at', 'desc')
            ->get()
            ->map(function ($article) {
                return [
                    'id' => $article->id,
                    'title' => $article->title,
                    'slug' => $article->slug,
                    'excerpt' => $article->summary, // Mapped 'summary' to 'excerpt' for frontend compatibility
                    'views_count' => $article->views, // 'views' column
                    // 'helpfulness_score' and 'reading_time' removed as they are not in KbArticle schema
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
        // FIX: Switched to KbArticle model
        $article = KbArticle::where('slug', $slug)
            ->where('status', 'published')
            ->with(['category', 'author'])
            ->firstOrFail();

        // FIX: Async View Tracking via Job (Performance)
        // Instead of synchronous write, we dispatch a job to prevent DB locking
        if (function_exists('setting') && setting('kb_article_views_tracking', true)) {
             ProcessKbView::dispatch($article->id, request()->ip(), request()->userAgent(), Auth::id());
        }

        // Get related articles (Simple logic: same category, popular)
        $relatedArticles = KbArticle::where('kb_category_id', $article->kb_category_id)
            ->where('id', '!=', $article->id)
            ->where('status', 'published')
            ->orderBy('views', 'desc')
            ->limit(5)
            ->get(['id', 'title', 'slug', 'summary as excerpt', 'views as views_count']);

        // Get user's rating if logged in
        $userRating = null;
        if (Auth::check()) {
            // FIX: Using ArticleFeedback model consistent with Public API
            $userRating = ArticleFeedback::where('article_id', $article->id)
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
            'category_id' => 'nullable|exists:kb_categories,id', // Fixed table name ref
        ]);

        $query = KbArticle::where('status', 'published')
            ->with('category');

        // FIX: Performance - Replaced LIKE %...% with Full-Text Search
        // Requires FULLTEXT index on (title, content)
        if (!empty($validated['q'])) {
            $searchTerm = $validated['q'];
            // Using boolean mode allows for operators if needed, or simple keyword matching
            $query->whereRaw("MATCH(title, content) AGAINST(? IN BOOLEAN MODE)", [$searchTerm]);
        }

        if (isset($validated['category_id'])) {
            $query->where('kb_category_id', $validated['category_id']);
        }

        $articles = $query->limit(20)->get();

        // Log search (Keeping legacy logging for now)
        if (function_exists('setting') && setting('kb_search_analytics', true)) {
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
        $articles = KbArticle::where('status', 'published')
            ->with('category')
            ->orderBy('views', 'desc')
            ->limit(10)
            ->get(['id', 'title', 'slug', 'summary as excerpt', 'views as views_count', 'kb_category_id']);

        return response()->json(['articles' => $articles]);
    }

    /**
     * Get recent articles
     * GET /api/v1/knowledge-base/recent
     */
    public function recent()
    {
        $articles = KbArticle::where('status', 'published')
            ->with('category')
            ->latest('published_at')
            ->limit(5)
            ->get(['id', 'title', 'slug', 'summary as excerpt', 'published_at', 'kb_category_id']);

        return response()->json(['articles' => $articles]);
    }

    /**
     * Rate article
     * POST /api/v1/knowledge-base/articles/{slug}/rate
     */
    public function rateArticle(Request $request, $slug)
    {
        if (function_exists('setting') && !setting('kb_article_rating_enabled', true)) {
            return response()->json([
                'message' => 'Article rating is disabled',
            ], 403);
        }

        $validated = $request->validate([
            'is_helpful' => 'required|boolean',
            'feedback' => 'nullable|string|max:1000', // Mapped to 'comment' in ArticleFeedback
        ]);

        $article = KbArticle::where('slug', $slug)
            ->where('status', 'published')
            ->firstOrFail();

        // FIX: Consolidated to ArticleFeedback model
        $rating = ArticleFeedback::updateOrCreate(
            [
                'article_id' => $article->id,
                'user_id' => Auth::id(), 
            ],
            [
                'is_helpful' => $validated['is_helpful'],
                'comment' => $validated['feedback'] ?? null,
                'ip_address' => $request->ip(),
            ]
        );

        return response()->json([
            'message' => 'Thank you for your feedback!',
            'rating' => $rating,
        ]);
    }

    /**
     * Log search click
     * POST /api/v1/knowledge-base/search-click
     */
    public function searchClick(Request $request)
    {
        $validated = $request->validate([
            'search_log_id' => 'required|exists:knowledge_base_search_logs,id',
            'article_id' => 'required|exists:kb_articles,id', // Fixed table ref
        ]);

        $searchLog = KnowledgeBaseSearchLog::find($validated['search_log_id']);
        $searchLog->update(['clicked_article_id' => $validated['article_id']]);

        return response()->json(['message' => 'Click tracked']);
    }
    
    // Removed trackView() helper as logic is now handled via Job in showArticle()
}