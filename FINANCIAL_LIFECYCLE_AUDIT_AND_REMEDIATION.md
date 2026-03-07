# FINANCIAL LIFECYCLE AUDIT AND REMEDIATION PLAN

## 1. Executive Summary

A comprehensive architecture audit of the financial lifecycle system reveals critical discrepancies between the intended "Single Financial Mutation Boundary" architecture and the current implementation. Approximately 70 test failures in the `FinancialLifecycle` suite are primarily caused by:

1.  **Architecture Mismatch**: Tests invoke `FinancialOrchestrator` methods expecting full lifecycle management, but the implementation relies on `PaymentWebhookService` for state transitions and locking, causing tests to fail on preconditions.
2.  **Mutation Boundary Violations**: `PaymentWebhookService` illegally performs financial mutations (chargeback handling, transaction management) instead of delegating to `FinancialOrchestrator`.
3.  **Missing Dependencies**: Critical helper classes (`App\Helpers\SettingsHelper`) and database columns (`purchase_price_per_unit`) referenced in tests do not exist in the codebase.
4.  **Monetary Precision Risks**: Widespread use of floating-point arithmetic in `WalletService` and related classes threatens financial integrity.
5.  **Invariant Guard Failure**: The `debugFinancialInvariant` check is flawed as it compares real-time wallet balances against potentially stale ledger balances within transactions.

---

## 2. Lifecycle Execution Flows

### 2.1. Payment Success Flow (Current vs Ideal)

**Current Flow (Actual Runtime):**
1.  **Webhook/Controller** calls `PaymentWebhookService::fulfillPayment`.
2.  `PaymentWebhookService` acquires **Cache Lock**.
3.  `PaymentWebhookService` opens **Outer DB Transaction**.
    *   Locks `Payment` row (`lockForUpdate`).
    *   Locks `Subscription` row (`lockForUpdate`).
    *   Updates `Payment` status to `paid`.
    *   **Commits Transaction**.
4.  `PaymentWebhookService` calls `FinancialOrchestrator::processSuccessfulPayment`.
5.  `FinancialOrchestrator` opens **Inner DB Transaction**.
    *   Locks `Payment`, `Subscription`, `Wallet`, `Product`, `BulkPurchase`.
    *   Calls `WalletService::deposit`.
    *   Calls `AllocationService::allocateSharesLegacy`.
    *   Calls `BonusCalculatorService`.
    *   **Commits Transaction**.

**Flaws:**
*   **Split Transaction**: Status update and financial mutation happen in separate transactions. If the second fails, payment remains `paid` but wallet is not credited (partial failure state).
*   **Double Locking**: Payment and Subscription are locked twice.

**Ideal Flow (Remediation Target):**
1.  **Webhook/Controller** calls `PaymentWebhookService::fulfillPayment`.
2.  `PaymentWebhookService` delegates immediately to `FinancialOrchestrator::executePaymentFulfillment`.
3.  `FinancialOrchestrator` opens **Single Atomic Transaction**.
    *   Locks all resources.
    *   Updates status.
    *   Credits wallet.
    *   Allocates shares.
    *   Records ledger entries.
    *   **Commits**.

### 2.2. Refund/Chargeback Flow (Violations)

**Current Flow:**
*   `PaymentWebhookService::handleChargebackConfirmed` opens its **own DB Transaction**.
*   Directly calls `AllocationService` (Violation).
*   Directly calls `WalletService` (Violation).
*   Manually calculates shortfalls.

**Ideal Flow:**
*   `PaymentWebhookService` delegates to `FinancialOrchestrator::processChargeback`.
*   Orchestrator handles all logic within a single transaction.

---

## 3. Financial Mutation Map & Violations

| File | Method | Mutation / Operation | Orchestrator? | Violation? |
| :--- | :--- | :--- | :--- | :--- |
| `PaymentWebhookService.php` | `handleChargebackConfirmed` | `DB::transaction` | No | **YES** |
| `PaymentWebhookService.php` | `handleChargebackConfirmed` | `lockForUpdate` | No | **YES** |
| `PaymentWebhookService.php` | `handleChargebackConfirmed` | `AllocationService::reverseAllocationLegacy` | No | **YES** |
| `PaymentWebhookService.php` | `handleChargebackConfirmed` | `WalletService::processChargebackAdjustment` | No | **YES** |
| `PaymentWebhookService.php` | `fulfillPayment` | `DB::transaction` | No | **YES** |
| `AdminBonusController.php` | `destroy` | `DB::transaction` | No | **YES** |
| `WalletService.php` | `deposit` | `round((float) $amount * 100)` | N/A | **Precision Risk** |

