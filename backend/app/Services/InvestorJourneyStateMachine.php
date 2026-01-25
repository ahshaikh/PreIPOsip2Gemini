<?php

namespace App\Services;

use App\Models\Company;
use App\Models\CompanyInvestment;
use App\Models\InvestorJourney;
use App\Models\InvestorJourneyTransition;
use App\Models\JourneyAcknowledgementBinding;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * P0 FIX (GAP 18-20): Investor Journey State Machine Service
 *
 * PURPOSE:
 * Enforce sequential investor journey through investment flow.
 * Prevents skipping steps and ensures all acknowledgements are captured.
 *
 * STATES (in order):
 * 1. initiated     - Journey started (auto)
 * 2. viewing       - Viewing company details
 * 3. acknowledging - Reading/accepting risk disclosures
 * 4. reviewing     - Reviewing terms and conditions
 * 5. confirming    - Final investment confirmation
 * 6. processing    - Payment in progress
 * 7. invested      - Successfully invested (terminal)
 * 8. blocked       - Blocked by compliance (terminal)
 * 9. abandoned     - Journey abandoned (terminal)
 *
 * ENFORCEMENT RULES:
 * - Cannot skip states (must go 1→2→3→4→5→6→7)
 * - Each state requires specific acknowledgements
 * - Snapshots bound at key points
 * - All transitions logged immutably
 */
class InvestorJourneyStateMachine
{
    /**
     * State requirements mapping
     */
    const STATE_REQUIREMENTS = [
        'viewing' => [],
        'acknowledging' => [],
        'reviewing' => [
            'acknowledgements' => ['risk_disclosure', 'investment_risks'],
        ],
        'confirming' => [
            'acknowledgements' => ['terms_conditions', 'privacy_policy'],
        ],
        'processing' => [
            'acknowledgements' => ['final_investment_terms'],
            'snapshot_required' => true,
        ],
        'invested' => [
            'investment_required' => true,
        ],
    ];

    /**
     * Journey expiry time in hours
     */
    const JOURNEY_EXPIRY_HOURS = 2;

