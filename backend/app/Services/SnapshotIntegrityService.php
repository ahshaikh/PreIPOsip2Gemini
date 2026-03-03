<?php

namespace App\Services;

use App\Models\Dispute;
use App\Models\DisputeSnapshot;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

/**
 * SnapshotIntegrityService - Verifies snapshot hash integrity
 *
 * This service COMPUTES integrity status - it does not blindly store or trust.
 * Every integrity check recalculates the hash from current data and compares
 * to the stored hash.
 *
 * Used for:
 * - Pre-resolution validation (ensure snapshot wasn't tampered)
 * - Audit reporting (batch integrity verification)
 * - Admin dashboard integrity status
 *
 * CRITICAL: Integrity failures should block dispute resolution.
 */
class SnapshotIntegrityService
{
    /**
     * Verify integrity of a single snapshot.
     *
     * @return array{valid: bool, computed_hash: string, stored_hash: string, error: ?string}
     */
    public function verify(DisputeSnapshot $snapshot): array
    {
        try {
            $computedHash = $snapshot->computeIntegrityHash();
            $storedHash = $snapshot->integrity_hash;

            $isValid = hash_equals($storedHash, $computedHash);

            if (!$isValid) {
                Log::channel('financial_contract')->error('Snapshot integrity failure', [
                    'snapshot_id' => $snapshot->id,
                    'dispute_id' => $snapshot->dispute_id,
                    'stored_hash' => $storedHash,
                    'computed_hash' => $computedHash,
                ]);
            }

            return [
                'valid' => $isValid,
                'computed_hash' => $computedHash,
                'stored_hash' => $storedHash,
                'error' => $isValid ? null : 'Hash mismatch detected - possible tampering',
            ];

        } catch (\Throwable $e) {
            Log::channel('financial_contract')->error('Snapshot integrity check failed', [
                'snapshot_id' => $snapshot->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'valid' => false,
                'computed_hash' => null,
                'stored_hash' => $snapshot->integrity_hash,
                'error' => 'Integrity check failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Verify integrity for a dispute's snapshot.
     */
    public function verifyForDispute(Dispute $dispute): array
    {
        $snapshot = $dispute->snapshot;

        if (!$snapshot) {
            return [
                'valid' => false,
                'computed_hash' => null,
                'stored_hash' => null,
                'error' => 'No snapshot exists for this dispute',
            ];
        }

        return $this->verify($snapshot);
    }

    /**
     * Verify that dispute can be resolved (snapshot integrity must pass).
     *
     * @throws \RuntimeException If integrity check fails
     */
    public function assertResolutionAllowed(Dispute $dispute): void
    {
        $result = $this->verifyForDispute($dispute);

        if (!$result['valid']) {
            throw new \RuntimeException(
                "Cannot resolve dispute #{$dispute->id}: Snapshot integrity check failed. " .
                $result['error']
            );
        }
    }

    /**
     * Batch verify all snapshots (for audit).
     *
     * @return array{total: int, valid: int, invalid: int, missing: int, failures: array}
     */
    public function verifyAll(): array
    {
        $results = [
            'total' => 0,
            'valid' => 0,
            'invalid' => 0,
            'missing' => 0,
            'failures' => [],
        ];

        // Get all disputes that should have snapshots
        $disputes = Dispute::whereNotNull('disputable_type')
            ->whereNotNull('disputable_id')
            ->with('snapshot')
            ->cursor();

        foreach ($disputes as $dispute) {
            $results['total']++;

            if (!$dispute->snapshot) {
                $results['missing']++;
                $results['failures'][] = [
                    'dispute_id' => $dispute->id,
                    'type' => 'missing',
                    'error' => 'No snapshot exists',
                ];
                continue;
            }

            $verification = $this->verify($dispute->snapshot);

            if ($verification['valid']) {
                $results['valid']++;
            } else {
                $results['invalid']++;
                $results['failures'][] = [
                    'dispute_id' => $dispute->id,
                    'snapshot_id' => $dispute->snapshot->id,
                    'type' => 'invalid',
                    'error' => $verification['error'],
                ];
            }
        }

        Log::channel('financial_contract')->info('Snapshot integrity batch verification', [
            'total' => $results['total'],
            'valid' => $results['valid'],
            'invalid' => $results['invalid'],
            'missing' => $results['missing'],
        ]);

        return $results;
    }

    /**
     * Get integrity status for admin dashboard.
     */
    public function getDashboardStatus(): array
    {
        $totalDisputes = Dispute::count();
        $disputesWithSnapshot = Dispute::whereHas('snapshot')->count();
        $disputesMissingSnapshot = Dispute::whereDoesntHave('snapshot')
            ->whereNotNull('disputable_type')
            ->count();

        // Sample check on recent snapshots (for performance)
        $recentSnapshots = DisputeSnapshot::orderBy('created_at', 'desc')
            ->limit(100)
            ->get();

        $validCount = 0;
        $invalidCount = 0;

        foreach ($recentSnapshots as $snapshot) {
            if ($snapshot->verifyIntegrity()) {
                $validCount++;
            } else {
                $invalidCount++;
            }
        }

        return [
            'total_disputes' => $totalDisputes,
            'disputes_with_snapshot' => $disputesWithSnapshot,
            'disputes_missing_snapshot' => $disputesMissingSnapshot,
            'recent_snapshots_checked' => $recentSnapshots->count(),
            'recent_snapshots_valid' => $validCount,
            'recent_snapshots_invalid' => $invalidCount,
            'integrity_percentage' => $recentSnapshots->count() > 0
                ? round(($validCount / $recentSnapshots->count()) * 100, 2)
                : 100,
            'status' => $invalidCount > 0 ? 'warning' : 'healthy',
        ];
    }

    /**
     * Find disputes with integrity issues.
     */
    public function findIntegrityIssues(int $limit = 100): Collection
    {
        $issues = collect();

        $snapshots = DisputeSnapshot::with('dispute')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        foreach ($snapshots as $snapshot) {
            if (!$snapshot->verifyIntegrity()) {
                $issues->push([
                    'snapshot_id' => $snapshot->id,
                    'dispute_id' => $snapshot->dispute_id,
                    'dispute_title' => $snapshot->dispute->title ?? 'Unknown',
                    'created_at' => $snapshot->created_at,
                    'stored_hash' => $snapshot->integrity_hash,
                    'computed_hash' => $snapshot->computeIntegrityHash(),
                ]);
            }
        }

        return $issues;
    }
}
