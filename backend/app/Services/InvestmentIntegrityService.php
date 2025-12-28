<?php
/**
 * Investment Integrity Service
 *
 * [C.10 FIX]: Prevent orphan investments and allocations
 *
 * PROTOCOL ENFORCEMENT:
 * - "No ownership, rewards, or allocations without completed, valid investment chain"
 * - Validates complete investment chain before operations
 * - Detects and reports orphaned records
 */

namespace App\Services;

use App\Models\Investment;
use App\Models\UserInvestment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InvestmentIntegrityService
{
    /**
     * Verify investment chain is complete and valid
     *
     * VALID CHAIN:
     * ```
     * User (kyc_status='approved')
     *   ↓
     * Investment (status='completed', payment_id NOT NULL)
     *   ↓
     * UserInvestment[] (is_reversed=false, value_allocated > 0)
     *   ↓
     * BulkPurchase (value_remaining consistent with allocations)
     * ```
     *
     * @param Investment $investment
     * @return array ['is_valid' => bool, 'violations' => array]
     */
    public function verifyInvestmentChain(Investment $investment): array
    {
        $violations = [];

        // Check 1: Investment must have user
        if (!$investment->user) {
            $violations[] = "Investment #{$investment->id} has no user";
        }

        // Check 2: User must have approved KYC
        if ($investment->user && $investment->user->kyc_status !== 'approved') {
            $violations[] = "Investment #{$investment->id} belongs to user with KYC status: {$investment->user->kyc_status}";
        }

        // Check 3: Investment must have payment reference
        if (!$investment->payment_id) {
            $violations[] = "Investment #{$investment->id} has no payment reference";
        }

        // Check 4: Completed investments must have allocations
        if ($investment->status === 'completed') {
            $allocations = UserInvestment::where('investment_id', $investment->id)
                ->where('is_reversed', false)
                ->get();

            if ($allocations->isEmpty()) {
                $violations[] = "Investment #{$investment->id} is marked completed but has no active allocations";
            }

            // Check 5: Allocation values must sum correctly
            $allocatedSum = $allocations->sum('value_allocated');
            $expectedAmount = $investment->final_amount;

            if (abs($allocatedSum - $expectedAmount) > 0.01) {
                $violations[] = "Investment #{$investment->id} allocation mismatch: expected {$expectedAmount}, got {$allocatedSum}";
            }
        }

        // Check 6: Pending/failed investments must NOT have allocations
        if (in_array($investment->status, ['pending', 'failed', 'cancelled'])) {
            $allocations = UserInvestment::where('investment_id', $investment->id)
                ->where('is_reversed', false)
                ->count();

            if ($allocations > 0) {
                $violations[] = "Investment #{$investment->id} has status '{$investment->status}' but has {$allocations} active allocations";
            }
        }

        $isValid = empty($violations);

        if (!$isValid) {
            Log::warning("INVESTMENT CHAIN INTEGRITY VIOLATION", [
                'investment_id' => $investment->id,
                'user_id' => $investment->user_id,
                'status' => $investment->status,
                'violations' => $violations,
            ]);
        }

        return [
            'is_valid' => $isValid,
            'violations' => $violations,
            'investment_id' => $investment->id,
            'status' => $investment->status,
        ];
    }

    /**
     * Verify user can receive bonus for investment
     *
     * @param Investment $investment
     * @param string $bonusType Type of bonus (referral, progressive, milestone, etc.)
     * @return array ['allowed' => bool, 'reason' => ?string]
     */
    public function canReceiveBonus(Investment $investment, string $bonusType): array
    {
        // First verify investment chain integrity
        $chainResult = $this->verifyInvestmentChain($investment);

        if (!$chainResult['is_valid']) {
            return [
                'allowed' => false,
                'reason' => 'Investment chain invalid',
                'violations' => $chainResult['violations'],
            ];
        }

        // Investment must be completed
        if ($investment->status !== 'completed') {
            return [
                'allowed' => false,
                'reason' => "Investment status is '{$investment->status}', not 'completed'",
            ];
        }

        // User must have approved KYC
        if ($investment->user->kyc_status !== 'approved') {
            return [
                'allowed' => false,
                'reason' => "User KYC status is '{$investment->user->kyc_status}', not 'approved'",
            ];
        }

        // Investment must have active allocations
        $hasAllocations = UserInvestment::where('investment_id', $investment->id)
            ->where('is_reversed', false)
            ->exists();

        if (!$hasAllocations) {
            return [
                'allowed' => false,
                'reason' => 'Investment has no active allocations',
            ];
        }

        return [
            'allowed' => true,
            'reason' => null,
        ];
    }

    /**
     * Find all orphaned UserInvestments
     *
     * Orphans are allocations that:
     * - Reference non-existent Investment
     * - Reference Investment with incompatible status
     * - Are marked active but Investment is cancelled/failed
     *
     * @return \Illuminate\Support\Collection
     */
    public function findOrphanedAllocations()
    {
        return DB::table('user_investments as ui')
            ->leftJoin('investments as i', 'ui.investment_id', '=', 'i.id')
            ->where('ui.is_reversed', false)
            ->where(function ($query) {
                $query->whereNull('i.id') // Investment doesn't exist
                    ->orWhereIn('i.status', ['failed', 'cancelled']) // Investment failed/cancelled
                    ->orWhereRaw('i.final_amount != (
                        SELECT SUM(value_allocated)
                        FROM user_investments
                        WHERE investment_id = i.id AND is_reversed = FALSE
                    )'); // Allocation sum mismatch
            })
            ->select('ui.*', 'i.status as investment_status')
            ->get();
    }

    /**
     * Find all incomplete investments
     *
     * Incomplete investments are:
     * - Status 'completed' but no allocations
     * - Status 'pending' for > 24 hours
     * - Status 'completed' but no payment reference
     *
     * @return \Illuminate\Support\Collection
     */
    public function findIncompleteInvestments()
    {
        $incompleteDueToNoAllocations = DB::table('investments as i')
            ->leftJoin('user_investments as ui', function ($join) {
                $join->on('i.id', '=', 'ui.investment_id')
                    ->where('ui.is_reversed', false);
            })
            ->where('i.status', 'completed')
            ->groupBy('i.id')
            ->havingRaw('COUNT(ui.id) = 0')
            ->select('i.*')
            ->get();

        $incompleteDueToStuck = DB::table('investments')
            ->where('status', 'pending')
            ->where('created_at', '<', now()->subHours(24))
            ->get();

        $incompleteDueToNoPayment = DB::table('investments')
            ->where('status', 'completed')
            ->whereNull('payment_id')
            ->get();

        return collect([
            'no_allocations' => $incompleteDueToNoAllocations,
            'stuck_pending' => $incompleteDueToStuck,
            'no_payment' => $incompleteDueToNoPayment,
        ]);
    }

    /**
     * Run full integrity audit across all investments
     *
     * @return array Report with violations and recommendations
     */
    public function auditIntegrity(): array
    {
        Log::info("INVESTMENT INTEGRITY AUDIT: Starting...");

        $orphanedAllocations = $this->findOrphanedAllocations();
        $incompleteInvestments = $this->findIncompleteInvestments();

        $totalOrphans = $orphanedAllocations->count();
        $totalIncomplete = $incompleteInvestments->sum(fn($group) => $group->count());

        $report = [
            'timestamp' => now()->toDateTimeString(),
            'orphaned_allocations' => [
                'count' => $totalOrphans,
                'records' => $orphanedAllocations->take(100), // Limit to first 100
            ],
            'incomplete_investments' => [
                'count' => $totalIncomplete,
                'breakdown' => [
                    'no_allocations' => $incompleteInvestments['no_allocations']->count(),
                    'stuck_pending' => $incompleteInvestments['stuck_pending']->count(),
                    'no_payment' => $incompleteInvestments['no_payment']->count(),
                ],
                'records' => $incompleteInvestments->map(fn($group) => $group->take(50)), // Limit each group
            ],
            'severity' => $this->calculateSeverity($totalOrphans, $totalIncomplete),
            'recommendations' => $this->generateRecommendations($totalOrphans, $totalIncomplete),
        ];

        if ($report['severity'] === 'critical') {
            Log::critical("INVESTMENT INTEGRITY AUDIT: CRITICAL VIOLATIONS FOUND", $report);
        } elseif ($report['severity'] === 'warning') {
            Log::warning("INVESTMENT INTEGRITY AUDIT: Violations found", $report);
        } else {
            Log::info("INVESTMENT INTEGRITY AUDIT: Complete - No violations", $report);
        }

        return $report;
    }

    /**
     * Calculate severity level based on violation counts
     */
    private function calculateSeverity(int $orphans, int $incomplete): string
    {
        $total = $orphans + $incomplete;

        if ($total === 0) {
            return 'healthy';
        } elseif ($total < 10) {
            return 'warning';
        } else {
            return 'critical';
        }
    }

    /**
     * Generate recommendations based on findings
     */
    private function generateRecommendations(int $orphans, int $incomplete): array
    {
        $recommendations = [];

        if ($orphans > 0) {
            $recommendations[] = "Review and reverse {$orphans} orphaned allocations";
            $recommendations[] = "Investigate why allocations exist without valid investment parent";
        }

        if ($incomplete > 0) {
            $recommendations[] = "Review {$incomplete} incomplete investments";
            $recommendations[] = "Check saga execution logs for failed compensation";
            $recommendations[] = "Consider implementing automated reconciliation job";
        }

        if ($orphans === 0 && $incomplete === 0) {
            $recommendations[] = "System integrity verified - no action needed";
        }

        return $recommendations;
    }
}
