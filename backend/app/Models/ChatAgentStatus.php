<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatAgentStatus extends Model
{
    use HasFactory;

    const STATUS_ONLINE = 'online';
    const STATUS_AWAY = 'away';
    const STATUS_BUSY = 'busy';
    const STATUS_OFFLINE = 'offline';

    protected $table = 'chat_agent_status';

    protected $fillable = [
        'agent_id',
        'status',
        'active_chats_count',
        'max_concurrent_chats',
        'is_accepting_chats',
        'last_activity_at',
    ];

    protected $casts = [
        'active_chats_count' => 'integer',
        'max_concurrent_chats' => 'integer',
        'is_accepting_chats' => 'boolean',
        'last_activity_at' => 'datetime',
    ];

    /**
     * Get the agent
     */
    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    /**
     * Scope for online agents
     */
    public function scopeOnline($query)
    {
        return $query->where('status', self::STATUS_ONLINE);
    }

    /**
     * Scope for available agents (online and accepting chats)
     */
    public function scopeAvailable($query)
    {
        return $query->where('status', self::STATUS_ONLINE)
            ->where('is_accepting_chats', true)
            ->whereRaw('active_chats_count < max_concurrent_chats');
    }

    /**
     * Check if agent is available
     */
    public function isAvailable(): bool
    {
        return $this->status === self::STATUS_ONLINE
            && $this->is_accepting_chats
            && $this->active_chats_count < $this->max_concurrent_chats;
    }

    /**
     * Set status to online
     */
    public function goOnline()
    {
        $this->update([
            'status' => self::STATUS_ONLINE,
            'last_activity_at' => now(),
        ]);
    }

    /**
     * Set status to offline
     */
    public function goOffline()
    {
        $this->update([
            'status' => self::STATUS_OFFLINE,
            'is_accepting_chats' => false,
        ]);
    }

    /**
     * Update last activity
     */
    public function updateActivity()
    {
        $this->update(['last_activity_at' => now()]);
    }

    /**
     * Get least busy available agent
     */
    public static function getLeastBusyAgent()
    {
        return static::available()
            ->orderBy('active_chats_count', 'asc')
            ->first();
    }
}
