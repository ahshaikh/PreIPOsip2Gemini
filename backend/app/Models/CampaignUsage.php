<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CampaignUsage extends Model
{
    use HasFactory;

    protected $fillable = [
        'campaign_id',
        'user_id',
        'applicable_type',
        'applicable_id',
        'original_amount',
        'discount_applied',
        'final_amount',
        'campaign_code',
        'campaign_snapshot',
        'ip_address',
        'user_agent',
        'used_at',
    ];

    protected $casts = [
        'original_amount' => 'decimal:2',
        'discount_applied' => 'decimal:2',
        'final_amount' => 'decimal:2',
        'campaign_snapshot' => 'array',
        'used_at' => 'datetime',
    ];

    /**
     * Relationships
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Polymorphic relationship to the entity this campaign was applied to
     * Can be: Investment, Subscription, Payment, etc.
     */
    public function applicable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Scopes
     */
    public function scopeForCampaign($query, $campaignId)
    {
        return $query->where('campaign_id', $campaignId);
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForType($query, $type)
    {
        return $query->where('applicable_type', $type);
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('used_at', '>=', now()->subDays($days));
    }

    /**
     * Helper to get discount percentage
     */
    public function getDiscountPercentageAttribute(): float
    {
        if ($this->original_amount > 0) {
            return round(($this->discount_applied / $this->original_amount) * 100, 2);
        }
        return 0.0;
    }
}