---

## 4. Lock Order Verification

**Observed Order in Orchestrator:**
1.  `Payment`
2.  `Subscription`
3.  `Wallet`
4.  `Product`
5.  `BulkPurchase`

**Violations:**
*   `PrivacyController` acquires `Wallet` lock directly.
*   `PaymentWebhookService` acquires `Payment` lock outside orchestrator.

---

## 5. Monetary Precision Audit

**Critical Risk**: `WalletService` methods accept `int|float|string` and perform casting:
```php
public function deposit(..., int|float|string $amount, ...) {
    return (int) round((float) $amount * 100);
}
```
This encourages callers to pass floats, introducing precision errors before the call.
**Remediation**: strictly enforce `int` (paise) in all service signatures.

---

## 6. Root Causes of Test Failures

1.  **`Class "App\Helpers\SettingsHelper" not found`**:
    *   **Cause**: The codebase contains a `SettingsHelper.php` file defining a `setting()` function, but tests reference a `SettingsHelper` **class** with static methods (`set`).
    *   **Fix**: Update tests to use the `setting()` function or create a `SettingsHelper` class wrapper.

2.  **`Column not found: unknown column 'purchase_price_per_unit'`**:
    *   **Cause**: Migration delta missing or tests run against outdated schema.
    *   **Fix**: Add migration for `purchase_price_per_unit` to `bulk_purchases` table.

3.  **`SuccessfulPaymentLifecycleTest` Failure**:
    *   **Cause**: Test calls `processSuccessfulPayment` on a `pending` payment. The method returns early because it expects `status === 'paid'`.
    *   **Fix**: Test must call the fulfillment entry point (`fulfillPayment`) or manually set status to `paid` before invoking orchestrator (not recommended as it bypasses logic).

4.  **`MoneyValueObjectStrictnessTest` Errors**:
    *   **Cause**: `Money` class methods (`fromPaise`, `equals`, `add`) are missing or undefined.
    *   **Fix**: Implement missing methods in `App\ValueObjects\Money`.

---

## 7. Remediation Plan

### Phase 1: Immediate Fixes (Test Green)

1.  **Fix Dependencies**:
    *   Create `App\Helpers\SettingsHelper` class to satisfy test requirements (proxy to `setting()` function).
    *   Add migration for `purchase_price_per_unit`.
2.  **Implement Money Value Object**:
    *   Complete `App\ValueObjects\Money` implementation.
3.  **Fix Test Logic**:
    *   Update `SuccessfulPaymentLifecycleTest` to call `PaymentWebhookService::fulfillPayment` instead of accessing orchestrator directly, OR refactor `processSuccessfulPayment` to handle the status transition (preferred for atomic architecture).

### Phase 2: Architecture Enforcement (Refactor)

1.  **Refactor `PaymentWebhookService`**:
    *   Remove `fulfillPayment` transaction logic.
    *   Delegate `fulfillPayment` to `FinancialOrchestrator::executePaymentFulfillment`.
    *   Delegate `handleChargebackConfirmed` to `FinancialOrchestrator::processChargeback`.
2.  **Strict Typing in Services**:
    *   Remove `float|string` support from `WalletService`. Enforce `int` (paise).
3.  **Invariant Guard Update**:
    *   Modify `debugFinancialInvariant` to be transaction-aware (pass current transaction scope) or disable it in high-concurrency paths.

### Phase 3: Cleanup

1.  **Remove Legacy Methods**:
    *   Delete `allocateSharesLegacy` once all calls are routed through `AllocateSharesOperation`.
2.  **Standardize Locking**:
    *   Enforce lock order via a `LockManager` service or strict convention.

---

## 8. Exact Code Locations Requiring Fixes

*   **`backend/app/Services/PaymentWebhookService.php`**: `fulfillPayment`, `handleChargebackConfirmed` (Remove logic, delegate to Orchestrator).
*   **`backend/app/Services/FinancialOrchestrator.php`**: `processSuccessfulPayment` (Update to handle status transition), `processChargeback` (Ensure full logic is present).
*   **`backend/app/Services/WalletService.php`**: `deposit`, `withdraw` (Remove float casting).
*   **`backend/tests/FinancialLifecycle/Lifecycle/SuccessfulPaymentLifecycleTest.php`**: `processPaymentLifecycle` (Update entry point).
*   **`backend/app/ValueObjects/Money.php`**: Complete implementation.
