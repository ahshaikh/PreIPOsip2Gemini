<?php

namespace App\Services;

use App\Models\CompanyDisclosure;
use App\Models\DisclosureVersion;

/**
 * PHASE 2 - SERVICE: DisclosureDiffService
 *
 * PURPOSE:
 * Provides version comparison and diff visualization for disclosure data.
 * Helps admins see what changed between submissions/versions.
 *
 * USE CASES:
 * - Admin reviewing resubmitted disclosure wants to see what changed
 * - Investor viewing version history wants to see evolution
 * - Audit trail showing data mutations over time
 * - Clarification context (what data was questioned vs current state)
 *
 * DIFF TYPES:
 * - Between current draft and last approved version
 * - Between two specific versions
 * - Between disclosure data and version snapshot
 * - Changes made during review (tracked edits)
 */
class DisclosureDiffService
{
    /**
     * Compare current disclosure data with last approved version
     *
     * USE CASE: Show admin what changed since last approval
     *
     * @param CompanyDisclosure $disclosure
     * @return array|null Diff data or null if no previous version
     */
    public function diffWithLastApprovedVersion(CompanyDisclosure $disclosure): ?array
    {
        $lastVersion = $disclosure->currentVersion;

        if (!$lastVersion) {
            return null; // No previous approved version
        }

        return $this->generateDiff(
            $lastVersion->disclosure_data,
            $disclosure->disclosure_data,
            [
                'from_type' => 'approved_version',
                'from_version' => $lastVersion->version_number,
                'from_approved_at' => $lastVersion->approved_at,
                'to_type' => 'current_draft',
                'to_status' => $disclosure->status,
            ]
        );
    }

    /**
     * Compare two specific versions
     *
     * USE CASE: Investor viewing version history timeline
     *
     * @param DisclosureVersion $fromVersion
     * @param DisclosureVersion $toVersion
     * @return array Diff data
     */
    public function diffBetweenVersions(
        DisclosureVersion $fromVersion,
        DisclosureVersion $toVersion
    ): array {
        if ($fromVersion->company_disclosure_id !== $toVersion->company_disclosure_id) {
            throw new \InvalidArgumentException('Cannot compare versions from different disclosures');
        }

        return $this->generateDiff(
            $fromVersion->disclosure_data,
            $toVersion->disclosure_data,
            [
                'from_type' => 'version',
                'from_version' => $fromVersion->version_number,
                'from_approved_at' => $fromVersion->approved_at,
                'to_type' => 'version',
                'to_version' => $toVersion->version_number,
                'to_approved_at' => $toVersion->approved_at,
            ]
        );
    }

    /**
     * Get all edits made during current review cycle
     *
     * USE CASE: Admin wants to see evolution of disclosure during review
     *
     * @param CompanyDisclosure $disclosure
     * @return array|null Edit history or null if none
     */
    public function getReviewCycleEdits(CompanyDisclosure $disclosure): ?array
    {
        if (empty($disclosure->edits_during_review)) {
            return null;
        }

        return [
            'total_edits' => $disclosure->edit_count_during_review,
            'last_edit_at' => $disclosure->last_edit_during_review_at,
            'edits' => $disclosure->edits_during_review,
            'summary' => $this->summarizeEdits($disclosure->edits_during_review),
        ];
    }

