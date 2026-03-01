🧊 TODO — PlatformLedgerService Freeze & Controlled Migration Plan
Status: FROZEN (No New Usage Allowed)

Rationale: Core financial invariants stable. No active drift. Immediate full deprecation not required. Migration deferred to controlled phase.
---
🔒 Phase 0 — Freeze (Immediate)
- Mark PlatformLedgerService as @deprecated
- Add inline warning comment: “DO NOT USE — legacy system”
- Add CI/static scan rule preventing new references to:
  - PlatformLedgerService
  - PlatformLedgerEntry
- Audit that no new writes are introduced going forward
---
🔍 Phase 1 — Full Dependency Audit (Scheduled)
- Search entire codebase for:
  - PlatformLedgerService
  - PlatformLedgerEntry
  - ledger_entry_id
  - SOURCE_BULK_PURCHASE
- Map:
  - Controllers
  - Jobs
  - Model boot hooks
  - Observers
  - Policies
  - DB constraints
  - Foreign keys
- Document all runtime dependencies
Deliverable: Dependency Map Document

---

🪞 Phase 2 — Shadow Invariant (Optional but Recommended)
- Add verification that:
  - Inventory value (PlatformLedger)
  - ≈ Inventory Asset balance (DoubleEntryLedger)
- Ensure no divergence over time
Goal: Detect silent drift before migration.

---

🔁 Phase 3 — Gradual Migration Plan
- Redirect new inventory capital flows to DoubleEntryLedgerService
- Stop writing new rows to platform_ledger_entries
- Keep legacy table read-only
- Replace ledger_entry_id coupling where possible

---

⚖ Phase 4 — Epic4 Resurrection
After migration:
- Rewrite Epic4FinancialAtomicityTest to assert:
  - DoubleEntry ledger immutability
  - Inventory ↔ ledger asset coupling
  - Deal creation inventory backing via ledger
  - No artificial skips / no incomplete tests
- Remove legacy PlatformLedger tests
- Run Epic4 cleanly (no skips, no risky)

---

🗑 Phase 5 — Final Deprecation
- Remove PlatformLedgerService
- Remove PlatformLedgerEntry model
- Drop obsolete foreign keys
- Archive legacy data
- Tag release as architectural milestone

🎯 Guiding Principle
< Do not migrate under emotional pressure.
< Migrate when invariants are measurable and shadow-verified.