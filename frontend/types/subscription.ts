/**
 * Subscription Domain Types - V-ARCH-2026
 *
 * Architected following strict separation principles:
 * 1. Subscription is the Financial Contract (immutable snapshot fields)
 * 2. Plan is a Mutable Template (display metadata only)
 * 3. Separate Persistence from Projection (Base → WithRelations)
 * 4. Immutable Config Snapshots (V-CONTRACT-HARDENING)
 * 5. Transport Consistency (matches Laravel Subscription model exactly)
 * 6. No financial authority in nested plan relation
 *
 * Source of Truth: backend/app/Models/Subscription.php
 * Migration: 2026_02_14_000001_add_bonus_config_snapshot_to_subscriptions.php
 */

import type {
  PlanWithRelations,
  ProgressiveConfig,
  MilestoneConfig,
  ConsistencyConfig,
  WelcomeBonusConfig,
  ReferralTierSimple,
  CelebrationBonusConfig,
  LuckyDrawConfig,
} from './plan';


// ============================================================================
// ENUMS & PRIMITIVES
// ============================================================================

/**
 * Subscription status values - verified from Subscription model
 */
export type SubscriptionStatus = 'pending' | 'active' | 'paused' | 'cancelled' | 'completed';

/**
 * Payment status values - verified from Payment model
 */
export type PaymentStatus = 'pending' | 'paid' | 'failed' | 'refunded' | 'pending_approval';

/**
 * Payment gateway values
 */
export type PaymentGateway = 'razorpay' | 'manual_transfer' | null;

// ============================================================================
// PAYMENT TYPES
// ============================================================================

/**
 * Payment - A single subscription payment instance
 * Source: backend/app/Models/Payment.php
 */
export interface Payment {
  id: number;
  user_id: number;
  subscription_id: number;
  amount: number;
  status: PaymentStatus;
  gateway: PaymentGateway;
  gateway_order_id: string | null;
  gateway_payment_id: string | null;
  payment_proof_path: string | null;
  is_on_time: boolean;
  due_date: string;
  paid_at: string | null;
  created_at: string;
  updated_at: string;
}

/**
 * Payment with optional subscription relation
 */
export interface PaymentWithSubscription extends Payment {
  subscription?: SubscriptionBase;
}

// ============================================================================
// SUBSCRIPTION ENTITY TYPES (Layered Architecture)
// ============================================================================

/**
 * SubscriptionBase - Pure persisted fields only
 * Source: Subscription model $fillable + database columns
 *
 * This is the foundation layer containing only what's stored in subscriptions table.
 * Does NOT include computed fields or eager-loaded relations.
 *
 * FINANCIAL AUTHORITY RULE:
 * All financial values (amount, configs) are snapshots at subscription time.
 * These fields represent the IMMUTABLE CONTRACT between user and platform.
 */
export interface SubscriptionBase {
  // Identity
  id: number;
  user_id: number;
  plan_id: number;
  subscription_code: string;

  // Status & Lifecycle
  status: SubscriptionStatus;
  start_date: string;
  end_date: string;
  next_payment_date: string | null;

  // Financial Contract Fields (IMMUTABLE SNAPSHOT)
  amount: number; // Also known as monthly_amount - the contracted payment amount
  bonus_multiplier: number;
  consecutive_payments_count: number;

  // Payment Configuration
  is_auto_debit: boolean;
  razorpay_subscription_id: string | null;

  // Pause State
  pause_count: number;
  pause_start_date: string | null;
  pause_end_date: string | null;

  // Cancellation State
  cancelled_at: string | null;
  cancellation_reason: string | null;

  // V-CONTRACT-HARDENING: Immutable Bonus Config Snapshots
  // These represent the contractual bonus terms at subscription creation time
  // MUST NEVER be modified after config_snapshot_at is set
  //
  // ARCHITECTURAL NOTE:
  // These fields are for display/reference only on the frontend.
  // All bonus calculations are performed server-side by BonusCalculatorService.
  // Frontend NEVER computes payout-critical values.
  progressive_config: ProgressiveConfig | null;
  milestone_config: MilestoneConfig | null;
  consistency_config: ConsistencyConfig | null;
  welcome_bonus_config: WelcomeBonusConfig | null;
  referral_tiers: ReferralTierSimple[] | null;
  celebration_bonus_config: CelebrationBonusConfig | null;
  lucky_draw_entries: LuckyDrawConfig | null;

  // Snapshot Metadata
  config_snapshot_at: string | null;
  config_snapshot_version: string | null;

