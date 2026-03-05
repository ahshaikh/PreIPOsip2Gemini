# Financial Lifecycle Refactor — Independent Architecture Audit

**Date:** 2026-03-06
**Auditor:** Claude Code
**Verdict:** PARTIALLY COMPLETE — Critical violations remain

---

## Executive Summary

The refactor introduced a `FinancialOrchestrator` with proper single-transaction semantics for the **payment lifecycle** (`processSuccessfulPayment`, `processRefund`, `processChargeback`). However, the system **does NOT achieve** the intended "Single Financial Orchestration Boundary" architecture.

**Multiple parallel mutation pathways remain active**, including:
- Direct service calls from jobs, commands, controllers
- Legacy saga infrastructure still callable
- DB::transaction() and lockForUpdate() scattered across services
- Async jobs still performing financial mutations

---

## 1. Architecture Status

### Correctly Implemented

| Component | Status |
|-----------|--------|
| `FinancialOrchestrator::processSuccessfulPayment()` | Correct single-transaction, idempotent, deterministic locks |
| `FinancialOrchestrator::processRefund()` | Correct single-transaction |
| `FinancialOrchestrator::processChargeback()` | Correct single-transaction |
| `FinancialOrchestrator::creditUserWallet()` | Wrapper with transaction |
| `FinancialOrchestrator::debitUserWallet()` | Wrapper with transaction |
| Idempotency via `fulfilled_at` | Present in payment lifecycle |
| Lock order documentation | Correct sequence defined |
| WalletService `Wallet|User` signature | Backward-compatible |

### NOT Implemented

| Component | Status |
|-----------|--------|
| Single mutation boundary for ALL financial ops | **VIOLATED** — Multiple pathways |
| Centralized lock acquisition | **VIOLATED** — Locks in 10+ services |
| No async mutation jobs | **VIOLATED** — 4+ jobs mutate state |
| Services receive locked models only | **VIOLATED** — Services still lock internally |
| Saga deprecation | **NOT DONE** — Still callable via controller |

---

## 2. Violations

### 2.1 Orchestration Boundary Violations

**Direct WalletService calls outside orchestrator:**

| Location | Method | Risk Level |
|----------|--------|------------|
| `AwardBulkBonusJob:90` | `walletService->deposit()` | HIGH |
| `ProcessReferralJob:155` | `walletService->deposit()` | HIGH |
| `ProcessCelebrationBonuses:202` | `walletService->deposit()` | HIGH |
| `BonusCalculatorService:859,937` | `walletService->depositTaxable()` | MEDIUM |
| `ChargebackResolutionService:184,307` | `deposit/withdraw` | HIGH |
| `DisputeSettlementOrchestrator:115,156` | `walletService->deposit()` | HIGH |
| `PaymentAllocationSaga:142,219,437,472` | `deposit/withdraw` | HIGH |
| `LuckyDrawService:278` | `walletService->deposit()` | MEDIUM |
| `ProfitShareService:459,572` | `deposit/withdraw` | HIGH |
| `WithdrawalService:90` | `walletService->withdraw()` | MEDIUM |
| `UserService:197` | `walletService->deposit()` | MEDIUM |
| `CreditUserWalletOperation:39,85` | `deposit/withdraw` | HIGH |

**Direct AllocationService calls:**

| Location | Method |
|----------|--------|
| `ProcessAllocationJob:129` | `allocationService->allocateShares()` |
| `AdminUserController:411` | `allocationService->overrideAllocation()` |

---

### 2.2 Transaction Boundary Violations

**DB::transaction() calls outside FinancialOrchestrator:**

| Location | Risk |
|----------|------|
| `AllocationService:90,315,354` | Nested transaction risk |
| `ChargebackResolutionService:151` | Parallel mutation pathway |
| `CelebrationBonusService:158` | Bonus mutation outside orchestrator |
| `DisputeSettlementOrchestrator:70` | Settlement mutations |
| `LuckyDrawService:219` | Prize distribution mutations |
| `PaymentAllocationSaga:398` | Legacy saga still active |
| `PaymentWebhookService:552,691,762,830,1090` | Multiple transaction boundaries |
| `ProcessAllocationJob:117` | Async financial mutation |
| `ProcessReferralJob:98` | Async referral bonus |
| `AwardBulkBonusJob:65` | Async bulk bonus |
| `WalletController:243` | Controller-level transaction |
| `AdminBonusController:197,306,476` | Controller-level mutations |
| `BulkPurchaseController:146` | Inventory mutation |
| `SagaCoordinator:75,155` | Legacy saga infrastructure |

