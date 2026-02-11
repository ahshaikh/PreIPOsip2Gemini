<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin IdeHelperUserChannelPreference
 */
class UserChannelPreference extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'channel_id',
        'channel_identifier',
        'verified',
        'verified_at',
        'notifications_enabled',
        'is_primary',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'channel_id' => 'integer',
        'verified' => 'boolean',
        'verified_at' => 'datetime',
        'notifications_enabled' => 'boolean',
        'is_primary' => 'boolean',
    ];

    /**
     * Get the user
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the communication channel
     */
    public function channel(): BelongsTo
    {
        return $this->belongsTo(CommunicationChannel::class);
    }

    /**
     * Scope for verified preferences
     */
    public function scopeVerified($query)
    {
        return $query->where('verified', true);
    }

    /**
     * Scope for preferences with notifications enabled
     */
    public function scopeNotificationsEnabled($query)
    {
        return $query->where('notifications_enabled', true);
    }

    /**
     * Scope for primary contact methods
     */
    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    /**
     * Scope for filtering by channel type
     */
    public function scopeByChannelType($query, string $channelType)
    {
        return $query->whereHas('channel', function ($q) use ($channelType) {
            $q->where('channel_type', $channelType);
        });
    }

    /**
     * Mark preference as verified
     */
    public function markVerified(): void
    {
        $this->update([
            'verified' => true,
            'verified_at' => now(),
        ]);
    }

    /**
     * Set as primary contact method for this channel type
     */
    public function setAsPrimary(): void
    {
        // Remove primary flag from other preferences of same channel type
        self::where('user_id', $this->user_id)
            ->where('channel_id', $this->channel_id)
            ->where('id', '!=', $this->id)
            ->update(['is_primary' => false]);

        $this->update(['is_primary' => true]);
    }

    /**
     * Enable notifications
     */
    public function enableNotifications(): void
    {
        $this->update(['notifications_enabled' => true]);
    }

    /**
     * Disable notifications
     */
    public function disableNotifications(): void
    {
        $this->update(['notifications_enabled' => true]);
    }

    /**
     * Get user's preferences for a specific channel type
     */
    public static function getUserChannelIdentifier(int $userId, string $channelType): ?string
    {
        $preference = self::where('user_id', $userId)
            ->byChannelType($channelType)
            ->verified()
            ->primary()
            ->first();

        return $preference?->channel_identifier;
    }

    /**
     * Get all verified contact methods for user
     */
    public static function getUserVerifiedChannels(int $userId)
    {
        return self::where('user_id', $userId)
            ->verified()
            ->with('channel')
            ->get();
    }

    /**
     * Format preference data for frontend
     */
    public function toFrontendFormat(): array
    {
        return [
            'id' => $this->id,
            'channelType' => $this->channel->channel_type,
            'channelName' => $this->channel->name,
            'identifier' => $this->channel_identifier,
            'verified' => $this->verified,
            'verifiedAt' => $this->verified_at?->toIso8601String(),
            'notificationsEnabled' => $this->notifications_enabled,
            'isPrimary' => $this->is_primary,
        ];
    }
}
