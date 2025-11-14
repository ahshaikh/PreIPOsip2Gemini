<?php
// V-FINAL-1730-362 (Created)

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;

class LuckyDraw extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'draw_date',
        'prize_structure',
        'status', // open, drawn, completed, cancelled
    ];

    protected $casts = [
        'draw_date' => 'date',
        'prize_structure' => 'array',
    ];

    /**
     * Boot logic to enforce validation.
     */
    protected static function booted()
    {
        static::saving(function ($draw) {
            // Calculate total pool from structure
            $pool = 0;
            if (is_array($draw->prize_structure)) {
                foreach ($draw->prize_structure as $tier) {
                    $pool += (float)($tier['count'] ?? 0) * (float)($tier['amount'] ?? 0);
                }
            }
            
            if ($pool <= 0) {
                throw new \InvalidArgumentException("Prize pool must be positive.");
            }
        });
    }

    // --- RELATIONSHIPS ---

    public function entries(): HasMany
    {
        return $this->hasMany(LuckyDrawEntry::class);
    }

    // --- ACCESSORS ---

    /**
     * Calculate total entries from the relationship.
     */
    protected function totalEntries(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->entries()->count()
        );
    }

    // --- LOGIC ---

    /**
     * Mark the draw as executed and paid.
     */
    public function execute(): void
    {
        if ($this->status !== 'open') {
            throw new \DomainException("Only 'open' draws can be executed.");
        }
        $this->update(['status' => 'completed']);
    }
}