<?php
// V-PHASE3-1730-342 (Created) | V-FINAL-1730-449 (TDS Field Added) | V-FINAL-1730-566 (TDS Added)

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BonusTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'subscription_id',
        'payment_id',
        'type',
        'amount',
        'tds_deducted', // <-- NEW
        'multiplier_applied',
        'base_amount',
        'description',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'tds_deducted' => 'decimal:2', // <-- NEW
        'multiplier_applied' => 'decimal:2',
        'base_amount' => 'decimal:2',
    ];

    /**
     * Boot logic.
     */
    protected static function booted()
    {
        static::saving(function ($bonus) {
            if (!empty($bonus->base_amount) && !empty($bonus->multiplier_applied) && empty($bonus->amount)) {
                $bonus->amount = $bonus->base_amount * $bonus->multiplier_applied;
            }
            if (empty($bonus->tds_deducted)) {
                $bonus->tds_deducted = 0;
            }
        });
    }

    // --- RELATIONSHIPS ---

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    // --- HELPERS ---

    /**
     * Get the Net Amount (Amount - TDS).
     */
    public function getNetAmountAttribute(): float
    {
        return (float)$this->amount - (float)$this->tds_deducted;
    }

    /**
     * Create a reversal (negative) transaction for this bonus.
     */
    public function reverse(string $reason): self
    {
        return self::create([
            'user_id' => $this->user_id,
            'subscription_id' => $this->subscription_id,
            'payment_id' => $this->payment_id,
            'type' => 'reversal',
            'amount' => -$this->amount, // Negative amount
            'tds_deducted' => -$this->tds_deducted,
            'description' => "Reversal of Bonus #{$this->id}: {$reason}",
        ]);
    }
}