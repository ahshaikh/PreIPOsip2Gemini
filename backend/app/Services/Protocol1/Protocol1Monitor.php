<?php

namespace App\Services\Protocol1;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * PROTOCOL-1 MONITOR
 *
 * PURPOSE:
 * Real-time monitoring and audit system for Protocol-1 enforcement.
 * Tracks violations, metrics, and provides alerting capabilities.
 *
 * FEATURES:
 * - Violation logging to database
 * - Real-time metrics tracking
 * - Anomaly detection
 * - Alert generation for critical violations
 * - Compliance reporting
 *
 * USAGE:
 * Called automatically by Protocol1Validator to record violations.
 * Admin dashboard consumes metrics via API endpoints.
 */
class Protocol1Monitor
{
    /**
     * Record protocol violations
     *
     * @param array $violations List of violations
     * @param array $context Validation context
     * @return void
     */
    public function recordViolations(array $violations, array $context): void
    {
        if (empty($violations)) {
            return;
        }

        foreach ($violations as $violation) {
            $this->recordViolation($violation, $context);
        }

        // Check if alert needed
        $this->checkAlertThresholds($violations, $context);

        // Update metrics cache
        $this->updateMetrics($violations);
    }

    /**
     * Record single violation
     *
     * @param array $violation Violation details
     * @param array $context Validation context
     * @return int Violation log ID
     */
    protected function recordViolation(array $violation, array $context): int
    {
        $logId = DB::table('protocol1_violation_log')->insertGetId([
            'protocol_version' => Protocol1Specification::VERSION,
            'rule_id' => $violation['rule_id'] ?? null,
            'rule_name' => $violation['rule_name'] ?? null,
            'severity' => $violation['severity'] ?? 'MEDIUM',
            'message' => $violation['message'] ?? 'Unspecified violation',

            // Context
            'actor_type' => $context['actor_type'] ?? null,
            'action' => $context['action'] ?? null,
            'company_id' => $context['company']?->id,
            'user_id' => $context['user']?->id,

            // Metadata
            'violation_details' => json_encode($violation),
            'context_data' => json_encode([
                'ip_address' => request()?->ip(),
                'user_agent' => request()?->userAgent(),
                'url' => request()?->fullUrl(),
                'method' => request()?->method(),
            ]),

            // Enforcement
            'was_blocked' => $violation['was_blocked'] ?? false,
            'enforcement_mode' => config('protocol1.enforcement_mode', 'strict'),

            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Log::warning('[PROTOCOL-1 MONITOR] Violation recorded', [
            'violation_log_id' => $logId,
            'rule_id' => $violation['rule_id'],
            'severity' => $violation['severity'],
            'actor_type' => $context['actor_type'],
            'action' => $context['action'],
        ]);

        return $logId;
    }

    /**
     * Check if violations exceed alert thresholds
     *
     * @param array $violations Violations detected
     * @param array $context Validation context
     * @return void
     */
    protected function checkAlertThresholds(array $violations, array $context): void
    {
        $criticalCount = count(array_filter($violations, fn($v) => $v['severity'] === 'CRITICAL'));

        // Alert on any CRITICAL violation
        if ($criticalCount > 0) {
            $this->sendAlert('CRITICAL', [
                'title' => 'CRITICAL Protocol-1 Violation Detected',
                'message' => "{$criticalCount} critical violation(s) detected",
                'actor_type' => $context['actor_type'],
                'action' => $context['action'],
                'company_id' => $context['company']?->id,
                'user_id' => $context['user']?->id,
                'violations' => $violations,
            ]);
        }

        // Check rate limits (anomaly detection)
        $recentViolations = $this->getRecentViolationCount($context['actor_type'], 5); // Last 5 minutes
        if ($recentViolations > 10) {
            $this->sendAlert('HIGH', [
                'title' => 'High Volume of Protocol-1 Violations',
                'message' => "{$recentViolations} violations in last 5 minutes from {$context['actor_type']}",
                'actor_type' => $context['actor_type'],
                'anomaly_detected' => true,
            ]);
        }
    }

    /**
     * Send alert
     *
     * @param string $severity Alert severity
     * @param array $alertData Alert details
     * @return void
     */
    protected function sendAlert(string $severity, array $alertData): void
    {
        // Log alert
        Log::alert('[PROTOCOL-1 ALERT] ' . ($alertData['title'] ?? 'Protocol-1 Alert'), $alertData);

        // Store alert in database
        DB::table('protocol1_alerts')->insert([
            'severity' => $severity,
            'title' => $alertData['title'] ?? 'Protocol-1 Alert',
            'message' => $alertData['message'] ?? '',
            'alert_data' => json_encode($alertData),
            'is_acknowledged' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // TODO: Send notification to admins (email, Slack, SMS, etc.)
        // This can be implemented via Laravel notifications or queue jobs
    }

    /**
     * Update metrics cache
     *
     * @param array $violations Violations detected
     * @return void
     */
    protected function updateMetrics(array $violations): void
    {
        $date = now()->toDateString();

        foreach ($violations as $violation) {
            $severity = $violation['severity'] ?? 'MEDIUM';
            $ruleId = $violation['rule_id'] ?? 'UNKNOWN';

            // Increment counters
            Cache::increment("protocol1:metrics:{$date}:total");
            Cache::increment("protocol1:metrics:{$date}:severity:{$severity}");
            Cache::increment("protocol1:metrics:{$date}:rule:{$ruleId}");
        }
    }

    /**
     * Get recent violation count
     *
     * @param string $actorType Actor type
     * @param int $minutes Time window in minutes
     * @return int Violation count
     */
    protected function getRecentViolationCount(string $actorType, int $minutes = 5): int
    {
        return DB::table('protocol1_violation_log')
            ->where('actor_type', $actorType)
            ->where('created_at', '>=', now()->subMinutes($minutes))
            ->count();
    }

    /**
     * Get Protocol-1 metrics
     *
     * @param string|null $date Date (default: today)
     * @return array Metrics
     */
    public function getMetrics(?string $date = null): array
    {
        $date = $date ?? now()->toDateString();

        return [
            'date' => $date,
            'total_violations' => Cache::get("protocol1:metrics:{$date}:total", 0),
            'by_severity' => [
                'CRITICAL' => Cache::get("protocol1:metrics:{$date}:severity:CRITICAL", 0),
                'HIGH' => Cache::get("protocol1:metrics:{$date}:severity:HIGH", 0),
                'MEDIUM' => Cache::get("protocol1:metrics:{$date}:severity:MEDIUM", 0),
                'LOW' => Cache::get("protocol1:metrics:{$date}:severity:LOW", 0),
            ],
            'top_violated_rules' => $this->getTopViolatedRules($date, 10),
            'by_actor_type' => $this->getViolationsByActorType($date),
        ];
    }

    /**
     * Get top violated rules
     *
     * @param string $date Date
     * @param int $limit Limit
     * @return array Top rules
     */
    protected function getTopViolatedRules(string $date, int $limit = 10): array
    {
        return DB::table('protocol1_violation_log')
            ->select('rule_id', 'rule_name', DB::raw('COUNT(*) as violation_count'))
            ->whereDate('created_at', $date)
            ->groupBy('rule_id', 'rule_name')
            ->orderByDesc('violation_count')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Get violations by actor type
     *
     * @param string $date Date
     * @return array Violations by actor
     */
    protected function getViolationsByActorType(string $date): array
    {
        $results = DB::table('protocol1_violation_log')
            ->select('actor_type', DB::raw('COUNT(*) as violation_count'))
            ->whereDate('created_at', $date)
            ->groupBy('actor_type')
            ->get();

        $byActor = [];
        foreach ($results as $result) {
            $byActor[$result->actor_type] = $result->violation_count;
        }

        return $byActor;
    }

    /**
     * Get compliance score
     *
     * Calculates compliance score based on violations vs total actions
     *
     * @param string|null $date Date (default: today)
     * @return array Compliance score
     */
    public function getComplianceScore(?string $date = null): array
    {
        $date = $date ?? now()->toDateString();

        $totalViolations = Cache::get("protocol1:metrics:{$date}:total", 0);
        $totalActions = Cache::get("protocol1:metrics:{$date}:total_actions", 0);

        if ($totalActions === 0) {
            return [
                'score' => 100,
                'grade' => 'A+',
                'total_actions' => 0,
                'total_violations' => 0,
            ];
        }

        $score = max(0, 100 - (($totalViolations / $totalActions) * 100));

        $grade = match (true) {
            $score >= 95 => 'A+',
            $score >= 90 => 'A',
            $score >= 85 => 'B+',
            $score >= 80 => 'B',
            $score >= 75 => 'C+',
            $score >= 70 => 'C',
            $score >= 60 => 'D',
            default => 'F',
        };

        return [
            'score' => round($score, 2),
            'grade' => $grade,
            'total_actions' => $totalActions,
            'total_violations' => $totalViolations,
            'date' => $date,
        ];
    }

    /**
     * Increment total actions counter
     *
     * Should be called for every platform action to calculate compliance score
     *
     * @return void
     */
    public function incrementActionCounter(): void
    {
        $date = now()->toDateString();
        Cache::increment("protocol1:metrics:{$date}:total_actions");
    }
}
