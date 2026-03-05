<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class WebhookEventLedger extends Model
{
    use HasFactory;

    protected $table = 'webhook_event_ledger';

    protected $fillable = [
        'provider',
        'event_id',
        'resource_type',
        'resource_id',
        'payload_hash',
        'payload_size',
        'headers_hash',
        'event_timestamp',
        'signature_verified',
        'timestamp_valid',
        'replay_detected',
        'payload_mismatch_detected',
        'processing_status',
        'received_at',
        'processed_at',
    ];

    protected $casts = [
        'signature_verified' => 'boolean',
        'timestamp_valid' => 'boolean',
        'replay_detected' => 'boolean',
        'payload_mismatch_detected' => 'boolean',
        'event_timestamp' => 'integer',
        'received_at' => 'datetime',
        'processed_at' => 'datetime',
    ];

    /**
     * Scope to find processed events.
     */
    public function scopeProcessed($query)
    {
        return $query->where('processing_status', 'success');
    }

    /**
     * Scope to find dead letter events.
     */
    public function scopeDeadLetter($query)
    {
        return $query->where('processing_status', 'dead_letter');
    }
}
