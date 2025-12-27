# PreIPOsip Platform Architecture

## Purpose

This document defines **structural invariants** that make entire classes of bugs **impossible** by design. These are not guidelines or best practices—they are **hard constraints** enforced at the database, type system, and service layer.

Violating these invariants will cause compilation failures, runtime exceptions, or database constraint violations.

---

## Protocol-1 Invariants

### INVARIANT 1: UserInvestment.subscription_id is Mandatory

**Declaration:**
> **All UserInvestment records MUST have a non-null subscription_id.**

**Rationale:**
- Ownership and allocation rights exist ONLY within the context of a Subscription
- No investment can exist without a payment plan
- No "orphaned" allocations are permitted in the system

**Enforcement Mechanisms:**

1. **Database Constraint** (`user_investments` table):
   ```sql
   subscription_id BIGINT UNSIGNED NOT NULL
   FOREIGN KEY (subscription_id) REFERENCES subscriptions(id) ON DELETE CASCADE
   ```

2. **Model Validation** (`UserInvestment.php`):
   ```php
   protected $fillable = ['subscription_id', ...]; // Required in mass assignment
   ```

3. **Service Layer** (`AllocationService.php`):
   - All allocation methods REQUIRE `subscription_id` parameter
   - Cannot create UserInvestment without valid subscription

**What This Prevents:**
- ❌ Admin creating allocations without subscription context
- ❌ Manual database inserts bypassing subscription ownership
- ❌ Legacy "free allocation" workflows that bypass payment plans
- ❌ Data migration scripts creating orphaned investments

**Migration Status:**
- ✅ Schema updated with NOT NULL constraint
- ✅ Legacy data backfilled (where applicable)
- ✅ Data migration rollback scripts exist for audit trail

**Future Requirements:**

If ANY future feature requires admin-initiated allocations (e.g., promotional grants, bonus shares), it:
1. MUST create a system-generated Subscription first
2. MUST link UserInvestment to that Subscription
3. MUST NOT bypass the subscription_id constraint

**Example Violation (FORBIDDEN):**
```php
// ❌ THIS WILL FAIL
UserInvestment::create([
    'user_id' => $userId,
    'product_id' => $productId,
    'subscription_id' => null, // Database constraint violation
]);
```

**Correct Pattern (ENFORCED):**
```php
// ✅ ONLY VALID APPROACH
$subscription = Subscription::where('user_id', $userId)->first();
UserInvestment::create([
    'user_id' => $userId,
    'product_id' => $productId,
    'subscription_id' => $subscription->id, // Required
]);
```

---

### INVARIANT 2: Campaign is the Sole Promotional Construct

**Declaration:**
> **Campaign is the ONLY model for promotions, discounts, and offers.**
> **No parallel promotion primitives are permitted.**

**Rationale:**
- Prevents dual models (Campaign + Offer) causing consistency issues
- Single source of truth for all promotional logic
- Eliminates semantic confusion about "campaign vs offer"

**Enforcement Mechanisms:**

1. **Model Layer:**
   - Only `Campaign.php` model exists for promotions
   - No `Offer.php` model in codebase
   - All relationship methods named `campaigns()` (not `offers()`)

2. **Database Schema:**
   - Table: `campaigns` (not `offers`)
   - Pivot tables: `campaign_products`, `campaign_deals`, `campaign_plans`
   - No `offer_*` tables permitted

3. **Service Layer:**
   - `CampaignService.php` is the sole service for promotional logic
   - No `OfferService` or parallel promotional services

4. **Semantic Enforcement:**
   - All methods/variables use `campaign` terminology
   - Relationship methods: `Product::campaigns()`, `Deal::campaigns()`, `Plan::campaigns()`
   - **NOT** `offers()` or `getActiveOffers()`

**What This Prevents:**
- ❌ Creating new "Offer" model alongside Campaign
- ❌ Creating parallel "Promotion", "Discount", "Deal" models
- ❌ Splitting promotional logic across multiple services
- ❌ Method names like `offers()` that imply Offer still exists

**Migration Status:**
- ✅ Offer model deleted
- ✅ `offers` table renamed to `campaigns`
- ✅ All pivot tables renamed (`offer_products` → `campaign_products`)
- ✅ All relationships updated to use Campaign model
- ✅ All method names changed to `campaigns()` nomenclature

**Future Requirements:**