    /**
     * Generate structured diff between two data arrays
     *
     * ALGORITHM:
     * - Deep comparison of nested JSON structures
     * - Categorize changes: added, removed, modified
     * - Calculate change percentage
     * - Highlight significant changes
     *
     * @param array $oldData
     * @param array $newData
     * @param array $metadata Context about comparison
     * @return array Structured diff
     */
    protected function generateDiff(array $oldData, array $newData, array $metadata = []): array
    {
        $changes = [];
        $stats = [
            'added' => 0,
            'removed' => 0,
            'modified' => 0,
            'unchanged' => 0,
        ];

        // Recursive diff
        $changes = $this->recursiveDiff($oldData, $newData, '', $stats);

        // Calculate change percentage
        $totalFields = $stats['added'] + $stats['removed'] + $stats['modified'] + $stats['unchanged'];
        $changedFields = $stats['added'] + $stats['removed'] + $stats['modified'];
        $changePercentage = $totalFields > 0 ? round(($changedFields / $totalFields) * 100, 2) : 0;

        return [
            'metadata' => $metadata,
            'changes' => $changes,
            'statistics' => [
                'total_fields' => $totalFields,
                'changed_fields' => $changedFields,
                'change_percentage' => $changePercentage,
                'added' => $stats['added'],
                'removed' => $stats['removed'],
                'modified' => $stats['modified'],
                'unchanged' => $stats['unchanged'],
            ],
            'has_significant_changes' => $changePercentage > 10, // Flag if >10% changed
            'generated_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Recursively compare nested data structures
     *
     * @param mixed $oldValue
     * @param mixed $newValue
     * @param string $path Current JSON path (e.g., "revenue_streams[0].percentage")
     * @param array &$stats Statistics counter
     * @return array Changes at this level
     */
    protected function recursiveDiff($oldValue, $newValue, string $path, array &$stats): array
    {
        $changes = [];

        // Both are arrays - recurse
        if (is_array($oldValue) && is_array($newValue)) {
            $allKeys = array_unique(array_merge(array_keys($oldValue), array_keys($newValue)));

            foreach ($allKeys as $key) {
                $newPath = $path ? "{$path}.{$key}" : $key;
                $oldExists = array_key_exists($key, $oldValue);
                $newExists = array_key_exists($key, $newValue);

                if (!$oldExists && $newExists) {
                    // Added field
                    $changes[] = [
                        'type' => 'added',
                        'path' => $newPath,
                        'old_value' => null,
                        'new_value' => $newValue[$key],
                    ];
                    $stats['added']++;
                } elseif ($oldExists && !$newExists) {
                    // Removed field
                    $changes[] = [
                        'type' => 'removed',
                        'path' => $newPath,
                        'old_value' => $oldValue[$key],
                        'new_value' => null,
                    ];
                    $stats['removed']++;
                } else {
                    // Both exist - recurse or compare
                    if (is_array($oldValue[$key]) || is_array($newValue[$key])) {
                        $nestedChanges = $this->recursiveDiff(
                            $oldValue[$key],
                            $newValue[$key],
                            $newPath,
                            $stats
                        );
                        $changes = array_merge($changes, $nestedChanges);
                    } else {
                        // Scalar comparison
                        if ($oldValue[$key] !== $newValue[$key]) {
                            $changes[] = [
                                'type' => 'modified',
                                'path' => $newPath,
                                'old_value' => $oldValue[$key],
                                'new_value' => $newValue[$key],
                            ];
                            $stats['modified']++;
                        } else {
                            $stats['unchanged']++;
                        }
                    }
                }
            }
        }
        // One is array, other isn't - type change
        elseif (is_array($oldValue) || is_array($newValue)) {
            $changes[] = [
                'type' => 'type_changed',
                'path' => $path,
                'old_value' => $oldValue,
                'new_value' => $newValue,
                'old_type' => gettype($oldValue),
                'new_type' => gettype($newValue),
            ];
            $stats['modified']++;
        }
        // Both scalar - direct comparison
        else {
            if ($oldValue !== $newValue) {
                $changes[] = [
                    'type' => 'modified',
                    'path' => $path,
                    'old_value' => $oldValue,
                    'new_value' => $newValue,
                ];
                $stats['modified']++;
            } else {
                $stats['unchanged']++;
            }
        }

        return $changes;
    }

    /**
     * Summarize edit history into human-readable insights
     *
     * @param array $edits Raw edit history
     * @return array Summary
     */
    protected function summarizeEdits(array $edits): array
    {
        $allFieldsChanged = [];
        $editsByUser = [];
        $editTimeline = [];

        foreach ($edits as $edit) {
            // Track all fields ever changed
            foreach ($edit['fields_changed'] as $field) {
                if (!isset($allFieldsChanged[$field])) {
                    $allFieldsChanged[$field] = 0;
                }
                $allFieldsChanged[$field]++;
            }

            // Track edits by user
            $userId = $edit['edited_by'];
            if (!isset($editsByUser[$userId])) {
                $editsByUser[$userId] = 0;
            }
            $editsByUser[$userId]++;

            // Timeline entry
            $editTimeline[] = [
                'edit_number' => $edit['edit_number'],
                'timestamp' => $edit['edited_at'],
                'user_id' => $userId,
                'fields_count' => count($edit['fields_changed']),
            ];
        }

        // Sort fields by frequency of change
        arsort($allFieldsChanged);

        return [
            'total_edits' => count($edits),
            'unique_fields_changed' => count($allFieldsChanged),
            'most_edited_fields' => array_slice($allFieldsChanged, 0, 5, true), // Top 5
            'edits_by_user' => $editsByUser,
            'timeline' => $editTimeline,
        ];
    }

    /**
     * Generate change visualization data for UI rendering
     *
     * USE CASE: Frontend needs structured data to render side-by-side diff UI
     *
     * @param array $diff Output from generateDiff()
     * @return array UI-friendly visualization data
     */
    public function generateVisualization(array $diff): array
    {
        $visualBlocks = [];

        foreach ($diff['changes'] as $change) {
            $pathParts = explode('.', $change['path']);
            $fieldName = end($pathParts);
            $section = $pathParts[0] ?? 'root';

            $visualBlocks[] = [
                'section' => $section,
                'field' => $fieldName,
                'path' => $change['path'],
                'change_type' => $change['type'],
                'old_display' => $this->formatValueForDisplay($change['old_value']),
                'new_display' => $this->formatValueForDisplay($change['new_value']),
                'severity' => $this->assessChangeSeverity($change),
            ];
        }

        // Group by section for organized display
        $grouped = [];
        foreach ($visualBlocks as $block) {
            $section = $block['section'];
            if (!isset($grouped[$section])) {
                $grouped[$section] = [];
            }
            $grouped[$section][] = $block;
        }

        return [
            'sections' => $grouped,
            'statistics' => $diff['statistics'],
            'has_significant_changes' => $diff['has_significant_changes'],
        ];
    }

    /**
     * Format value for human-readable display
     *
     * @param mixed $value
     * @return string
     */
    protected function formatValueForDisplay($value): string
    {
        if (is_null($value)) {
            return '(empty)';
        }

        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        if (is_array($value)) {
            return json_encode($value, JSON_PRETTY_PRINT);
        }

        if (is_numeric($value)) {
            return number_format($value, 2);
        }

        return (string) $value;
    }

    /**
     * Assess severity of a change for UI highlighting
     *
     * @param array $change
     * @return string low|medium|high
     */
    protected function assessChangeSeverity(array $change): string
    {
        // Critical fields that should be highlighted
        $criticalFields = [
            'revenue', 'profit', 'valuation', 'share_price',
            'legal_issues', 'compliance_status', 'risk_factors',
        ];

        foreach ($criticalFields as $critical) {
            if (str_contains($change['path'], $critical)) {
                return 'high';
            }
        }

        // Type changes are always medium severity
        if ($change['type'] === 'type_changed') {
            return 'medium';
        }

        // Large value changes (>50% difference for numbers)
        if (is_numeric($change['old_value'] ?? null) && is_numeric($change['new_value'] ?? null)) {
            $old = (float) $change['old_value'];
            $new = (float) $change['new_value'];

            if ($old > 0) {
                $percentChange = abs(($new - $old) / $old) * 100;
                if ($percentChange > 50) {
                    return 'high';
                } elseif ($percentChange > 20) {
                    return 'medium';
                }
            }
        }

        return 'low';
    }

    /**
     * Get version comparison timeline
     *
     * USE CASE: Show investor "what changed over time" narrative
     *
     * @param CompanyDisclosure $disclosure
     * @return array Timeline of all versions with diffs
     */
    public function getVersionTimeline(CompanyDisclosure $disclosure): array
    {
        $versions = $disclosure->versions()
            ->orderBy('version_number', 'asc')
            ->get();

        if ($versions->count() < 2) {
            return [
                'has_history' => false,
                'message' => 'Only one version exists',
            ];
        }

        $timeline = [];

        for ($i = 1; $i < $versions->count(); $i++) {
            $fromVersion = $versions[$i - 1];
            $toVersion = $versions[$i];

            $diff = $this->diffBetweenVersions($fromVersion, $toVersion);

            $timeline[] = [
                'from_version' => $fromVersion->version_number,
                'to_version' => $toVersion->version_number,
                'approved_at' => $toVersion->approved_at,
                'approved_by' => $toVersion->approved_by,
                'change_summary' => $diff['statistics'],
                'has_significant_changes' => $diff['has_significant_changes'],
                'top_changes' => array_slice($diff['changes'], 0, 3), // Show top 3 changes
            ];
        }

        return [
            'has_history' => true,
            'total_versions' => $versions->count(),
            'timeline' => $timeline,
        ];
    }
}
