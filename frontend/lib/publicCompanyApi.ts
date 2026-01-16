/**
 * PHASE 5 - Public Frontend: API Functions
 *
 * PURPOSE:
 * - Fetch public companies from backend PublicCompanyPageService
 * - Support dynamic filters (all, live, upcoming, by sector)
 * - No authentication required
 *
 * DEFENSIVE PRINCIPLES:
 * - Never hardcode company data
 * - All data comes from platform-governed backend
 * - Respects platform visibility controls
 */

import api from './api';

export interface PublicCompany {
  id: number;
  name: string;
  slug: string;
  logo_url?: string;
  sector?: string;
  category?: string;
  short_description?: string;
  headquarters?: string;
  founded_year?: number;
  website_url?: string;

  // Platform state (read-only, informational)
  is_visible_public: boolean;
  lifecycle_state?: string;

  // Tier status (informational only, no financial data)
  tier_1_approved: boolean;
  tier_2_approved: boolean;
}

export interface PublicCompanyDetail extends PublicCompany {
  description?: string;
  platform_context?: {
    lifecycle_state: string;
    tier_status: {
      tier_1_approved: boolean;
      tier_2_approved: boolean;
      tier_3_approved: boolean;
    };
  };

  // Approved disclosures (platform-sanitized)
  disclosures?: Array<{
    module_name: string;
    status: string;
    data?: any;
    message?: string; // If under-review or not-available
  }>;
}

export interface PublicCompanyListParams {
  filter?: 'all' | 'live' | 'upcoming';
  sector?: string;
  page?: number;
  per_page?: number;
}

/**
 * Fetch list of public companies
 *
 * Backend endpoint: GET /public/companies
 * Uses PublicCompanyPageService to fetch only publicly visible companies
 */
export async function fetchPublicCompanies(
  params: PublicCompanyListParams = {}
): Promise<{ companies: PublicCompany[]; total: number; sectors: string[] }> {
  try {
    const response = await api.get('/public/companies', { params });

    return {
      companies: response.data.data?.companies || [],
      total: response.data.data?.total || 0,
      sectors: response.data.data?.sectors || [],
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
 * Uses PublicCompanyPageService.getPublicCompanyPage()
 */
export async function fetchPublicCompanyDetail(
  slug: string
): Promise<PublicCompanyDetail | null> {
  try {
    const response = await api.get(`/public/companies/${slug}`);

    return response.data.data || null;
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
 */
export async function fetchPublicSectors(): Promise<string[]> {
  try {
    const response = await api.get('/public/sectors');

    return response.data.data?.sectors || [];
  } catch (error) {
    console.error('[PUBLIC API] Failed to fetch sectors:', error);

    return [];
  }
}
