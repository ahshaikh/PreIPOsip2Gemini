<?php
// V-FINAL-1730-357 (Created) | V-FINAL-1730-497 (TDS Added)

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Withdrawal extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'wallet_id',
        'amount',
        'fee',
        'tds_deducted', // <-- NEW
        'net_amount',
        'status',
        'bank_details',
        'admin_id',
        'utr_number',
        'rejection_reason',
    ];

    protected $casts = [
        'bank_details' => 'json',
        'amount' => 'decimal:2',
        'fee' => 'decimal:2',
        'tds_deducted' => 'decimal:2', // <-- NEW
        'net_amount' => 'decimal:2',
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