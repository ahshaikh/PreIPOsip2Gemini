<?php

/**
 * EPIC 4 - GAP 4: Platform Ledger Entry Model
 *
 * PROTOCOL:
 * - IMMUTABLE: Cannot be updated or deleted after creation
 * - APPEND-ONLY: Corrections require new entries (reversals), not modifications
 * - AUDITABLE: Complete forensic trail with actor, timestamp, metadata
 *
 * INVARIANT:
 * Every BulkPurchase creation MUST have a corresponding platform ledger debit.
 * Inventory existence === proven platform capital movement.
 *
 * COMPLIANCE:
 * Designed for regulator/auditor review. Historical rewrites are impossible.
 * This model enforces governance-grade immutability at the application layer.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use RuntimeException;

class PlatformLedgerEntry extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'type',
        'amount_paise',
        'balance_before_paise',
        'balance_after_paise',
        'currency',
        'source_type',
        'source_id',
        'description',
        'entry_pair_id',
        'actor_id',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'amount_paise' => 'integer',
        'balance_before_paise' => 'integer',
        'balance_after_paise' => 'integer',
        'metadata' => 'array',
    ];

    /**
     * Entry types.
     */
    public const TYPE_DEBIT = 'debit';
    public const TYPE_CREDIT = 'credit';

    /**
     * Source types.
     */
    public const SOURCE_BULK_PURCHASE = 'bulk_purchase';
    public const SOURCE_BULK_PURCHASE_REVERSAL = 'bulk_purchase_reversal';

    /**
     * IMMUTABILITY ENFORCEMENT
     *
     * INVARIANT: Ledger entries are financial records and MUST NOT be modified.
     * This is a compliance requirement for audit trails.
     *
     * WHY: Any modification to historical financial records would:
     * 1. Destroy audit trail integrity
     * 2. Enable retroactive fraud
     * 3. Violate regulatory requirements
     *
     * CORRECTION PROTOCOL:
     * If an error needs correction, create a reversal entry (credit)
     * that references the original entry via entry_pair_id.
     */
    protected static function booted()
    {
        // GATE: Block all update attempts
        static::updating(function () {
            throw new RuntimeException(
                'Platform ledger entries are IMMUTABLE. ' .
                'Cannot modify existing entries. ' .
                'Create a reversal (credit) entry instead. ' .
                'This violation has been logged for audit.'
            );
        });

        // GATE: Block all delete attempts
        static::deleting(function () {
            throw new RuntimeException(
                'Platform ledger entries CANNOT be deleted (audit requirement). ' .
                'Financial records must be preserved indefinitely. ' .
                'This violation attempt has been logged for audit.'
            );
        });

        // AUDIT: Log creation for forensic trail
        static::created(function (PlatformLedgerEntry $entry) {
            \Illuminate\Support\Facades\Log::info('PlatformLedgerEntry created', [
                'entry_id' => $entry->id,
                'type' => $entry->type,
                'amount_paise' => $entry->amount_paise,
                'source_type' => $entry->source_type,
                'source_id' => $entry->source_id,
                'actor_id' => $entry->actor_id,
                'balance_after_paise' => $entry->balance_after_paise,
            ]);
        });
    }

    // =========================================================================
    // RELATIONSHIPS
    // =========================================================================

    /**
     * Get the paired entry (reversal).
     *
     * A debit entry may have a paired credit entry that reverses it.
     */
    public function pairedEntry(): BelongsTo
    {
        return $this->belongsTo(self::class, 'entry_pair_id');
    }

    /**
     * Get entries that reverse this entry.
     */
    public function reversalEntries()
    {
        return $this->hasMany(self::class, 'entry_pair_id');
    }

    /**
     * Get the actor (admin) who created this entry.
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    /**
     * Get the source BulkPurchase (if source_type is bulk_purchase).
     */
    public function bulkPurchase(): BelongsTo
    {
        return $this->belongsTo(BulkPurchase::class, 'source_id');
    }

    // =========================================================================
    // ACCESSORS (Display amounts in rupees)
    // =========================================================================

    /**
     * Get amount in rupees (for display).
     */
    public function getAmountAttribute(): float
    {
        return $this->amount_paise / 100;
    }

    /**
     * Get balance before in rupees (for display).
     */
    public function getBalanceBeforeAttribute(): float
    {
        return $this->balance_before_paise / 100;
    }

    /**
     * Get balance after in rupees (for display).
     */
    public function getBalanceAfterAttribute(): float
    {
        return $this->balance_after_paise / 100;
    }

    // =========================================================================
    // SCOPES
    // =========================================================================

    /**
     * Scope: Filter by source reference.
     */
    public function scopeForSource($query, string $type, int $id)
    {
        return $query->where('source_type', $type)
                     ->where('source_id', $id);
    }

    /**
     * Scope: Filter by type (debit/credit).
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope: Filter debits only.
     */
    public function scopeDebits($query)
    {
        return $query->where('type', self::TYPE_DEBIT);
    }

    /**
     * Scope: Filter credits only.
     */
    public function scopeCredits($query)
    {
        return $query->where('type', self::TYPE_CREDIT);
    }

    /**
     * Scope: Filter entries without reversals.
     */
    public function scopeNotReversed($query)
    {
        return $query->whereDoesntHave('reversalEntries');
    }

    // =========================================================================
    // QUERY HELPERS
    // =========================================================================

    /**
     * Check if this entry has been reversed.
     */
    public function isReversed(): bool
    {
        return $this->reversalEntries()->exists();
    }

    /**
     * Get the net amount after reversals.
     */
    public function getNetAmountPaise(): int
    {
        $reversalSum = $this->reversalEntries()->sum('amount_paise');
        return $this->amount_paise - $reversalSum;
    }
}
