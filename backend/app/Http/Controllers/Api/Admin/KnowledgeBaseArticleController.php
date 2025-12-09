<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\KnowledgeBaseArticle;
use App\Models\KnowledgeBaseSearchLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class KnowledgeBaseArticleController extends Controller
{
    /**
     * List all articles
     * GET /api/v1/admin/knowledge-base/articles
     */
    public function index(Request $request)
    {
        $query = KnowledgeBaseArticle::with(['category', 'author'])
            ->withCount('views', 'ratings');

        // Filter by category
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // Filter by published status
        if ($request->filled('is_published')) {
            $query->where('is_published', $request->boolean('is_published'));
        }

        // Filter by featured
        if ($request->filled('is_featured')) {
            $query->where('is_featured', $request->boolean('is_featured'));
        }

        // Search
        if ($request->filled('search')) {
            $query->search($request->search);
        }

        // Sort
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $perPage = $request->input('per_page', 15);
        $articles = $query->paginate($perPage);

        return response()->json($articles);
    }

    /**
     * Show single article
     * GET /api/v1/admin/knowledge-base/articles/{id}
     */
    public function show(KnowledgeBaseArticle $article)
    {
        $article->load(['category', 'author', 'ratings']);

        return response()->json(['data' => $article]);
    }

    /**
     * Create new article
     * POST /api/v1/admin/knowledge-base/articles
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:knowledge_base_categories,id',
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|unique:knowledge_base_articles,slug',
            'excerpt' => 'nullable|string',
            'content' => 'required|string',
            'tags' => 'nullable|array',
            'tags.*' => 'string',
            'sort_order' => 'nullable|integer',
            'is_published' => 'boolean',
            'is_featured' => 'boolean',
        ]);

        $validated['author_id'] = Auth::id();

        $article = KnowledgeBaseArticle::create($validated);

        return response()->json([
            'message' => 'Knowledge base article created successfully',
            'data' => $article,
        ], 201);
    }

    /**
     * Update article
     * PUT /api/v1/admin/knowledge-base/articles/{id}
     */
    public function update(Request $request, KnowledgeBaseArticle $article)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:knowledge_base_categories,id',
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|unique:knowledge_base_articles,slug,' . $article->id,
            'excerpt' => 'nullable|string',
            'content' => 'required|string',
            'tags' => 'nullable|array',
            'tags.*' => 'string',
            'sort_order' => 'nullable|integer',
            'is_published' => 'boolean',
            'is_featured' => 'boolean',
        ]);

        $article->update($validated);

        return response()->json([
            'message' => 'Knowledge base article updated successfully',
            'data' => $article->fresh(['category', 'author']),
        ]);
    }

    /**
     * Delete article
     * DELETE /api/v1/admin/knowledge-base/articles/{id}
     */
    public function destroy(KnowledgeBaseArticle $article)
    {
        $article->delete();

        return response()->json([
            'message' => 'Knowledge base article deleted successfully',
        ]);
    }

    /**
     * Publish/Unpublish article
     * POST /api/v1/admin/knowledge-base/articles/{id}/publish
     */
    public function togglePublish(KnowledgeBaseArticle $article)
    {
        $article->update([
            'is_published' => !$article->is_published,
            'published_at' => !$article->is_published ? now() : null,
        ]);

        return response()->json([
            'message' => $article->is_published ? 'Article published successfully' : 'Article unpublished successfully',
            'data' => $article,
        ]);
    }

    /**
     * Feature/Unfeature article
     * POST /api/v1/admin/knowledge-base/articles/{id}/feature
     */
    public function toggleFeature(KnowledgeBaseArticle $article)
    {
        $article->update([
            'is_featured' => !$article->is_featured,
        ]);

        return response()->json([
            'message' => $article->is_featured ? 'Article featured successfully' : 'Article unfeatured successfully',
            'data' => $article,
        ]);
    }

    /**
     * Get article analytics
     * GET /api/v1/admin/knowledge-base/articles/{id}/analytics
     */
    public function analytics(KnowledgeBaseArticle $article)
    {
        $analytics = [
            'views' => [
                'total' => $article->views_count,
                'last_7_days' => $article->views()->where('viewed_at', '>=', now()->subDays(7))->count(),
                'last_30_days' => $article->views()->where('viewed_at', '>=', now()->subDays(30))->count(),
            ],
            'ratings' => [
                'helpful' => $article->helpful_count,
                'not_helpful' => $article->not_helpful_count,
                'score' => $article->helpfulness_score,
                'total' => $article->ratings()->count(),
            ],
            'reading_time' => $article->reading_time,
        ];

        return response()->json(['data' => $analytics]);
    }

    /**
     * Get search analytics
     * GET /api/v1/admin/knowledge-base/search-analytics
     */
    public function searchAnalytics(Request $request)
    {
        $days = $request->input('days', 30);
        $limit = $request->input('limit', 10);

        $analytics = [
            'popular_searches' => KnowledgeBaseSearchLog::popularSearches($limit, $days),
            'no_results_searches' => KnowledgeBaseSearchLog::searchesWithNoResults($limit, $days),
            'total_searches' => KnowledgeBaseSearchLog::where('created_at', '>=', now()->subDays($days))->count(),
            'avg_results_count' => KnowledgeBaseSearchLog::where('created_at', '>=', now()->subDays($days))
                ->avg('results_count'),
        ];

        return response()->json(['data' => $analytics]);
    }

    /**
     * Duplicate article
     * POST /api/v1/admin/knowledge-base/articles/{id}/duplicate
     */
    public function duplicate(KnowledgeBaseArticle $article)
    {
        $newArticle = $article->replicate();
        $newArticle->title = $article->title . ' (Copy)';
        $newArticle->slug = null; // Will be auto-generated
        $newArticle->is_published = false;
        $newArticle->published_at = null;
        $newArticle->views_count = 0;
        $newArticle->helpful_count = 0;
        $newArticle->not_helpful_count = 0;
        $newArticle->author_id = Auth::id();
        $newArticle->save();

        return response()->json([
            'message' => 'Article duplicated successfully',
            'data' => $newArticle,
        ], 201);
    }
}
