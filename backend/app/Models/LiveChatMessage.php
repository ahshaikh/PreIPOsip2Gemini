<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LiveChatMessage extends Model
{
    use HasFactory;

    const TYPE_TEXT = 'text';
    const TYPE_FILE = 'file';
    const TYPE_IMAGE = 'image';
    const TYPE_SYSTEM = 'system';

    const SENDER_TYPE_USER = 'user';
    const SENDER_TYPE_AGENT = 'agent';
    const SENDER_TYPE_SYSTEM = 'system';

    protected $fillable = [
        'session_id',
        'sender_id',
        'sender_type',
        'message',
        'type',
        'attachments',
        'is_read',
        'read_at',
    ];

    protected $casts = [
        'attachments' => 'array',
        'is_read' => 'boolean',
        'read_at' => 'datetime',
    ];

    /**
     * Get the session
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(LiveChatSession::class, 'session_id');
    }

    /**
     * Get the sender
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::created(function ($message) {
            // Update unread count
            if ($message->sender_type === self::SENDER_TYPE_USER) {
                $message->session->increment('unread_agent_count');
            } else if ($message->sender_type === self::SENDER_TYPE_AGENT) {
                $message->session->increment('unread_user_count');
            }
        });
    }

    /**
     * Mark as read
     */
    public function markAsRead()
    {
        if (!$this->is_read) {
            $this->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

            // Decrement unread count
            if ($this->sender_type === self::SENDER_TYPE_USER) {
                $this->session->decrement('unread_agent_count');
            } else if ($this->sender_type === self::SENDER_TYPE_AGENT) {
                $this->session->decrement('unread_user_count');
            }
        }
    }

    /**
     * Scope for unread messages
     */
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }
}
