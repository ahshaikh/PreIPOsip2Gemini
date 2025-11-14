<?php
// V-PHASE3-1730-080 (Created) | V-FINAL-1730-374 (Logic Upgraded) | V-FINAL-1730-377 (Campaign FK Added)

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class Referral extends Model
{
    use HasFactory;
    
    // ADD 'referral_campaign_id' to fillable
    protected $fillable = [
        'referrer_id', 
        'referred_id', 
        'status', 
        'completed_at',
        'referral_campaign_id'
    ];

    protected $casts = [
        'completed_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::creating(function ($referral) {
            if ($referral->referrer_id === $referral->referred_id) {
                throw new \InvalidArgumentException("A user cannot refer themselves.");
            }
            $exists = self::where('referred_id', $referral->referred_id)->exists();
            if ($exists) {
                throw new \InvalidArgumentException("This user has already been referred.");
            }
        });
    }

    // --- RELATIONSHIPS ---

    public function referrer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referrer_id');
    }

    public function referred(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referred_id');
    }

    /**
     * NEW: The campaign this referral is part of.
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(ReferralCampaign::class, 'referral_campaign_id');
    }

    // --- SCOPES ---

    public function scopeCompleted(Builder $query): void
    {
        $query->where('status', 'completed');
    }

    // --- HELPERS ---

    public function complete(): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }

    public function isBonusEligible(): bool
    {
        return $this->status === 'completed';
    }
}