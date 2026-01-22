/**
 * PHASE 5 - Subscriber/Investor Frontend: API Functions
 *
 * PURPOSE:
 * - Fetch investor-visible companies from backend
 * - Check buy eligibility using BuyEnablementGuardService
 * - Fetch risk acknowledgements required
 * - Submit investments with snapshot binding
 *
 * DEFENSIVE PRINCIPLES:
 * - All buy eligibility checked via backend guards
 * - No silent failures or hidden blockers
 * - Surface all platform warnings and restrictions
 * - Require explicit risk acknowledgements
 */

import api from './api';

export interface InvestorCompany {
  id: number;
  name: string;
  slug: string;
  logo_url?: string;
  sector?: string;
  short_description?: string;

  // Platform Context (governance state)
  lifecycle_state: string;
  buying_enabled: boolean;
  is_suspended: boolean;
  is_frozen: boolean;

  // Tier Status
  tier_2_approved: boolean;

  // Buy Eligibility (from BuyEnablementGuardService)
  buy_eligibility: {
    allowed: boolean;
    blockers: Array<{
      guard: string;
      severity: 'critical' | 'warning';
      message: string;
    }>;
  };

  // Platform Warnings
  has_material_changes: boolean;
  material_change_warnings?: string[];

  // Risk flags visible to investor
  risk_flags?: Array<{
    flag_type: string;
    severity: string;
    description: string;
  }>;
}

export interface InvestorCompanyDetail extends InvestorCompany {
  description?: string;

  // Approved disclosures (full investor view)
  disclosures: Array<{
    module_name: string;
    status: string;
    data: any;
    version_number: number;
    approved_at: string;
  }>;

  // Platform context snapshot
  platform_context: {
    lifecycle_state: string;
    buying_enabled: boolean;
    tier_status: {
      tier_1_approved: boolean;
      tier_2_approved: boolean;
      tier_3_approved: boolean;
    };
    restrictions: {
      is_suspended: boolean;
      is_frozen: boolean;
      is_under_investigation: boolean;
      buying_pause_reason?: string;
    };
    risk_assessment: {
      platform_risk_score: number;
      risk_level: string;
      risk_flags: any[];
    };
  };

  // Required acknowledgements
  required_acknowledgements: Array<{
    type: string;
    text: string;
    required: boolean;
  }>;
}

export interface WalletBalance {
  available_balance: number;
  allocated_balance: number;
  pending_balance: number;
  total_balance: number;
  currency: string;
}

export interface AllocationIntent {
  company_id: number;
  amount: number;
  acknowledged_risks: string[]; // Acknowledgement types granted
}

export interface InvestmentReview {
  allocations: AllocationIntent[];
  total_amount: number;
  remaining_balance: number;
  warnings: string[];
  acknowledgement_status: {
    [company_id: number]: {
      all_acknowledged: boolean;
      missing: string[];
    };
  };
}

/**
 * Fetch investor-visible companies (for /deals page)
 *
 * Backend endpoint: GET /investor/companies
 * Requires authentication
 */
export async function fetchInvestorCompanies(): Promise<{
  companies: InvestorCompany[];
  wallet: WalletBalance;
}> {
  const response = await api.get('/investor/companies');

  return {
    companies: response.data.data?.companies || [],
    wallet: response.data.data?.wallet || {
      available_balance: 0,
      allocated_balance: 0,
      pending_balance: 0,
      total_balance: 0,
      currency: 'INR',
    },
  };
}

/**
 * Fetch single company detail (investor view)
 *
 * Backend endpoint: GET /investor/companies/{id}
 * Uses PublicCompanyPageService.getPublicCompanyPage() with investor context
 */
export async function fetchInvestorCompanyDetail(
  companyId: number
): Promise<InvestorCompanyDetail> {
  const response = await api.get(`/investor/companies/${companyId}`);

  return response.data.data;
}

