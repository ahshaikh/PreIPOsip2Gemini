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
 *
 * @mixin IdeHelperWallet
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
    // Virtual Accessors (Rupees - READ-ONLY)
    // ------------------------------------------------------------------

    /**
     * Virtual balance (₹) backed by balance_paise.
     * READ-ONLY: For display/API serialization only.
     *
     * ⚠️ FINANCIAL INTEGRITY: No setter provided.
     * All balance mutations MUST go through WalletService which uses
     * increment('balance_paise') / decrement('balance_paise') for atomic math.
     *
     * Direct writes to balance are FORBIDDEN. Use:
     *   WalletService::deposit() or WalletService::withdraw()
     */
    protected function balance(): Attribute
    {
        return Attribute::make(
            get: fn ($value, $attributes) =>
                ($attributes['balance_paise'] ?? 0) / 100,
        );
    }

    /**
     * Virtual locked_balance (₹) backed by locked_balance_paise.
     * READ-ONLY: For display/API serialization only.
     *
     * ⚠️ FINANCIAL INTEGRITY: No setter provided.
     * All locked balance mutations MUST go through WalletService which uses
     * increment('locked_balance_paise') / decrement('locked_balance_paise').
     */
    protected function lockedBalance(): Attribute
    {
        return Attribute::make(
            get: fn ($value, $attributes) =>
                ($attributes['locked_balance_paise'] ?? 0) / 100,
        );
    }

    /**
     * Available balance (₹).
     * FIX 18: Now accounts for locked funds
     */
    protected function availableBalance(): Attribute
    {
        return Attribute::make(
            get: fn () => max(0, $this->balance - $this->locked_balance)
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

    /**
     * FIX 18: Fund locks relationship
     */
    public function fundLocks(): HasMany
    {
        return $this->hasMany(FundLock::class, 'user_id', 'user_id');
    }

    /**
     * FIX 18: Active fund locks
     */
    public function activeFundLocks(): HasMany
    {
        return $this->fundLocks()->where('status', 'active');
    }

    // ------------------------------------------------------------------
    // FIX 18: Fund Locking Methods
    // ------------------------------------------------------------------

    /**
     * Lock funds (reserve for pending transaction)
     *
     * @param float $amount Amount in rupees
     * @param string $lockType Type of lock (withdrawal, investment_hold, etc.)
     * @param Model $lockable The entity causing the lock (Withdrawal, Subscription, etc.)
     * @param array $metadata Additional data
     * @return FundLock
     * @throws \RuntimeException
     */
    public function lockFunds(
        float $amount,
        string $lockType,
        Model $lockable,
        array $metadata = []
    ): FundLock {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Lock amount must be positive');
        }

        if ($this->available_balance < $amount) {
            throw new \RuntimeException(
                "Insufficient funds. Available: ₹{$this->available_balance}, Required: ₹{$amount}"
            );
        }

        // Create lock record
        $lock = FundLock::create([
            'user_id' => $this->user_id,
            'lock_type' => $lockType,
            'lockable_type' => get_class($lockable),
            'lockable_id' => $lockable->id,
            'amount' => $amount,
            'amount_paise' => bcmul($amount, 100),
            'status' => 'active',
            'locked_at' => now(),
            'locked_by' => auth()->id(),
            'metadata' => $metadata,
        ]);

        // Increment locked balance
        $this->incrementLockedBalance($amount);

        \Log::info('Funds locked', [
            'user_id' => $this->user_id,
            'amount' => $amount,
            'lock_type' => $lockType,
            'lock_id' => $lock->id,
        ]);

        return $lock;
    }

    /**
     * Unlock funds (release reservation)
     *
     * @param FundLock $lock
     * @param string|null $reason
     * @return bool
     */
    public function unlockFunds(FundLock $lock, ?string $reason = null): bool
    {
        return $lock->release(auth()->id(), $reason);
    }

    /**
     * Increment locked balance atomically
     *
     * @param float $amount
     * @return bool
     */
    public function incrementLockedBalance(float $amount): bool
    {
        $amountPaise = (int) bcmul($amount, 100);

        return $this->increment('locked_balance_paise', $amountPaise);
    }

    /**
     * Decrement locked balance atomically
     *
     * @param float $amount
     * @return bool
     */
    public function decrementLockedBalance(float $amount): bool
    {
        $amountPaise = (int) bcmul($amount, 100);

        return $this->decrement('locked_balance_paise', $amountPaise);
    }

    /**
     * Check if sufficient funds available (considering locks)
     *
     * @param float $amount
     * @return bool
     */
    public function hasSufficientFunds(float $amount): bool
    {
        return $this->available_balance >= $amount;
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
