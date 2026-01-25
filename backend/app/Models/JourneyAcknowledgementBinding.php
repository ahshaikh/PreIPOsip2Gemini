<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * P0 FIX (GAP 19-20): Journey Acknowledgement Binding Model
 *
 * Binds acknowledgements to specific journey states and snapshots.
 * Proves what the investor saw and acknowledged at each step.
 *
 * IMMUTABLE: Cannot be modified after creation.
 */
class JourneyAcknowledgementBinding extends Model
{
    use HasFactory;

    protected $table = 'journey_acknowledgement_bindings';

    protected $fillable = [
        'journey_id',
        'acknowledgement_type',
        'acknowledgement_key',
        'acknowledgement_version',
        'journey_state_at_ack',
        'transition_id',
        'snapshot_id_at_ack',
        'snapshot_hash',
        'acknowledgement_text',
        'explicit_consent',
        'acknowledged_at',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'snapshot_hash' => 'array',
        'explicit_consent' => 'boolean',
        'acknowledged_at' => 'datetime',
    ];

    /**
     * Boot - enforce immutability
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($binding) {
            $binding->acknowledged_at = $binding->acknowledged_at ?? now();

            // Capture request info
            if (request()) {
                $binding->ip_address = $binding->ip_address ?? request()->ip();
                $binding->user_agent = $binding->user_agent ?? request()->userAgent();
            }
        });

        // Prevent updates (immutable)
        static::updating(function ($binding) {
            throw new \RuntimeException(
                'Acknowledgement bindings are immutable and cannot be modified. ' .
                'Binding ID: ' . $binding->id
            );
        });

        // Prevent deletes
        static::deleting(function ($binding) {
            throw new \RuntimeException(
                'Acknowledgement bindings cannot be deleted (audit trail integrity). ' .
                'Binding ID: ' . $binding->id
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

    public function transition(): BelongsTo
    {
        return $this->belongsTo(InvestorJourneyTransition::class, 'transition_id');
    }

    // =========================================================================
    // VERIFICATION HELPERS
    // =========================================================================

    /**
     * Verify snapshot integrity
     */
    public function verifySnapshotIntegrity(string $currentSnapshotHash): bool
    {
        if (empty($this->snapshot_hash)) {
            return false;
        }

        return $this->snapshot_hash['content_hash'] === $currentSnapshotHash;
    }

    /**
     * Get proof of acknowledgement
     */
    public function getProof(): array
    {
        return [
            'binding_id' => $this->id,
            'journey_id' => $this->journey_id,
            'type' => $this->acknowledgement_type,
            'key' => $this->acknowledgement_key,
            'version' => $this->acknowledgement_version,
            'journey_state' => $this->journey_state_at_ack,
            'snapshot_id' => $this->snapshot_id_at_ack,
            'explicit_consent' => $this->explicit_consent,
            'acknowledged_at' => $this->acknowledged_at,
            'ip_address' => $this->ip_address,
        ];
    }
}
