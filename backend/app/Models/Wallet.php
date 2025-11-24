<?php
// V-PHASE3-1730-076 (Created) | V-FINAL-1730-356 (Upgraded for Full Ledger)

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\DB;

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

    // --- CORE LOGIC METHODS (UPGRADED) ---

    /**
     * Deposits money into the wallet and creates a transaction.
     */
    public function deposit(float $amount, string $type, string $description, ?Model $reference = null)
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException("Deposit amount must be positive.");
        }

        return DB::transaction(function () use ($amount, $type, $description, $reference) {
            $balance_before = $this->balance; // Get balance before increment
            
            $this->increment('balance', $amount);
            
            return $this->transactions()->create([
                'user_id' => $this->user_id,
                'type' => $type,
                'status' => 'completed', // Deposits are instant
                'amount' => $amount,
                'balance_before' => $balance_before,
                'balance_after' => $this->balance,
                'description' => $description,
                'reference_type' => $reference ? get_class($reference) : null,
                'reference_id' => $reference ? $reference->id : null,
            ]);
        });
    }

    /**
     * Withdraws money from the wallet and creates a transaction.
     */
    public function withdraw(float $amount, string $type, string $description, ?Model $reference = null)
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException("Withdrawal amount must be positive.");
        }

        if ($this->available_balance < $amount) {
            throw new \Exception("Insufficient funds.");
        }

        return DB::transaction(function () use ($amount, $type, $description, $reference) {
            $balance_before = $this->balance; // Get balance before decrement

            $this->decrement('balance', $amount);
            
            return $this->transactions()->create([
                'user_id' => $this->user_id,
                'type' => $type,
                'status' => 'completed', // Withdrawals are instant
                'amount' => -$amount,
                'balance_before' => $balance_before,
                'balance_after' => $this->balance,
                'description' => $description,
                'reference_type' => $reference ? get_class($reference) : null,
                'reference_id' => $reference ? $reference->id : null,
            ]);
        });
    }
}