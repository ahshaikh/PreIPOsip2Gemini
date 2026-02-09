# SYSTEM_CONTEXT.md — PreIPOsip

## System Overview

PreIPOsip is a pre-launch financial platform for pre-IPO investments. The system architecture is built on the principle of **Admin Supremacy**, where a central Admin Panel acts as the control plane for all business-critical behavior. The platform is undergoing a hardening process to achieve a regulator-grade, audit-complete state. The system enforces a strict separation of concerns: a server-driven financial authority (backend) and a frontend that acts as a declarative, non-authoritative terminal. The system enforces immutability for financial records and investor-facing snapshots, with a double-entry ledger as the single source of financial truth. A formal "Audit Line" has been declared as of 1 February 2026, freezing all legacy schema and enforcing forward-only migrations for future changes. The system state is formally declared AUDIT-COMPLETE, covering EPICs 1–6. Future changes must be introduced only as new epics; modification of historical behavior or audit surfaces is disallowed.

---

## Business Rules

### Admin Authority & Control

- All business-critical behavior (user registration, plans, bonuses, KYC flows, payments, withdrawals, feature toggles) must be enableable/disableable at runtime from the admin panel.
- Admin has absolute runtime authority and can override automated flows (payments, KYC, allocations, bonuses, scheduled jobs), with all overrides being logged.
- User lifecycle is admin-governed. Users can be suspended, reactivated, or restricted by admin regardless of automated state.
- Admin power is bounded: admins cannot bypass ownership, disclosure, or audit rules. All admin actions affecting products or disclosures must be auditable.
- Admin role is governance, not authorship. Admins may review, approve, reject, or override in exceptional cases, but must not create or edit product content as a primary actor.
- Admin actions represent platform authority, not issuer data, and must always surface investor impact and snapshot immutability.

### Feature Toggles

- Feature toggles are first-class business controls. Registration, referrals, bonus types, lucky draws, withdrawals, and KYC flows must all be controllable via toggles.
- When a feature is disabled, the system must hard-block the flow (not soft-hide UI). Disabled features must be unreachable at all layers (UI, API, background jobs).
- Feature flags over code changes: operational changes must be handled via feature flags/settings, not redeployments.

### Plan Mutability

- Investment plans are fully mutable by admin (create, modify, disable, extend), including all parameters (price, duration, bonus %, referral multiplier, eligibility rules).
- Changes must not require code changes or redeployments.
- Plan changes must be versioned and auditable. Disabling a plan blocks new subscriptions but must not silently corrupt existing ones.
- Direct destructive edits to live plans without version history are disallowed.

### Product & Deal Lifecycle

- Products are authored exclusively by companies (issuers), never by admins.
- Product lifecycle follows a strict state machine: `draft` → `submitted` → `approved` / `rejected`. `submitted` → `rejected` → `draft` is also valid. Any other transition must throw.
- "Approved" is the investable state, not "active". The `active` label is not part of the domain lifecycle and must not be used.
- A product may have only one active deal window at a time. Overlapping active deals for the same product are forbidden.
- Product visibility and sellability are gated by approval, not by creation. Creation ≠ approval.
- Admin-led product creation is a confirmed architectural violation and must be removed or disabled.
- Dual authorship (Admin + Company) is rejected. There must be exactly one author class for products — CompanyUser.

### Financial Authority & Ownership

- The backend is the sole financial authority. All monetary limits, eligibility, approvals, SLAs, and compliance decisions are computed and enforced server-side.
- The frontend is a declarative terminal. The client must never decide or infer financial rules; it only requests previews/quotes and renders server decisions.
- There must be a single authoritative source of truth for money and liabilities; user-visible balances are derived views, not primary truth.
- User trust is a binary business property: any state where funds are deducted without deterministic allocation or reversal is unacceptable.
- Financial operations must be explainable post-facto with deterministic state transitions suitable for audit narratives.
- Performance optimization is explicitly deprioritized in favor of correctness, determinism, and auditability.

### Platform Revenue Model

- Platform revenue is earned only from bulk-purchase discounts on pre-IPO shares sold to users.
- No user-paid amount becomes platform revenue unless it is directly tied to a share sale margin.

### Subscription Semantics

- Subscription grants entitlement (access, permission, participation rights) to buy pre-IPO shares via the platform.
- Subscription does not represent a fee charged for service.
- Subscription funds remain user-owned capital, not platform income. Platform acts as a custodian, not an owner, of subscription funds.

### Investment Lifecycle

- Deposit → wallet credit → share purchase → demat transfer.
- No intermediate step may recognize revenue or cost incorrectly.

### Revenue Recognition

- Recognize income only on share sale (`SHARE_SALE_INCOME`).
- Recognizing income on subscription payment or wallet top-ups is explicitly rejected.

### Investor Transparency & Defensibility

- An investment is valid only if the platform can later reconstruct exactly: what the investor saw, what risks were disclosed, what acknowledgements were accepted.
- Platform context (risk assessments, rationale, mitigation guidance) is platform-owned and must not be editable or influenced by issuing companies.
- Investor-facing presentation must clearly distinguish "Platform says" from "Company says" through data ownership, not UI inference.
- Investors must be able to see: platform risk rationale (why a risk exists) and snapshot-based comparisons of "state at investment" vs "current state". These are transparency and audit functions, not optional UX features.
- Platform authority must always be explicitly labeled and never visually conflated with issuer or investor data.
- Historical investor snapshots are immutable and cannot be altered by admin, issuer, or system actions after creation.

### Platform Supremacy Over Company Data

- The platform (admin) is the sole authority controlling company visibility, governance state, and investor eligibility.
- Companies (issuers) can submit, correct, and clarify information but cannot override platform decisions.
- Issuer UI is subordinate to platform governance; issuers may submit and correct information but cannot affect visibility, tiering, or investor impact.

