<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin IdeHelperOutboundMessageQueue
 */
class OutboundMessageQueue extends Model
{
    use HasFactory;

    protected $table = 'outbound_message_queue';

    protected $fillable = [
        'channel_id',
        'recipient_identifier',
        'message_content',
        'subject',
        'scheduled_at',
        'sent_at',
        'status',
        'priority',
        'retry_count',
        'max_retries',
        'delivered',
        'delivered_at',
        'read',
        'read_at',
        'error_message',
        'external_message_id',
        'message_metadata',
    ];

    protected $casts = [
        'channel_id' => 'integer',
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
        'priority' => 'integer',
        'retry_count' => 'integer',
        'max_retries' => 'integer',
        'delivered' => 'boolean',
        'delivered_at' => 'datetime',
        'read' => 'boolean',
        'read_at' => 'datetime',
        'message_metadata' => 'array',
    ];

    /**
     * Get the communication channel
     */
    public function channel(): BelongsTo
    {
        return $this->belongsTo(CommunicationChannel::class);
    }

    /**
     * Scope for pending messages
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for sending messages
     */
    public function scopeSending($query)
    {
        return $query->where('status', 'sending');
    }

    /**
     * Scope for sent messages
     */
    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    /**
     * Scope for failed messages
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope for messages due to be sent
     */
    public function scopeDueForSending($query)
    {
        return $query->where('status', 'pending')
            ->where(function ($q) {
                $q->whereNull('scheduled_at')
                  ->orWhere('scheduled_at', '<=', now());
            });
    }

    /**
     * Scope for high priority messages
     */
    public function scopeHighPriority($query)
    {
        return $query->where('priority', '>=', 5)
            ->orderBy('priority', 'desc');
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
     * Mark message as sending
     */
    public function markSending(): void
    {
        $this->update(['status' => 'sending']);
    }

    /**
     * Mark message as sent
     */
    public function markSent(?string $externalMessageId = null): void
    {
        $this->update([
            'status' => 'sent',
            'sent_at' => now(),
            'external_message_id' => $externalMessageId,
        ]);
    }

    /**
     * Mark message as failed
     */
    public function markFailed(string $errorMessage): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
        ]);
    }

    /**
     * Mark message as delivered
     */
    public function markDelivered(): void
    {
        $this->update([
            'delivered' => true,
            'delivered_at' => now(),
        ]);
    }

    /**
     * Mark message as read
     */
    public function markRead(): void
    {
        $this->update([
            'read' => true,
            'read_at' => now(),
        ]);
    }

    /**
     * Increment retry count
     */
    public function incrementRetry(): void
    {
        $this->increment('retry_count');
    }

    /**
     * Check if can retry
     */
    public function canRetry(): bool
    {
        return $this->retry_count < ($this->max_retries ?? 3);
    }

    /**
     * Retry sending the message
     */
    public function retry(): void
    {
        if ($this->canRetry()) {
            $this->incrementRetry();
            $this->update([
                'status' => 'pending',
                'error_message' => null,
            ]);
        }
    }

    /**
     * Cancel scheduled message
     */
    public function cancel(): void
    {
        if ($this->status === 'pending') {
            $this->update(['status' => 'cancelled']);
        }
    }

    /**
     * Schedule message for later sending
     */
    public function schedule(\DateTimeInterface $scheduledAt): void
    {
        $this->update([
            'scheduled_at' => $scheduledAt,
            'status' => 'pending',
        ]);
    }

    /**
     * Queue a new outbound message
     */
    public static function queueMessage(
        int $channelId,
        string $recipientIdentifier,
        string $messageContent,
        ?string $subject = null,
        int $priority = 0,
        ?\DateTimeInterface $scheduledAt = null
    ): self {
        return self::create([
            'channel_id' => $channelId,
            'recipient_identifier' => $recipientIdentifier,
            'message_content' => $messageContent,
            'subject' => $subject,
            'priority' => $priority,
            'scheduled_at' => $scheduledAt,
            'status' => 'pending',
            'retry_count' => 0,
            'max_retries' => 3,
        ]);
    }

    /**
     * Get messages that need to be sent now
     */
    public static function getMessagesToSend(int $limit = 100)
    {
        return self::dueForSending()
            ->orderBy('priority', 'desc')
            ->orderBy('scheduled_at', 'asc')
            ->orderBy('created_at', 'asc')
            ->limit($limit)
            ->get();
    }

    /**
     * Format message data for admin queue display
     */
    public function toQueueFormat(): array
    {
        return [
            'id' => $this->id,
            'channelType' => $this->channel->channel_type,
            'recipient' => $this->recipient_identifier,
            'message' => $this->message_content,
            'subject' => $this->subject,
            'status' => $this->status,
            'priority' => $this->priority,
            'retryCount' => $this->retry_count,
            'scheduledAt' => $this->scheduled_at?->toIso8601String(),
            'sentAt' => $this->sent_at?->toIso8601String(),
            'delivered' => $this->delivered,
            'read' => $this->read,
            'error' => $this->error_message,
        ];
    }
}
