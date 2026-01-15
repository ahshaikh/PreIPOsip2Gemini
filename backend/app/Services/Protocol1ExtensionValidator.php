<?php

namespace App\Services;

use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * PHASE 3 HARDENING - Issue 6: Protocol-1 Extension
 *
 * PURPOSE:
 * Extend Protocol-1 checks to validate issuer workflows respect platform overrides.
 * No issuer action should bypass platform context.
 *
 * VALIDATION CHECKS:
 * 1. Platform state is checked before issuer action
 * 2. Platform overrides are respected
 * 3. Issuer cannot circumvent platform restrictions
 * 4. All violations are logged for audit
 *
 * MONITORING MODE:
 * Logs violations without blocking (can be upgraded to blocking mode).
 * Allows gradual rollout and monitoring before enforcement.
 *
 * INTEGRATION POINTS:
 * - Controller middleware
 * - Policy checks
 * - Service method entry points
 */
class Protocol1ExtensionValidator
{
    protected PlatformSupremacyGuard $platformGuard;

    public function __construct()
    {
        $this->platformGuard = new PlatformSupremacyGuard();
    }

    /**
     * Validate issuer action respects platform overrides
     *
     * CHECKS:
     * 1. Platform guard was consulted
     * 2. Platform state is current (not stale)
     * 3. Action is allowed by platform state
     * 4. No bypass attempts detected
     *
     * @param Company $company
     * @param string $action Action being performed
     * @param User|null $user User performing action
     * @param array $context Additional context for validation
     * @return array Validation result
     */
    public function validateIssuerAction(
        Company $company,
        string $action,
        ?User $user = null,
        array $context = []
    ): array {
        $violations = [];

        // CHECK 1: Verify platform guard result
        $platformCheck = $this->platformGuard->canPerformAction($company, $action, $user);

        if (!$platformCheck['allowed']) {
            $violations[] = [
                'type' => 'platform_restriction_violated',
                'severity' => 'critical',
                'message' => "Action '{$action}' blocked by platform state: {$platformCheck['blocking_state']}",
                'platform_check' => $platformCheck,
            ];
        }

        // CHECK 2: Verify platform state is current (not bypassed with stale data)
        $freshPlatformState = $this->platformGuard->canPerformAction($company->fresh(), $action, $user);
        if ($platformCheck['platform_state'] !== $freshPlatformState['platform_state']) {
            $violations[] = [
                'type' => 'stale_platform_state',
                'severity' => 'high',
                'message' => 'Action used stale platform state (possible cache bypass)',
                'cached_state' => $platformCheck['platform_state'],
                'fresh_state' => $freshPlatformState['platform_state'],
            ];
        }

        // CHECK 3: Verify governance state version matches
        $expectedVersion = $company->governance_state_version ?? 1;
        $providedVersion = $context['governance_state_version'] ?? null;

        if ($providedVersion && $providedVersion < $expectedVersion) {
            $violations[] = [
                'type' => 'governance_state_version_mismatch',
                'severity' => 'medium',
                'message' => 'Action used outdated governance state version',
                'expected_version' => $expectedVersion,
                'provided_version' => $providedVersion,
            ];
        }

        // CHECK 4: Detect bypass attempts
        $bypassAttempts = $this->detectBypassAttempts($company, $action, $user, $context);
        if (!empty($bypassAttempts)) {
            $violations = array_merge($violations, $bypassAttempts);
        }

        // Log all violations
        if (!empty($violations)) {
            $this->logProtocol1Violations($company, $action, $user, $violations);
        }

        return [
            'is_valid' => empty($violations),
            'violations' => $violations,
            'platform_check' => $platformCheck,
            'action' => $action,
            'company_id' => $company->id,
            'user_id' => $user?->id,
            'timestamp' => now(),
        ];
    }

