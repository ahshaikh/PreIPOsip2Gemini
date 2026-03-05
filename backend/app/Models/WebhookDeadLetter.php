<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebhookDeadLetter extends Model
{
    protected $fillable = [
        'provider',
        'event_id',
        'resource_type',
        'resource_id',
        'payload',
        'error_message',
        'attempts',
        'failed_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'failed_at' => 'datetime',
        'attempts' => 'integer',
    ];
}
