# TASK: Design Admin Disclosure UI From Canonical Disclosure Taxonomy

## Context
We have completed the **Company (Subscriber) Disclosure UI** using a **requirement-centric, backend-authoritative model**.

The backend is the **single source of truth** and returns **all disclosure requirements**, including:
- `not_started` items
- category
- tier
- `is_required`
- immutable audit timelines
- disclosure threads (submissions, decisions, timestamps)

The Company UI already consumes this correctly.

Now we need to derive the **Admin Disclosure UI** from the **same taxonomy and data**, with:
- **Zero workflow leakage**
- **Zero semantic drift**
- **Zero duplicated logic**

The Admin UI must be a **projection of the same disclosure graph**, not a new system.

---

## Goal
Design the **Admin Disclosure UI** (List View + Detail View) optimized for:

- Fast review
- Clear compliance signal
- Minimal narrative overhead
- Strong audit guarantees
- Decisive, regulator-grade language

---

## Hard Constraints (Non-Negotiable)

- Admin UI must project the **same disclosure requirements and threads**
- **No frontend inference**
- **No admin-only semantics**
- Backend enums, statuses, and timelines are rendered verbatim
- Immutable timestamps and threads must be visible
- Do **not** over-engineer

---

## Prime Directive
> **The Admin UI is a different lens on the same disclosure graph — not a different workflow.**

Same:
- requirement IDs  
- statuses  
- timelines  
- threads  

Different:
- emphasis  
- density  
- decisiveness  

---

## Deliverables Required From You

Provide a **precise UI design spec** covering:

1. **Mental model**
   - What the admin is actually doing (audit vs workflow)

2. **Information architecture**
   - Exactly two views only:
     - Disclosure List View
     - Disclosure Detail View

3. **Disclosure List View**
   - Purpose
   - Row model (1 row = 1 requirement)
   - Required columns and their backend sources
   - Allowed filters (strict)
   - Visual status → signal mapping (presentation only)
   - Explicitly forbidden UI elements

4. **Disclosure Detail View**
   - Layout from top to bottom
   - Immutable requirement header
   - Submission rendering rules
   - Disclosure thread rules (audit spine)
   - Admin decision panel
   - Required confirmation and notes
   - Explicitly forbidden actions

5. **Language system**
   - Regulatory, respectful, decisive phrasing
   - Examples of correct vs incorrect language

6. **Audit guarantees**
   - What the UI must prevent
   - What the UI must surface
   - Why this survives external audits

7. **Zero-drift contract**
   - Frontend responsibilities
   - Backend responsibilities
   - Rules that prevent semantic drift over time

8. **Explicit non-features**
   - Things that must NOT be built (scores, heuristics, dashboards, etc.)

9. **Litmus tests**
   - Simple yes/no tests to validate correctness of the design

---

## Style & Output Rules

- Write as a **senior systems / compliance architect**
- Be structured, explicit, and opinionated
- No fluff, no motivational language
- No references to “users” — use **Company** and **Admin**
- Avoid buzzwords
- Optimize for clarity and audit defensibility
- Markdown output only

---

## Framing Reminder
You are **not** designing an “admin dashboard”.

You are designing a **regulatory review instrument**.

It should feel closer to:
- A filings review console
- A compliance examiner’s desk

Than to:
- A SaaS admin panel

---

## Final Check
If the design allows:
- Admin inference without backend state → ❌ wrong
- Silent or implicit decisions → ❌ wrong
- Narrative summaries replacing raw facts → ❌ wrong

If the design allows:
- Fast scanning
- Decisive judgment
- Full audit traceability

→ ✅ correct

Proceed.
