<?php
// V-PHASE3-1730-342 (Created) | V-FINAL-1730-449 (TDS Field Added)

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate.Database\Eloquent\Model;
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
     * Boot logic to auto-calculate final amount.
     */
    protected static function booted()
    {
        static::saving(function ($bonus) {
            // Auto-calculate final amount if base and multiplier are provided
            if (!empty($bonus->base_amount) && !empty($bonus->multiplier_applied) && empty($bonus->amount)) {
                $bonus->amount = $bonus->base_amount * $bonus->multiplier_applied;
            }
            // Ensure TDS is never null
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

    // --- HELPER METHODS ---

    /**
     * Create a reversal (negative) transaction for this bonus.
     */
    public function reverse(string $reason): self
    {
        // This should also reverse the TDS, which is complex.
        // For V1, we create a simple negative transaction.
        return self::create([
            'user_id' => $this->user_id,
            'subscription_id' => $this->subscription_id,
            'payment_id' => $this->payment_id,
            'type' => 'reversal',
            'amount' => -$this->amount, // Negative amount
            'tds_deducted' => -$this.tds_deducted,
            'description' => "Reversal of Bonus #{$this->id}: {$reason}",
        ]);
    }

    /**
     * Get the Net Amount (Amount - TDS).
     */
    public function getNetAmountAttribute(): float
    {
        return $this->amount - $this.tds_deducted;
    }
}