### Consent & Disclosures

- Signup requires explicit, consolidated consent to linked legal documents (ToS, Privacy Policy, etc.). A single checkbox accepting multiple linked legal documents is acceptable and defensible when properly versioned and auditable.
- Consent records must store document IDs, versions, timestamp, and user identifier.
- Signup consent must be explicit but not coercive. Users must not be forced to scroll or read entire documents to proceed.
- Consent must be progressive across the user lifecycle. Detailed or high-friction disclosures must be deferred to later lifecycle stages (e.g., KYC completion, first transaction), not front-loaded at signup.
- A company can only have one disclosure per disclosure module at any time.
- Auto-approval, auto-acknowledgement, or inferred investor consent is forbidden.
- Investor-facing actions require explicit, provable consent tied to disclosures, risk acknowledgements, and immutable snapshots.

### Compliance & Governance

- The system must enforce regulator-grade guarantees at the database level.
- An "Audit Line" is effective as of 1 February 2026; anything prior is considered legacy.
- The system does not claim historical perfection. It explicitly claims provable correctness from the audit line onward.
- Governance rules apply equally to humans and AI. Governance is not model-specific and not advisory. Rules bind developers, AI agents, scripts, migrations, and CI.
- Compliance controls (audit trails, immutability, state machines) are non-optional. A system that runs without them is considered broken, even if migrations succeed.
- Failure is preferable to partial compliance. A hard failure is an acceptable and correct outcome when prerequisites are not met.
- Developer velocity must not override regulatory correctness. Convenience fixes that weaken guarantees are invalid.
- Governance is established through governance markers (commit + tag + policy), not by rewriting history.

### Security & Risk

- Pre-IPO investments are illiquid, high-risk assets and must be framed as such to end users.
- No pre-IPO investment may be marketed with implicit or explicit guarantees of IPO gains.
- CAPTCHA is a bot-mitigation control, not an identity control. It must be applied in a risk-based or conditional manner.
- Pre-IPO investments must be evaluated primarily on downside protection, not upside narratives.
- Consumers must have instrument-level clarity (equity vs CCPS vs SPV, etc.) before investing.
- Absence of legal documentation (SHA, rights, disclosures) is a hard stop, not a risk trade-off.
- Portfolio allocation to pre-IPO investments must be capped at 10–15% of net worth.
- Investment horizon for pre-IPO assets is defined as minimum 3–5 years.
- Lack of transparency from company or platform is treated as a negative signal, not neutral.

### Public Frontend

- Public-facing content is informational only and must never imply solicitation, investment availability, pricing, or eligibility.
- Public pages may show existence and high-level information only. No pricing, valuation, funding, risk flags, or buy signals are ever allowed publicly.

### Backward Compatibility

- Existing API consumers must never break due to enrichment. All fixes must be additive, not destructive.

### Bonus Logic

- Bonus formulas, milestones, multipliers, and schedules are business rules, not developer logic. All bonus logic must be configurable, not hardcoded.
- Bonus computation must fail safely if a bonus type is disabled.

### Documentation Hierarchy

- A strict documentation hierarchy is enforced: Architecture > Governance > Project Context > README.
- The README is a public-facing developer document and holds no architectural or governance authority.
- README must not explain governance hierarchy or enforcement rules.

### Audit Views

- Audit views are read-only evidence surfaces, not control planes. They exist to reflect historical truth, not to manage state.
- Audit access is restricted to admin/compliance roles only; audit surfaces must never appear for issuer or investor roles.

### Education / Learning Center

- The Learning Center exists to educate users before they invest, not as marketing content.
- Tutorials are educational only and must explicitly avoid being framed as investment advice.
- Users are responsible for their own investment decisions; the platform provides access and education, not guarantees or recommendations.
- Education precedes monetization: users must understand pre-IPO fundamentals before being encouraged to invest.

---

## Module Rules

### Admin Panel

- The Admin Panel is the core control plane of the system, not a secondary or cosmetic feature.
- All critical system behavior must be surfaced here with appropriate role-based access control (RBAC).
- All admin mutations require role checks (RBAC). Critical actions require enhanced confirmation (and optionally 2FA).
- Admin navigation must group all audit routes under a single "Audit & Compliance" section, visually separated from operational admin menus.

### System Settings

- All toggles and configuration values must be stored in the database (not hardcoded or env-only).
- Using `.env` for runtime business toggles is prohibited.
- Settings must be readable at runtime and cached with immediate invalidation on change.

### Authentication & Authorization

- Registration endpoint must hard-block when `registration_enabled = false`. UI hiding alone is insufficient; API guards are mandatory.
- Password reset must be supported via secure email link.
- OTP verification and optional 2FA are required.
- Any code path that invokes `$this->authorize()` must guarantee the method's availability at runtime.
- Traits calling `authorize()` must include the `AuthorizesRequests` trait, regardless of controller inheritance.
- JSON API behavior in Laravel depends on request headers (`Accept: application/json`); behavior may differ from browser/web requests.
- CAPTCHA must be applied in a risk-based or conditional manner.
- reCAPTCHA v3 (score-based) is preferred over visible challenges. Backend must validate CAPTCHA tokens and decide outcomes based on score thresholds. Different actions (signup, login, OTP) must use distinct action names and thresholds.

### Plan & Subscription Module

- Plan calculations must be parameter-driven (no fixed constants in code).
- Plan changes must support versioning and optional application to existing subscriptions.
- Disabling a plan blocks new subscriptions but must not silently corrupt existing ones.

### Bonus Engine

