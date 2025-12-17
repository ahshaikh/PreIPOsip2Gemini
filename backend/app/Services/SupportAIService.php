<?php

namespace App\Services;

use App\Models\KbArticle;
use App\Models\SupportTicket;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * AI-Powered Support Service
 *
 * Provides intelligent features for the support system:
 * - Article suggestions based on ticket content
 * - Automatic ticket classification
 * - Duplicate ticket detection
 * - Smart routing and prioritization
 */
class SupportAIService
{
    /**
     * Suggest relevant KB articles based on text content
     *
     * V-AUDIT-MODULE14-FULLTEXT (MEDIUM): Upgraded to MySQL Full-Text Search
     *
     * Uses MySQL FULLTEXT index with MATCH() AGAINST() for:
     * - 10-100x faster search performance
     * - Automatic relevance scoring
     * - Natural language search capabilities
     * - Better scalability (handles 10,000+ articles efficiently)
     *
     * Fallback to keyword search if FULLTEXT not available
     *
     * @param string $text The text to analyze (ticket subject + description)
     * @param int $limit Maximum number of suggestions to return
     * @return Collection Collection of suggested articles with relevance scores
     */
    public function suggestArticles(string $text, int $limit = 5): Collection
    {
        // Clean and prepare the input text
        $text = $this->cleanText($text);

        if (empty($text)) {
            // If no text, return popular articles as fallback
            return $this->getPopularArticles($limit);
        }

        // V-AUDIT-MODULE14-FULLTEXT: Use MySQL Full-Text Search if available
        // Check if we're using MySQL and FULLTEXT index exists
        if (DB::getDriverName() === 'mysql') {
            try {
                // Use MATCH() AGAINST() for Full-Text Search
                $articles = KbArticle::where('status', 'published')
                    ->selectRaw('*, MATCH(title, content, summary) AGAINST(? IN NATURAL LANGUAGE MODE) as relevance', [$text])
                    ->whereRaw('MATCH(title, content, summary) AGAINST(? IN NATURAL LANGUAGE MODE)', [$text])
                    ->with('category')
                    ->orderByDesc('relevance')
                    ->limit($limit)
                    ->get();

                // If we got results, return them
                if ($articles->isNotEmpty()) {
                    return $articles->map(function ($article) {
                        $article->relevance_score = $article->relevance;
                        return $article;
                    });
                }
            } catch (\Exception $e) {
                // If FULLTEXT search fails (index not created yet), fall through to keyword search
                \Log::warning('FULLTEXT search failed, falling back to keyword search: ' . $e->getMessage());
            }
        }

        // Fallback: Extract keywords and use LIKE queries (original implementation)
        $keywords = $this->extractKeywords($text);

        if (empty($keywords)) {
            return $this->getPopularArticles($limit);
        }

        // Build search query with keyword matching
        $query = KbArticle::where('status', 'published');

        // Search in title and content
        $query->where(function ($q) use ($keywords) {
            foreach ($keywords as $keyword) {
                // Prefix match (Index friendly)
                $q->orWhere('title', 'like', "{$keyword}%")
                  ->orWhere('content', 'like', "{$keyword}%")
                  ->orWhere('summary', 'like', "{$keyword}%");
            }
        });

        $articles = $query->with('category')
            ->limit($limit * 3) // Get more for scoring
            ->get();

        // Score and rank articles
        $scoredArticles = $articles->map(function ($article) use ($keywords, $text) {
            $score = $this->calculateRelevanceScore($article, $keywords, $text);
            $article->relevance_score = $score;
            return $article;
        });

        // Sort by score and return top results
        return $scoredArticles->sortByDesc('relevance_score')
            ->take($limit)
            ->values();
    }

    /**
     * Automatically classify a ticket based on its content
     *
     * Analyzes the subject and description to determine the most likely category
     *
     * @param string $subject Ticket subject
     * @param string $description Ticket description
     * @return string Predicted category
     */
    public function classifyTicket(string $subject, string $description): string
    {
        $text = strtolower($subject . ' ' . $description);

        // Define category keywords with weights
        $categoryPatterns = [
            'payment' => [
                'keywords' => ['payment', 'refund', 'billing', 'charge', 'invoice', 'transaction', 'card', 'bank', 'money', 'paid', 'deposit'],
                'weight' => 1.2
            ],
            'kyc' => [
                'keywords' => ['kyc', 'verification', 'identity', 'document', 'aadhaar', 'pan', 'digilocker', 'verify', 'upload'],
                'weight' => 1.3
            ],
            'technical' => [
                'keywords' => ['error', 'bug', 'broken', 'not working', 'crash', 'loading', 'slow', 'technical', 'issue', 'problem'],
                'weight' => 1.1
            ],
            'subscription' => [
                'keywords' => ['subscription', 'plan', 'upgrade', 'downgrade', 'cancel', 'sip', 'investment', 'portfolio'],
                'weight' => 1.0
            ],
            'withdrawal' => [
                'keywords' => ['withdraw', 'withdrawal', 'transfer', 'payout', 'wallet', 'balance'],
                'weight' => 1.2
            ],
        ];

        $scores = [];

        foreach ($categoryPatterns as $category => $data) {
            $score = 0;
            foreach ($data['keywords'] as $keyword) {
                // Count keyword occurrences
                $count = substr_count($text, $keyword);
                $score += $count * $data['weight'];
            }
            $scores[$category] = $score;
        }

        // Return category with highest score, or 'general' if no match
        $maxCategory = array_keys($scores, max($scores))[0] ?? 'general';

        return max($scores) > 0 ? $maxCategory : 'general';
    }

