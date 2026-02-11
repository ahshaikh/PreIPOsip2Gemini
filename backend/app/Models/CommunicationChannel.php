<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @mixin IdeHelperCommunicationChannel
 */
class CommunicationChannel extends Model
{
    use HasFactory;

    protected $fillable = [
        'channel_type',
        'channel_name',
        'is_enabled',
        'configuration',
        'auto_reply_enabled',
        'auto_reply_message',
        'available_from',
        'available_to',
        'available_days',
    ];

    protected $casts = [
        'configuration' => 'array',
        'available_days' => 'array',
        'is_enabled' => 'boolean',
        'auto_reply_enabled' => 'boolean',
    ];

    public function messages(): HasMany
    {
        return $this->hasMany(UnifiedInboxMessage::class, 'channel_id');
    }

    public function templates(): HasMany
    {
        return $this->hasMany(ChannelMessageTemplate::class, 'channel_id');
    }

    public function outboundQueue(): HasMany
    {
        return $this->hasMany(OutboundMessageQueue::class, 'channel_id');
    }

    public function userPreferences(): HasMany
    {
        return $this->hasMany(UserChannelPreference::class, 'channel_id');
    }

    /**
     * Check if channel is available now
     */
    public function isAvailableNow(): bool
    {
        if (!$this->is_enabled) {
            return false;
        }

        $now = now();
        $currentDay = $now->dayOfWeek; // 0 = Sunday, 1 = Monday, etc.
        $currentTime = $now->format('H:i:s');

        // Check if current day is in available days
        if (!in_array($currentDay, $this->available_days)) {
            return false;
        }

        // Check if current time is within available hours
        return $currentTime >= $this->available_from && $currentTime <= $this->available_to;
    }

    /**
     * Get enabled channels only
     */
    public function scopeEnabled($query)
    {
        return $query->where('is_enabled', true);
    }

    /**
     * Get channel by type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('channel_type', $type);
    }
}
