<?php
// V-FINAL-1730-598 (Created)
// Enhanced with tracking features

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin IdeHelperEmailLog
 */
class EmailLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'email_template_id',
        'template_slug',
        'to_email',
        'recipient_name',
        'subject',
        'body',
        'status',
        'provider',
        'provider_message_id',
        'provider_response',
        'error_message',
        'sent_at',
        'delivered_at',
        'opened_at',
        'clicked_at',
        'bounced_at',
        'complained_at',
        'ip_address',
        'user_agent',
        'open_count',
        'click_count',
        'metadata',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'opened_at' => 'datetime',
        'clicked_at' => 'datetime',
        'bounced_at' => 'datetime',
        'complained_at' => 'datetime',
        'provider_response' => 'array',
        'metadata' => 'array',
        'open_count' => 'integer',
        'click_count' => 'integer',
    ];

    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_QUEUED = 'queued';
    const STATUS_SENDING = 'sending';
    const STATUS_SENT = 'sent';
    const STATUS_DELIVERED = 'delivered';
    const STATUS_OPENED = 'opened';
    const STATUS_CLICKED = 'clicked';
    const STATUS_BOUNCED = 'bounced';
    const STATUS_FAILED = 'failed';
    const STATUS_COMPLAINED = 'complained';

    /**
     * Relationships
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function emailTemplate(): BelongsTo
    {
        return $this->belongsTo(EmailTemplate::class);
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

    public function scopeClicked($query)
    {
        return $query->whereNotNull('clicked_at');
    }

    public function scopeBounced($query)
    {
        return $query->whereNotNull('bounced_at');
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

    public function markAsOpened($ipAddress = null, $userAgent = null)
    {
        $this->update([
            'status' => self::STATUS_OPENED,
            'opened_at' => $this->opened_at ?? now(),
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'open_count' => $this->open_count + 1,
        ]);
    }

    public function markAsClicked($ipAddress = null, $userAgent = null)
    {
        $this->update([
            'status' => self::STATUS_CLICKED,
            'clicked_at' => $this->clicked_at ?? now(),
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'click_count' => $this->click_count + 1,
        ]);
    }

    public function markAsBounced($errorMessage = null)
    {
        $this->update([
            'status' => self::STATUS_BOUNCED,
            'bounced_at' => now(),
            'error_message' => $errorMessage,
        ]);
    }

    public function markAsFailed($errorMessage)
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'error_message' => $errorMessage,
        ]);
    }

    public function markAsComplained()
    {
        $this->update([
            'status' => self::STATUS_COMPLAINED,
            'complained_at' => now(),
        ]);
    }

    /**
     * Attributes
     */
    public function getIsOpenedAttribute()
    {
        return $this->opened_at !== null;
    }

    public function getIsClickedAttribute()
    {
        return $this->clicked_at !== null;
    }

    public function getIsBouncedAttribute()
    {
        return $this->bounced_at !== null;
    }

    public function getIsDeliveredAttribute()
    {
        return $this->delivered_at !== null;
    }
}
