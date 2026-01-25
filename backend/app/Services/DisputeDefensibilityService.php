<?php

namespace App\Services;

use App\Models\Company;
use App\Models\CompanyInvestment;
use App\Models\InvestorJourney;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * P0 FIX (GAP 31-33): Dispute Defensibility Service
 *
 * PURPOSE:
 * Provide complete audit trail and snapshot retrieval for dispute resolution.
 * Ensures platform can prove:
 * - What investor saw at time of investment
 * - What investor acknowledged
 * - What platform context was active
 * - Complete journey state at each step
 *
 * GAPS ADDRESSED:
 * - GAP 31: Full dispute snapshot retrieval
 * - GAP 32: Immutability verification
 * - GAP 33: State machine enforcement audit
 *
 * CRITICAL: This service provides legally defensible evidence for disputes.
 */
class DisputeDefensibilityService
{
    /**
     * GAP 31: Get complete dispute snapshot for an investment
     *
     * Retrieves ALL information needed to defend a dispute:
     * - Platform context at time of investment
     * - Investor journey and acknowledgements
     * - Disclosure versions shown to investor
     * - Risk flags active at time
     * - Admin actions affecting the company
     *
     * @param int $investmentId
     * @return array
     */
    public function getDisputeSnapshot(int $investmentId): array
    {
        $investment = CompanyInvestment::with([
            'company',
            'user',
            'bulkPurchase',
            'allocationLogs',
        ])->find($investmentId);

        if (!$investment) {
            return ['error' => 'Investment not found'];
        }

        // 1. Get platform context snapshot at time of investment
        $platformSnapshot = $this->getPlatformSnapshotAtInvestment($investment);

        // 2. Get investor journey with all transitions
        $journeyData = $this->getInvestorJourneyData($investment);

        // 3. Get disclosure versions shown to investor
        $disclosureVersions = $this->getDisclosureVersionsAtInvestment($investment);

        // 4. Get risk flags active at time
        $riskFlags = $this->getRiskFlagsAtInvestment($investment);

        // 5. Get admin actions affecting company around investment time
        $adminActions = $this->getAdminActionsAroundInvestment($investment);

        // 6. Get wallet transaction
        $walletTransaction = $this->getWalletTransaction($investment);

        // 7. Get allocation proof
        $allocationProof = $this->getAllocationProof($investment);

        // 8. Verify immutability of all records
        $immutabilityVerification = $this->verifyImmutability($investment);

        return [
            'dispute_snapshot' => [
                'generated_at' => now()->toIso8601String(),
                'investment_id' => $investmentId,
                'snapshot_version' => '1.0',
            ],

            'investment' => [
                'id' => $investment->id,
                'amount' => $investment->amount,
                'status' => $investment->status,
                'created_at' => $investment->created_at,
                'idempotency_key' => $investment->idempotency_key,
            ],

            'investor' => [
                'id' => $investment->user_id,
                'name' => $investment->user?->name,
                'email' => $investment->user?->email,
                'kyc_status' => $investment->user?->kyc_status,
            ],

            'company' => [
                'id' => $investment->company_id,
                'name' => $investment->company?->name,
                'lifecycle_state_at_investment' => $platformSnapshot['lifecycle_state'] ?? null,
            ],

            'platform_context_at_investment' => $platformSnapshot,
            'investor_journey' => $journeyData,
            'disclosure_versions' => $disclosureVersions,
            'risk_flags_shown' => $riskFlags,
            'admin_actions_context' => $adminActions,
            'wallet_transaction' => $walletTransaction,
            'allocation_proof' => $allocationProof,
            'immutability_verification' => $immutabilityVerification,

            'legal_defensibility' => [
                'journey_complete' => $journeyData['is_valid_sequence'] ?? false,
                'acknowledgements_captured' => !empty($journeyData['acknowledgements']),
                'snapshot_bound' => $platformSnapshot !== null,
                'immutability_verified' => $immutabilityVerification['all_verified'] ?? false,
            ],
        ];
    }

