/**
 * EPIC 6 - Audit & Regulator Views
 *
 * READ-ONLY API client for audit endpoints.
 *
 * ABSOLUTE CONSTRAINTS:
 * - NO write operations (POST/PUT/PATCH/DELETE)
 * - NO edit, approve, reject, or modify actions
 * - Only GET requests for data retrieval
 * - All data is immutable from this client's perspective
 *
 * INVARIANTS:
 * - Responses are rendered exactly as returned
 * - Timestamps are in ISO 8601 format
 * - Snapshot hashes are verbatim (no transformation)
 * - Actor identities are traceable
 */

import api from "./api";

// =============================================================================
// TYPE DEFINITIONS
// =============================================================================

/**
 * Base pagination response wrapper
 */
export interface PaginatedResponse<T> {
  data: T[];
  meta: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
  };
}

/**
 * Authority types for audit decisions
 */
export type AuditAuthorityType = "issuer" | "platform" | "investor" | "system";

/**
 * Actor types for audit trails
 */
export type AuditActorType = "admin" | "system" | "investor" | "issuer";

/**
 * Investment snapshot - captured at purchase time
 */
export interface InvestmentSnapshot {
  id: number;
  investment_id: number;
  snapshot_hash: string;
  captured_at: string; // ISO 8601
  is_immutable: true; // Always true

  // Company state at purchase
  company_state: {
    id: number;
    name: string;
    slug: string;
    lifecycle_state: string;
    disclosure_tier: number;
    risk_flags: Array<{
      flag_type: string;
      severity: string;
      description: string;
    }>;
    platform_context: {
      is_suspended: boolean;
      is_frozen: boolean;
      buying_enabled: boolean;
    };
  };

  // Disclosures shown to investor at purchase
  disclosures_shown: Array<{
    module_name: string;
    version_number: number;
    content_hash: string;
    status: "approved";
  }>;

  // Acknowledgements captured
  acknowledgements: Array<{
    acknowledgement_type: string;
    acknowledged_at: string;
    investor_id: number;
  }>;

  // Financial terms at purchase
  financial_terms: {
    price_per_unit: string;
    quantity: number;
    total_amount: string;
    currency: string;
  };
}

/**
 * Investment reconstruction - full audit view
 */
export interface InvestmentReconstruction {
  investment: {
    id: number;
    investor_id: number;
    company_id: number;
    amount: string;
    quantity: number;
    status: string;
    created_at: string;
    completed_at?: string;
  };

  snapshot: InvestmentSnapshot;

  // Journey validation that was performed
  journey_validation: {
    passed: boolean;
    checks_performed: Array<{
      check_name: string;
      result: "passed" | "failed";
      reason?: string;
    }>;
    validated_at: string;
  };

  // Wallet transaction
  wallet_transaction?: {
    id: number;
    type: string;
    amount: string;
    balance_before: string;
    balance_after: string;
    created_at: string;
  };

  // Timeline of events
  timeline: AuditTimelineEvent[];
}

/**
 * Timeline event for audit trails
 */
export interface AuditTimelineEvent {
  id: number;
  event_type: string;
  event_description: string;
  timestamp: string; // ISO 8601
  authority: AuditAuthorityType;
  actor: {
    type: AuditActorType;
    id?: number;
    name?: string;
  };
  reason?: string;
  metadata?: Record<string, unknown>;
  is_immutable: boolean;
}

/**
 * Investor audit summary
 */
export interface InvestorAuditSummary {
  investor_id: number;
  anonymized_id: string;

  // Investment summary
  investments: {
    total_count: number;
    total_amount: string;
    companies_invested: number;
  };

  // KYC status (anonymized)
  kyc_status: {
    is_verified: boolean;
    verified_at?: string;
  };

  // Wallet summary
  wallet: {
    total_deposited: string;
    total_withdrawn: string;
    current_balance: string;
  };

  // Timeline of significant events
  timeline: AuditTimelineEvent[];
}

/**
 * Company governance audit summary
 */
export interface CompanyGovernanceAudit {
  company: {
    id: number;
    name: string;
    slug: string;
    created_at: string;
  };

  // Current governance state
  current_state: {
    lifecycle_state: string;
    disclosure_tier: number;
    is_suspended: boolean;
    is_frozen: boolean;
    is_under_investigation: boolean;
    buying_enabled: boolean;
  };

  // Disclosure history
  disclosure_history: Array<{
    module_name: string;
    version_number: number;
    status: string;
    submitted_at: string;
    reviewed_at?: string;
    reviewed_by?: string;
    reason?: string;
  }>;

  // Tier transitions
  tier_transitions: Array<{
    from_tier: number;
    to_tier: number;
    transitioned_at: string;
    authority: AuditAuthorityType;
    reason: string;
  }>;

  // Risk flag history
  risk_flag_history: Array<{
    flag_type: string;
    severity: string;
    status: string;
    created_at: string;
    resolved_at?: string;
    authority: AuditAuthorityType;
    reason: string;
  }>;

  // Platform actions history
  platform_actions: AuditTimelineEvent[];

  // Investor impact summary
  investor_impact: {
    total_investors: number;
    total_investments: number;
    total_invested_amount: string;
  };
}

/**
 * Admin action audit detail
 */
export interface AdminActionAudit {
  id: number;
  action_type: string;
  action_description: string;
  performed_at: string; // ISO 8601

  // Who performed the action
  actor: {
    admin_id: number;
    admin_name: string;
    admin_email: string;
  };

  // What was affected
  target: {
    type: "company" | "investor" | "investment" | "disclosure" | "system";
    id: number;
    name?: string;
  };

  // Decision details
  decision: {
    authority: AuditAuthorityType;
    reason: string;
    is_mandatory_reason: boolean;
  };

