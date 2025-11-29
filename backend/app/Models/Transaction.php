<?php
// V-PHASE3-1730-077 (Created) | V-PHASE3-1730-355 | V-FINAL-1730-450 (TDS Field Added)

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
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
        'amount',
        'balance_before',
        'balance_after',
        'tds_deducted', // <-- NEW (Tracks tax on deposits)
        'description',
        'reference_type',
        'reference_id',
    ];
    
    protected $casts = [
        'amount' => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'tds_deducted' => 'decimal:2', // <-- NEW
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