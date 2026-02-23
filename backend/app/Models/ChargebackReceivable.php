<?php
// V-WAVE3-REVERSAL-AUDIT: Dedicated receivable tracking model
// Provides granular audit trail for each chargeback/refund shortfall

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChargebackReceivable extends Model
{
    use HasFactory;

    const STATUS_PENDING = 'pending';
    const STATUS_PARTIAL = 'partial';
    const STATUS_SETTLED = 'settled';
    const STATUS_WRITTEN_OFF = 'written_off';

    const SOURCE_REFUND = 'refund';
    const SOURCE_CHARGEBACK = 'chargeback';

    protected $fillable = [
        'user_id',
        'payment_id',
        'ledger_entry_id',
        'amount_paise',
        'paid_paise',
        'balance_paise',
        'status',
        'source_type',
        'reason',
        'settled_at',
        'written_off_at',
        'written_off_by',
        'write_off_reason',
    ];

    protected $casts = [
        'amount_paise' => 'integer',
        'paid_paise' => 'integer',
        'balance_paise' => 'integer',
        'settled_at' => 'datetime',
        'written_off_at' => 'datetime',
    ];

    // =========================================================================
    // RELATIONSHIPS
    // =========================================================================

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function ledgerEntry(): BelongsTo
    {
        return $this->belongsTo(LedgerEntry::class);
    }

    public function writtenOffByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'written_off_by');
    }

    // =========================================================================
    // ACCESSORS
    // =========================================================================

    /**
     * Get amount in rupees.
     */
    public function getAmountAttribute(): float
    {
        return $this->amount_paise / 100;
    }

    /**
     * Get paid amount in rupees.
     */
    public function getPaidAttribute(): float
    {
        return $this->paid_paise / 100;
    }

    /**
     * Get balance in rupees.
     */
    public function getBalanceAttribute(): float
    {
        return $this->balance_paise / 100;
    }

    /**
     * Check if fully settled.
     */
    public function isSettled(): bool
    {
        return $this->status === self::STATUS_SETTLED;
    }

    /**
     * Check if written off.
     */
    public function isWrittenOff(): bool
    {
        return $this->status === self::STATUS_WRITTEN_OFF;
    }

    /**
     * Check if has outstanding balance.
     */
    public function hasOutstandingBalance(): bool
    {
        return $this->balance_paise > 0 && !$this->isWrittenOff();
    }

    // =========================================================================
    // MUTATIONS
    // =========================================================================

    /**
     * Apply a payment towards this receivable.
     *
     * @param int $amountPaise Amount to apply in paise
     * @return int Amount actually applied (may be less if receivable is smaller)
     */
    public function applyPayment(int $amountPaise): int
    {
        $applicableAmount = min($amountPaise, $this->balance_paise);

        if ($applicableAmount <= 0) {
            return 0;
        }

        $this->paid_paise += $applicableAmount;
        $this->balance_paise -= $applicableAmount;

        if ($this->balance_paise <= 0) {
            $this->status = self::STATUS_SETTLED;
            $this->settled_at = now();
        } else {
            $this->status = self::STATUS_PARTIAL;
        }

        $this->save();

        return $applicableAmount;
    }

    /**
     * Write off this receivable (admin action).
     *
     * @param int $adminId Admin user ID
     * @param string $reason Write-off reason
     */
    public function writeOff(int $adminId, string $reason): void
    {
        $this->status = self::STATUS_WRITTEN_OFF;
        $this->written_off_at = now();
        $this->written_off_by = $adminId;
        $this->write_off_reason = $reason;
        $this->save();
    }

    // =========================================================================
    // SCOPES
    // =========================================================================

    /**
     * Scope to outstanding (unpaid) receivables.
     */
    public function scopeOutstanding($query)
    {
        return $query->whereIn('status', [self::STATUS_PENDING, self::STATUS_PARTIAL]);
    }

    /**
     * Scope to receivables for a specific user.
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }
}