/**
 * Fetch comprehensive company detail for investment decision
 *
 * Backend endpoint: GET /investor/companies/{id}/comprehensive
 * Returns ALL 15 categories of information for informed investment decisions
 */
export async function fetchInvestorCompanyDetailComprehensive(
  companyId: number
): Promise<any> {
  const response = await api.get(`/investor/companies/${companyId}/comprehensive`);

  return response.data.data;
}

/**
 * Check buy eligibility for company
 *
 * Backend endpoint: POST /investor/companies/{id}/check-eligibility
 * Calls BuyEnablementGuardService.canInvest()
 */
export async function checkBuyEligibility(companyId: number, acknowledgements: string[] = []): Promise<{
  allowed: boolean;
  blockers: Array<{
    guard: string;
    severity: 'critical' | 'warning';
    message: string;
  }>;
}> {
  const response = await api.post(`/investor/companies/${companyId}/check-eligibility`, {
    acknowledgements,
  });

  return response.data.data;
}

/**
 * Get required risk acknowledgements for company
 *
 * Backend endpoint: GET /investor/companies/{id}/required-acknowledgements
 * Uses RiskAcknowledgementService.getRequiredAcknowledgements()
 */
export async function getRequiredAcknowledgements(companyId: number): Promise<
  Array<{
    type: string;
    text: string;
    required: boolean;
  }>
> {
  const response = await api.get(`/investor/companies/${companyId}/required-acknowledgements`);

  return response.data.data?.acknowledgements || [];
}

/**
 * Record risk acknowledgement
 *
 * Backend endpoint: POST /investor/acknowledgements
 * Calls RiskAcknowledgementService.recordAcknowledgement()
 */
export async function recordAcknowledgement(
  companyId: number,
  acknowledgementType: string,
  context?: any
): Promise<{ acknowledgement_id: number }> {
  const response = await api.post('/investor/acknowledgements', {
    company_id: companyId,
    acknowledgement_type: acknowledgementType,
    context,
  });

  return response.data.data;
}

/**
 * Preview investment (validate allocations before submission)
 *
 * Backend endpoint: POST /investor/investments/preview
 * Validates allocations, checks guards, returns warnings
 */
export async function previewInvestment(allocations: AllocationIntent[]): Promise<{
  valid: boolean;
  total_amount: number;
  warnings: string[];
  blockers: Array<{
    company_id: number;
    company_name: string;
    blocker: string;
    severity: string;
    message: string;
  }>;
  acknowledgement_status: {
    [company_id: number]: {
      all_acknowledged: boolean;
      missing: string[];
    };
  };
}> {
  const response = await api.post('/investor/investments/preview', {
    allocations,
  });

  return response.data.data;
}

/**
 * Submit investment (final confirmation with snapshot binding)
 *
 * Backend endpoint: POST /investor/investments
 * Calls:
 * - BuyEnablementGuardService.canInvest() (validation)
 * - InvestmentSnapshotService.captureAtPurchase() (snapshot binding)
 * - RiskAcknowledgementService (records acknowledgements)
 *
 * DEFENSIVE: Backend validates everything, creates immutable snapshots
 *
 * GAP 3 FIX: Added idempotency key to prevent duplicate submissions
 */
export async function submitInvestment(
  allocations: AllocationIntent[],
  idempotencyKey?: string
): Promise<{
  success: boolean;
  investment_ids: number[];
  snapshot_ids: number[];
  message: string;
}> {
  const response = await api.post('/investor/investments', {
    allocations,
    idempotency_key: idempotencyKey,
  });

  return response.data.data;
}

/**
 * Get investor's wallet balance
 *
 * Backend endpoint: GET /investor/wallet
 */
export async function getWalletBalance(): Promise<WalletBalance> {
  const response = await api.get('/investor/wallet');

  return response.data.wallet || {
    available_balance: 0,
    allocated_balance: 0,
    pending_balance: 0,
    total_balance: 0,
    currency: 'INR',
  };
}
