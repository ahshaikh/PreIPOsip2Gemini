<?php
// V-FINAL-1730-347

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @mixin IdeHelperCelebrationEvent
 */
class CelebrationEvent extends Model
{
    protected $fillable = [
        'name',
        'event_date',
        'bonus_amount_by_plan',
        'is_active',
        'is_recurring_annually',
    ];

    protected $casts = [
        'event_date' => 'date',
        'bonus_amount_by_plan' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Scope for active events happening today.
     */
    public function scopeActiveToday($query, $today)
    {
        return $query->where('is_active', true)
                     ->whereMonth('event_date', $today->month)
                     ->whereDay('event_date', $today->day);
    }
}