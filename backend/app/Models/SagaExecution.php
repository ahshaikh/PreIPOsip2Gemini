<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * SagaExecution Model
 * 
 * Tracks saga lifecycle and enables crash-safe recovery
 *
 * @mixin IdeHelperSagaExecution
 */
class SagaExecution extends Model
{
    protected $fillable = [
        'saga_id',
        'status',
        'metadata',
        'steps_total',
        'steps_completed',
        'failure_step',
        'failure_reason',
        'resolution_data',
        'resolved_by',
        'initiated_at',
        'completed_at',
        'failed_at',
        'compensated_at',
        'resolved_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'resolution_data' => 'array',
        'initiated_at' => 'datetime',
        'completed_at' => 'datetime',
        'failed_at' => 'datetime',
        'compensated_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    /**
     * IMMUTABILITY: Prevent updates to critical fields after completion
     */
    protected static function booted()
    {
        static::updating(function ($saga) {
            if (in_array($saga->getOriginal('status'), ['completed', 'compensated']) &&
                $saga->status !== 'manually_resolved') {
                throw new \RuntimeException(
                    "Cannot modify saga in terminal state: {$saga->getOriginal('status')}"
                );
            }
        });

        static::deleting(function () {
            throw new \RuntimeException('Saga executions cannot be deleted (audit requirement)');
        });
    }

    public function steps(): HasMany
    {
        return $this->hasMany(SagaStep::class);
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }
}
