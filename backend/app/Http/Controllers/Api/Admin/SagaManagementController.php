<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\{SagaExecution, Payment, User};
use App\Services\PaymentAllocationSaga;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Log};

/**
 * Saga Management Controller
 *
 * Admin dashboard for monitoring and manually resolving failed sagas
 */
class SagaManagementController extends Controller
{
    /**
     * List all sagas with filtering
     */
    public function index(Request $request)
    {
        $query = SagaExecution::query();

        // Filter by status
        if ($request->status) {
            $statuses = explode(',', $request->status);
            $query->whereIn('status', $statuses);
        } else {
            // Default: show problematic sagas
            $query->whereIn('status', [
                'processing',
                'failed',
                'compensation_failed',
                'requires_manual_resolution'
            ]);
        }

        // Filter by date range
        if ($request->from_date) {
            $query->where('initiated_at', '>=', $request->from_date);
        }
        if ($request->to_date) {
            $query->where('initiated_at', '<=', $request->to_date);
        }

        // Search by saga_id
        if ($request->search) {
            $query->where('saga_id', 'like', "%{$request->search}%");
        }

        $sagas = $query->orderBy('initiated_at', 'desc')
            ->paginate($request->per_page ?? 20);

        // Enrich with related data
        $sagas->getCollection()->transform(function ($saga) {
            return $this->enrichSagaData($saga);
        });

        return response()->json([
            'data' => $sagas,
            'stats' => $this->getSagaStats(),
        ]);
    }

    /**
     * Get specific saga details
     */
    public function show(SagaExecution $saga)
    {
        return response()->json([
            'data' => $this->enrichSagaData($saga, true),
        ]);
    }

    /**
     * Get saga statistics for dashboard
     */
    public function stats()
    {
        return response()->json([
            'data' => $this->getSagaStats(),
        ]);
    }

