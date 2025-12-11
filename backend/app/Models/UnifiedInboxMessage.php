<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UnifiedInboxMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'channel_id',
        'user_id',
        'sender_identifier',
        'message_content',
        'direction',
        'support_ticket_id',
        'status',
        'replied',
        'processed_at',
        'failed_at',
        'error_message',
        'message_metadata',
        'external_message_id',
        'attachments',
    ];

    protected $casts = [
        'channel_id' => 'integer',
        'user_id' => 'integer',
        'support_ticket_id' => 'integer',
        'replied' => 'boolean',
        'processed_at' => 'datetime',
        'failed_at' => 'datetime',
        'message_metadata' => 'array',
        'attachments' => 'array',
    ];

    /**
     * Get the communication channel
     */
    public function channel(): BelongsTo
    {
        return $this->belongsTo(CommunicationChannel::class, 'channel_id');
    }

    /**
     * Get the user (if identified)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the associated support ticket
     */
    public function supportTicket(): BelongsTo
    {
        return $this->belongsTo(SupportTicket::class);
    }

    /**
     * Scope for inbound messages
     */
    public function scopeInbound($query)
    {
        return $query->where('direction', 'inbound');
    }

    /**
     * Scope for outbound messages
     */
    public function scopeOutbound($query)
    {
        return $query->where('direction', 'outbound');
    }

    /**
     * Scope for pending messages
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for processing messages
     */
    public function scopeProcessing($query)
    {
        return $query->where('status', 'processing');
    }

    /**
     * Scope for failed messages
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope for unreplied messages
     */
    public function scopeUnreplied($query)
    {
        return $query->where('replied', false)
            ->where('direction', 'inbound');
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
     * Mark message as processed
     */
    public function markProcessed(): void
    {
        $this->update([
            'status' => 'processed',
            'processed_at' => now(),
        ]);
    }

    /**
     * Mark message as failed
     */
    public function markFailed(string $errorMessage): void
    {
        $this->update([
            'status' => 'failed',
            'failed_at' => now(),
            'error_message' => $errorMessage,
        ]);
    }

    /**
     * Mark message as replied
     */
    public function markReplied(): void
    {
        $this->update(['replied' => true]);
    }

    /**
     * Link message to a support ticket
     */
    public function linkToTicket(int $ticketId): void
    {
        $this->update(['support_ticket_id' => $ticketId]);
    }

    /**
     * Check if message is from an identified user
     */
    public function isFromIdentifiedUser(): bool
    {
        return $this->user_id !== null;
    }

    /**
     * Get sender display name
     */
    public function getSenderDisplayName(): string
    {
        if ($this->user) {
            return $this->user->name ?? $this->user->username;
        }

        return $this->sender_identifier;
    }

    /**
     * Format message for admin inbox display
     */
    public function toInboxFormat(): array
    {
        return [
            'id' => $this->id,
            'channelType' => $this->channel->channel_type,
            'senderName' => $this->getSenderDisplayName(),
            'senderIdentifier' => $this->sender_identifier,
            'message' => $this->message_content,
            'direction' => $this->direction,
            'status' => $this->status,
            'replied' => $this->replied,
            'ticketId' => $this->support_ticket_id,
            'hasAttachments' => !empty($this->attachments),
            'createdAt' => $this->created_at->toIso8601String(),
        ];
    }
}
