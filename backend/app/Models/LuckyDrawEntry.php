<?php
// V-FINAL-1730-364 (Created)

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;

class LuckyDrawEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'lucky_draw_id',
        'payment_id',
        'base_entries',
        'bonus_entries',
        'is_winner',
        'prize_rank',
        'prize_amount',
    ];

    protected $casts = [
        'is_winner' => 'boolean',
    ];

    /**
     * Boot logic to enforce validation.
     */
    protected static function booted()
    {
        static::saving(function ($entry) {
            if ($entry->base_entries < 0) {
                throw new \InvalidArgumentException("Base entries cannot be negative.");
            }
            if ($entry->bonus_entries < 0) {
                throw new \InvalidArgumentException("Bonus entries cannot be negative.");
            }
        });
    }

    // --- RELATIONSHIPS ---

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function luckyDraw(): BelongsTo
    {
        return $this->belongsTo(LuckyDraw::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    // --- ACCESSORS ---

    /**
     * Calculates the total entries for this user in this draw.
     */
    protected function totalEntries(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->base_entries + $this->bonus_entries
        );
    }
}