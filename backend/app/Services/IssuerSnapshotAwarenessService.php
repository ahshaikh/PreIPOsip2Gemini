<?php

namespace App\Services;

use App\Models\Company;
use App\Models\CompanyDisclosure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * PHASE 3 HARDENING - Issue 5: Issuer Awareness of Investor Snapshots
 *
 * PURPOSE:
 * Make issuers aware (read-only) of which disclosure versions investors see.
 * Show which changes affect only future investors vs existing investors.
 *
 * CRITICAL PRIVACY:
 * - NO investor personal data exposed
 * - NO individual investor investment amounts
 * - ONLY aggregate version awareness: "X investors saw version Y"
 *
 * USE CASES:
 * - Issuer updates revenue figure → See "145 existing investors saw old version"
 * - Issuer wants to know impact of correction → "New version only affects future investors"
 * - Transparency for issuer about investor knowledge state
 */
class IssuerSnapshotAwarenessService
{
    /**
     * Get snapshot awareness summary for disclosure
     *
     * Shows which versions investors currently see.
     * NO investor personal data exposed.
     *
     * @param CompanyDisclosure $disclosure
     * @return array Version awareness summary
     */
    public function getDisclosureVersionAwareness(CompanyDisclosure $disclosure): array
    {
        // Get current approved version
        $currentVersion = $disclosure->version_number;
        $isApproved = $disclosure->status === 'approved';

        // Get historical versions
        $allVersions = $this->getHistoricalVersions($disclosure);

        // Get investor snapshot distribution (aggregate only)
        $snapshotDistribution = $this->getInvestorSnapshotDistribution($disclosure);

        // Calculate impact of changes
        $changeImpact = $this->calculateChangeImpact($disclosure, $snapshotDistribution);

        return [
            'disclosure_id' => $disclosure->id,
            'module_name' => $disclosure->module->name,
            'current_version' => $currentVersion,
            'is_approved' => $isApproved,
            'historical_versions' => $allVersions,
            'investor_snapshot_distribution' => $snapshotDistribution,
            'change_impact' => $changeImpact,
            'privacy_notice' => 'Individual investor data is not shown. Only aggregate version awareness.',
        ];
    }

    /**
     * Get company-wide snapshot awareness
     *
     * Shows across all disclosures which versions investors see.
     *
     * @param Company $company
     * @return array Company-wide version awareness
     */
    public function getCompanySnapshotAwareness(Company $company): array
    {
        $disclosures = $company->disclosures()->with('module')->get();

        $disclosureAwareness = [];
        foreach ($disclosures as $disclosure) {
            $disclosureAwareness[] = $this->getDisclosureVersionAwareness($disclosure);
        }

        // Get overall stats
        $totalInvestors = $this->getTotalInvestorCount($company);
        $investorsWithSnapshots = $this->getInvestorsWithSnapshotsCount($company);

        return [
            'company_id' => $company->id,
            'company_name' => $company->name,
            'disclosures' => $disclosureAwareness,
            'overall_stats' => [
                'total_investors' => $totalInvestors,
                'investors_with_snapshots' => $investorsWithSnapshots,
                'snapshot_coverage_percentage' => $totalInvestors > 0
                    ? round(($investorsWithSnapshots / $totalInvestors) * 100, 1)
                    : 0,
            ],
            'interpretation' => [
                'what_this_means' => 'These numbers show how many investors saw each version of your disclosures.',
                'future_changes' => 'Any changes you make now will only affect NEW investors. Existing investors retain their original snapshots.',
                'privacy' => 'Individual investor identities and investment amounts are not shown.',
            ],
        ];
    }