- All bonus formulas must be configurable and individually enable/disable-able.
- Bonus calculations must read from admin-configured rules.
- No bonus logic may be hardcoded.
- Must support manual recompute and admin-triggered runs.
- Bonus computation must fail safely if a bonus type is disabled.
- All bonuses and profit-share incentives must follow the same invariant: accrual to liability → transfer to wallet.
- All bonus services must calculate TDS and post the required three-leg ledger entry.
- Profit share is treated as a bonus-class incentive, not a separate economic model.

### Payments & Wallet Module

- All transactions must be ledger-based and auditable.
- Webhook reconciliation is mandatory; manual admin reconciliation must exist.
- Payment methods must be dynamically enabled/disabled from admin. Disabled methods must not appear in checkout or be accepted via API.
- Preview/Quote precedes execution: any withdrawal (and sensitive financial action) must require a server-issued preview/quote before execution.
- Execution must validate preview reference. Withdrawal execution requests without a valid server-issued preview reference must be rejected.
- KYC gates financial capabilities. Withdrawal capability is disabled until KYC is verified; this is enforced server-side.
- WalletRulesService is the single source of truth. All controllers (rules, preview, execution) must call a centralized service to avoid duplicated or drifting logic.
- Client-side validations are UX-only. HTML input attributes (min/max/step) may exist for user guidance but never replace backend validation.
- No mock or fallback data in production paths. UI must show explicit loading/error states; silent defaults (e.g., balance = 0) are disallowed.

### Double-Entry Ledger

- Ledger is the single source of financial truth.
- Every financial event must produce a balanced ledger entry (debits = credits).
- Ledger entries are immutable after creation.
- Inventory creation must be linked to a platform ledger debit.
- Ledger logic is additive; no refactors of existing allocation services.

### WalletService

- WalletService may move entitlements operationally but must never invent money.
- Wallet balance is treated as a materialized projection of ledger state and must reconcile with ledger totals.

### TransactionType Discipline

- Every TransactionType maps to exactly one economic meaning.
- `SUBSCRIPTION_PAYMENT` must never be grouped with `INVESTMENT`.
- String-based transaction types are legacy and must not be extended.

### KYC Module

- KYC is a state machine: pending → approved / rejected.
- Admin bulk actions are required.

### Audit Logging

- Every admin mutation must write an immutable audit record with old/new values.
- `actor_type` is mandatory and non-null for every audit log entry; system, seeder, or service actions must explicitly set it.
- Audit logs must explicitly distinguish actor types (e.g., USER, ADMIN, SYSTEM, SEEDER, SERVICE); no nullable or default fallbacks are allowed.

### Products & Inventory

- Every product must be owned by a company (`company_id` is mandatory and non-nullable). Orphan products are invalid.
- `company_id` is derived from the authenticated CompanyUser. It must never be accepted from request payloads.
- Product creation must originate from `Company\ProductController`. `POST /admin/products` must not exist.
- Products must always belong to a company. Creation flow is single-source (issuer portal only).
- CompanyUsers may edit only their own products in `draft` or `rejected`. Submitted or approved products are read-only for CompanyUsers.
- Admin override on products must be explicit, field-limited, justified, and audited.
- Admin endpoints must not bypass validation or ownership rules.
- Inventory (Bulk Purchases) must have clear provenance (source, ownership, approval). Inventory without provenance is invalid.
- Bulk purchases must record source type, owning company, admin approval, and verification metadata.
- For `company_listing` source type, `company_share_listing_id` is mandatory and must reference an approved listing.
- Manual inventory entries require `manual_entry_reason` and `source_documentation`.
- Inventory creation must be financially atomic and ledger-linked.
- A product cannot be approved unless backing inventory exists.
- All inventory must have provenance. Seeded inventory must originate from company_listing provenance, not manual_entry.
- Visibility is derived from the owning company's disclosure tier.

### Companies

- Companies must have exactly one `disclosure_tier`.
- `disclosure_tier` is immutable except via the authoritative promotion service.
- Public visibility requires `disclosure_tier >= tier_2_live`.
- Global scopes enforce visibility by tier at query level.

### Deals

- Deal creation allowed only if `company.disclosure_tier >= tier_1_upcoming`.
- Deal activation allowed only if `company.disclosure_tier >= tier_2_live`.
- Featured status allowed only if `company.disclosure_tier >= tier_3_featured`.
- Enforcement occurs via model hooks with hard exceptions.
- Deal creation enforces a runtime guard against date overlap with existing active deals for the same product.

### Company Share Listings

- Listings require title, description, pricing, valuation, terms, and validity window at creation.
- Share listings must be submitted by `company_users`, not platform users.
- `company_share_listings.submitted_by` must always reference a valid `company_users.id`.

### Disclosures

- Disclosures are versioned. `(company_id, disclosure_module_id)` is a unique key.
- Updates are in-place with an incremented `version_number`, never as new rows.
- Immutable disclosure versions cannot be modified; attempts must be blocked and audited.
- Disclosure data represents company attestations and must originate from the Company side.
- Disclosure visibility is authority-bound. Investors may only see disclosures explicitly approved for investor visibility.
- Disclosure error reports must never mutate approved disclosures. Corrections are handled via new disclosures linked to error reports.

### Issuer Role Model

- Company users have scoped roles (`founder`, `finance`, `legal`, `viewer`) with clearly bounded permissions.
- Only one active role per user per company is allowed.

### Orchestration

- All multi-step financial or inventory flows must be coordinated through a central orchestration authority responsible for ordering, state tracking, and recovery triggers.
- The orchestration layer may manage state and sequencing only; it must not embed business logic such as pricing, eligibility, or campaign rules.
- Every asynchronous financial action must define a compensation (undo) path before it is allowed to ship.
- Failure handling is a first-class requirement: error paths must be explicitly implemented, not inferred or left to defaults.

