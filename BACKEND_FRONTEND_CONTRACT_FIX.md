# Backend-Frontend Contract Fix

## Problem
Frontend expected `IssuerCompanyData` with guaranteed structure:
```typescript
{
  disclosures: Disclosure[],
  effective_permissions: {...},
  platform_context: {...},
  clarifications: Clarification[]
}
```

Backend was returning raw Eloquent model, causing:
- `company.disclosures is undefined`
- `effectivePermissions is undefined`
- `lifecycle_state is undefined`

## Solution Hierarchy

### ✅ Layer 1: Backend Contract Enforcement (ROOT CAUSE FIX)

**File**: `backend/app/Services/CompanyDisclosureService.php`
- **Added**: `getIssuerCompanyData(Company $company)` method
- **Guarantees**: Complete structure matching TypeScript interface
- **Returns**: All required fields with safe defaults

**File**: `backend/app/Http/Controllers/Api/Company/CompanyProfileController.php`
- **Updated**: `dashboard()` method to use `getIssuerCompanyData()`
- **Before**: Returned raw `$company` model
- **After**: Returns contract-compliant structure

**Benefits**:
- Backend always returns correct shape
- No undefined fields possible
- TypeScript interface matches reality
- Single source of truth for structure

### ✅ Layer 2: Frontend Defensive Boundary (DEFENSIVE)

**File**: `frontend/lib/dataNomalizer.ts`
- **Added**: `normalizeIssuerCompanyData()` function
- **Purpose**: Defensive safety net if backend contract breaks
- **Logs**: Console warnings when normalization is needed (catches backend issues)

**File**: `frontend/app/company/disclosures/page.tsx`
- **Updated**: Apply normalization at data boundary
- **Benefit**: Single point of normalization, clean JSX

**Why Keep This?**
Even with backend fix, defensive normalization:
1. Catches backend regressions early (via console warnings)
2. Provides graceful degradation
3. Documents expected structure
4. Acts as monitoring layer

### ❌ Layer 3: JSX Guards (REMOVED)

**Removed**: Scattered `?.` operators and `|| []` fallbacks throughout JSX
**Replaced**: Clean references like `company.disclosures.map(...)`

## Testing the Fix

### Backend Test (curl)
```bash
curl -H "Authorization: Bearer <token>" \
  http://localhost:8000/api/v1/company-profile/dashboard
```

Should return complete structure with all fields guaranteed.

### Frontend Test
1. Visit `/company/disclosures`
2. No more runtime errors
3. Check console for normalization warnings (should be none)

## Monitoring

If you see console warnings from `dataNomalizer.ts`:
```
[DATA NORMALIZER] API response missing expected fields
```

This indicates backend contract violation - investigate the backend endpoint.

## Future: Schema Validation

Consider adding:
1. OpenAPI/Swagger schema definitions
2. Laravel API Resources for consistent transformations
3. Automated contract tests (frontend ↔ backend)
4. TypeScript code generation from backend schemas

## Principle Applied

> **Always fix the root cause first; use guards or fallbacks only after the underlying issue is understood and consciously accepted.**

This fix:
1. ✅ Fixed backend to always return correct shape (root cause)
2. ✅ Kept frontend normalization as defensive monitoring layer
3. ✅ Removed scattered JSX guards (symptoms)