  // Timestamps
  created_at: string;
  updated_at: string;
  deleted_at?: string | null;
}

/**
 * SubscriptionWithRelations - SubscriptionBase + eager-loaded relations
 *
 * This is what most API endpoints return when loading with('plan', 'payments').
 *
 * CRITICAL ARCHITECTURAL RULE:
 * The nested `plan` relation is for DISPLAY PURPOSES ONLY.
 * - ✅ Allowed: subscription.plan?.name, subscription.plan?.slug
 * - ❌ Forbidden: subscription.plan?.monthly_amount, subscription.plan?.configs
 *
 * Financial authority MUST come from subscription snapshot fields:
 * - Use subscription.amount (NOT subscription.plan?.monthly_amount)
 * - Use subscription.progressive_config (NOT subscription.plan?.configs)
 * - Use subscription.bonus_multiplier (NOT derived from plan)
 */
export interface SubscriptionWithRelations extends SubscriptionBase {
  plan?: PlanWithRelations;
  payments?: Payment[];
}

/**
 * Alias for compatibility - monthly_amount is the canonical name for subscription.amount
 * Some backend endpoints may return monthly_amount instead of amount
 */
export interface SubscriptionWithMonthlyAmount extends Omit<SubscriptionBase, 'amount'> {
  monthly_amount: number;
}

/**
 * SubscriptionWithRelationsAndMonthlyAmount - Handles both field names
 */
export interface SubscriptionWithRelationsAndMonthlyAmount extends SubscriptionWithMonthlyAmount {
  plan?: PlanWithRelations;
  payments?: Payment[];
}

// ============================================================================
// PAYLOAD TYPES (For mutations)
// ============================================================================

/**
 * CreateSubscriptionPayload - Request body for POST /api/v1/user/subscription
 */
export interface CreateSubscriptionPayload {
  plan_id: number;
}

/**
 * ChangeSubscriptionPlanPayload - Request body for POST /api/v1/user/subscription/change-plan
 */
export interface ChangeSubscriptionPlanPayload {
  new_plan_id: number;
}

/**
 * PauseSubscriptionPayload - Request body for POST /api/v1/user/subscription/pause
 */
export interface PauseSubscriptionPayload {
  months: string | number;
}

/**
 * CancelSubscriptionPayload - Request body for POST /api/v1/user/subscription/cancel
 */
export interface CancelSubscriptionPayload {
  reason: string;
}

/**
 * Payment Initiation Payload - Request body for POST /api/v1/user/payment/initiate
 */
export interface PaymentInitPayload {
  payment_id: number;
  enable_auto_debit?: boolean;
}

/**
 * Payment Initiation Response - Razorpay order/subscription details
 */
export interface PaymentInitResponse {
  type: 'order' | 'subscription';
  razorpay_key: string;
  name: string;
  description: string;
  prefill: {
    name: string;
    email: string;
    contact: string;
  };
  // For one-time orders
  order_id?: string;
  amount?: number;
  currency?: string;
  // For subscriptions
  subscription_id?: string;
}

/**
 * Payment Verification Payload - Request body for POST /api/v1/user/payment/verify
 */
export interface PaymentVerifyPayload {
  payment_id: number;
  razorpay_payment_id: string;
  razorpay_signature: string;
  razorpay_order_id?: string;
  razorpay_subscription_id?: string;
}

/**
 * Subscription Creation Response - Response from POST /api/v1/user/subscription
 * May include wallet payment info and redirect instructions
 */
export interface CreateSubscriptionResponse {
  data: SubscriptionWithRelations;
  paid_from_wallet?: boolean;
  redirect_to?: 'companies' | 'subscription';
  message?: string;
}

// ============================================================================
// TYPE GUARDS & HELPERS
// ============================================================================

/**
 * Check if subscription status is active-like (can make payments, earn bonuses)
 */
export function isActiveLike(subscription: SubscriptionBase | null | undefined): boolean {
  if (!subscription) return false;
  return subscription.status === 'active' || subscription.status === 'pending';
}

/**
 * Check if subscription is paused
 */
export function isPaused(subscription: SubscriptionBase | null | undefined): boolean {
  if (!subscription) return false;
  return subscription.status === 'paused';
}

/**
 * Check if subscription is cancelled or completed (terminal states)
 */
export function isTerminal(subscription: SubscriptionBase | null | undefined): boolean {
  if (!subscription) return false;
  return subscription.status === 'cancelled' || subscription.status === 'completed';
}

/**
 * Check if subscription has valid bonus config snapshot
 */
