<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * DisputeTimeline - Append-only event log for dispute history
 *
 * CRITICAL: This model is protected by database triggers that prevent:
 * - UPDATE operations (entries are immutable)
 * - DELETE operations (entries are permanent)
 *
 * Every action on a dispute creates a timeline entry:
 * - Status transitions
 * - Comments from admin/investor
 * - Evidence uploads
 * - Assignment changes
 * - Settlement actions
 *
 * @property int $id
 * @property int $dispute_id
 * @property string $event_type
 * @property int|null $actor_user_id
 * @property string $actor_role
 * @property string $title
 * @property string|null $description
 * @property string|null $old_status
 * @property string|null $new_status
 * @property array|null $attachments
 * @property array|null $metadata
 * @property bool $visible_to_investor
 * @property bool $is_internal_note
 * @property \Carbon\Carbon $created_at
 */
class DisputeTimeline extends Model
{
    use HasFactory;

    /**
     * Disable timestamps - only created_at is used, managed by database.
     */
    public $timestamps = false;

    protected $table = 'dispute_timelines';

    protected $fillable = [
        'dispute_id',
        'event_type',
        'actor_user_id',
        'actor_role',
        'title',
        'description',
        'old_status',
        'new_status',
        'attachments',
        'metadata',
        'visible_to_investor',
        'is_internal_note',
    ];

    protected $casts = [
        'attachments' => 'array',
        'metadata' => 'array',
        'visible_to_investor' => 'boolean',
        'is_internal_note' => 'boolean',
        'created_at' => 'datetime',
    ];

    // Event type constants
    public const EVENT_CREATED = 'created';
    public const EVENT_STATUS_CHANGE = 'status_change';
    public const EVENT_COMMENT = 'comment';
    public const EVENT_EVIDENCE_ADDED = 'evidence_added';
    public const EVENT_ASSIGNED = 'assigned';
    public const EVENT_ESCALATED = 'escalated';
    public const EVENT_SETTLEMENT = 'settlement';
    public const EVENT_SLA_WARNING = 'sla_warning';
    public const EVENT_SLA_BREACH = 'sla_breach';
    public const EVENT_AUTO_ESCALATION = 'auto_escalation';

    // Actor role constants
    public const ROLE_ADMIN = 'admin';
    public const ROLE_INVESTOR = 'investor';
    public const ROLE_SYSTEM = 'system';

    /**
     * Relationship: The dispute this timeline entry belongs to.
     */
    public function dispute(): BelongsTo
    {
        return $this->belongsTo(Dispute::class);
    }

    /**
     * Relationship: The user who performed the action.
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    // Scopes

    /**
     * Scope: Entries visible to investor.
     */
    public function scopeVisibleToInvestor($query)
    {
        return $query->where('visible_to_investor', true)
            ->where('is_internal_note', false);
    }

    /**
     * Scope: Status change events only.
     */
    public function scopeStatusChanges($query)
    {
        return $query->where('event_type', self::EVENT_STATUS_CHANGE);
    }

    /**
     * Scope: Comments only.
     */
    public function scopeComments($query)
    {
        return $query->where('event_type', self::EVENT_COMMENT);
    }

    /**
     * Scope: By event type.
     */
    public function scopeByEventType($query, string $eventType)
    {
        return $query->where('event_type', $eventType);
    }

    /**
     * Scope: By actor role.
     */
    public function scopeByActorRole($query, string $role)
    {
        return $query->where('actor_role', $role);
    }

    /**
     * Scope: Internal notes only (admin-visible only).
     */
    public function scopeInternalNotes($query)
    {
        return $query->where('is_internal_note', true);
    }

    // Helper methods

    /**
     * Check if this is a status change event.
     */
    public function isStatusChange(): bool
    {
        return $this->event_type === self::EVENT_STATUS_CHANGE;
    }

    /**
     * Check if this is a system-generated event.
     */
    public function isSystemGenerated(): bool
    {
        return $this->actor_role === self::ROLE_SYSTEM;
    }

    /**
     * Get all valid event types.
     */
    public static function getEventTypes(): array
    {
        return [
            self::EVENT_CREATED,
            self::EVENT_STATUS_CHANGE,
            self::EVENT_COMMENT,
            self::EVENT_EVIDENCE_ADDED,
            self::EVENT_ASSIGNED,
            self::EVENT_ESCALATED,
            self::EVENT_SETTLEMENT,
            self::EVENT_SLA_WARNING,
            self::EVENT_SLA_BREACH,
            self::EVENT_AUTO_ESCALATION,
        ];
    }

    /**
     * Get all valid actor roles.
     */
    public static function getActorRoles(): array
    {
        return [
            self::ROLE_ADMIN,
            self::ROLE_INVESTOR,
            self::ROLE_SYSTEM,
        ];
    }
}
