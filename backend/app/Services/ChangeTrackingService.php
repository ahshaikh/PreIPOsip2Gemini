<?php

namespace App\Services;

use App\Models\Company;
use App\Models\CompanyDisclosure;
use App\Models\DisclosureChangeLog;
use App\Models\InvestorViewHistory;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * PHASE 4 - SERVICE: ChangeTrackingService
 *
 * PURPOSE:
 * Track all disclosure changes and show investors "what's new" since last visit.
 *
 * USE CASE:
 * Investor visits company profile → Platform shows:
 * "Since your last visit on Jan 5:
 *  - Financial data updated (revenue increased 15%)
 *  - New risk flag detected (cash flow negative)
 *  - Governance disclosure submitted"
 */
class ChangeTrackingService
{
    /**
     * Log a disclosure change
     *
     * @param CompanyDisclosure $disclosure
     * @param string $changeType
     * @param int $userId
     * @param array $changedFields
     * @param string|null $reason
     * @return DisclosureChangeLog
     */
    public function logChange(
        CompanyDisclosure $disclosure,
        string $changeType,
        int $userId,
        array $changedFields = [],
        ?string $reason = null
    ): DisclosureChangeLog {
        // Determine if change is material
        $isMaterial = $this->isMaterialChange($changeType, $changedFields);
        $priority = $this->calculateNotificationPriority($changeType, $isMaterial);

        $changeLog = DisclosureChangeLog::create([
            'company_disclosure_id' => $disclosure->id,
            'company_id' => $disclosure->company_id,
            'change_type' => $changeType,
            'change_summary' => $this->generateChangeSummary($changeType, $changedFields),
            'changed_fields' => $changedFields,
            'field_diffs' => $this->calculateFieldDiffs($disclosure, $changedFields),
            'changed_by' => $userId,
            'changed_at' => now(),
            'change_reason' => $reason,
            'is_material_change' => $isMaterial,
            'investor_notification_priority' => $priority,
            'version_before' => $disclosure->getOriginal('version_number'),
            'version_after' => $disclosure->version_number,
            'is_visible_to_investors' => $changeType !== 'draft_updated', // Drafts not visible
            'investor_visible_at' => $changeType !== 'draft_updated' ? now() : null,
        ]);

        return $changeLog;
    }

    /**
     * Record investor view for "what's new" tracking
     */
    public function recordInvestorView(
        User $investor,
        Company $company,
        string $viewType,
        array $snapshot
    ): InvestorViewHistory {
        return InvestorViewHistory::create([
            'user_id' => $investor->id,
            'company_id' => $company->id,
            'viewed_at' => now(),
            'view_type' => $viewType,
            'disclosure_snapshot' => $snapshot['disclosures'] ?? null,
            'metrics_snapshot' => $snapshot['metrics'] ?? null,
            'risk_flags_snapshot' => $snapshot['risk_flags'] ?? null,
            'was_under_review' => $snapshot['under_review'] ?? false,
            'data_as_of' => now(),
            'session_id' => request()->session()?->getId(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Get changes since investor's last visit
     */
    public function getChangesSinceLastVisit(User $investor, Company $company): array
    {
        $lastVisit = InvestorViewHistory::where('user_id', $investor->id)
            ->where('company_id', $company->id)
            ->orderBy('viewed_at', 'desc')
            ->first();

        if (!$lastVisit) {
            return ['first_visit' => true, 'changes' => []];
        }

        $changes = DisclosureChangeLog::where('company_id', $company->id)
            ->where('investor_visible_at', '>', $lastVisit->viewed_at)
            ->where('is_visible_to_investors', true)
            ->orderBy('changed_at', 'desc')
            ->get();

        return [
            'first_visit' => false,
            'last_visit_at' => $lastVisit->viewed_at,
            'changes_count' => $changes->count(),
            'changes' => $changes->map(fn($c) => [
                'type' => $c->change_type,
                'summary' => $c->change_summary,
                'changed_at' => $c->changed_at,
                'is_material' => $c->is_material_change,
                'priority' => $c->investor_notification_priority,
            ]),
        ];
    }

    /**
     * Determine if change is material
     */
    private function isMaterialChange(string $changeType, array $changedFields): bool
    {
        $materialChangeTypes = ['approved', 'error_reported', 'rejected'];

        if (in_array($changeType, $materialChangeTypes)) {
            return true;
        }

        // Check if any critical fields changed
        $criticalFields = ['revenue', 'net_profit', 'cash_flow', 'board_members', 'pending_litigation'];
        foreach ($changedFields as $field) {
            if (in_array($field, $criticalFields)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Calculate notification priority
     */
    private function calculateNotificationPriority(string $changeType, bool $isMaterial): string
    {
        if ($changeType === 'error_reported') {
            return 'critical';
        }

        if ($changeType === 'rejected') {
            return 'high';
        }

        if ($isMaterial) {
            return 'medium';
        }

        return 'low';
    }

    private function generateChangeSummary(string $changeType, array $changedFields): string
    {
        return match($changeType) {
            'created' => 'Disclosure created',
            'draft_updated' => sprintf('Draft updated (%d fields changed)', count($changedFields)),
            'submitted' => 'Disclosure submitted for review',
            'approved' => 'Disclosure approved by platform',
            'rejected' => 'Disclosure rejected - requires revision',
            'error_reported' => 'Company reported error in approved disclosure',
            'clarification_added' => 'Platform requested clarification',
            'clarification_answered' => 'Company answered clarification',
            default => 'Disclosure updated',
        };
    }

    private function calculateFieldDiffs(CompanyDisclosure $disclosure, array $changedFields): array
    {
        // Would calculate before/after values for changed fields
        return [];
    }
}
