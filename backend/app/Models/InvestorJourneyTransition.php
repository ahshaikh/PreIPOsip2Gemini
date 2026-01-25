<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * P0 FIX (GAP 18): Investor Journey Transition Model
 *
 * Immutable record of a state transition in an investor's journey.
 * Cannot be modified after creation (audit trail integrity).
 */
class InvestorJourneyTransition extends Model
{
    use HasFactory;

    protected $table = 'investor_journey_transitions';

    public $timestamps = false;

    protected $fillable = [
        'journey_id',
        'from_state',
        'to_state',
        'transition_type',
        'was_valid_transition',
        'validation_result',
        'state_data',
        'acknowledgements_at_transition',
        'snapshot_id_at_transition',
        'triggered_by',
        'ip_address',
        'user_agent',
        'transitioned_at',
    ];

    protected $casts = [
        'was_valid_transition' => 'boolean',
        'state_data' => 'array',
        'acknowledgements_at_transition' => 'array',
        'transitioned_at' => 'datetime',
    ];

    /**
     * Boot - enforce immutability
     */
    protected static function boot()
    {
        parent::boot();

        // Set transition timestamp on create
        static::creating(function ($transition) {
            $transition->transitioned_at = $transition->transitioned_at ?? now();

            // Capture request info
            if (request()) {
                $transition->ip_address = $transition->ip_address ?? request()->ip();
                $transition->user_agent = $transition->user_agent ?? request()->userAgent();
            }
        });

        // Prevent updates (immutable)
        static::updating(function ($transition) {
            throw new \RuntimeException(
                'Journey transitions are immutable and cannot be modified. ' .
                'Transition ID: ' . $transition->id
            );
        });

        // Prevent deletes
        static::deleting(function ($transition) {
            throw new \RuntimeException(
                'Journey transitions cannot be deleted (audit trail integrity). ' .
                'Transition ID: ' . $transition->id
            );
        });
    }

    // =========================================================================
    // RELATIONSHIPS
    // =========================================================================

    public function journey(): BelongsTo
    {
        return $this->belongsTo(InvestorJourney::class, 'journey_id');
    }
}
