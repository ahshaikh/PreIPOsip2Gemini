<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @deprecated PHASE 4.1: This model is DEPRECATED. Use LedgerEntry and LedgerLine instead.
 *
 * MIGRATION NOTICE:
 * - New entries are stored in ledger_entries + ledger_lines tables (true double-entry)
 * - This model remains ONLY for querying historical records
 * - DO NOT create new AdminLedgerEntry records
 * - Use App\Models\LedgerEntry and App\Models\LedgerLine for new entries
 *
 * DATA PRESERVATION:
 * Historical admin_ledger_entries are preserved for audit compliance.
 *
 * ============================================================================
 * LEGACY DOCUMENTATION (for historical context):
 * ============================================================================
 *
 * AdminLedgerEntry Model
 *
 * PROTOCOL:
 * - IMMUTABLE: Cannot be updated or deleted after creation
 * - Double-entry: Every entry has a paired entry (entry_pair_id)
 * - Balance calculated from entries, not stored separately
 * - Source of truth for admin financial state
 */
class AdminLedgerEntry extends Model
{
    protected $fillable = [
        'account',
        'type',
        'amount_paise',
        'balance_before_paise',
        'balance_after_paise',
        'reference_type',
        'reference_id',
        'description',
        'entry_pair_id',
    ];

    protected $casts = [
        'amount_paise' => 'integer',
        'balance_before_paise' => 'integer',
        'balance_after_paise' => 'integer',
    ];

    /**
     * IMMUTABILITY ENFORCEMENT
     *
     * Ledger entries are financial records and MUST NOT be modified.
     * This is a compliance requirement for audit trails.
     */
    protected static function booted()
    {
        static::updating(function () {
            throw new \RuntimeException(
                'Admin ledger entries are immutable. Create a reversal entry instead.'
            );
        });

        static::deleting(function () {
            throw new \RuntimeException(
                'Admin ledger entries cannot be deleted (audit requirement)'
            );
        });
    }

    /**
     * Get paired entry (debit has corresponding credit)
     */
    public function pairedEntry(): BelongsTo
    {
        return $this->belongsTo(self::class, 'entry_pair_id');
    }

    /**
     * Get amount in rupees (for display)
     */
    public function getAmountAttribute(): float
    {
        return $this->amount_paise / 100;
    }

    /**
     * Get balance before in rupees (for display)
     */
    public function getBalanceBeforeAttribute(): float
    {
        return $this->balance_before_paise / 100;
    }

    /**
     * Get balance after in rupees (for display)
     */
    public function getBalanceAfterAttribute(): float
    {
        return $this->balance_after_paise / 100;
    }

    /**
     * Scope: Filter by account
     */
    public function scopeForAccount($query, string $account)
    {
        return $query->where('account', $account);
    }

    /**
     * Scope: Filter by reference
     */
    public function scopeForReference($query, string $type, int $id)
    {
        return $query->where('reference_type', $type)
                     ->where('reference_id', $id);
    }
}
