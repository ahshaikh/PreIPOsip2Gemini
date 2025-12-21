// Unit tests for extractRoleNames helper
import { extractRoleNames } from '@/lib/auth';

describe('extractRoleNames', () => {
  it('extracts from roles array', () => {
    const user = { roles: [{ name: 'admin' }, { name: 'company' }] };
    const roles = extractRoleNames(user);
    expect(roles).toEqual(expect.arrayContaining(['admin','company']));
  });

  it('handles ROLE_ prefix and returns normalized admin', () => {
    const user = { role: 'ROLE_ADMIN' };
    const roles = extractRoleNames(user);
    expect(roles).toEqual(['admin']);
  });

  it('handles role_name with underscores and hyphens', () => {
    const u1 = { role_name: 'super_admin' };
    const u2 = { roles: [{ name: 'super-admin' }] };
    expect(extractRoleNames(u1)).toEqual(['superadmin']);
    expect(extractRoleNames(u2)).toEqual(['superadmin']);
  });

  it('honors is_admin flag', () => {
    const user = { is_admin: true };
    expect(extractRoleNames(user)).toEqual(['admin']);
  });

  it('returns empty for missing data', () => {
    expect(extractRoleNames(null)).toEqual([]);
    expect(extractRoleNames({})).toEqual([]);
  });
});