If ANY new promotional feature is needed (e.g., "flash sales", "coupons", "loyalty rewards"):
1. MUST extend the `Campaign` model (not create new model)
2. MUST use `CampaignService` for business logic
3. MUST use existing `campaign_*` pivot tables
4. MUST NOT create parallel promotional constructs

**Example Violation (FORBIDDEN):**
```php
// ❌ FORBIDDEN: Creating parallel promotional model
class Offer extends Model {
    // This violates INVARIANT 2
}

// ❌ FORBIDDEN: Method name preserving legacy semantics
public function offers() {
    return $this->belongsToMany(Campaign::class);
}
```

**Correct Pattern (ENFORCED):**
```php
// ✅ ONLY VALID APPROACH
class Campaign extends Model {
    // All promotional logic here
}

// ✅ Method name matches domain model
public function campaigns() {
    return $this->belongsToMany(Campaign::class, 'campaign_products');
}
```

---

## Architectural Principles

### 1. Single Source of Truth
- Every entity has ONE authoritative model
- Derived data is computed, not stored
- When caching is needed, mark it explicitly

### 2. State Machines for Workflows
- KYC: Formal state transitions via `KycStatusService`
- Subscription: Lifecycle managed via `SubscriptionService`
- Campaign: Status controlled via `CampaignService`
- No manual status field updates

### 3. Service Layer for Complex Logic
- Controllers are thin (routing, validation, response)
- Models are rich (domain behavior, not anemic)
- Services orchestrate multi-model operations

### 4. Database Constraints Enforce Invariants
- Use NOT NULL for required fields
- Use FOREIGN KEY for referential integrity
- Use CHECK constraints for business rules
- Use UNIQUE constraints to prevent duplicates

### 5. Type System Prevents Wrong Usage
- Use value objects with private constructors (e.g., `TdsResult`)
- Use enums for state transitions (e.g., `KycStatus`)
- Use type hints to enforce correct parameters

---

## Invariant Verification

### How to Verify INVARIANT 1

**Database Check:**
```sql
-- This should return 0 (no orphaned investments)
SELECT COUNT(*) FROM user_investments WHERE subscription_id IS NULL;
```

**Schema Check:**
```sql
-- Verify NOT NULL constraint exists
SHOW CREATE TABLE user_investments;
-- Should show: subscription_id bigint unsigned NOT NULL
```

**Code Check:**
```bash
# No UserInvestment creation without subscription_id
grep -r "UserInvestment::create" --include="*.php" | grep -v "subscription_id"
# Should return empty (all creates include subscription_id)
```

### How to Verify INVARIANT 2

**Model Check:**
```bash
# Verify no Offer model exists
find . -name "Offer.php" -path "*/Models/*"
# Should return empty

# Verify no parallel promotional models
find . -name "*Promotion*.php" -o -name "*Discount*.php" -path "*/Models/*"
# Should only return Campaign-related files
```

**Database Check:**
```sql
-- This should fail (table doesn't exist)
SELECT * FROM offers;

-- This should succeed (table exists)
SELECT * FROM campaigns;
```

**Relationship Check:**
```bash
# No methods named offers() should exist in models
grep -r "public function offers()" app/Models/*.php
# Should return empty (all renamed to campaigns())
```

---

## Regression Prevention

### Adding New Features

Before adding ANY new feature:

1. **Check INVARIANT 1**: Does this create UserInvestments?
   - If YES: Ensure `subscription_id` is required and validated
   - Test with NULL `subscription_id` to verify constraint enforcement

2. **Check INVARIANT 2**: Does this involve promotions/discounts?
   - If YES: Extend `Campaign` model (do NOT create new model)
   - Use `CampaignService` for logic
   - Use `campaign_*` terminology in code

### Code Review Checklist

- [ ] No new promotional models created (only Campaign permitted)
- [ ] All UserInvestment creates include `subscription_id`
- [ ] No method names use legacy "offer" terminology
- [ ] Database migrations include NOT NULL constraints for required fields
- [ ] Service layer enforces invariants (not just database)

---

## Contact

For questions about these invariants or architectural decisions, refer to:
- `ARCHITECTURAL_AUDIT_REPORT.md` - Detailed audit findings
- `P0.1_FIX_PROOF.md` - Investment/UserInvestment consolidation
- `P0.2_FIX_PROOF.md` - Campaign/Offer consolidation

**Last Updated:** 2025-12-27
**Audit Session:** claude/audit-fintech-architecture-Xi25l
