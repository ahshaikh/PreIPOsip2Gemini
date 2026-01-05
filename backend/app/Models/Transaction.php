<?php
// V-PHASE3-1730-077 (Created)
// V-PHASE3-1730-355
// V-FINAL-1730-450 (TDS Paise Migration)
// V-AUDIT-REFACTOR-2025 (Atomic Integers, Immutability, Ledger Safety)

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Str;

/**
 * Transaction Model
 *
 * Immutable, append-only financial ledger.
 *
 * CORE GUARANTEES:
 * - ALL monetary values stored in PAISA (integer)
 * - Rupee values exposed ONLY via virtual accessors
 * - Transactions are IMMUTABLE after creation
 * - Reversals must be explicit (no updates)
 */
class Transaction extends Model
{
    use HasFactory;

    /**
     * Only atomic, auditable fields are mass assignable.
     */
    protected $fillable = [
        'wallet_id',
        'user_id',
        'transaction_id',
        'type',                     // credit | debit
        'status',                   // completed | pending | reversed | failed
        'reference_type',           // SystemGenesis | Payment | Withdrawal | etc
        'reference_id',

        // Atomic monetary fields (PAISE)
        'amount_paise',
        'balance_before_paise',
        'balance_after_paise',
        'tds_deducted_paise',

        'description',
        'is_reversed',
        'reversed_at',
    ];

    /**
     * Casts for atomic math + safety.
     */
    protected $casts = [
        'amount_paise' => 'integer',
        'balance_before_paise' => 'integer',
        'balance_after_paise' => 'integer',
        'tds_deducted_paise' => 'integer',
        'is_reversed' => 'boolean',
        'reversed_at' => 'datetime',
    ];

    /**
     * Virtual rupee fields for API / frontend compatibility.
     */
    protected $appends = [
        'amount',
        'balance_before',
        'balance_after',
        'tds_deducted',
    ];

    // ------------------------------------------------------------------
    // BOOT LOGIC (UUID + IMMUTABILITY)
    // ------------------------------------------------------------------

    protected static function booted(): void
    {
        static::creating(function (Transaction $transaction) {
            // Auto-generate UUID
            if (empty($transaction->transaction_id)) {
                $transaction->transaction_id = (string) Str::uuid();
            }

            // Ensure TDS always exists (paise)
            if (! isset($transaction->tds_deducted_paise)) {
                $transaction->tds_deducted_paise = 0;
            }

            // Default flags
            $transaction->is_reversed = $transaction->is_reversed ?? false;
        });

        // 🚫 HARD GUARD: Ledger is append-only
        static::updating(function () {
            throw new \RuntimeException(
                'Transactions are immutable. Create reversal entries instead.'
            );
        });
    }

    // ------------------------------------------------------------------
    // VIRTUAL ACCESSORS (PAISE → RUPEES)
    // ------------------------------------------------------------------

    protected function amount(): Attribute
    {
        return Attribute::make(
            get: fn ($value, $attributes) =>
                ($attributes['amount_paise'] ?? 0) / 100
        );
    }

    protected function balanceBefore(): Attribute
    {
        return Attribute::make(
            get: fn ($value, $attributes) =>
                ($attributes['balance_before_paise'] ?? 0) / 100
        );
    }

    protected function balanceAfter(): Attribute
    {
        return Attribute::make(
            get: fn ($value, $attributes) =>
                ($attributes['balance_after_paise'] ?? 0) / 100
        );
    }

    protected function tdsDeducted(): Attribute
    {
        return Attribute::make(
            get: fn ($value, $attributes) =>
                ($attributes['tds_deducted_paise'] ?? 0) / 100
        );
    }

    // ------------------------------------------------------------------
    // RELATIONSHIPS
    // ------------------------------------------------------------------

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reference()
    {
        return $this->morphTo();
    }

    // ------------------------------------------------------------------
    // SCOPES (Chainable)
    // ------------------------------------------------------------------

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', 'completed');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }
}
