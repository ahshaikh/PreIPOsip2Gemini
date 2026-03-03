<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * DisputeSnapshot - Immutable state capture at dispute filing time
 *
 * CRITICAL: This model is protected by a database trigger that prevents updates.
 * Once created, a snapshot cannot be modified.
 *
 * Contains:
 * - disputable_snapshot: Complete state of the disputed entity
 * - wallet_snapshot: User's wallet state at filing time
 * - related_transactions_snapshot: Ledger entries, bonuses, etc.
 * - system_state_snapshot: Relevant system settings
 * - integrity_hash: SHA256 for tamper detection
 *
 * @property int $id
 * @property int $dispute_id
 * @property array $disputable_snapshot
 * @property array $wallet_snapshot
 * @property array $related_transactions_snapshot
 * @property array $system_state_snapshot
 * @property string $integrity_hash
 * @property int|null $captured_by_user_id
 * @property string $capture_trigger
 * @property \Carbon\Carbon $created_at
 */
class DisputeSnapshot extends Model
{
    use HasFactory;

    /**
     * Disable timestamps - only created_at is used, managed by database.
     */
    public $timestamps = false;

    protected $fillable = [
        'dispute_id',
        'disputable_snapshot',
        'wallet_snapshot',
        'related_transactions_snapshot',
        'system_state_snapshot',
        'integrity_hash',
        'captured_by_user_id',
        'capture_trigger',
    ];

    protected $casts = [
        'disputable_snapshot' => 'array',
        'wallet_snapshot' => 'array',
        'related_transactions_snapshot' => 'array',
        'system_state_snapshot' => 'array',
        'created_at' => 'datetime',
    ];

    // Capture trigger constants
    public const TRIGGER_DISPUTE_FILED = 'dispute_filed';
    public const TRIGGER_ADMIN_REQUEST = 'admin_request';
    public const TRIGGER_AUTO_ESCALATION = 'auto_escalation';

    /**
     * Relationship: The dispute this snapshot belongs to.
     */
    public function dispute(): BelongsTo
    {
        return $this->belongsTo(Dispute::class);
    }

    /**
     * Relationship: Admin who captured the snapshot (if manual).
     */
    public function capturedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'captured_by_user_id');
    }

    /**
     * Compute the expected integrity hash from current snapshot data.
     * Used by SnapshotIntegrityService to verify no tampering.
     */
    public function computeIntegrityHash(): string
    {
        $data = json_encode([
            'dispute_id' => $this->dispute_id,
            'disputable_snapshot' => $this->disputable_snapshot,
            'wallet_snapshot' => $this->wallet_snapshot,
            'related_transactions_snapshot' => $this->related_transactions_snapshot,
            'system_state_snapshot' => $this->system_state_snapshot,
            'capture_trigger' => $this->capture_trigger,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return hash('sha256', $data);
    }

    /**
     * Verify that the stored hash matches the computed hash.
     */
    public function verifyIntegrity(): bool
    {
        return hash_equals($this->integrity_hash, $this->computeIntegrityHash());
    }

    /**
     * Get all valid capture triggers.
     */
    public static function getCaptureTriggers(): array
    {
        return [
            self::TRIGGER_DISPUTE_FILED,
            self::TRIGGER_ADMIN_REQUEST,
            self::TRIGGER_AUTO_ESCALATION,
        ];
    }

    /**
     * Boot method - compute hash before creating.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (DisputeSnapshot $snapshot) {
            // Always compute fresh hash before insert
            $snapshot->integrity_hash = $snapshot->computeIntegrityHash();
        });
    }
}
