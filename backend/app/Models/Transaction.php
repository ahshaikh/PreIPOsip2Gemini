<?php
// V-PHASE3-1730-077 (Created) | V-PHASE3-1730-355 | V-FINAL-1730-450 (TDS Field Added) | V-AUDIT-REFACTOR-2025 (Atomic Integers & JSON Fix)

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Str;

class Transaction extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'wallet_id',
        'user_id',
        'transaction_id',
        'type',
        'status',
        // [AUDIT FIX]: Switched to atomic integers
        'amount_paise',
        'balance_before_paise',
        'balance_after_paise',
        'tds_deducted', 
        'description',
        'reference_type',
        'reference_id',
    ];
    
    protected $casts = [
        // [AUDIT FIX]: Cast as integers for atomic math operations
        'amount_paise' => 'integer',
        'balance_before_paise' => 'integer',
        'balance_after_paise' => 'integer',
        'tds_deducted' => 'decimal:2',
    ];

    // [AUDIT FIX]: Automatically append these accessors to array/JSON representation
    // This ensures frontend receives 'amount', 'balance_before', etc.
    protected $appends = [
        'amount',
        'balance_before',
        'balance_after'
    ];

    /**
     * Boot logic to auto-generate UUIDs.
     */
    protected static function booted()
    {
        static::creating(function ($transaction) {
            if (empty($transaction->transaction_id)) {
                $transaction->transaction_id = (string) Str::uuid();
            }
            if (empty($transaction->tds_deducted)) {
                $transaction->tds_deducted = 0;
            }
        });
    }

    // --- ACCESSORS (Backward Compatibility & API Response) ---

    /**
     * Get amount in Rupees (Float).
     * Usage: $transaction->amount
     */
    protected function amount(): Attribute
    {
        return Attribute::make(
            get: fn ($value, $attributes) => ($attributes['amount_paise'] ?? 0) / 100,
        );
    }

    /**
     * Get balance before in Rupees (Float).
     * Usage: $transaction->balance_before
     */
    protected function balanceBefore(): Attribute
    {
        return Attribute::make(
            get: fn ($value, $attributes) => ($attributes['balance_before_paise'] ?? 0) / 100,
        );
    }

    /**
     * Get balance after in Rupees (Float).
     * Usage: $transaction->balance_after
     */
    protected function balanceAfter(): Attribute
    {
        return Attribute::make(
            get: fn ($value, $attributes) => ($attributes['balance_after_paise'] ?? 0) / 100,
        );
    }

    // --- RELATIONSHIPS ---

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

    // --- SCOPES ---

    public function scopeCompleted(Builder $query): void
    {
        $query->where('status', 'completed');
    }

    public function scopePending(Builder $query): void
    {
        $query->where('status', 'pending');
    }
}