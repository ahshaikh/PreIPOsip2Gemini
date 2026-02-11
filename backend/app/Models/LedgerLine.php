<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * DOUBLE-ENTRY LEDGER: Debit/Credit Line
 * 
 * Each ledger entry contains multiple lines. Lines represent the actual
 * debits and credits that affect account balances.
 * 
 * IMMUTABILITY:
 * - Lines cannot be updated after creation
 * - Lines cannot be deleted
 * 
 * VALIDATION:
 * - Amount must be positive (direction determines sign)
 * - Direction must be DEBIT or CREDIT
 * 
 * ACCOUNTING RULES:
 * - DEBIT increases ASSET and EXPENSE accounts
 * - CREDIT increases LIABILITY, EQUITY, and INCOME accounts
 * - Each entry's debits must equal credits
 *
 * @property int $id
 * @property int $ledger_entry_id
 * @property int $ledger_account_id
 * @property string $direction
 * @property float $amount
 * @property \Carbon\Carbon $created_at
 * @mixin IdeHelperLedgerLine
 */
class LedgerLine extends Model
{
    use HasFactory;

    protected $table = 'ledger_lines';

    /**
     * Disable updated_at - lines are immutable
     */
    public const UPDATED_AT = null;

    protected $fillable = [
        'ledger_entry_id',
        'ledger_account_id',
        'direction',
        'amount',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'created_at' => 'datetime',
    ];

    /**
     * Boot method for model event handling
     */
    protected static function booted(): void
    {
        // Validate on creation
        static::creating(function (LedgerLine $line) {
            // Amount must be positive
            if ($line->amount <= 0) {
                throw new \RuntimeException(
                    'Ledger line amount must be positive. Got: ' . $line->amount
                );
            }

            // Direction must be valid
            if (!in_array($line->direction, [LedgerAccount::DIRECTION_DEBIT, LedgerAccount::DIRECTION_CREDIT])) {
                throw new \RuntimeException(
                    'Invalid ledger line direction: ' . $line->direction
                );
            }

            // Account must exist
            if (!LedgerAccount::find($line->ledger_account_id)) {
                throw new \RuntimeException(
                    'Ledger account not found: ' . $line->ledger_account_id
                );
            }
        });

        // IMMUTABILITY: Prevent updates
        static::updating(function (LedgerLine $line) {
            throw new \RuntimeException(
                'Ledger lines are immutable. Cannot update line #' . $line->id
            );
        });

        // IMMUTABILITY: Prevent deletion
        static::deleting(function (LedgerLine $line) {
            throw new \RuntimeException(
                'Ledger lines are immutable. Cannot delete line #' . $line->id
            );
        });
    }

    // =========================================================================
    // RELATIONSHIPS
    // =========================================================================

    /**
     * Get the parent ledger entry.
     */
    public function entry(): BelongsTo
    {
        return $this->belongsTo(LedgerEntry::class, 'ledger_entry_id');
    }

    /**
     * Alias for entry() for clarity.
     */
    public function ledgerEntry(): BelongsTo
    {
        return $this->entry();
    }

    /**
     * Get the account this line affects.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(LedgerAccount::class, 'ledger_account_id');
    }

    /**
     * Alias for account() for clarity.
     */
    public function ledgerAccount(): BelongsTo
    {
        return $this->account();
    }

    // =========================================================================
    // HELPERS
    // =========================================================================

    /**
     * Check if this is a debit line.
     */
    public function isDebit(): bool
    {
        return $this->direction === LedgerAccount::DIRECTION_DEBIT;
    }

    /**
     * Check if this is a credit line.
     */
    public function isCredit(): bool
    {
        return $this->direction === LedgerAccount::DIRECTION_CREDIT;
    }

    /**
     * Get the signed amount (positive for increases, negative for decreases).
     *
     * For ASSET/EXPENSE accounts: DEBIT is positive, CREDIT is negative
     * For LIABILITY/EQUITY/INCOME accounts: CREDIT is positive, DEBIT is negative
     */
    public function getSignedAmountAttribute(): float
    {
        $account = $this->account;

        if (!$account) {
            return $this->amount;
        }

        $isIncrease = ($account->isDebitNormal() && $this->isDebit()) ||
                      ($account->isCreditNormal() && $this->isCredit());

        return $isIncrease ? $this->amount : -$this->amount;
    }

    /**
     * Get a human-readable description of this line.
     */
    public function getDescriptionAttribute(): string
    {
        $account = $this->account;
        $accountName = $account ? $account->name : 'Unknown';
        $action = $this->isDebit() ? 'Dr' : 'Cr';

        return "{$action} {$accountName}: ₹" . number_format($this->amount, 2);
    }

    // =========================================================================
    // SCOPES
    // =========================================================================

    /**
     * Scope to debit lines only.
     */
    public function scopeDebits($query)
    {
        return $query->where('direction', LedgerAccount::DIRECTION_DEBIT);
    }

    /**
     * Scope to credit lines only.
     */
    public function scopeCredits($query)
    {
        return $query->where('direction', LedgerAccount::DIRECTION_CREDIT);
    }

    /**
     * Scope to lines for a specific account.
     */
    public function scopeForAccount($query, $accountId)
    {
        return $query->where('ledger_account_id', $accountId);
    }

    /**
     * Scope to lines for accounts of a specific type.
     */
    public function scopeForAccountType($query, string $type)
    {
        return $query->whereHas('account', function ($q) use ($type) {
            $q->where('type', $type);
        });
    }
}
