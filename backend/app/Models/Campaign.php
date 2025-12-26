<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\SoftDeletes;

class Campaign extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'subtitle',
        'code',
        'description',
        'long_description',
        'discount_type', // percentage, fixed_amount
        'discount_percent',
        'discount_amount',
        'min_investment',
        'max_discount',
        'usage_limit',
        'usage_count',
        'user_usage_limit',
        'start_at',
        'end_at',
        'image_url',
        'hero_image',
        'video_url',
        'features',
        'terms',
        'is_featured',
        'is_active',
        'is_archived',
        'created_by',
        'approved_by',
        'approved_at',
        'archived_by',
        'archived_at',
        'archive_reason',
    ];

    protected $casts = [
        'discount_percent' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'min_investment' => 'decimal:2',
        'max_discount' => 'decimal:2',
        'usage_limit' => 'integer',
        'usage_count' => 'integer',
        'user_usage_limit' => 'integer',
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'approved_at' => 'datetime',
        'archived_at' => 'datetime',
        'features' => 'array',
        'terms' => 'array',
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
        'is_archived' => 'boolean',
    ];

    /**
     * Relationships
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function archiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'archived_by');
    }

    public function usages(): HasMany
    {
        return $this->hasMany(CampaignUsage::class);
    }

    /**
     * Scopes - Query Filters
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('start_at')
                  ->orWhere('start_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('end_at')
                  ->orWhere('end_at', '>=', now());
            })
            ->whereNotNull('approved_at');
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeApproved($query)
    {
        return $query->whereNotNull('approved_at');
    }

    public function scopePending($query)
    {
        return $query->whereNull('approved_at');
    }

    public function scopeScheduled($query)
    {
        return $query->whereNotNull('start_at')
            ->where('start_at', '>', now());
    }

    public function scopeExpired($query)
    {
        return $query->whereNotNull('end_at')
            ->where('end_at', '<', now());
    }

    /**
     * Accessors - Derived State
     * These compute the campaign's state from timestamps and flags
     */
    protected function isDraft(): Attribute
    {
        return Attribute::make(
            get: fn () => is_null($this->approved_at),
        );
    }

    protected function isApproved(): Attribute
    {
        return Attribute::make(
            get: fn () => !is_null($this->approved_at),
        );
    }

    protected function isScheduled(): Attribute
    {
        return Attribute::make(
            get: fn () => !is_null($this->start_at) && $this->start_at->isFuture(),
        );
    }

    protected function isLive(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->is_approved
                && $this->is_active
                && (!$this->start_at || $this->start_at->isPast())
                && (!$this->end_at || $this->end_at->isFuture()),
        );
    }

    protected function isExpired(): Attribute
    {
        return Attribute::make(
            get: fn () => !is_null($this->end_at) && $this->end_at->isPast(),
        );
    }

    protected function isPaused(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->is_approved && !$this->is_active,
        );
    }

    protected function state(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->is_draft) return 'draft';
                if ($this->is_expired) return 'expired';
                if ($this->is_paused) return 'paused';
                if ($this->is_scheduled) return 'scheduled';
                if ($this->is_live) return 'live';
                return 'inactive';
            },
        );
    }

    protected function remainingUsage(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->usage_limit
                ? max(0, $this->usage_limit - $this->usage_count)
                : null,
        );
    }

    protected function usagePercentage(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->usage_limit && $this->usage_limit > 0
                ? round(($this->usage_count / $this->usage_limit) * 100, 2)
                : 0,
        );
    }

    /**
     * Helper Methods
     */
    public function canBeEdited(): bool
    {
        // Can only edit drafts or campaigns that haven't been used yet
        return $this->is_draft || $this->usage_count === 0;
    }

    public function canBeApproved(): bool
    {
        return $this->is_draft;
    }

    public function canBeActivated(): bool
    {
        return $this->is_approved && !$this->is_active && !$this->is_expired;
    }

    public function canBePaused(): bool
    {
        return $this->is_approved && $this->is_active;
    }

    public function hasReachedLimit(): bool
    {
        return $this->usage_limit && $this->usage_count >= $this->usage_limit;
    }
}