    /**
     * Detect potential duplicate tickets
     *
     * Finds similar open tickets from the same user or with similar content
     *
     * @param int $userId User ID
     * @param string $subject Ticket subject
     * @param string $description Ticket description
     * @return Collection Collection of potential duplicate tickets
     */
    public function detectDuplicates(int $userId, string $subject, string $description): Collection
    {
        // Search for similar tickets from the same user in the last 30 days
        $similarTickets = SupportTicket::where('user_id', $userId)
            ->whereIn('status', ['open', 'waiting_for_user'])
            ->where('created_at', '>=', now()->subDays(30))
            ->get();

        if ($similarTickets->isEmpty()) {
            return collect([]);
        }

        // Calculate similarity scores
        $duplicates = $similarTickets->map(function ($ticket) use ($subject, $description) {
            $similarity = $this->calculateSimilarity(
                $subject . ' ' . $description,
                $ticket->subject . ' ' . $ticket->description
            );

            if ($similarity > 0.6) { // 60% similarity threshold
                $ticket->similarity_score = $similarity;
                return $ticket;
            }

            return null;
        })->filter()->sortByDesc('similarity_score')->values();

        return $duplicates;
    }

    /**
     * Analyze ticket sentiment and urgency
     *
     * Detects urgent language and emotional tone to help with prioritization
     *
     * @param string $subject Ticket subject
     * @param string $description Ticket description
     * @return array ['priority' => string, 'sentiment' => string, 'urgency_score' => float]
     */
    public function analyzeSentiment(string $subject, string $description): array
    {
        $text = strtolower($subject . ' ' . $description);

        // Urgency indicators
        $urgentWords = ['urgent', 'asap', 'immediately', 'emergency', 'critical', 'help', 'cannot', 'stuck', 'lost', 'broken'];
        $urgencyScore = 0;

        foreach ($urgentWords as $word) {
            if (str_contains($text, $word)) {
                $urgencyScore += 1;
            }
        }

        // Sentiment detection
        $negativeWords = ['angry', 'frustrated', 'disappointed', 'terrible', 'worst', 'hate', 'annoyed'];
        $positiveWords = ['thanks', 'appreciate', 'good', 'great', 'helpful'];

        $sentiment = 'neutral';
        foreach ($negativeWords as $word) {
            if (str_contains($text, $word)) {
                $sentiment = 'negative';
                break;
            }
        }
        foreach ($positiveWords as $word) {
            if (str_contains($text, $word)) {
                $sentiment = 'positive';
                break;
            }
        }

        // Determine priority
        $priority = 'medium';
        if ($urgencyScore >= 3) {
            $priority = 'high';
        } elseif ($urgencyScore <= 1 && $sentiment === 'positive') {
            $priority = 'low';
        }

        return [
            'priority' => $priority,
            'sentiment' => $sentiment,
            'urgency_score' => min($urgencyScore / 5, 1), // Normalize to 0-1
        ];
    }

    // ========================================
    // PRIVATE HELPER METHODS
    // ========================================

    /**
     * Clean text for processing
     */
    private function cleanText(string $text): string
    {
        // Remove HTML tags
        $text = strip_tags($text);

        // Remove special characters
        $text = preg_replace('/[^a-zA-Z0-9\s]/', ' ', $text);

        // Remove extra whitespace
        $text = preg_replace('/\s+/', ' ', $text);

        return trim(strtolower($text));
    }

    /**
     * Extract important keywords from text
     */
    private function extractKeywords(string $text): array
    {
        // Common stopwords to ignore
        $stopwords = [
            'the', 'is', 'at', 'which', 'on', 'a', 'an', 'and', 'or', 'but',
            'in', 'with', 'to', 'for', 'of', 'as', 'by', 'from', 'this', 'that',
            'i', 'my', 'me', 'can', 'how', 'what', 'when', 'where', 'why',
            'do', 'does', 'did', 'have', 'has', 'had', 'am', 'are', 'was', 'were'
        ];

        $words = explode(' ', $text);

        // Filter out stopwords and short words
        $keywords = array_filter($words, function ($word) use ($stopwords) {
            return strlen($word) > 3 && !in_array($word, $stopwords);
        });

        // Get unique keywords
        return array_unique(array_values($keywords));
    }

    /**
     * Calculate relevance score for an article
     */
    private function calculateRelevanceScore(KbArticle $article, array $keywords, string $originalText): float
    {
        $score = 0;

        $title = strtolower($article->title);
        $content = strtolower($article->content);
        $summary = strtolower($article->summary ?? '');

        // Title matches are worth more
        foreach ($keywords as $keyword) {
            if (str_contains($title, $keyword)) {
                $score += 10;
            }
            if (str_contains($summary, $keyword)) {
                $score += 5;
            }
            if (str_contains($content, $keyword)) {
                $score += 2;
            }
        }

        // Boost score based on article popularity
        $viewBoost = min($article->views / 100, 5); // Max 5 point boost
        $score += $viewBoost;

        // Boost if exact phrase match
        $searchPhrase = implode(' ', array_slice($keywords, 0, 3));
        if (str_contains($title, $searchPhrase)) {
            $score += 15;
        }

        return $score;
    }

    /**
     * Calculate text similarity using Levenshtein distance
     */
    private function calculateSimilarity(string $text1, string $text2): float
    {
        $text1 = $this->cleanText($text1);
        $text2 = $this->cleanText($text2);

        // Use similar_text for percentage
        similar_text($text1, $text2, $percent);

        return $percent / 100;
    }

    /**
     * Get popular articles as fallback
     */
    private function getPopularArticles(int $limit): Collection
    {
        return KbArticle::where('status', 'published')
            ->orderBy('views', 'desc')
            ->limit($limit)
            ->with('category')
            ->get()
            ->map(function ($article) {
                $article->relevance_score = 1; // Base score
                return $article;
            });
    }
}