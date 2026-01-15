<?php

namespace App\Services;

use App\Models\Company;
use App\Models\CompanyDisclosure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * PHASE 3 HARDENING - Issue 2: Platform-Aware Error Reporting
 *
 * PURPOSE:
 * Make issuer error reports explicitly platform-aware.
 * Classify error severity and define platform reactions.
 *
 * SEVERITY LEVELS:
 * - minor: Typo, formatting, non-material correction
 *   → Platform reaction: Log, no restriction
 *
 * - moderate: Material data correction (e.g., revenue figure)
 *   → Platform reaction: Warning banner, require re-review
 *
 * - major: Fundamental disclosure error (e.g., wrong financial statements)
 *   → Platform reaction: Pause buying, require cross-module re-review
 *
 * - critical: Fraud indicator, intentional misrepresentation
 *   → Platform reaction: Immediate suspension, full investigation
 *
 * ISSUER WORKFLOW UNCHANGED:
 * Issuer still reports error the same way.
 * Platform reaction is automatic based on classification.
 */
class ErrorReportClassificationService
{
    /**
     * Severity levels
     */
    public const SEVERITY_MINOR = 'minor';
    public const SEVERITY_MODERATE = 'moderate';
    public const SEVERITY_MAJOR = 'major';
    public const SEVERITY_CRITICAL = 'critical';

    /**
     * Valid severity levels
     */
    public const VALID_SEVERITIES = [
        self::SEVERITY_MINOR,
        self::SEVERITY_MODERATE,
        self::SEVERITY_MAJOR,
        self::SEVERITY_CRITICAL,
    ];

    /**
     * Classify error severity and determine platform reaction
     *
     * @param CompanyDisclosure $disclosure
     * @param string $errorDescription
     * @param array $originalData
     * @param array $correctedData
     * @param string $issuerProvidedSeverity Optional issuer classification
     * @return array Classification result with platform reaction
     */
    public function classifyErrorReport(
        CompanyDisclosure $disclosure,
        string $errorDescription,
        array $originalData,
        array $correctedData,
        ?string $issuerProvidedSeverity = null
    ): array {
        // Validate issuer-provided severity if present
        if ($issuerProvidedSeverity && !in_array($issuerProvidedSeverity, self::VALID_SEVERITIES)) {
            $issuerProvidedSeverity = null; // Invalid, will auto-classify
        }

        // Auto-classify based on data changes
        $autoClassification = $this->autoClassifySeverity(
            $disclosure,
            $errorDescription,
            $originalData,
            $correctedData
        );

        // Use stricter of issuer-provided or auto-classified
        $finalSeverity = $this->selectStricterSeverity(
            $issuerProvidedSeverity,
            $autoClassification['severity']
        );

        // Determine platform reaction
        $platformReaction = $this->determinePlatformReaction(
            $finalSeverity,
            $disclosure,
            $autoClassification
        );

        return [
            'severity' => $finalSeverity,
            'auto_classification' => $autoClassification,
            'issuer_provided_severity' => $issuerProvidedSeverity,
            'platform_reaction' => $platformReaction,
            'requires_admin_review' => $platformReaction['requires_admin_review'],
            'triggers_investigation' => $platformReaction['triggers_investigation'],
        ];
    }

