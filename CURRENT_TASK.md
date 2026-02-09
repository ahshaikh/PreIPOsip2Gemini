# CURRENT_TASK.md — PreIPOsip

## Task Classification (MANDATORY)

Select exactly one and delete the rest:

- [ ] Routine Feature / UI / API wiring
- [ ] Bug Fix (non-financial, non-auth)
- [ ] Debugging / Investigation
- [ ] Refactor (structural)
- [ ] Financial / Accounting / Ledger
- [ ] Auth / RBAC / Onboarding
- [ ] Governance / Audit / Compliance

> If unsure, choose the **higher-risk** category.

---

## Context Loading Declaration (MANDATORY)

I confirm that I have loaded context according to `AI_CONTEXT_POLICY.md`.

### Loaded Context Files

- [x] SYSTEM_CONTEXT.md (LAW)
- [ ] INSTITUTIONAL_MEMORY.md (EVIDENCE)
- [ ] README.md (REFERENCE)
- [ ] CLAUDE.md (TOOLING)

> ❗ If SYSTEM_CONTEXT.md is not loaded, you must STOP.

---

## Task Statement (Concrete & Bounded)

Describe **exactly** what is being done.

- What is changing?
- Where (file/module/route)?
- What is explicitly **out of scope**?

Example:
> Add Company User Management UI inside `/company/settings/users` that allows a `company_admin` to invite users, assign roles, and suspend users. No backend changes unless strictly required.

---

## Authority & Scope Check (MANDATORY)

Answer explicitly:

1. Which actor is performing this action?
   - Platform Admin
   - Company Admin
   - Company User (role: ___)
   - System / Job

2. Which company is in scope?
   - Single company only
   - Cross-company (❌ usually invalid)

3. Does this task cross an authority boundary?
   - [ ] Yes (must justify)
   - [ ] No

---

## SYSTEM_CONTEXT Compliance Checklist (MANDATORY)

For each item, answer **YES / NO / N/A**:

- [ ] Backend remains sole authority
- [ ] Frontend does not infer eligibility or permissions
- [ ] Tenant isolation preserved (`company_id`)
- [ ] Cold-start safe (works with 1 user, no seeded data)
- [ ] Null-safe reads (no assumed config rows)
- [ ] Disabled features are unreachable (not hidden)
- [ ] State machines are respected
- [ ] No immutability violations
- [ ] No silent fallback or cosmetic-only fix

If any answer is **NO**, you must STOP and explain why.

---

## Expected System Behavior (Observable)

Describe what must be true **after** this task:

- What new behavior should exist?
- What should be impossible?
- What should hard-fail?

Example:
> A company with only one user must see the Users menu and be able to invite users without any seeded quotas or plans. Attempting to assign an invalid role must fail server-side.

---

## Non-Goals (Explicit)

List things that must **not** be attempted:

- No schema changes
- No seeders
- No role expansion
- No UI-only enforcement
- No admin shortcuts

---

## Failure Modes to Actively Avoid

List at least 2 relevant failure modes from `INSTITUTIONAL_MEMORY.md`
(or state `N/A` if not loaded):

- …
- …

---

## Completion Criteria (Hard)

The task is **NOT DONE** unless:

- Code compiles
- UI is reachable via real navigation
- Permissions enforced server-side
- Cold-start path manually reasoned
- No instruction ends with “try refreshing / re-login”

---

## AI Output Contract (MANDATORY)

You must:

- Show **exact file paths**
- Explain **why each change is necessary**
- Call out **assumptions explicitly**
- Stop if information is missing
- Never declare completion without verification logic

You must NOT:

- Say “should work”
- Blame caching without proof
- Ask me to “check UI” as proof of correctness
- Proceed cosmetically

---

END OF CURRENT_TASK.md
