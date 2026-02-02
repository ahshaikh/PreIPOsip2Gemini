# 🏛️ PreIPOsip Platform — Living Architecture Document (Post-Audit)

## 1. Purpose

This document defines the **architectural rules, invariants, and boundaries** of the PreIPOsip platform.

Any change that violates this document **must not be merged** unless this document is explicitly updated as part of the same change.

---

## 2. Architectural Principles (Non-Negotiable)

### 2.1 Single Source of Truth (SSOT)

For any financial, compliance, or ownership-related concept:

* There must be **exactly one authoritative model**
* All other representations must be **derived or read-only**

Violations are considered **P0 defects**.

---

### 2.2 Financial Immutability

Once recorded, financial facts are:

* append-only
* reversible only via compensating records
* never editable in place

Applies to:

* transactions
* investments
* bonuses
* withdrawals
* inventory allocations

---

### 2.3 Workflow Enforcement via State Machines

Any multi-step business process must:

* have explicit states
* enforce allowed transitions
* emit events on transitions
* prohibit direct state mutation

Manual updates that bypass workflows are forbidden.

---

### 2.4 Domain Isolation

Domains may:

* publish events
* consume events
* read exposed projections

Domains must **not**:

* directly query another domain’s core models
* compute another domain’s business rules

---

## 3. Domain Model & Ownership

### 3.1 Identity & Access Domain

**Owns:** Users, Authentication, KYC lifecycle, Roles & Permissions

**Rules:**

* Does not create financial records
* All KYC transitions go through state machine
* Emits `KycVerified`, `KycRejected` events

---

### 3.2 Financial Domain

**Owns:** Wallet, Transactions, Payments, Bonuses, Withdrawals, TDS

**Invariant:**

```
Wallet.balance_paise = SUM(Transaction.amount_paise)
```

**Rules:**

* All balance changes via WalletService
* Paise-only storage (integers)
* No floating-point math

---

### 3.3 Inventory & Allocation Domain

**Owns:** BulkPurchase, Allocation logic, UserInvestment

**Single Source of Truth:**

```
BulkPurchase.value_remaining
```

**Rules:**

* FIFO allocation
* Atomic transactions
* Allocation creates UserInvestment
* No parallel ownership models

---

### 3.4 Investment Ownership (CRITICAL)

**Authoritative Model:** `UserInvestment`

**Rules:**

* `Investment` model is **REMOVED**
* Portfolio and reporting read from `UserInvestment` only
* Any duplicate ownership model is forbidden

Violation severity: **P0**

---

### 3.5 Product & Deal Domain

**Owns:** Product metadata, Deals, Pricing history, Disclosures

**Rules:**

* Deal inventory is derived from BulkPurchase
* Deal stores no inventory state
* Deal does not own ownership data

---

### 3.6 Campaign & Promotion Domain

**Authoritative Model:** `Campaign`

**Rules:**

* `Offer` model is **REMOVED**
* All discounts via CampaignService
* Campaign approval required before usage
* Discount priority must be deterministic

---

### 3.7 Subscription & Plan Domain

**Owns:** Plans, Subscriptions, SIP logic, Eligibility

**Rules:**

* Subscription does not calculate ownership
* Reads summaries via projections
* Lifecycle managed via states

---

### 3.8 Referral & Bonus Domain

**Owns:** Referrals, Bonus calculation, Multipliers

**Rules:**

* Single BonusCalculatorService
* Bonus logic must be idempotent
* Bonus credits reference Payment IDs

---

## 4. Cross-Domain Communication

### 4.1 Events (Preferred)

Domains communicate via immutable events:

* PaymentSuccessful
* KycVerified
* CampaignActivated
* AllocationCompleted

---

### 4.2 Forbidden Patterns

* Cross-domain model queries
* Duplicate calculators
* Manual DB updates
* Workflow bypasses

---

## 5. Non-Negotiable Invariants

> **Status: Permanently Enforced**
> These invariants are no longer design intent — they are mechanically enforced through database constraints, service-layer contracts, and automated tests.
> Any violation is a **hard architectural failure (P0)**.

### Financial

```
Payment.amount =
  UserInvestment.value_allocated +
  BonusTransaction.amount +
  refunds
```

### Inventory

```
BulkPurchase.total_value_received =
  BulkPurchase.value_remaining +
  UserInvestment.value_allocated
```

### Compliance

* Campaign must be approved
* KYC required before allocation
* All admin actions logged

---

### Permanent Platform Invariants (Mechanically Enforced)

> These invariants represent irreversible architectural decisions.
> They are enforced at **schema, service, and audit levels** and may not be bypassed, relaxed, or conditionally disabled.

#### INVARIANT 1: UserInvestment.subscription_id is Mandatory (PERMANENT)

**Declaration:**

> All UserInvestment records MUST have a non-null subscription_id.

**Rationale:**

* Ownership exists only within a Subscription context
* No orphaned allocations permitted

**Enforcement:**

* NOT NULL + FK constraint
* Mandatory service parameters
* AllocationService hard requirement

---

#### INVARIANT 2: Campaign is the Sole Promotional Construct (PERMANENT)

**Declaration:**

> Campaign is the ONLY model for promotions, discounts, and offers.

**Enforcement:**

* No Offer model exists
* campaigns() naming enforced
* CampaignService is the sole entry point

---

## 6. Enforcement Mechanisms

* Database constraints (NOT NULL, FK, CASCADE)
* Service-layer hard requirements
* CI-blocking invariant tests
* PR architectural checklist
* Audit logging of admin actions
* Zero tolerance for manual DB mutation

---

## 7. Evolution Policy

This document may change **only** when:

* A domain boundary is intentionally redefined
* A source of truth is formally migrated
* A new domain is introduced

All changes require:

* Architecture document update (same PR)
* Explicit migration plan
* Rollback strategy
* Audit sign-off

> Feature additions, refactors, or optimizations do **not** justify modifying this document.

---

## 8. Audit Alignment & Freeze Status

**Audit Phases:**

* Phase 1: Ownership & creation violations — **CLOSED**
* Phase 2: Financial duplication & SSOT breaches — **CLOSED**
* Phase 3: Subscription, Campaign, Allocation invariants — **CLOSED**
* Phase 4: Architectural freeze — **ACTIVE**

**Current Status:**

> The platform is in **post-audit frozen architecture mode**.
> Code must conform to this document; the document does not bend for code.

---

## Final Note

This document exists to **prevent architectural regression** and ensure PreIPOsip remains scalable, auditable, and enforceable as it grows.
