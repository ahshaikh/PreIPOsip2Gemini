/**
 * Plan Domain Types - V-ARCH-2026
 *
 * Architected following 6 structural principles:
 * 1. Separate Persistence from Projection (PlanBase → PlanWithRelations → AdminPlan)
 * 2. Config Union Discipline (only backend-verified config types)
 * 3. Transport vs Domain Consistency (matches Laravel exactly)
 * 4. deleted_at only in AdminPlan
 * 5. Enum Accuracy for BillingCycle
 * 6. No weak union fallbacks
 *
 * Source of Truth: backend/app/Models/Plan.php, PlanConfig.php, PlanFeature.php
 */

// ============================================================================
// ENUMS & PRIMITIVES
// ============================================================================

/**
 * Billing cycle options - verified from StorePlanRequest validation rules
 */
export type BillingCycle = 'weekly' | 'bi-weekly' | 'monthly' | 'quarterly' | 'yearly';

// ============================================================================
// CONFIG TYPES (Backend-verified from BonusCalculatorService)
// ============================================================================

/**
 * Progressive bonus configuration
 * Source: BonusCalculatorService::calculateProgressive()
 */
export interface ProgressiveConfig {
  rate: number;              // Percentage rate per month
  start_month: number;       // Month when progressive bonus starts
  max_percentage: number;    // Cap on total percentage
  overrides?: Record<number, number>; // Month-specific rate overrides
}

/**
 * Single milestone entry
 * Source: BonusCalculatorService::calculateMilestone()
 */
export interface MilestoneEntry {
  month: number;
  amount: number;
}

/**
 * Milestone entry with optional tracking fields for admin UI
 */
export interface MilestoneEntryEditable extends MilestoneEntry {
  id?: number;
  _uid?: string;
}

/**
 * Milestone configuration is an array of entries
 */
export type MilestoneConfig = MilestoneEntry[];

/**
 * Streak multiplier rule
 */
export interface StreakRule {
  months: number;
  multiplier: number;
}

/**
 * Consistency bonus configuration
 * Source: BonusCalculatorService::calculateConsistency()
 */
export interface ConsistencyConfig {
  amount_per_payment: number;
  streaks?: StreakRule[];
}

/**
 * Welcome bonus configuration
 * Source: BonusCalculatorService::calculateWelcomeBonus()
 */
export interface WelcomeBonusConfig {
  enabled?: boolean;
  amount: number;
}

/**
 * Referral tier configuration (full structure)
 */
export interface ReferralTier {
  name: string;
  min_referrals: number;
  multiplier: number;
}

/**
 * Referral tier configuration (simplified for admin UI)
 * Used by bonuses settings page
 */
export interface ReferralTierSimple {
  count: number;
  multiplier: number;
  id?: number;
  _uid?: string;
}

/**
 * Referral configuration
 * Source: BonusCalculatorService::awardReferralBonus()
 */
export interface ReferralConfig {
  tiers?: ReferralTier[];
}

/**
 * Celebration bonus configuration
 * Source: BonusCalculatorService - birthday/anniversary bonuses
 */
export interface CelebrationBonusConfig {
  birthday_amount: number;
  anniversary_amount: number;
}

/**
 * Lucky draw entries configuration
 * Source: Plan config for lucky draw participation
 */
export interface LuckyDrawConfig {
  count: number;
}

/**
 * Known config keys - discriminated union for type-safe access
 * Only includes configs verified in BonusCalculatorService
 */
export type ConfigKey =
  | 'progressive_config'
  | 'milestone_config'
  | 'consistency_config'
  | 'welcome_bonus_config'
  | 'referral_config'
  | 'celebration_bonus_config'
  | 'lucky_draw_entries'
  | 'referral_tiers';

/**
 * Map of config keys to their value types
 */
export interface ConfigValueMap {
  progressive_config: ProgressiveConfig;
  milestone_config: MilestoneConfig;
  consistency_config: ConsistencyConfig;
  welcome_bonus_config: WelcomeBonusConfig;
  referral_config: ReferralConfig;
  celebration_bonus_config: CelebrationBonusConfig;
  lucky_draw_entries: LuckyDrawConfig;
  referral_tiers: ReferralTierSimple[];
}

// ============================================================================
// RELATION TYPES
// ============================================================================

/**
 * Plan feature - persisted in plan_features table
 * Source: backend/app/Models/PlanFeature.php
 */
export interface PlanFeature {
  id: number;
  plan_id: number;
  feature_text: string;
  icon: string | null;
  display_order: number;
  created_at: string;
  updated_at: string;
}

/**
 * Plan config - persisted in plan_configs table
 * Source: backend/app/Models/PlanConfig.php
 *
 * Note: config_key is typed as ConfigKey for known configs,
 * but the raw API may return string. Use type guards for safe access.
 */
export interface PlanConfig<K extends ConfigKey = ConfigKey> {
  id: number;
  plan_id: number;
  config_key: K;
  value: K extends keyof ConfigValueMap ? ConfigValueMap[K] : unknown;
  created_at: string;
  updated_at: string;
}

/**
 * Generic plan config for when config_key is unknown
 */
export interface GenericPlanConfig {
  id: number;
  plan_id: number;
  config_key: string;
  value: unknown;
  created_at: string;
  updated_at: string;
}

// ============================================================================
// PLAN ENTITY TYPES (Layered Architecture)
// ============================================================================

/**
 * Plan metadata - JSON field in plans table
 */
export interface PlanMetadata {
  emoji?: string;
  [key: string]: unknown;
}

