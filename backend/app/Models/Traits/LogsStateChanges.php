<?php

namespace App\Models\Traits;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Log;

/**
 * FIX 11 (P2): Logs State Changes Trait
 *
 * Automatically logs state transitions to audit_logs table
 * Provides regulatory compliance for state change tracking
 *
 * Usage in Model:
 * use LogsStateChanges;
 *
 * protected static $stateFields = ['status', 'is_verified', 'is_active'];
 * protected static $includeInStateLog = ['user_id', 'company_id']; // Additional context fields
 */
trait LogsStateChanges
{
    /**
     * Default state fields to track if not specified in model
     */
    protected static $defaultStateFields = ['status'];

    /**
     * Boot the trait
     */
    public static function bootLogsStateChanges(): void
    {
        // Log state changes on update
        static::updated(function ($model) {
            $stateFields = static::$stateFields ?? static::$defaultStateFields;

            foreach ($stateFields as $field) {
                if ($model->wasChanged($field)) {
                    $oldValue = $model->getOriginal($field);
                    $newValue = $model->$field;

                    try {
                        // Build context metadata
                        $metadata = [
                            'model_type' => get_class($model),
                            'model_id' => $model->id,
                            'field' => $field,
                            'transition' => "{$oldValue} → {$newValue}",
                        ];

                        // Include additional context fields if specified
                        if (isset(static::$includeInStateLog)) {
                            foreach (static::$includeInStateLog as $contextField) {
                                if (isset($model->$contextField)) {
                                    $metadata[$contextField] = $model->$contextField;
                                }
                            }
                        }

                        // Determine actor
                        $actorId = auth()->id();
                        $actorType = auth()->check() ? get_class(auth()->user()) : 'System';

                        // Create audit log
                        // FIX: Added module field to prevent SQL error
                        AuditLog::create([
                            'action' => class_basename($model) . '.state_change',
                            'module' => strtolower(class_basename($model)), // e.g., 'companyuser', 'user', 'subscription'
                            'actor_id' => $actorId,
                            'actor_type' => $actorType,
                            'description' => static::getStateChangeDescription($model, $field, $oldValue, $newValue),
                            'old_values' => [$field => $oldValue],
                            'new_values' => [$field => $newValue],
                            'metadata' => $metadata,
                        ]);

                        // Also log to Laravel logs for immediate visibility
                        Log::info('State change logged', [
                            'model' => class_basename($model),
                            'id' => $model->id,
                            'field' => $field,
                            'from' => $oldValue,
                            'to' => $newValue,
                            'actor_id' => $actorId,
                        ]);

                    } catch (\Exception $e) {
                        // Don't fail the model save if audit log fails
                        Log::error('Failed to log state change', [
                            'model' => get_class($model),
                            'id' => $model->id,
                            'field' => $field,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            }
        });

        // Log creation of new records with initial state
        static::created(function ($model) {
            $stateFields = static::$stateFields ?? static::$defaultStateFields;

            try {
                $initialState = [];
                foreach ($stateFields as $field) {
                    if (isset($model->$field)) {
                        $initialState[$field] = $model->$field;
                    }
                }

                if (!empty($initialState)) {
                    // FIX: Added module field to prevent SQL error
                    AuditLog::create([
                        'action' => class_basename($model) . '.created',
                        'module' => strtolower(class_basename($model)), // e.g., 'companyuser', 'user', 'subscription'
                        'actor_id' => auth()->id(),
                        'actor_type' => auth()->check() ? get_class(auth()->user()) : 'System',
                        'description' => static::getCreationDescription($model, $initialState),
                        'new_values' => $initialState,
                        'metadata' => [
                            'model_type' => get_class($model),
                            'model_id' => $model->id,
                            'initial_state' => $initialState,
                        ],
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Failed to log record creation', [
                    'model' => get_class($model),
                    'id' => $model->id,
                    'error' => $e->getMessage(),
                ]);
            }
        });
    }

    /**
     * Get human-readable description for state change
     *
     * Can be overridden in model for custom descriptions
     *
     * @param object $model
     * @param string $field
     * @param mixed $oldValue
     * @param mixed $newValue
     * @return string
     */
    protected static function getStateChangeDescription($model, string $field, $oldValue, $newValue): string
    {
        $modelName = class_basename($model);
        $identifier = method_exists($model, 'getIdentifierForAudit')
            ? $model->getIdentifierForAudit()
            : "#{$model->id}";

        return "{$modelName} {$identifier}: {$field} changed from '{$oldValue}' to '{$newValue}'";
    }

    /**
     * Get human-readable description for record creation
     *
     * @param object $model
     * @param array $initialState
     * @return string
     */
    protected static function getCreationDescription($model, array $initialState): string
    {
        $modelName = class_basename($model);
        $identifier = method_exists($model, 'getIdentifierForAudit')
            ? $model->getIdentifierForAudit()
            : "#{$model->id}";

        $stateString = collect($initialState)
            ->map(fn($value, $key) => "{$key}={$value}")
            ->implode(', ');

        return "{$modelName} {$identifier} created with state: {$stateString}";
    }

    /**
     * Get state change history for this model instance
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getStateChangeHistory()
    {
        return AuditLog::where('metadata->model_type', get_class($this))
            ->where('metadata->model_id', $this->id)
            ->where('action', 'like', class_basename($this) . '.%')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get specific field change history
     *
     * @param string $field
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getFieldChangeHistory(string $field)
    {
        return AuditLog::where('metadata->model_type', get_class($this))
            ->where('metadata->model_id', $this->id)
            ->where('metadata->field', $field)
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
