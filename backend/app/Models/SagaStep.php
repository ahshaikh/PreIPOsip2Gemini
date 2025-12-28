<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SagaStep extends Model
{
    protected $fillable = [
        'saga_execution_id',
        'step_number',
        'operation_class',
        'status',
        'compensation_status',
        'result_data',
        'compensation_error',
        'executed_at',
        'compensated_at',
    ];

    protected $casts = [
        'result_data' => 'array',
        'executed_at' => 'datetime',
        'compensated_at' => 'datetime',
    ];

    /**
     * IMMUTABILITY: Steps cannot be modified after creation (audit trail)
     */
    protected static function booted()
    {
        static::updating(function ($step) {
            // Only allow compensation status updates
            $allowed = ['compensation_status', 'compensation_error', 'compensated_at'];
            $changed = array_keys($step->getDirty());
            $unauthorized = array_diff($changed, $allowed);

            if (!empty($unauthorized)) {
                throw new \RuntimeException(
                    'Saga steps are immutable. Only compensation fields can be updated.'
                );
            }
        });

        static::deleting(function () {
            throw new \RuntimeException('Saga steps cannot be deleted (audit requirement)');
        });
    }

    public function sagaExecution(): BelongsTo
    {
        return $this->belongsTo(SagaExecution::class);
    }
}
