<?php
// V-PHASE3-1730-076 (Created) | V-FINAL-1730-356 (Upgraded) | V-SEC-1730-604 (Unsafe Methods Removed) | V-AUDIT-FIX-MODULE7 (Performance Optimization) | V-AUDIT-REFACTOR-2025 (Atomic Integers & Accessors)

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Casts\Attribute;

/**
 * Wallet Model
 * * Represents the user's financial wallet.
 * * ⚠️ SECURITY NOTE:
 * Do not add 'deposit' or 'withdraw' methods here. 
 * All financial operations must go through \App\Services\WalletService
 * to ensure ACID compliance, double-entry ledger logging, and 
 * pessimistic locking (lockForUpdate) to prevent race conditions.
 */
class Wallet extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        // [AUDIT FIX]: Switched to integer-based storage (Paise) to prevent floating point drift.
        'balance_paise', 
        'locked_balance_paise',
    ];

    protected $casts = [
        // [AUDIT FIX]: Cast as integers for atomic math operations
        'balance_paise' => 'integer',
        'locked_balance_paise' => 'integer',
    ];

    // [PROTOCOL 7 FIX]: Automatically append these accessors to array/JSON serialization.
    // This guarantees that whenever $wallet is accessed in a Controller or View,
    // the 'balance' and 'locked_balance' fields (in Rupees) are available.
    protected $appends = [
        'balance',
        'locked_balance',
    ];

    // --- RELATIONSHIPS ---

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    // --- ACCESSORS ---
    // Note: Use Wallet::with('transactions') when loading multiple wallets to avoid N+1

    /**
     * Virtual Accessor: Convert stored Paise to Rupees for display/API.
     * Usage: $wallet->balance
     */
    protected function balance(): Attribute
    {
        return Attribute::make(
            get: fn ($value, $attributes) => ($attributes['balance_paise'] ?? 0) / 100,
        );
    }

    /**
     * Virtual Accessor: Convert stored Locked Paise to Rupees for display/API.
     * Usage: $wallet->locked_balance
     */
    protected function lockedBalance(): Attribute
    {
        return Attribute::make(
            get: fn ($value, $attributes) => ($attributes['locked_balance_paise'] ?? 0) / 100,
        );
    }

    protected function availableBalance(): Attribute
    {
        // [AUDIT FIX]: Updated to use the virtual 'balance' accessor
        return Attribute::make(get: fn () => $this->balance);
    }

    /*
    |--------------------------------------------------------------------------
    | DEPRECATED ACCESSORS - MODULE 7 AUDIT FIX
    |--------------------------------------------------------------------------
    | The following accessors were removed to prevent "Memory Exhaustion" and N+1 issues.
    | Previously, calling $wallet->total_deposited would load ALL transactions into PHP memory
    | to calculate the sum. For a user with 5,000 transactions, this crashes the app.
    |
    | REPLACEMENT: Use withSum() in Controllers.
    | Example: Wallet::withSum('transactions as total_deposited', 'amount')->get();
    */

    // removed totalDeposited(): Attribute
    // removed totalWithdrawn(): Attribute
}