// V-ENHANCED-ERROR-HANDLING - API Type Definitions

/**
 * Generic API Response Wrapper
 * Laravel returns data in this format
 */
export interface ApiResponse<T> {
  data: T;
  message?: string;
  errors?: Record<string, string[]>;
  meta?: PaginationMeta;
}

/**
 * Pagination Metadata
 */
export interface PaginationMeta {
  current_page: number;
  from: number;
  last_page: number;
  path: string;
  per_page: number;
  to: number;
  total: number;
}

/**
 * Paginated API Response
 */
export interface PaginatedResponse<T> {
  data: T[];
  links: {
    first: string;
    last: string;
    prev: string | null;
    next: string | null;
  };
  meta: PaginationMeta;
}

/**
 * User Entity
 */
export interface User {
  id: number;
  username: string;
  email: string;
  email_verified_at: string | null;
  status: 'active' | 'pending' | 'suspended' | 'banned';
  role: 'user' | 'admin' | 'super_admin';
  referral_code: string;
  referred_by: number | null;
  created_at: string;
  updated_at: string;
  profile?: UserProfile;
  kyc?: UserKyc;
  kyc_status?: 'pending' | 'verified' | 'rejected' | 'not_submitted';
  subscription?: Subscription;
  bank_details?: BankDetails;
  two_factor_enabled?: boolean;
}

/**
 * User Profile
 */
export interface UserProfile {
  user_id: number;
  first_name: string;
  last_name: string;
  full_name?: string;
  date_of_birth: string | null;
  phone: string | null;
  address: string | null;
  city: string | null;
  state: string | null;
  pincode: string | null;
  country: string;
  avatar_url: string | null;
  created_at: string;
  updated_at: string;
}

/**
 * KYC Data
 */
export interface UserKyc {
  id: number;
  user_id: number;
  status: 'pending' | 'verified' | 'rejected';
  pan_number: string | null;
  aadhar_number: string | null;
  aadhar_front_url: string | null;
  aadhar_back_url: string | null;
  pan_card_url: string | null;
  selfie_url: string | null;
  rejection_reason: string | null;
  verified_at: string | null;
  verified_by: number | null;
  created_at: string;
  updated_at: string;
}

/**
 * Bank Details
 */
export interface BankDetails {
  id: number;
  user_id: number;
  account_holder_name: string;
  account_number: string;
  ifsc_code: string;
  bank_name: string;
  branch_name: string;
  account_type: 'savings' | 'current';
  is_verified: boolean;
  created_at: string;
  updated_at: string;
}

/**
 * Wallet
 */
export interface Wallet {
  id: number;
  user_id: number;
  balance: number;
  locked: number;
  total_deposited: number;
  total_withdrawn: number;
  currency: string;
  created_at: string;
  updated_at: string;
}

/**
 * Wallet Transaction
 */
export interface WalletTransaction {
  id: number;
  wallet_id: number;
  type: 'deposit' | 'withdrawal' | 'investment' | 'refund' | 'bonus' | 'referral';
  amount: number;
  balance_before: number;
  balance_after: number;
  description: string;
  reference_id: string | null;
  status: 'pending' | 'completed' | 'failed' | 'cancelled';
  created_at: string;
  updated_at: string;
}

/**
 * Subscription / Plan
 */
export interface Subscription {
  id: number;
  user_id: number;
  plan_id: number;
  plan?: Plan;
  status: 'active' | 'paused' | 'cancelled' | 'expired';
  amount: number;
  start_date: string;
  end_date: string | null;
  next_billing_date: string | null;
  auto_renew: boolean;
  cancelled_at: string | null;
  created_at: string;
  updated_at: string;
}

/**
 * Plan - Re-exported from dedicated plan types module
 * See types/plan.ts for full type hierarchy
 */
export type { Plan, PlanWithRelations, AdminPlan, PublicPlan, PlanFeature } from './plan';

/**
 * Product / Investment Opportunity
 */
export interface Product {
  id: number;
  name: string;
  slug: string;
  company_name: string;
  description: string;
  category: string;
  price_per_share: number;
  min_investment: number;
  max_investment: number;
  total_shares: number;
  available_shares: number;
  status: 'draft' | 'active' | 'closed' | 'archived';
  featured: boolean;
  listing_date: string | null;
  closing_date: string | null;
  images: string[];
  documents: string[];
  created_at: string;
  updated_at: string;
}

/**
 * User Investment / Portfolio Item
 */
