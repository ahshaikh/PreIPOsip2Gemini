<?php
// V-PHASE3-1730-076 (Created) | V-FINAL-1730-356 (Upgraded) | V-SEC-1730-604 (Unsafe Methods Removed)

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
        'balance',
        'locked_balance',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'locked_balance' => 'decimal:2',
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

    protected function availableBalance(): Attribute
    {
        return Attribute::make(get: fn () => $this->balance);
    }

    /**
     * Get total deposited amount.
     * N+1 SAFE: Uses eager-loaded transactions if available, falls back to query.
     */
    protected function totalDeposited(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->relationLoaded('transactions')) {
                    return $this->transactions
                        ->whereIn('type', ['deposit', 'admin_adjustment', 'bonus_credit', 'refund'])
                        ->where('amount', '>', 0)
                        ->sum('amount');
                }
                return $this->transactions()
                    ->whereIn('type', ['deposit', 'admin_adjustment', 'bonus_credit', 'refund'])
                    ->where('amount', '>', 0)
                    ->sum('amount');
            }
        );
    }

    /**
     * Get total withdrawn amount.
     * N+1 SAFE: Uses eager-loaded transactions if available, falls back to query.
     */
    protected function totalWithdrawn(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->relationLoaded('transactions')) {
                    return abs($this->transactions->where('type', 'withdrawal')->sum('amount'));
                }
                return abs($this->transactions()->where('type', 'withdrawal')->sum('amount'));
            }
        );
    }
}