  // Before/after state comparison
  state_change?: {
    before: Record<string, unknown>;
    after: Record<string, unknown>;
    changed_fields: string[];
  };

  // Impact assessment (captured at action time)
  impact_captured?: {
    affected_investors: number;
    affected_investments: number;
    blocked_actions?: string[];
  };

  // Immutability
  is_immutable: true;
  snapshot_hash?: string;
}

/**
 * Global audit dashboard stats
 */
export interface AuditDashboardStats {
  // Investment activity
  investments: {
    total_count: number;
    total_amount: string;
    today_count: number;
    today_amount: string;
  };

  // Governance activity
  governance: {
    companies_active: number;
    companies_suspended: number;
    companies_frozen: number;
    pending_disclosures: number;
  };

  // Admin activity
  admin_actions: {
    today_count: number;
    week_count: number;
    month_count: number;
  };

  // Snapshots
  snapshots: {
    total_count: number;
    oldest_snapshot_date: string;
    newest_snapshot_date: string;
  };
}

/**
 * Audit list item (for navigation lists)
 */
export interface AuditListItem {
  id: number;
  type: string;
  title: string;
  subtitle?: string;
  timestamp: string;
  status?: string;
}

// =============================================================================
// API FUNCTIONS - READ ONLY
// =============================================================================

/**
 * Fetch global audit dashboard statistics
 */
export async function fetchAuditDashboardStats(): Promise<AuditDashboardStats> {
  const response = await api.get<{ data: AuditDashboardStats }>("/admin/audit-logs/stats");
  return response.data.data;
}

/**
 * Fetch investment reconstruction (ANCHOR VIEW)
 * This is the most critical audit view - reconstructs exactly what
 * was shown to investor at purchase time.
 */
export async function fetchInvestmentReconstruction(
  investmentId: number
): Promise<InvestmentReconstruction> {
  const response = await api.get<{ data: InvestmentReconstruction }>(
    `/audit/investments/${investmentId}`
  );
  return response.data.data;
}

/**
 * Fetch investment snapshot by ID
 */
export async function fetchInvestmentSnapshot(
  snapshotId: number
): Promise<InvestmentSnapshot> {
  const response = await api.get<{ data: InvestmentSnapshot }>(
    `/audit/snapshots/${snapshotId}`
  );
  return response.data.data;
}

/**
 * Fetch investor audit timeline
 */
export async function fetchInvestorAuditTimeline(
  investorId: number
): Promise<InvestorAuditSummary> {
  const response = await api.get<{ data: InvestorAuditSummary }>(
    `/audit/investors/${investorId}`
  );
  return response.data.data;
}

/**
 * Fetch company governance audit
 */
export async function fetchCompanyGovernanceAudit(
  companyId: number
): Promise<CompanyGovernanceAudit> {
  const response = await api.get<{ data: CompanyGovernanceAudit }>(
    `/audit/companies/${companyId}`
  );
  return response.data.data;
}

/**
 * Fetch admin action detail
 */
export async function fetchAdminActionDetail(
  actionId: number
): Promise<AdminActionAudit> {
  const response = await api.get<{ data: AdminActionAudit }>(
    `/audit/actions/${actionId}`
  );
  return response.data.data;
}

// =============================================================================
// LIST ENDPOINTS (for navigation)
// =============================================================================

export interface AuditListParams {
  page?: number;
  per_page?: number;
  sort_by?: string;
  sort_order?: "asc" | "desc";
  filter_date_from?: string;
  filter_date_to?: string;
}

/**
 * List investments for audit
 */
export async function listAuditInvestments(
  params?: AuditListParams
): Promise<PaginatedResponse<AuditListItem>> {
  const response = await api.get<PaginatedResponse<AuditListItem>>(
    "/audit/investments",
    { params }
  );
  return response.data;
}

/**
 * List investors for audit
 */
export async function listAuditInvestors(
  params?: AuditListParams
): Promise<PaginatedResponse<AuditListItem>> {
  const response = await api.get<PaginatedResponse<AuditListItem>>(
    "/audit/investors",
    { params }
  );
  return response.data;
}

/**
 * List companies for audit
 */
export async function listAuditCompanies(
  params?: AuditListParams
): Promise<PaginatedResponse<AuditListItem>> {
  const response = await api.get<PaginatedResponse<AuditListItem>>(
    "/audit/companies",
    { params }
  );
  return response.data;
}

/**
 * List admin actions for audit
 */
export async function listAuditAdminActions(
  params?: AuditListParams & { admin_id?: number; action_type?: string }
): Promise<PaginatedResponse<AuditListItem>> {
  const response = await api.get<PaginatedResponse<AuditListItem>>(
    "/audit/actions",
    { params }
  );
  return response.data;
}

/**
 * Search audit records globally
 */
export async function searchAuditRecords(
  query: string,
  params?: AuditListParams
): Promise<PaginatedResponse<AuditListItem>> {
  const response = await api.get<PaginatedResponse<AuditListItem>>(
    "/audit/search",
    { params: { q: query, ...params } }
  );
  return response.data;
}

// =============================================================================
// EXPORT ENDPOINTS (Read-only data export)
// =============================================================================

/**
 * Export investment reconstruction as JSON
 * Returns raw data for regulatory submission
 */
export async function exportInvestmentReconstruction(
  investmentId: number
): Promise<Blob> {
  const response = await api.get(`/audit/investments/${investmentId}/export`, {
    responseType: "blob",
  });
  return response.data;
}

/**
 * Export company governance audit as JSON
 */
export async function exportCompanyGovernanceAudit(
  companyId: number
): Promise<Blob> {
  const response = await api.get(`/audit/companies/${companyId}/export`, {
    responseType: "blob",
  });
  return response.data;
}