export interface Investment {
  id: number;
  user_id: number;
  product_id: number;
  product?: Product;
  quantity: number;
  price_per_share: number;
  total_amount: number;
  status: 'pending' | 'active' | 'sold' | 'cancelled';
  purchase_date: string;
  certificate_url: string | null;
  created_at: string;
  updated_at: string;
}

/**
 * Payment
 */
export interface Payment {
  id: number;
  user_id: number;
  amount: number;
  currency: string;
  type: 'subscription' | 'investment' | 'wallet_deposit';
  payment_method: 'card' | 'upi' | 'netbanking' | 'wallet' | 'manual';
  status: 'pending' | 'processing' | 'completed' | 'failed' | 'refunded';
  gateway: string;
  transaction_id: string | null;
  gateway_response: any;
  reference_id: string;
  created_at: string;
  updated_at: string;
}

/**
 * Withdrawal Request
 */
export interface WithdrawalRequest {
  id: number;
  user_id: number;
  amount: number;
  status: 'pending' | 'approved' | 'processing' | 'completed' | 'rejected' | 'cancelled';
  bank_account_id: number;
  reference_number: string;
  admin_notes: string | null;
  rejection_reason: string | null;
  processed_at: string | null;
  processed_by: number | null;
  created_at: string;
  updated_at: string;
}

/**
 * Bonus
 */
export interface Bonus {
  id: number;
  user_id: number;
  type: 'signup' | 'referral' | 'investment' | 'loyalty' | 'promotional';
  amount: number;
  description: string;
  status: 'pending' | 'credited' | 'expired';
  expires_at: string | null;
  credited_at: string | null;
  created_at: string;
  updated_at: string;
}

/**
 * Referral Data
 */
export interface ReferralData {
  stats: {
    total_referrals: number;
    active_referrals: number;
    total_earnings: number;
    pending_earnings: number;
    referral_code: string;
  };
  referrals: Referral[];
  recent_earnings: Bonus[];
}

/**
 * Referral
 */
export interface Referral {
  id: number;
  referrer_id: number;
  referred_user_id: number;
  referred_user?: User;
  status: 'pending' | 'active' | 'inactive';
  earnings: number;
  joined_at: string;
  first_investment_at: string | null;
}

/**
 * Notification
 */
export interface Notification {
  id: number;
  user_id: number;
  type: 'investment' | 'payment' | 'kyc' | 'withdrawal' | 'system' | 'promotional';
  title: string;
  message: string;
  data: any;
  read: boolean;
  read_at: string | null;
  created_at: string;
}

/**
 * Support Ticket
 */
export interface SupportTicket {
  id: number;
  user_id: number;
  user?: User;
  subject: string;
  category: 'technical' | 'billing' | 'kyc' | 'general';
  priority: 'low' | 'medium' | 'high' | 'urgent';
  status: 'open' | 'in_progress' | 'waiting_user' | 'resolved' | 'closed';
  assigned_to: number | null;
  rating: number | null;
  feedback: string | null;
  created_at: string;
  updated_at: string;
  replies?: SupportTicketReply[];
}

/**
 * Support Ticket Reply
 */
export interface SupportTicketReply {
  id: number;
  ticket_id: number;
  user_id: number;
  user?: User;
  message: string;
  is_admin: boolean;
  attachments: string[];
  created_at: string;
}

/**
 * Lucky Draw
 */
export interface LuckyDraw {
  id: number;
  title: string;
  description: string;
  prize_amount: number;
  entry_cost: number;
  max_entries: number;
  current_entries: number;
  status: 'upcoming' | 'active' | 'drawing' | 'completed' | 'cancelled';
  draw_date: string;
  winner_id: number | null;
  winner?: User;
  created_at: string;
  updated_at: string;
}

/**
 * Profit Share
 */
export interface ProfitShare {
  id: number;
  product_id: number;
  product?: Product;
  total_profit: number;
  distribution_date: string;
  status: 'pending' | 'calculating' | 'distributed' | 'completed';
  total_investors: number;
  created_at: string;
  updated_at: string;
}

/**
 * Admin Dashboard Stats
 */
export interface AdminDashboardStats {
  users: {
    total: number;
    active: number;
    new_today: number;
    kyc_pending: number;
  };
  financials: {
    total_revenue: number;
    total_investments: number;
    pending_withdrawals: number;
    wallet_balance: number;
  };
  investments: {
    total_products: number;
    active_products: number;
    total_investments: number;
  };
  recent_activities: Activity[];
}

/**
 * Activity Log
 */
