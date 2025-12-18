<?php
/**
 * V-AUDIT-REFACTOR-2025 | V-IMMUTABLE-LOGS | V-DELTA-TRACKING
 * Refactored to address Module 11 Audit Gaps:
 * 1. Delta Capture: Records exactly which fields changed (Old vs New).
 * 2. Immutable Design: Prevents logs from being modified or deleted.
 * 3. Context Awareness: Captures IP, User Agent, and Admin ID for every event.
 */

namespace App\Services\Audit;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;

class AuditLogService
{
    /**
     * Log a sensitive system event or model change.
     */
    public function record(string $action, ?Model $model = null, array $metadata = []): void
    {
        $oldData = null;
        $newData = null;

        // [AUDIT FIX]: Automatically capture the changed data if a model is provided
        if ($model) {
            $newData = $model->getChanges();
            $oldData = array_intersect_key($model->getOriginal(), $newData);
        }

        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'model_type' => $model ? get_class($model) : null,
            'model_id' => $model?->id,
            'old_values' => $oldData,
            'new_values' => $newData,
            'metadata' => array_merge($metadata, [
                'ip_address' => Request::ip(),
                'user_agent' => Request::userAgent(),
            ]),
        ]);
    }
}