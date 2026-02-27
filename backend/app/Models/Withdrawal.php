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
        'amount_paise',
        'fee_paise',
        'tds_deducted_paise',
        'net_amount_paise',
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
        'amount_paise' => 'integer',
        'fee_paise' => 'integer',
        'tds_deducted_paise' => 'integer',
        'net_amount_paise' => 'integer',
        'approved_at' => 'datetime',
        'processed_at' => 'datetime',
        'funds_locked' => 'boolean', // FIX 18
        'funds_locked_at' => 'datetime', // FIX 18
        'funds_unlocked_at' => 'datetime', // FIX 18
    ];

    protected $appends = [
        'amount',
        'fee',
        'tds_deducted',
        'net_amount',
    ];

    /**
     * Boot logic to enforce validation and calculations.
     */
    protected static function booted()
    {
        static::saving(function ($withdrawal) {
            if (($withdrawal->amount_paise ?? 0) <= 0) {
                throw new \InvalidArgumentException("Withdrawal amount must be positive.");
            }
            if (empty($withdrawal->tds_deducted_paise)) {
                $withdrawal->tds_deducted_paise = 0;
            }

            // Auto-calculate Net Amount (paise)
            $withdrawal->net_amount_paise = $withdrawal->amount_paise - ($withdrawal->fee_paise ?? 0) - ($withdrawal->tds_deducted_paise ?? 0);
        });
    }

    // --- VIRTUAL ACCESSORS (PAISE -> RUPEES) ---

    protected function amount(): Attribute
    {
        return Attribute::make(
            get: fn ($value, $attributes) =>
                (float) (($attributes['amount_paise'] ?? 0) / 100)
        );
    }

    protected function fee(): Attribute
    {
        return Attribute::make(
            get: fn ($value, $attributes) =>
                ($attributes['fee_paise'] ?? 0) / 100
        );
    }

    protected function tdsDeducted(): Attribute
    {
        return Attribute::make(
            get: fn ($value, $attributes) =>
                ($attributes['tds_deducted_paise'] ?? 0) / 100
        );
    }

    protected function netAmount(): Attribute
    {
        return Attribute::make(
            get: fn ($value, $attributes) =>
                ($attributes['net_amount_paise'] ?? 0) / 100
        );
    }

    // --- RELATIONSHIPS ---

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function wallet(): BelongsTo { return $this->belongsTo(Wallet::class); }
    public function admin(): BelongsTo { return $this->belongsTo(User::class, 'admin_id'); }
}