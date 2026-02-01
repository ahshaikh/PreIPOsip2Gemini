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
     * Fields that contain the actual disclosure content - STRICTLY IMMUTABLE
     * These fields MUST NEVER be modified after creation.
     */
    protected const IMMUTABLE_DATA_FIELDS = [
        'disclosure_data',
        'attachments',
        'version_hash',
        'version_number',
        'company_disclosure_id',
        'company_id',
        'disclosure_module_id',
        'approved_at',
        'approved_by',
        'approval_notes',
        'is_locked',
        'locked_at',
        'changes_summary',
        'change_reason',
        'created_by_type',
        'created_by_id',
        'created_by_ip',
        'created_by_user_agent',
        'sebi_filing_reference',
        'sebi_filed_at',
        'certification',
    ];

    /**
     * Fields that can be updated post-creation (tracking/metadata only)
     * These do NOT affect what investors see - they track view metrics.
     */
    protected const ALLOWED_METADATA_FIELDS = [
        'was_investor_visible',
        'first_investor_view_at',
        'investor_view_count',
        'linked_transactions',
    ];

    /**
     * Handle the DisclosureVersion "updating" event.
     * BLOCK updates to immutable data fields.
     * ALLOW updates to tracking metadata fields only.
     *
     * PHASE 1 AUDIT FIX: Refined immutability that allows view tracking
     * while protecting disclosure content.
     */
    public function updating(DisclosureVersion $version): bool
    {
        $dirty = $version->getDirty();
        $attemptedFields = array_keys($dirty);

        // Check if any immutable data field is being modified
        $immutableViolations = array_intersect($attemptedFields, self::IMMUTABLE_DATA_FIELDS);

        if (!empty($immutableViolations)) {
            // CRITICAL: Immutable data field modification attempted
            Log::critical('PHASE 1 AUDIT: Attempted to modify immutable disclosure version data', [
                'version_id' => $version->id,
                'company_id' => $version->company_id,
                'disclosure_id' => $version->company_disclosure_id,
                'version_number' => $version->version_number,
                'version_hash' => $version->version_hash,
                'violated_fields' => $immutableViolations,
                'all_attempted_changes' => $attemptedFields,
                'old_values' => array_intersect_key($version->getOriginal(), array_flip($immutableViolations)),
                'new_values' => array_intersect_key($dirty, array_flip($immutableViolations)),
                'attempted_by' => auth()->id() ?? 'unauthenticated',
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'stack_trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5),
            ]);

            // Create audit log entry
            \App\Models\AuditLog::create([
                'action' => 'disclosure_version.immutability_violation',
                'actor_id' => auth()->id(),
                'description' => 'BLOCKED: Attempted to modify immutable disclosure version data',
                'metadata' => [
                    'version_id' => $version->id,
                    'company_id' => $version->company_id,
                    'version_number' => $version->version_number,
                    'violated_fields' => $immutableViolations,
                    'severity' => 'critical',
                    'audit_phase' => 'phase_1',
                ],
            ]);

            // BLOCK the update
            return false;
        }

        // Check if only allowed metadata fields are being updated
        $disallowedUpdates = array_diff($attemptedFields, self::ALLOWED_METADATA_FIELDS);
        if (!empty($disallowedUpdates)) {
            Log::warning('PHASE 1 AUDIT: Unknown field update on DisclosureVersion blocked', [
                'version_id' => $version->id,
                'disallowed_fields' => $disallowedUpdates,
            ]);

            return false;
        }

        // Only allowed metadata fields - permit update
        Log::debug('DisclosureVersion metadata update permitted by observer', [
            'version_id' => $version->id,
            'updated_fields' => $attemptedFields,
        ]);

        return true;
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
