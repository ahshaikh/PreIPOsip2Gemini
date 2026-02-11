<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin IdeHelperErrorLog
 */
class ErrorLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'level',
        'message',
        'exception',
        'stack_trace',
        'file',
        'line',
        'url',
        'method',
        'user_id',
        'ip_address',
        'user_agent',
        'context',
        'is_resolved',
        'resolution_notes',
        'resolved_at',
        'resolved_by',
    ];

    protected $casts = [
        'context' => 'array',
        'is_resolved' => 'boolean',
        'resolved_at' => 'datetime',
        'line' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }
}