**Total: 30+ transaction boundaries outside orchestrator**

---

### 2.3 Lock Ownership Violations

**lockForUpdate() calls outside FinancialOrchestrator:**

| Location | Model Locked |
|----------|--------------|
| `AllocationService:322,369,460` | BulkPurchase |
| `ChargebackResolutionService:153,293,463,478` | Payment, Wallet |
| `InventoryConservationService:53,138,278` | BulkPurchase |
| `PaymentWebhookService:557,597,694,764,832,1092` | Payment |
| `CompanyShareAllocationService:68,256` | BulkPurchase |
| `ProfitShareService:361` | ProfitShare |
| `InvestmentSecurityGuard:198,237` | Investment |
| `WalletService:135,142,358,365,518` | Wallet |
| `PrivacyController:142,151` | User, Wallet |
| `CampaignService:188` | Campaign |

**Total: 25+ lock acquisitions outside orchestrator**

---

### 2.4 Async Financial Mutation Violations

| Job | Financial Operation |
|-----|---------------------|
| `ProcessAllocationJob` | Allocates shares, mutates inventory |
| `AwardBulkBonusJob` | Credits wallet, records ledger |
| `ProcessReferralJob` | Credits referrer wallet |
| `ProcessPaymentBonusJob` | Calculates and credits bonuses |
| `ExpireInventoryLock` | Releases inventory locks |
| `ProcessWebhookJob` | Processes ledger entries |

---

### 2.5 Controller Integrity Violations

| Controller | Direct Service Call |
|------------|---------------------|
| `AdminUserController:411` | `allocationService->overrideAllocation()` |
| `AdminBonusController:306,476` | Creates bonus + wallet mutation in transaction |
| `WalletController:243` | Withdrawal creation with transaction |
| `LuckyDrawController:286` | `distributePrizes()` with walletService |
| `BulkPurchaseController:306` | Direct inventory allocation |

---

## 3. Hidden Risks

### 3.1 Race Conditions

**Risk:** Multiple pathways can mutate the same wallet concurrently:
- `ProcessReferralJob` deposits to wallet
- `FinancialOrchestrator::processSuccessfulPayment()` deposits to wallet
- Both can run simultaneously for the same user

**Root Cause:** No global mutex on wallet mutations.

### 3.2 Deadlock Risks

**Risk:** Inconsistent lock ordering between:
- `FinancialOrchestrator` (Payment → Subscription → Wallet → Product → BulkPurchase)
- `ChargebackResolutionService` (Payment → Wallet → Investments)
- `AllocationService` (BulkPurchase only)
- `PaymentWebhookService` (Payment only)

**Scenario:** Two transactions acquiring locks in different orders can deadlock.

### 3.3 Double-Spend Risks

**Risk:** Payment can be fulfilled via multiple pathways:
1. `PaymentWebhookService` → `FinancialOrchestrator::processSuccessfulPayment()`
2. `ProcessSuccessfulPaymentJob` → `FinancialOrchestrator::processSuccessfulPayment()`
3. `PaymentAllocationSaga::execute()` (via SagaManagementController)

While `fulfilled_at` provides idempotency, **pathway 3 does NOT check `fulfilled_at`**.

### 3.4 Ledger Integrity Risks

**Risk:** Ledger entries created outside orchestrator transaction:
- `AwardBulkBonusJob` records `recordBonusWithTds()` in separate transaction
- If job fails after ledger write but before wallet credit: **orphan ledger entry**

---

## 4. Incomplete Refactor Areas

### 4.1 Bonus Lifecycle

BonusCalculatorService still performs:
- Direct wallet mutations via `depositTaxable()`
- Direct ledger writes via `recordBonusWithTds()`

**Should route through:** `FinancialOrchestrator::awardBonus()`

### 4.2 Referral Lifecycle

ProcessReferralJob still:
- Opens own transaction
- Deposits to wallet directly
- Records ledger entries

**Should route through:** Orchestrator's referral flow

### 4.3 Withdrawal Lifecycle

WithdrawalService still:
- Opens own transaction
- Withdraws from wallet directly

**Should route through:** `FinancialOrchestrator::processWithdrawal()`

