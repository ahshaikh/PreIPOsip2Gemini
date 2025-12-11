<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SupportAIService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Support AI Controller
 *
 * Provides AI-powered features for the support system
 */
class SupportAIController extends Controller
{
    protected $aiService;

    public function __construct(SupportAIService $aiService)
    {
        $this->aiService = $aiService;
    }

    /**
     * Get article suggestions based on ticket content
     *
     * POST /api/v1/support/ai/suggest-articles
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function suggestArticles(Request $request)
    {
        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'limit' => 'nullable|integer|min:1|max:10'
        ]);

        $text = $validated['subject'] . ' ' . ($validated['description'] ?? '');
        $limit = $validated['limit'] ?? 5;

        $suggestions = $this->aiService->suggestArticles($text, $limit);

        return response()->json([
            'success' => true,
            'suggestions' => $suggestions->map(function ($article) {
                return [
                    'id' => $article->id,
                    'title' => $article->title,
                    'summary' => $article->summary,
                    'slug' => $article->slug,
                    'category' => $article->category->name ?? 'General',
                    'relevance_score' => round($article->relevance_score, 2),
                    'views' => $article->views,
                    'url' => "/help-center/articles/{$article->slug}"
                ];
            }),
            'message' => $suggestions->isEmpty()
                ? 'No relevant articles found. Our support team will help you!'
                : 'Found ' . $suggestions->count() . ' relevant article(s) that might help.'
        ]);
    }

    /**
     * Auto-classify a ticket based on its content
     *
     * POST /api/v1/support/ai/classify
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function classifyTicket(Request $request)
    {
        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000'
        ]);

        $category = $this->aiService->classifyTicket(
            $validated['subject'],
            $validated['description'] ?? ''
        );

        return response()->json([
            'success' => true,
            'suggested_category' => $category,
            'message' => "We think this is a '{$category}' issue. You can change this if needed."
        ]);
    }

    /**
     * Detect duplicate tickets
     *
     * POST /api/v1/support/ai/detect-duplicates
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function detectDuplicates(Request $request)
    {
        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000'
        ]);

        $userId = Auth::id();

        $duplicates = $this->aiService->detectDuplicates(
            $userId,
            $validated['subject'],
            $validated['description'] ?? ''
        );

        return response()->json([
            'success' => true,
            'has_duplicates' => $duplicates->isNotEmpty(),
            'duplicates' => $duplicates->map(function ($ticket) {
                return [
                    'id' => $ticket->id,
                    'ticket_id' => $ticket->ticket_id,
                    'subject' => $ticket->subject,
                    'status' => $ticket->status,
                    'created_at' => $ticket->created_at->diffForHumans(),
                    'similarity_score' => round($ticket->similarity_score * 100, 0) . '%',
                    'url' => "/support/{$ticket->id}"
                ];
            }),
            'message' => $duplicates->isEmpty()
                ? 'No similar tickets found.'
                : 'We found ' . $duplicates->count() . ' similar ticket(s). Would you like to check them first?'
        ]);
    }

    /**
     * Analyze ticket sentiment and suggest priority
     *
     * POST /api/v1/support/ai/analyze-sentiment
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function analyzeSentiment(Request $request)
    {
        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000'
        ]);

        $analysis = $this->aiService->analyzeSentiment(
            $validated['subject'],
            $validated['description'] ?? ''
        );

        return response()->json([
            'success' => true,
            'analysis' => $analysis,
            'message' => 'Ticket analyzed successfully.'
        ]);
    }

    /**
     * Get comprehensive AI analysis (all features at once)
     *
     * POST /api/v1/support/ai/analyze
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function comprehensiveAnalysis(Request $request)
    {
        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000'
        ]);

        $text = $validated['subject'] . ' ' . ($validated['description'] ?? '');
        $userId = Auth::id();

        // Run all AI features in parallel
        $suggestions = $this->aiService->suggestArticles($text, 3);
        $category = $this->aiService->classifyTicket(
            $validated['subject'],
            $validated['description'] ?? ''
        );
        $duplicates = $this->aiService->detectDuplicates(
            $userId,
            $validated['subject'],
            $validated['description'] ?? ''
        );
        $sentiment = $this->aiService->analyzeSentiment(
            $validated['subject'],
            $validated['description'] ?? ''
        );

        return response()->json([
            'success' => true,
            'analysis' => [
                'suggested_category' => $category,
                'suggested_priority' => $sentiment['priority'],
                'sentiment' => $sentiment['sentiment'],
                'urgency_score' => $sentiment['urgency_score'],
                'has_duplicates' => $duplicates->isNotEmpty(),
                'duplicate_count' => $duplicates->count(),
                'has_suggested_articles' => $suggestions->isNotEmpty(),
                'suggested_article_count' => $suggestions->count()
            ],
            'suggestions' => $suggestions->take(3)->map(function ($article) {
                return [
                    'id' => $article->id,
                    'title' => $article->title,
                    'summary' => $article->summary,
                    'slug' => $article->slug,
                    'url' => "/help-center/articles/{$article->slug}"
                ];
            }),
            'duplicates' => $duplicates->take(2)->map(function ($ticket) {
                return [
                    'id' => $ticket->id,
                    'ticket_id' => $ticket->ticket_id,
                    'subject' => $ticket->subject,
                    'similarity_score' => round($ticket->similarity_score * 100, 0) . '%'
                ];
            })
        ]);
    }
}
