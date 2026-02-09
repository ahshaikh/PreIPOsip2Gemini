/**
 * EPIC 5 Story 5.1 - Public Data Sanitizer
 *
 * PURPOSE:
 * Frontend projection layer that whitelists public-safe fields and drops
 * prohibited data at render time. Backend responses are treated as authoritative
 * but potentially containing investor-only data that must not be shown publicly.
 *
 * ARCHITECTURE:
 * Backend Response → Sanitizer → Safe Data → React Component
 *                        ↓
 *              Log prohibited fields (breach detection)
 *
 * DEFENSIVE PRINCIPLES:
 * - Whitelist approach: only explicitly allowed fields pass through
 * - Drop prohibited data silently (never render)
 * - Log detected breaches for future backend escalation
 * - Never modify backend - this is a frontend-only projection
 */

import { logBackendBreach } from './backendBreachLogger';
import { transformStorageUrl } from './storageUrlHelper';

// ============================================================================
// PUBLIC-SAFE COMPANY INTERFACE
// ============================================================================

/**
 * PublicSafeCompany - The sanitized shape for public (unauthenticated) display
 *
 * These fields are explicitly allowed per Story 5.1 Acceptance Criteria A2:
 * - Company identity (name, sector, description)
 * - Platform-approved content only
 * - NO financial data, NO deals, NO investor signals
 */
export interface PublicSafeCompany {
  id: number;
  name: string;
  slug: string;
  logo_url?: string;
  sector?: string;
  short_description?: string;
  description?: string;
  headquarters?: string;
  founded_year?: number;
  website_url?: string;
  // disclosure_tier is kept for frontend gate check only, not for display
  disclosure_tier?: string;
}

// ============================================================================
// FIELD WHITELISTS AND BLACKLISTS
// ============================================================================

/**
 * WHITELIST: Fields explicitly allowed for public display
 * Everything else is dropped.
 */
const ALLOWED_PUBLIC_FIELDS: ReadonlySet<string> = new Set([
  'id',
  'name',
  'slug',
  'logo_url',
  'logo',           // Alternative field name, mapped to logo_url
  'sector',
  'category',       // Alternative for sector
  'short_description',
  'description',
  'headquarters',
  'founded_year',
  'website_url',
  'website',        // Alternative field name, mapped to website_url
  'disclosure_tier', // For gate check only, not display
]);

/**
 * PROHIBITED FIELDS: Fields that MUST NEVER appear in public view
 * Presence of these in raw data indicates a backend invariant breach.
 *
 * Per Story 5.1 "Must NEVER Display":
 * - Prices, valuations, funding
 * - Risk flags, disclosure completeness
 * - Buy eligibility or CTA signals
 */
const PROHIBITED_FIELDS: ReadonlySet<string> = new Set([
  // Financial data
  'latest_valuation',
  'valuation',
  'total_funding',
  'funding_stage',
  'funding_rounds',
  'fundingRounds',
  'financialReports',
  'financial_reports',
  'revenue',
  'profit',
  'loss',
  'price',
  'share_price',
  'price_per_share',

  // Investment signals
  'deals',
  'active_deals',
  'buy_eligibility',
  'buying_enabled',
  'is_investable',
  'allocation',
  'available_shares',
  'min_investment',
  'max_investment',

  // Risk and compliance (investor-only)
  'risk_flags',
  'platform_risk_score',
  'risk_level',
  'compliance_score',
  'material_changes',
  'has_material_changes',

  // Investor metrics
  'key_metrics',
  'investors',
  'investor_count',
  'total_invested',

  // Platform context (investor-only)
  'platform_context',
  'tier_status',
  'tier_1_approved',
  'tier_2_approved',
  'tier_3_approved',
  'tier_1_approved_at',
  'tier_2_approved_at',
  'tier_3_approved_at',

  // Disclosures detail (investor-only)
  'disclosures',
  'disclosure_data',
  'disclosure_versions',

  // Other investor-oriented data
  'updates',
  'documents',
  'team_members',
  'teamMembers',
]);

// ============================================================================
// SANITIZATION FUNCTIONS
// ============================================================================

/**
 * Sanitize a single company object for public display
 *
 * @param raw - Raw company data from backend (may contain prohibited fields)
 * @returns PublicSafeCompany with only whitelisted fields
 */
