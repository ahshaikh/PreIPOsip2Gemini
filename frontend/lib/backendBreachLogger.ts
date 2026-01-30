/**
 * EPIC 5 Story 5.1 - Backend Breach Logger
 *
 * PURPOSE:
 * Log detected backend invariant breaches for future escalation.
 * When the public frontend projection layer detects prohibited fields
 * in backend responses, this logger records the breach.
 *
 * IMPORTANT:
 * - This does NOT fix the backend issue
 * - This does NOT display the prohibited data
 * - This logs for awareness and future backend work (outside EPIC 5)
 *
 * PRINCIPLE:
 * "Prefer hard failure over false success" - we detect and log,
 * but the frontend silently drops prohibited data from display.
 */

// ============================================================================
// TYPES
// ============================================================================

interface BreachContext {
  companyId?: number;
  companySlug?: string;
  endpoint?: string;
  timestamp?: string;
  [key: string]: any;
}

interface BreachLogEntry {
  severity: 'warning' | 'error';
  source: string;
  prohibitedFields: string[];
  context: BreachContext;
  timestamp: string;
  message: string;
}

// ============================================================================
// CONFIGURATION
// ============================================================================

/**
 * Enable/disable breach logging
 * In production, you might want to send these to a monitoring service
 */
const BREACH_LOGGING_ENABLED = true;

/**
 * Log to console in development
 */
const LOG_TO_CONSOLE = process.env.NODE_ENV !== 'production';

/**
 * Prefix for all breach log messages
 */
const LOG_PREFIX = '[BACKEND BREACH]';

// ============================================================================
// BREACH TRACKING
// ============================================================================

/**
 * In-memory breach tracking for deduplication
 * Prevents flooding logs with repeated breaches for same fields
 */
const recentBreaches = new Map<string, number>();
const DEDUP_WINDOW_MS = 60000; // 1 minute deduplication window

/**
 * Generate a deduplication key for a breach
 */
function getBreachKey(source: string, fields: string[]): string {
  return `${source}:${fields.sort().join(',')}`;
}

/**
 * Check if this breach was recently logged (deduplication)
 */
function wasRecentlyLogged(key: string): boolean {
  const lastLogged = recentBreaches.get(key);
  if (!lastLogged) return false;
  return Date.now() - lastLogged < DEDUP_WINDOW_MS;
}

/**
 * Mark breach as logged
 */
function markAsLogged(key: string): void {
  recentBreaches.set(key, Date.now());
  // Cleanup old entries periodically
  if (recentBreaches.size > 100) {
    const now = Date.now();
    for (const [k, time] of recentBreaches.entries()) {
      if (now - time > DEDUP_WINDOW_MS) {
        recentBreaches.delete(k);
      }
    }
  }
}

// ============================================================================
// MAIN LOGGING FUNCTION
// ============================================================================

/**
 * Log a detected backend invariant breach
 *
 * Called when the public data sanitizer detects prohibited fields
 * in backend response data.
 *
 * @param source - The function/component that detected the breach
 * @param prohibitedFields - Array of prohibited field names found
 * @param context - Additional context (company ID, slug, etc.)
 */
export function logBackendBreach(
  source: string,
  prohibitedFields: string[],
  context: BreachContext = {}
): void {
  if (!BREACH_LOGGING_ENABLED) return;
  if (prohibitedFields.length === 0) return;

  // Deduplication check
  const key = getBreachKey(source, prohibitedFields);
  if (wasRecentlyLogged(key)) return;
  markAsLogged(key);

  const timestamp = new Date().toISOString();

  const entry: BreachLogEntry = {
    severity: 'warning',
    source,
    prohibitedFields,
    context: {
      ...context,
      timestamp,
    },
    timestamp,
    message: `${LOG_PREFIX} Prohibited fields detected in public API response`,
  };

  // Console logging for development
  if (LOG_TO_CONSOLE) {
    console.warn(
      `${LOG_PREFIX} Prohibited fields detected in ${source}:`,
      prohibitedFields.join(', '),
      context.companySlug ? `(company: ${context.companySlug})` : ''
    );
    console.warn(
      `${LOG_PREFIX} These fields were dropped at render time. Backend should not send investor-only data to public endpoints.`
    );
  }

  // In production, you would send to monitoring service here:
  // sendToMonitoringService(entry);
}

/**
 * Log a tier visibility breach
 *
 * Called when a company with tier < 2 is detected in public response
 *
 * @param companySlug - The company slug
 * @param actualTier - The actual disclosure tier
 * @param context - Additional context
 */
export function logTierVisibilityBreach(
  companySlug: string,
  actualTier: string | undefined,
  context: BreachContext = {}
): void {
  if (!BREACH_LOGGING_ENABLED) return;

  const key = `tier:${companySlug}:${actualTier}`;
  if (wasRecentlyLogged(key)) return;
  markAsLogged(key);

  const timestamp = new Date().toISOString();

  if (LOG_TO_CONSOLE) {
    console.warn(
      `${LOG_PREFIX} Non-public tier company in public API:`,
      `slug=${companySlug}, tier=${actualTier || 'undefined'}`,
      'Expected tier_2_live or tier_3_featured'
    );
  }
}

/**
 * Get breach statistics (for debugging/monitoring)
 */
export function getBreachStats(): { recentBreachCount: number; keys: string[] } {
  return {
    recentBreachCount: recentBreaches.size,
    keys: Array.from(recentBreaches.keys()),
  };
}

/**
 * Clear breach tracking (for testing)
 */
export function clearBreachTracking(): void {
  recentBreaches.clear();
}
