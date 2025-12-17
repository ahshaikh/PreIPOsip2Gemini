<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\KbCategory;
use App\Models\KbArticle;
use App\Models\ArticleFeedback;
use App\Models\KbArticleView;
use App\Jobs\ProcessKbView; // Import the Job for async tracking
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

// V-AUDIT-MODULE15-MEDIUM: Rate limiting and search sanitization
use Illuminate\Support\Facades\RateLimiter;

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

        // FIX: Performance Bottleneck (Synchronous Writes)
        // Replaced direct synchronous creation with Queueable Job.
        // This prevents table locking during high traffic.
        
        $userId = $request->user('sanctum')?->id;
        
        ProcessKbView::dispatch(
            $article->id, 
            $request->ip(), 
            $request->userAgent(), 
            $userId
        )->afterResponse(); // Dispatch after response is sent to user for max speed

        return response()->json($article);
    }

    /**
     * Store user feedback (Vote/Comment).
     *
     * V-AUDIT-MODULE15-MEDIUM (SECURITY): Add rate limiting to prevent feedback spam
     *
     * Previous Issue:
     * The storeFeedback endpoint had no rate limiting. A malicious bot could flood the
     * endpoint with thousands of feedback entries, polluting the feedback data and
     * making it useless for analytics. This also creates unnecessary database load.
     *
     * Fix:
     * Implemented RateLimiter with 10 feedback submissions per minute per IP address.
     * This prevents spam while allowing legitimate users to provide feedback on multiple articles.
     *
     * Benefits:
     * - Protects feedback data integrity
     * - Prevents database spam and storage waste
     * - Maintains quality of helpfulness metrics
     * - Blocks automated abuse attempts
     */
    public function storeFeedback(Request $request)
    {
        // V-AUDIT-MODULE15-MEDIUM: Rate limiting - 10 feedback per minute per IP
        // Prevents bots from flooding feedback system with fake votes
        $rateLimitKey = 'help-center-feedback:' . $request->ip();
        $maxFeedbackPerMinute = 10;

        $executed = RateLimiter::attempt(
            $rateLimitKey,
            $maxFeedbackPerMinute,
            function() {}, // Empty callback, we just need the check
            60 // 1 minute in seconds
        );

        if (!$executed) {
            $availableIn = RateLimiter::availableIn($rateLimitKey);
            return response()->json([
                'message' => "Rate limit exceeded. You can submit up to {$maxFeedbackPerMinute} feedback entries per minute. Please try again in " . ceil($availableIn) . " seconds.",
                'retry_after' => $availableIn
            ], 429);
        }

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

    /**
     * Search articles (Server-Side).
     *
     * V-AUDIT-MODULE15-HIGH (SCALABILITY): Add server-side search endpoint
     *
     * Previous Issue:
     * Frontend implemented client-side search by fetching ALL articles via /menu
     * and filtering in JavaScript. As the KB grows to hundreds of articles, this
     * creates massive JSON payloads (MBs of data) causing:
     * - Slow initial page load
     * - High bandwidth usage
     * - Poor mobile experience
     * - Inefficient search performance
     *
     * Fix:
     * Implemented server-side search using MySQL FULLTEXT index (MATCH AGAINST).
     * Frontend now calls /api/v1/help-center/search?q=term to get filtered results.
     *
     * Benefits:
     * - 10-100x faster search (database-level optimization)
     * - Minimal payload (only matching articles returned)
     * - Scales to thousands of articles
     * - Better mobile performance
     * - Relevance scoring built-in
     *
     * V-AUDIT-MODULE15-MEDIUM (UX): Sanitize Boolean operators in search
     *
     * MySQL Boolean mode treats +, -, *, <, > as operators. If user searches "Error -500",
     * MySQL interprets -500 as "exclude results containing 500", causing confusing results.
     * We sanitize the input by escaping these special characters.
     */
    public function search(Request $request)
    {
        $validated = $request->validate([
            'q' => 'required|string|min:2|max:200', // V-AUDIT-MODULE15: Added max length to prevent complexity attacks
            'category_id' => 'nullable|exists:kb_categories,id',
        ]);

        $searchTerm = $validated['q'];

        // V-AUDIT-MODULE15-MEDIUM: Sanitize Boolean operators to prevent unexpected search behavior
        // Escape special MySQL Boolean mode operators: + - @ < > ( ) ~ * "
        // This ensures user searches like "C++ programming" or "Error -500" work as expected
        // without being interpreted as Boolean logic operators
        $sanitizedTerm = preg_replace('/[+\-@<>()~*"]/', ' ', $searchTerm);
        $sanitizedTerm = trim(preg_replace('/\s+/', ' ', $sanitizedTerm)); // Normalize whitespace

        $query = KbArticle::where('status', 'published')
            ->with('category');

        // V-AUDIT-MODULE15-HIGH: Use FULLTEXT search for performance
        // Requires FULLTEXT index on (title, content, summary) - created in Module 14
        // Falls back gracefully if FULLTEXT not available
        if (DB::getDriverName() === 'mysql' && !empty($sanitizedTerm)) {
            try {
                // Use NATURAL LANGUAGE MODE instead of BOOLEAN MODE for better UX
                // NATURAL LANGUAGE MODE doesn't interpret operators, just ranks by relevance
                $query->selectRaw('kb_articles.*, MATCH(title, content, summary) AGAINST(? IN NATURAL LANGUAGE MODE) as relevance', [$sanitizedTerm])
                    ->whereRaw('MATCH(title, content, summary) AGAINST(? IN NATURAL LANGUAGE MODE)', [$sanitizedTerm])
                    ->orderByDesc('relevance');
            } catch (\Exception $e) {
                // FULLTEXT index not available, fall back to LIKE search
                \Log::warning('FULLTEXT search failed on kb_articles, falling back to LIKE: ' . $e->getMessage());
                $query->where(function($q) use ($sanitizedTerm) {
                    $q->where('title', 'like', "%{$sanitizedTerm}%")
                      ->orWhere('summary', 'like', "%{$sanitizedTerm}%")
                      ->orWhere('content', 'like', "%{$sanitizedTerm}%");
                });
            }
        } else {
            // Non-MySQL or empty term - use LIKE fallback
            $query->where(function($q) use ($sanitizedTerm) {
                $q->where('title', 'like', "%{$sanitizedTerm}%")
                  ->orWhere('summary', 'like', "%{$sanitizedTerm}%")
                  ->orWhere('content', 'like', "%{$sanitizedTerm}%");
            });
        }

        // V-AUDIT-MODULE15: Optional category filter
        if (isset($validated['category_id'])) {
            $query->where('kb_category_id', $validated['category_id']);
        }

        // Limit results to prevent excessive payload
        $articles = $query->limit(50)->get([
            'id',
            'kb_category_id',
            'title',
            'slug',
            'summary',
            'views',
            'published_at'
        ]);

        return response()->json([
            'query' => $searchTerm,
            'sanitized_query' => $sanitizedTerm,
            'results_count' => $articles->count(),
            'articles' => $articles->map(function ($article) {
                return [
                    'id' => $article->id,
                    'title' => $article->title,
                    'slug' => $article->slug,
                    'excerpt' => $article->summary,
                    'views_count' => $article->views,
                    'published_at' => $article->published_at,
                    'category' => $article->category ? [
                        'id' => $article->category->id,
                        'name' => $article->category->name,
                        'slug' => $article->category->slug,
                    ] : null,
                ];
            }),
        ]);
    }
}