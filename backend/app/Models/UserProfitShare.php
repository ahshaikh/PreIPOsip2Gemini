<?php
// V-FINAL-1730-369 (Created)

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserProfitShare extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'profit_share_id',
        'amount',
        'bonus_transaction_id' // Tracks the credit to wallet
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    /**
     * Boot logic to enforce validation.
     */
    protected static function booted()
    {
        static::saving(function ($share) {
            if ($share->amount <= 0) {
                throw new \InvalidArgumentException("Profit share amount must be positive.");
            }
        });
    }

    // --- RELATIONSHIPS ---

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the parent Profit Share period.
     */
    public function profitSharePeriod(): BelongsTo
    {
        return $this->belongsTo(ProfitShare::class, 'profit_share_id');
    }

    /**
     * Get the bonus transaction that credited this to the wallet.
     */
    public function bonusTransaction(): BelongsTo
    {
        return $this->belongsTo(BonusTransaction::class);
    }
}