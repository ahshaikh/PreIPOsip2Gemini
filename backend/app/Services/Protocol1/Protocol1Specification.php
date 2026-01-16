<?php

namespace App\Services\Protocol1;

/**
 * PROTOCOL-1: PLATFORM GOVERNANCE ENFORCEMENT PROTOCOL
 *
 * VERSION: 1.0.0
 * DATE: 2026-01-16
 *
 * PURPOSE:
 * Protocol-1 is the comprehensive governance enforcement framework that ensures
 * all platform rules from Phases 1-5 are correctly enforced across all actors,
 * actions, and data mutations.
 *
 * SCOPE:
 * - All actor types: Public, Subscriber, Issuer, Admin, System
 * - All actions: Read, Write, Submit, Approve, Reject, Delete
 * - All data: Companies, Disclosures, Investments, Platform Context
 *
 * ENFORCEMENT LAYERS:
 * 1. HTTP Middleware (API requests)
 * 2. Service Layer (business logic entry points)
 * 3. Model Observers (database mutations)
 * 4. Policy Checks (authorization)
 * 5. Queue Jobs (async operations)
 *
 * CORE PRINCIPLES:
 * 1. Platform Supremacy: Platform state overrides all other authority
 * 2. Immutability: Snapshots and locked records cannot be mutated
 * 3. Actor Separation: Each actor has explicit, bounded permissions
 * 4. Explicit Attribution: All actions must have clear actor_type
 * 5. Audit Trail: All violations and overrides must be logged
 * 6. Defensive Default: Block by default, allow by explicit rule
 *
 * VIOLATION SEVERITY LEVELS:
 * - CRITICAL: Immediate block, security alert
 * - HIGH: Block action, require admin override
 * - MEDIUM: Allow with warning, log for review
 * - LOW: Log only, monitoring mode
 *
 * PROTOCOL VERSIONING:
 * Protocol-1 follows semantic versioning (MAJOR.MINOR.PATCH)
 * Breaking changes increment MAJOR version
 * New rules increment MINOR version
 * Bug fixes increment PATCH version
 */
class Protocol1Specification
{
    /**
     * Protocol version
     */
    public const VERSION = '1.0.0';
    public const VERSION_DATE = '2026-01-16';

    /**
     * RULE SET 1: PLATFORM SUPREMACY RULES
     * Platform state overrides all issuer and admin actions
     */
    public const PLATFORM_SUPREMACY_RULES = [
        // Rule 1.1: Suspended companies
        'RULE_1_1_SUSPENSION' => [
            'name' => 'Company Suspension Enforcement',
            'description' => 'Suspended companies cannot perform any issuer actions or accept new investments',
            'severity' => 'CRITICAL',
            'enforcement' => 'BLOCK',
            'applies_to' => ['issuer', 'investor'],
            'blocked_actions' => [
                'edit_disclosure',
                'submit_disclosure',
                'answer_clarification',
                'create_investment',
            ],
            'exceptions' => [], // No exceptions
        ],

        // Rule 1.2: Frozen disclosures
        'RULE_1_2_FREEZE' => [
            'name' => 'Disclosure Freeze Enforcement',
            'description' => 'Frozen disclosures cannot be edited or submitted',
            'severity' => 'CRITICAL',
            'enforcement' => 'BLOCK',
            'applies_to' => ['issuer'],
            'blocked_actions' => [
                'edit_disclosure',
                'submit_disclosure',
            ],
            'exceptions' => ['admin_override_with_reason'],
        ],

        // Rule 1.3: Buying disabled
        'RULE_1_3_BUYING_DISABLED' => [
            'name' => 'Buying Controls Enforcement',
            'description' => 'Companies with buying disabled cannot accept new investments',
            'severity' => 'CRITICAL',
            'enforcement' => 'BLOCK',
            'applies_to' => ['investor'],
            'blocked_actions' => [
                'create_investment',
                'allocate_wallet',
            ],
            'exceptions' => [], // No exceptions
        ],

        // Rule 1.4: Under investigation
        'RULE_1_4_INVESTIGATION' => [
            'name' => 'Investigation Mode Enforcement',
            'description' => 'Companies under investigation have limited issuer access',
            'severity' => 'HIGH',
            'enforcement' => 'BLOCK',
            'applies_to' => ['issuer'],
            'blocked_actions' => [
                'submit_disclosure',
                'delete_disclosure',
            ],
            'exceptions' => ['platform_review_complete'],
        ],
    ];