    /**
     * Auto-classify severity based on data changes
     *
     * HEURISTICS:
     * - Financial data changes → moderate or major
     * - Legal/compliance changes → major
     * - Keywords like "fraud", "intentional", "hidden" → critical
     * - Formatting/typo fixes → minor
     *
     * @param CompanyDisclosure $disclosure
     * @param string $errorDescription
     * @param array $originalData
     * @param array $correctedData
     * @return array Classification with reasoning
     */
    protected function autoClassifySeverity(
        CompanyDisclosure $disclosure,
        string $errorDescription,
        array $originalData,
        array $correctedData
    ): array {
        $reasoning = [];
        $score = 0; // 0-10 scale

        // Check for critical keywords in error description
        $criticalKeywords = ['fraud', 'intentional', 'deliberate', 'hidden', 'concealed', 'misrepresented'];
        $lowerDescription = strtolower($errorDescription);
        foreach ($criticalKeywords as $keyword) {
            if (str_contains($lowerDescription, $keyword)) {
                $score += 10;
                $reasoning[] = "Critical keyword detected: '{$keyword}'";
                break; // One critical keyword is enough
            }
        }

        // Check module type (financial modules are higher risk)
        $moduleCode = $disclosure->module->code ?? '';
        if (str_contains($moduleCode, 'financial') || str_contains($moduleCode, 'revenue') || str_contains($moduleCode, 'valuation')) {
            $score += 3;
            $reasoning[] = 'Financial module - material data';
        }

        if (str_contains($moduleCode, 'legal') || str_contains($moduleCode, 'compliance')) {
            $score += 2;
            $reasoning[] = 'Legal/compliance module';
        }

        // Analyze data changes
        $changedFields = $this->analyzeDataChanges($originalData, $correctedData);
        $changeCount = count($changedFields);

        if ($changeCount > 5) {
            $score += 3;
            $reasoning[] = "Multiple fields changed ({$changeCount})";
        } elseif ($changeCount > 2) {
            $score += 2;
            $reasoning[] = "Several fields changed ({$changeCount})";
        } elseif ($changeCount === 1) {
            $score += 0;
            $reasoning[] = "Single field changed";
        }

        // Check for financial value changes (numbers)
        foreach ($changedFields as $field => $change) {
            if (is_numeric($change['old']) && is_numeric($change['new'])) {
                $percentChange = $change['old'] != 0
                    ? abs((($change['new'] - $change['old']) / $change['old']) * 100)
                    : 100;

                if ($percentChange > 20) {
                    $score += 3;
                    $reasoning[] = "Significant financial change in '{$field}': {$percentChange}%";
                } elseif ($percentChange > 10) {
                    $score += 2;
                    $reasoning[] = "Moderate financial change in '{$field}': {$percentChange}%";
                }
            }
        }

        // Map score to severity
        if ($score >= 10) {
            $severity = self::SEVERITY_CRITICAL;
        } elseif ($score >= 6) {
            $severity = self::SEVERITY_MAJOR;
        } elseif ($score >= 3) {
            $severity = self::SEVERITY_MODERATE;
        } else {
            $severity = self::SEVERITY_MINOR;
        }

        return [
            'severity' => $severity,
            'score' => $score,
            'reasoning' => $reasoning,
            'changed_fields' => $changedFields,
            'change_count' => $changeCount,
        ];
    }

    /**
     * Determine platform reaction based on severity
     *
     * @param string $severity
     * @param CompanyDisclosure $disclosure
     * @param array $classification
     * @return array Platform reaction details
     */
    protected function determinePlatformReaction(
        string $severity,
        CompanyDisclosure $disclosure,
        array $classification
    ): array {
        $company = $disclosure->company;

        switch ($severity) {
            case self::SEVERITY_CRITICAL:
                return [
                    'action' => 'immediate_suspension',
                    'description' => 'Immediate company suspension and full investigation',
                    'requires_admin_review' => true,
                    'triggers_investigation' => true,
                    'pauses_buying' => true,
                    'requires_cross_module_review' => true,
                    'shows_warning_banner' => true,
                    'warning_message' => 'Critical disclosure error reported. Company under investigation.',
                    'admin_alert_priority' => 'critical',
                    'estimated_review_time' => '5-10 business days',
                ];

            case self::SEVERITY_MAJOR:
                return [
                    'action' => 'pause_buying_and_review',
                    'description' => 'Pause buying, require cross-module re-review',
                    'requires_admin_review' => true,
                    'triggers_investigation' => false,
                    'pauses_buying' => true,
                    'requires_cross_module_review' => true,
                    'shows_warning_banner' => true,
                    'warning_message' => 'Major disclosure correction in progress. Investment temporarily paused.',
                    'admin_alert_priority' => 'high',
                    'estimated_review_time' => '3-5 business days',
                ];

            case self::SEVERITY_MODERATE:
                return [
                    'action' => 'require_review',
                    'description' => 'Require admin re-review of corrected disclosure',
                    'requires_admin_review' => true,
                    'triggers_investigation' => false,
                    'pauses_buying' => false, // Buying continues, but correction tracked
                    'requires_cross_module_review' => false,
                    'shows_warning_banner' => false,
                    'warning_message' => null,
                    'admin_alert_priority' => 'medium',
                    'estimated_review_time' => '1-3 business days',
                ];

            case self::SEVERITY_MINOR:
            default:
                return [
                    'action' => 'log_and_continue',
                    'description' => 'Log correction, no restrictions',
                    'requires_admin_review' => false,
                    'triggers_investigation' => false,
                    'pauses_buying' => false,
                    'requires_cross_module_review' => false,
                    'shows_warning_banner' => false,
                    'warning_message' => null,
                    'admin_alert_priority' => 'low',
                    'estimated_review_time' => 'Auto-approved',
                ];
        }
    }

