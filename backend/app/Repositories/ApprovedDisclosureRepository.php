<?php

namespace App\Repositories;

use App\Models\CompanyDisclosure;
use App\Models\DisclosureVersion;
use App\Models\Company;
use App\Exceptions\DisclosureAuthorityViolationException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * PHASE 1 AUDIT FIX: ApprovedDisclosureRepository
 *
 * PURPOSE:
 * Single source of truth for investor-visible disclosure data.
 * ENFORCES the invariant that investors ONLY see approved disclosures
 * with IMMUTABLE version data.
 *
 * INVARIANTS ENFORCED:
 * 1. ONLY status='approved' disclosures returned
 * 2. Disclosure data comes from IMMUTABLE DisclosureVersion, NOT CompanyDisclosure
 * 3. No fallback to draft/previous versions
 * 4. Hard failure if approved disclosure lacks a version (invariant violation)
 *
 * WHY THIS EXISTS:
 * - CompanyDisclosure.disclosure_data is MUTABLE (can be edited for next version)
 * - DisclosureVersion.disclosure_data is IMMUTABLE (observer-enforced)
 * - Investors MUST see the frozen, approved version data
 * - Snapshots MUST capture the frozen version data
 *
 * USAGE:
 * - InvestmentSnapshotService MUST use this for capturing disclosures
 * - Investor-facing APIs MUST use this for disclosure queries
 * - Never query CompanyDisclosure directly for investor-visible data
 */
class ApprovedDisclosureRepository
{
    /**
     * Get all approved disclosures for a company with IMMUTABLE version data
     *
     * CRITICAL: Returns disclosure_data from DisclosureVersion (immutable),
     * NOT from CompanyDisclosure (mutable).
     *
     * @param int $companyId
     * @return array Array of disclosure data with guaranteed immutable content
     * @throws \RuntimeException If approved disclosure lacks version (invariant violation)
     */
    public function getApprovedDisclosuresForInvestor(int $companyId): array
    {
        // Query ONLY approved disclosures with their current version
        $disclosures = CompanyDisclosure::query()
            ->where('company_id', $companyId)
            ->where('status', 'approved')  // CRITICAL: Only approved
            ->where('is_visible', true)    // Must be visible
            ->with(['disclosureModule', 'currentVersion'])
            ->get();

        $result = [];
        $invariantViolations = [];

        foreach ($disclosures as $disclosure) {
            // INVARIANT CHECK: Approved disclosure MUST have a current version
            if (!$disclosure->current_version_id) {
                $invariantViolations[] = [
                    'disclosure_id' => $disclosure->id,
                    'module_id' => $disclosure->disclosure_module_id,
                    'error' => 'Approved disclosure lacks current_version_id',
                ];
                continue;
            }

            $version = $disclosure->currentVersion;

            // INVARIANT CHECK: Version record MUST exist
            if (!$version) {
                $invariantViolations[] = [
                    'disclosure_id' => $disclosure->id,
                    'current_version_id' => $disclosure->current_version_id,
                    'error' => 'current_version_id points to non-existent version',
                ];
                continue;
            }

            // INVARIANT CHECK: Version MUST be locked (immutable)
            if (!$version->is_locked) {
                $invariantViolations[] = [
                    'disclosure_id' => $disclosure->id,
                    'version_id' => $version->id,
                    'error' => 'Version is not locked (immutability violated)',
                ];
                continue;
            }

            // SUCCESS: Return IMMUTABLE version data
            $result[$disclosure->id] = [
                'disclosure_id' => $disclosure->id,
                'module_id' => $disclosure->disclosure_module_id,
                'module_name' => $disclosure->disclosureModule->name ?? 'Unknown Module',
                'module_code' => $disclosure->disclosureModule->code ?? null,
                'status' => 'approved',  // Guaranteed by query
                'visibility' => $disclosure->visibility,

                // CRITICAL: Use IMMUTABLE version data, NOT mutable disclosure data
                'data' => $version->disclosure_data,
                'attachments' => $version->attachments,

                // Version metadata for audit trail
                'version_id' => $version->id,
                'version_number' => $version->version_number,
                'version_hash' => $version->version_hash,
                'approved_at' => $version->approved_at?->toIso8601String(),
                'approved_by' => $version->approved_by,
                'is_locked' => true,  // Guaranteed by check above
                'locked_at' => $version->locked_at?->toIso8601String(),
            ];
        }

        // HARD FAILURE on invariant violations
        if (!empty($invariantViolations)) {
            $this->handleInvariantViolations($companyId, $invariantViolations);
        }

        Log::debug('ApprovedDisclosureRepository: Retrieved approved disclosures', [
            'company_id' => $companyId,
            'count' => count($result),
            'disclosure_ids' => array_keys($result),
        ]);

        return $result;
    }

