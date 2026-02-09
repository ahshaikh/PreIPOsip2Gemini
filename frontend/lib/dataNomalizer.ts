/**
 * Data Normalization Layer
 *
 * PURPOSE:
 * Normalize API responses at the data boundary to ensure safe defaults
 * and prevent scattered null checks throughout the UI.
 *
 * PRINCIPLES:
 * - Normalize once at the boundary, not scattered across JSX
 * - Provide safe defaults that match TypeScript interfaces
 * - Log when normalization is needed (indicates backend contract breach)
 * - Single source of truth for data shape
 */

import { IssuerCompanyData } from './issuerCompanyApi';

/**
 * Normalize issuer company data from API
 * Ensures all expected fields exist with safe defaults
 *
 * @param data - Raw API response
 * @returns Normalized data with guaranteed structure
 */
export function normalizeIssuerCompanyData(data: any): IssuerCompanyData {
  const hasIssues = !data.disclosures || !data.effective_permissions || !data.platform_context;

  if (hasIssues) {
    console.warn('[DATA NORMALIZER] API response missing expected fields:', {
      missingDisclosures: !data.disclosures,
      missingEffectivePermissions: !data.effective_permissions,
      missingPlatformContext: !data.platform_context,
    });
  }

  return {
    ...data,
    // Always ensure arrays exist
    disclosures: data.disclosures ?? [],
    clarifications: data.clarifications ?? [],
    platform_overrides: data.platform_overrides ?? [],

    // Ensure effective_permissions with safe defaults
    effective_permissions: data.effective_permissions ?? {
      can_edit_disclosures: false,
      can_submit_disclosures: false,
      can_answer_clarifications: false,
    },

    // Ensure platform_context with safe defaults (normalize nested fields too)
    platform_context: {
      lifecycle_state: data.platform_context?.lifecycle_state ?? 'unknown',
      is_suspended: data.platform_context?.is_suspended ?? false,
      is_frozen: data.platform_context?.is_frozen ?? false,
      is_under_investigation: data.platform_context?.is_under_investigation ?? false,
      buying_enabled: data.platform_context?.buying_enabled ?? false,
      buying_pause_reason: data.platform_context?.buying_pause_reason,
      tier_status: {
        tier_1_approved: data.platform_context?.tier_status?.tier_1_approved ?? false,
        tier_2_approved: data.platform_context?.tier_status?.tier_2_approved ?? false,
        tier_3_approved: data.platform_context?.tier_status?.tier_3_approved ?? false,
      },
    },
  };
}

/**
 * Type guard to check if data needs normalization
 * Useful for debugging and monitoring backend contract adherence
 */
export function needsNormalization(data: any): boolean {
  return (
    !data.disclosures ||
    !data.effective_permissions ||
    !data.platform_context ||
    !Array.isArray(data.disclosures)
  );
}