### Database & Migrations

- Migrations must be deterministic. The same migration set must always produce the same schema regardless of environment.
- DDL-only rule for migrations. No runtime DML (logging, inserts, side effects) inside schema migrations.
- Explicit prerequisite enforcement. Migrations may assume required tables exist; they must fail if prerequisites are missing.
- No conditional schema logic for governance controls. Triggers, constraints, and state machines must be created unconditionally.
- Foreign key integrity must be respected during cleanup. Child FKs must be removed before dropping parent tables after partial migrations.
- Index and constraint identifiers must respect MySQL/MariaDB limits. Explicit, short names are required for composite indexes.
- Legacy schemas (pre-audit-line) are frozen and must not be edited: no edits, no backfills, no in-place constraint changes.
- Any correction must be forward-only or via new V2 tables.
- `ON DELETE SET NULL` requires a nullable column. Any FK using `nullOnDelete()` must have the column explicitly declared `nullable()`.
- Approval timestamps (`approved_at`) must be nullable and set explicitly on approval. Never use implicit or automatic timestamps for approval semantics.
- Database migrations define enforceable truth; application logic cannot compensate for missing schema guarantees.
- ENUM and schema values must be matched exactly. Inserting values not defined in ENUM columns causes truncation warnings and runtime failures.

### Seeders

- Seeders must be idempotent, obey all domain invariants, and use `updateOrCreate()` to avoid duplicate entries.
- Seeders must not bypass state machines or provenance checks.
- Seeders must follow the same lifecycle as production workflows.
- Seeders must create placeholder entities (e.g., dummy company) rather than violate ownership rules.
- Weakening database constraints or guards to "make seeders pass" is explicitly rejected.
- Seeders must never assume a clean database state.
- Use `updateOrCreate` for credential-bearing models. `company_users` creation must always include required credentials (e.g., password) to avoid partial inserts.

### API & Networking

- The backend and frontend must share an explicit, contractually aligned API path structure (including versioning and admin scoping).
- Axios `baseURL` concatenation rules are strict: if `baseURL` ends with `/`, request paths must not start with `/`. If `baseURL` does not end with `/`, request paths must start with `/`.
- Frontend Axios configuration is standardized to `baseURL = http://localhost:8000/api/v1/` with all request paths as relative without a leading slash.
- Admin APIs must require authenticated admin context to return JSON responses.
- Admin API endpoints may intentionally return `404` or `401` depending on request headers and authentication context.

### Frontend & UI

- The frontend is a declarative terminal and must not own business meaning or logic.
- UI components must not determine workflow outcomes. No `.tsx` file may determine workflow outcomes.
- The Next.js App Router filesystem is the single source of truth for frontend routes.
- Frontend wiring may only occur when backend contracts fully satisfy component prop requirements.
- Adapters, mappers, or semantic reinterpretation in the frontend are forbidden.
- Component prop interfaces are authoritative declarations of required data. If backend APIs do not satisfy them, the backend must move—not the component.
- Contexts may expose state, not orchestrate lifecycles. React Contexts must not manage authentication timelines, side effects, redirects, or persistence logic.
- Layouts, navbars, and guards are structurally high-risk. These modules must be treated as infrastructure and audited for logic gravity.
- Policy logic must be pure and relocatable. Compliance, entitlement, and eligibility logic must exist in policy engines, services, or hooks—never embedded in JSX.
- Frontend files must have a single reason to change. UI changes must not require modification due to business-rule changes.
- Business rules must not be duplicated across frontend and backend.

### Audit Components & API

- All audit components (`frontend/components/audit/*`) are strictly read-only: no edit, approve, reject, or mutate actions. Copying identifiers (e.g., snapshot hash) is allowed; modification is not.
- The audit API client (`auditApi.ts`) is GET-only: POST/PUT/PATCH/DELETE are forbidden. Data is treated as immutable from the client's perspective.
- Audit timestamps must always display UTC time as primary authority with local time as supplementary context.
- Audit layouts and routes must exist under a dedicated audit route group and layout that clearly signals a read-only zone.

### Snapshot Safety

- Any "then vs now" comparison must use immutable snapshot data for "then" and live data for "now". Reconstruction from live state is not acceptable.

### Legal Documents

- All legal documents must be versioned and retrievable by ID and version.
- Forced scrolling or sequential document rendering is not required for valid consent.

### Notifications

- Push notifications require pre-production testing. Notification delivery must be tested in sandbox/dev environments before any production send.
- Notification providers must support sandbox/test modes. Separate dev/staging/prod Firebase projects are required.

### Documentation Pipeline

- When updating README.md: existing sections must not be renamed, reordered, or removed. New information may only be added inside the most semantically equivalent existing section.
- Feature states must be communicated using light qualifiers: Allowed: `(Admin-only)`, `(Manual)`, `(Configurable)`, `(Partial)`. Disallowed: "Not compliant", "High risk", "Architectural debt".
- AI-assisted documentation updates must be explicitly constrained to prevent structural drift.

### Company Portal Routing

- The Company Portal's user-visible navigation and URLs are entirely defined by the filesystem under `/frontend/app/company`, following Next.js App Router conventions.
- Company-facing "Products" do not exist as a first-class concept in the current system. The authoritative business concept is "Deals", exposed at `/company/deals`.
- "Create Deal" is not a standalone route. Creation occurs via tabbed UI inside `/company/deals`.
- Reporting for company users is split by intent: financial data → `/company/financial-reports`, analytical/metrics data → `/company/analytics`. There is no canonical `/company/reports` route.
- A 404 response for a non-existent `/company/*` route is a healthy state, not an error condition.

### Tutorials / Learning Center

