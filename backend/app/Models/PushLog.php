<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @mixin IdeHelperPushLog
 */
class PushLog extends Model
{
    use HasFactory;

    protected $table = 'push_logs';

    protected $fillable = [
        'user_id',
        'device_token',
        'device_type',
        'title',
        'body',
        'data',
        'status',
        'provider',
        'provider_message_id',
        'provider_response',
        'error_message',
        'sent_at',
        'delivered_at',
        'opened_at',
        'failed_at',
        'priority',
        'ttl',
        'image_url',
        'action_url',
        'badge_count',
        'metadata',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'opened_at' => 'datetime',
        'failed_at' => 'datetime',
        'data' => 'array',
        'provider_response' => 'array',
        'metadata' => 'array',
        'badge_count' => 'integer',
        'ttl' => 'integer',
    ];

    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_QUEUED = 'queued';
    const STATUS_SENT = 'sent';
    const STATUS_DELIVERED = 'delivered';
    const STATUS_OPENED = 'opened';
    const STATUS_FAILED = 'failed';

    // Device type constants
    const DEVICE_IOS = 'ios';
    const DEVICE_ANDROID = 'android';
    const DEVICE_WEB = 'web';

    // Priority constants
    const PRIORITY_HIGH = 'high';
    const PRIORITY_NORMAL = 'normal';

    /**
     * Relationships
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scopes
     */
    public function scopeStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeSent($query)
    {
        return $query->whereNotNull('sent_at');
    }

    public function scopeDelivered($query)
    {
        return $query->whereNotNull('delivered_at');
    }

    public function scopeOpened($query)
    {
        return $query->whereNotNull('opened_at');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    public function scopeByProvider($query, $provider)
    {
        return $query->where('provider', $provider);
    }

    public function scopeByDeviceType($query, $deviceType)
    {
        return $query->where('device_type', $deviceType);
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Helper Methods
     */
    public function markAsSent($providerMessageId = null, $providerResponse = null)
    {
        $this->update([
            'status' => self::STATUS_SENT,
            'sent_at' => now(),
            'provider_message_id' => $providerMessageId,
            'provider_response' => $providerResponse,
        ]);
    }

    public function markAsDelivered()
    {
        $this->update([
            'status' => self::STATUS_DELIVERED,
            'delivered_at' => now(),
        ]);
    }

    public function markAsOpened()
    {
        $this->update([
            'status' => self::STATUS_OPENED,
            'opened_at' => now(),
        ]);
    }

    public function markAsFailed($errorMessage)
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'failed_at' => now(),
            'error_message' => $errorMessage,
        ]);
    }

    /**
     * Attributes
     */
    public function getIsDeliveredAttribute()
    {
        return $this->delivered_at !== null;
    }

    public function getIsOpenedAttribute()
    {
        return $this->opened_at !== null;
    }

    public function getIsFailedAttribute()
    {
        return $this->failed_at !== null;
    }
}
