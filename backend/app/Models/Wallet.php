<?php
// V-PHASE3-1730-076 (Created)
// V-FINAL-1730-356 (Upgraded)
// V-SEC-1730-604 (Unsafe Methods Removed)
// V-AUDIT-FIX-MODULE7 (Performance Optimization)
// V-AUDIT-REFACTOR-2025 (Atomic Integers & Accessors)

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Casts\Attribute;

/**
 * Wallet Model
 *
 * Represents the user's financial wallet.
 *
 * ⚠️ SECURITY NOTE:
 * Do NOT add deposit / withdraw methods here.
 * All financial mutations MUST go through WalletService
 * to guarantee:
 *  - ACID compliance
 *  - Double-entry ledger
 *  - Pessimistic locking (lockForUpdate)
 */
class Wallet extends Model
{
    use HasFactory;

    /**
     * Only atomic, integer-backed fields are persisted.
     */
    protected $fillable = [
        'user_id',
        'balance_paise',
        'locked_balance_paise',
    ];

    /**
     * Casts for atomic math safety.
     */
    protected $casts = [
        'balance_paise' => 'integer',
        'locked_balance_paise' => 'integer',
    ];

    /**
     * Backward-compatible virtual fields (Rupees).
     * These are computed from paise and exposed automatically.
     */
    protected $appends = [
        'balance',
        'locked_balance',
    ];

    // ------------------------------------------------------------------
    // Virtual Accessors & Mutators (Rupees ↔ Paise)
    // ------------------------------------------------------------------

    /**
     * Virtual balance (₹) backed by balance_paise.
     *
     * Allows legacy code to read/write:
     *   $wallet->balance
     */
    protected function balance(): Attribute
    {
        return Attribute::make(
            // Getter: Paise → Rupees
            get: fn ($value, $attributes) =>
                ($attributes['balance_paise'] ?? 0) / 100,

            // Setter: Rupees → Paise
            set: fn ($value) => [
                'balance_paise' => (int) round($value * 100),
            ],
        );
    }

    /**
     * Virtual locked_balance (₹) backed by locked_balance_paise.
     *
     * Allows legacy code to read/write:
     *   $wallet->locked_balance
     */
    protected function lockedBalance(): Attribute
    {
        return Attribute::make(
            // Getter: Paise → Rupees
            get: fn ($value, $attributes) =>
                ($attributes['locked_balance_paise'] ?? 0) / 100,

            // Setter: Rupees → Paise
            set: fn ($value) => [
                'locked_balance_paise' => (int) round($value * 100),
            ],
        );
    }

    /**
     * Available balance (₹).
     * Business logic intentionally minimal here.
     */
    protected function availableBalance(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->balance
        );
    }

    // ------------------------------------------------------------------
    // Relationships
    // ------------------------------------------------------------------

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /*
    |--------------------------------------------------------------------------
    | AUDIT NOTE
    |--------------------------------------------------------------------------
    | Heavy computed accessors (totalDeposited, totalWithdrawn, etc.)
    | were intentionally REMOVED to prevent memory exhaustion and N+1
    | failures in high-transaction wallets.
    |
    | Replacement:
    |   Wallet::withSum('transactions as total_deposited', 'amount')
    |
    */
}