- Only tutorials with `Status = Published` are visible to end users.
- Tutorials are organized primarily by Category + Difficulty, not chronology.
- No tutorial may imply guaranteed returns, guaranteed IPOs, liquidity certainty, or platform-backed investment advice.
- Beginner tutorials must assume zero prior knowledge.
- Pre-IPO investing must always be described as: illiquid, long-term, high-risk, exit-uncertain.

---

## System Invariants

### Admin Control

- No business-critical behavior without admin control. If a feature exists, the admin must be able to control it.
- No runtime change requires code changes. Any business rule change must be achievable via admin UI.
- Disabled means unreachable. A feature disabled in the admin panel must be unreachable at all layers (UI, API, background jobs).
- Admin actions are traceable. Every admin change must be attributable to a user and timestamp.
- An Admin cannot create or freely edit product data.
- A CompanyUser cannot approve or bypass review.
- Every admin override must be auditable and attributable.
- Invalid states must be structurally impossible, not merely discouraged.

### Financial Integrity

- There must never be more than one authoritative accounting boundary for funds or liabilities.
- Every financial state transition must be traceable to an orchestrated flow and a ledger entry.
- Money cannot move without a corresponding ledger entry.
- Ledger must be able to explain: what is owned, what is owed, what is earned, what is spent.
- No financial decision originates on the client. Violating this is a security and compliance failure.
- Backend re-validates everything at execution time, even after a successful preview.
- No financial state transition may be untraceable.
- A user-facing state must never represent a completed action unless all required downstream effects (allocation, accounting, compliance) are either completed or safely compensable.
- Failure must result in a recoverable or halted state, never in a silent partial success.
- No flow may credit `USER_WALLET_LIABILITY` more than once for the same economic event.
- Legacy single-entry ledger must never be written to.
- `SUBSCRIPTION_PAYMENT` must never trigger `SHARE_SALE_INCOME` or `COST_OF_SHARES`.
- `COST_OF_SHARES` may be posted only during: bulk purchase, bonus usage, or investment reversal (refund).

### Immutability

- Ledger entries and investor snapshots must be immutable once created.
- Investor snapshots must never be mutated or recomputed. Historical investor state must always remain reconstructable.
- Platform context used at investment time must be snapshotted and immutable. Any comparison must reference this snapshot.
- Investment snapshots must be hash-verified and immutable once created.
- Past investor snapshots are immutable and must never be silently altered.
- Immutability violations must be blocked and recorded; silent failures are not permitted.

### Ownership & Provenance

- A product must never exist without a company owner. Orphan products are invalid and non-compliant.
- No inventory may exist without provenance and approval.
- No listing without a company actor. `company_share_listings.submitted_by` must always reference a valid `company_users.id`.
- A Product cannot exist without a Company.
- A Product cannot be approved without submission.

### Authority Boundaries

- The frontend must not fabricate or reinterpret backend data. All semantics must originate from persisted backend fields.
- Admins must never be the source of regulated financial disclosures. All disclosures must be attributable to a company actor.
- Companies cannot create, edit, or influence platform context fields. Only platform/admin-controlled services may mutate platform context.
- Approval is a state transition, not data creation. Approval actions may only change status, never authorship.
- Visibility and investability depend on disclosure state, not admin intent. Manual admin creation or activation cannot override disclosure-based visibility rules.
- No frontend may infer eligibility, disclosure tiers, or authority; all such states are rendered strictly from backend data.
- No frontend may bypass backend governance guards.
- No frontend may contradict another role's view of the same company.

### Disclosure & Compliance

- `(company_id, disclosure_module_id)` uniqueness must never be violated.
- No two active deals for the same product may overlap in time.
- All audit log entries must include a valid `actor_type`.
- No public visibility without `disclosure_tier >= tier_2_live`.
- No deal, product, or inventory object may exist outside compliance tiers.
- No direct mutation of disclosure tier, deal state, or inventory provenance.
- Compliance depends on what the user saw and acknowledged, not only backend enforcement.
- No investment without backing inventory. Every deal and subscription must be backed by inventory with positive remaining value.

### State Machine Enforcement

- Product lifecycle transitions must be explicit and ordered; skipping states is forbidden.
- Products must move step-by-step through allowed states: `draft` → `submitted` → `approved` | `rejected`.
- State transitions must be explicit and ordered.

### Auditability & Traceability

- Every admin action and financial mutation must be auditable, attributable, and logged.
- All platform context and snapshot data must be attributable, timestamped, and retrievable for dispute resolution.
- Every investor action must be reconstructible to show: what was displayed, what was acknowledged, who acted, why it occurred, when it occurred.
- Consent must always be auditable. Every acceptance must be traceable to specific document versions and timestamps.
- Auditability over convenience. Decisions must be explainable and logged server-side.
- Audit navigation must never link to mutating admin pages or imply control capabilities.

### Rendering & Frontend

- Render trees must not encode business law. JSX may render outcomes but must never define "why" those outcomes exist.
- High-level frontend components (layouts, navbars, guards, providers) must remain policy-agnostic.
- Authority must be singular and explicit. Business rules must not be duplicated across frontend and backend.
- Filesystem is the single source of truth for company routes. URLs must never be invented or inferred without a corresponding folder.

### Security

- Security controls must be proportional to risk. CAPTCHA and friction must scale with threat level, not be uniformly applied.
- Secret keys (e.g., reCAPTCHA) must never be exposed client-side.
- Admin, user, and system roles must be isolated. Feature toggles must block APIs, not just UI.

### Safe Cold Start

- System must boot with sane defaults even if no plans or features are enabled.
- System must boot with default plans and disabled risky features if not configured.

### Environment Separation

- Dev/staging notifications and financial actions must not reach production users.

