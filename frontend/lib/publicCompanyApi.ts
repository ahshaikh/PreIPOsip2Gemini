/**
 * EPIC 5 Story 5.1 - Public Frontend: API Functions
 *
 * PURPOSE:
 * - Fetch public companies from backend
 * - Apply frontend projection layer to sanitize responses
 * - Support dynamic filters (all, live, upcoming, by sector)
 * - No authentication required
 *
 * ARCHITECTURE:
 * Backend Response → Sanitizer → Safe Data → React Component
 *
 * DEFENSIVE PRINCIPLES:
 * - Never hardcode company data
 * - Whitelist allowed fields at API boundary
 * - Drop prohibited fields before returning to components
 * - Log backend breaches for future escalation
 */

import api from './api';
import {
  sanitizePublicCompany,
  sanitizePublicCompanyList,
  PublicSafeCompany,
} from './publicDataSanitizer';
import {
  filterPubliclyVisible,
  checkPublicVisibility,
} from './disclosureTierGate';

// ============================================================================
// RE-EXPORT TYPES
// ============================================================================

// Re-export PublicSafeCompany as the canonical public company type
export type { PublicSafeCompany };

/**
 * @deprecated Use PublicSafeCompany instead
 * Kept for backward compatibility during transition
 */
export interface PublicCompany extends PublicSafeCompany {}

/**
 * PublicCompanyDetail - Sanitized company detail for public display
 *
 * Note: This intentionally EXCLUDES investor-only fields like:
 * - platform_context
 * - tier_status
 * - disclosures
 * - deals
 * - financialReports
 */
export interface PublicCompanyDetail extends PublicSafeCompany {
  // Extended description for detail page
  description?: string;
}

export interface PublicCompanyListParams {
  filter?: 'all' | 'live' | 'upcoming';
  sector?: string;
  page?: number;
  per_page?: number;
}

export interface PublicCompanyListResult {
  companies: PublicSafeCompany[];
  total: number;
  sectors: string[];
}

// ============================================================================
// API FUNCTIONS
// ============================================================================

/**
 * Fetch list of public companies
 *
 * Backend endpoint: GET /public/companies
 *
 * SANITIZATION:
 * - Raw backend response is passed through sanitizePublicCompanyList
 * - Only whitelisted fields are returned
 * - Prohibited fields are dropped and logged
 * - Non-public tier companies are filtered out
 */
export async function fetchPublicCompanies(
  params: PublicCompanyListParams = {}
): Promise<PublicCompanyListResult> {
  try {
    const response = await api.get('/public/companies', { params });

    // Extract raw companies from response
    const rawCompanies = response.data.data?.companies || response.data.companies || [];

    // SANITIZATION LAYER: Whitelist allowed fields, drop prohibited
    const sanitizedCompanies = sanitizePublicCompanyList(rawCompanies);

    // TIER GATE: Filter to only publicly visible companies (tier_2_live+)
    // Projection guard: frontend refuses to render entities below public disclosure tier.
    // This does NOT assert backend correctness or enforcement.
    const publicCompanies = filterPubliclyVisible(sanitizedCompanies);

    return {
      companies: publicCompanies,
      total: response.data.data?.total || response.data.total || publicCompanies.length,
      sectors: response.data.data?.sectors || response.data.sectors || [],
    };
  } catch (error) {
    console.error('[PUBLIC API] Failed to fetch companies:', error);

    // Return empty data on error (graceful degradation)
    return {
      companies: [],
      total: 0,
      sectors: [],
    };
  }
}

/**
 * Fetch single company detail (public view)
 *
 * Backend endpoint: GET /public/companies/{slug}
 *
 * SANITIZATION:
 * - Raw backend response is passed through sanitizePublicCompany
 * - Only whitelisted fields are returned
 * - Prohibited fields (deals, financialReports, etc.) are dropped
 * - Returns null if company not publicly visible (tier < 2)
 */
export async function fetchPublicCompanyDetail(
  slug: string
): Promise<PublicCompanyDetail | null> {
  try {
    const response = await api.get(`/public/companies/${slug}`);

    // Extract raw company from response
    // Backend may return { company: {...} } or { data: {...} } or direct object
    const rawCompany =
      response.data.company ||
      response.data.data?.company ||
      response.data.data ||
      response.data;

    if (!rawCompany) {
      return null;
    }

    // SANITIZATION LAYER: Whitelist allowed fields, drop prohibited
    const sanitized = sanitizePublicCompany(rawCompany);

    if (!sanitized) {
      return null;
    }

    // TIER GATE: Check if company is publicly visible
    // If backend sent a non-public company, return null and log breach
    if (!checkPublicVisibility(rawCompany)) {
      // Non-public company detected - this is a backend invariant breach
      // The breach is logged inside checkPublicVisibility
      // Return null to trigger 404 in UI
      return null;
    }

    // Return sanitized company detail
    return {
      ...sanitized,
      // Include description if present (allowed field)
      description: rawCompany.description || sanitized.short_description,
    };
  } catch (error: any) {
    console.error('[PUBLIC API] Failed to fetch company detail:', error);

    // Return null if company not found or not publicly visible
    if (error?.response?.status === 404) {
      return null;
    }

    throw error;
  }
}

/**
 * Fetch available sectors for filtering
 *
 * Backend endpoint: GET /public/sectors
 *
 * Note: Sectors are simple strings, no sanitization needed
 */
export async function fetchPublicSectors(): Promise<string[]> {
  try {
    const response = await api.get('/public/sectors');

    return response.data.data?.sectors || response.data.sectors || [];
  } catch (error) {
    console.error('[PUBLIC API] Failed to fetch sectors:', error);

    return [];
  }
}

// ============================================================================
// UTILITY EXPORTS
// ============================================================================

/**
 * Re-export sanitization utilities for direct use in components
 * (if needed for additional defensive guards at render time)
 */
export { sanitizePublicCompany, sanitizePublicCompanyList } from './publicDataSanitizer';
export { isPubliclyVisibleTier, shouldShow404 } from './disclosureTierGate';