    /**
     * Retry failed saga
     */
    public function retry(SagaExecution $saga)
    {
        if (!in_array($saga->status, ['failed', 'compensation_failed'])) {
            return response()->json([
                'message' => "Cannot retry saga with status: {$saga->status}",
            ], 422);
        }

        // Get payment ID from metadata
        $paymentId = $saga->metadata['payment_id'] ?? null;
        if (!$paymentId) {
            return response()->json([
                'message' => 'Payment ID not found in saga metadata',
            ], 422);
        }

        $payment = Payment::find($paymentId);
        if (!$payment) {
            return response()->json([
                'message' => 'Payment not found',
            ], 404);
        }

        try {
            // Create new saga execution
            $sagaService = app(PaymentAllocationSaga::class);
            $newSaga = $sagaService->execute($payment);

            // Mark old saga as retried
            $saga->update([
                'resolution_data' => [
                    'retried' => true,
                    'retried_at' => now()->toDateTimeString(),
                    'retried_by' => auth()->id(),
                    'new_saga_id' => $newSaga->saga_id,
                ],
            ]);

            Log::info('Saga retried successfully', [
                'old_saga_id' => $saga->saga_id,
                'new_saga_id' => $newSaga->saga_id,
                'admin_id' => auth()->id(),
            ]);

            return response()->json([
                'message' => 'Saga retried successfully',
                'data' => [
                    'old_saga' => $saga->fresh(),
                    'new_saga' => $newSaga,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Saga retry failed', [
                'saga_id' => $saga->saga_id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Saga retry failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Manually mark saga as resolved
     */
    public function resolve(Request $request, SagaExecution $saga)
    {
        $request->validate([
            'resolution_notes' => 'required|string|min:50',
            'resolution_action' => 'required|in:refunded,compensated_manually,ignored,escalated',
        ]);

        DB::transaction(function () use ($saga, $request) {
            $saga->update([
                'status' => 'manually_resolved',
                'resolved_at' => now(),
                'resolved_by' => auth()->id(),
                'resolution_data' => [
                    'action' => $request->resolution_action,
                    'notes' => $request->resolution_notes,
                    'resolved_by_name' => auth()->user()->name ?? auth()->user()->username,
                    'resolved_at' => now()->toDateTimeString(),
                ],
            ]);

            // Log to audit
            \App\Models\AuditLog::create([
                'action' => 'saga.manually_resolved',
                'actor_id' => auth()->id(),
                'description' => "Manually resolved saga: {$saga->saga_id}",
                'metadata' => [
                    'saga_id' => $saga->saga_id,
                    'old_status' => $saga->getOriginal('status'),
                    'resolution_action' => $request->resolution_action,
                    'resolution_notes' => $request->resolution_notes,
                ],
            ]);
        });

        return response()->json([
            'message' => 'Saga marked as resolved',
            'data' => $saga->fresh(),
        ]);
    }

    /**
     * Force compensate saga
     */
    public function forceCompensate(SagaExecution $saga)
    {
        if (!in_array($saga->status, ['failed', 'compensation_failed'])) {
            return response()->json([
                'message' => "Cannot compensate saga with status: {$saga->status}",
            ], 422);
        }

        try {
            $sagaService = app(PaymentAllocationSaga::class);

            // Use reflection to call protected compensate method
            $reflection = new \ReflectionClass($sagaService);
            $method = $reflection->getMethod('compensate');
            $method->setAccessible(true);
            $method->invoke($sagaService, $saga);

            Log::info('Saga force compensated', [
                'saga_id' => $saga->saga_id,
                'admin_id' => auth()->id(),
            ]);

            return response()->json([
                'message' => 'Saga compensation completed',
                'data' => $saga->fresh(),
            ]);
        } catch (\Exception $e) {
            Log::error('Force compensation failed', [
                'saga_id' => $saga->saga_id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Force compensation failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Run saga recovery (find and flag incomplete sagas)
     */
    public function runRecovery()
    {
        try {
            $results = PaymentAllocationSaga::recoverIncompleteSagas();

            Log::info('Saga recovery completed', [
                'sagas_recovered' => count($results),
                'admin_id' => auth()->id(),
            ]);

            return response()->json([
                'message' => 'Saga recovery completed',
                'data' => [
                    'sagas_recovered' => count($results),
                    'results' => $results,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Saga recovery failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get payment details for saga
     */
    public function getPaymentDetails(SagaExecution $saga)
    {
        $paymentId = $saga->metadata['payment_id'] ?? null;
        if (!$paymentId) {
            return response()->json([
                'message' => 'Payment ID not found in saga metadata',
            ], 404);
        }

        $payment = Payment::with([
            'user.wallet',
            'subscription',
            'bonusTransactions',
            'userInvestments',
        ])->find($paymentId);

        if (!$payment) {
            return response()->json([
                'message' => 'Payment not found',
            ], 404);
        }

        return response()->json([
            'data' => $payment,
        ]);
    }

    /**
     * Get saga statistics
     */
    protected function getSagaStats(): array
    {
        $last24h = now()->subHours(24);
        $last7d = now()->subDays(7);
        $last30d = now()->subDays(30);

        return [
            'by_status' => [
                'processing' => SagaExecution::where('status', 'processing')->count(),
                'completed' => SagaExecution::where('status', 'completed')->count(),
                'failed' => SagaExecution::where('status', 'failed')->count(),
                'compensated' => SagaExecution::where('status', 'compensated')->count(),
                'compensation_failed' => SagaExecution::where('status', 'compensation_failed')->count(),
                'requires_manual_resolution' => SagaExecution::where('status', 'requires_manual_resolution')->count(),
                'manually_resolved' => SagaExecution::where('status', 'manually_resolved')->count(),
            ],
            'time_periods' => [
                'last_24h' => [
                    'total' => SagaExecution::where('initiated_at', '>=', $last24h)->count(),
                    'completed' => SagaExecution::where('initiated_at', '>=', $last24h)
                        ->where('status', 'completed')->count(),
                    'failed' => SagaExecution::where('initiated_at', '>=', $last24h)
                        ->where('status', 'failed')->count(),
                ],
                'last_7d' => [
                    'total' => SagaExecution::where('initiated_at', '>=', $last7d)->count(),
                    'completed' => SagaExecution::where('initiated_at', '>=', $last7d)
                        ->where('status', 'completed')->count(),
                    'failed' => SagaExecution::where('initiated_at', '>=', $last7d)
                        ->where('status', 'failed')->count(),
                ],
                'last_30d' => [
                    'total' => SagaExecution::where('initiated_at', '>=', $last30d)->count(),
                    'completed' => SagaExecution::where('initiated_at', '>=', $last30d)
                        ->where('status', 'completed')->count(),
                    'failed' => SagaExecution::where('initiated_at', '>=', $last30d)
                        ->where('status', 'failed')->count(),
                ],
            ],
            'success_rate' => [
                'last_24h' => $this->calculateSuccessRate($last24h),
                'last_7d' => $this->calculateSuccessRate($last7d),
                'last_30d' => $this->calculateSuccessRate($last30d),
            ],
            'needs_attention' => SagaExecution::whereIn('status', [
                'processing',
                'failed',
                'compensation_failed',
                'requires_manual_resolution',
            ])->count(),
        ];
    }

    /**
     * Calculate success rate for a time period
     */
    protected function calculateSuccessRate($from): float
    {
        $total = SagaExecution::where('initiated_at', '>=', $from)->count();
        if ($total === 0) {
            return 100.0;
        }

        $successful = SagaExecution::where('initiated_at', '>=', $from)
            ->where('status', 'completed')
            ->count();

        return round(($successful / $total) * 100, 2);
    }

    /**
     * Enrich saga data with related information
     */
    protected function enrichSagaData(SagaExecution $saga, bool $detailed = false): array
    {
        $data = $saga->toArray();

        // Add payment info
        $paymentId = $saga->metadata['payment_id'] ?? null;
        if ($paymentId) {
            $payment = Payment::find($paymentId);
            $data['payment'] = $payment ? [
                'id' => $payment->id,
                'amount' => $payment->amount,
                'status' => $payment->status,
                'gateway_payment_id' => $payment->gateway_payment_id,
            ] : null;
        }

        // Add user info
        $userId = $saga->metadata['user_id'] ?? null;
        if ($userId) {
            $user = User::find($userId);
            $data['user'] = $user ? [
                'id' => $user->id,
                'username' => $user->username,
                'email' => $user->email,
            ] : null;
        }

        // Add resolver info
        if ($saga->resolved_by) {
            $resolver = User::find($saga->resolved_by);
            $data['resolved_by_user'] = $resolver ? [
                'id' => $resolver->id,
                'name' => $resolver->name ?? $resolver->username,
            ] : null;
        }

        // Add computed fields
        $data['duration'] = $this->calculateSagaDuration($saga);
        $data['steps_progress'] = "{$saga->steps_completed}/{$saga->steps_total}";
        $data['needs_attention'] = in_array($saga->status, [
            'processing',
            'failed',
            'compensation_failed',
            'requires_manual_resolution',
        ]);

        // If detailed view, add more information
        if ($detailed) {
            $data['completed_steps'] = $saga->metadata['steps'] ?? [];
            $data['timeline'] = $this->buildSagaTimeline($saga);
        }

        return $data;
    }

    /**
     * Calculate saga duration
     */
    protected function calculateSagaDuration(SagaExecution $saga): ?string
    {
        if (!$saga->initiated_at) {
            return null;
        }

        $end = $saga->completed_at ?? $saga->failed_at ?? $saga->compensated_at ?? now();
        $seconds = $saga->initiated_at->diffInSeconds($end);

        if ($seconds < 60) {
            return "{$seconds}s";
        } elseif ($seconds < 3600) {
            return round($seconds / 60, 1) . "m";
        } else {
            return round($seconds / 3600, 1) . "h";
        }
    }

    /**
     * Build saga timeline for visualization
     */
    protected function buildSagaTimeline(SagaExecution $saga): array
    {
        $timeline = [];

        // Initiated
        $timeline[] = [
            'event' => 'Saga Initiated',
            'timestamp' => $saga->initiated_at?->toDateTimeString(),
            'status' => 'info',
        ];

        // Completed steps
        $steps = $saga->metadata['steps'] ?? [];
        foreach ($steps as $stepName => $stepData) {
            $timeline[] = [
                'event' => 'Step Completed: ' . ucfirst(str_replace('_', ' ', $stepName)),
                'timestamp' => $stepData['completed_at'] ?? null,
                'status' => 'success',
                'data' => $stepData,
            ];
        }

        // Failed
        if ($saga->failed_at) {
            $timeline[] = [
                'event' => 'Saga Failed',
                'timestamp' => $saga->failed_at->toDateTimeString(),
                'status' => 'error',
                'reason' => $saga->failure_reason,
            ];
        }

        // Compensated
        if ($saga->compensated_at) {
            $timeline[] = [
                'event' => 'Compensation Completed',
                'timestamp' => $saga->compensated_at->toDateTimeString(),
                'status' => 'warning',
            ];
        }

        // Completed
        if ($saga->completed_at) {
            $timeline[] = [
                'event' => 'Saga Completed',
                'timestamp' => $saga->completed_at->toDateTimeString(),
                'status' => 'success',
            ];
        }

        // Resolved
        if ($saga->resolved_at) {
            $timeline[] = [
                'event' => 'Manually Resolved',
                'timestamp' => $saga->resolved_at->toDateTimeString(),
                'status' => 'info',
                'data' => $saga->resolution_data,
            ];
        }

        return $timeline;
    }
}