export interface Activity {
  id: number;
  user_id: number;
  user?: User;
  type: string;
  description: string;
  ip_address: string;
  user_agent: string;
  created_at: string;
}

/**
 * Settings
 */
export interface GlobalSettings {
  site_name: string;
  site_logo: string;
  support_email: string;
  support_phone: string;
  maintenance_mode: boolean;
  referral_bonus: number;
  min_withdrawal: number;
  withdrawal_fee: number;
  kyc_required: boolean;
  [key: string]: any;
}

/**
 * User Settings
 */
export interface UserSettings {
  notifications?: NotificationSettings;
  security?: SecuritySettings;
  preferences?: PreferenceSettings;
}

export interface NotificationSettings {
  email_notifications: boolean;
  sms_notifications: boolean;
  push_notifications: boolean;
  payment_alerts: boolean;
  investment_updates: boolean;
  promotional_emails: boolean;
  weekly_summary: boolean;
  kyc_updates: boolean;
  withdrawal_alerts: boolean;
  bonus_alerts: boolean;
}

export interface SecuritySettings {
  two_factor_enabled: boolean;
  email_verification: boolean;
  login_alerts: boolean;
  session_timeout: number;
}

export interface PreferenceSettings {
  language: string;
  currency: string;
  timezone: string;
  theme: 'light' | 'dark' | 'auto';
  date_format: string;
  number_format: string;
}

/**
 * Form Data Types
 */
export interface LoginCredentials {
  login: string; // username or email
  password: string;
  remember?: boolean;
}

export interface RegisterData {
  username: string;
  email: string;
  password: string;
  password_confirmation: string;
  referral_code?: string;
}

export interface UpdateProfileData {
  first_name?: string;
  last_name?: string;
  phone?: string;
  address?: string;
  city?: string;
  state?: string;
  pincode?: string;
  date_of_birth?: string;
}

export interface KycSubmission {
  pan_number: string;
  aadhar_number: string;
  aadhar_front: File;
  aadhar_back: File;
  pan_card: File;
  selfie: File;
}

/**
 * Validation Error Response
 */
export interface ValidationErrors {
  message: string;
  errors: Record<string, string[]>;
}

// =============================================================================
// P0 FIX (GAP 35-36): Risk Flag & Snapshot Comparison Types
// =============================================================================

/**
 * Risk Flag with Rationale
 * GAP 35: Risk flags now include explanation for investor clarity
 */
export interface RiskFlag {
  id: number;
  code: string;
  name: string;
  severity: 'low' | 'medium' | 'high' | 'critical';
  category: 'market' | 'liquidity' | 'regulatory' | 'operational' | 'financial';
  is_active: boolean;
  rationale: string;
  mitigation_guidance: string;
  created_at: string;
  updated_at: string;
}

/**
 * Company Risk Assessment
 * Aggregated risk flags for a company/investment
 */
export interface CompanyRiskAssessment {
  company_id: number;
  company_name: string;
  overall_risk_level: 'low' | 'medium' | 'high' | 'critical';
  risk_score: number;
  flags: RiskFlag[];
  last_assessed_at: string;
  assessor_notes?: string;
}

/**
 * Platform Context Snapshot
 * GAP 36: For "then vs now" comparison
 */
export interface PlatformContextSnapshot {
  id: number;
  company_id: number;
  lifecycle_state: string;
  buying_enabled: boolean;
  risk_level: string;
  compliance_score: number;
  active_risk_flags: RiskFlag[];
  valid_from: string;
  valid_until: string | null;
  is_current: boolean;
  is_locked: boolean;
  created_at: string;
}

/**
 * Snapshot Comparison Result
 * Diff between investment-time and current platform state
 */
export interface SnapshotComparison {
  investment_id: number;
  investment_date: string;
  company_name: string;
  then: {
    snapshot_id: number;
    snapshot_date: string;
    lifecycle_state: string;
    buying_enabled: boolean;
    risk_level: string;
    compliance_score: number;
    risk_flags: RiskFlag[];
  };
  now: {
    snapshot_id: number;
    snapshot_date: string;
    lifecycle_state: string;
    buying_enabled: boolean;
    risk_level: string;
    compliance_score: number;
    risk_flags: RiskFlag[];
  };
  changes: {
    lifecycle_state_changed: boolean;
    buying_status_changed: boolean;
    risk_level_changed: boolean;
    compliance_score_delta: number;
    new_risk_flags: RiskFlag[];
    removed_risk_flags: RiskFlag[];
  };
}