    /**
     * Start a new investor journey
     *
     * @param User $user
     * @param Company $company
     * @param int|null $platformSnapshotId Current platform snapshot
     * @return array{success: bool, journey: InvestorJourney|null, message: string}
     */
    public function startJourney(User $user, Company $company, ?int $platformSnapshotId = null): array
    {
        // Check for existing active journey
        $existingJourney = $this->getActiveJourney($user->id, $company->id);
        if ($existingJourney && !$existingJourney->hasExpired()) {
            return [
                'success' => true,
                'journey' => $existingJourney,
                'message' => 'Existing active journey found',
                'journey_token' => $existingJourney->journey_token,
            ];
        }

        // Expire any old journeys
        $this->expireOldJourneys($user->id, $company->id);

        DB::beginTransaction();

        try {
            // Create new journey
            $journey = InvestorJourney::create([
                'user_id' => $user->id,
                'company_id' => $company->id,
                'current_state' => 'initiated',
                'state_entered_at' => now(),
                'journey_token' => Str::random(64),
                'journey_started_at' => now(),
                'platform_snapshot_id' => $platformSnapshotId,
                'snapshot_bound_at' => $platformSnapshotId ? now() : null,
                'expires_at' => now()->addHours(self::JOURNEY_EXPIRY_HOURS),
            ]);

            // Log initial transition
            InvestorJourneyTransition::create([
                'journey_id' => $journey->id,
                'from_state' => 'none',
                'to_state' => 'initiated',
                'transition_type' => 'start',
                'was_valid_transition' => true,
                'validation_result' => 'journey_started',
                'triggered_by' => 'user_action',
                'snapshot_id_at_transition' => $platformSnapshotId,
            ]);

            DB::commit();

            Log::info('JOURNEY: Started new investor journey', [
                'journey_id' => $journey->id,
                'journey_token' => $journey->journey_token,
                'user_id' => $user->id,
                'company_id' => $company->id,
                'snapshot_id' => $platformSnapshotId,
            ]);

            return [
                'success' => true,
                'journey' => $journey,
                'message' => 'Journey started successfully',
                'journey_token' => $journey->journey_token,
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('JOURNEY: Failed to start journey', [
                'user_id' => $user->id,
                'company_id' => $company->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'journey' => null,
                'message' => 'Failed to start journey: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Advance journey to next state
     *
     * @param InvestorJourney $journey
     * @param string $targetState
     * @param array $stateData Additional data for this state
     * @return array{success: bool, message: string, new_state: string|null}
     */
    public function advanceState(InvestorJourney $journey, string $targetState, array $stateData = []): array
    {
        // Validate journey is active
        if ($journey->isTerminal()) {
            return [
                'success' => false,
                'message' => "Journey is in terminal state: {$journey->current_state}",
                'new_state' => null,
            ];
        }

        if ($journey->hasExpired()) {
            $this->abandonJourney($journey, 'expired', 'Journey expired due to inactivity');
            return [
                'success' => false,
                'message' => 'Journey has expired',
                'new_state' => 'abandoned',
            ];
        }

        // Validate transition is allowed (no skipping)
        if (!$journey->canTransitionTo($targetState)) {
            $nextState = $journey->getNextState();
            return [
                'success' => false,
                'message' => "Cannot transition from {$journey->current_state} to {$targetState}. " .
                            ($nextState ? "Next valid state is: {$nextState}" : "No valid transitions available."),
                'new_state' => null,
                'violation' => 'state_skip_attempted',
            ];
        }

        // Check state requirements
        $requirementCheck = $this->checkStateRequirements($journey, $targetState);
        if (!$requirementCheck['satisfied']) {
            return [
                'success' => false,
                'message' => $requirementCheck['message'],
                'new_state' => null,
                'missing_requirements' => $requirementCheck['missing'],
            ];
        }

        DB::beginTransaction();

        try {
            $fromState = $journey->current_state;

            // Create transition record
            $transition = InvestorJourneyTransition::create([
                'journey_id' => $journey->id,
                'from_state' => $fromState,
                'to_state' => $targetState,
                'transition_type' => 'advance',
                'was_valid_transition' => true,
                'validation_result' => 'requirements_satisfied',
                'state_data' => $stateData,
                'acknowledgements_at_transition' => $journey->acknowledgementBindings()->pluck('acknowledgement_key')->toArray(),
                'snapshot_id_at_transition' => $journey->platform_snapshot_id,
                'triggered_by' => 'user_action',
            ]);

            // Update journey state
            $journey->update([
                'current_state' => $targetState,
                'state_entered_at' => now(),
                // Extend expiry on activity
                'expires_at' => now()->addHours(self::JOURNEY_EXPIRY_HOURS),
            ]);

            DB::commit();

            Log::info('JOURNEY: State advanced', [
                'journey_id' => $journey->id,
                'from_state' => $fromState,
                'to_state' => $targetState,
                'transition_id' => $transition->id,
            ]);

            return [
                'success' => true,
                'message' => "Advanced to state: {$targetState}",
                'new_state' => $targetState,
                'transition_id' => $transition->id,
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('JOURNEY: Failed to advance state', [
                'journey_id' => $journey->id,
                'target_state' => $targetState,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to advance state: ' . $e->getMessage(),
                'new_state' => null,
            ];
        }
    }

    /**
     * Record an acknowledgement for the journey
     *
     * @param InvestorJourney $journey
     * @param string $type Acknowledgement type
     * @param string $key Specific item being acknowledged
     * @param string $text Exact text user acknowledged
     * @param bool $explicitConsent Whether user explicitly consented
     * @param string|null $version Version of document acknowledged
     * @return array{success: bool, message: string, binding_id: int|null}
     */
    public function recordAcknowledgement(
        InvestorJourney $journey,
        string $type,
        string $key,
        string $text,
        bool $explicitConsent = true,
        ?string $version = null
    ): array {
        if ($journey->isTerminal()) {
            return [
                'success' => false,
                'message' => 'Cannot acknowledge on terminal journey',
                'binding_id' => null,
            ];
        }

        // Check if already acknowledged
        $existing = JourneyAcknowledgementBinding::where('journey_id', $journey->id)
            ->where('acknowledgement_key', $key)
            ->first();

        if ($existing) {
            return [
                'success' => true,
                'message' => 'Already acknowledged',
                'binding_id' => $existing->id,
            ];
        }

        try {
            // Create snapshot hash for integrity verification
            $snapshotHash = $journey->platform_snapshot_id ? [
                'snapshot_id' => $journey->platform_snapshot_id,
                'content_hash' => hash('sha256', $text . $journey->platform_snapshot_id),
                'bound_at' => now()->toIso8601String(),
            ] : null;

            $binding = JourneyAcknowledgementBinding::create([
                'journey_id' => $journey->id,
                'acknowledgement_type' => $type,
                'acknowledgement_key' => $key,
                'acknowledgement_version' => $version,
                'journey_state_at_ack' => $journey->current_state,
                'snapshot_id_at_ack' => $journey->platform_snapshot_id,
                'snapshot_hash' => $snapshotHash,
                'acknowledgement_text' => $text,
                'explicit_consent' => $explicitConsent,
            ]);

            // Update journey's acknowledged_risks if it's a risk acknowledgement
            if (str_contains($type, 'risk')) {
                $acknowledgedRisks = $journey->acknowledged_risks ?? [];
                $acknowledgedRisks[] = $key;
                $journey->update([
                    'acknowledged_risks' => array_unique($acknowledgedRisks),
                    'risks_acknowledged_at' => now(),
                ]);
            }

            // Update journey's accepted_terms if it's a terms acknowledgement
            if (str_contains($type, 'terms')) {
                $acceptedTerms = $journey->accepted_terms ?? [];
                $acceptedTerms[] = $key;
                $journey->update([
                    'accepted_terms' => array_unique($acceptedTerms),
                    'terms_accepted_at' => now(),
                ]);
            }

            Log::info('JOURNEY: Acknowledgement recorded', [
                'journey_id' => $journey->id,
                'type' => $type,
                'key' => $key,
                'state_at_ack' => $journey->current_state,
                'binding_id' => $binding->id,
            ]);

            return [
                'success' => true,
                'message' => 'Acknowledgement recorded',
                'binding_id' => $binding->id,
            ];

        } catch (\Exception $e) {
            Log::error('JOURNEY: Failed to record acknowledgement', [
                'journey_id' => $journey->id,
                'type' => $type,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to record acknowledgement: ' . $e->getMessage(),
                'binding_id' => null,
            ];
        }
    }

    /**
     * Complete journey with investment
     *
     * @param InvestorJourney $journey
     * @param CompanyInvestment $investment
     * @param int|null $investmentSnapshotId
     * @return array{success: bool, message: string}
     */
    public function completeWithInvestment(
        InvestorJourney $journey,
        CompanyInvestment $investment,
        ?int $investmentSnapshotId = null
    ): array {
        if ($journey->current_state !== 'processing') {
            return [
                'success' => false,
                'message' => "Journey must be in 'processing' state to complete. Current: {$journey->current_state}",
            ];
        }

        DB::beginTransaction();

        try {
            // Create final transition
            InvestorJourneyTransition::create([
                'journey_id' => $journey->id,
                'from_state' => 'processing',
                'to_state' => 'invested',
                'transition_type' => 'complete',
                'was_valid_transition' => true,
                'validation_result' => 'investment_successful',
                'state_data' => [
                    'investment_id' => $investment->id,
                    'amount' => $investment->amount,
                ],
                'acknowledgements_at_transition' => $journey->acknowledgementBindings()->pluck('acknowledgement_key')->toArray(),
                'snapshot_id_at_transition' => $investmentSnapshotId ?? $journey->platform_snapshot_id,
                'triggered_by' => 'system',
            ]);

            // Update journey to complete
            $journey->update([
                'current_state' => 'invested',
                'state_entered_at' => now(),
                'journey_completed_at' => now(),
                'is_complete' => true,
                'completion_type' => 'invested',
                'company_investment_id' => $investment->id,
                'investment_snapshot_id' => $investmentSnapshotId,
            ]);

            DB::commit();

            Log::info('JOURNEY: Completed with investment', [
                'journey_id' => $journey->id,
                'investment_id' => $investment->id,
                'snapshot_id' => $investmentSnapshotId,
            ]);

            return [
                'success' => true,
                'message' => 'Journey completed successfully',
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('JOURNEY: Failed to complete with investment', [
                'journey_id' => $journey->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to complete journey: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Block a journey (compliance/eligibility failure)
     *
     * @param InvestorJourney $journey
     * @param string $reason
     * @param string $code
     * @return array{success: bool, message: string}
     */
    public function blockJourney(InvestorJourney $journey, string $reason, string $code): array
    {
        if ($journey->isTerminal()) {
            return [
                'success' => false,
                'message' => 'Journey is already terminal',
            ];
        }

        DB::beginTransaction();

        try {
            InvestorJourneyTransition::create([
                'journey_id' => $journey->id,
                'from_state' => $journey->current_state,
                'to_state' => 'blocked',
                'transition_type' => 'block',
                'was_valid_transition' => true,
                'validation_result' => $code,
                'state_data' => [
                    'reason' => $reason,
                    'code' => $code,
                ],
                'triggered_by' => 'system',
            ]);

            $journey->update([
                'current_state' => 'blocked',
                'state_entered_at' => now(),
                'journey_completed_at' => now(),
                'is_complete' => true,
                'completion_type' => 'blocked',
                'block_reason' => $reason,
                'block_code' => $code,
                'blocked_at' => now(),
            ]);

            DB::commit();

            Log::warning('JOURNEY: Blocked', [
                'journey_id' => $journey->id,
                'reason' => $reason,
                'code' => $code,
            ]);

            return [
                'success' => true,
                'message' => 'Journey blocked',
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'message' => 'Failed to block journey: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Abandon a journey
     *
     * @param InvestorJourney $journey
     * @param string $reason
     * @param string|null $detail
     * @return array{success: bool, message: string}
     */
    public function abandonJourney(InvestorJourney $journey, string $reason, ?string $detail = null): array
    {
        if ($journey->isTerminal()) {
            return ['success' => false, 'message' => 'Journey is already terminal'];
        }

        DB::beginTransaction();

        try {
            InvestorJourneyTransition::create([
                'journey_id' => $journey->id,
                'from_state' => $journey->current_state,
                'to_state' => 'abandoned',
                'transition_type' => 'abandon',
                'was_valid_transition' => true,
                'validation_result' => $reason,
                'state_data' => ['detail' => $detail],
                'triggered_by' => $reason === 'expired' ? 'timeout' : 'user_action',
            ]);

            $journey->update([
                'current_state' => 'abandoned',
                'state_entered_at' => now(),
                'journey_completed_at' => now(),
                'is_complete' => true,
                'completion_type' => 'abandoned',
                'is_expired' => $reason === 'expired',
            ]);

            DB::commit();

            return ['success' => true, 'message' => 'Journey abandoned'];

        } catch (\Exception $e) {
            DB::rollBack();
            return ['success' => false, 'message' => 'Failed to abandon journey'];
        }
    }

    /**
     * Validate investment request against journey state
     *
     * Called by InvestorInvestmentController before processing investment.
     *
     * @param int $userId
     * @param int $companyId
     * @param string $journeyToken
     * @return array{valid: bool, journey: InvestorJourney|null, message: string, proof: array|null}
     */
    public function validateInvestmentRequest(int $userId, int $companyId, string $journeyToken): array
    {
        // Find journey by token
        $journey = InvestorJourney::where('journey_token', $journeyToken)
            ->where('user_id', $userId)
            ->where('company_id', $companyId)
            ->first();

        if (!$journey) {
            return [
                'valid' => false,
                'journey' => null,
                'message' => 'Invalid journey token or journey not found',
                'proof' => null,
            ];
        }

        // Check expiry
        if ($journey->hasExpired()) {
            return [
                'valid' => false,
                'journey' => $journey,
                'message' => 'Journey has expired',
                'proof' => null,
            ];
        }

        // Check journey is in correct state for investment
        if (!in_array($journey->current_state, ['confirming', 'processing'])) {
            return [
                'valid' => false,
                'journey' => $journey,
                'message' => "Journey must be in 'confirming' or 'processing' state. Current: {$journey->current_state}",
                'proof' => null,
            ];
        }

        // Validate all required acknowledgements are present
        $sequenceProof = $journey->proveJourneySequence();
        if (!$sequenceProof['is_valid_sequence']) {
            return [
                'valid' => false,
                'journey' => $journey,
                'message' => 'Journey sequence validation failed',
                'proof' => $sequenceProof,
            ];
        }

        // Check all acknowledgements
        if (!$journey->hasCompletedStateAcknowledgements()) {
            return [
                'valid' => false,
                'journey' => $journey,
                'message' => 'Required acknowledgements not completed',
                'proof' => $sequenceProof,
            ];
        }

        return [
            'valid' => true,
            'journey' => $journey,
            'message' => 'Journey validated for investment',
            'proof' => $sequenceProof,
        ];
    }

    /**
     * Get active journey for user/company
     */
    public function getActiveJourney(int $userId, int $companyId): ?InvestorJourney
    {
        return InvestorJourney::where('user_id', $userId)
            ->where('company_id', $companyId)
            ->where('is_complete', false)
            ->where('is_expired', false)
            ->where('expires_at', '>', now())
            ->orderBy('created_at', 'desc')
            ->first();
    }

    /**
     * Get journey by token
     */
    public function getJourneyByToken(string $token): ?InvestorJourney
    {
        return InvestorJourney::where('journey_token', $token)->first();
    }

    // =========================================================================
    // HELPER METHODS
    // =========================================================================

    /**
     * Check if state requirements are satisfied
     */
    protected function checkStateRequirements(InvestorJourney $journey, string $targetState): array
    {
        $requirements = self::STATE_REQUIREMENTS[$targetState] ?? [];
        $missing = [];

        // Check acknowledgement requirements
        if (!empty($requirements['acknowledgements'])) {
            $acknowledged = $journey->acknowledgementBindings()
                ->pluck('acknowledgement_type')
                ->toArray();

            foreach ($requirements['acknowledgements'] as $required) {
                if (!in_array($required, $acknowledged)) {
                    $missing[] = "acknowledgement:{$required}";
                }
            }
        }

        // Check snapshot requirement
        if (!empty($requirements['snapshot_required']) && !$journey->hasSnapshotBinding()) {
            $missing[] = 'snapshot_binding';
        }

        // Check investment requirement
        if (!empty($requirements['investment_required']) && !$journey->company_investment_id) {
            $missing[] = 'investment';
        }

        return [
            'satisfied' => empty($missing),
            'missing' => $missing,
            'message' => empty($missing)
                ? 'All requirements satisfied'
                : 'Missing requirements: ' . implode(', ', $missing),
        ];
    }

    /**
     * Expire old journeys for user/company
     */
    protected function expireOldJourneys(int $userId, int $companyId): void
    {
        InvestorJourney::where('user_id', $userId)
            ->where('company_id', $companyId)
            ->where('is_complete', false)
            ->where(function ($query) {
                $query->where('expires_at', '<', now())
                    ->orWhere('is_expired', true);
            })
            ->each(function ($journey) {
                $this->abandonJourney($journey, 'expired', 'Auto-expired on new journey start');
            });
    }
}
