<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\KbArticle;
use App\Models\ArticleFeedback;
use App\Models\KbArticleView;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HelpCenterDashboardController extends Controller
{
    /**
     * High-level statistics for the dashboard widgets.
     */
    public function stats()
    {
        $totalViews = KbArticleView::count();
        $totalVotes = ArticleFeedback::count();
        
        $helpfulVotes = ArticleFeedback::where('is_helpful', true)->count();
        $helpfulPercentage = $totalVotes > 0 ? round(($helpfulVotes / $totalVotes) * 100) : 0;

        return response()->json([
            'total_views' => $totalViews,
            'total_feedback' => $totalVotes,
            'satisfaction_rate' => $helpfulPercentage,
            'views_today' => KbArticleView::whereDate('created_at', today())->count(),
        ]);
    }

    /**
     * List of recent feedback with user details (Who voted/commented).
     */
    public function feedback(Request $request)
    {
        $feedback = ArticleFeedback::with(['article:id,title,slug', 'user:id,name,email']) // Eager load article and user
            ->latest()
            ->paginate(20);

        return response()->json($feedback);
    }

    /**
     * List of recent traffic/visits (Who visited).
     */
    public function visits(Request $request)
    {
        $visits = KbArticleView::with(['article:id,title', 'user:id,name,email'])
            ->latest()
            ->paginate(20);

        return response()->json($visits);
    }

    /**
     * Articles that need attention (High views, low helpfulness).
     */
    public function needsAttention()
    {
        // Articles with > 10 votes and < 60% helpfulness
        $problematic = KbArticle::withCount(['feedback as total_votes', 'feedback as helpful_votes' => function ($query) {
                $query->where('is_helpful', true);
            }])
            ->having('total_votes', '>', 5) // Threshold
            ->get()
            ->filter(function ($article) {
                $rate = ($article->helpful_votes / $article->total_votes) * 100;
                return $rate < 60;
            })
            ->values(); // Reset keys

        return response()->json($problematic);
    }
}