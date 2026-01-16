<?php

namespace App\Services\Protocol1;

use App\Models\Company;
use App\Models\User;
use App\Services\PlatformSupremacyGuard;
use App\Services\BuyEnablementGuardService;
use App\Services\CrossPhaseEnforcementGuard;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * PROTOCOL-1 VALIDATOR
 *
 * PURPOSE:
 * Comprehensive validation service that enforces all Protocol-1 rules
 * across all actors, actions, and data mutations.
 *
 * USAGE:
 * Call validate() before any critical platform action to ensure
 * it complies with Protocol-1 governance rules.
 *
 * ENFORCEMENT MODES:
 * - BLOCK: Immediately reject action, throw exception
 * - WARN: Allow action but log warning for review
 * - MONITOR: Log only, no action blocking (gradual rollout)
 *
 * INTEGRATION:
 * - HTTP Middleware: Protocol1Middleware
 * - Service Methods: Call explicitly before critical operations
 * - Model Observers: Automatic validation on model changes
 * - Policies: Authorization layer validation
 */
class Protocol1Validator
{
    protected PlatformSupremacyGuard $platformGuard;
    protected BuyEnablementGuardService $buyGuard;
    protected CrossPhaseEnforcementGuard $crossPhaseGuard;
    protected Protocol1Monitor $monitor;

    /**
     * Enforcement mode
     * - strict: Block all violations
     * - lenient: Log warnings, block only CRITICAL
     * - monitor: Log only, no blocking
     */
    protected string $enforcementMode = 'strict';

    public function __construct()
    {
        $this->platformGuard = new PlatformSupremacyGuard();
        $this->buyGuard = new BuyEnablementGuardService();
        $this->crossPhaseGuard = new CrossPhaseEnforcementGuard();
        $this->monitor = new Protocol1Monitor();

        // Get enforcement mode from config
        $this->enforcementMode = config('protocol1.enforcement_mode', 'strict');
    }

    /**
     * Validate action against Protocol-1 rules
     *
     * @param array $context Validation context
     * @return array Validation result
     * @throws \RuntimeException If validation fails in strict mode
     */
    public function validate(array $context): array
    {
        $violations = [];

        // Extract context
        $actorType = $context['actor_type'] ?? null;
        $action = $context['action'] ?? null;
        $company = $context['company'] ?? null;
        $user = $context['user'] ?? null;
        $data = $context['data'] ?? [];

        // Validation timestamp
        $validationStartTime = microtime(true);

        // Get applicable rules for this actor and action
        $applicableRules = $this->getApplicableRules($actorType, $action);

        Log::info('[PROTOCOL-1] Validation started', [
            'actor_type' => $actorType,
            'action' => $action,
            'company_id' => $company?->id,
            'user_id' => $user?->id,
            'applicable_rules_count' => count($applicableRules),
        ]);

        // Run all validation checks
        foreach ($applicableRules as $ruleId => $rule) {
            $ruleViolations = $this->validateRule($ruleId, $rule, $context);
            if (!empty($ruleViolations)) {
                $violations = array_merge($violations, $ruleViolations);
            }
        }

        // Calculate validation duration
        $validationDuration = (microtime(true) - $validationStartTime) * 1000; // ms

        // Categorize violations by severity
        $criticalViolations = array_filter($violations, fn($v) => $v['severity'] === 'CRITICAL');
        $highViolations = array_filter($violations, fn($v) => $v['severity'] === 'HIGH');
        $mediumViolations = array_filter($violations, fn($v) => $v['severity'] === 'MEDIUM');
        $lowViolations = array_filter($violations, fn($v) => $v['severity'] === 'LOW');

        // Determine if action should be blocked
        $shouldBlock = false;
        $blockReason = null;

        if ($this->enforcementMode === 'strict') {
            $shouldBlock = !empty($criticalViolations) || !empty($highViolations);
            if (!empty($criticalViolations)) {
                $blockReason = 'CRITICAL Protocol-1 violation detected';
            } elseif (!empty($highViolations)) {
                $blockReason = 'HIGH severity Protocol-1 violation detected';
            }
        } elseif ($this->enforcementMode === 'lenient') {
            $shouldBlock = !empty($criticalViolations);
            if (!empty($criticalViolations)) {
                $blockReason = 'CRITICAL Protocol-1 violation detected';
            }
        } // monitor mode never blocks

        // Log all violations
        if (!empty($violations)) {
            $this->monitor->recordViolations($violations, $context);

            Log::warning('[PROTOCOL-1] Violations detected', [
                'actor_type' => $actorType,
                'action' => $action,
                'company_id' => $company?->id,
                'critical_count' => count($criticalViolations),
                'high_count' => count($highViolations),
                'medium_count' => count($mediumViolations),
                'low_count' => count($lowViolations),
                'should_block' => $shouldBlock,
                'enforcement_mode' => $this->enforcementMode,
            ]);
        }

        // Build result
        $result = [
            'protocol_version' => Protocol1Specification::VERSION,
            'validation_passed' => empty($criticalViolations) && empty($highViolations),
            'should_block' => $shouldBlock,
            'block_reason' => $blockReason,
            'violations' => [
                'total' => count($violations),
                'critical' => $criticalViolations,
                'high' => $highViolations,
                'medium' => $mediumViolations,
                'low' => $lowViolations,
            ],
            'enforcement_mode' => $this->enforcementMode,
            'validation_duration_ms' => round($validationDuration, 2),
            'timestamp' => now()->toIso8601String(),
        ];

        // Throw exception if should block
        if ($shouldBlock) {
            throw new Protocol1ViolationException($blockReason, $result);
        }

        Log::info('[PROTOCOL-1] Validation completed', [
            'passed' => $result['validation_passed'],
            'violations_count' => $result['violations']['total'],
            'duration_ms' => $result['validation_duration_ms'],
        ]);

        return $result;
    }

