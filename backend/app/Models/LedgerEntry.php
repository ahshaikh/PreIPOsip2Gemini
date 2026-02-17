<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * DOUBLE-ENTRY LEDGER: Journal Entry Header
 * 
 * One ledger entry = one business event. Each entry contains multiple
 * ledger lines that MUST balance (debits = credits).
 * 
 * IMMUTABILITY:
 * - Entries cannot be updated after creation
 * - Entries cannot be deleted
 * - Corrections are made by creating reversal entries
 * 
 * REFERENCE TYPES:
 * - bulk_purchase: Inventory acquisition
 * - user_deposit: User adds funds to wallet
 * - user_investment: User invests in shares
 * - bonus_credit: Bonus awarded to user
 * - withdrawal: User withdraws funds
 * - capital_injection: Owner adds capital
 * - share_sale: Platform sells shares to user
 *
 * @property int $id
 * @property string $reference_type
 * @property int $reference_id
 * @property string|null $description
 * @property \Carbon\Carbon $entry_date
 * @property int|null $created_by
 * @property bool $is_reversal
 * @property int|null $reverses_entry_id
 * @property \Carbon\Carbon $created_at
 * @mixin IdeHelperLedgerEntry
 */
class LedgerEntry extends Model
{
    use HasFactory;

    /**
     * Reference type constants
     */
    public const REF_BULK_PURCHASE = 'bulk_purchase';
    public const REF_BULK_PURCHASE_REVERSAL = 'bulk_purchase_reversal';
    public const REF_USER_DEPOSIT = 'user_deposit';
    public const REF_USER_INVESTMENT = 'user_investment';
    public const REF_SHARE_ALLOCATION = 'share_allocation'; // DEPRECATED: No longer used in expense model
    public const REF_BONUS_CREDIT = 'bonus_credit';
    public const REF_WITHDRAWAL = 'withdrawal';
    public const REF_WITHDRAWAL_REVERSAL = 'withdrawal_reversal';
    public const REF_REFUND = 'refund';
    public const REF_CAPITAL_INJECTION = 'capital_injection';
    public const REF_SHARE_SALE = 'share_sale';
    public const REF_TDS_DEDUCTION = 'tds_deduction';
    public const REF_TDS_REMITTANCE = 'tds_remittance'; // PHASE 4.2: TDS payment to government
    public const REF_GATEWAY_FEE = 'gateway_fee';
    public const REF_MANUAL_ADJUSTMENT = 'manual_adjustment';
    public const REF_BONUS_USAGE = 'bonus_usage'; // PHASE 4 SECTION 7.2: Cost recognition for bonus-funded shares
    public const REF_SUBSCRIPTION_FEE = 'subscription_fee'; // Subscription fee earned by platform
    public const REF_OPERATING_EXPENSE = 'operating_expense'; // Platform operating expenses (rent, salaries, etc.)
    public const REF_PROFIT_SHARE = 'profit_share'; // Profit share distribution to users
    public const REF_CHARGEBACK = 'chargeback'; // V-DISPUTE-REMEDIATION-2026: Bank-initiated reversal
    public const REF_CHARGEBACK_RECEIVABLE = 'chargeback_receivable'; // V-CHARGEBACK-HARDENING-2026: User owes shortfall

    protected $table = 'ledger_entries';

    /**
     * Disable updated_at - entries are immutable
     */
    public const UPDATED_AT = null;

    protected $fillable = [
        'reference_type',
        'reference_id',
        'description',
        'entry_date',
        'created_by',
        'is_reversal',
        'reverses_entry_id',
    ];

    protected $casts = [
        'entry_date' => 'date',
        'is_reversal' => 'boolean',
        'created_at' => 'datetime',
    ];

    /**
     * Boot method for model event handling
     */
    protected static function booted(): void
    {
        // IMMUTABILITY: Prevent updates
        static::updating(function (LedgerEntry $entry) {
            throw new \RuntimeException(
                'Ledger entries are immutable. Cannot update entry #' . $entry->id .
                '. Create a reversal entry instead.'
            );
        });

        // IMMUTABILITY: Prevent deletion
        static::deleting(function (LedgerEntry $entry) {
            throw new \RuntimeException(
                'Ledger entries are immutable. Cannot delete entry #' . $entry->id .
                '. Create a reversal entry instead.'
            );
        });

        // Log creation for audit trail
        static::created(function (LedgerEntry $entry) {
            \Log::info('Ledger entry created', [
                'entry_id' => $entry->id,
                'reference_type' => $entry->reference_type,
                'reference_id' => $entry->reference_id,
                'description' => $entry->description,
                'entry_date' => $entry->entry_date->toDateString(),
                'is_reversal' => $entry->is_reversal,
                'created_by' => $entry->created_by,
            ]);
        });
    }

