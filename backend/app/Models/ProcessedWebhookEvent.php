<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProcessedWebhookEvent extends Model
{
    protected $fillable = [
        'provider',
        'event_id',
        'resource_type',
        'resource_id',
        'event_type',
        'event_timestamp',
        'payload_hash',
        'processed_at',
    ];

    protected $casts = [
        'event_timestamp' => 'integer',
        'processed_at' => 'datetime',
    ];
}