    /**
     * Validate specific rule
     *
     * @param string $ruleId Rule identifier
     * @param array $rule Rule specification
     * @param array $context Validation context
     * @return array Violations (empty if compliant)
     */
    protected function validateRule(string $ruleId, array $rule, array $context): array
    {
        $violations = [];

        // Extract context
        $actorType = $context['actor_type'] ?? null;
        $action = $context['action'] ?? null;
        $company = $context['company'] ?? null;
        $user = $context['user'] ?? null;
        $data = $context['data'] ?? [];

        // Platform Supremacy Rules
        if (str_starts_with($ruleId, 'RULE_1_')) {
            $violations = array_merge($violations, $this->validatePlatformSupremacyRule($ruleId, $rule, $context));
        }

        // Immutability Rules
        if (str_starts_with($ruleId, 'RULE_2_')) {
            $violations = array_merge($violations, $this->validateImmutabilityRule($ruleId, $rule, $context));
        }

        // Actor Separation Rules
        if (str_starts_with($ruleId, 'RULE_3_')) {
            $violations = array_merge($violations, $this->validateActorSeparationRule($ruleId, $rule, $context));
        }

        // Attribution Rules
        if (str_starts_with($ruleId, 'RULE_4_')) {
            $violations = array_merge($violations, $this->validateAttributionRule($ruleId, $rule, $context));
        }

        // Buy Eligibility Rules
        if (str_starts_with($ruleId, 'RULE_5_')) {
            $violations = array_merge($violations, $this->validateBuyEligibilityRule($ruleId, $rule, $context));
        }

        // Cross-Phase Enforcement Rules
        if (str_starts_with($ruleId, 'RULE_6_')) {
            $violations = array_merge($violations, $this->validateCrossPhaseRule($ruleId, $rule, $context));
        }

        return $violations;
    }