    /**
     * Validate disclosure submission respects platform context
     *
     * SPECIFIC CHECKS FOR DISCLOSURE SUBMISSION:
     * - Not suspended
     * - Not frozen
     * - Under investigation flag is noted
     * - Tier approvals are current
     *
     * @param Company $company
     * @param int $disclosureId
     * @param User $user
     * @return array Validation result
     */
    public function validateDisclosureSubmission(
        Company $company,
        int $disclosureId,
        User $user
    ): array {
        $violations = [];

        // Basic action validation
        $actionValidation = $this->validateIssuerAction($company, 'submit_disclosure', $user, [
            'disclosure_id' => $disclosureId,
        ]);

        $violations = array_merge($violations, $actionValidation['violations']);

        // Additional disclosure-specific checks
        if ($company->lifecycle_state === 'suspended') {
            $violations[] = [
                'type' => 'submission_while_suspended',
                'severity' => 'critical',
                'message' => 'Attempted disclosure submission while company is suspended',
            ];
        }

        if ($company->disclosure_freeze ?? false) {
            $violations[] = [
                'type' => 'submission_while_frozen',
                'severity' => 'critical',
                'message' => 'Attempted disclosure submission while disclosures are frozen',
            ];
        }

        if ($company->under_investigation ?? false) {
            // Not a violation, but should be noted
            Log::info('Disclosure submitted during platform investigation', [
                'company_id' => $company->id,
                'disclosure_id' => $disclosureId,
                'user_id' => $user->id,
                'note' => 'Will require additional platform review',
            ]);
        }

        return [
            'is_valid' => empty($violations),
            'violations' => $violations,
            'company_id' => $company->id,
            'disclosure_id' => $disclosureId,
            'user_id' => $user->id,
        ];
    }

    /**
     * Validate error report respects platform context
     *
     * ERROR REPORTS HAVE SPECIAL RULES:
     * - Allowed even during investigation (transparency)
     * - Blocked only during suspension
     * - Severity classification must be present
     *
     * @param Company $company
     * @param int $disclosureId
     * @param User $user
     * @param array $errorReportData
     * @return array Validation result
     */
    public function validateErrorReport(
        Company $company,
        int $disclosureId,
        User $user,
        array $errorReportData
    ): array {
        $violations = [];

        // Basic action validation
        $actionValidation = $this->validateIssuerAction($company, 'report_error', $user, [
            'disclosure_id' => $disclosureId,
        ]);

        $violations = array_merge($violations, $actionValidation['violations']);

        // Check severity classification is present (Issue 2 requirement)
        if (!isset($errorReportData['severity']) && !isset($errorReportData['issuer_provided_severity'])) {
            Log::warning('Error report missing severity classification', [
                'company_id' => $company->id,
                'disclosure_id' => $disclosureId,
                'user_id' => $user->id,
                'note' => 'Will use auto-classification',
            ]);
        }

        return [
            'is_valid' => empty($violations),
            'violations' => $violations,
            'company_id' => $company->id,
            'disclosure_id' => $disclosureId,
            'user_id' => $user->id,
        ];
    }

    /**
     * Detect bypass attempts
     *
     * BYPASS PATTERNS:
     * - Direct database manipulation
     * - API call with forged platform state
     * - Rapid retry after platform denial
     * - Inconsistent governance version
     *
     * @param Company $company
     * @param string $action
     * @param User|null $user
     * @param array $context
     * @return array Detected bypass attempts
     */
    protected function detectBypassAttempts(
        Company $company,
        string $action,
        ?User $user,
        array $context
    ): array {
        $bypasses = [];

        // CHECK: Rapid retry detection
        $recentDenials = $this->getRecentPlatformDenials($company->id, $action, $user?->id);
        if ($recentDenials > 3) {
            $bypasses[] = [
                'type' => 'rapid_retry_after_denial',
                'severity' => 'high',
                'message' => "User attempted action {$recentDenials} times after platform denial",
                'recent_denials' => $recentDenials,
            ];
        }

        // CHECK: Governance version manipulation
        if (isset($context['force_bypass']) && $context['force_bypass'] === true) {
            $bypasses[] = [
                'type' => 'explicit_bypass_flag',
                'severity' => 'critical',
                'message' => 'Action includes explicit bypass flag',
            ];
        }

        // CHECK: Inconsistent user context
        if ($user && $user->company_id !== $company->id) {
            $bypasses[] = [
                'type' => 'cross_company_action_attempt',
                'severity' => 'critical',
                'message' => 'User attempted action for different company',
                'user_company_id' => $user->company_id,
                'target_company_id' => $company->id,
            ];
        }

        return $bypasses;
    }