### Audit Boundary

- No claim of regulator-grade correctness is made for data or schema prior to the audit line (1 February 2026).
- All guarantees apply only after the audit line.
- All future correctness guarantees must be enforced via new migrations, new tables, explicit provenance. Never via mutation of legacy artifacts.
- Governance commits must contain only declarative policy. Never include schema, logic, or data changes.

### API Integrity

- Admin API requests must reach Laravel with correct path resolution (no double slashes), include appropriate authentication context, and be treated as JSON requests when invoked from the frontend.
- Any code path that invokes `$this->authorize()` must guarantee method availability at runtime.
- Route cache success (`php artisan route:cache`) implies no duplicate route names and valid route definitions.

### Documentation

- README.md must never change its document type implicitly (e.g., from developer guide → audit report).
- Governance must be model-agnostic. No AI-brand-specific governance rules may exist.
- No competing sources of authority. If documents conflict, Architecture > Governance > Context.
- Explicitness over assumption. Document role and authority must be stated, not inferred.
- `composer.json` and `composer.lock` must always be consistent.
- Generated artifacts (`vendor/`, `node_modules/`, `bootstrap/cache/*`, `.env`) are never committed.

### Consumer Protection

- Legal clarity must precede financial analysis.
- Downside scenarios must always be disclosed alongside upside narratives.
- Illiquidity must always be assumed by default.
- Valuation must never be justified solely by future IPO expectations.
- Consumer communication must avoid FOMO-driven framing.

---

## Known Failure Modes

### Financial & Accounting

- Treating subscription payments as revenue or investments caused false income recognition, profit overstatement, and audit risk.
- Grouping `SUBSCRIPTION_PAYMENT` with `INVESTMENT` in WalletService caused incorrect ledger postings.
- Bonus usage without cost recognition caused "free shares" in P&L.
- Duplicate wallet credits occurred when ledger accrual and wallet deposit both credited liabilities.
- Fragmented ledgers or wallet-based balance mutations lead to financial drift that is only discovered during audits.
- Event-chained architectures without a central coordinator lose transactions in intermediate "pending" states.
- Compensation logic without strict referential integrity can result in duplicate credits or reversals across accounting periods.
- Inventory creation without ledger atomicity risked financial inconsistency.

### Database & Migrations

- Partial migrations due to MySQL non-transactional DDL. Tables and FKs can exist even when migrations fail mid-run. Requires manual cleanup before re-running.
- Identifier length overflow (MySQL error 1059). Auto-generated index names exceeded 64 characters.
- Foreign key blocking table drops (error 1451). Child tables prevented cleanup of partially created parents.
- Schema assumption mismatch (error 1054). Migrations referenced columns that did not exist.
- Missing dependency tables (error 1146). Structural migrations attempted DML on tables not yet created.
- MySQL strict-mode timestamp failure. `timestamp NOT NULL` without default causes migration failure. Resolved by making approval timestamps nullable.
- Foreign key errno 150. Caused by mismatch between `ON DELETE SET NULL` and non-nullable columns.
- Non-deterministic migrations. Conditional logic, DML, or destructive operations inside migrations destroy provability of schema state.
- Assuming DB defaults define lifecycle. DB default values (e.g., `status = active`) do not override model state machines and caused repeated transition errors.
- Using invalid ENUM values. Inserting values not defined in ENUM columns caused truncation warnings and runtime failures.
- Schema defaults conflicting with lifecycle rules. Historical migrations defaulting status to `active` or `approved` caused silent violation of the draft → submit → approve workflow.

### Product & Ownership

- Orphan products created when `company_id` was nullable caused data integrity and audit failures.
- Parallel product creation flows (admin + issuer) broke chain-of-custody.
- Controller-level invariants allowed bypassing model enforcement.
- Silent approval bypass when admins created products directly.
- Broken audit chains caused by conflating authorship and approval.
- Downstream logic failures due to products existing in invalid states.
- Frontend-driven violations where admin UI invoked illegal APIs.
- Bypassed approval lifecycle through admin creation. Admin `store()` methods setting products directly into active/approved-like states.
- Creating products as active before inventory triggered inventory guards and runtime exceptions.
- Using platform users where company users are required. Referencing `users.id` instead of `company_users.id` caused FK violations.

### Disclosure & Audit

- Re-running seeders that use `create()` instead of `updateOrCreate()` causes unique-constraint violations on disclosures.
- System-initiated actions (seeders/guards) that omit `actor_type` trigger audit log insertion failures.
- Deal seeders that attempt to create overlapping active deals raise runtime exceptions due to enforced exclusivity.

### Frontend & API

- Frontend–backend contract mismatch. Frontend components required richer data than backend APIs provided, blocking lawful wiring.
- Orphaned components. Components may exist physically but remain unused due to unresolved contracts. Orphaning is a signal of contract or spec misalignment.
- Snapshot comparison without immutable source. Rendering "then vs now" without immutable snapshot backing leads to audit indefensibility.
- Silent architectural rot. Components that "work correctly" can still be structurally invalid (e.g., UI components owning business logic), escaping traditional audits.
- Brittle Workflow Syndrome. Backend business-rule changes force frontend redeployments due to embedded logic.
- Entitlement Drift. UI-based role/permission logic diverges from backend truth over time.
- Temporal coupling via React lifecycle. Authentication and compliance timelines tied to component mounts cause hidden fragility.
- Implicit frontend assumptions masked backend authority violations.
- Using `baseURL` with trailing `/` and Axios calls with leading `/` causes silent double-slash URLs and 404s.
- Relying on curl without JSON headers can give false confidence about API availability (HTML/web response vs API response).
- Calling `$this->authorize()` from a trait without `AuthorizesRequests` causes a fatal `Call to undefined method` error.
- Debugging by line number in stack traces is unreliable due to caching, line ending differences, and trait flattening.
- Assuming future or conceptual routes (e.g., `/company/products`) exist without verifying the App Router filesystem caused incorrect 404 diagnostics and misidentification of "missing files".
- Treating tabbed UI states as separate routes led to false expectations about `/create` URLs.
- Attempting to create directories with unescaped parentheses in shell commands caused runtime errors.
- Mixing audit navigation with operational admin menus risks authority ambiguity.

