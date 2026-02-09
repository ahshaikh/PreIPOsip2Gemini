# INSTITUTIONAL_MEMORY.md — PreIPOsip

This document preserves institutional learning, failures, and rationale.
It is intentionally loss-free and explanatory.

This file is NOT enforceable law.

---

## 1. Historical Failure Modes

### Financial & Accounting
- Incorrect revenue recognition from subscriptions
- Duplicate wallet credits from mixed ledger/wallet flows
- Bonus usage without cost recognition
- Fragmented ledger implementations
- Missing compensation paths in orchestration flows

### Database & Migrations
- Partial MySQL DDL migrations (1059, 1451, 150)
- ENUM truncation errors
- Non-null timestamps breaking approvals
- Conditional migrations masking failures
- Foreign key cleanup deadlocks

### Product & Ownership
- Orphan products due to nullable `company_id`
- Parallel admin + issuer creation paths
- Approval bypass via admin controllers
- Inventory created before product approval

### Frontend & API
- Axios double-slash baseURL bugs
- Frontend–backend contract mismatches
- Route assumptions not backed by filesystem
- UI-driven authority leaks
- Snapshot reconstruction from live state

---

## 2. Anti-Patterns (Never Repeat)

- Frontend-driven authority
- Admins acting as content authors
- Bypassing invariants in seeders
- UI-only feature disabling
- Retroactive schema fixes
- Silent fallbacks masking backend failures
- Dual sources of financial truth

---

## 3. Tooling & Environment Lessons

- Composer partial-update deadlocks
- Framework/package version mismatches
- Windows path and wildcard failures
- Cache artifacts mistaken for source
- Shell escaping issues in generated commands

---

## 4. AI Interaction Lessons

- One-shot code generation causes omissions
- Ambiguous prompts lead to hallucinated files
- Missing file/path constraints break imports
- AI summaries cannot replace full code
- Governance must not be model-specific

---

## 5. Governance Rationale

- Admin supremacy emerged from audit failures, not preference
- Immutability was enforced after reconstruction failures
- Cold-start rules were discovered via first-login crashes
- Null-safety rules emerged from production runtime errors

---

## 6. Known Conflicts (Pending Human Arbitration)
- “Resolved: Laravel 11 is the enforced baseline”
- Laravel framework baseline (11)
- Documentation hierarchy authority wording

---

## 7. Superseded or Historical Decisions

- Legacy single-entry ledger (deprecated)
- Admin-authored products (removed)
- Seed-time invariant bypasses (rejected)

---

END OF INSTITUTIONAL_MEMORY.md
