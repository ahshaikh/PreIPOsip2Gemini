<?php
// V-PHASE3-1730-078 (Created) | V-FINAL-1730-357 | V-FINAL-1730-497 (TDS Added)
// V-AUDIT-MODULE3-006 (Updated) - Added idempotency_key for duplicate prevention

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;

/**
 * @mixin IdeHelperWithdrawal
 */
class Withdrawal extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'wallet_id',
        'amount',
        'fee',
        'tds_deducted',
        'net_amount',
        'status',
        'priority',
        'fee_breakdown',
        'bank_details',
        'admin_id',
        'approved_at',
        'processed_at',
        'utr_number',
        'rejection_reason',
        'admin_notes',
        'idempotency_key', // AUDIT FIX: For duplicate prevention
        'funds_locked', // FIX 18: Fund locking status
        'funds_locked_at', // FIX 18: When funds were locked
        'funds_unlocked_at', // FIX 18: When funds were unlocked
    ];

    protected $casts = [
        'bank_details' => 'json',
        'fee_breakdown' => 'json',
        'amount' => 'decimal:2',
        'fee' => 'decimal:2',
        'tds_deducted' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'approved_at' => 'datetime',
        'processed_at' => 'datetime',
        'funds_locked' => 'boolean', // FIX 18
        'funds_locked_at' => 'datetime', // FIX 18
        'funds_unlocked_at' => 'datetime', // FIX 18
    ];

    /**
     * Boot logic to enforce validation and calculations.
     */
    protected static function booted()
    {
        static::saving(function ($withdrawal) {
            if ($withdrawal->amount <= 0) {
                throw new \InvalidArgumentException("Withdrawal amount must be positive.");
            }
            if (empty($withdrawal->tds_deducted)) {
                $withdrawal->tds_deducted = 0;
            }
            
            // Auto-calculate Net Amount
            $withdrawal->net_amount = $withdrawal->amount - $withdrawal->fee - $withdrawal->tds_deducted;
        });
    }

    // --- RELATIONSHIPS ---

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function wallet(): BelongsTo { return $this->belongsTo(Wallet::class); }
    public function admin(): BelongsTo { return $this->belongsTo(User::class, 'admin_id'); }
}