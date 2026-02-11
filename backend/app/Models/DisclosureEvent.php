<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * DisclosureEvent Model
 * 
 * Represents an immutable timeline event in a disclosure thread.
 * Similar to GitHub PR timeline entries.
 * 
 * IMMUTABILITY:
 * - No updates allowed (enforced by service layer)
 * - No deletes allowed (except via cascade)
 * - All corrections are new events
 * 
 * EVENT TYPES:
 * - submission: Company submits disclosure
 * - clarification: Platform requests info
 * - response: Company responds
 * - approval: Platform approves
 * - status_change: Status transitions
 * - rejection: Platform rejects
 *
 * @property int $id
 * @property int $company_disclosure_id
 * @property string $event_type
 * @property string|null $actor_type
 * @property int|null $actor_id
 * @property string $actor_name
 * @property string|null $message
 * @property array|null $metadata
 * @property int|null $disclosure_clarification_id
 * @property string|null $status_from
 * @property string|null $status_to
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property \Carbon\Carbon $created_at
 * @mixin IdeHelperDisclosureEvent
 */
class DisclosureEvent extends Model
{
    /**
     * Disable updated_at (events are immutable)
     */
    const UPDATED_AT = null;

    /**
     * The table associated with the model.
     */
    protected $table = 'disclosure_events';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'company_disclosure_id',
        'event_type',
        'actor_type',
        'actor_id',
        'actor_name',
        'message',
        'metadata',
        'disclosure_clarification_id',
        'status_from',
        'status_to',
        'ip_address',
        'user_agent',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * Disable model updates and deletes to enforce immutability
     */
    public static function boot()
    {
        parent::boot();

        // Prevent updates
        static::updating(function ($model) {
            throw new \Exception('Disclosure events cannot be updated (immutable audit trail)');
        });

        // Prevent deletes (except via cascade)
        static::deleting(function ($model) {
            if (!$model->isForceDeleting()) {
                throw new \Exception('Disclosure events cannot be deleted (immutable audit trail)');
            }
        });
    }

    /**
     * Get the parent disclosure thread
     */
    public function disclosure(): BelongsTo
    {
        return $this->belongsTo(CompanyDisclosure::class, 'company_disclosure_id');
    }

    /**
     * Get the actor (polymorphic)
     */
    public function actor(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get associated clarification (if event_type = clarification)
     */
    public function clarification(): BelongsTo
    {
        return $this->belongsTo(DisclosureClarification::class, 'disclosure_clarification_id');
    }

    /**
     * Get attached documents
     */
    public function documents(): HasMany
    {
        return $this->hasMany(DisclosureDocument::class);
    }

    /**
     * Scope to get events by type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('event_type', $type);
    }

    /**
     * Scope to get events by actor type
     */
    public function scopeByActorType($query, string $actorType)
    {
        return $query->where('actor_type', 'LIKE', '%' . $actorType . '%');
    }
}
