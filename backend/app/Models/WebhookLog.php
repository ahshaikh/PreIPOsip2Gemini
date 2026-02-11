<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Prunable;

/**
 * @mixin IdeHelperWebhookLog
 */
class WebhookLog extends Model
{
    use HasFactory, Prunable;

    protected $fillable = [
        'event_type',
        'webhook_id',
        'payload',
        'headers',
        'status',
        'retry_count',
        'max_retries',
        'response',
        'response_code',
        'error_message',
        'next_retry_at',
        'processed_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'headers' => 'array',
        'response' => 'array',
        'next_retry_at' => 'datetime',
        'processed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Scope to get pending retries.
     */
    public function scopePendingRetries($query)
    {
        return $query->where('status', 'pending')
            ->where('next_retry_at', '<=', now())
            ->where('retry_count', '<', function ($query) {
                $query->selectRaw('max_retries');
            });
    }

    /**
     * Scope to get failed webhooks.
     */
    public function scopeFailed($query)
    {
        return $query->whereIn('status', ['failed', 'max_retries_reached']);
    }

    /**
     * Check if webhook can be retried.
     */
    public function canRetry(): bool
    {
        return $this->retry_count < $this->max_retries
            && !in_array($this->status, ['success', 'max_retries_reached']);
    }

    /**
     * Calculate next retry time using exponential backoff.
     */
    public function calculateNextRetryAt(): \DateTime
    {
        // Exponential backoff: 2^retry_count minutes
        // Retry 0: 1 minute
        // Retry 1: 2 minutes
        // Retry 2: 4 minutes
        // Retry 3: 8 minutes
        // Retry 4: 16 minutes
        $minutes = pow(2, $this->retry_count);

        // Cap at 60 minutes
        $minutes = min($minutes, 60);

        return now()->addMinutes($minutes);
    }

    /**
     * Mark as processing.
     */
    public function markAsProcessing(): void
    {
        $this->update([
            'status' => 'processing',
        ]);
    }

    /**
     * Mark as success.
     */
    public function markAsSuccess(array $response = null, int $responseCode = null): void
    {
        $this->update([
            'status' => 'success',
            'response' => $response,
            'response_code' => $responseCode,
            'processed_at' => now(),
            'error_message' => null,
        ]);
    }

    /**
     * Mark as failed and schedule retry.
     */
    public function markAsFailed(string $errorMessage, int $responseCode = null, array $response = null): void
    {
        $this->increment('retry_count');

        if ($this->retry_count >= $this->max_retries) {
            $this->update([
                'status' => 'max_retries_reached',
                'error_message' => $errorMessage,
                'response' => $response,
                'response_code' => $responseCode,
                'processed_at' => now(),
            ]);
        } else {
            $this->update([
                'status' => 'pending',
                'error_message' => $errorMessage,
                'response' => $response,
                'response_code' => $responseCode,
                'next_retry_at' => $this->calculateNextRetryAt(),
            ]);
        }
    }

    /**
     * Get the prunable model query.
     * V-AUDIT-MODULE4-007 (LOW) - Extended retention from 30 to 90 days
     * Prune webhook logs older than 90 days for audit compliance.
     * This balances forensic needs with database size management.
     *
     * To manually run: php artisan model:prune --model=WebhookLog
     * Scheduled via Console/Kernel.php: $schedule->command('model:prune')->daily();
     */
    public function prunable()
    {
        return static::where('created_at', '<=', now()->subDays(90));
    }
}
