// Helper utilities for normalizing and extracting role names from varied API shapes.
// This file centralizes role-detection logic so all frontend modules use the same
// rules. It handles different response shapes (response.data, response.data.data, user object)
// and normalizes role names like ROLE_ADMIN, super-admin, super_admin -> 'admin' / 'superadmin'.

export function normalizeRoleString(r: any): string {
  if (!r && r !== 0) return '';
  const s = String(r).toLowerCase();
  // Remove common ROLE_ prefixes and non-letter characters, normalize hyphens/underscores
  return s.replace(/^role_/, '').replace(/[-_]/g, '').replace(/[^a-z]/g, '');
}

/**
 * Extract normalized role names from a user object.
 * Handles: user.roles (array), user.role (string), user.role_name (accessor), and user.is_admin (boolean).
 * Returns an array of normalized role names (e.g., ['admin','company']).
 */
export function extractRoleNames(userData: any): string[] {
  if (!userData) return [];

  // roles relationship (e.g., [{name:'admin'}])
  const rolesFromRelation: string[] = (userData.roles || []).map((r: any) => {
    if (!r) return '';
    return normalizeRoleString(r.name || r);
  }).filter(Boolean);

  const candidates: string[] = [];
  if (userData.role) candidates.push(normalizeRoleString(userData.role));
  if (userData.role_name) candidates.push(normalizeRoleString(userData.role_name));
  if (userData.roleName) candidates.push(normalizeRoleString(userData.roleName));
  if (userData.is_admin) candidates.push('admin');

  // Also handle nested shapes where the user object might be wrapped
  // (callers should pass response.data.user || response.data, but be defensive)
  if (userData.user) {
    const nested = userData.user;
    if (nested.role) candidates.push(normalizeRoleString(nested.role));
    if (nested.role_name) candidates.push(normalizeRoleString(nested.role_name));
    if (nested.roles) {
      nested.roles.forEach((r: any) => {
        candidates.push(normalizeRoleString(r.name || r));
      });
    }
    if (nested.is_admin) candidates.push('admin');
  }

  const all = Array.from(new Set([...rolesFromRelation, ...candidates].filter(Boolean)));
  return all;
}