    /**
     * RULE SET 2: IMMUTABILITY RULES
     * Once locked, records cannot be mutated
     */
    public const IMMUTABILITY_RULES = [
        // Rule 2.1: Investment snapshots
        'RULE_2_1_INVESTMENT_SNAPSHOTS' => [
            'name' => 'Investment Snapshot Immutability',
            'description' => 'Investment disclosure snapshots are permanently frozen after creation',
            'severity' => 'CRITICAL',
            'enforcement' => 'BLOCK',
            'applies_to' => ['system', 'admin', 'issuer'],
            'blocked_actions' => [
                'update_snapshot',
                'delete_snapshot',
                'recalculate_snapshot',
            ],
            'exceptions' => [], // Absolutely no exceptions
        ],

        // Rule 2.2: Platform context snapshots
        'RULE_2_2_PLATFORM_CONTEXT_SNAPSHOTS' => [
            'name' => 'Platform Context Snapshot Immutability',
            'description' => 'Platform context snapshots are permanently locked after creation',
            'severity' => 'CRITICAL',
            'enforcement' => 'BLOCK',
            'applies_to' => ['system', 'admin', 'issuer'],
            'blocked_actions' => [
                'update_snapshot',
                'delete_snapshot',
                'modify_locked_snapshot',
            ],
            'exceptions' => [], // Absolutely no exceptions
        ],

        // Rule 2.3: Approved disclosures
        'RULE_2_3_APPROVED_DISCLOSURES' => [
            'name' => 'Approved Disclosure Lock',
            'description' => 'Approved disclosures cannot be edited by issuers',
            'severity' => 'HIGH',
            'enforcement' => 'BLOCK',
            'applies_to' => ['issuer'],
            'blocked_actions' => [
                'edit_disclosure',
                'delete_disclosure',
            ],
            'exceptions' => ['admin_reopen_for_correction'],
        ],

        // Rule 2.4: Acknowledgement records
        'RULE_2_4_ACKNOWLEDGEMENT_IMMUTABILITY' => [
            'name' => 'Risk Acknowledgement Immutability',
            'description' => 'Risk acknowledgements cannot be deleted or modified after creation',
            'severity' => 'CRITICAL',
            'enforcement' => 'BLOCK',
            'applies_to' => ['system', 'admin', 'investor'],
            'blocked_actions' => [
                'delete_acknowledgement',
                'modify_acknowledgement',
                'backdate_acknowledgement',
            ],
            'exceptions' => [], // No exceptions for compliance
        ],
    ];