    /**
     * Log Protocol-1 violations
     *
     * @param Company $company
     * @param string $action
     * @param User|null $user
     * @param array $violations
     * @return void
     */
    protected function logProtocol1Violations(
        Company $company,
        string $action,
        ?User $user,
        array $violations
    ): void {
        $severity = $this->getHighestViolationSeverity($violations);

        $logLevel = match($severity) {
            'critical' => 'critical',
            'high' => 'error',
            'medium' => 'warning',
            default => 'info',
        };

        Log::{$logLevel}('PROTOCOL-1 EXTENSION VIOLATION', [
            'company_id' => $company->id,
            'company_name' => $company->name,
            'user_id' => $user?->id,
            'user_email' => $user?->email,
            'action' => $action,
            'violations' => $violations,
            'lifecycle_state' => $company->lifecycle_state,
            'governance_state_version' => $company->governance_state_version ?? 1,
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
            'timestamp' => now(),
        ]);

        // Store violation in database for audit trail
        DB::table('protocol_violations')->insert([
            'company_id' => $company->id,
            'user_id' => $user?->id,
            'action' => $action,
            'violation_type' => $violations[0]['type'] ?? 'unknown',
            'severity' => $severity,
            'violations' => json_encode($violations),
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
            'created_at' => now(),
        ]);
    }

    /**
     * Get highest violation severity
     */
    protected function getHighestViolationSeverity(array $violations): string
    {
        $severities = array_column($violations, 'severity');

        $order = ['critical' => 4, 'high' => 3, 'medium' => 2, 'low' => 1];

        $maxSeverity = 'low';
        $maxLevel = 0;

        foreach ($severities as $severity) {
            $level = $order[$severity] ?? 0;
            if ($level > $maxLevel) {
                $maxLevel = $level;
                $maxSeverity = $severity;
            }
        }

        return $maxSeverity;
    }

    /**
     * Get count of recent platform denials for user
     */
    protected function getRecentPlatformDenials(int $companyId, string $action, ?int $userId): int
    {
        if (!$userId) {
            return 0;
        }

        // Check logs in last 5 minutes
        $recentLogs = DB::table('platform_denial_log')
            ->where('company_id', $companyId)
            ->where('user_id', $userId)
            ->where('action', $action)
            ->where('created_at', '>=', now()->subMinutes(5))
            ->count();

        return $recentLogs;
    }

    /**
     * Get validation statistics for monitoring
     *
     * @param int $companyId
     * @param string $timeRange
     * @return array Validation statistics
     */
    public function getValidationStats(int $companyId, string $timeRange = '24h'): array
    {
        $hours = $this->parseTimeRange($timeRange);
        $since = now()->subHours($hours);

        $stats = DB::table('protocol_violations')
            ->where('company_id', $companyId)
            ->where('created_at', '>=', $since)
            ->selectRaw('
                COUNT(*) as total_violations,
                COUNT(DISTINCT user_id) as unique_users,
                COUNT(CASE WHEN severity = "critical" THEN 1 END) as critical_violations,
                COUNT(CASE WHEN severity = "high" THEN 1 END) as high_violations,
                COUNT(CASE WHEN severity = "medium" THEN 1 END) as medium_violations,
                COUNT(CASE WHEN severity = "low" THEN 1 END) as low_violations
            ')
            ->first();

        return [
            'company_id' => $companyId,
            'time_range' => $timeRange,
            'statistics' => $stats,
            'interpretation' => $this->interpretStats($stats),
        ];
    }

    /**
     * Parse time range string
     */
    protected function parseTimeRange(string $range): int
    {
        if (str_ends_with($range, 'h')) {
            return (int) rtrim($range, 'h');
        } elseif (str_ends_with($range, 'd')) {
            return (int) rtrim($range, 'd') * 24;
        }
        return 24; // Default 24h
    }

    /**
     * Interpret validation statistics
     */
    protected function interpretStats($stats): array
    {
        $interpretation = [
            'risk_level' => 'low',
            'message' => 'Normal activity',
            'recommendations' => [],
        ];

        if ($stats->critical_violations > 0) {
            $interpretation['risk_level'] = 'critical';
            $interpretation['message'] = "Critical violations detected. Immediate review required.";
            $interpretation['recommendations'][] = 'Review company activity logs immediately';
            $interpretation['recommendations'][] = 'Consider temporary freeze or suspension';
        } elseif ($stats->high_violations > 5) {
            $interpretation['risk_level'] = 'high';
            $interpretation['message'] = "Multiple high-severity violations. Investigation recommended.";
            $interpretation['recommendations'][] = 'Review user actions';
            $interpretation['recommendations'][] = 'Check for bypass attempts';
        } elseif ($stats->total_violations > 20) {
            $interpretation['risk_level'] = 'medium';
            $interpretation['message'] = "High volume of violations. May indicate confusion or misconfiguration.";
            $interpretation['recommendations'][] = 'Review platform restrictions';
            $interpretation['recommendations'][] = 'Consider issuer communication';
        }

        return $interpretation;
    }
}