export function hasValidSnapshot(subscription: SubscriptionBase | null | undefined): boolean {
  if (!subscription) return false;
  return (
    subscription.config_snapshot_at !== null &&
    subscription.config_snapshot_version !== null &&
    subscription.progressive_config !== null
  );
}

/**
 * Get the contracted monthly amount from subscription
 * Handles both 'amount' and 'monthly_amount' field names
 *
 * FINANCIAL AUTHORITY: Subscription snapshot only.
 * This function NEVER falls back to subscription.plan.monthly_amount.
 * The subscription amount is the immutable contractual value.
 */
export function getMonthlyAmount(
  subscription:
    | SubscriptionBase
    | SubscriptionWithMonthlyAmount
    | SubscriptionWithRelations
    | SubscriptionWithRelationsAndMonthlyAmount
    | null
    | undefined
): number | null {
  if (!subscription) return null;

  // Check for amount field first (canonical name in DB)
  if ('amount' in subscription && typeof subscription.amount === 'number') {
    return subscription.amount;
  }

  // Fallback to monthly_amount if present (some API responses use this)
  if ('monthly_amount' in subscription && typeof subscription.monthly_amount === 'number') {
    return subscription.monthly_amount;
  }

  // ❌ FORBIDDEN: Never fall back to subscription.plan.monthly_amount
  // Plan is a mutable template, Subscription is the immutable contract
  return null;
}

/**
 * Type guard to check if an object is a valid SubscriptionBase
 */
export function isSubscription(obj: unknown): obj is SubscriptionBase {
  if (typeof obj !== 'object' || obj === null) {
    return false;
  }
  const record = obj as Record<string, unknown>;
  return (
    typeof record.id === 'number' &&
    typeof record.user_id === 'number' &&
    typeof record.plan_id === 'number' &&
    typeof record.status === 'string'
  );
}

/**
 * Type guard to check if subscription has plan relation loaded
 */
export function hasPlansRelation(
  subscription: SubscriptionBase | SubscriptionWithRelations
): subscription is SubscriptionWithRelations {
  return 'plan' in subscription && subscription.plan !== undefined;
}

/**
 * Safe getter for plan name (display only)
 * ✅ Allowed usage of nested plan
 */
export function getPlanName(subscription: SubscriptionWithRelations | null | undefined): string {
  return subscription?.plan?.name || 'Unknown Plan';
}

/**
 * Safe getter for plan slug (display only)
 * ✅ Allowed usage of nested plan
 */
export function getPlanSlug(subscription: SubscriptionWithRelations | null | undefined): string | null {
  return subscription?.plan?.slug || null;
}

// ============================================================================
// RAZORPAY THIRD-PARTY TYPES (Boundary Hardening)
// ============================================================================

/**
 * Razorpay payment response from payment gateway
 * This is the response received in the handler callback
 */
export interface RazorpayPaymentResponse {
  razorpay_payment_id: string;
  razorpay_signature: string;
  razorpay_order_id?: string;
  razorpay_subscription_id?: string;
}

/**
 * Razorpay prefill data for checkout form
 */
export interface RazorpayPrefill {
  name?: string;
  email?: string;
  contact?: string;
}

/**
 * Razorpay checkout options
 * Minimal typing for third-party Razorpay SDK
 */
export interface RazorpayOptions {
  key: string;
  name: string;
  description: string;
  prefill: RazorpayPrefill;
  handler: (response: RazorpayPaymentResponse) => void | Promise<void>;
  // For one-time orders
  order_id?: string;
  amount?: number;
  currency?: string;
  // For subscriptions
  subscription_id?: string;
}

/**
 * Razorpay SDK instance
 */
export interface RazorpayInstance {
  open(): void;
}

/**
 * Global Window extension for Razorpay SDK
 */
declare global {
  interface Window {
    Razorpay: new (options: RazorpayOptions) => RazorpayInstance;
  }
}

// ============================================================================
// API RESPONSE TYPES
// ============================================================================

/**
 * Paginated payment history response
 */
export interface PaginatedPaymentsResponse {
  data: Payment[];
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
  from: number;
  to: number;
}

/**
 * Generic API error shape
 */
export interface ApiError {
  message: string;
  errors?: Record<string, string[]>;
}

// ============================================================================
// BACKWARD COMPATIBILITY (Deprecated - use specific types)
// ============================================================================

/**
 * @deprecated Use SubscriptionWithRelations instead
 * Preserved for gradual migration
 */
export type Subscription = SubscriptionWithRelations;
