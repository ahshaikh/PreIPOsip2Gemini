# PreIPOsip Governance & AI Enforcement Protocol

> **Status:** Binding governance document
> **Scope:** Humans, AI agents, migrations, scripts, CI pipelines
> “This file replaces the former claude.md.
> The rules herein apply to all AI agents, not a specific model.”
---

## 0. Authority & Scope

This document is a **project-wide governance and enforcement specification**.

It applies equally to:

* Human developers
* AI coding agents (Claude, ChatGPT, Gemini, etc.)
* Database migrations
* One-off scripts
* CI/CD pipelines

> This is **not advisory guidance**. It defines *mandatory enforcement rules*.

### Architectural Hierarchy

This document **derives authority from** the **PreIPOsip Living Architecture Document**.

* Architecture defines **what must be true** (invariants)
* This document defines **how fixes must be performed** to preserve those truths

In case of conflict:

> **Architecture invariants always take precedence.**

---

## 1. Core Doctrine — Governance-Grade Fix Protocol

You must fix errors by restoring **root causes and invariants**, never by masking symptoms.

### Absolute Rule

> **Prefer hard failure over false success.**
> If correctness requires failure, the system **must fail**.

Silent success is considered a defect.

---

## 2. Root-Cause Discipline (Mandatory)

Before changing any code, migration, or configuration, you **must explicitly identify**:

1. The invariant being violated
2. Why the invariant exists
3. How the current code breaks it
4. The required guaranteed end-state

A fix that does **not** restore the invariant is **invalid**, even if it passes tests.

---

## 3. Absolute Prohibitions (Never Allowed)

The following are **forbidden** as error fixes:

* `Schema::hasTable()` or similar guards in migrations
* `try/catch` or conditional logic that allows silent skipping
* Best-effort or partial compliance
* Weakening constraints to make errors disappear
* Feature flags to bypass invariants

> If a required dependency is missing, the operation **must fail loudly**.

---

## 4. Migration Law (Strict)

All database migrations must obey:

### Determinism

* Same inputs → same schema
* No environment-dependent logic

### Scope

* **DDL only** (schema definitions)
* No runtime DML (no inserts, logging, backfills, or side effects)

### Dependency Handling

* Dependencies enforced by **ordering**, not guards
* Each migration must document:

  * Preconditions
  * Postconditions

If ordering is wrong, migration failure is **correct behavior**.

---

## 5. Audit & Regulator Posture

For any of the following domains:

* Financial data
* Wallets & transactions
* Bonuses & profit sharing
* KYC & compliance
* State machines & approvals

Assume **forensic and regulatory review years later**.

Rules:

* Silent failure is unacceptable
* Invariants must be provable from **schema and logs alone**
* Behavior must be explainable without tribal knowledge

---

## 6. Required Error-Fix Disclosure

Every fix **must explicitly state**:

1. Violated invariant
2. Why current code breaks it
3. Why naive fixes are dangerous
4. The invariant-restoring fix
5. Guarantees after the fix
6. What now fails loudly if misconfigured

Fixes without this reasoning are incomplete.

---

## 7. Escalation Rule

If an issue **cannot** be fixed without weakening guarantees:

* Do **not** apply a patch
* State explicitly that it is unfixable under current constraints
* Propose a **structural refactor** instead

Correct refusal is preferred over incorrect compliance.

---

## 8. Zero Hardcoded Values — Clarified

### Never Hardcode (Must Be Configurable & Audited)

* Bonus percentages and formulas
* Thresholds, limits, caps
* Eligibility rules
* Fees, charges, penalties
* Time-based business rules

These **must** live in database-backed configuration with audit logs and admin control.

### Must Remain Hardcoded (Never Configurable)

* Enums and state machine states
* Currency units (e.g., paise-only storage)
* Invariant-required constants
* Domain boundaries
* Event names and semantic meanings

> Making invariants configurable is a **governance violation**.

---

## 9. Behavioral Summary

Always choose:

* Invariants over convenience
* Determinism over flexibility
* Structural correctness over velocity
* Explicit failure over silent corruption

These rules override:

* Convenience
* Speed
* Style preferences
* Local optimizations

---

## Final Declaration

This document exists to ensure that **no fix today becomes an audit failure tomorrow**.

If a change feels uncomfortable but correct — it is likely right.
If a change feels easy but weakens guarantees — it is wrong.
