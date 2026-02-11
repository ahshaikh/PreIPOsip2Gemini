<?php
// V-REMEDIATE-1730-147 (Created) | V-FINAL-1730-379 (Logic Upgraded) | V-FINAL-1730-532 (Agent Relation)

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @mixin IdeHelperSupportTicket
 */
class SupportTicket extends Model
{
    use HasFactory, SoftDeletes;

    // --- Constants for Validation ---
    const STATUS_OPEN = 'open';
    const STATUS_WAITING_USER = 'waiting_for_user';
    const STATUS_RESOLVED = 'resolved';
    const STATUS_CLOSED = 'closed';

    const PRIORITY_LOW = 'low';
    const PRIORITY_MEDIUM = 'medium';
    const PRIORITY_HIGH = 'high';

    protected $fillable = [
        'user_id',
        'ticket_code',
        'subject',
        'category',
        'priority',
        'status',
        'assigned_to', // <-- NEW (from previous migration)
        'sla_hours',   // <-- NEW
        'resolved_by',
        'resolved_at',
        'closed_at',   // <-- NEW
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    // --- RELATIONSHIPS ---

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(SupportMessage::class)->orderBy('created_at', 'asc');
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    /**
     * NEW: The admin/agent this ticket is assigned to.
     */
    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    // --- SCOPES ---

    /**
     * Finds tickets that were resolved (but not closed)
     * more than 7 days ago.
     */
    public function scopeAutoClose(Builder $query, $days = 7): void
    {
        $query->where('status', self::STATUS_RESOLVED)
              ->where('resolved_at', '<=', now()->subDays($days));
    }
}