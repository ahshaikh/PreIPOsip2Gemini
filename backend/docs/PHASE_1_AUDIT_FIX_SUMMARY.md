# Phase 1 Audit Fix Summary: Disclosure Authority & Immutability

**Date:** 2026-01-31
**Audit Phase:** Phase 1 — Disclosure & Authority Gaps
**Status:** Implemented

---

## Executive Summary

This document describes the fixes implemented for Phase 1 audit gaps related to disclosure authority and immutability. All fixes follow the governance-grade protocol: hard failures over silent skips, invariant restoration over symptom masking.

---

## Gaps Addressed

### 1. Disclosure Authority Completeness
**Problem:** Investors could potentially see non-approved disclosures or mutable data.

**Invariants Enforced:**
- ONLY approved disclosure versions are visible to investors
- ONLY approved disclosure versions are used in snapshots
- No mixed-version reads
- No fallback to draft/previous disclosures

### 2. Immutability & Version Safety
**Problem:** Approved disclosures lacked enforcement against modifications.

**Invariants Enforced:**
- Approved disclosures are immutable at the model/observer level
- DisclosureVersion records cannot be modified after creation
- Hard failure if invariants are violated

---

## Files Changed/Created

### New Files

| File | Purpose |
|------|---------|
| `app/Repositories/ApprovedDisclosureRepository.php` | Single source of truth for investor-visible disclosures. Enforces approved-only + immutable version data. |
| `app/Exceptions/DisclosureAuthorityViolationException.php` | Dedicated exception for authority violations with auto-logging. |
| `app/Scopes/InvestorVisibleDisclosureScope.php` | Global scope for investor queries enforcing approved-only. |
| `tests/Unit/Phase1Audit/DisclosureAuthorityTest.php` | Comprehensive tests for Phase 1 invariants. |

### Modified Files

| File | Changes |
|------|---------|
| `app/Observers/CompanyDisclosureObserver.php` | Added `updating()` hook to block modifications to approved disclosures. |
| `app/Observers/DisclosureVersionObserver.php` | Refined to allow metadata updates while blocking data changes. |
| `app/Models/CompanyDisclosure.php` | Added `investor_visible_data` accessor enforcing version data usage. |
| `app/Models/DisclosureVersion.php` | Added `booted()` method for defense-in-depth immutability. |
| `app/Traits/HasVisibilityScope.php` | Updated `toInvestorArray()` to use immutable version data. |
| `app/Services/InvestmentSnapshotService.php` | Now uses ApprovedDisclosureRepository for capturing disclosures. |

---

## Invariants Enforced

### 1. Approved Disclosure Immutability

**Rule:** Once a disclosure is approved, its disclosure_data MUST NOT change.

**Enforcement Points:**
1. `CompanyDisclosureObserver::updating()` - Blocks changes to IMMUTABLE_FIELDS
2. `CompanyDisclosure::$is_locked` - Flag checked in business methods
3. Audit logging of all violation attempts

**Allowed Updates on Approved Disclosures:**
- `internal_notes` (admin notes, never shown to investors)
- `current_version_id` (set when creating new version)
- `version_number` (incremented for new version)
- `is_locked` (locking mechanism)

**Blocked Updates (IMMUTABLE_FIELDS):**
- `disclosure_data`
- `attachments`
- `status`
- `visibility`
- `is_visible`
- `completion_percentage`

### 2. DisclosureVersion Immutability

**Rule:** DisclosureVersion records are permanently immutable. No updates or deletes.

**Enforcement Points:**
1. `DisclosureVersion::booted()` - Model-level blocking
2. `DisclosureVersionObserver::updating()` - Observer-level blocking
3. `DisclosureVersionObserver::deleting()` - Block all deletes
4. Database triggers (if configured)

**Allowed Metadata Updates:**
- `was_investor_visible`
- `first_investor_view_at`
- `investor_view_count`
- `linked_transactions`