export function sanitizePublicCompany(raw: any): PublicSafeCompany | null {
  if (!raw || typeof raw !== 'object') {
    return null;
  }

  // Detect and log any prohibited fields present (backend breach)
  const breaches = detectBackendBreaches(raw);
  if (breaches.length > 0) {
    logBackendBreach('sanitizePublicCompany', breaches, {
      companyId: raw.id,
      companySlug: raw.slug,
    });
  }

  // Extract only whitelisted fields
  const sanitized: PublicSafeCompany = {
    id: raw.id,
    name: raw.name || '',
    slug: raw.slug || '',
  };

  // Optional fields - only include if present
  // Transform storage URLs to use Next.js proxy
  if (raw.logo_url) sanitized.logo_url = transformStorageUrl(raw.logo_url);
  else if (raw.logo) sanitized.logo_url = transformStorageUrl(raw.logo);

  if (raw.sector) {
    // Sector might be a string or an object with name
    sanitized.sector = typeof raw.sector === 'string' ? raw.sector : raw.sector?.name;
  } else if (raw.category) {
    sanitized.sector = typeof raw.category === 'string' ? raw.category : raw.category?.name;
  }

  if (raw.short_description) sanitized.short_description = raw.short_description;
  if (raw.description) sanitized.description = raw.description;
  if (raw.headquarters) sanitized.headquarters = raw.headquarters;
  if (raw.founded_year) sanitized.founded_year = raw.founded_year;

  if (raw.website_url) sanitized.website_url = raw.website_url;
  else if (raw.website) sanitized.website_url = raw.website;

  // Keep disclosure_tier for frontend gate check (not for display)
  if (raw.disclosure_tier) sanitized.disclosure_tier = raw.disclosure_tier;

  return sanitized;
}

/**
 * Sanitize a list of companies for public display
 *
 * @param rawList - Array of raw company data from backend
 * @returns Array of PublicSafeCompany objects
 */
export function sanitizePublicCompanyList(rawList: any[]): PublicSafeCompany[] {
  if (!Array.isArray(rawList)) {
    logBackendBreach('sanitizePublicCompanyList', ['expected_array_got_' + typeof rawList], {});
    return [];
  }

  return rawList
    .map(raw => sanitizePublicCompany(raw))
    .filter((company): company is PublicSafeCompany => company !== null);
}

/**
 * Detect prohibited fields in raw data (backend invariant breach detection)
 *
 * @param raw - Raw data object from backend
 * @returns Array of prohibited field names found in the data
 */
export function detectBackendBreaches(raw: any): string[] {
  if (!raw || typeof raw !== 'object') {
    return [];
  }

  const breaches: string[] = [];

  // Check top-level fields
  for (const key of Object.keys(raw)) {
    if (PROHIBITED_FIELDS.has(key)) {
      breaches.push(key);
    }
  }

  // Check nested objects that shouldn't be present
  if (raw.company && typeof raw.company === 'object') {
    for (const key of Object.keys(raw.company)) {
      if (PROHIBITED_FIELDS.has(key)) {
        breaches.push(`company.${key}`);
      }
    }
  }

  return breaches;
}

/**
 * Check if raw data contains any prohibited fields
 *
 * @param raw - Raw data object from backend
 * @returns true if prohibited fields are present
 */
export function hasProhibitedFields(raw: any): boolean {
  return detectBackendBreaches(raw).length > 0;
}

// ============================================================================
// RENDER GUARDS
// ============================================================================

/**
 * Safe getter for company name - never returns prohibited data
 */
export function safeGetName(company: any): string {
  return company?.name || '';
}

/**
 * Safe getter for company description - sanitized
 */
export function safeGetDescription(company: any): string | undefined {
  return company?.description || company?.short_description;
}

/**
 * Safe getter for sector - handles both string and object forms
 */
export function safeGetSector(company: any): string | undefined {
  if (!company?.sector) return undefined;
  return typeof company.sector === 'string' ? company.sector : company.sector?.name;
}

/**
 * Type guard to check if an object is a valid PublicSafeCompany
 */
export function isPublicSafeCompany(obj: any): obj is PublicSafeCompany {
  return (
    obj &&
    typeof obj === 'object' &&
    typeof obj.id === 'number' &&
    typeof obj.name === 'string' &&
    typeof obj.slug === 'string'
  );
}
