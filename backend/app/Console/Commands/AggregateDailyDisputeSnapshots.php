<?php

/**
 * V-DISPUTE-RISK-2026-009: Aggregate Daily Dispute Snapshots Command
 *
 * Creates immutable daily records of dispute and chargeback metrics.
 * Used for trend analysis, reporting, and regulatory compliance.
 *
 * Scheduled: Daily at 02:00 (captures full previous day data)
 */

namespace App\Console\Commands;

use App\Models\DailyDisputeSnapshot;
use App\Models\Dispute;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AggregateDailyDisputeSnapshots extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dispute:aggregate-snapshots
                            {--date= : Specific date to aggregate (YYYY-MM-DD format)}
                            {--force : Overwrite existing snapshot for the date}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Aggregate dispute and chargeback metrics into daily snapshots';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $snapshotDate = $this->option('date')
            ? \Carbon\Carbon::parse($this->option('date'))->toDateString()
            : now()->subDay()->toDateString(); // Default: yesterday

        $this->info("Aggregating dispute snapshots for {$snapshotDate}...");

        try {
            DB::transaction(function () use ($snapshotDate) {
                // Create platform-wide snapshot (null plan_id)
                $this->createSnapshot($snapshotDate, null);

                // Create per-plan snapshots
                $plans = Plan::where('is_active', true)->get();
                foreach ($plans as $plan) {
                    $this->createSnapshot($snapshotDate, $plan->id);
                }
            });

            $this->info("Snapshots created successfully for {$snapshotDate}");

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error("Failed to create snapshots: {$e->getMessage()}");
            Log::error('AggregateDailyDisputeSnapshots failed', [
                'date' => $snapshotDate,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return self::FAILURE;
        }
    }

    /**
     * Create a snapshot for a specific date and plan.
     *
     * @param string $snapshotDate
     * @param int|null $planId
     */
    protected function createSnapshot(string $snapshotDate, ?int $planId): void
    {
        // Check if snapshot already exists
        $existing = DailyDisputeSnapshot::where('snapshot_date', $snapshotDate)
            ->where('plan_id', $planId)
            ->first();

        if ($existing && !$this->option('force')) {
            $this->warn("Snapshot already exists for {$snapshotDate} (plan_id: " . ($planId ?? 'null') . "). Skipping.");
            return;
        }

        // Build dispute query (optionally filtered by plan)
        $start = \Carbon\Carbon::parse($snapshotDate)->startOfDay();
        $end   = \Carbon\Carbon::parse($snapshotDate)->endOfDay();

        $end = \Carbon\Carbon::parse($snapshotDate)->endOfDay();
        $disputeQuery = Dispute::where('opened_at', '<=', $end);

        // Count disputes by status (cumulative up to snapshot date)
        $statusCounts = (clone $disputeQuery)
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // Count disputes by severity
        $severityCounts = (clone $disputeQuery)
            ->select('severity', DB::raw('COUNT(*) as count'))
            ->groupBy('severity')
            ->pluck('count', 'severity')
            ->toArray();

        // Count disputes by category
        $categoryCounts = (clone $disputeQuery)
            ->select('category', DB::raw('COUNT(*) as count'))
            ->groupBy('category')
            ->pluck('count', 'category')
            ->toArray();

        // Build chargeback query (optionally filtered by plan via subscription)
        $chargebackQuery = Payment::where('chargeback_confirmed_at', '<=', $end);
        if ($planId) {
            $chargebackQuery->whereHas('subscription', function ($q) use ($planId) {
                $q->where('plan_id', $planId);
            });
        }

        // Count chargebacks
        $chargebackStats = (clone $chargebackQuery)
            ->where('status', Payment::STATUS_CHARGEBACK_CONFIRMED)
            ->select(
                DB::raw('COUNT(*) as count'),
                DB::raw('COALESCE(SUM(chargeback_amount_paise), 0) as total_amount')
            )
            ->first();

        // Pending chargebacks
        $pendingChargebacks = Payment::where('status', Payment::STATUS_CHARGEBACK_PENDING)
            ->where('created_at', '<=', $end)
            ->when($planId, function ($q) use ($planId) {
                $q->whereHas('subscription', function ($sq) use ($planId) {
                    $sq->where('plan_id', $planId);
                });
            })
            ->count();

        // Risk user counts
        $blockedUsers = User::where('is_blocked', true)->count();
        $highRiskThreshold = config('risk.thresholds.high_risk', 50);
        $highRiskUsers = User::where('risk_score', '>=', $highRiskThreshold)
            ->where('is_blocked', false)
            ->count();

        // Create or update snapshot
        $data = [
            'snapshot_date' => $snapshotDate,
            'plan_id' => $planId,
            'total_disputes' => array_sum($statusCounts),
            'open_disputes' => $statusCounts['open'] ?? 0,
            'under_investigation_disputes' => $statusCounts['under_investigation'] ?? 0,
            'resolved_disputes' => $statusCounts['resolved'] ?? 0,
            'escalated_disputes' => $statusCounts['escalated'] ?? 0,
            'total_chargeback_count' => ($chargebackStats->count ?? 0) + $pendingChargebacks,
            'total_chargeback_amount_paise' => (int) ($chargebackStats->total_amount ?? 0),
            'confirmed_chargeback_count' => $chargebackStats->count ?? 0,
            'confirmed_chargeback_amount_paise' => (int) ($chargebackStats->total_amount ?? 0),
            'low_severity_count' => $severityCounts['low'] ?? 0,
            'medium_severity_count' => $severityCounts['medium'] ?? 0,
            'high_severity_count' => $severityCounts['high'] ?? 0,
            'critical_severity_count' => $severityCounts['critical'] ?? 0,
            'category_breakdown' => $categoryCounts,
            'blocked_users_count' => $blockedUsers,
            'high_risk_users_count' => $highRiskUsers,
        ];

        if ($existing && $this->option('force')) {
            $existing->update($data);
            $this->info("Updated snapshot for {$snapshotDate} (plan_id: " . ($planId ?? 'null') . ")");
        } else {
            DailyDisputeSnapshot::create($data);
            $this->info("Created snapshot for {$snapshotDate} (plan_id: " . ($planId ?? 'null') . ")");
        }

        Log::info('DailyDisputeSnapshot created', [
            'snapshot_date' => $snapshotDate,
            'plan_id' => $planId,
            'total_disputes' => $data['total_disputes'],
            'confirmed_chargebacks' => $data['confirmed_chargeback_count'],
            'blocked_users' => $data['blocked_users_count'],
        ]);
    }
}