    // =========================================================================
    // RELATIONSHIPS
    // =========================================================================

    /**
     * Get all ledger lines (debits/credits) for this entry.
     */
    public function lines(): HasMany
    {
        return $this->hasMany(LedgerLine::class, 'ledger_entry_id');
    }

    /**
     * Alias for lines() for clarity.
     */
    public function ledgerLines(): HasMany
    {
        return $this->lines();
    }

    /**
     * Get the entry this reverses (if is_reversal=true).
     */
    public function reversedEntry(): BelongsTo
    {
        return $this->belongsTo(LedgerEntry::class, 'reverses_entry_id');
    }

    /**
     * Get all entries that reverse this one.
     */
    public function reversalEntries(): HasMany
    {
        return $this->hasMany(LedgerEntry::class, 'reverses_entry_id');
    }

    /**
     * Get the admin/user who created this entry.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // =========================================================================
    // BALANCE VALIDATION
    // =========================================================================

    /**
     * Check if this entry is balanced (debits = credits).
     *
     * THIS IS THE FUNDAMENTAL INVARIANT OF DOUBLE-ENTRY ACCOUNTING.
     *
     * Uses amount_paise (BIGINT) for exact integer comparison.
     */
    public function isBalanced(): bool
    {
        $totals = $this->lines()
            ->selectRaw('direction, SUM(amount_paise) as total')
            ->groupBy('direction')
            ->pluck('total', 'direction')
            ->toArray();

        $debitsPaise = (int) ($totals[LedgerAccount::DIRECTION_DEBIT] ?? 0);
        $creditsPaise = (int) ($totals[LedgerAccount::DIRECTION_CREDIT] ?? 0);

        // Integer comparison - exact match required
        return $debitsPaise === $creditsPaise;
    }

    /**
     * Get total debits for this entry (in rupees for display).
     */
    public function getTotalDebitsAttribute(): float
    {
        $totalPaise = (int) $this->lines()
            ->where('direction', LedgerAccount::DIRECTION_DEBIT)
            ->sum('amount_paise');

        return $totalPaise / 100;
    }

    /**
     * Get total credits for this entry (in rupees for display).
     */
    public function getTotalCreditsAttribute(): float
    {
        $totalPaise = (int) $this->lines()
            ->where('direction', LedgerAccount::DIRECTION_CREDIT)
            ->sum('amount_paise');

        return $totalPaise / 100;
    }

    /**
     * Get the imbalance amount (should always be 0 for valid entries).
     * Returns rupee value for display.
     */
    public function getImbalanceAttribute(): float
    {
        return abs($this->total_debits - $this->total_credits);
    }

    // =========================================================================
    // REVERSAL HELPERS
    // =========================================================================

    /**
     * Check if this entry has been reversed.
     */
    public function isReversed(): bool
    {
        return $this->reversalEntries()->exists();
    }

    /**
     * Check if this entry can still be reversed.
     */
    public function canBeReversed(): bool
    {
        // Cannot reverse an already reversed entry
        if ($this->isReversed()) {
            return false;
        }

        // Cannot reverse a reversal entry
        if ($this->is_reversal) {
            return false;
        }

        return true;
    }

    // =========================================================================
    // SCOPES
    // =========================================================================

    /**
     * Scope to entries of a specific reference type.
     */
    public function scopeForReference($query, string $type, ?int $id = null)
    {
        $query->where('reference_type', $type);

        if ($id !== null) {
            $query->where('reference_id', $id);
        }

        return $query;
    }

    /**
     * Scope to non-reversal entries only.
     */
    public function scopeNotReversals($query)
    {
        return $query->where('is_reversal', false);
    }

    /**
     * Scope to reversal entries only.
     */
    public function scopeReversalsOnly($query)
    {
        return $query->where('is_reversal', true);
    }

    /**
     * Scope to entries on a specific date.
     */
    public function scopeOnDate($query, $date)
    {
        return $query->whereDate('entry_date', $date);
    }

    /**
     * Scope to entries between dates.
     */
    public function scopeBetweenDates($query, $start, $end)
    {
        return $query->whereBetween('entry_date', [$start, $end]);
    }
}