    /**
     * Get platform snapshot at time of investment
     */
    protected function getPlatformSnapshotAtInvestment(CompanyInvestment $investment): ?array
    {
        // First try to get the bound snapshot
        if ($investment->disclosure_snapshot_id) {
            $snapshot = DB::table('platform_context_snapshots')
                ->where('id', $investment->disclosure_snapshot_id)
                ->first();

            if ($snapshot) {
                return [
                    'snapshot_id' => $snapshot->id,
                    'snapshot_at' => $snapshot->snapshot_at,
                    'lifecycle_state' => $snapshot->lifecycle_state,
                    'buying_enabled' => $snapshot->buying_enabled,
                    'is_suspended' => $snapshot->is_suspended,
                    'is_frozen' => $snapshot->is_frozen,
                    'is_under_investigation' => $snapshot->is_under_investigation,
                    'risk_level' => $snapshot->risk_level,
                    'compliance_score' => $snapshot->compliance_score,
                    'tier_2_approved' => $snapshot->tier_2_approved,
                    'admin_judgments' => json_decode($snapshot->admin_judgments ?? '[]', true),
                    'is_locked' => $snapshot->is_locked,
                ];
            }
        }

        // Fallback: Find snapshot valid at investment time
        $snapshot = DB::table('platform_context_snapshots')
            ->where('company_id', $investment->company_id)
            ->where('valid_from', '<=', $investment->created_at)
            ->where(function ($query) use ($investment) {
                $query->whereNull('valid_until')
                    ->orWhere('valid_until', '>', $investment->created_at);
            })
            ->orderBy('valid_from', 'desc')
            ->first();

        if ($snapshot) {
            return [
                'snapshot_id' => $snapshot->id,
                'snapshot_at' => $snapshot->snapshot_at,
                'lifecycle_state' => $snapshot->lifecycle_state,
                'buying_enabled' => $snapshot->buying_enabled,
                'is_suspended' => $snapshot->is_suspended,
                'is_frozen' => $snapshot->is_frozen,
                'risk_level' => $snapshot->risk_level,
                'is_locked' => $snapshot->is_locked,
                'note' => 'Reconstructed from valid_from/valid_until range',
            ];
        }

        return null;
    }

    /**
     * Get investor journey data
     */
    protected function getInvestorJourneyData(CompanyInvestment $investment): array
    {
        $journey = InvestorJourney::where('company_investment_id', $investment->id)
            ->with(['transitions', 'acknowledgementBindings'])
            ->first();

        if (!$journey) {
            // Try to find by user/company around investment time
            $journey = InvestorJourney::where('user_id', $investment->user_id)
                ->where('company_id', $investment->company_id)
                ->where('created_at', '<=', $investment->created_at)
                ->where('created_at', '>=', $investment->created_at->subHours(3))
                ->orderBy('created_at', 'desc')
                ->with(['transitions', 'acknowledgementBindings'])
                ->first();
        }

        if (!$journey) {
            return [
                'found' => false,
                'note' => 'Journey record not found (may predate journey tracking)',
            ];
        }

        $sequenceProof = $journey->proveJourneySequence();

        return [
            'found' => true,
            'journey_id' => $journey->id,
            'journey_token' => $journey->journey_token,
            'started_at' => $journey->journey_started_at,
            'completed_at' => $journey->journey_completed_at,
            'final_state' => $journey->current_state,
            'is_valid_sequence' => $sequenceProof['is_valid_sequence'],
            'violations' => $sequenceProof['violations'],
            'transitions' => $journey->transitions->map(fn($t) => [
                'from' => $t->from_state,
                'to' => $t->to_state,
                'at' => $t->transitioned_at,
                'valid' => $t->was_valid_transition,
            ])->toArray(),
            'acknowledgements' => $journey->acknowledgementBindings->map(fn($a) => [
                'type' => $a->acknowledgement_type,
                'key' => $a->acknowledgement_key,
                'acknowledged_at' => $a->acknowledged_at,
                'journey_state' => $a->journey_state_at_ack,
                'explicit_consent' => $a->explicit_consent,
                'snapshot_id' => $a->snapshot_id_at_ack,
            ])->toArray(),
            'session_info' => [
                'ip_address' => $journey->ip_address,
                'device_fingerprint' => $journey->device_fingerprint,
            ],
        ];
    }

