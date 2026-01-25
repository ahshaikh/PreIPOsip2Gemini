# COMPANY MODULE AUDIT REPORT
## PreIPOsip Platform — Governance, Compliance & Systems Audit
### Generated: 2026-01-25 | Auditor: Claude Opus 4.5 | Protocol: Governance & Compliance Audit

---

## 1. EXECUTIVE GAP SUMMARY

**CRITICAL DEFICIENCIES (Existential Risk)**

1. **NO PLATFORM WALLET IMPLEMENTATION** — The platform acts as principal (buys shares from companies, sells to subscribers) but has NO dedicated platform wallet/account. Cash from subscriber investments goes to user wallets; there is NO mechanism to credit the platform's own account when shares are sold.

2. **NO TIER-2 APPROVAL → PLATFORM PURCHASE HOOK** — The specification mandates that platform buys shares from company after Tier 2 approval. NO CODE exists to trigger platform share purchase when Tier 2 is approved. The `CompanyLifecycleService::transitionTo()` only sets `buying_enabled = true` but does NOT invoke any share purchase service.

3. **INCOMPLETE SHARE TRACEABILITY** — While `UserInvestment` records link to `bulk_purchase_id`, there is NO ledger entry recording platform→subscriber share transfers. The `AdminLedger` tracks inventory purchase but NOT subscriber allocation as a sale event. Cannot answer: "Did the platform receive money for each share sold?"

4. **NO PLATFORM REVENUE FROM SUBSCRIBER PURCHASES** — When a subscriber invests, wallet is debited but NO corresponding credit to platform revenue/cash account. The `AdminLedger::recordPaymentReceived()` exists but is NOT called in `InvestorInvestmentController::store()`.

5. **PLATFORM CONTEXT EDITABLE BY ADMINS BUT NO ATTRIBUTION UI** — While `PlatformContextSnapshotService` exists and captures snapshots, the frontend does NOT clearly separate "Platform says" vs "Company says" in the investor view. Platform context is displayed inline without clear visual separation.

6. **INVESTMENT CONTROLLER → SNAPSHOT SERVICE MISMATCH** — `InvestorInvestmentController::store()` calls `snapshotService->captureAtPurchase()` expecting parameters `(int, int)` but the actual service expects `(int, User, Company, array)`. This is a BREAKING BUG.

7. **NO SHARE CUSTODY CONCEPT** — Specification implies platform holds shares in custody before subscriber allocation. No custody model exists. Shares go directly from `BulkPurchase` (inventory) to `UserInvestment` (subscriber) with no intermediate custody state.

8. **NO PLATFORM ACCOUNTING RECONCILIATION FOR SUBSCRIBER INVESTMENTS** — The `AdminLedger` has proper double-entry for inventory purchase but NO corresponding entries when subscribers buy. The accounting equation cannot prove platform solvency for subscriber transactions.

---

## 2. MISSING / INCOMPLETE SYSTEMS

### A. BACKEND — Models, Services, Guards, Ledgers

| Missing/Incomplete | Description | Invariant Violated | Severity |
|---|---|---|---|
| **Platform Wallet Model** | No `PlatformWallet` or `AdminWallet` entity exists | Platform cash position unprovable | **CRITICAL** |
| **Tier-2 Purchase Hook** | No automatic share purchase when company reaches Tier-2 | Platform inventory flow broken | **CRITICAL** |
| **Subscriber Payment → Platform Credit** | `InvestorInvestmentController` debits user wallet but never credits platform | Cash reconciliation impossible | **CRITICAL** |
| **Share Transfer Ledger** | No ledger entry for inventory→subscriber allocation | Share traceability broken | **CRITICAL** |
| **Custody State Model** | No intermediate custody state for platform-held shares | Regulatory custody requirements unmet | **HIGH** |
| **Investment → AdminLedger Integration** | Controller does not call `AdminLedger::recordPaymentReceived()` | Revenue not tracked | **CRITICAL** |
| **BulkPurchase → Company Lifecycle Link** | No validation that bulk purchase can only occur for Tier-2+ companies | Inventory provenance weak | **HIGH** |

