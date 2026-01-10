<?php

namespace App\Observers;

use App\Models\DisclosureVersion;
use Illuminate\Support\Facades\Log;

/**
 * PHASE 1 REMEDIATION: DisclosureVersion Immutability Enforcement
 *
 * PURPOSE:
 * Enforces immutability of disclosure version snapshots for regulatory compliance.
 * Prevents any modifications or deletions of version records after creation.
 *
 * REGULATORY REQUIREMENT:
 * SEBI requires permanent audit trail of investor-relied-upon data.
 * Versions must be tamper-proof to prove exact data shown at purchase time.
 *
 * ENFORCEMENT STRATEGY:
 * - Block all updates via updating() event
 * - Block all deletes via deleting() event
 * - Block soft deletes via forceDeleting() event
 * - Log all attempted violations for security audit
 */
class DisclosureVersionObserver
{
    /**
     * Handle the DisclosureVersion "creating" event.
     * Lock versions immediately on creation.
     */
    public function creating(DisclosureVersion $version): void
    {
        // Force is_locked to true on creation
        $version->is_locked = true;
        $version->locked_at = now();
    }

    /**
     * Handle the DisclosureVersion "updating" event.
     * BLOCK all update attempts for immutability.
     */
    public function updating(DisclosureVersion $version): bool
    {
        // Get attempted changes
        $dirty = $version->getDirty();

        // Log critical security violation
        Log::critical('IMMUTABILITY VIOLATION: Attempted to modify locked disclosure version', [
            'version_id' => $version->id,
            'company_id' => $version->company_id,
            'disclosure_id' => $version->company_disclosure_id,
            'version_number' => $version->version_number,
            'version_hash' => $version->version_hash,
            'is_locked' => $version->is_locked,
            'locked_at' => $version->locked_at,
            'approved_at' => $version->approved_at,
            'approved_by' => $version->approved_by,
            'attempted_changes' => array_keys($dirty),
            'old_values' => array_intersect_key($version->getOriginal(), array_flip(array_keys($dirty))),
            'new_values' => $dirty,
            'attempted_by' => auth()->id() ?? 'unauthenticated',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'stack_trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5),
        ]);

        // Create audit log entry
        \App\Models\AuditLog::create([
            'action' => 'disclosure_version.immutability_violation',
            'actor_id' => auth()->id(),
            'description' => 'BLOCKED: Attempted to modify immutable disclosure version',
            'metadata' => [
                'version_id' => $version->id,
                'company_id' => $version->company_id,
                'version_number' => $version->version_number,
                'attempted_changes' => array_keys($dirty),
                'severity' => 'critical',
            ],
        ]);

        // BLOCK the update by returning false
        return false;
    }

    /**
     * Handle the DisclosureVersion "deleting" event.
     * BLOCK all delete attempts for regulatory compliance.
     */
    public function deleting(DisclosureVersion $version): bool
    {
        // Log critical security violation
        Log::critical('IMMUTABILITY VIOLATION: Attempted to delete disclosure version', [
            'version_id' => $version->id,
            'company_id' => $version->company_id,
            'disclosure_id' => $version->company_disclosure_id,
            'version_number' => $version->version_number,
            'version_hash' => $version->version_hash,
            'approved_at' => $version->approved_at,
            'was_investor_visible' => $version->was_investor_visible,
            'investor_view_count' => $version->investor_view_count,
            'linked_transactions_count' => count($version->linked_transactions ?? []),
            'attempted_by' => auth()->id() ?? 'unauthenticated',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'stack_trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5),
        ]);

        // Create audit log entry
        \App\Models\AuditLog::create([
            'action' => 'disclosure_version.deletion_attempted',
            'actor_id' => auth()->id(),
            'description' => 'BLOCKED: Attempted to delete immutable disclosure version',
            'metadata' => [
                'version_id' => $version->id,
                'company_id' => $version->company_id,
                'version_number' => $version->version_number,
                'was_investor_visible' => $version->was_investor_visible,
                'severity' => 'critical',
            ],
        ]);

        // BLOCK the deletion by returning false
        return false;
    }

    /**
     * Handle the DisclosureVersion "forceDeleting" event.
     * BLOCK even force deletes (permanent deletion).
     */
    public function forceDeleting(DisclosureVersion $version): bool
    {
        // Log CRITICAL security violation (force delete attempted)
        Log::emergency('CRITICAL: Force delete attempted on disclosure version', [
            'version_id' => $version->id,
            'company_id' => $version->company_id,
            'version_number' => $version->version_number,
            'attempted_by' => auth()->id() ?? 'unauthenticated',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        // Create audit log entry with highest severity
        \App\Models\AuditLog::create([
            'action' => 'disclosure_version.force_delete_attempted',
            'actor_id' => auth()->id(),
            'description' => 'CRITICAL: Force delete blocked on immutable version',
            'metadata' => [
                'version_id' => $version->id,
                'company_id' => $version->company_id,
                'version_number' => $version->version_number,
                'severity' => 'emergency',
            ],
        ]);

        // BLOCK even force deletion
        return false;
    }

    /**
     * Handle the DisclosureVersion "restoring" event.
     * Allow restoration (versions don't use soft deletes anyway).
     */
    public function restoring(DisclosureVersion $version): void
    {
        // This should never happen (no soft deletes on versions)
        // But if attempted, log it
        Log::warning('Unexpected restore attempted on disclosure version', [
            'version_id' => $version->id,
        ]);
    }
}
