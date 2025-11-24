// V-PHASE4-1730-100 | V-TYPES-ENHANCED (Comprehensive type definitions)

// ============================================
// COMMON TYPES
// ============================================

export type Status = 'active' | 'inactive' | 'pending' | 'suspended';
export type PaymentStatus = 'pending' | 'paid' | 'failed' | 'refunded' | 'pending_approval';
export type KycStatus = 'pending' | 'submitted' | 'verified' | 'rejected';
export type SubscriptionStatus = 'pending' | 'active' | 'paused' | 'cancelled' | 'completed';
export type WithdrawalStatus = 'pending' | 'approved' | 'processed' | 'rejected';
export type TicketStatus = 'open' | 'in_progress' | 'resolved' | 'closed';
export type TicketPriority = 'low' | 'medium' | 'high' | 'urgent';
export type BonusType = 'progressive' | 'milestone' | 'consistency' | 'referral' | 'celebration' | 'lucky_draw' | 'profit_share';

export interface Timestamps {
  created_at: string;
  updated_at: string;
}

export interface PaginatedResponse<T> {
  data: T[];
  meta: {
    current_page: number;
    from: number;
    last_page: number;
    per_page: number;
    to: number;
    total: number;
  };
  links: {
    first: string;
    last: string;
    prev: string | null;
    next: string | null;
  };
}

export interface ApiResponse<T> {
  data: T;
  message?: string;
}

export interface ApiError {
  message: string;
  errors?: Record<string, string[]>;
}

// ============================================
// USER & AUTHENTICATION
// ============================================

export interface User {
  id: number;
  username: string;
  email: string;
  mobile: string;
  status: Status;
  email_verified_at: string | null;
  mobile_verified_at: string | null;
  two_factor_confirmed_at: string | null;
  profile: UserProfile;
  kyc: UserKyc | null;
  wallet: Wallet | null;
  roles?: Role[];
}

export interface UserProfile {
  id: number;
  user_id: number;
  first_name: string | null;
  last_name: string | null;
  avatar_url: string | null;
  date_of_birth: string | null;
  gender: 'male' | 'female' | 'other' | null;
  address_line_1: string | null;
  address_line_2: string | null;
  city: string | null;
  state: string | null;
  pincode: string | null;
  country: string;
}

export interface UserKyc {
  id: number;
  user_id: number;
  status: KycStatus;
  pan_number: string | null;
  aadhaar_number: string | null;
  rejection_reason: string | null;
  verified_at: string | null;
  documents?: KycDocument[];
}

export interface KycDocument {
  id: number;
  kyc_id: number;
  document_type: 'pan' | 'aadhaar_front' | 'aadhaar_back' | 'selfie' | 'address_proof';
  file_path: string;
  status: 'pending' | 'verified' | 'rejected';
  rejection_reason: string | null;
}

export interface Role {
  id: number;
  name: string;
  guard_name: string;
}

// ============================================
// PLANS & PRODUCTS
// ============================================

export interface Plan {
  id: number;
  name: string;
  slug: string;
  monthly_amount: number;
  duration_months: number;
  description: string;
  is_active: boolean;
  is_featured: boolean;
  bonus_multiplier: number;
  display_order: number;
  features: PlanFeature[];
  configs: PlanConfig[];
}

export interface PlanFeature {
  id: number;
  plan_id: number;
  feature_text: string;
  display_order: number;
}

export interface PlanConfig {
  id: number;
  plan_id: number;
  config_key: string;
  value: Record<string, unknown>;
}

export interface Product {
  id: number;
  name: string;
  slug: string;
  sector: string;
  description: string;
  logo_url: string | null;
  total_shares_available: number;
  price_per_share: number;
  min_investment: number;
  max_investment: number;
  is_active: boolean;
  status: 'upcoming' | 'open' | 'closed';
  opens_at: string | null;
  closes_at: string | null;
  highlights?: ProductHighlight[];
  founders?: ProductFounder[];
  funding_rounds?: ProductFundingRound[];
  key_metrics?: ProductKeyMetric[];
}

