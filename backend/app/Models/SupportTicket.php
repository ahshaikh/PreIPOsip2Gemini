<?php
// V-REMEDIATE-1730-147 (Created) | V-FINAL-1730-379 (Logic Upgraded)

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;

class SupportTicket extends Model
{
    use HasFactory, SoftDeletes; // Added SoftDeletes for good practice

    // --- Constants for Validation ---
    const STATUS_OPEN = 'open';
    const STATUS_WAITING_USER = 'waiting_for_user';
    const STATUS_RESOLVED = 'resolved';

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
        'resolved_by', // Admin who resolved it
        'resolved_at',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
    ];

    // --- RELATIONSHIPS ---

    /**
     * Get the user who owns the ticket.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all messages for the ticket.
     */
    public function messages(): HasMany
    {
        return $this->hasMany(SupportMessage::class)->orderBy('created_at', 'asc');
    }

    /**
     * Get the admin who resolved the ticket.
     */
    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
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