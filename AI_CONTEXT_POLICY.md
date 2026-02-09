# AI Context Loading Policy — PreIPOsip

This document defines **which context files must be loaded** when using AI
(Claude, Gemini, ChatGPT, etc.) on this repository.

Failure to follow this policy will result in incomplete or unsafe outputs.

---

## Context Files & Authority Levels

### 1. SYSTEM_CONTEXT.md (LAW)
**Authority:** Highest  
**Purpose:** Defines non-negotiable system laws

- Contains:
  - Authority boundaries
  - Financial invariants
  - Cold-start guarantees
  - Tenant isolation rules
  - State machine constraints
- Violation of any rule here means the system is **incorrect or unsafe**

This file must be loaded for **all AI coding tasks**.

---

### 2. INSTITUTIONAL_MEMORY.md (EVIDENCE)
**Authority:** Informational  
**Purpose:** Preserves historical failures and rationale

- Contains:
  - Known failure modes
  - Anti-patterns
  - Debugging lessons
  - Tooling and AI pitfalls
- Explains *why* SYSTEM_CONTEXT rules exist
- Not enforceable law

This file is loaded **only** for:
- Debugging
- Audits
- Refactors
- Architecture changes
- Post-mortem analysis

---

### 3. README.md (REFERENCE)
**Authority:** Descriptive  
**Purpose:** Implementation guide & feature inventory

- Describes:
  - Modules
  - APIs
  - Tables
  - Setup steps
- Must never override SYSTEM_CONTEXT rules
- May be incomplete or lag behind enforcement rules

---

### 4. CLAUDE.md (TOOLING)
**Authority:** Operational guidance  
**Purpose:** Helps AI navigate the repo

- Describes:
  - Tech stack
  - Commands
  - Folder structure
  - Repo conventions
- Does not define system behavior

---

## Mandatory Loading Rules

### A. Standard Coding Task (default)

Use this for:
- Feature implementation
- Bug fixes
- UI work
- API wiring

cat SYSTEM_CONTEXT.md CURRENT_TASK.md | claude

or

cat SYSTEM_CONTEXT.md CURRENT_TASK.md | gemini

B. Debugging / Refactor / Audit Tasks
Use this when:
- Fixing runtime errors
- Investigating regressions
- Refactoring core flows
- Touching financial, auth, or onboarding logic

cat SYSTEM_CONTEXT.md INSTITUTIONAL_MEMORY.md CURRENT_TASK.md | claude

C. Repo Orientation / Exploration
Use this when:
- Asking AI to understand the codebase
- Planning work
- Reviewing architecture

cat SYSTEM_CONTEXT.md CLAUDE.md README.md CURRENT_TASK.md | claude

D. Task Size Policy

- Use CURRENT_TASK_LITE.md for:
  - UI bugs
  - Simple API fixes
  - Null checks
  - Error handling
  - Non-financial, non-auth issues

- Use CURRENT_TASK.md for:
  - Auth, RBAC, onboarding
  - Financial logic
  - Cross-module changes
  - Schema or lifecycle changes
  - Any task that previously failed

If unsure, default to CURRENT_TASK.md.

Prohibited Patterns
- ❌ Loading INSTITUTIONAL_MEMORY.md for routine feature work
- ❌ Allowing README.md to override SYSTEM_CONTEXT.md
- ❌ Asking AI to infer authority without explicit context
- ❌ Mixing governance, tooling, and law into a single file

Final Rule
If two documents conflict:

SYSTEM_CONTEXT.md > INSTITUTIONAL_MEMORY.md > README.md > CLAUDE.md
SYSTEM_CONTEXT.md always wins.

END OF AI_CONTEXT_POLICY.md