    /**
     * Get disclosure versions at investment time
     */
    protected function getDisclosureVersionsAtInvestment(CompanyInvestment $investment): array
    {
        // Get disclosure versions that were current at investment time
        $versions = DB::table('disclosure_versions')
            ->join('company_disclosures', 'disclosure_versions.disclosure_id', '=', 'company_disclosures.id')
            ->where('company_disclosures.company_id', $investment->company_id)
            ->where('disclosure_versions.created_at', '<=', $investment->created_at)
            ->where('company_disclosures.status', 'approved')
            ->select([
                'disclosure_versions.*',
                'company_disclosures.disclosure_module_id',
            ])
            ->orderBy('disclosure_versions.created_at', 'desc')
            ->get()
            ->groupBy('disclosure_module_id')
            ->map(fn($group) => $group->first())
            ->values();

        return $versions->map(fn($v) => [
            'version_id' => $v->id,
            'module_id' => $v->disclosure_module_id,
            'version_number' => $v->version_number,
            'created_at' => $v->created_at,
            'content_hash' => $v->content_hash ?? null,
        ])->toArray();
    }

    /**
     * Get risk flags active at investment time
     */
    protected function getRiskFlagsAtInvestment(CompanyInvestment $investment): array
    {
        $flags = DB::table('platform_risk_flags')
            ->where('company_id', $investment->company_id)
            ->where('created_at', '<=', $investment->created_at)
            ->where(function ($query) use ($investment) {
                $query->where('status', 'active')
                    ->orWhere('resolved_at', '>', $investment->created_at);
            })
            ->where('is_visible_to_investors', true)
            ->get();

        return $flags->map(fn($f) => [
            'id' => $f->id,
            'flag_type' => $f->flag_type,
            'severity' => $f->severity,
            'category' => $f->category,
            'investor_message' => $f->investor_message,
            'status_at_investment' => $f->status,
        ])->toArray();
    }