### Business Logic

- Hardcoded business logic leads to inability to adapt plans or bonuses without redeploy.
- UI-only feature disabling. Hiding buttons without backend enforcement causes rule bypass via API.
- Unversioned plan changes. Modifying live plans without audit or versioning risks user disputes.
- Missing audit trails make compliance and debugging impossible.
- Client-side rule checks can be bypassed. Amount thresholds or approvals in JS are trivially tampered with and unsafe.
- Silent UI fallbacks mask backend failures. Defaulting balances or rules hides outages and creates false correctness.
- Failure-first execution without state-aware retries produces user-visible dead ends.

### Investor Risk

- Investors over-weight last funding round valuation as "fair value."
- Investors underestimate dilution from ESOP pools and preference stacks.
- Platform-led investments obscure true ownership and custody of shares.
- Exit assumptions rely solely on IPO timing without contingency paths.
- Treating the Learning Center as "content marketing" rather than risk education leads to user misunderstanding, increased complaints, and legal/reputational exposure.
- Skipping expectation-setting (liquidity, timelines, risk) results in misaligned investors.
- Conversion drop due to legal overexposure at signup. Forcing multiple long documents during initial onboarding leads to abandonment.
- Ineffective bot protection from always-on CAPTCHA. Applying CAPTCHA indiscriminately degrades UX without materially improving security.

### Documentation

- Allowing phrases like "rewrite completely", "reality-based overview", or "audit-friendly" in prompts causes structural replacement of README and genre shift into compliance/audit documents.
- Failing to anchor the AI to the existing README as canonical template results in overcorrection and loss of continuity.
- Parallel governance documents. Maintaining Claude/Gemini-specific full docs leads to drift and confusion.
- Misusing README as governance surface. Mixing public-facing README content with enforcement rules dilutes both.

### Tooling & Environment

- Composer partial-update deadlocks. Attempting to upgrade framework or packages individually while others are lock-pinned leads to unsatisfiable graphs.
- Incorrect version assumptions. Assuming non-existent package versions causes hard resolution failures.
- Framework/package mismatch. Upgrading Laravel beyond what critical packages support leads to unavoidable conflicts.
- Environment artifacts mistaken for source. Cache files and runtime artifacts appearing as deletions can confuse Git state.
- PowerShell wildcard handling breaks repo dumps. Paths containing `[ ]` (Next.js dynamic routes) fail unless `-LiteralPath` is used.
- Windows reserved files in Git. Presence of `nul` can block clean working trees and must be handled via Git/NTFS-specific methods.
- PHP method shadowing (duplicate method names) caused silent override of accounting logic.

### AI Code Generation

- Prompting AI for full project output in a single phase results in token overflow and code loss.
- Ambiguous or high-level file names lead to invalid outputs, unreadable project structures, or broken imports.
- Large outputs without sequential control lead to missing modules, broken endpoint references, and omitted admin features.
- AI tools often hallucinate or skip required modules unless explicitly structured in parts.

---

## Anti-Patterns

### Product & Ownership

- Admins acting as content authors. This collapses governance and execution roles and must never be repeated.
- Parallel workflows for the same domain entity. Maintaining separate admin and company creation flows causes confusion and regressions.
- Nullable foreign keys to preserve broken flows. Allowing nullable ownership to "accommodate legacy data" or "support flexibility" entrenches invalid states.
- UI-driven legitimacy of invalid actions. Providing polished admin UIs for forbidden actions normalizes architectural violations.
- Treating approval as a soft convention instead of a hard gate.
- Preserving broken flows to avoid cleanup work.
- Enforcing compliance only downstream instead of at object creation.
- "Fixing" symptoms (visibility, filters) without fixing origin authority.
- Treating admin privilege as benign or unlimited.

### Financial & Accounting

- Inventing revenue flows without explicit business instruction.
- Reusing transaction paths "because debits/credits look similar".
- Mixing business-logic refactors with accounting fixes.
- Allowing AI or developers to modify ledger core without design review.
- Using string transaction identifiers instead of enums.
- Assuming wallet balance mutations are harmless without ledger reconciliation.
- Treating entitlement semantics as equivalent to revenue semantics.
- Maintaining dual sources of financial truth during migration or refactor.
- Shipping financial flows without explicit compensation definitions.
- Allowing orchestration layers to accumulate business logic ("God Object" saga coordinators).

### Frontend & Authority

- Frontend-driven authority. Relying on frontend logic to enforce rules instead of mandatory backend guards.
- Frontend adapters for missing semantics. Mapping or fabricating fields in the frontend hides real contract gaps.
- Weakening components to match APIs. Downgrading component contracts to fit underspecified APIs causes long-term loss of governance capability.
- Silent patch-through fixes. "Quick fixes" that make UI render without proving contract correctness create latent audit and regulatory risk.
- Trusting the client for financial logic. Moving code into smaller frontend files without shifting authority does not reduce risk.
- Masquerading UI components. Files presented as UI wrappers that actually act as policy engines or workflow owners.
- "Convenience-driven" logic placement. Placing business rules in UI components because they are "close to rendering."
- Global coordinators disguised as contexts. Contexts that manage side effects, persistence, navigation, and lifecycle control.
- Audits that ignore responsibility density. Structural audits that validate correctness but not authority ownership are insufficient.

