● I now have full context from all the key files. Let me write the design spec and implementation plan directly to the plan file.

● Updated plan
  ⎿  /plan to preview

● The plan is written — a 9-section design spec plus implementation steps, grounded in the actual backend endpoints and data structures. The key decisions:

  - List View: Table-based (not cards), FIFO ordering, filters for status/tier/company, default to actionable items only
  - Detail View: 5 sections — header, structured content (not raw JSON), clarifications panel with accept/dispute, timeline from backend, status-aware decision panel
  - Backend: One new index() endpoint to return all disclosures (not just pending), existing endpoints unchanged
  - Zero drift: All state from backend, refetch after every mutation, no frontend business logic

────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────
 Ready to code?

 Here is Claude's plan:
╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌
 Admin Disclosure UI — Design Spec & Implementation Plan

 Context

 The Company (Subscriber) Disclosure UI is complete and uses a requirement-centric, backend-authoritative model. The backend is the single source of truth for all
 disclosure requirements, statuses, threads, and timelines.

 The existing Admin Disclosure UI (frontend/app/admin/disclosures/) is a placeholder: card-based pending list with raw JSON display. It lacks filters, timeline
 rendering, clarification workflow, start-review action, and proper decision language.

 This plan redesigns the Admin Disclosure UI as a regulatory review instrument — two views projecting the same disclosure graph the Company sees, with different
 emphasis, density, and decisiveness.

 ---
 1. Mental Model

 The admin is a compliance examiner, not a workflow operator.

 - Input: A company-submitted disclosure against a platform-defined requirement
 - Task: Examine the submission for completeness, accuracy, and compliance
 - Output: A decisive, recorded judgment — approve, reject, or request clarification
 - Constraint: Every action is immutable, timestamped, and attributable

 The admin does not author disclosures. The admin does not interpret data. The admin reviews and decides.

 ---
 2. Information Architecture

 Exactly two views:
 ┌───────────────────┬─────────────────────────┬────────────────────────────────────────────────────────┐
 │       View        │           URL           │                        Purpose                         │
 ├───────────────────┼─────────────────────────┼────────────────────────────────────────────────────────┤
 │ Disclosure List   │ /admin/disclosures      │ Scan all actionable disclosures across companies       │
 ├───────────────────┼─────────────────────────┼────────────────────────────────────────────────────────┤
 │ Disclosure Detail │ /admin/disclosures/[id] │ Examine one disclosure, review thread, render decision │
 └───────────────────┴─────────────────────────┴────────────────────────────────────────────────────────┘
 No other views. No dashboards. No aggregate pages.

 ---
 3. Disclosure List View

 Purpose

 Enable rapid triage: which disclosures need attention, in what order, and at what urgency.

 Row Model

 1 row = 1 CompanyDisclosure record. Each row represents one company's instance of one disclosure requirement.

 Columns
 ┌────────────────┬───────────────────────────────────────────────┬─────────────────────────────────┐
 │     Column     │                    Source                     │             Render              │
 ├────────────────┼───────────────────────────────────────────────┼─────────────────────────────────┤
 │ Company        │ company.name                                  │ Text                            │
 ├────────────────┼───────────────────────────────────────────────┼─────────────────────────────────┤
 │ Module         │ module.name (module.code)                     │ Text + code badge               │
 ├────────────────┼───────────────────────────────────────────────┼─────────────────────────────────┤
 │ Tier           │ module.tier                                   │ Badge: T1/T2/T3                 │
 ├────────────────┼───────────────────────────────────────────────┼─────────────────────────────────┤
 │ Status         │ disclosure.status                             │ Badge, color-mapped (see below) │
 ├────────────────┼───────────────────────────────────────────────┼─────────────────────────────────┤
 │ Submitted      │ disclosure.submitted_at                       │ Date, relative if <7d           │
 ├────────────────┼───────────────────────────────────────────────┼─────────────────────────────────┤
 │ Review Started │ disclosure.review_started_at                  │ Date or "—"                     │
 ├────────────────┼───────────────────────────────────────────────┼─────────────────────────────────┤
 │ Clarifications │ clarifications.total / clarifications.pending │ Count: "2/3 pending" or "—"     │
 ├────────────────┼───────────────────────────────────────────────┼─────────────────────────────────┤
 │ Action         │ —                                             │ Link: "Review"                  │
 └────────────────┴───────────────────────────────────────────────┴─────────────────────────────────┘
 Status Signal Mapping (presentation only)
 ┌────────────────────────┬─────────────┬────────────────────────┐
 │     Backend Status     │ Badge Color │         Label          │
 ├────────────────────────┼─────────────┼────────────────────────┤
 │ submitted              │ amber       │ Submitted              │
 ├────────────────────────┼─────────────┼────────────────────────┤
 │ resubmitted            │ amber       │ Resubmitted            │
 ├────────────────────────┼─────────────┼────────────────────────┤
 │ under_review           │ blue        │ Under Review           │
 ├────────────────────────┼─────────────┼────────────────────────┤
 │ clarification_required │ orange      │ Clarification Required │
 ├────────────────────────┼─────────────┼────────────────────────┤
 │ approved               │ green       │ Approved               │
 ├────────────────────────┼─────────────┼────────────────────────┤
 │ rejected               │ red         │ Rejected               │
 └────────────────────────┴─────────────┴────────────────────────┘
 Filters (strict)
 ┌─────────┬──────────────────────────────────────────────────────────────────────────────────┬───────────────┐
 │ Filter  │                                      Values                                      │ Backend Param │
 ├─────────┼──────────────────────────────────────────────────────────────────────────────────┼───────────────┤
 │ Status  │ submitted, resubmitted, under_review, clarification_required, approved, rejected │ status        │
 ├─────────┼──────────────────────────────────────────────────────────────────────────────────┼───────────────┤
 │ Tier    │ 1, 2, 3                                                                          │ tier          │
 ├─────────┼──────────────────────────────────────────────────────────────────────────────────┼───────────────┤
 │ Company │ Dynamic from data                                                                │ company_id    │
 └─────────┴──────────────────────────────────────────────────────────────────────────────────┴───────────────┘
 Default filter: status = submitted,resubmitted,under_review,clarification_required (actionable items).

 Sorting

 Default: submitted_at ASC (oldest first — FIFO review queue).

 Forbidden UI Elements

 - No checkboxes or bulk actions
 - No inline editing
 - No progress bars or completion percentages
 - No color-coded priority scores
 - No "quick approve" buttons on the list

 ---
 4. Disclosure Detail View

 Layout (top to bottom)

 4.1 Requirement Header (immutable)

 Renders the disclosure requirement identity. Never editable by admin.
 ┌───────────────────┬──────────────────────────────┐
 │       Field       │            Source            │
 ├───────────────────┼──────────────────────────────┤
 │ Company Name      │ company.name                 │
 ├───────────────────┼──────────────────────────────┤
 │ Lifecycle State   │ company.lifecycle_state      │
 ├───────────────────┼──────────────────────────────┤
 │ Module Name       │ module.name                  │
 ├───────────────────┼──────────────────────────────┤
 │ Module Code       │ module.code                  │
 ├───────────────────┼──────────────────────────────┤
 │ Tier              │ module.tier                  │
 ├───────────────────┼──────────────────────────────┤
 │ Current Status    │ disclosure.status (badge)    │
 ├───────────────────┼──────────────────────────────┤
 │ Version           │ disclosure.version_number    │
 ├───────────────────┼──────────────────────────────┤
 │ Submitted At      │ disclosure.submitted_at      │
 ├───────────────────┼──────────────────────────────┤
 │ Review Started At │ disclosure.review_started_at │
 ├───────────────────┼──────────────────────────────┤
 │ Submitted By      │ disclosure.submitted_by      │
 └───────────────────┴──────────────────────────────┘
 4.2 Disclosure Content

 The company's submission. Rendered as structured key-value display from disclosure.disclosure_data. If attachments exist, listed with download links.

 Not raw JSON. Render each top-level key as a labeled field. Nested objects rendered as indented sub-sections. Arrays as bullet lists.

 4.3 Clarifications Panel

 If clarifications exist, render each as a threaded item:

 For each clarification:
 - Subject (from question_subject)
 - Question (from question_body)
 - Type badge (from question_type)
 - Priority badge (from priority)
 - Status (open / answered / accepted / disputed)
 - Company Answer (from answer_body, if answered)
 - Admin Resolution (accept / dispute buttons if status = answered)

 Blocking clarifications marked with a distinct visual indicator.

 4.4 Timeline (audit spine)

 Chronological list of all disclosure events from GET /admin/disclosures/{id}/timeline.

 Each entry shows:
 - Timestamp (immutable, from backend)
 - Actor (name + type: Company / Admin)
 - Event type (submission, response, clarification_requested, approval, rejection)
 - Message text

 No frontend timestamps. No frontend-generated events.

 4.5 Admin Decision Panel

 Available actions based on current status:
 ┌─────────────────────────┬──────────────────────────────────────────────┐
 │     Current Status      │              Available Actions               │
 ├─────────────────────────┼──────────────────────────────────────────────┤
 │ submitted / resubmitted │ Start Review                                 │
 ├─────────────────────────┼──────────────────────────────────────────────┤
 │ under_review            │ Approve, Reject, Request Clarification       │
 ├─────────────────────────┼──────────────────────────────────────────────┤
 │ clarification_required  │ (waiting — no action until company responds) │
 ├─────────────────────────┼──────────────────────────────────────────────┤
 │ approved                │ None (read-only)                             │
 ├─────────────────────────┼──────────────────────────────────────────────┤
 │ rejected                │ None (read-only)                             │
 └─────────────────────────┴──────────────────────────────────────────────┘
 Approve: Optional notes textarea. Green button: "Approve Disclosure". Confirmation dialog: "This will lock this disclosure version and may trigger a tier transition.
 Proceed?"

 Reject: Required reason textarea. Red button: "Reject Disclosure". Rejection reason is mandatory.

 Request Clarification: Form to add clarification questions:
 - Subject (required)
 - Question body (required)
 - Type (dropdown: missing_data, inconsistency, insufficient_detail, verification, compliance, other)
 - Priority (dropdown: low, medium, high, critical)
 - Blocking (checkbox)

 Start Review: Single button: "Begin Review". Transitions status from submitted to under_review.

 Forbidden Actions

 - No inline data editing by admin
 - No status override without going through the workflow
 - No "undo" on any decision
 - No "draft" decision saving

 ---
 5. Language System

 Principles

 - Regulatory, not conversational
 - Decisive, not hedging
 - Respectful, not casual

 Examples
 ┌──────────────────────┬─────────────────────────────────────────────────┬───────────────────────────────────┐
 │       Context        │                     Correct                     │             Incorrect             │
 ├──────────────────────┼─────────────────────────────────────────────────┼───────────────────────────────────┤
 │ Approve button       │ "Approve Disclosure"                            │ "Looks Good" / "Accept"           │
 ├──────────────────────┼─────────────────────────────────────────────────┼───────────────────────────────────┤
 │ Reject button        │ "Reject Disclosure"                             │ "Decline" / "Send Back"           │
 ├──────────────────────┼─────────────────────────────────────────────────┼───────────────────────────────────┤
 │ Clarification        │ "Request Clarification"                         │ "Ask Question" / "Need More Info" │
 ├──────────────────────┼─────────────────────────────────────────────────┼───────────────────────────────────┤
 │ Start review         │ "Begin Review"                                  │ "Take a Look" / "Start"           │
 ├──────────────────────┼─────────────────────────────────────────────────┼───────────────────────────────────┤
 │ Status label         │ "Clarification Required"                        │ "Needs Info" / "Waiting"          │
 ├──────────────────────┼─────────────────────────────────────────────────┼───────────────────────────────────┤
 │ Rejection field      │ "Reason for Rejection"                          │ "Comments" / "Feedback"           │
 ├──────────────────────┼─────────────────────────────────────────────────┼───────────────────────────────────┤
 │ Empty clarifications │ "No clarifications requested"                   │ "All good!"                       │
 ├──────────────────────┼─────────────────────────────────────────────────┼───────────────────────────────────┤
 │ Confirmation         │ "This action is recorded and cannot be undone." │ "Are you sure?"                   │
 └──────────────────────┴─────────────────────────────────────────────────┴───────────────────────────────────┘
 ---
 6. Audit Guarantees

 What the UI must prevent

 - Admin rendering a status not returned by the backend
 - Admin taking an action not permitted by the current status
 - Any timestamp being generated or formatted by the frontend
 - Any decision being recorded without explicit admin confirmation

 What the UI must surface

 - Every timeline event with its immutable timestamp
 - Every clarification with its question, answer, and resolution
 - The admin who started review, the admin who decided
 - Edit tracking: if the company edited during review, show edit count and last-edit timestamp
 - Version number of the disclosure being reviewed

 Audit Defensibility

 Every decision screen shows: who (admin name), what (action taken), when (server timestamp), why (notes/reason). This chain is unbroken and immutable. An external
 auditor can reconstruct the entire review lifecycle from the timeline alone.

 ---
 7. Zero-Drift Contract

 Frontend Responsibilities

 - Render backend state verbatim
 - Map status strings to visual signals (badges, colors) — presentation only
 - Never compute, infer, or derive business meaning
 - Never cache decisions or statuses locally
 - Refetch disclosure state after every mutation

 Backend Responsibilities

 - Return all disclosure requirements, statuses, timelines, and permissions
 - Enforce all state transitions (frontend cannot skip states)
 - Return can_approve, can_reject flags in review summary
 - All timestamps generated server-side
 - All event records immutable once created

 Drift Prevention Rules

 1. Frontend must not contain if (status === 'X') then infer Y logic beyond visual mapping
 2. All action availability determined by backend response, not frontend status checks
 3. No localStorage or sessionStorage for disclosure state
 4. Every admin action triggers a full refetch of the disclosure detail

 ---
 8. Explicit Non-Features

 Do NOT build:
 - Disclosure completeness scores or progress bars
 - Risk heuristics or automated flagging
 - Aggregate dashboards (counts by status, charts, KPIs)
 - Batch operations (bulk approve, bulk reject)
 - Admin-to-admin assignment or routing
 - Notification preferences or digest settings
 - PDF export or report generation
 - Comparison views across companies
 - Search-by-content (searching disclosure text)
 - Auto-save of admin notes

 ---
 9. Litmus Tests
 ┌────────────────────────────────────────────────────────────────────┬──────────┬───────────────────────────────────┐
 │                                Test                                │ Expected │             If Failed             │
 ├────────────────────────────────────────────────────────────────────┼──────────┼───────────────────────────────────┤
 │ Can admin see a status not in the backend enum?                    │ No       │ Frontend is inventing states      │
 ├────────────────────────────────────────────────────────────────────┼──────────┼───────────────────────────────────┤
 │ Can admin approve without the backend permitting it?               │ No       │ Frontend is bypassing guards      │
 ├────────────────────────────────────────────────────────────────────┼──────────┼───────────────────────────────────┤
 │ Does every timeline entry have a server-generated timestamp?       │ Yes      │ Frontend is generating timestamps │
 ├────────────────────────────────────────────────────────────────────┼──────────┼───────────────────────────────────┤
 │ Can admin take an action without a confirmation step?              │ No       │ Silent decisions possible         │
 ├────────────────────────────────────────────────────────────────────┼──────────┼───────────────────────────────────┤
 │ Does rejecting require a written reason?                           │ Yes      │ Unattributed rejections possible  │
 ├────────────────────────────────────────────────────────────────────┼──────────┼───────────────────────────────────┤
 │ After approve/reject, does the page refetch from server?           │ Yes      │ Stale state risk                  │
 ├────────────────────────────────────────────────────────────────────┼──────────┼───────────────────────────────────┤
 │ Can admin see a disclosure's full history from the timeline alone? │ Yes      │ Audit trail incomplete            │
 ├────────────────────────────────────────────────────────────────────┼──────────┼───────────────────────────────────┤
 │ Is any business logic computed in the frontend?                    │ No       │ Authority violation               │
 └────────────────────────────────────────────────────────────────────┴──────────┴───────────────────────────────────┘
 ---
 Implementation Plan

 Backend Changes

 File: backend/app/Http/Controllers/Api/Admin/DisclosureController.php

 Add new index() method that returns all disclosures (not just pending) with the same filters + status filter support for all statuses. The existing pending()
 continues to work but index() becomes the primary list endpoint.

 Add route: GET /admin/disclosures → index()

 File: backend/routes/api.php

 Add the new route in the admin disclosure group.

 Frontend Changes

 File: frontend/app/admin/disclosures/page.tsx (currently redirects — rewrite)

 Rewrite as the Disclosure List View:
 - Table layout using shadcn Table component
 - Filter bar with dropdowns for status, tier, company
 - Default to actionable statuses
 - Row click → navigate to detail
 - Reuse existing api.get('/admin/disclosures') pattern

 File: frontend/app/admin/disclosures/pending/page.tsx

 Keep as-is or redirect to /admin/disclosures?status=submitted,resubmitted,under_review,clarification_required. Can be deprecated.

 File: frontend/app/admin/disclosures/[id]/page.tsx

 Rewrite with the 5-section layout:
 1. Requirement header card
 2. Disclosure content card (structured rendering, not raw JSON)
 3. Clarifications panel (threaded, with accept/dispute actions)
 4. Timeline card (chronological events from /timeline endpoint)
 5. Decision panel (status-aware action buttons with confirmation dialogs)

 Verification

 1. Navigate to /admin/disclosures — verify table renders with all columns
 2. Apply status filter — verify list updates correctly
 3. Click a disclosure row — verify detail page loads all 5 sections
 4. On a submitted disclosure — verify only "Begin Review" is available
 5. Click "Begin Review" — verify status transitions to under_review
 6. On under_review — verify Approve, Reject, Request Clarification all available
 7. Reject without reason — verify rejection is blocked
 8. Approve — verify confirmation dialog appears, disclosure locks after approval
 9. Request clarification — verify form submits, status changes to clarification_required
 10. Check timeline — verify all events appear with server timestamps
╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌

 Claude has written up a plan and is ready to execute. Would you like to proceed?

 > 1. Yes, clear context and auto-accept edits (shift+tab)
   2. Yes, auto-accept edits
   3. Yes, manually approve edits
   4. Type here to tell Claude what to change