    /**
     * Get comparison between current version and investor-visible versions
     *
     * Shows issuer what changed and who saw which version.
     *
     * @param CompanyDisclosure $disclosure
     * @return array Version comparison details
     */
    public function getVersionComparisonForIssuer(CompanyDisclosure $disclosure): array
    {
        $currentData = $disclosure->disclosure_data;

        // Get most common investor-visible version
        $investorVersion = $this->getMostCommonInvestorVersion($disclosure);

        if (!$investorVersion) {
            return [
                'has_investors' => false,
                'message' => 'No investors have invested yet. Changes will not affect existing investors.',
            ];
        }

        // Get that version's data
        $investorVersionData = $this->getVersionData($disclosure, $investorVersion['version_number']);

        // Calculate differences
        $differences = $this->calculateDataDifferences($investorVersionData, $currentData);

        return [
            'has_investors' => true,
            'current_version' => $disclosure->version_number,
            'most_common_investor_version' => $investorVersion['version_number'],
            'investors_seeing_old_version' => $investorVersion['investor_count'],
            'investors_seeing_old_version_percentage' => $investorVersion['percentage'],
            'differences' => $differences,
            'difference_count' => count($differences),
            'material_changes' => $this->identifyMaterialChanges($differences),
            'impact_message' => count($differences) > 0
                ? "{$investorVersion['investor_count']} investors saw the old version with " . count($differences) . " different field(s)"
                : 'No material differences between versions',
        ];
    }

    // =========================================================================
    // HELPER METHODS
    // =========================================================================

    /**
     * Get historical versions of disclosure
     */
    protected function getHistoricalVersions(CompanyDisclosure $disclosure): array
    {
        // Get from disclosure_versions table or audit trail
        $versions = DB::table('disclosure_versions')
            ->where('company_disclosure_id', $disclosure->id)
            ->orderBy('version_number', 'desc')
            ->get();

        return $versions->map(function ($version) {
            return [
                'version_number' => $version->version_number,
                'created_at' => $version->created_at,
                'created_by' => $version->created_by_user_id,
                'change_summary' => $version->change_summary ?? 'Version ' . $version->version_number,
            ];
        })->toArray();
    }

