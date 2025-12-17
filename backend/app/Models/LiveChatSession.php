<?php
// V-AUDIT-MODULE14-003 (LOW): Replaced insecure uniqid() with secure Str::random()

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class LiveChatSession extends Model
{
    use HasFactory, SoftDeletes;

    const STATUS_WAITING = 'waiting';
    const STATUS_ACTIVE = 'active';
    const STATUS_CLOSED = 'closed';
    const STATUS_ARCHIVED = 'archived';

    protected $fillable = [
        'session_code',
        'user_id',
        'agent_id',
        'status',
        'subject',
        'initial_message',
        'unread_user_count',
        'unread_agent_count',
        'user_rating',
        'user_feedback',
        'started_at',
        'closed_at',
        'closed_by_type',
        'closed_by_id',
    ];

    protected $casts = [
        'unread_user_count' => 'integer',
        'unread_agent_count' => 'integer',
        'started_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    /**
     * Boot the model
     * V-AUDIT-MODULE14-003 (LOW): Use secure random string instead of predictable uniqid()
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($session) {
            if (empty($session->session_code)) {
                // V-AUDIT-MODULE14-003: Replace uniqid() with Str::random()
                // Previous Issue: uniqid() is time-based and predictable. An attacker could
                // enumerate session codes to spy on conversations if authorization is weak.
                // Fix: Use Str::random(16) which generates cryptographically secure random strings.
                // Benefits: Prevents session code enumeration attacks, enhances security.
                $session->session_code = 'CHAT-' . strtoupper(Str::random(16));
            }
        });
    }

    /**
     * Get the user
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the agent
     */
    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    /**
     * Get the user who closed the session
     */
    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by_id');
    }

    /**
     * Get messages
     */
    public function messages(): HasMany
    {
        return $this->hasMany(LiveChatMessage::class, 'session_id')->orderBy('created_at');
    }

    /**
     * Scope for active sessions
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope for waiting sessions
     */
    public function scopeWaiting($query)
    {
        return $query->where('status', self::STATUS_WAITING);
    }

    /**
     * Scope for closed sessions
     */
    public function scopeClosed($query)
    {
        return $query->where('status', self::STATUS_CLOSED);
    }

    /**
     * Scope for agent's sessions
     */
    public function scopeForAgent($query, $agentId)
    {
        return $query->where('agent_id', $agentId);
    }

    /**
     * Check if session is active
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Check if session is waiting
     */
    public function isWaiting(): bool
    {
        return $this->status === self::STATUS_WAITING;
    }

    /**
     * Check if session is closed
     */
    public function isClosed(): bool
    {
        return in_array($this->status, [self::STATUS_CLOSED, self::STATUS_ARCHIVED]);
    }

    /**
     * Assign agent to session
     */
    public function assignAgent($agentId)
    {
        $this->update([
            'agent_id' => $agentId,
            'status' => self::STATUS_ACTIVE,
            'started_at' => now(),
        ]);
    }

    /**
     * Close session
     */
    public function closeSession($closedByType, $closedById)
    {
        $this->update([
            'status' => self::STATUS_CLOSED,
            'closed_at' => now(),
            'closed_by_type' => $closedByType,
            'closed_by_id' => $closedById,
        ]);
    }
}