    /**
     * Validate platform supremacy rules
     */
    protected function validatePlatformSupremacyRule(string $ruleId, array $rule, array $context): array
    {
        $violations = [];
        $company = $context['company'] ?? null;
        $action = $context['action'] ?? null;
        $actorType = $context['actor_type'] ?? null;

        if (!$company || !$action) {
            return $violations;
        }

        // Use PlatformSupremacyGuard to check
        $platformCheck = $this->platformGuard->canPerformAction($company, $action, $context['user'] ?? null);

        if (!$platformCheck['allowed']) {
            $violations[] = [
                'rule_id' => $ruleId,
                'rule_name' => $rule['name'],
                'severity' => $rule['severity'],
                'message' => "Platform supremacy violation: {$platformCheck['reason']}",
                'blocking_state' => $platformCheck['blocking_state'] ?? null,
                'platform_state' => $platformCheck['platform_state'] ?? null,
            ];
        }

        return $violations;
    }

    /**
     * Validate immutability rules
     */
    protected function validateImmutabilityRule(string $ruleId, array $rule, array $context): array
    {
        $violations = [];
        $action = $context['action'] ?? null;
        $targetModel = $context['target_model'] ?? null;
        $targetId = $context['target_id'] ?? null;

        // Check if action is trying to mutate immutable record
        if (in_array($action, $rule['blocked_actions'] ?? [])) {
            // Check if target is locked/immutable
            $isImmutable = $this->checkImmutability($targetModel, $targetId);

            if ($isImmutable) {
                $violations[] = [
                    'rule_id' => $ruleId,
                    'rule_name' => $rule['name'],
                    'severity' => $rule['severity'],
                    'message' => "Immutability violation: Cannot perform '{$action}' on locked/immutable record",
                    'target_model' => $targetModel,
                    'target_id' => $targetId,
                ];
            }
        }

        return $violations;
    }

    /**
     * Validate actor separation rules
     */
    protected function validateActorSeparationRule(string $ruleId, array $rule, array $context): array
    {
        $violations = [];
        $actorType = $context['actor_type'] ?? null;
        $action = $context['action'] ?? null;

        // Check if actor is trying to perform action outside their scope
        if (isset($rule['blocked_actions']) && in_array($action, $rule['blocked_actions'])) {
            $violations[] = [
                'rule_id' => $ruleId,
                'rule_name' => $rule['name'],
                'severity' => $rule['severity'],
                'message' => "Actor separation violation: '{$actorType}' cannot perform '{$action}'",
                'actor_type' => $actorType,
                'attempted_action' => $action,
            ];
        }

        // Check allowed actions (whitelist approach)
        if (isset($rule['allowed_actions']) && !in_array($action, $rule['allowed_actions'])) {
            $violations[] = [
                'rule_id' => $ruleId,
                'rule_name' => $rule['name'],
                'severity' => $rule['severity'],
                'message' => "Actor separation violation: '{$actorType}' action '{$action}' not in allowed list",
                'actor_type' => $actorType,
                'attempted_action' => $action,
                'allowed_actions' => $rule['allowed_actions'],
            ];
        }

        return $violations;
    }

    /**
     * Validate attribution rules
     */
    protected function validateAttributionRule(string $ruleId, array $rule, array $context): array
    {
        $violations = [];
        $actorType = $context['actor_type'] ?? null;
        $action = $context['action'] ?? null;
        $data = $context['data'] ?? [];

        // Check required fields
        if (isset($rule['required_fields'])) {
            foreach ($rule['required_fields'] as $field) {
                if (!isset($data[$field]) || empty($data[$field])) {
                    $violations[] = [
                        'rule_id' => $ruleId,
                        'rule_name' => $rule['name'],
                        'severity' => $rule['severity'],
                        'message' => "Attribution violation: Required field '{$field}' missing for action '{$action}'",
                        'missing_field' => $field,
                        'actor_type' => $actorType,
                    ];
                }
            }
        }

        // Validate actor_type is valid
        if (isset($rule['valid_actor_types'])) {
            if (!in_array($actorType, $rule['valid_actor_types'])) {
                $violations[] = [
                    'rule_id' => $ruleId,
                    'rule_name' => $rule['name'],
                    'severity' => $rule['severity'],
                    'message' => "Attribution violation: Invalid actor_type '{$actorType}'",
                    'provided_actor_type' => $actorType,
                    'valid_actor_types' => $rule['valid_actor_types'],
                ];
            }
        }

        return $violations;
    }

