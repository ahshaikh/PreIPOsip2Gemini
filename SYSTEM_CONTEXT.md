# SYSTEM_CONTEXT.md — PreIPOsip

This document defines the **non-negotiable system laws** for PreIPOsip.
All humans, services, scripts, and AI agents must obey this file.

Violation of any rule below renders the system incorrect or unsafe.

---

## 1. Authority Model

- The backend is the **sole authority** for all financial, compliance, and eligibility decisions.
- The frontend is a **non-authoritative rendering terminal** and must never infer, compute, or reinterpret business meaning.
- Platform Admin authority is **governance-only** and applies primarily during approval, audit, and exceptional override.
- After company approval, **Company Admins are sovereign within their company scope**.
- No platform admin action may bypass ownership, disclosure, audit, or financial invariants.

---

## 2. Tenant Isolation

- The system is strictly multi-tenant.
- All data access **must be scoped by `company_id`**.
- Cross-company access is forbidden under all circumstances.
- No product, deal, disclosure, inventory, or user may exist without a valid owning company.

---

## 3. Financial Integrity

- The double-entry ledger is the **single source of financial truth**.
- No money, entitlement, or liability may move without a corresponding ledger entry.
- Subscription payments are **not revenue**.
- Revenue may be recognized **only** on share sale.
- Ledger entries and financial snapshots are **immutable once created**.
- The system must always be able to explain: what is owned, owed, earned, and spent.

---

## 4. Cold-Start & Bootstrap Guarantees

- The system must function correctly when:
  - A company has exactly **one user**
  - No plans, features, quotas, or configs are pre-seeded
- The **first company user is always `company_admin` by definition**.
- No API, service, or UI may assume the existence of configuration rows, quotas, plans, or seeded data.
- All company-scoped reads must be **null-safe with deterministic defaults**.
- The company portal UI must render a meaningful, non-broken state on first login.

---

## 5. State Machines & Lifecycle Rules

- Product lifecycle is enforced strictly:
  `draft → submitted → approved | rejected`
- Skipping lifecycle states is forbidden.
- Approval is a **state transition**, never data creation.
- KYC is a strict state machine: `pending → approved | rejected`.
- No deal may exist without backing inventory.
- No two active deals for the same product may overlap in time.

---

## 6. Enforcement Rules

- Disabled means **unreachable**:
  - UI, API, background jobs, and scripts must all hard-block.
- All eligibility, approval, and financial rules are **revalidated at execution time**.
- Frontend role checks are UX-only and **never authoritative**.
- Violations must hard-fail; silent degradation is forbidden.

---

## 7. Immutability & Audit Boundary

- Ledger entries, investor snapshots, and platform context snapshots are immutable.
- An **Audit Line** exists (1 February 2026).
- No historical schema or data before the audit line may be modified.
- All post–audit-line changes must be forward-only.
- Provenance is mandatory for all regulated objects (products, inventory, disclosures).

---

## 8. Documentation Authority

- There must be **one source of law** for system behavior: this file.
- README.md is descriptive and non-authoritative.
- Governance, tooling, and institutional memory documents may not override this file.
- The declared framework baseline in composer.json is authoritative over README claims.


---

END OF SYSTEM_CONTEXT.md