### 4.4 Dispute Settlement Lifecycle

DisputeSettlementOrchestrator still:
- Opens own transaction
- Deposits to wallet directly

**Should route through:** `FinancialOrchestrator::settlementCredit()`

### 4.5 Lucky Draw Lifecycle

LuckyDrawService still:
- Opens own transaction
- Distributes prizes with wallet deposits

**Should route through:** `FinancialOrchestrator::awardPrize()`

### 4.6 Profit Share Lifecycle

ProfitShareService still:
- Opens own transaction
- Deposits/withdraws directly

**Should route through:** `FinancialOrchestrator::distributeProfitShare()`

### 4.7 Legacy Saga Infrastructure

PaymentAllocationSaga remains:
- Fully functional
- Callable via `SagaManagementController`
- Has own transaction boundaries
- Performs wallet mutations

**Should be:** Deprecated, disabled, or removed

---

## 5. Exact Fixes Required

### 5.1 Critical (Must Fix)

| # | Fix | Files Affected |
|---|-----|----------------|
| 1 | Remove `DB::transaction()` from WalletService | `WalletService.php` |
| 2 | Remove `lockForUpdate()` from WalletService | `WalletService.php` |
| 3 | Remove `DB::transaction()` from AllocationService | `AllocationService.php` |
| 4 | Remove `lockForUpdate()` from AllocationService | `AllocationService.php` |
| 5 | Deprecate and disable `PaymentAllocationSaga` | `PaymentAllocationSaga.php`, `SagaManagementController.php` |
| 6 | Route `AwardBulkBonusJob` through orchestrator | `AwardBulkBonusJob.php` |
| 7 | Route `ProcessReferralJob` through orchestrator | `ProcessReferralJob.php` |
| 8 | Route `ProcessAllocationJob` through orchestrator | `ProcessAllocationJob.php` |
| 9 | Add `fulfilled_at` check to saga (if keeping) | `PaymentAllocationSaga.php` |

### 5.2 High Priority

| # | Fix | Files Affected |
|---|-----|----------------|
| 10 | Move ChargebackResolutionService mutations to orchestrator | `ChargebackResolutionService.php` |
| 11 | Move DisputeSettlementOrchestrator mutations to FinancialOrchestrator | `DisputeSettlementOrchestrator.php` |
| 12 | Move LuckyDrawService prize distribution to orchestrator | `LuckyDrawService.php` |
| 13 | Move ProfitShareService mutations to orchestrator | `ProfitShareService.php` |
| 14 | Move WithdrawalService mutations to orchestrator | `WithdrawalService.php` |
| 15 | Remove controller-level transactions | `AdminBonusController.php`, `WalletController.php` |

### 5.3 Medium Priority

| # | Fix | Files Affected |
|---|-----|----------------|
| 16 | Remove `lockForUpdate()` from PaymentWebhookService | `PaymentWebhookService.php` |
| 17 | Consolidate all bonus flows through single orchestrator method | Multiple |
| 18 | Add integration tests for single-boundary enforcement | Tests |
| 19 | Add static analysis rule to detect direct service calls | CI/CD |

---

## 6. Architectural Debt Summary

| Category | Count | Severity |
|----------|-------|----------|
| Transaction boundary violations | 30+ | CRITICAL |
| Lock ownership violations | 25+ | CRITICAL |
| Direct wallet mutation calls | 15+ | CRITICAL |
| Async mutation jobs | 6 | HIGH |
| Controller integrity violations | 5 | MEDIUM |
| Legacy saga pathways | 1 | HIGH |

---

## 7. Conclusion

The `FinancialOrchestrator` correctly implements single-transaction semantics for the **core payment lifecycle** (payment → bonus → allocation). However, **the refactor is approximately 40% complete**.

**The system currently has:**
- 1 correct orchestration pathway (payment lifecycle)
- 10+ parallel mutation pathways (violations)
- No enforcement mechanism to prevent direct service calls

**To achieve the intended architecture:**
1. All financial mutations must route through `FinancialOrchestrator`
2. Domain services must be stripped of transaction/lock logic
3. Async jobs must only dispatch non-financial work
4. Legacy saga infrastructure must be deprecated
5. Static analysis must enforce the boundary

Until these fixes are applied, the system remains vulnerable to:
- Race conditions
- Deadlocks
- Double-spend scenarios
- Ledger inconsistencies

---

*End of Audit Report*