### 3. Investor Visibility Authority

**Rule:** Investors ONLY see approved disclosures with immutable version data.

**Enforcement Points:**
1. `ApprovedDisclosureRepository` - ONLY returns approved disclosures
2. `CompanyDisclosure::investor_visible_data` - Returns version data, not disclosure data
3. `HasVisibilityScope::toInvestorArray()` - Uses version data
4. `InvestmentSnapshotService` - Only captures approved disclosures

### 4. Snapshot Authority

**Rule:** Investment snapshots MUST capture approved disclosures with locked version data.

**Enforcement Points:**
1. `InvestmentSnapshotService::captureAtPurchase()` uses `ApprovedDisclosureRepository`
2. Repository returns version data, not mutable disclosure data
3. Hard failure if approved disclosure lacks a valid version

---

## Error Handling

### Hard Failures

All invariant violations throw `DisclosureAuthorityViolationException`:

```php
// Missing version on approved disclosure
DisclosureAuthorityViolationException::missingVersion($disclosureId, $companyId);

// Version record not found
DisclosureAuthorityViolationException::versionNotFound($disclosureId, $versionId, $companyId);

// Version not locked
DisclosureAuthorityViolationException::unlockedVersion($disclosureId, $versionId, $companyId);

// Hash mismatch (tampering detected)
DisclosureAuthorityViolationException::hashMismatch($versionId, $storedHash, $computedHash);
```

### Audit Logging

All violations are logged:
1. Immediately to Laravel logs (CRITICAL/EMERGENCY level)
2. To `audit_logs` table for permanent record
3. With full context: actor, IP, user agent, stack trace

---

## Usage Guidelines

### For Investor-Facing APIs

```php
// CORRECT: Use repository
$repository = new ApprovedDisclosureRepository();
$disclosures = $repository->getApprovedDisclosuresForInvestor($companyId);

// CORRECT: Use accessor
$investorData = $disclosure->investor_visible_data;

// CORRECT: Use trait scope
$disclosures = CompanyDisclosure::approvedWithVersion()->get();

// WRONG: Direct query without scopes
$disclosures = CompanyDisclosure::where('company_id', $companyId)->get(); // May include drafts!
```

### For Snapshots

```php
// CORRECT: Service uses repository internally
$snapshotService = new InvestmentSnapshotService();
$snapshotId = $snapshotService->captureAtPurchase($investmentId, $investor, $company);

// WRONG: Direct query for snapshot data
$disclosures = DB::table('company_disclosures')->get(); // May include non-approved!
```

---

## Testing

Run Phase 1 audit tests:

```bash
php artisan test --filter=DisclosureAuthorityTest
```

### Test Coverage

1. DisclosureVersion data immutability
2. DisclosureVersion delete blocking
3. DisclosureVersion metadata updates allowed
4. Approved disclosure data immutability
5. Approved disclosure status immutability
6. Approved disclosure internal_notes updates allowed
7. Investor visible data accessor returns version data
8. Accessor throws for approved without version
9. Accessor returns null for drafts
10. Repository only returns approved disclosures
11. Repository returns immutable version data
12. Repository throws for approved without version
13. Version hash integrity verification
14. Tampered version fails integrity check

---

## Regulatory Compliance

These fixes support:

1. **SEBI Requirements:** Permanent audit trail of investor-relied-upon data
2. **Dispute Resolution:** Snapshots prove exactly what investor saw at purchase
3. **Tamper Detection:** Hash verification proves data integrity
4. **Data Integrity:** Immutability prevents post-hoc modifications

---

## Future Considerations

1. **Database Triggers:** Consider adding MySQL triggers as additional defense layer
2. **Read Replicas:** For investor queries, route to read replicas with additional row-level security
3. **Periodic Audits:** Automated jobs to verify all approved disclosures have valid versions
4. **Monitoring:** Alerts on any immutability violation attempts
