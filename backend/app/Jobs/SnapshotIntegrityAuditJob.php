<?php
/**
 * V-AUDIT-FIX-2026: Snapshot Integrity Audit Job
 *
 * PURPOSE:
 * Scheduled job to verify integrity of all investment snapshots.
 * Detects tampering by recomputing hashes and comparing against stored values.
 *
 * ALGORITHM:
 * For each snapshot:
 * 1. Load disclosure_snapshot JSON
 * 2. For each disclosure, recompute SHA-256 hash of data
 * 3. Compare against stored version_hash
 * 4. Alert if mismatch detected
 *
 * SCHEDULE:
 * Recommended: Run daily via Laravel scheduler
 * Can also be run on-demand for specific companies
 *
 * ALERTS:
 * - Tampering logged as CRITICAL
 * - Notification sent to admin channel
 * - Creates admin_alert record
 */

namespace App\Jobs;

use App\Models\InvestmentDisclosureSnapshot;
use App\Services\InvestmentSnapshotService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SnapshotIntegrityAuditJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1; // Don't retry - audit is read-only
    public $timeout = 600; // 10 minutes max

    protected ?int $companyId;
    protected ?int $snapshotId;

    /**
     * Create a new job instance.
     *
     * @param int|null $companyId Verify snapshots for specific company (null = all)
     * @param int|null $snapshotId Verify specific snapshot (null = all)
     */
    public function __construct(?int $companyId = null, ?int $snapshotId = null)
    {
        $this->companyId = $companyId;
        $this->snapshotId = $snapshotId;
    }

    /**
     * Execute the job.
     */
    public function handle(InvestmentSnapshotService $snapshotService): void
    {
        $startTime = now();
        Log::info('[SNAPSHOT INTEGRITY AUDIT] Job started', [
            'company_id' => $this->companyId ?? 'ALL',
            'snapshot_id' => $this->snapshotId ?? 'ALL',
            'started_at' => $startTime->toIso8601String(),
        ]);

        try {
            if ($this->snapshotId) {
                // Verify specific snapshot
                $results = [$snapshotService->verifySnapshotIntegrity($this->snapshotId)];
            } elseif ($this->companyId) {
                // Verify all snapshots for company
                $companyResult = $snapshotService->verifyAllSnapshotsForCompany($this->companyId);
                $results = [$companyResult];
            } else {
                // Verify all snapshots (batch processing)
                $results = $this->verifyAllSnapshots($snapshotService);
            }

            // Analyze and alert
            $this->analyzeAndAlert($results, $startTime);

        } catch (\Exception $e) {
            Log::critical('[SNAPSHOT INTEGRITY AUDIT] Job failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Verify all snapshots in batches.
     */
    protected function verifyAllSnapshots(InvestmentSnapshotService $snapshotService): array
    {
        $results = [
            'mode' => 'full_audit',
            'total_snapshots' => 0,
            'verified' => 0,
            'tampered' => 0,
            'errors' => 0,
            'tampered_details' => [],
        ];

        // Process in batches of 100
        InvestmentDisclosureSnapshot::query()
            ->select('id', 'investment_id', 'company_id')
            ->chunk(100, function ($snapshots) use ($snapshotService, &$results) {
                foreach ($snapshots as $snapshot) {
                    $results['total_snapshots']++;

                    $verifyResult = $snapshotService->verifySnapshotIntegrity($snapshot->id);

                    if ($verifyResult['verified']) {
                        $results['verified']++;
                    } elseif ($verifyResult['tamper_detected']) {
                        $results['tampered']++;
                        $results['tampered_details'][] = [
                            'snapshot_id' => $snapshot->id,
                            'investment_id' => $snapshot->investment_id,
                            'company_id' => $snapshot->company_id,
                            'tampered_disclosures' => $verifyResult['tampered_disclosures'] ?? [],
                        ];
                    } else {
                        $results['errors']++;
                    }
                }
            });

        return [$results];
    }

    /**
     * Analyze results and alert on tampering.
     */
    protected function analyzeAndAlert(array $results, \DateTimeInterface $startTime): void
    {
        $totalTampered = 0;
        $totalVerified = 0;
        $tamperedDetails = [];

        foreach ($results as $result) {
            if (isset($result['tampered'])) {
                $totalTampered += $result['tampered'];
                $totalVerified += $result['verified'] ?? 0;
                if (!empty($result['tampered_details'])) {
                    $tamperedDetails = array_merge($tamperedDetails, $result['tampered_details']);
                }
            } elseif (isset($result['tamper_detected']) && $result['tamper_detected']) {
                $totalTampered++;
                $tamperedDetails[] = $result;
            } elseif (isset($result['verified']) && $result['verified']) {
                $totalVerified++;
            }
        }

        $summary = [
            'job' => 'SnapshotIntegrityAuditJob',
            'started_at' => $startTime->toIso8601String(),
            'completed_at' => now()->toIso8601String(),
            'duration_seconds' => $startTime->diffInSeconds(now()),
            'total_verified' => $totalVerified,
            'total_tampered' => $totalTampered,
            'integrity_rate' => ($totalVerified + $totalTampered) > 0
                ? round(($totalVerified / ($totalVerified + $totalTampered)) * 100, 2)
                : 100,
        ];

        if ($totalTampered === 0) {
            Log::info('[SNAPSHOT INTEGRITY AUDIT] All snapshots verified', $summary);
            return;
        }

        // CRITICAL: Tampering detected
        Log::critical('[SNAPSHOT INTEGRITY AUDIT] Tampering detected', array_merge($summary, [
            'tampered_snapshots' => $tamperedDetails,
        ]));

        // Create admin alert
        $this->createAdminAlert($tamperedDetails, $summary);
    }

    /**
     * Create admin alert for tampering.
     */
    protected function createAdminAlert(array $tamperedDetails, array $summary): void
    {
        try {
            DB::table('admin_alerts')->insert([
                'alert_type' => 'snapshot_integrity_violation',
                'severity' => 'critical',
                'title' => 'Snapshot Integrity Violation Detected',
                'message' => sprintf(
                    '%d snapshot(s) show signs of tampering. Integrity rate: %.2f%%. Immediate investigation required.',
                    count($tamperedDetails),
                    $summary['integrity_rate']
                ),
                'context' => json_encode([
                    'tampered_snapshots' => $tamperedDetails,
                    'summary' => $summary,
                ]),
                'is_read' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::critical('[SNAPSHOT INTEGRITY AUDIT] Failed to create admin alert', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