### B. FRONTEND — Issuer, Admin, Investor, Public

| Missing/Incomplete | Description | Invariant Violated | Severity |
|---|---|---|---|
| **Platform Context Card Component** | Platform context hardcoded inline in deal detail page (not modular) | Reusability, maintainability | **MEDIUM** |
| **"Platform Says" vs "Company Says" Separation** | No clear visual/UI separation in investor view | Regulatory misrepresentation risk | **HIGH** |
| **Material Change Diff Viewer** | Links to external diff URL, no in-app viewer | Investor must leave platform | **MEDIUM** |
| **Risk Flag Rationale Display** | Shows flags but not WHY they were raised | Investor opacity | **HIGH** |
| **Snapshot Comparison Interface** | No way for investor to compare current vs purchase-time snapshot | Dispute resolution gap | **MEDIUM** |
| **Acknowledgement Context Binding UI** | Checkboxes shown but no explanation of what triggered them | Informed consent weak | **HIGH** |
| **Admin → Investor Impact Visibility** | Issuers cannot see how admin changes affect investor view | Transparency gap | **MEDIUM** |

### C. PLATFORM CONTEXT

| Missing/Incomplete | Description | Invariant Violated | Severity |
|---|---|---|---|
| **PlatformContextSnapshot → Investment Binding** | Service exists but controller call signature mismatch | Investment flow broken | **CRITICAL** |
| **Context Change Timeline (Investor-facing)** | No audit trail visible to investors | "What changed" invisible | **HIGH** |
| **Admin Judgment vs Automated Flag Attribution** | Captured but not surfaced to investors | Attribution unclear | **MEDIUM** |
| **Platform Context Approval Flow** | Admin can set context but no multi-step approval | Single point of failure | **MEDIUM** |

### D. INVENTORY & WALLET ACCOUNTING

| Missing/Incomplete | Description | Invariant Violated | Severity |
|---|---|---|---|
| **Platform Balance Tracking** | No platform balance entity — only user wallets exist | Platform solvency unprovable | **CRITICAL** |
| **Subscriber Investment → AdminLedger Entry** | Missing call to record subscriber payment as platform revenue | Double-entry incomplete | **CRITICAL** |
| **Share Allocation → Cash Credit Link** | UserInvestment created but no corresponding ledger credit | Cash-share reconciliation broken | **CRITICAL** |
| **Admin Dashboard: Platform Balance View** | No UI to show platform cash vs liabilities | Admin visibility gap | **HIGH** |
| **Reconciliation Report** | No report proving: shares_purchased = shares_allocated + shares_remaining | Conservation audit gap | **HIGH** |

### E. AUDIT & COMPLIANCE

| Missing/Incomplete | Description | Invariant Violated | Severity |
|---|---|---|---|
| **Per-Share Traceability** | Bulk allocation tracking but no per-share/per-lot provenance | SEBI audit requirement unmet | **CRITICAL** |
| **Investment → Cash Flow Proof** | Cannot prove: "100K shares bought → 99K sold → Platform received ₹X" | Financial audit failure | **CRITICAL** |
| **Dispute Resolution Snapshot Retrieval** | Service exists but no admin UI to retrieve and display | Dispute resolution blocked | **HIGH** |
| **Compliance Gate for Company Investments** | `ComplianceGateService` exists but not integrated in company investment flow | KYC bypass possible | **HIGH** |
| **Immutability Enforcement** | `InvestmentDisclosureSnapshot` has `is_immutable` but no DB-level enforcement | Mutation possible | **MEDIUM** |

---

## 3. INVENTORY & WALLET TRACEABILITY VERDICT

### THE CRITICAL QUESTION:
> "Admin bought 100,000 shares on Day 1. Today balance shows 1,000 shares.
> Where did the other 99,000 shares go, and did the platform receive money for each of them?"

### VERDICT: **CANNOT BE ANSWERED PROVABLY**

**WHAT EXISTS:**
- `BulkPurchase.value_remaining` tracks unallocated inventory
- `UserInvestment` records link subscriber → bulk_purchase (allocation)
- `AdminLedger::recordInventoryPurchase()` records platform cash → inventory conversion
- Atomic decrements prevent race conditions

