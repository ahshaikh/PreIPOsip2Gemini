<?php

namespace App\Observers;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Log;

/**
 * AuditLogObserver - Enforce Audit Log Immutability
 *
 * [F.21]: Preserve audit history permanently
 *
 * PROTOCOL:
 * - Audit logs are IMMUTABLE after creation
 * - NO updates allowed
 * - NO deletes allowed
 * - Complete preservation for regulatory compliance
 *
 * EXCEPTIONS:
 * - NONE. Audit logs must NEVER be modified or deleted.
 * - Even system administrators cannot modify audit logs.
 * - Retention: Permanent (or per regulatory requirement, e.g., 7+ years)
 *
 * RATIONALE:
 * - Regulatory compliance (SOC 2, ISO 27001, SEBI)
 * - Forensic investigation capability
 * - Tamper-proof audit trail
 */
class AuditLogObserver
{
    /**
     * Handle the AuditLog "updating" event.
     *
     * CRITICAL: Prevent ALL updates
     *
     * @param AuditLog $auditLog
     * @return bool
     */
    public function updating(AuditLog $auditLog): bool
    {
        Log::critical("IMMUTABILITY VIOLATION: Attempt to modify audit log", [
            'audit_log_id' => $auditLog->id,
            'actor_type' => $auditLog->actor_type,
            'action' => $auditLog->action,
            'module' => $auditLog->module,
            'attempted_changes' => $auditLog->getDirty(),
            'stack_trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5),
        ]);

        throw new \RuntimeException(
            "IMMUTABILITY VIOLATION: Audit logs are immutable. " .
            "Cannot update audit log #{$auditLog->id}. " .
            "Regulatory compliance requires permanent, tamper-proof audit trails."
        );
    }

    /**
     * Handle the AuditLog "deleting" event.
     *
     * CRITICAL: Prevent ALL deletes
     *
     * @param AuditLog $auditLog
     * @return bool
     */
    public function deleting(AuditLog $auditLog): bool
    {
        Log::critical("IMMUTABILITY VIOLATION: Attempt to delete audit log", [
            'audit_log_id' => $auditLog->id,
            'actor_type' => $auditLog->actor_type,
            'action' => $auditLog->action,
            'module' => $auditLog->module,
            'stack_trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5),
        ]);

        throw new \RuntimeException(
            "IMMUTABILITY VIOLATION: Audit logs are immutable. " .
            "Cannot delete audit log #{$auditLog->id}. " .
            "Regulatory compliance requires permanent retention."
        );
    }

    /**
     * Handle the AuditLog "created" event.
     *
     * Log audit log creation (meta-audit)
     *
     * @param AuditLog $auditLog
     * @return void
     */
    public function created(AuditLog $auditLog): void
    {
        // Meta-audit: Log that an audit log was created
        // (This is not stored in database to avoid infinite loop)
        Log::channel('compliance')->info("AUDIT LOG CREATED", [
            'audit_log_id' => $auditLog->id,
            'actor_type' => $auditLog->actor_type,
            'action' => $auditLog->action,
            'module' => $auditLog->module,
            'risk_level' => $auditLog->risk_level,
        ]);
    }
}
