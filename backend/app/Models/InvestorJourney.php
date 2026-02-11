<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * P0 FIX (GAP 18): Investor Journey State Model
 * 
 * Represents an investor's journey through the investment flow for a specific company.
 * Tracks state transitions, acknowledgements, and snapshot bindings.
 * 
 * STATES:
 * - initiated: Journey started
 * - viewing: Viewing company details
 * - acknowledging: Reading/accepting risks
 * - reviewing: Reviewing terms
 * - confirming: Final confirmation
 * - processing: Payment in progress
 * - invested: Successfully completed (terminal)
 * - blocked: Blocked by compliance (terminal)
 * - abandoned: Journey abandoned (terminal)
 *
 * @mixin IdeHelperInvestorJourney
 */
class InvestorJourney extends Model
{
    use HasFactory;

    protected $table = 'investor_journeys';

    protected $fillable = [
        'user_id',
        'company_id',
        'current_state',
        'state_entered_at',
        'journey_token',
        'journey_started_at',
        'journey_completed_at',
        'is_complete',
        'completion_type',
        'platform_snapshot_id',
        'investment_snapshot_id',
        'snapshot_bound_at',
        'acknowledged_risks',
        'risks_acknowledged_at',
        'accepted_terms',
        'terms_accepted_at',
        'company_investment_id',
        'block_reason',
        'block_code',
        'blocked_at',
        'session_id',
        'ip_address',
        'user_agent',
        'device_fingerprint',
        'expires_at',
        'is_expired',
    ];

    protected $casts = [
        'state_entered_at' => 'datetime',
        'journey_started_at' => 'datetime',
        'journey_completed_at' => 'datetime',
        'is_complete' => 'boolean',
        'snapshot_bound_at' => 'datetime',
        'acknowledged_risks' => 'array',
        'risks_acknowledged_at' => 'datetime',
        'accepted_terms' => 'array',
        'terms_accepted_at' => 'datetime',
        'blocked_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_expired' => 'boolean',
    ];

    /**
     * Valid journey states
     */
    const STATES = [
        'initiated',
        'viewing',
        'acknowledging',
        'reviewing',
        'confirming',
        'processing',
        'invested',
        'blocked',
        'abandoned',
    ];

    /**
     * Terminal states (journey cannot continue)
     */
    const TERMINAL_STATES = ['invested', 'blocked', 'abandoned'];