    /**
     * Get investor snapshot distribution (aggregate only)
     *
     * RETURNS: Count of investors per version, NO personal data
     */
    protected function getInvestorSnapshotDistribution(CompanyDisclosure $disclosure): array
    {
        // Query investment_disclosure_snapshots
        $distribution = DB::table('investment_disclosure_snapshots as ids')
            ->join('investments as i', 'ids.investment_id', '=', 'i.id')
            ->where('i.company_id', $disclosure->company_id)
            ->where('i.status', 'active') // Only count active investments
            ->selectRaw('
                JSON_UNQUOTE(JSON_EXTRACT(ids.disclosure_versions_map, "$.\"' . $disclosure->id . '\"")) as version_id,
                COUNT(DISTINCT i.user_id) as investor_count
            ')
            ->groupBy('version_id')
            ->get();

        // Map version IDs back to version numbers
        $versionMap = DB::table('disclosure_versions')
            ->where('company_disclosure_id', $disclosure->id)
            ->pluck('version_number', 'id');

        $result = [];
        $totalInvestors = 0;

        foreach ($distribution as $item) {
            if ($item->version_id) {
                $versionNumber = $versionMap[$item->version_id] ?? 'unknown';
                $count = $item->investor_count;
                $totalInvestors += $count;

                $result[] = [
                    'version_number' => $versionNumber,
                    'investor_count' => $count,
                    'note' => "Investors who invested when version {$versionNumber} was live",
                ];
            }
        }

        // Add percentages
        foreach ($result as &$item) {
            $item['percentage'] = $totalInvestors > 0
                ? round(($item['investor_count'] / $totalInvestors) * 100, 1)
                : 0;
        }

        return [
            'total_investors' => $totalInvestors,
            'version_distribution' => $result,
            'privacy_note' => 'Individual investor identities are not exposed',
        ];
    }

    /**
     * Calculate impact of changes between versions
     */
    protected function calculateChangeImpact(
        CompanyDisclosure $disclosure,
        array $snapshotDistribution
    ): array {
        $totalInvestors = $snapshotDistribution['total_investors'];

        if ($totalInvestors === 0) {
            return [
                'has_investors' => false,
                'message' => 'No investors yet. All changes will only affect future investors.',
            ];
        }

        // Count how many investors saw each version
        $investorsWithOldVersions = 0;
        $latestVersion = $disclosure->version_number;

        foreach ($snapshotDistribution['version_distribution'] as $versionDist) {
            if ($versionDist['version_number'] < $latestVersion) {
                $investorsWithOldVersions += $versionDist['investor_count'];
            }
        }

        return [
            'has_investors' => true,
            'total_investors' => $totalInvestors,
            'investors_with_latest_version' => $totalInvestors - $investorsWithOldVersions,
            'investors_with_old_versions' => $investorsWithOldVersions,
            'impact_of_new_changes' => [
                'affects_existing_investors' => false,
                'affects_future_investors_only' => true,
                'message' => 'Any changes you make now will only affect NEW investors. ' .
                             "Existing {$totalInvestors} investors retain their original snapshots.",
            ],
        ];
    }

    /**
     * Get total investor count for company
     */
    protected function getTotalInvestorCount(Company $company): int
    {
        return DB::table('investments')
            ->where('company_id', $company->id)
            ->where('status', 'active')
            ->distinct('user_id')
            ->count('user_id');
    }

    /**
     * Get count of investors with snapshots
     */
    protected function getInvestorsWithSnapshotsCount(Company $company): int
    {
        return DB::table('investment_disclosure_snapshots as ids')
            ->join('investments as i', 'ids.investment_id', '=', 'i.id')
            ->where('i.company_id', $company->id)
            ->where('i.status', 'active')
            ->distinct('i.user_id')
            ->count('i.user_id');
    }

    /**
     * Get most common investor-visible version
     */
    protected function getMostCommonInvestorVersion(CompanyDisclosure $disclosure): ?array
    {
        $distribution = $this->getInvestorSnapshotDistribution($disclosure);

        if ($distribution['total_investors'] === 0) {
            return null;
        }

        $versions = $distribution['version_distribution'];
        usort($versions, fn($a, $b) => $b['investor_count'] <=> $a['investor_count']);

        return $versions[0] ?? null;
    }

    /**
     * Get data for specific version
     */
    protected function getVersionData(CompanyDisclosure $disclosure, int $versionNumber): array
    {
        $version = DB::table('disclosure_versions')
            ->where('company_disclosure_id', $disclosure->id)
            ->where('version_number', $versionNumber)
            ->first();

        return $version ? json_decode($version->version_data, true) : [];
    }

    /**
     * Calculate differences between two data sets
     */
    protected function calculateDataDifferences(array $oldData, array $newData): array
    {
        $differences = [];

        foreach ($newData as $key => $newValue) {
            $oldValue = $oldData[$key] ?? null;

            if ($oldValue !== $newValue) {
                $differences[$key] = [
                    'field' => $key,
                    'old_value' => $oldValue,
                    'new_value' => $newValue,
                    'change_type' => $this->classifyChangeType($key, $oldValue, $newValue),
                ];
            }
        }

        return array_values($differences);
    }

    /**
     * Identify material changes
     */
    protected function identifyMaterialChanges(array $differences): array
    {
        $materialKeywords = ['revenue', 'profit', 'valuation', 'shares', 'price', 'risk', 'legal'];

        return array_filter($differences, function ($diff) use ($materialKeywords) {
            $field = strtolower($diff['field']);

            foreach ($materialKeywords as $keyword) {
                if (str_contains($field, $keyword)) {
                    return true;
                }
            }

            // Also check if numeric change is >10%
            if (is_numeric($diff['old_value']) && is_numeric($diff['new_value']) && $diff['old_value'] != 0) {
                $percentChange = abs((($diff['new_value'] - $diff['old_value']) / $diff['old_value']) * 100);
                if ($percentChange > 10) {
                    return true;
                }
            }

            return false;
        });
    }

    /**
     * Classify type of change
     */
    protected function classifyChangeType(string $field, $oldValue, $newValue): string
    {
        if ($oldValue === null) return 'added';
        if ($newValue === null) return 'removed';
        if (is_numeric($oldValue) && is_numeric($newValue)) return 'numeric_change';
        if (is_string($oldValue) && is_string($newValue)) return 'text_change';
        return 'modified';
    }
}
