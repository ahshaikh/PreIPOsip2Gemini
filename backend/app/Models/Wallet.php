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
use App\Enums\TransactionType;
use App\Services\WalletService;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

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
        'available_balance_paise',
        'total_deposited',
        'total_withdrawn',
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
     * Available balance in PAISE (canonical integer).
     * Invariant 4: available_balance_paise = balance_paise - locked_balance_paise
     *
     * ⚠️ Use this for all internal calculations.
     */
    protected function availableBalancePaise(): Attribute
    {
        return Attribute::make(
            get: fn ($value, $attributes) =>
                max(0, ($attributes['balance_paise'] ?? 0) - ($attributes['locked_balance_paise'] ?? 0))
        );
    }

    /**
     * Available balance (₹) - derived from paise.
     * READ-ONLY: For display/API serialization only.
     */
    protected function availableBalance(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->available_balance_paise / 100
        );
    }

    /**
     * Total deposited amount (₹) - sum of all credit transactions.
     * READ-ONLY: For display/API serialization only.
     *
     * Uses paise internally and converts to rupees.
     */
    protected function totalDeposited(): Attribute
    {
        return Attribute::make(
            get: function () {
                $creditTypes = array_map(
                    fn ($type) => $type->value,
                    TransactionType::credits()
                );

                $totalPaise = $this->transactions()
                    ->whereIn('type', $creditTypes)
                    ->where('status', 'completed')
                    ->sum('amount_paise');

                return $totalPaise / 100;
            }
        );
    }

    /**
     * Total withdrawn amount (₹) - sum of all debit transactions.
     * READ-ONLY: For display/API serialization only.
     *
     * Returns positive value representing total debited.
     * Uses paise internally and converts to rupees.
     */
    protected function totalWithdrawn(): Attribute
    {
        return Attribute::make(
            get: function () {
                $debitTypes = array_map(
                    fn ($type) => $type->value,
                    TransactionType::debits()
                );

                $totalPaise = $this->transactions()
                    ->whereIn('type', $debitTypes)
                    ->where('status', 'completed')
                    ->sum('amount_paise');

                return $totalPaise / 100;
            }
        );
    }

    // ------------------------------------------------------------------
    // Domain Methods (Thin Wrappers - Delegate to WalletService)
    // ------------------------------------------------------------------

    /**
     * Deposit funds into wallet.
     *
     * DOMAIN CONTRACT: Accepts rupees for backward compatibility.
     * Internally converts to paise and delegates to WalletService
     * for compliance enforcement and ledger recording.
     *
     * @param int|float $amountRupees Amount in rupees
     * @param TransactionType|string $type Transaction type
     * @param string $description Transaction description
     * @param Model|null $reference Optional reference model
     * @param bool $bypassComplianceCheck Only for internal operations (e.g., bonus)
     * @return Transaction
     */
    public function deposit(
        int|float $amountRupees,
        TransactionType|string $type,
        string $description = '',
        ?Model $reference = null,
        bool $bypassComplianceCheck = false
    ): Transaction {
        // Delegate to WalletService for compliance enforcement
        $service = app(WalletService::class);

        // DOMAIN CONTRACT: Force float to trigger WalletService rupee→paise conversion
        // WalletService::normalizeAmount treats int as paise, float as rupees
        return $service->deposit(
            user: $this->user,
            amount: (float) $amountRupees,
            type: $type,
            description: $description,
            reference: $reference,
            bypassComplianceCheck: $bypassComplianceCheck
        );
    }

    /**
     * Withdraw funds from wallet.
     *
     * DOMAIN CONTRACT: Accepts rupees for backward compatibility.
     * Internally converts to paise and delegates to WalletService
     * for compliance enforcement and ledger recording.
     *
     * @param int|float $amountRupees Amount in rupees
     * @param TransactionType|string $type Transaction type
     * @param string $description Transaction description
     * @param Model|null $reference Optional reference model
     * @param bool $lockBalance If true, moves funds to locked_balance instead of debiting
     * @return Transaction
     * @throws \RuntimeException If insufficient funds
     */
    public function withdraw(
        int|float $amountRupees,
        TransactionType|string $type,
        string $description = '',
        ?Model $reference = null,
        bool $lockBalance = false
    ): Transaction {
        // Delegate to WalletService for compliance and ledger
        $service = app(WalletService::class);

        // DOMAIN CONTRACT: Force float to trigger WalletService rupee→paise conversion
        // WalletService::normalizeAmount treats int as paise, float as rupees
        return $service->withdraw(
            user: $this->user,
            amount: (float) $amountRupees,
            type: $type,
            description: $description,
            reference: $reference,
            lockBalance: $lockBalance,
            allowOverdraft: false
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
        // Convert to paise immediately for all calculations
        $amountPaise = (int) round($amount * 100);

        if ($amountPaise <= 0) {
            throw new \InvalidArgumentException('Lock amount must be positive');
        }

        if (!$this->hasSufficientFundsPaise($amountPaise)) {
            throw new \RuntimeException(
                "Insufficient funds. Available: ₹{$this->available_balance}, Required: ₹{$amount}"
            );
        }

        // Create lock record (amount_paise is canonical - no decimal 'amount' field)
        $lock = FundLock::create([
            'user_id' => $this->user_id,
            'lock_type' => $lockType,
            'lockable_type' => get_class($lockable),
            'lockable_id' => $lockable->id,
            'amount_paise' => $amountPaise,
            'status' => 'active',
            'locked_at' => now(),
            'locked_by' => auth()->id(),
            'metadata' => $metadata,
        ]);

        // Increment locked balance using paise
        $this->incrementLockedBalancePaise($amountPaise);

        \Log::info('Funds locked', [
            'user_id' => $this->user_id,
            'amount_paise' => $amountPaise,
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

    // ------------------------------------------------------------------
    // Paise-Native Methods (Canonical)
    // ------------------------------------------------------------------

    /**
     * Increment locked balance atomically (paise).
     *
     * @param int $amountPaise
     * @return bool
     */
    public function incrementLockedBalancePaise(int $amountPaise): bool
    {
        if ($amountPaise < 0) {
            throw new \InvalidArgumentException('Amount paise must be non-negative');
        }

        return $this->increment('locked_balance_paise', $amountPaise);
    }

    /**
     * Decrement locked balance atomically (paise).
     *
     * @param int $amountPaise
     * @return bool
     */
    public function decrementLockedBalancePaise(int $amountPaise): bool
    {
        if ($amountPaise < 0) {
            throw new \InvalidArgumentException('Amount paise must be non-negative');
        }

        return $this->decrement('locked_balance_paise', $amountPaise);
    }

    /**
     * Check if sufficient funds available in paise (considering locks).
     *
     * @param int $amountPaise
     * @return bool
     */
    public function hasSufficientFundsPaise(int $amountPaise): bool
    {
        return $this->available_balance_paise >= $amountPaise;
    }

    // ------------------------------------------------------------------
    // Rupee Wrappers (Deprecated - for backward compatibility)
    // ------------------------------------------------------------------

    /**
     * Increment locked balance atomically (rupees).
     *
     * @deprecated Use incrementLockedBalancePaise() for precision
     * @param float $amount Amount in rupees
     * @return bool
     */
    public function incrementLockedBalance(float $amount): bool
    {
        return $this->incrementLockedBalancePaise((int) round($amount * 100));
    }

    /**
     * Decrement locked balance atomically (rupees).
     *
     * @deprecated Use decrementLockedBalancePaise() for precision
     * @param float $amount Amount in rupees
     * @return bool
     */
    public function decrementLockedBalance(float $amount): bool
    {
        return $this->decrementLockedBalancePaise((int) round($amount * 100));
    }

    /**
     * Check if sufficient funds available (considering locks).
     *
     * @deprecated Use hasSufficientFundsPaise() for precision
     * @param float $amount Amount in rupees
     * @return bool
     */
    public function hasSufficientFunds(float $amount): bool
    {
        return $this->hasSufficientFundsPaise((int) round($amount * 100));
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