/**
 * PlanBase - Pure persisted fields only
 * Source: Plan model $fillable + database columns
 *
 * This is the foundation layer containing only what's stored in the plans table.
 * Does NOT include computed fields, relations, or admin-only fields.
 *
 * TRANSPORT NOTE: Laravel casts monthly_amount as 'decimal:2' which technically
 * returns a STRING like "5000.00". However, JavaScript coerces this in arithmetic
 * and Intl.NumberFormat.format() accepts both. We type as `number` for ergonomics
 * but consumers doing strict arithmetic should use parseFloat() explicitly.
 */
export interface PlanBase {
  id: number;
  name: string;
  slug: string;
  monthly_amount: number;        // Note: Laravel returns string, JS coerces
  duration_months: number;
  description: string | null;
  is_active: boolean;
  is_featured: boolean;
  display_order: number;
  available_from: string | null; // ISO datetime
  available_until: string | null; // ISO datetime
  max_subscriptions_per_user: number | null;
  allow_pause: boolean;
  max_pause_count: number | null;
  max_pause_duration_months: number | null;
  min_investment: number | null;
  max_investment: number | null;
  billing_cycle: BillingCycle;
  trial_period_days: number;
  metadata: PlanMetadata | null;
  created_at: string;
  updated_at: string;
}

/**
 * PlanWithRelations - PlanBase + eager-loaded relations
 *
 * This is what most API endpoints return when loading with('configs', 'features').
 * The subscribers_count is appended by default via Plan model $appends.
 */
export interface PlanWithRelations extends PlanBase {
  features: PlanFeature[];
  configs: GenericPlanConfig[];
  subscribers_count: number; // Appended accessor from Plan model
}

/**
 * AdminPlan - Full admin view including soft delete and aggregates
 *
 * Used by admin controllers that return withTrashed() or withCount().
 * Includes deleted_at which is irrelevant for public/user contexts.
 */
export interface AdminPlan extends PlanWithRelations {
  deleted_at: string | null;     // SoftDeletes - only visible to admin
  subscriptions_count?: number;  // From withCount('subscriptions')
}

/**
 * PublicPlan - Minimal projection for public API
 *
 * Used by public endpoints that don't need full plan details.
 */
export interface PublicPlan {
  id: number;
  name: string;
  slug: string;
  monthly_amount: number;
  duration_months: number;
  description: string | null;
  is_featured: boolean;
  features: PlanFeature[];
  metadata: PlanMetadata | null;
}

// ============================================================================
// PAYLOAD TYPES (For mutations)
// ============================================================================

/**
 * Feature input - can be string or structured object
 */
export type FeatureInput = string | {
  feature_text: string;
  icon?: string;
  display_order?: number;
};

/**
 * Config input map for create/update operations
 */
export interface ConfigInput {
  progressive_config?: ProgressiveConfig;
  milestone_config?: MilestoneConfig;
  consistency_config?: ConsistencyConfig;
  welcome_bonus_config?: WelcomeBonusConfig;
  referral_config?: ReferralConfig;
}

/**
 * CreatePlanPayload - Request body for POST /api/v1/admin/plans
 * Source: StorePlanRequest validation rules
 */
export interface CreatePlanPayload {
  name: string;
  monthly_amount: number;
  duration_months: number;
  description?: string;
  is_active?: boolean;
  is_featured?: boolean;
  available_from?: string;
  available_until?: string;
  allow_pause?: boolean;
  max_pause_count?: number;
  max_pause_duration_months?: number;
  max_subscriptions_per_user?: number;
  min_investment?: number;
  max_investment?: number;
  display_order?: number;
  billing_cycle?: BillingCycle;
  trial_period_days?: number;
  metadata?: PlanMetadata;
  features?: FeatureInput[];
  configs?: ConfigInput;
}

/**
 * UpdatePlanPayload - Request body for PUT /api/v1/admin/plans/{id}
 * All fields optional for partial updates
 */
export type UpdatePlanPayload = Partial<CreatePlanPayload>;

// ============================================================================
// TYPE GUARDS
// ============================================================================

/**
 * Check if a config_key is a known config type
 */
export function isKnownConfigKey(key: string): key is ConfigKey {
  return [
    'progressive_config',
    'milestone_config',
    'consistency_config',
    'welcome_bonus_config',
    'referral_config',
    'celebration_bonus_config',
    'lucky_draw_entries',
    'referral_tiers',
  ].includes(key);
}

/**
 * Type-safe config accessor
 *
 * @example
 * const progressive = getTypedConfig(plan.configs, 'progressive_config');
 * if (progressive) {
 *   console.log(progressive.rate); // TypeScript knows this is ProgressiveConfig
 * }
 */
export function getTypedConfig<K extends ConfigKey>(
  configs: GenericPlanConfig[],
  key: K
): ConfigValueMap[K] | undefined {
  const config = configs.find(c => c.config_key === key);
  return config?.value as ConfigValueMap[K] | undefined;
}

/**
 * Check if plan has a specific config
 */
export function hasConfig(configs: GenericPlanConfig[], key: ConfigKey): boolean {
  return configs.some(c => c.config_key === key);
}

// ============================================================================
// BACKWARD COMPATIBILITY (Deprecated - use specific types)
// ============================================================================

/**
 * @deprecated Use PlanWithRelations instead
 * Preserved for gradual migration
 */
export type Plan = PlanWithRelations;

/**
 * @deprecated Use MilestoneEntry instead
 * MilestoneConfig now refers to a single entry for backward compatibility
 * with existing code that used MilestoneConfig[]
 */
export type MilestoneConfig = MilestoneEntry;