    /**
     * RULE SET 3: ACTOR SEPARATION RULES
     * Each actor has bounded permissions
     */
    public const ACTOR_SEPARATION_RULES = [
        // Rule 3.1: Issuer boundaries
        'RULE_3_1_ISSUER_BOUNDARIES' => [
            'name' => 'Issuer Action Boundaries',
            'description' => 'Issuers can only perform issuer-scoped actions on their own company',
            'severity' => 'CRITICAL',
            'enforcement' => 'BLOCK',
            'applies_to' => ['issuer'],
            'allowed_actions' => [
                'edit_own_disclosure_draft',
                'submit_own_disclosure',
                'answer_own_clarification',
                'view_own_company_data',
            ],
            'blocked_actions' => [
                'edit_platform_context',
                'approve_disclosure',
                'suspend_company',
                'change_visibility',
                'access_other_company_data',
            ],
        ],

        // Rule 3.2: Investor boundaries
        'RULE_3_2_INVESTOR_BOUNDARIES' => [
            'name' => 'Investor Action Boundaries',
            'description' => 'Investors can only view approved data and manage their own investments',
            'severity' => 'CRITICAL',
            'enforcement' => 'BLOCK',
            'applies_to' => ['investor'],
            'allowed_actions' => [
                'view_approved_disclosures',
                'view_platform_context',
                'create_own_investment',
                'manage_own_wallet',
                'acknowledge_risks',
            ],
            'blocked_actions' => [
                'view_draft_disclosures',
                'view_under_review_disclosures',
                'edit_company_data',
                'approve_disclosures',
                'access_admin_functions',
            ],
        ],

        // Rule 3.3: Platform context separation
        'RULE_3_3_PLATFORM_CONTEXT_SEPARATION' => [
            'name' => 'Platform Context Write Restrictions',
            'description' => 'Only platform/admin can write to platform context tables',
            'severity' => 'CRITICAL',
            'enforcement' => 'BLOCK',
            'applies_to' => ['issuer', 'investor'],
            'blocked_tables' => [
                'platform_context_snapshots',
                'platform_governance_log',
                'platform_risk_flags',
                'platform_company_metrics',
            ],
            'allowed_actors' => ['admin', 'system'],
        ],
    ];

    /**
     * RULE SET 4: ATTRIBUTION RULES
     * All actions must have explicit actor_type
     */
    public const ATTRIBUTION_RULES = [
        // Rule 4.1: Explicit actor type
        'RULE_4_1_EXPLICIT_ACTOR_TYPE' => [
            'name' => 'Explicit Actor Type Required',
            'description' => 'All platform actions must declare actor_type explicitly',
            'severity' => 'HIGH',
            'enforcement' => 'BLOCK',
            'applies_to' => ['all'],
            'required_fields' => [
                'actor_type', // One of: issuer, admin, system, automated_platform
            ],
            'valid_actor_types' => [
                'issuer',
                'admin_judgment',
                'admin_override',
                'system_enforcement',
                'automated_platform',
            ],
        ],

        // Rule 4.2: Admin actions require reason
        'RULE_4_2_ADMIN_REASON_REQUIRED' => [
            'name' => 'Admin Action Reason Required',
            'description' => 'Admin actions must include explicit reason for audit trail',
            'severity' => 'HIGH',
            'enforcement' => 'BLOCK',
            'applies_to' => ['admin'],
            'required_fields' => [
                'reason',
                'admin_user_id',
            ],
            'actions_requiring_reason' => [
                'suspend_company',
                'freeze_disclosures',
                'change_visibility',
                'override_platform_decision',
                'approve_tier',
            ],
        ],
    ];

    /**
     * RULE SET 5: BUY ELIGIBILITY RULES
     * 6-layer guard system from BuyEnablementGuardService
     */
    public const BUY_ELIGIBILITY_RULES = [
        // Rule 5.1: Company-side guards
        'RULE_5_1_COMPANY_GUARDS' => [
            'name' => 'Company Buy Eligibility Guards',
            'description' => 'Company must meet all requirements for investment',
            'severity' => 'CRITICAL',
            'enforcement' => 'BLOCK',
            'applies_to' => ['investor'],
            'required_conditions' => [
                'tier_2_approved',
                'buying_enabled',
                'not_suspended',
                'not_frozen',
                'investable_lifecycle_state',
            ],
        ],

        // Rule 5.2: Investor-side guards
        'RULE_5_2_INVESTOR_GUARDS' => [
            'name' => 'Investor Buy Eligibility Guards',
            'description' => 'Investor must meet all requirements for investment',
            'severity' => 'CRITICAL',
            'enforcement' => 'BLOCK',
            'applies_to' => ['investor'],
            'required_conditions' => [
                'kyc_approved',
                'account_active',
                'terms_accepted',
            ],
        ],

        // Rule 5.3: Acknowledgement guards
        'RULE_5_3_ACKNOWLEDGEMENT_GUARDS' => [
            'name' => 'Risk Acknowledgement Guards',
            'description' => 'All required risks must be acknowledged before investment',
            'severity' => 'CRITICAL',
            'enforcement' => 'BLOCK',
            'applies_to' => ['investor'],
            'required_acknowledgements' => [
                'illiquidity',
                'no_guarantee',
                'platform_non_advisory',
            ],
            'conditional_acknowledgements' => [
                'material_changes' => 'if_material_changes_detected',
            ],
        ],
    ];