    /**
     * State order for validation
     */
    const STATE_ORDER = [
        'initiated' => 0,
        'viewing' => 1,
        'acknowledging' => 2,
        'reviewing' => 3,
        'confirming' => 4,
        'processing' => 5,
        'invested' => 6,
        'blocked' => 99,
        'abandoned' => 99,
    ];

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($journey) {
            // Generate unique journey token
            if (empty($journey->journey_token)) {
                $journey->journey_token = Str::random(64);
            }

            // Set initial timestamps
            if (empty($journey->journey_started_at)) {
                $journey->journey_started_at = now();
            }
            if (empty($journey->state_entered_at)) {
                $journey->state_entered_at = now();
            }

            // Set default expiry (2 hours)
            if (empty($journey->expires_at)) {
                $journey->expires_at = now()->addHours(2);
            }

            // Capture request info
            if (request()) {
                $journey->ip_address = $journey->ip_address ?? request()->ip();
                $journey->user_agent = $journey->user_agent ?? request()->userAgent();
                $journey->session_id = $journey->session_id ?? session()->getId();
            }
        });
    }

    // =========================================================================
    // RELATIONSHIPS
    // =========================================================================

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function transitions(): HasMany
    {
        return $this->hasMany(InvestorJourneyTransition::class, 'journey_id');
    }

    public function acknowledgementBindings(): HasMany
    {
        return $this->hasMany(JourneyAcknowledgementBinding::class, 'journey_id');
    }

    public function investment(): BelongsTo
    {
        return $this->belongsTo(CompanyInvestment::class, 'company_investment_id');
    }

    // =========================================================================
    // STATE HELPERS
    // =========================================================================

    /**
     * Check if journey is in a terminal state
     */
    public function isTerminal(): bool
    {
        return in_array($this->current_state, self::TERMINAL_STATES);
    }

    /**
     * Check if journey has expired
     */
    public function hasExpired(): bool
    {
        return $this->is_expired || now()->gt($this->expires_at);
    }

    /**
     * Check if journey can transition to a state
     */
    public function canTransitionTo(string $toState): bool
    {
        // Cannot transition if terminal
        if ($this->isTerminal()) {
            return false;
        }

        // Cannot transition if expired
        if ($this->hasExpired()) {
            return false;
        }

        // Block and abandon are always allowed (emergency exits)
        if (in_array($toState, ['blocked', 'abandoned'])) {
            return true;
        }

        // Validate state order (cannot skip states)
        $currentOrder = self::STATE_ORDER[$this->current_state] ?? 0;
        $targetOrder = self::STATE_ORDER[$toState] ?? 0;

        // Can only advance by 1 step (no skipping)
        return $targetOrder === $currentOrder + 1;
    }

    /**
     * Get the next valid state in the journey
     */
    public function getNextState(): ?string
    {
        if ($this->isTerminal()) {
            return null;
        }

        $currentOrder = self::STATE_ORDER[$this->current_state] ?? 0;

        foreach (self::STATE_ORDER as $state => $order) {
            if ($order === $currentOrder + 1 && !in_array($state, ['blocked', 'abandoned'])) {
                return $state;
            }
        }

        return null;
    }

    /**
     * Check if a specific acknowledgement is required for current state
     */
    public function requiresAcknowledgement(string $type): bool
    {
        $stateRequirements = [
            'acknowledging' => ['risk_disclosure', 'investment_risks'],
            'reviewing' => ['terms_conditions', 'privacy_policy'],
            'confirming' => ['final_investment_terms'],
        ];

        $required = $stateRequirements[$this->current_state] ?? [];
        return in_array($type, $required);
    }

    /**
     * Check if all required acknowledgements for current state are complete
     */
    public function hasCompletedStateAcknowledgements(): bool
    {
        $requirements = [
            'acknowledging' => ['risk_disclosure', 'investment_risks'],
            'reviewing' => ['terms_conditions', 'privacy_policy'],
            'confirming' => ['final_investment_terms'],
        ];

        $required = $requirements[$this->current_state] ?? [];

        if (empty($required)) {
            return true;
        }

        $completed = $this->acknowledgementBindings()
            ->whereIn('acknowledgement_type', $required)
            ->pluck('acknowledgement_type')
            ->toArray();

        return count(array_diff($required, $completed)) === 0;
    }

    // =========================================================================
    // SNAPSHOT BINDING
    // =========================================================================

    /**
     * Bind platform snapshot to this journey
     */
    public function bindPlatformSnapshot(int $snapshotId): void
    {
        $this->update([
            'platform_snapshot_id' => $snapshotId,
            'snapshot_bound_at' => now(),
        ]);
    }

    /**
     * Bind investment snapshot to this journey
     */
    public function bindInvestmentSnapshot(int $snapshotId): void
    {
        $this->update([
            'investment_snapshot_id' => $snapshotId,
        ]);
    }

    /**
     * Check if snapshots are bound
     */
    public function hasSnapshotBinding(): bool
    {
        return $this->platform_snapshot_id !== null;
    }

    // =========================================================================
    // AUDIT HELPERS
    // =========================================================================

    /**
     * Get complete journey audit trail
     */
    public function getAuditTrail(): array
    {
        return [
            'journey_token' => $this->journey_token,
            'user_id' => $this->user_id,
            'company_id' => $this->company_id,
            'started_at' => $this->journey_started_at,
            'completed_at' => $this->journey_completed_at,
            'final_state' => $this->current_state,
            'completion_type' => $this->completion_type,
            'platform_snapshot_id' => $this->platform_snapshot_id,
            'investment_snapshot_id' => $this->investment_snapshot_id,
            'transitions' => $this->transitions()->orderBy('transitioned_at')->get()->map(fn($t) => [
                'from' => $t->from_state,
                'to' => $t->to_state,
                'at' => $t->transitioned_at,
                'valid' => $t->was_valid_transition,
            ])->toArray(),
            'acknowledgements' => $this->acknowledgementBindings()->get()->map(fn($a) => [
                'type' => $a->acknowledgement_type,
                'key' => $a->acknowledgement_key,
                'at' => $a->acknowledged_at,
                'state' => $a->journey_state_at_ack,
            ])->toArray(),
            'session_info' => [
                'session_id' => $this->session_id,
                'ip_address' => $this->ip_address,
                'device_fingerprint' => $this->device_fingerprint,
            ],
        ];
    }

    /**
     * Prove that investor followed required sequence
     */
    public function proveJourneySequence(): array
    {
        $transitions = $this->transitions()
            ->orderBy('transitioned_at')
            ->get();

        $proof = [
            'journey_token' => $this->journey_token,
            'is_valid_sequence' => true,
            'sequence' => [],
            'violations' => [],
        ];

        $previousState = 'initiated';
        $previousOrder = 0;

        foreach ($transitions as $transition) {
            $fromOrder = self::STATE_ORDER[$transition->from_state] ?? 0;
            $toOrder = self::STATE_ORDER[$transition->to_state] ?? 0;

            $isValidStep = $transition->was_valid_transition &&
                          ($toOrder === $fromOrder + 1 || in_array($transition->to_state, ['blocked', 'abandoned']));

            $proof['sequence'][] = [
                'from' => $transition->from_state,
                'to' => $transition->to_state,
                'at' => $transition->transitioned_at,
                'valid' => $isValidStep,
                'acknowledgements_present' => !empty($transition->acknowledgements_at_transition),
                'snapshot_bound' => $transition->snapshot_id_at_transition !== null,
            ];

            if (!$isValidStep) {
                $proof['is_valid_sequence'] = false;
                $proof['violations'][] = [
                    'transition' => "{$transition->from_state} → {$transition->to_state}",
                    'reason' => 'Invalid state transition (possible skip)',
                    'at' => $transition->transitioned_at,
                ];
            }

            $previousState = $transition->to_state;
            $previousOrder = $toOrder;
        }

        return $proof;
    }
}
