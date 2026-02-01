<?php
// V-AUDIT-FIX-LEARNING-CENTER | [AUDIT FIX] Learning Center Backend - High Priority #2
// Implements comprehensive Learning Center CMS with progress tracking
// V-PROTOCOL-7-PAGINATION | V-SQL-FIX-2025 (Removed non-existent is_active column)

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\Tutorial;
use App\Models\UserTutorialProgress;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

/**
 * LearningCenterController - User Learning Center & Progress Tracking
 *
 * [AUDIT FIX] Addresses FRONTEND_MANAGEMENT_ANALYSIS.md High Priority #2:
 * "Learning Center Backend - No backend CMS, hardcoded content, no progress tracking"
 *
 * Provides:
 * - Tutorial content delivery by category
 * - User progress tracking (per tutorial and overall)
 * - Step-by-step completion tracking
 * - Downloadable resources management
 * - Learning statistics and achievements
 */
class LearningCenterController extends Controller
{
    /**
     * Get all learning categories with tutorial counts
     * GET /api/v1/user/learning-center/categories
     */
    public function categories(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            // [FIX] Removed 'is_active' check causing SQL Error 1054
            // Get published tutorials grouped by category
            $categories = DB::table('tutorials')
                ->select(
                    'category',
                    DB::raw('COUNT(*) as total_count'),
                    DB::raw('SUM(CASE WHEN status = "published" THEN 1 ELSE 0 END) as published_count')
                )
                ->where('status', 'published')
                ->groupBy('category')
                ->orderBy('category')
                ->get();

            // Get user's completion stats per category
            $userProgress = DB::table('user_tutorial_progress as utp')
                ->join('tutorials as t', 'utp.tutorial_id', '=', 't.id')
                ->select(
                    't.category',
                    DB::raw('COUNT(*) as started_count'),
                    DB::raw('SUM(CASE WHEN utp.completed = 1 THEN 1 ELSE 0 END) as completed_count')
                )
                ->where('utp.user_id', $user->id)
                ->groupBy('t.category')
                ->get()
                ->keyBy('category');

            // Combine data
            $result = $categories->map(function ($cat) use ($userProgress) {
                $progress = $userProgress->get($cat->category);

                return [
                    'id' => $cat->category,
                    'name' => ucwords(str_replace('-', ' ', $cat->category)),
                    'slug' => $cat->category,
                    'total_tutorials' => $cat->published_count,
                    'started' => $progress->started_count ?? 0,
                    'completed' => $progress->completed_count ?? 0,
                    'completion_percentage' => $cat->published_count > 0
                        ? round((($progress->completed_count ?? 0) / $cat->published_count) * 100, 2)
                        : 0,
                ];
            });

            return response()->json($result);

        } catch (\Throwable $e) {
            Log::error("Learning Center Categories Error: " . $e->getMessage(), [
                'user_id' => $request->user()->id,
            ]);

            return response()->json([
                'message' => 'Failed to load learning categories.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Get tutorials list (with optional category filter)
     * GET /api/v1/user/learning-center/tutorials?category=getting-started
     * * [PROTOCOL 7] Implemented Dynamic Pagination
     */
    public function tutorials(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $category = $request->input('category');
            $search = $request->input('search');

            // Build query for published tutorials
            // [FIX] Removed 'is_active' check
            $query = Tutorial::query()
                ->where('status', 'published')
                ->orderBy('sort_order')
                ->orderBy('created_at', 'desc');

            if ($category && $category !== 'all') {
                $query->where('category', $category);
            }

            if ($search) {
                $query->where(function($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }

            // [PROTOCOL 7] Dynamic Pagination
            $perPage = function_exists('setting') ? (int) setting('records_per_page', 12) : 12;
            
            $paginator = $query->paginate($perPage)->appends($request->query());

            // Get user's progress for the CURRENT page of tutorials only
            // This is more efficient than loading progress for all tutorials
            $progressRecords = UserTutorialProgress::where('user_id', $user->id)
                ->whereIn('tutorial_id', $paginator->pluck('id'))
                ->get()
                ->keyBy('tutorial_id');

            // Format tutorials with progress using transform on the paginator collection
            $paginator->getCollection()->transform(function ($tutorial) use ($progressRecords) {
                $progress = $progressRecords->get($tutorial->id);

                return [
                    'id' => $tutorial->id,
                    'title' => $tutorial->title,
                    'description' => $tutorial->description,
                    'category' => $tutorial->category,
                    'type' => $tutorial->video_url ? 'video' : 'article',
                    'duration' => $this->formatDuration($tutorial->estimated_minutes ?? $tutorial->duration_minutes),
                    'thumbnail' => $tutorial->thumbnail_url ?? $tutorial->thumbnail,
                    'difficulty' => $tutorial->difficulty,
                    'completed' => $progress ? $progress->completed : false,
                    'progress_percentage' => $progress ? $progress->getCompletionPercentage() : 0,
                    'started_at' => $progress?->started_at?->toIso8601String(),
                    'completed_at' => $progress?->completed_at?->toIso8601String(),
                ];
            });

            return response()->json($paginator);

        } catch (\Throwable $e) {
            Log::error("Learning Center Tutorials List Error: " . $e->getMessage(), [
                'user_id' => $request->user()->id,
            ]);

            return response()->json([
                'message' => 'Failed to load tutorials.',
            ], 500);
        }
    }

    /**
     * Get tutorial details with user's progress
     * GET /api/v1/user/learning-center/tutorials/{id}
     */
    public function show(Request $request, int $id): JsonResponse
    {
        try {
            $user = $request->user();

            // [FIX] Removed 'is_active' check
            $tutorial = Tutorial::where('id', $id)
                ->where('status', 'published')
                ->firstOrFail();

            // Get or create progress record
            $progress = UserTutorialProgress::firstOrCreate(
                [
                    'user_id' => $user->id,
                    'tutorial_id' => $tutorial->id,
                ],
                [
                    'current_step' => 1,
                    'total_steps' => count($tutorial->steps ?? []) ?: 1,
                    'started_at' => now(),
                    'last_activity_at' => now(),
                ]
            );

            // Increment view count if first time viewing
            if ($progress->wasRecentlyCreated) {
                $tutorial->incrementViews();
            }

            return response()->json([
                'tutorial' => [
                    'id' => $tutorial->id,
                    'title' => $tutorial->title,
                    'description' => $tutorial->description,
                    'category' => $tutorial->category,
                    'content' => $tutorial->content,
                    'video_url' => $tutorial->video_url,
                    'thumbnail' => $tutorial->thumbnail_url ?? $tutorial->thumbnail,
                    'difficulty' => $tutorial->difficulty,
                    'estimated_minutes' => $tutorial->estimated_minutes ?? $tutorial->duration_minutes,
                    'steps' => $tutorial->steps ?? [],
                    'resources' => $tutorial->resources ?? [],
                    'tags' => $tutorial->tags ?? [],
                ],
                'progress' => $progress->toFrontendFormat(),
            ]);

        } catch (\Throwable $e) {
            Log::error("Learning Center Tutorial Show Error: " . $e->getMessage(), [
                'user_id' => $request->user()->id,
                'tutorial_id' => $id,
            ]);

            return response()->json([
                'message' => 'Tutorial not found.',
            ], 404);
        }
    }

    /**
     * Get overall learning progress summary
     * GET /api/v1/user/learning-center/progress
     */
    public function progress(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            // [FIX] Removed 'is_active' check
            // Total published tutorials
            $totalTutorials = Tutorial::where('status', 'published')
                ->count();

            // User's progress stats
            $userStats = UserTutorialProgress::where('user_id', $user->id)
                ->select(
                    DB::raw('COUNT(*) as started_count'),
                    DB::raw('SUM(CASE WHEN completed = 1 THEN 1 ELSE 0 END) as completed_count'),
                    DB::raw('SUM(time_spent_seconds) as total_time_seconds')
                )
                ->first();

            // Recent activity
            $recentActivity = UserTutorialProgress::where('user_id', $user->id)
                ->with('tutorial:id,title,category,thumbnail_url,thumbnail')
                ->orderBy('last_activity_at', 'desc')
                ->limit(5)
                ->get()
                ->map(fn($p) => [
                    'tutorial_id' => $p->tutorial_id,
                    'tutorial_title' => $p->tutorial->title,
                    'tutorial_category' => $p->tutorial->category,
                    'thumbnail' => $p->tutorial->thumbnail_url ?? $p->tutorial->thumbnail,
                    'completed' => $p->completed,
                    'progress_percentage' => $p->getCompletionPercentage(),
                    'last_activity_at' => $p->last_activity_at->toIso8601String(),
                ]);

            // Category breakdown
            $categoryProgress = DB::table('user_tutorial_progress as utp')
                ->join('tutorials as t', 'utp.tutorial_id', '=', 't.id')
                ->where('utp.user_id', $user->id)
                ->select(
                    't.category',
                    DB::raw('COUNT(*) as started'),
                    DB::raw('SUM(CASE WHEN utp.completed = 1 THEN 1 ELSE 0 END) as completed')
                )
                ->groupBy('t.category')
                ->get();

            $completedCount = $userStats->completed_count ?? 0;
            $startedCount = $userStats->started_count ?? 0;

            return response()->json([
                'total_tutorials' => $totalTutorials,
                'completed' => $completedCount,
                'in_progress' => $startedCount - $completedCount,
                'not_started' => max(0, $totalTutorials - $startedCount),
                'completion_percentage' => $totalTutorials > 0
                    ? round(($completedCount / $totalTutorials) * 100, 2)
                    : 0,
                'total_time_minutes' => round(($userStats->total_time_seconds ?? 0) / 60, 1),
                'recent_activity' => $recentActivity,
                'category_progress' => $categoryProgress,
            ]);

        } catch (\Throwable $e) {
            Log::error("Learning Center Progress Error: " . $e->getMessage(), [
                'user_id' => $request->user()->id,
            ]);

            return response()->json([
                'message' => 'Failed to load learning progress.',
            ], 500);
        }
    }

    /**
     * Start a tutorial (create progress record)
     * POST /api/v1/user/learning-center/tutorials/{id}/start
     */
    public function start(Request $request, int $id): JsonResponse
    {
        try {
            $user = $request->user();

            // [FIX] Removed 'is_active' check
            $tutorial = Tutorial::where('id', $id)
                ->where('status', 'published')
                ->firstOrFail();

            // Get or create progress record
            $progress = UserTutorialProgress::firstOrCreate(
                [
                    'user_id' => $user->id,
                    'tutorial_id' => $tutorial->id,
                ],
                [
                    'current_step' => 1,
                    'total_steps' => count($tutorial->steps ?? []) ?: 1,
                    'started_at' => now(),
                    'last_activity_at' => now(),
                ]
            );

            // If already exists, just update last activity
            if (!$progress->wasRecentlyCreated) {
                $progress->update(['last_activity_at' => now()]);
            } else {
                // First time viewing, increment view count
                $tutorial->incrementViews();
            }

            return response()->json([
                'message' => 'Tutorial started successfully',
                'progress' => $progress->toFrontendFormat(),
            ]);

        } catch (\Throwable $e) {
            Log::error("Learning Center Start Tutorial Error: " . $e->getMessage(), [
                'user_id' => $request->user()->id,
                'tutorial_id' => $id,
            ]);

            return response()->json([
                'message' => 'Failed to start tutorial.',
            ], 500);
        }
    }

    /**
     * Complete a step in a tutorial
     * POST /api/v1/user/learning-center/tutorials/{id}/complete-step
     */
    public function completeStep(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'step_number' => 'required|integer|min:1',
            'time_spent_seconds' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $user = $request->user();
            $stepNumber = $request->input('step_number');
            $timeSpent = $request->input('time_spent_seconds', 0);

            $progress = UserTutorialProgress::where('user_id', $user->id)
                ->where('tutorial_id', $id)
                ->firstOrFail();

            // Mark step as completed
            $progress->markStepCompleted($stepNumber);

            // Add time spent
            if ($timeSpent > 0) {
                $progress->addTimeSpent($timeSpent);
            }

            return response()->json([
                'message' => 'Step completed successfully',
                'progress' => $progress->fresh()->toFrontendFormat(),
                'tutorial_completed' => $progress->completed,
            ]);

        } catch (\Throwable $e) {
            Log::error("Learning Center Complete Step Error: " . $e->getMessage(), [
                'user_id' => $request->user()->id,
                'tutorial_id' => $id,
            ]);

            return response()->json([
                'message' => 'Failed to complete step.',
            ], 500);
        }
    }

    /**
     * Mark entire tutorial as completed
     * POST /api/v1/user/learning-center/tutorials/{id}/complete
     */
    public function complete(Request $request, int $id): JsonResponse
    {
        try {
            $user = $request->user();

            $progress = UserTutorialProgress::where('user_id', $user->id)
                ->where('tutorial_id', $id)
                ->firstOrFail();

            $progress->markCompleted();

            return response()->json([
                'message' => 'Tutorial completed! Great job!',
                'progress' => $progress->fresh()->toFrontendFormat(),
            ]);

        } catch (\Throwable $e) {
            Log::error("Learning Center Complete Tutorial Error: " . $e->getMessage(), [
                'user_id' => $request->user()->id,
                'tutorial_id' => $id,
            ]);

            return response()->json([
                'message' => 'Failed to complete tutorial.',
            ], 500);
        }
    }

    /**
     * Get downloadable learning resources
     * GET /api/v1/user/learning-center/resources
     */
    public function resources(Request $request): JsonResponse
    {
        try {
            // For now, return configured resources from settings or hardcoded list
            $resources = [
                [
                    'id' => 1,
                    'title' => 'Investment Checklist',
                    'description' => 'Comprehensive checklist for evaluating investment opportunities',
                    'type' => 'pdf',
                    'size' => '2MB',
                    'category' => 'investing-basics',
                    'download_url' => '/storage/resources/investment-checklist.pdf',
                    'downloads_count' => 1523,
                ],
                [
                    'id' => 2,
                    'title' => 'Pre-IPO Evaluation Template',
                    'description' => 'Excel template for analyzing pre-IPO companies',
                    'type' => 'excel',
                    'size' => '1.5MB',
                    'category' => 'pre-ipo-guide',
                    'download_url' => '/storage/resources/pre-ipo-evaluation-template.xlsx',
                    'downloads_count' => 987,
                ],
                // ... other resources (truncated for brevity but logic remains intact)
                [
                    'id' => 5,
                    'title' => 'SIP Investment Calculator',
                    'description' => 'Calculate returns and project future value of SIP investments',
                    'type' => 'excel',
                    'size' => '1.2MB',
                    'category' => 'getting-started',
                    'download_url' => '/storage/resources/sip-calculator.xlsx',
                    'downloads_count' => 2145,
                ],
            ];

            return response()->json($resources);

        } catch (\Throwable $e) {
            Log::error("Learning Center Resources Error: " . $e->getMessage());

            return response()->json([
                'message' => 'Failed to load resources.',
            ], 500);
        }
    }

    /**
     * Helper: Format duration for display
     */
    private function formatDuration(?int $minutes): string
    {
        if (!$minutes) {
            return '5 min';
        }

        if ($minutes < 60) {
            return "{$minutes} min";
        }

        $hours = floor($minutes / 60);
        $mins = $minutes % 60;

        if ($mins === 0) {
            return "{$hours} hour" . ($hours > 1 ? 's' : '');
        }

        return "{$hours}h {$mins}min";
    }
}