    /**
     * RULE SET 6: CROSS-PHASE ENFORCEMENT RULES
     * All phases respect each other's rules
     */
    public const CROSS_PHASE_ENFORCEMENT_RULES = [
        // Rule 6.1: Snapshot binding
        'RULE_6_1_SNAPSHOT_BINDING' => [
            'name' => 'Investment Snapshot Binding',
            'description' => 'All investments must be bound to immutable snapshots',
            'severity' => 'CRITICAL',
            'enforcement' => 'BLOCK',
            'applies_to' => ['system'],
            'required_bindings' => [
                'disclosure_snapshot_id',
                'platform_context_snapshot_id',
            ],
            'validation' => 'snapshot_exists_and_locked',
        ],

        // Rule 6.2: Material change impact
        'RULE_6_2_MATERIAL_CHANGE_IMPACT' => [
            'name' => 'Material Change Buy Impact',
            'description' => 'Material changes must trigger buy impact rules',
            'severity' => 'HIGH',
            'enforcement' => 'ENFORCE',
            'applies_to' => ['system'],
            'required_actions' => [
                'pause_buying_if_critical_or_high_severity',
                'require_acknowledgement_for_all_material_changes',
            ],
        ],

        // Rule 6.3: Mutation guards
        'RULE_6_3_MUTATION_GUARDS' => [
            'name' => 'Platform Context Mutation Guards',
            'description' => 'Platform context recalculation must respect mutation rules',
            'severity' => 'HIGH',
            'enforcement' => 'BLOCK',
            'applies_to' => ['system'],
            'blocked_during' => [
                'active_dispute',
                'suspension',
                'admin_freeze',
            ],
            'minimum_interval' => '15_minutes',
        ],
    ];

    /**
     * Get all protocol rules
     *
     * @return array All rules indexed by rule ID
     */
    public static function getAllRules(): array
    {
        return array_merge(
            self::PLATFORM_SUPREMACY_RULES,
            self::IMMUTABILITY_RULES,
            self::ACTOR_SEPARATION_RULES,
            self::ATTRIBUTION_RULES,
            self::BUY_ELIGIBILITY_RULES,
            self::CROSS_PHASE_ENFORCEMENT_RULES
        );
    }

    /**
     * Get rule by ID
     *
     * @param string $ruleId Rule identifier
     * @return array|null Rule specification
     */
    public static function getRule(string $ruleId): ?array
    {
        $allRules = self::getAllRules();
        return $allRules[$ruleId] ?? null;
    }

    /**
     * Get rules by severity
     *
     * @param string $severity Severity level (CRITICAL, HIGH, MEDIUM, LOW)
     * @return array Rules matching severity
     */
    public static function getRulesBySeverity(string $severity): array
    {
        return array_filter(self::getAllRules(), function ($rule) use ($severity) {
            return $rule['severity'] === $severity;
        });
    }

    /**
     * Get rules applicable to actor
     *
     * @param string $actorType Actor type (issuer, admin, investor, system, public)
     * @return array Rules applicable to actor
     */
    public static function getRulesForActor(string $actorType): array
    {
        return array_filter(self::getAllRules(), function ($rule) use ($actorType) {
            if (isset($rule['applies_to'])) {
                return in_array($actorType, $rule['applies_to']) || in_array('all', $rule['applies_to']);
            }
            return false;
        });
    }
}
