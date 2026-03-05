# Financial Lifecycle Test Suite

**Refactor Target**: "Refactor Payment Lifecycle into Single Financial Orchestration Boundary"

This test suite validates the architectural, financial, and concurrency correctness of the payment lifecycle. Tests are written BEFORE the refactor implementation and are expected to initially fail until the FinancialOrchestrator is implemented.

## Target Architecture

```
PaymentWebhookService
    → FinancialOrchestrator::processSuccessfulPayment()
        → Domain Operations (WalletService, AllocationService, BonusCalculatorService)
        → Domain Services (DoubleEntryLedgerService)
```

**Key Constraint**: Only the FinancialOrchestrator may open DB transactions and acquire row locks.

## Financial Invariants

1. **Single DB transaction** per payment lifecycle
2. **No nested transactions** in domain services
3. **Strict lock order**: Payment → Subscription → Wallet → Product → UserInvestment → BonusTransaction
4. **Wallet passbook behavior**: +Principal Deposit, -Allocation Withdrawal, +Bonus Credit
5. **Allocation invariant**: `amount_paise = allocated_paise + remainder_paise`
6. **Idempotency**: Repeated webhook calls must not duplicate mutations
7. **Paise-only arithmetic**: No floats in lifecycle code

## Directory Structure

```
tests/FinancialLifecycle/
├── FinancialLifecycleTestCase.php      # Base test case with helpers
├── README.md                            # This file
├── Support/
│   ├── ConcurrencyTestHelper.php       # Concurrency simulation utilities
│   └── StaticAnalysisHelper.php        # Code scanning for rule violations
├── TransactionBoundary/
│   ├── SingleTransactionBoundaryTest.php
│   ├── NoNestedTransactionTest.php
│   ├── NoLowerLayerLockingTest.php
│   ├── LockOrderEnforcementTest.php
│   ├── NoAsyncFinancialMutationTest.php
│   ├── LifecycleScopeIsolationTest.php
│   └── OrchestratorEntryOnlyTest.php
├── MonetaryPrecision/
│   ├── FloatEliminationTest.php
│   ├── NoRupeeConversionBridgeTest.php
│   ├── MoneyValueObjectStrictnessTest.php
│   └── PaiseOnlyArithmeticTest.php
├── Allocation/
│   ├── AllocationDeterminismTest.php
│   └── AllocationInvariantTest.php
├── WalletLedger/
│   ├── WalletPassbookSequenceTest.php
│   └── LedgerInvariantTest.php
├── Bonus/
│   └── BonusPaisePrecisionTest.php
├── Idempotency/
│   └── PaymentIdempotencyTest.php
├── Rollback/
│   └── PartialFailureRollbackTest.php
├── Concurrency/
│   └── ConcurrentPaymentProcessingTest.php
└── Lifecycle/
    ├── SuccessfulPaymentLifecycleTest.php
    ├── RefundLifecycleTest.php
    └── ChargebackLifecycleTest.php
```

## Running Tests

```bash
# Run all financial lifecycle tests
php artisan test tests/FinancialLifecycle/

# Run specific category
php artisan test tests/FinancialLifecycle/TransactionBoundary/
php artisan test tests/FinancialLifecycle/MonetaryPrecision/

# Run single test file
php artisan test tests/FinancialLifecycle/TransactionBoundary/SingleTransactionBoundaryTest.php
```

## Test Categories

### Transaction Boundary Tests
Verify that all financial mutations occur within a single transaction boundary managed by FinancialOrchestrator.

### Monetary Precision Tests
Ensure all calculations use integer paise with no float operations or /100 conversions in lifecycle code.

### Allocation Tests
Validate that inventory allocation is deterministic, maintains invariants, and handles remainders correctly.

### Wallet/Ledger Tests
Verify wallet passbook integrity and double-entry ledger balance.

### Idempotency Tests
Ensure duplicate webhooks don't create duplicate financial mutations.

### Rollback Tests
Verify that partial failures roll back ALL changes atomically.

### Concurrency Tests
Test that concurrent payment processing doesn't cause data corruption or deadlocks.

### Lifecycle Scenario Tests
End-to-end tests for successful payments, refunds, and chargebacks.

## Static Analysis

The `StaticAnalysisHelper` scans the codebase for:
- Float usage in financial calculations
- /100 conversions inside lifecycle code
- Nested transactions
- lockForUpdate outside orchestrator
- Async job dispatches inside transactions

## Expected Failures

These tests are designed to FAIL until the refactor is complete:

1. `SingleTransactionBoundaryTest` - Current implementation uses multiple transactions
2. `NoLowerLayerLockingTest` - Domain services currently acquire their own locks
3. `OrchestratorEntryOnlyTest` - FinancialOrchestrator doesn't exist yet

After refactor, all tests should pass.

## Implementation Checklist

- [ ] Create `App\Services\FinancialOrchestrator`
- [ ] Implement `processSuccessfulPayment()` with single transaction
- [ ] Implement `processRefund()` with proper reversals
- [ ] Implement `processChargeback()` with receivable handling
- [ ] Remove DB::transaction() calls from domain services
- [ ] Remove lockForUpdate() calls from domain services
- [ ] Add orchestrator context checking to domain services
- [ ] Use afterCommit for non-critical job dispatches
- [ ] Create Money value object for type-safe amounts
