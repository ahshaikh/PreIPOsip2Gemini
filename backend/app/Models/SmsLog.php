<?php
// V-FINAL-1730-391 (Created)
// Enhanced with tracking features

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin IdeHelperSmsLog
 */
class SmsLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'sms_template_id',
        'recipient_mobile',
        'recipient_name',
        'template_slug',
        'dlt_template_id',
        'message',
        'status',
        'provider',
        'provider_message_id',
        'provider_response',
        'error_message',
        'gateway_message_id', // Keep for backward compatibility
        'sent_at',
        'delivered_at',
        'failed_at',
        'credits_used',
        'metadata',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'failed_at' => 'datetime',
        'provider_response' => 'array',
        'metadata' => 'array',
        'credits_used' => 'decimal:2',
    ];

    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_QUEUED = 'queued';
    const STATUS_SENDING = 'sending';
    const STATUS_SENT = 'sent';
    const STATUS_DELIVERED = 'delivered';
    const STATUS_FAILED = 'failed';
    const STATUS_REJECTED = 'rejected';

    /**
     * Relationships
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function smsTemplate(): BelongsTo
    {
        return $this->belongsTo(SmsTemplate::class);
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

    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    public function scopeByProvider($query, $provider)
    {
        return $query->where('provider', $provider);
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Helper Methods
     */
    public function markAsSent($providerMessageId = null, $providerResponse = null, $creditsUsed = null)
    {
        $this->update([
            'status' => self::STATUS_SENT,
            'sent_at' => now(),
            'provider_message_id' => $providerMessageId,
            'gateway_message_id' => $providerMessageId, // Backward compatibility
            'provider_response' => $providerResponse,
            'credits_used' => $creditsUsed,
        ]);
    }

    public function markAsDelivered()
    {
        $this->update([
            'status' => self::STATUS_DELIVERED,
            'delivered_at' => now(),
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

    public function markAsRejected($errorMessage)
    {
        $this->update([
            'status' => self::STATUS_REJECTED,
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

    public function getIsFailedAttribute()
    {
        return $this->failed_at !== null;
    }

    public function getMessageLengthAttribute()
    {
        return mb_strlen($this->message);
    }
}
