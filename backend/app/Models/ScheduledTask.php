<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin IdeHelperScheduledTask
 */
class ScheduledTask extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'command',
        'expression',
        'description',
        'parameters',
        'is_active',
        'last_run_at',
        'last_run_status',
        'last_run_output',
        'last_run_duration',
        'next_run_at',
        'run_count',
        'failure_count',
        'created_by',
    ];

    protected $casts = [
        'parameters' => 'array',
        'is_active' => 'boolean',
        'last_run_at' => 'datetime',
        'next_run_at' => 'datetime',
        'run_count' => 'integer',
        'failure_count' => 'integer',
        'last_run_duration' => 'integer',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