export interface ProductHighlight {
  id: number;
  product_id: number;
  content: string;
  display_order: number;
}

export interface ProductFounder {
  id: number;
  product_id: number;
  name: string;
  title: string;
  photo_url: string | null;
  linkedin_url: string | null;
  bio: string | null;
  display_order: number;
}

export interface ProductFundingRound {
  id: number;
  product_id: number;
  round_name: string;
  date: string;
  amount: number;
  valuation: number | null;
  investors: string | null;
}

export interface ProductKeyMetric {
  id: number;
  product_id: number;
  metric_name: string;
  value: string;
  unit: string | null;
}

// ============================================
// SUBSCRIPTIONS & PAYMENTS
// ============================================

export interface Subscription {
  id: number;
  user_id: number;
  plan_id: number;
  status: SubscriptionStatus;
  monthly_amount: number;
  bonus_multiplier: number;
  duration_months: number;
  is_auto_debit: boolean;
  starts_at: string;
  ends_at: string | null;
  paused_at: string | null;
  next_payment_due: string | null;
  consecutive_payments_count: number;
  plan?: Plan;
  payments?: Payment[];
}

export interface Payment {
  id: number;
  user_id: number;
  subscription_id: number;
  amount: number;
  status: PaymentStatus;
  gateway: 'razorpay' | 'manual_transfer' | null;
  gateway_order_id: string | null;
  gateway_payment_id: string | null;
  payment_proof_path: string | null;
  is_on_time: boolean;
  due_date: string;
  paid_at: string | null;
  subscription?: Subscription;
}

// ============================================
// WALLET & TRANSACTIONS
// ============================================

export interface Wallet {
  id: number;
  user_id: number;
  balance: number;
  locked_balance: number;
  total_deposited?: number;
  total_withdrawn?: number;
}

export interface Transaction {
  id: number;
  wallet_id: number;
  user_id: number;
  type: 'deposit' | 'withdrawal' | 'bonus' | 'investment' | 'refund' | 'admin_adjustment';
  amount: number;
  balance_before: number;
  balance_after: number;
  description: string;
  status: 'pending' | 'completed' | 'failed';
  reference_type: string | null;
  reference_id: number | null;
}

export interface Withdrawal {
  id: number;
  user_id: number;
  wallet_id: number;
  amount: number;
  status: WithdrawalStatus;
  bank_account_number: string;
  bank_ifsc: string;
  bank_name: string;
  account_holder_name: string;
  utr_number: string | null;
  rejection_reason: string | null;
  requested_at: string;
  approved_at: string | null;
  processed_at: string | null;
}

// ============================================
// BONUSES & REWARDS
// ============================================

export interface BonusTransaction {
  id: number;
  user_id: number;
  subscription_id: number;
  payment_id: number | null;
  type: BonusType;
  amount: number;
  multiplier_applied: number;
  base_amount: number;
  description: string;
}

export interface Referral {
  id: number;
  referrer_id: number;
  referred_id: number;
  referral_campaign_id: number | null;
  status: 'pending' | 'successful' | 'expired';
  bonus_amount: number;
  bonus_credited: boolean;
  credited_at: string | null;
  referred_user?: User;
}

export interface ReferralCampaign {
  id: number;
  name: string;
  slug: string;
  description: string;
  bonus_amount: number;
  multiplier: number;
  starts_at: string;
  ends_at: string;
  is_active: boolean;
  max_referrals: number | null;
}

export interface LuckyDraw {
  id: number;
  name: string;
  draw_date: string;
  prize_structure: Record<string, number>;
  status: 'open' | 'closed' | 'drawn';
}

export interface LuckyDrawEntry {
  id: number;
  user_id: number;
  lucky_draw_id: number;
  payment_id: number;
  base_entries: number;
  bonus_entries: number;
  is_winner: boolean;
  prize_rank: number | null;
  prize_amount: number | null;
}

// ============================================
// INVESTMENTS
// ============================================

