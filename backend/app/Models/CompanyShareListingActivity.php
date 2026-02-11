<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Activity log for share listing workflow.
 *
 * @mixin IdeHelperCompanyShareListingActivity
 */
class CompanyShareListingActivity extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'listing_id',
        'actor_id',
        'actor_type',
        'action',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::creating(function ($activity) {
            $activity->created_at = now();
        });
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(CompanyShareListing::class, 'listing_id');
    }

    /**
     * Polymorphic relationship - actor can be admin or company user.
     */
    public function actor(): BelongsTo
    {
        if ($this->actor_type === 'admin') {
            return $this->belongsTo(User::class, 'actor_id');
        } elseif ($this->actor_type === 'company_user') {
            return $this->belongsTo(CompanyUser::class, 'actor_id');
        }

        return $this->belongsTo(User::class, 'actor_id');
    }

    // --- HELPERS ---

    /**
     * Get human-readable action label.
     */
    public function getActionLabelAttribute(): string
    {
        return match($this->action) {
            'submitted' => 'Listing Submitted',
            'viewed' => 'Viewed by Admin',
            'review_started' => 'Review Started',
            'status_changed' => 'Status Changed',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            'deal_created' => 'Deal Created',
            'withdrawn' => 'Withdrawn by Company',
            default => ucwords(str_replace('_', ' ', $this->action)),
        };
    }

    /**
     * Get actor name (works for both admin and company users).
     */
    public function getActorNameAttribute(): string
    {
        if (!$this->actor) {
            return 'Unknown';
        }

        if ($this->actor_type === 'admin') {
            return $this->actor->name ?? 'Admin';
        } elseif ($this->actor_type === 'company_user') {
            return $this->actor->full_name ?? $this->actor->email;
        }

        return 'System';
    }
}
