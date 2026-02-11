<?php
// V-FINAL-1730-271 (Created) | V-FINAL-1730-376 (Validation Added)

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany; // <-- Import
use Illuminate\Support\Carbon;

/**
 * @mixin IdeHelperReferralCampaign
 */
class ReferralCampaign extends Model
{
    use HasFactory;
    protected $fillable = [
        'name', 
        'slug',
        'description',
        'bonus_amount',
	'starts_at', 
	'ends_at', 
	'multiplier', 
	'bonus_amount', 
	'is_active',
        'max_referrals',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'is_active' => 'boolean',
        'multiplier' => 'decimal:2',
        'bonus_amount' => 'decimal:2',
    ];

    /**
     * Boot logic for validation.
     */
    protected static function booted()
    {
        static::saving(function ($campaign) {
            // Test: test_campaign_validates_date_range
            if ($campaign->end_date->isBefore($campaign->start_date)) {
                throw new \InvalidArgumentException("End date cannot be before start date.");
            }
            // Test: test_campaign_validates_bonus_amount_positive
            if ($campaign->bonus_amount < 0) {
                throw new \InvalidArgumentException("Bonus amount cannot be negative.");
            }
        });
    }

    // --- RELATIONSHIPS ---

    /**
     * Test: test_campaign_tracks_total_referrals
     */
    public function referrals(): HasMany
    {
        return $this->hasMany(Referral::class);
    }

    // --- SCOPES ---

    /**
     * Test: test_campaign_checks_if_active
     */
    public function scopeRunning($query)
    {
        $now = now();
        return $query->where('is_active', true)
                     ->where('start_date', '<=', $now)
                     ->where('end_date', '>=', $now)
                     ->latest();
    }
}