export interface UserInvestment {
  id: number;
  user_id: number;
  product_id: number;
  bulk_purchase_id: number | null;
  shares: number;
  price_per_share: number;
  total_amount: number;
  source: 'sip' | 'bulk';
  status: 'pending' | 'active' | 'exited';
  allocated_at: string | null;
  exited_at: string | null;
  product?: Product;
}

// ============================================
// SUPPORT
// ============================================

export interface SupportTicket {
  id: number;
  user_id: number;
  subject: string;
  description: string;
  category: string;
  priority: TicketPriority;
  status: TicketStatus;
  assigned_to: number | null;
  resolved_at: string | null;
  closed_at: string | null;
  messages?: SupportMessage[];
}

export interface SupportMessage {
  id: number;
  ticket_id: number;
  user_id: number;
  message: string;
  is_staff_reply: boolean;
  attachments: string[];
}

// ============================================
// CMS & CONTENT
// ============================================

export interface Banner {
  id: number;
  title: string;
  type: 'top_bar' | 'popup';
  content: string;
  link_url: string | null;
  is_active: boolean;
  trigger_type: 'load' | 'time_delay' | 'scroll' | 'exit_intent';
  trigger_value: number;
  frequency: 'always' | 'once_per_session' | 'once_daily' | 'once';
}

export interface Page {
  id: number;
  title: string;
  slug: string;
  content: string;
  meta_title: string | null;
  meta_description: string | null;
  is_published: boolean;
}

export interface Faq {
  id: number;
  question: string;
  answer: string;
  category: string;
  display_order: number;
  is_published: boolean;
}

export interface BlogPost {
  id: number;
  title: string;
  slug: string;
  excerpt: string | null;
  content: string;
  featured_image: string | null;
  author_id: number;
  is_published: boolean;
  published_at: string | null;
  author?: User;
}

// ============================================
// NOTIFICATIONS
// ============================================

export interface Notification {
  id: string;
  type: string;
  data: Record<string, unknown>;
  read_at: string | null;
  created_at: string;
}

// ============================================
// SETTINGS & CONFIGURATION
// ============================================

export interface Setting {
  key: string;
  value: string | number | boolean | Record<string, unknown>;
  group: string;
}

export interface FeatureFlag {
  id: number;
  name: string;
  key: string;
  description: string;
  is_enabled: boolean;
  rollout_percentage: number;
}

// ============================================
// DASHBOARD & ANALYTICS
// ============================================

export interface DashboardStats {
  total_invested: number;
  current_value: number;
  total_bonuses: number;
  wallet_balance: number;
  active_subscriptions: number;
  payments_this_month: number;
  referral_count: number;
  investment_count: number;
}

export interface AdminDashboardStats {
  total_users: number;
  active_subscriptions: number;
  total_revenue: number;
  pending_kyc: number;
  pending_withdrawals: number;
  open_tickets: number;
  monthly_revenue: number[];
  user_growth: number[];
}

// ============================================
// FORM TYPES
// ============================================

export interface LoginFormData {
  email: string;
  password: string;
  remember?: boolean;
}

export interface RegisterFormData {
  username: string;
  email: string;
  mobile: string;
  password: string;
  password_confirmation: string;
  referral_code?: string;
}

export interface ProfileUpdateData {
  first_name: string;
  last_name: string;
  date_of_birth?: string;
  gender?: 'male' | 'female' | 'other';
  address_line_1?: string;
  address_line_2?: string;
  city?: string;
  state?: string;
  pincode?: string;
}

export interface KycSubmissionData {
  pan_number: string;
  aadhaar_number?: string;
  pan_document: File;
  aadhaar_front?: File;
  aadhaar_back?: File;
  selfie?: File;
}

export interface WithdrawalRequestData {
  amount: number;
  bank_account_number: string;
  bank_ifsc: string;
  bank_name: string;
  account_holder_name: string;
}

export interface TicketCreateData {
  subject: string;
  description: string;
  category: string;
  priority?: TicketPriority;
  attachments?: File[];
}
