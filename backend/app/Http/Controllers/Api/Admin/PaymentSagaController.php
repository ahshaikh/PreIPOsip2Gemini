<?php
/**
 * Payment Saga Monitoring API Controller
 *
 * Provides endpoints for monitoring and managing payment sagas.
 * Part of FIX 44, 45: Payment Saga Tracking and Rollback implementation.
 */

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\PaymentSaga;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentSagaController extends Controller
{
    /**
     * Get all payment sagas with filtering
     * GET /api/v1/admin/payment-sagas
     */
    public function index(Request $request)
    {
        $query = PaymentSaga::with(['payment:id,amount,status', 'user:id,name,email'])
            ->orderBy('created_at', 'desc');

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by saga type
        if ($request->has('saga_type')) {
            $query->where('saga_type', $request->saga_type);
        }

        // Filter by payment
        if ($request->has('payment_id')) {
            $query->where('payment_id', $request->payment_id);
        }

        // Filter by user
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filter failed sagas only
        if ($request->has('failed_only') && $request->failed_only) {
            $query->where('status', 'failed');
        }

        // Filter in-progress sagas only
        if ($request->has('in_progress_only') && $request->in_progress_only) {
            $query->whereIn('status', ['pending', 'in_progress', 'rolling_back']);
        }

        // Filter by date range
        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('failure_reason', 'like', "%{$search}%")
                  ->orWhere('failed_step', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($userQuery) use ($search) {
                      $userQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        $sagas = $query->paginate($request->get('per_page', 50));

        // Add computed fields to each saga
        $sagas->getCollection()->transform(function ($saga) {
            $saga->progress_percentage = $saga->getProgressPercentage();
            $saga->is_in_progress = $saga->isInProgress();
            $saga->can_rollback = $saga->canRollback();
            return $saga;
        });

        // Get statistics
        $stats = [
            'total_sagas' => PaymentSaga::count(),
            'pending' => PaymentSaga::where('status', 'pending')->count(),
            'in_progress' => PaymentSaga::where('status', 'in_progress')->count(),
            'completed' => PaymentSaga::where('status', 'completed')->count(),
            'failed' => PaymentSaga::where('status', 'failed')->count(),
            'rolled_back' => PaymentSaga::where('status', 'rolled_back')->count(),
            'active_sagas' => PaymentSaga::whereIn('status', ['pending', 'in_progress', 'rolling_back'])->count(),
            'failed_today' => PaymentSaga::where('status', 'failed')
                ->whereDate('failed_at', today())
                ->count(),
            'success_rate' => 0,
        ];

        // Calculate success rate
        $total = PaymentSaga::whereIn('status', ['completed', 'failed'])->count();
        if ($total > 0) {
            $stats['success_rate'] = round(($stats['completed'] / $total) * 100, 2);
        }

        return response()->json([
            'sagas' => $sagas,
            'stats' => $stats,
        ]);
    }

    /**
     * Get active sagas (in progress)
     * GET /api/v1/admin/payment-sagas/active
     */
    public function active(Request $request)
    {
        $sagas = PaymentSaga::with(['payment:id,amount,status,user_id', 'user:id,name,email'])
            ->whereIn('status', ['pending', 'in_progress', 'rolling_back'])
            ->orderBy('started_at', 'desc')
            ->get();

        // Add computed fields
        $sagas->transform(function ($saga) {
            $saga->progress_percentage = $saga->getProgressPercentage();
            $saga->duration_minutes = $saga->started_at?->diffInMinutes(now());
            return $saga;
        });

        return response()->json([
            'active_sagas' => $sagas,
            'total_active' => $sagas->count(),
        ]);
    }

    /**
     * Get failed sagas
     * GET /api/v1/admin/payment-sagas/failed
     */
    public function failed(Request $request)
    {
        $query = PaymentSaga::with(['payment:id,amount,status', 'user:id,name,email'])
            ->where('status', 'failed')
            ->orderBy('failed_at', 'desc');

        // Filter by can rollback
        if ($request->has('can_rollback') && $request->can_rollback) {
            $query->whereNotNull('completed_steps');
        }

        $sagas = $query->paginate($request->get('per_page', 50));

        // Add computed fields
        $sagas->getCollection()->transform(function ($saga) {
            $saga->can_rollback = $saga->canRollback();
            $saga->completed_step_count = count($saga->completed_steps ?? []);
            return $saga;
        });

        return response()->json([
            'failed_sagas' => $sagas,
        ]);
    }

    /**
     * Get specific saga details
     * GET /api/v1/admin/payment-sagas/{saga}
     */
    public function show(PaymentSaga $saga)
    {
        $saga->load(['payment', 'user']);

        // Add computed fields
        $saga->progress_percentage = $saga->getProgressPercentage();
        $saga->is_in_progress = $saga->isInProgress();
        $saga->can_rollback = $saga->canRollback();
        $saga->is_complete = $saga->isComplete();

        // Calculate duration
        if ($saga->started_at) {
            $endTime = $saga->completed_at ?? $saga->failed_at ?? $saga->rolled_back_at ?? now();
            $saga->duration_minutes = $saga->started_at->diffInMinutes($endTime);
        }

        return response()->json([
            'saga' => $saga,
        ]);
    }

    /**
     * Get sagas for a specific payment
     * GET /api/v1/admin/payments/{payment}/sagas
     */
    public function paymentSagas(Payment $payment)
    {
        $sagas = $payment->sagas()
            ->orderBy('created_at', 'desc')
            ->get();

        // Add computed fields
        $sagas->transform(function ($saga) {
            $saga->progress_percentage = $saga->getProgressPercentage();
            $saga->can_rollback = $saga->canRollback();
            return $saga;
        });

        return response()->json([
            'payment' => $payment->only(['id', 'amount', 'status', 'gateway']),
            'sagas' => $sagas,
            'total_sagas' => $sagas->count(),
        ]);
    }

    /**
     * Trigger rollback for a failed saga
     * POST /api/v1/admin/payment-sagas/{saga}/rollback
     */
    public function rollback(PaymentSaga $saga)
    {
        if (!$saga->canRollback()) {
            return response()->json([
                'error' => 'Saga cannot be rolled back',
                'reason' => $saga->status !== 'failed' ? 'Saga status must be failed' : 'No completed steps to rollback',
                'current_status' => $saga->status,
            ], 400);
        }

        try {
            $saga->rollback();

            return response()->json([
                'message' => 'Saga rollback initiated successfully',
                'saga' => $saga->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to rollback saga',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get saga execution timeline
     * GET /api/v1/admin/payment-sagas/{saga}/timeline
     */
    public function timeline(PaymentSaga $saga)
    {
        $timeline = [];

        // Started event
        if ($saga->started_at) {
            $timeline[] = [
                'timestamp' => $saga->started_at->toISOString(),
                'event' => 'saga_started',
                'description' => 'Saga started',
            ];
        }

        // Completed steps
        foreach ($saga->completed_steps ?? [] as $step) {
            $timeline[] = [
                'timestamp' => $step['completed_at'] ?? null,
                'event' => 'step_completed',
                'step_name' => $step['step'],
                'description' => "Step completed: {$step['step']}",
                'data' => $step['data'] ?? null,
            ];
        }

        // Failed event
        if ($saga->failed_at) {
            $timeline[] = [
                'timestamp' => $saga->failed_at->toISOString(),
                'event' => 'saga_failed',
                'step_name' => $saga->failed_step,
                'description' => "Saga failed at step: {$saga->failed_step}",
                'reason' => $saga->failure_reason,
            ];
        }

        // Rollback steps
        foreach ($saga->rollback_steps ?? [] as $rollbackStep) {
            $timeline[] = [
                'timestamp' => $rollbackStep['rolled_back_at'] ?? null,
                'event' => 'step_rolled_back',
                'step_name' => $rollbackStep['step'],
                'description' => "Step rolled back: {$rollbackStep['step']}",
                'success' => $rollbackStep['success'] ?? null,
                'error' => $rollbackStep['error'] ?? null,
            ];
        }

        // Completed/RolledBack event
        if ($saga->completed_at) {
            $timeline[] = [
                'timestamp' => $saga->completed_at->toISOString(),
                'event' => 'saga_completed',
                'description' => 'Saga completed successfully',
            ];
        } elseif ($saga->rolled_back_at) {
            $timeline[] = [
                'timestamp' => $saga->rolled_back_at->toISOString(),
                'event' => 'saga_rolled_back',
                'description' => 'Saga fully rolled back',
            ];
        }

        // Sort timeline by timestamp
        usort($timeline, function ($a, $b) {
            return strtotime($a['timestamp'] ?? 0) - strtotime($b['timestamp'] ?? 0);
        });

        return response()->json([
            'saga' => $saga->only(['id', 'saga_type', 'status']),
            'timeline' => $timeline,
        ]);
    }

    /**
     * Get saga analytics and statistics
     * GET /api/v1/admin/payment-sagas/analytics
     */
    public function analytics(Request $request)
    {
        $dateFrom = $request->get('date_from', now()->subDays(30));
        $dateTo = $request->get('date_to', now());

        $analytics = [
            // Overall metrics
            'total_sagas' => PaymentSaga::whereBetween('created_at', [$dateFrom, $dateTo])->count(),
            'completed_sagas' => PaymentSaga::where('status', 'completed')
                ->whereBetween('completed_at', [$dateFrom, $dateTo])
                ->count(),
            'failed_sagas' => PaymentSaga::where('status', 'failed')
                ->whereBetween('failed_at', [$dateFrom, $dateTo])
                ->count(),
            'rolled_back_sagas' => PaymentSaga::where('status', 'rolled_back')
                ->whereBetween('rolled_back_at', [$dateFrom, $dateTo])
                ->count(),

            // Success rate
            'success_rate' => 0,
            'rollback_success_rate' => 0,

            // Time metrics
            'avg_completion_time_minutes' => PaymentSaga::where('status', 'completed')
                ->whereNotNull('started_at')
                ->whereNotNull('completed_at')
                ->whereBetween('completed_at', [$dateFrom, $dateTo])
                ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, started_at, completed_at)) as avg_minutes')
                ->value('avg_minutes'),

            // Failure analysis
            'by_failed_step' => PaymentSaga::where('status', 'failed')
                ->whereNotNull('failed_step')
                ->whereBetween('failed_at', [$dateFrom, $dateTo])
                ->select('failed_step', DB::raw('count(*) as count'))
                ->groupBy('failed_step')
                ->orderBy('count', 'desc')
                ->pluck('count', 'failed_step'),

            // Saga type breakdown
            'by_saga_type' => PaymentSaga::whereBetween('created_at', [$dateFrom, $dateTo])
                ->select('saga_type', DB::raw('count(*) as count'))
                ->groupBy('saga_type')
                ->pluck('count', 'saga_type'),

            // Daily trends
            'daily_trends' => PaymentSaga::whereBetween('created_at', [$dateFrom, $dateTo])
                ->selectRaw('DATE(created_at) as date, status, COUNT(*) as count')
                ->groupBy('date', 'status')
                ->orderBy('date', 'asc')
                ->get()
                ->groupBy('date'),
        ];

        // Calculate success rate
        $totalCompleted = $analytics['completed_sagas'] + $analytics['failed_sagas'];
        if ($totalCompleted > 0) {
            $analytics['success_rate'] = round(($analytics['completed_sagas'] / $totalCompleted) * 100, 2);
        }

        // Calculate rollback success rate
        $totalRollbacks = PaymentSaga::where('status', 'rolled_back')
            ->whereBetween('rolled_back_at', [$dateFrom, $dateTo])
            ->count();

        if ($totalRollbacks > 0) {
            $successfulRollbacks = PaymentSaga::where('status', 'rolled_back')
                ->whereJsonDoesntContain('rollback_steps', ['success' => false])
                ->whereBetween('rolled_back_at', [$dateFrom, $dateTo])
                ->count();

            $analytics['rollback_success_rate'] = round(($successfulRollbacks / $totalRollbacks) * 100, 2);
        }

        return response()->json([
            'analytics' => $analytics,
            'period' => [
                'from' => $dateFrom,
                'to' => $dateTo,
            ],
        ]);
    }
}