    /**
     * Execute platform reaction (called after error report created)
     *
     * @param Company $company
     * @param array $platformReaction
     * @param int $errorReportId
     * @return void
     */
    public function executePlatformReaction(
        Company $company,
        array $platformReaction,
        int $errorReportId
    ): void {
        DB::beginTransaction();

        try {
            // Execute based on action type
            switch ($platformReaction['action']) {
                case 'immediate_suspension':
                    $this->suspendCompany($company, $errorReportId, $platformReaction);
                    break;

                case 'pause_buying_and_review':
                    $this->pauseBuying($company, $errorReportId, $platformReaction);
                    break;

                case 'require_review':
                    $this->flagForReview($company, $errorReportId, $platformReaction);
                    break;

                case 'log_and_continue':
                    // No action needed, just log
                    Log::info('Minor error report logged', [
                        'company_id' => $company->id,
                        'error_report_id' => $errorReportId,
                    ]);
                    break;
            }

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to execute platform reaction', [
                'company_id' => $company->id,
                'error_report_id' => $errorReportId,
                'platform_reaction' => $platformReaction,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    // =========================================================================
    // HELPER METHODS
    // =========================================================================

    /**
     * Select stricter of two severity levels
     */
    protected function selectStricterSeverity(?string $severity1, ?string $severity2): string
    {
        $order = [
            self::SEVERITY_MINOR => 1,
            self::SEVERITY_MODERATE => 2,
            self::SEVERITY_MAJOR => 3,
            self::SEVERITY_CRITICAL => 4,
        ];

        $level1 = $order[$severity1 ?? self::SEVERITY_MINOR] ?? 1;
        $level2 = $order[$severity2 ?? self::SEVERITY_MINOR] ?? 1;

        return $level1 > $level2
            ? $severity1
            : $severity2;
    }

    /**
     * Analyze data changes between original and corrected
     */
    protected function analyzeDataChanges(array $originalData, array $correctedData): array
    {
        $changes = [];

        foreach ($correctedData as $key => $newValue) {
            $oldValue = $originalData[$key] ?? null;

            if ($oldValue !== $newValue) {
                $changes[$key] = [
                    'old' => $oldValue,
                    'new' => $newValue,
                ];
            }
        }

        return $changes;
    }

    /**
     * Suspend company for critical error
     */
    protected function suspendCompany(Company $company, int $errorReportId, array $reaction): void
    {
        $company->lifecycle_state = 'suspended';
        $company->is_suspended = true;
        $company->suspended_at = now();
        $company->suspended_by = 0; // System/automated
        $company->suspension_reason = 'Critical disclosure error reported - under investigation';
        $company->show_warning_banner = true;
        $company->warning_banner_message = $reaction['warning_message'];
        $company->buying_enabled = false;
        $company->save();

        Log::critical('COMPANY SUSPENDED: Critical error report', [
            'company_id' => $company->id,
            'error_report_id' => $errorReportId,
            'platform_reaction' => $reaction,
        ]);
    }

    /**
     * Pause buying for major error
     */
    protected function pauseBuying(Company $company, int $errorReportId, array $reaction): void
    {
        $company->buying_enabled = false;
        $company->buying_pause_reason = 'Major disclosure correction in progress';
        $company->show_warning_banner = true;
        $company->warning_banner_message = $reaction['warning_message'];
        $company->save();

        Log::warning('BUYING PAUSED: Major error report', [
            'company_id' => $company->id,
            'error_report_id' => $errorReportId,
            'platform_reaction' => $reaction,
        ]);
    }

    /**
     * Flag company for admin review
     */
    protected function flagForReview(Company $company, int $errorReportId, array $reaction): void
    {
        $company->under_investigation = true;
        $company->investigation_reason = 'Moderate disclosure correction reported';
        $company->save();

        Log::info('COMPANY FLAGGED: Moderate error report', [
            'company_id' => $company->id,
            'error_report_id' => $errorReportId,
            'platform_reaction' => $reaction,
        ]);
    }
}