    /**
     * Get a single approved disclosure with IMMUTABLE version data
     *
     * @param int $disclosureId
     * @return array|null Disclosure data or null if not approved/visible
     * @throws \RuntimeException If approved disclosure lacks version (invariant violation)
     */
    public function getApprovedDisclosure(int $disclosureId): ?array
    {
        $disclosure = CompanyDisclosure::query()
            ->where('id', $disclosureId)
            ->where('status', 'approved')  // CRITICAL: Only approved
            ->where('is_visible', true)    // Must be visible
            ->with(['disclosureModule', 'currentVersion'])
            ->first();

        if (!$disclosure) {
            return null;
        }

        // INVARIANT CHECK: Approved disclosure MUST have a current version
        if (!$disclosure->current_version_id || !$disclosure->currentVersion) {
            $this->handleInvariantViolations($disclosure->company_id, [[
                'disclosure_id' => $disclosure->id,
                'current_version_id' => $disclosure->current_version_id,
                'error' => 'Approved disclosure lacks valid current version',
            ]]);
        }

        $version = $disclosure->currentVersion;

        // INVARIANT CHECK: Version MUST be locked
        if (!$version->is_locked) {
            $this->handleInvariantViolations($disclosure->company_id, [[
                'disclosure_id' => $disclosure->id,
                'version_id' => $version->id,
                'error' => 'Version is not locked (immutability violated)',
            ]]);
        }

        return [
            'disclosure_id' => $disclosure->id,
            'module_id' => $disclosure->disclosure_module_id,
            'module_name' => $disclosure->disclosureModule->name ?? 'Unknown Module',
            'module_code' => $disclosure->disclosureModule->code ?? null,
            'status' => 'approved',
            'visibility' => $disclosure->visibility,

            // CRITICAL: IMMUTABLE version data
            'data' => $version->disclosure_data,
            'attachments' => $version->attachments,

            'version_id' => $version->id,
            'version_number' => $version->version_number,
            'version_hash' => $version->version_hash,
            'approved_at' => $version->approved_at?->toIso8601String(),
            'approved_by' => $version->approved_by,
            'is_locked' => true,
            'locked_at' => $version->locked_at?->toIso8601String(),
        ];
    }

    /**
     * Get version map for snapshot (disclosure_id => version_id)
     *
     * @param int $companyId
     * @return array Map of disclosure_id => version_id
     */
    public function getVersionMap(int $companyId): array
    {
        $disclosures = $this->getApprovedDisclosuresForInvestor($companyId);

        $map = [];
        foreach ($disclosures as $disclosureId => $data) {
            $map[$disclosureId] = $data['version_id'];
        }

        return $map;
    }

    /**
     * Verify version integrity using hash
     *
     * @param int $versionId
     * @return array{valid: bool, message: string, hash: string|null}
     */
    public function verifyVersionIntegrity(int $versionId): array
    {
        $version = DisclosureVersion::find($versionId);

        if (!$version) {
            return [
                'valid' => false,
                'message' => 'Version not found',
                'hash' => null,
            ];
        }

        $computedHash = hash('sha256', json_encode($version->disclosure_data));
        $storedHash = $version->version_hash;

        if ($computedHash !== $storedHash) {
            // CRITICAL: Data tampering detected
            Log::emergency('PHASE 1 AUDIT: VERSION HASH MISMATCH - POSSIBLE TAMPERING', [
                'version_id' => $versionId,
                'disclosure_id' => $version->company_disclosure_id,
                'company_id' => $version->company_id,
                'stored_hash' => $storedHash,
                'computed_hash' => $computedHash,
                'is_locked' => $version->is_locked,
            ]);

            return [
                'valid' => false,
                'message' => 'Hash mismatch - data integrity compromised',
                'hash' => $computedHash,
            ];
        }

        return [
            'valid' => true,
            'message' => 'Integrity verified',
            'hash' => $storedHash,
        ];
    }

    /**
     * Handle invariant violations - HARD FAILURE
     *
     * GOVERNANCE RULE: If an approved disclosure lacks a valid immutable version,
     * the system is in an inconsistent state. We MUST fail hard, not silently skip.
     *
     * @param int $companyId
     * @param array $violations
     * @throws DisclosureAuthorityViolationException Always throws - invariant violations are fatal
     */
    protected function handleInvariantViolations(int $companyId, array $violations): void
    {
        // Create audit log for permanent record (before throwing)
        DB::table('audit_logs')->insert([
            'action' => 'disclosure.invariant_violation',
            'actor_id' => auth()->id(),
            'description' => 'CRITICAL: Approved disclosure lacks valid immutable version',
            'metadata' => json_encode([
                'company_id' => $companyId,
                'violations' => $violations,
                'severity' => 'emergency',
                'audit_phase' => 'phase_1',
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Determine the primary violation type and disclosure ID
        $primaryViolation = $violations[0] ?? [];
        $disclosureId = $primaryViolation['disclosure_id'] ?? null;
        $versionId = $primaryViolation['current_version_id'] ?? $primaryViolation['version_id'] ?? null;
        $errorType = $primaryViolation['error'] ?? 'unknown';

        // Throw appropriate exception based on violation type
        if (str_contains($errorType, 'lacks current_version_id')) {
            throw DisclosureAuthorityViolationException::missingVersion($disclosureId, $companyId);
        }

        if (str_contains($errorType, 'non-existent version')) {
            throw DisclosureAuthorityViolationException::versionNotFound($disclosureId, $versionId, $companyId);
        }

        if (str_contains($errorType, 'not locked')) {
            throw DisclosureAuthorityViolationException::unlockedVersion($disclosureId, $versionId, $companyId);
        }

        // Generic violation - shouldn't happen but handle gracefully
        throw new DisclosureAuthorityViolationException(
            "DISCLOSURE AUTHORITY INVARIANT VIOLATION: " .
            "Company {$companyId} has approved disclosures without valid immutable versions. " .
            "Violations: " . json_encode($violations),
            'generic_violation',
            $disclosureId,
            $companyId,
            $versionId,
            ['violations' => $violations]
        );
    }

    /**
     * Check if a disclosure is investor-visible
     *
     * @param int $disclosureId
     * @return bool
     */
    public function isInvestorVisible(int $disclosureId): bool
    {
        return CompanyDisclosure::query()
            ->where('id', $disclosureId)
            ->where('status', 'approved')
            ->where('is_visible', true)
            ->exists();
    }

    /**
     * Get disclosure count by status for a company
     *
     * @param int $companyId
     * @return array
     */
    public function getDisclosureStatusSummary(int $companyId): array
    {
        return CompanyDisclosure::query()
            ->where('company_id', $companyId)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();
    }
}