    /**
     * Validate buy eligibility rules
     */
    protected function validateBuyEligibilityRule(string $ruleId, array $rule, array $context): array
    {
        $violations = [];
        $company = $context['company'] ?? null;
        $user = $context['user'] ?? null;
        $action = $context['action'] ?? null;

        if ($action !== 'create_investment' || !$company || !$user) {
            return $violations;
        }

        // Use BuyEnablementGuardService to check
        $buyCheck = $this->buyGuard->canInvest($company->id, $user->id);

        if (!$buyCheck['allowed']) {
            foreach ($buyCheck['blockers'] as $blocker) {
                if ($blocker['severity'] === 'critical') {
                    $violations[] = [
                        'rule_id' => $ruleId,
                        'rule_name' => $rule['name'],
                        'severity' => 'CRITICAL',
                        'message' => "Buy eligibility violation: {$blocker['message']}",
                        'guard' => $blocker['guard'],
                        'blocker_details' => $blocker,
                    ];
                }
            }
        }

        return $violations;
    }

    /**
     * Validate cross-phase enforcement rules
     */
    protected function validateCrossPhaseRule(string $ruleId, array $rule, array $context): array
    {
        $violations = [];
        $action = $context['action'] ?? null;
        $company = $context['company'] ?? null;
        $data = $context['data'] ?? [];

        // Use CrossPhaseEnforcementGuard
        if ($company) {
            try {
                // Check snapshot immutability
                if ($action === 'update_snapshot' || $action === 'delete_snapshot') {
                    $snapshotId = $data['snapshot_id'] ?? null;
                    if ($snapshotId) {
                        $this->crossPhaseGuard->assertSnapshotImmutability($snapshotId, 'investment_disclosure_snapshots');
                    }
                }

                // Check platform context mutation
                if ($action === 'recalculate_platform_context') {
                    // This will throw if mutation not allowed
                    $this->crossPhaseGuard->assertCanMutatePlatformContext($company->id, 'admin_action', $context['user']?->id);
                }
            } catch (\RuntimeException $e) {
                $violations[] = [
                    'rule_id' => $ruleId,
                    'rule_name' => $rule['name'],
                    'severity' => $rule['severity'],
                    'message' => "Cross-phase enforcement violation: {$e->getMessage()}",
                ];
            }
        }

        return $violations;
    }

    /**
     * Check if record is immutable
     */
    protected function checkImmutability(string $modelType, int $id): bool
    {
        switch ($modelType) {
            case 'investment_snapshot':
                $snapshot = DB::table('investment_disclosure_snapshots')->find($id);
                return $snapshot && ($snapshot->is_immutable ?? false);

            case 'platform_context_snapshot':
                $snapshot = DB::table('platform_context_snapshots')->find($id);
                return $snapshot && ($snapshot->is_locked ?? false);

            case 'disclosure':
                $disclosure = DB::table('company_disclosures')->find($id);
                return $disclosure && $disclosure->status === 'approved';

            case 'acknowledgement':
                // Acknowledgements are always immutable
                return true;

            default:
                return false;
        }
    }

    /**
     * Get applicable rules for actor and action
     */
    protected function getApplicableRules(string $actorType, string $action): array
    {
        $allRules = Protocol1Specification::getAllRules();
        $applicable = [];

        foreach ($allRules as $ruleId => $rule) {
            // Check if rule applies to this actor
            if (isset($rule['applies_to'])) {
                if (in_array($actorType, $rule['applies_to']) || in_array('all', $rule['applies_to'])) {
                    $applicable[$ruleId] = $rule;
                }
            }
        }

        return $applicable;
    }
}

/**
 * Protocol-1 Violation Exception
 */
class Protocol1ViolationException extends \RuntimeException
{
    protected array $validationResult;

    public function __construct(string $message, array $validationResult)
    {
        parent::__construct($message);
        $this->validationResult = $validationResult;
    }

    public function getValidationResult(): array
    {
        return $this->validationResult;
    }
}