**WHAT IS MISSING:**
1. **No Share Transfer Ledger Entry** — When subscriber buys, `UserInvestment` is created but NO ledger entry records "Platform sold X shares for ₹Y"

2. **No Cash Credit to Platform** — Subscriber wallet is debited but platform receives no credit. The call chain is:
   ```
   InvestorInvestmentController::store()
     → walletService->debit(user, amount, ...)  ✓ Debits user
     → [MISSING: adminLedger->recordPaymentReceived(...)]
   ```

3. **No End-to-End Chain** — Cannot join:
   - `BulkPurchase` (100K shares bought)
   - `UserInvestment` (99K shares allocated)
   - `AdminLedgerEntry` (99K shares' worth of cash received)

   Because the third entry DOES NOT EXIST.

4. **Conservation Provable, Revenue Not** — `InventoryConservationService::verifyConservation()` proves shares are conserved, but there is NO corresponding cash conservation proof.

### REQUIRED FIXES:
1. Add `AdminLedger::recordShareSale(amount, investmentId, ...)` method
2. Call it from `InvestorInvestmentController::store()` after wallet debit
3. Create reconciliation query: `SUM(bulk_purchase.value_purchased) == SUM(user_investment.value_allocated) + SUM(bulk_purchase.value_remaining)`
4. Create cash reconciliation: `SUM(share_sales) == SUM(user_debits_for_investments)`

---

## 4. PLATFORM CONTEXT MATURITY SCORE

### RATING: **PARTIAL** (2.5/5)

| Criterion | Status | Notes |
|---|---|---|
| Platform context exists as own data model | YES | `platform_context_snapshots` table with full schema |
| Platform context has own approval flow | NO | Admins set directly, no multi-step approval |
| Immutable versions exist | YES | `is_locked = true`, `supersedes_snapshot_id` chain |
| NOT editable by companies | YES | Backend enforced — no company API to edit |
| ONLY editable by admins | YES | Via `PlatformContextSnapshotService` |
| SNAPSHOTTED at investment time | PARTIAL | Service exists but controller call is broken |
| Investor UI separates "Company says" vs "Platform context" | PARTIAL | Displayed but not visually distinct |
| Never mixes company + platform data | PARTIAL | Displayed together without clear attribution |
| Platform context never hides warnings | YES | Warnings always shown when present |

### JUSTIFICATION:
The **backend implementation is architecturally sound** — proper snapshot tables, immutability controls, and provenance tracking exist. However:
- The **investment flow is broken** due to service call mismatch
- The **frontend does not visually separate** platform context from company disclosures
- There is **no admin approval workflow** for platform context — single admin can set anything
- **No investor-facing context change history** exists

### REQUIRED TO REACH "ARCHITECTURALLY SOUND":
1. Fix `InvestorInvestmentController` → `InvestmentSnapshotService` parameter mismatch
2. Add distinct UI card for "Platform Assessment" separate from "Company Disclosures"
3. Add context change history visible to investors

### REQUIRED TO REACH "AUDIT-READY":
1. Add multi-step admin approval for platform context changes
2. Add admin attribution ("Set by Admin X on Date Y" visible to investors)
3. Add automated platform context change notifications to affected investors
4. Add reconciliation between snapshot-at-investment vs current context

---

## 5. STATE MACHINE AUDIT

### COMPANY LIFECYCLE STATE MACHINE

**Specification States:**
```
draft → live_limited (Tier 1) → live_investable (Tier 2) → live_fully_disclosed (Tier 3)
any → suspended (admin action)
suspended → any (admin restore)
```

**Implementation Status:**

| State Machine Aspect | Implementation | Status |
|---|---|---|
| State transitions validated | `CompanyLifecycleService::isValidTransition()` | IMPLEMENTED |
| Backend guard prevents invalid transitions | Yes, throws `RuntimeException` | IMPLEMENTED |
| Buying gated by state | `isBuyingAllowed()` returns `true` only for `live_investable`, `live_fully_disclosed` | IMPLEMENTED |
| Suspension overrides everything | `suspend()` sets `buying_enabled = false`, verifies it | IMPLEMENTED |
| State changes logged | `CompanyLifecycleLog::create()` with full audit trail | IMPLEMENTED |
| Frontend cannot bypass backend guard | `BuyEnablementGuardService` enforces server-side | IMPLEMENTED |

**GAP: State machine exists but is NOT enforced as hard DB constraint.** State transitions are validated in PHP but nothing prevents direct DB update bypassing the service.

### DISCLOSURE MODULE STATE MACHINE

**Specification States:**
```
draft → submitted → under_review → (clarification_required → resubmitted)* → approved
                                 → rejected
```

**Implementation Status:**

| Disclosure Lifecycle Aspect | Implementation | Status |
|---|---|---|
| States defined | `CompanyDisclosure::status` field | IMPLEMENTED |
| Submit enforces completion | `submit()` checks `completion_percentage < 100` | IMPLEMENTED |
| Locked after approval | `is_locked = true` set in `approve()` | IMPLEMENTED |
| Clarification blocks approval | `approve()` throws if `hasPendingClarifications()` | IMPLEMENTED |
| Version snapshots | `DisclosureVersion::createFromDisclosure()` | IMPLEMENTED |

**STATE MACHINE VERDICT: IMPLEMENTED**
Both company lifecycle and disclosure lifecycle state machines are properly implemented with guards, logging, and enforcement. The only gap is the lack of DB-level state transition constraints.

---

## 6. ROLE & VISIBILITY INVARIANTS

### SPECIFICATION REQUIREMENTS vs IMPLEMENTATION

| Role | Specification | Implementation | Status |
|---|---|---|---|
| **Company** can submit disclosures | Yes | `CompanyDisclosure::submit()` | PASS |
| **Company** cannot edit approved disclosures | Yes | `is_locked` check in `updateDisclosureData()` | PASS |
| **Company** cannot see admin notes | Yes | `internal_notes` excluded from API responses | PASS |
| **Company** cannot edit platform context | Yes | No company API for platform context | PASS |
| **Admin** can approve/reject disclosures | Yes | `approve()`, `reject()` methods | PASS |
| **Admin** can suspend companies | Yes | `CompanyLifecycleService::suspend()` | PASS |
| **Admin** can freeze disclosures | Yes | `disclosure_freeze` field on Company | PASS |
| **Admin** controls platform context | Yes | `PlatformContextSnapshotService` | PASS |
| **Investor** sees approved disclosures only | PARTIAL | API filters by status but no explicit scope in controller | HIGH RISK |
| **Investor** sees platform warnings | YES | Displayed in deal detail page | PASS |
| **Investor** cannot see drafts | UNVERIFIED | No explicit test/check in investor API | HIGH RISK |
| **Investor** cannot see clarifications | UNVERIFIED | API should filter but not verified | MEDIUM RISK |
| **Public** sees limited company info | PARTIAL | Public page exists but no clear visibility scope | MEDIUM RISK |

### GAPS:
1. **No explicit visibility scope enforcement** — Controllers should have `->approved()` scope by default for investor queries
2. **No test coverage** verifying investors cannot see drafts/clarifications
3. **Public vs Subscriber visibility toggle** exists in spec but not clearly implemented

---

## 7. PRIORITIZED REMEDIATION ROADMAP

### P0 — CRITICAL (Block Deployment)

1. **Create Platform Wallet/Account System**
   - Add `platform_wallet` table or use `AdminLedger` as sole source of truth
   - Track platform cash balance separately from user wallets
   - Estimated: 8-16 hours

2. **Integrate Subscriber Investment → AdminLedger**
   - Add `AdminLedger::recordShareSale()` method
   - Call from `InvestorInvestmentController::store()` after wallet debit
   - Estimated: 2-4 hours

3. **Fix InvestorInvestmentController → InvestmentSnapshotService Call**
   - Current: `captureAtPurchase(companyId, userId)` — WRONG
   - Expected: `captureAtPurchase(investmentId, User, Company, acknowledgements)`
   - Estimated: 1-2 hours

4. **Add Tier-2 → Platform Purchase Hook**
   - When `CompanyLifecycleService` transitions to `live_investable`:
     - Trigger notification to admin for share purchase
     - Or: Create pending `BulkPurchaseRequest` for admin action
   - Estimated: 4-8 hours

### P1 — HIGH (Pre-Launch)

5. **Add Share Transfer Ledger Entries**
   - Create immutable record of each allocation with cash link
   - Enable end-to-end traceability query
   - Estimated: 4-8 hours

6. **Implement Platform Context UI Separation**
   - Create `PlatformContextCard` component
   - Visually distinct from "Company Disclosures"
   - Estimated: 4-6 hours

7. **Add Investor Visibility Scope Enforcement**
   - Add `->approved()` scope to all investor company APIs
   - Add explicit filter for `status != 'draft'`
   - Estimated: 2-4 hours

8. **Create Reconciliation Report**
   - Admin view: Total purchased vs allocated vs remaining
   - Cash in vs shares sold
   - Estimated: 4-6 hours

### P2 — MEDIUM (Post-Launch)

9. **Add Context Change History (Investor-facing)**
10. **Add Multi-Step Platform Context Approval**
11. **Add Material Change In-App Diff Viewer**
12. **Add Risk Flag Rationale Display**
13. **Add DB-Level State Machine Constraints**

---

## 8. FINAL VERDICT

| Dimension | Rating | Justification |
|---|---|---|
| **Disclosure System** | GOOD | State machine, versioning, immutability implemented |
| **Buy Enablement Guards** | GOOD | 6-layer guard with proper blocking |
| **Platform Context** | PARTIAL | Backend solid, frontend/integration broken |
| **Inventory Management** | PARTIAL | Conservation works, cash reconciliation missing |
| **Share Traceability** | CRITICAL GAP | Cannot prove cash received for shares sold |
| **Platform Wallet** | NON-EXISTENT | No platform account concept |
| **State Machines** | GOOD | Properly enforced in services |
| **Audit Trail** | PARTIAL | Snapshots exist, reconciliation queries missing |
| **Role Visibility** | PARTIAL | Implemented but not explicitly verified |

### OVERALL: **NOT DEPLOYMENT-READY**

The platform has solid foundations for disclosure management, state machines, and buy guards. However, the **fundamental two-sided transaction architecture is incomplete**:
- Platform buys from companies → **PARTIALLY IMPLEMENTED** (via BulkPurchase)
- Platform sells to subscribers → **NOT PROPERLY TRACKED** (no revenue ledger entry)
- Platform cash position → **CANNOT BE CALCULATED** (no platform wallet)

Until P0 items are resolved, the platform **cannot prove financial integrity** to regulators, auditors, or acquirers.

---

## KEY FILES AUDITED

### Backend
- `backend/app/Services/CompanyLifecycleService.php` (476 lines)
- `backend/app/Services/PlatformContextSnapshotService.php` (425 lines)
- `backend/app/Services/InvestmentSnapshotService.php` (408 lines)
- `backend/app/Services/BuyEnablementGuardService.php` (492 lines)
- `backend/app/Services/CompanyInventoryService.php` (378 lines)
- `backend/app/Services/AllocationService.php` (337 lines)
- `backend/app/Services/WalletService.php` (449 lines)
- `backend/app/Services/Accounting/AdminLedger.php` (419 lines)
- `backend/app/Http/Controllers/Api/Investor/InvestorInvestmentController.php` (351 lines)
- `backend/app/Models/CompanyDisclosure.php` (497 lines)
- `backend/app/Models/BulkPurchase.php`
- `backend/app/Models/Company.php`

### Frontend
- `frontend/app/(user)/deals/page.tsx` (387 lines)
- `frontend/app/(user)/deals/[id]/page.tsx` (841 lines)
- `frontend/app/(user)/deals/[id]/comprehensive/page.tsx` (1,747 lines)

### Migrations
- `backend/database/migrations/2026_01_15_000002_create_platform_context_snapshots.php`
- `backend/database/migrations/2026_01_10_220000_create_platform_context_layer.php`

---

*This audit was conducted following Protocol 1 — Mandatory Self-Verification & Governance Check specifications.*