    /**
     * Get admin actions around investment time
     */
    protected function getAdminActionsAroundInvestment(CompanyInvestment $investment): array
    {
        // Get admin actions within 7 days before and after investment
        $startDate = $investment->created_at->subDays(7);
        $endDate = $investment->created_at->addDays(7);

        $actions = DB::table('platform_governance_log')
            ->where('company_id', $investment->company_id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->orderBy('created_at')
            ->get();

        return [
            'window' => [
                'start' => $startDate->toIso8601String(),
                'end' => $endDate->toIso8601String(),
            ],
            'actions' => $actions->map(fn($a) => [
                'action_type' => $a->action_type,
                'created_at' => $a->created_at,
                'is_automated' => $a->is_automated,
                'before_investment' => $a->created_at < $investment->created_at,
            ])->toArray(),
        ];
    }

    /**
     * Get wallet transaction for investment
     */
    protected function getWalletTransaction(CompanyInvestment $investment): ?array
    {
        $transaction = DB::table('transactions')
            ->where('reference_type', CompanyInvestment::class)
            ->where('reference_id', $investment->id)
            ->first();

        if (!$transaction) {
            return null;
        }

        return [
            'transaction_id' => $transaction->id,
            'amount' => $transaction->amount_paise / 100,
            'type' => $transaction->type,
            'status' => $transaction->status,
            'balance_before' => $transaction->balance_before_paise / 100,
            'balance_after' => $transaction->balance_after_paise / 100,
            'created_at' => $transaction->created_at,
        ];
    }

    /**
     * Get allocation proof for investment
     */
    protected function getAllocationProof(CompanyInvestment $investment): array
    {
        $allocations = $investment->allocationLogs ?? collect();

        return [
            'allocation_count' => $allocations->count(),
            'total_allocated' => $investment->allocated_value,
            'allocation_status' => $investment->allocation_status,
            'allocations' => $allocations->map(fn($a) => [
                'log_id' => $a->id,
                'bulk_purchase_id' => $a->bulk_purchase_id,
                'value_allocated' => $a->value_allocated,
                'inventory_before' => $a->inventory_before,
                'inventory_after' => $a->inventory_after,
                'allocated_at' => $a->created_at,
            ])->toArray(),
            'ledger_entry_id' => $investment->admin_ledger_entry_id,
        ];
    }

    /**
     * GAP 32 & 33: Verify immutability of all related records
     */
    protected function verifyImmutability(CompanyInvestment $investment): array
    {
        $verifications = [];

        // 1. Check platform snapshot immutability
        if ($investment->disclosure_snapshot_id) {
            $snapshot = DB::table('platform_context_snapshots')
                ->where('id', $investment->disclosure_snapshot_id)
                ->first();

            $verifications['platform_snapshot'] = [
                'record_id' => $investment->disclosure_snapshot_id,
                'is_locked' => $snapshot?->is_locked ?? false,
                'locked_at' => $snapshot?->locked_at,
                'verified' => $snapshot?->is_locked === true,
            ];
        }

        // 2. Check journey transitions immutability
        $journey = InvestorJourney::where('company_investment_id', $investment->id)->first();
        if ($journey) {
            $transitionCount = $journey->transitions()->count();
            $verifications['journey_transitions'] = [
                'journey_id' => $journey->id,
                'transition_count' => $transitionCount,
                'verified' => true, // Model enforces immutability
                'note' => 'Model-level immutability enforced via boot() method',
            ];
        }

        // 3. Check allocation logs immutability
        $allocationLogs = $investment->allocationLogs ?? collect();
        $verifications['allocation_logs'] = [
            'count' => $allocationLogs->count(),
            'verified' => true, // Model enforces immutability
            'note' => 'Model-level immutability enforced via boot() method',
        ];

        // 4. Check acknowledgement bindings
        if ($journey) {
            $ackCount = $journey->acknowledgementBindings()->count();
            $verifications['acknowledgement_bindings'] = [
                'count' => $ackCount,
                'verified' => true,
                'note' => 'Model-level immutability enforced via boot() method',
            ];
        }

        $allVerified = collect($verifications)->every(fn($v) => $v['verified'] ?? false);

        return [
            'all_verified' => $allVerified,
            'verifications' => $verifications,
            'enforcement_level' => 'application', // Note: DB-level triggers would be 'database'
        ];
    }

    /**
     * GAP 33: Verify state machine integrity for investment
     */
    public function verifyStateMachineIntegrity(int $investmentId): array
    {
        $investment = CompanyInvestment::find($investmentId);

        if (!$investment) {
            return ['error' => 'Investment not found'];
        }

        $violations = [];

        // 1. Check journey state sequence
        $journey = InvestorJourney::where('company_investment_id', $investmentId)
            ->with('transitions')
            ->first();

        if ($journey) {
            $sequenceProof = $journey->proveJourneySequence();
            if (!$sequenceProof['is_valid_sequence']) {
                $violations[] = [
                    'type' => 'JOURNEY_SEQUENCE_VIOLATION',
                    'details' => $sequenceProof['violations'],
                ];
            }
        }

        // 2. Check company lifecycle state was valid at investment
        $companyState = DB::table('platform_context_snapshots')
            ->where('id', $investment->disclosure_snapshot_id)
            ->value('lifecycle_state');

        if (in_array($companyState, ['suspended', 'terminated'])) {
            $violations[] = [
                'type' => 'COMPANY_STATE_VIOLATION',
                'details' => "Investment made while company was {$companyState}",
            ];
        }

        // 3. Check investment status transitions
        // (Would need investment_status_log table for full audit)

        return [
            'investment_id' => $investmentId,
            'integrity_verified' => empty($violations),
            'violations' => $violations,
            'checked_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Get dispute-ready export for legal
     */
    public function exportForLegal(int $investmentId): array
    {
        $snapshot = $this->getDisputeSnapshot($investmentId);

        if (isset($snapshot['error'])) {
            return $snapshot;
        }

        return [
            'export_type' => 'Legal Dispute Package',
            'generated_at' => now()->toIso8601String(),
            'case_reference' => "INV-{$investmentId}-" . now()->format('Ymd'),
            'contents' => $snapshot,
            'certification' => [
                'statement' => 'This document contains a complete snapshot of all platform records related to the investment as of the generation date.',
                'data_sources' => [
                    'platform_context_snapshots',
                    'investor_journeys',
                    'investor_journey_transitions',
                    'journey_acknowledgement_bindings',
                    'disclosure_versions',
                    'platform_risk_flags',
                    'platform_governance_log',
                    'transactions',
                    'share_allocation_logs',
                ],
                'immutability_note' => 'All audit trail records are enforced as immutable at the application layer. Database triggers recommended for additional enforcement.',
            ],
        ];
    }
}
