<?php
// V-FINAL-1730-271

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReferralCampaign extends Model
{
    protected $fillable = [
        'name', 'start_date', 'end_date', 'multiplier', 'bonus_amount', 'is_active'
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'is_active' => 'boolean',
        'multiplier' => 'decimal:2',
        'bonus_amount' => 'decimal:2',
    ];

    /**
     * Scope to find the currently running campaign.
     */
    public function scopeRunning($query)
    {
        $now = now();
        return $query->where('is_active', true)
                     ->where('start_date', '<=', $now)
                     ->where('end_date', '>=', $now)
                     ->latest(); // If multiple overlap, take the newest
    }
}