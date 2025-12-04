<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\KbCategory;
use App\Models\KbArticle;
use App\Models\ArticleFeedback;
use App\Models\KbArticleView;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HelpCenterController extends Controller
{
    /**
     * Get the full Help Center menu structure (Categories + Articles).
     */
    public function menu()
    {
        // Fetch active categories with their active articles
        $categories = KbCategory::with(['articles' => function($query) {
            $query->where('status', 'published')
                  ->select('id', 'kb_category_id', 'title', 'slug');
        }])
        ->where('is_active', true)
        ->orderBy('display_order')
        ->get();

        return response()->json($categories);
    }

    /**
     * Get a single article by slug and log the view.
     */
    public function show(Request $request, $slug)
    {
        $article = KbArticle::where('slug', $slug)
            ->where('status', 'published')
            ->with('category')
            ->firstOrFail();

        // 1. Log the View (Async-like)
        KbArticleView::create([
            'kb_article_id' => $article->id,
            'user_id'       => $request->user('sanctum')?->id, // Detects logged-in user
            'ip_address'    => $request->ip(),
            'user_agent'    => $request->userAgent(),
        ]);

        // 2. Increment cached counter for sorting
        $article->increment('views');

        return response()->json($article);
    }

    /**
     * Store user feedback (Vote/Comment).
     */
    public function storeFeedback(Request $request)
    {
        $validated = $request->validate([
            'article_id' => 'required|exists:kb_articles,id', // Make sure to send ID, not slug
            'is_helpful' => 'required|boolean',
            'comment'    => 'nullable|string|max:1000',
        ]);

        ArticleFeedback::create([
            'article_id' => $validated['article_id'],
            'is_helpful' => $validated['is_helpful'],
            'comment'    => $validated['comment'] ?? null,
            'ip_address' => $request->ip(),
            'user_id'    => $request->user('sanctum')?->id,
        ]);

        return response()->json(['message' => 'Feedback recorded']);
    }
}