### Database & Migrations

- Masking errors with conditional guards (`Schema::hasTable`, `try/catch`). Enables silent skipping and breaks compliance guarantees.
- Allowing migrations to "succeed" with partial or skipped compliance controls.
- Combining table creation, FK wiring, trigger creation, and DML in a single migration without enforced order.
- Relying on auto-generated index/constraint names for long table names.
- Using development shortcuts as permanent production fixes.
- Retroactive schema fixes. Modifying old, committed migrations to "patch" correctness leads to non-reproducible schema states.
- Assuming application logic can replace schema guarantees.

### Seeders

- Bypassing invariants in seeders. "Forcing" data into the system without respecting domain rules hides real issues and violates system design.
- Disabling or bypassing domain guards (e.g., deal overlap checks) for convenience during seeding.
- Assuming a clean database state when designing seeders.
- Making audit fields nullable or adding silent defaults to bypass integrity checks.
- Treating versioned entities (disclosures) as append-only tables instead of mutable, versioned records.
- Using `firstOrCreate` for non-nullable credential models. Causes partial inserts and missing required fields.
- Inventing lifecycle states. Using non-existent statuses (e.g., `review`, `active`) outside the defined state machine.
- Guessing schema or enum values instead of inspecting migrations/schema.

### Admin & Control

- Treating admin panel as secondary. Admin panel is core infrastructure, not an afterthought.
- Silent admin overrides. Making admin changes without explicit logging or confirmation.
- Assuming fixed plans or fixed percentages. All such assumptions were invalidated.
- Developer-controlled business rules. Business rules belong to business operators, not developers.
- Environment-variable business rules. Using `.env` for runtime business toggles is prohibited.
- Implicit requirements. Anything not explicitly documented is considered nonexistent.

### Governance & Process

- Mixed-intent commits. Combining governance declarations with code/schema changes invalidates audit boundaries.
- Treating pre-launch code as something that can be "cleaned up later."
- Optimizing performance before correctness and invariants are frozen.
- Partial or hybrid approaches (e.g., dual accounting truths, optional compensation paths) are rejected as unsafe.
- Skipping failure documentation. Fixing without freezing and documenting the failure leads to ambiguous intent and future regressions.
- Backend-only completion claims. Declaring a phase complete without fully wired frontends is invalid.
- Implicit consent or defaults. Auto-approval, auto-acknowledgement, or inferred investor consent is forbidden.

### Documentation

- Treating README.md as a greenfield document when updating legacy systems.
- Allowing AI to introduce new top-level sections during a "simple update" task.
- Conflating "truthfulness" with "audit rigor" in user-facing developer documentation.
- Model-specific governance. Writing separate governance rules per AI tool. Treating governance as "AI-only" guidance.
- Documentation role creep. Allowing context or README files to silently acquire authority.
- Duplication instead of indirection. Copying rules across files instead of pointing to a single source.

### Communication & UX

- Mock data in runtime paths. Acceptable only in isolated dev tooling, never in production UI flows.
- Duplicating rules across controllers. Leads to drift and audit failure; centralized services are required.
- No-sandbox notification sends. Sending pushes without test environments risks accidental user impact and audit issues.
- Hiding or soft-disabling controls instead of rendering immutable, read-only views for locked or historical data.
- Treating audit views as extensions of admin control surfaces (e.g., editable or action-oriented UIs).
- Relying on frontend-only enforcement or inference for compliance-critical rules.
- Publishing tutorials without explicit disclaimers.
- Mixing platform promotion with educational content in beginner lessons.
- Assuming users understand financial jargon at the Beginner level.

### Investor Communication

- Treating pre-IPO investing as a shortcut to IPO allotment.
- Ignoring governance and regulatory risks in favor of growth metrics.
- Using vague terms like "economic exposure" instead of legal ownership.
- Allowing intermediaries to replace primary company disclosures.
- Scroll-gated legal acceptance. Treating scroll-to-bottom as proof of informed consent.
- Using CAPTCHA as a primary security mechanism. Relying on CAPTCHA instead of layered controls.
- Hardcoded CAPTCHA thresholds. Failing to tune thresholds based on real traffic and action context.

### AI Code Generation

- Asking the AI to generate all code in one go without specifying file names, paths, or chunked delivery.
- Using placeholder file names or trusting the AI to infer correct naming from informal labels.
- Allowing AI to progress from one phase to the next without explicit human confirmation and validation.
- Trusting "summary" outputs to contain working, full-length code blocks or integrations.
- Designing or documenting routes that are not backed by the filesystem.
- Mentally importing a "future-state" or "ideal architecture" into audits of the current repo.
- Debugging 404s by searching for deleted files instead of validating the App Router directory structure.
- Trusting curl success without matching Axios request headers and behavior.
- Assuming `$this->authorize()` exists implicitly on all controllers.
- Fixing authorization errors at the middleware or route level when the failure is due to missing traits.

---

> **⚠️ Conflict detected — requires human arbitration:**
> File 20 (Context/20.md) states: "Laravel 11.x is the supported framework baseline. Laravel 12 was explicitly rejected due to upstream package incompatibility." However, `composer.json` declares `"laravel/framework": "^11.0"` (which permits 11.x) while the README states "Laravel 12". The actual installed version and the authoritative framework baseline require human verification. The constraint from Context/20.md is preserved as-is pending resolution.

---

*This document was constructed via failure-aware context accumulation from 25 session context files plus the existing ARCHITECTURE_AND_GOVERNANCE_CONTEXT.md. No rules were weakened, dropped, or silently resolved. Contradictions are surfaced. All known failure modes are preserved regardless of current fix status.*
