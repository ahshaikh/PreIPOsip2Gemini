# Demand-Side Fixes Implementation Guide
## KYC Enforcement & Investment Atomicity

**Date:** 2025-12-28
**Status:** Implementation Ready
**Risk Level:** P0 - Architectural

---

## EXECUTIVE SUMMARY

Implements fixes C.8, C.9, C.10 from the architectural audit:

- ✅ **C.8: Block Wallet Funding Before KYC** - No cash ingress until KYC complete
- ✅ **C.9: Ensure Atomicity for Investment Flows** - Automatic refunds if allocation fails
- ✅ **C.10: Prevent Orphan Investments** - No allocations without valid investment chain

**PROTOCOL ENFORCED:**
- "Compliance must gate money BEFORE, not after"
- "If allocation fails after payment, funds must be refunded or held pending"
- "No ownership, rewards, or allocations without completed, valid investment chain"

---

## WHAT WAS BROKEN

### BEFORE (Compliance Bypass):

```
User initiates wallet deposit:
  - User kyc_status: 'pending' ❌
  - Deposit allowed: YES ❌
  - Payment processed: ₹10,000 credited to wallet
  - Result: Cash ingress BEFORE KYC verification

Investment flow:
  - Payment received: ₹5,000
  - Wallet credited: ₹5,000 ✓
  - Allocation attempted: FAILED (insufficient inventory) ❌
  - Result: Money trapped in wallet, no refund, no shares

Orphaned allocations:
  - UserInvestment created with investment_id = 999
  - Investment #999 doesn't exist ❌
  - Bonus calculated on orphan allocation ❌
  - Result: Rewards without valid investment chain
```

**REGULATORY VIOLATIONS:**
- KYC requirement bypassed for cash transactions
- Money trapped in failed states (no compensation)
- Orphaned records creating phantom ownership

---

## WHAT WAS FIXED

### Fix C.8: Block Wallet Funding Before KYC

**ComplianceGateService:**

```php
// Central compliance gate for ALL financial operations
public function canReceiveFunds(User $user): array
{
    // Check 1: KYC must be approved
    if ($user->kyc_status !== 'approved') {
        return [
            'allowed' => false,
            'reason' => 'KYC verification required before receiving funds',
        ];
    }

    // Check 2: Account must be active
    if ($user->status === 'suspended') {
        return ['allowed' => false, ...];
    }

    return ['allowed' => true];
}
```

**Integration Points:**

1. **WalletDepositRequest::authorize()** - Blocks deposit initiation
2. **WalletService::deposit()** - Fail-safe layer blocks external cash ingress
3. **VerifyComplianceOperation** - Orchestrator-level gate (runs FIRST in every saga)

**Enforcement Layers:**

```
Layer 1 (Controller): WalletDepositRequest checks KYC → 403 if not approved
Layer 2 (Service): WalletService checks for external cash types → throws ComplianceBlockedException
Layer 3 (Orchestrator): VerifyComplianceOperation → saga aborts if KYC incomplete
```

**Transaction Type Classification:**

```php
// External cash ingress (KYC REQUIRED):
- PAYMENT_RECEIVED
- WALLET_DEPOSIT

// Internal operations (KYC BYPASSED):
- BONUS_CREDIT
- REFUND
- ADMIN_CREDIT
```

---

### Fix C.9: Ensure Atomicity for Investment Flows

**Already Implemented via Saga Pattern** ✅

**FinancialOrchestrator::executePaymentToInvestment():**

```
Step 1: VerifyComplianceOperation (KYC check)
Step 2: CalculateCampaignBenefitOperation
Step 3: CreditUserWalletOperation (credit ₹5,000)
Step 4: RecordAdminReceiptOperation
Step 5: DebitUserWalletOperation (debit ₹4,500 after discount)
Step 6: RecordCampaignLiabilityOperation
Step 7: AllocateSharesOperation ← FAILS HERE
Step 8: CompleteInvestmentOperation

If Step 7 fails:
  ↓
SagaCoordinator.compensate() (AUTOMATIC):
  - Step 6 compensation (no-op or reverse liability)
  - Step 5 compensation (credit ₹4,500 back to wallet)
  - Step 4 compensation (reverse admin ledger entry)
  - Step 3 compensation (debit ₹5,000 from wallet)
  ↓
Result: User wallet returns to pre-payment state
```

**Saga Execution Record:**

```php
// saga_executions table tracks EVERY financial operation
{
    "saga_id": "550e8400-e29b-41d4-a716-446655440000",
    "status": "compensated",
    "steps_total": 8,
    "steps_completed": 6,
    "failure_step": 7,
    "failure_reason": "Insufficient inventory",
    "compensated_at": "2025-12-28 12:34:56"
}
```

**Guarantees:**
- Money NEVER trapped in limbo
- All state changes reversed on failure
- Admin ledger remains balanced
- Full audit trail of compensation

---

### Fix C.10: Prevent Orphan Investments

**Database Constraints:**

```sql
-- Constraint 1: UserInvestment MUST have parent Investment
ALTER TABLE user_investments
ADD CONSTRAINT check_user_investment_has_parent
CHECK (investment_id IS NOT NULL);

-- Constraint 2: Foreign key with RESTRICT
ALTER TABLE user_investments
ADD FOREIGN KEY (investment_id)
REFERENCES investments(id)
ON DELETE RESTRICT; -- Cannot delete Investment if allocations exist

-- Constraint 3: Reversed allocations cannot be active
ALTER TABLE user_investments
ADD CONSTRAINT check_reversed_not_active
CHECK (
    (is_reversed = FALSE AND status = 'active')
    OR (is_reversed = TRUE AND status != 'active')
);

-- Constraint 4: Positive allocation values
ALTER TABLE user_investments
ADD CONSTRAINT check_user_investment_positive_value
CHECK (value_allocated > 0);

-- Constraint 5: Final amount cannot exceed total
ALTER TABLE investments
ADD CONSTRAINT check_final_not_exceed_total
CHECK (final_amount <= total_amount);
```

**InvestmentIntegrityService:**

```php
// Application-level validation
public function verifyInvestmentChain(Investment $investment): array
{
    $violations = [];

    // Check 1: Investment must have user with approved KYC
    if ($investment->user->kyc_status !== 'approved') {
        $violations[] = "User KYC not approved";
    }

    // Check 2: Investment must have payment reference
    if (!$investment->payment_id) {
        $violations[] = "No payment reference";
    }

    // Check 3: Completed investments MUST have allocations
    if ($investment->status === 'completed') {
        $allocations = UserInvestment::where('investment_id', $investment->id)
            ->where('is_reversed', false)
            ->count();

        if ($allocations === 0) {
            $violations[] = "Marked completed but has no allocations";
        }
    }

    // Check 4: Pending/failed investments must NOT have allocations
    if (in_array($investment->status, ['pending', 'failed'])) {
        if ($allocations > 0) {
            $violations[] = "Has allocations despite status: {$investment->status}";
        }
    }

    return ['is_valid' => empty($violations), 'violations' => $violations];
}
```

**Bonus Distribution Gate:**

```php
// BEFORE distributing any bonus
$integrity = $integrityService->canReceiveBonus($investment, 'referral');

if (!$integrity['allowed']) {
    Log::warning("BONUS BLOCKED: Investment chain invalid", [
        'investment_id' => $investment->id,
        'reason' => $integrity['reason'],
    ]);
    return; // Do NOT distribute bonus
}

// Proceed with bonus distribution
$bonusService->distribute($investment, 'referral');
```

---

## IMPLEMENTATION STEPS

### Phase 1: Deploy Compliance Gate

```bash
cd backend

# 1. ComplianceGateService is already created
# 2. Update WalletDepositRequest to use compliance gate
# 3. Update WalletService::deposit() with enforcement layer

# No database migration needed for this phase
```

**Test Compliance Gate:**

```php
// Test: User with pending KYC cannot deposit
$user = User::factory()->create(['kyc_status' => 'pending']);

$response = $this->actingAs($user)->postJson('/api/user/wallet/deposit', [
    'amount' => 1000,
]);

$response->assertStatus(403);
$response->assertJson([
    'message' => 'KYC verification required before receiving funds',
]);
```

---

### Phase 2: Verify Saga Atomicity

**Orchestration is ALREADY in place** - Just need to verify:

```bash
# Run saga test suite
php artisan test --filter=SagaCoordinatorTest
php artisan test --filter=FinancialOrchestratorTest

# Expected results:
# ✓ Saga compensates on allocation failure
# ✓ User wallet restored to original state
# ✓ Admin ledger remains balanced
# ✓ Saga execution logged in database
```

**Manual Verification:**

1. Create test payment with insufficient inventory
2. Check saga_executions table for compensation record
3. Verify user wallet balance matches pre-payment state
4. Verify admin ledger has matching debit/credit entries

---

### Phase 3: Enforce Investment Integrity

```bash
cd backend

# Run migration to add constraints
php artisan migrate --path=database/migrations/2025_12_28_120001_enforce_investment_chain_integrity.php

# This will:
# - Add foreign keys (user_investments → investments)
# - Add CHECK constraints (positive values, valid states)
# - Create indexes for chain traversal queries
```

**CRITICAL:** Migration may fail if orphaned records exist.

**If migration fails:**

```sql
-- Find orphaned allocations
SELECT ui.*
FROM user_investments ui
LEFT JOIN investments i ON ui.investment_id = i.id
WHERE i.id IS NULL;

-- Options:
-- 1. Delete orphans (if small number):
DELETE FROM user_investments WHERE investment_id NOT IN (SELECT id FROM investments);

-- 2. Create placeholder investments (if many orphans):
INSERT INTO investments (id, user_id, status, ...)
SELECT DISTINCT investment_id, user_id, 'failed', ...
FROM user_investments
WHERE investment_id NOT IN (SELECT id FROM investments);
```

---

### Phase 4: Integrate Integrity Checks

**Update BonusCalculatorService:**

```php
// Before calculating ANY bonus
public function calculateReferralBonus(Investment $investment): float
{
    $integrityService = app(InvestmentIntegrityService::class);

    // GATE: Verify investment chain is valid
    $canReceiveBonus = $integrityService->canReceiveBonus($investment, 'referral');

    if (!$canReceiveBonus['allowed']) {
        Log::warning("BONUS BLOCKED: Invalid investment chain", [
            'investment_id' => $investment->id,
            'reason' => $canReceiveBonus['reason'],
        ]);
        return 0; // No bonus for invalid investments
    }

    // Proceed with bonus calculation...
}
```

**Update CompleteInvestmentOperation:**

```php
public function execute(SagaContext $context): OperationResult
{
    $integrityService = app(InvestmentIntegrityService::class);

    // Verify chain BEFORE marking complete
    $chainResult = $integrityService->verifyInvestmentChain($this->investment);

    if (!$chainResult['is_valid']) {
        return OperationResult::failure(
            "Cannot complete investment: " . implode(', ', $chainResult['violations'])
        );
    }

    // Mark investment as completed
    $this->investment->update(['status' => 'completed']);

    return OperationResult::success('Investment completed');
}
```

---

### Phase 5: Scheduled Integrity Audits

```php
// app/Console/Kernel.php

protected function schedule(Schedule $schedule)
{
    // Daily integrity audit at 2 AM
    $schedule->call(function () {
        $integrityService = app(InvestmentIntegrityService::class);
        $report = $integrityService->auditIntegrity();

        if ($report['severity'] === 'critical') {
            // Alert super-admins
            $admins = User::role('super-admin')->get();
            Notification::send($admins, new InvestmentIntegrityViolationAlert($report));

            Log::critical("INVESTMENT INTEGRITY VIOLATIONS DETECTED", $report);
        } else {
            Log::info("Investment integrity audit complete", [
                'orphans' => $report['orphaned_allocations']['count'],
                'incomplete' => $report['incomplete_investments']['count'],
            ]);
        }
    })->daily()->at('02:00');
}
```

---

## TESTING

### Test 1: KYC Gate Enforcement

```php
public function test_kyc_blocks_wallet_deposit()
{
    $user = User::factory()->create(['kyc_status' => 'pending']);

    $response = $this->actingAs($user)->postJson('/api/user/wallet/deposit', [
        'amount' => 1000,
        'payment_method' => 'razorpay',
    ]);

    $response->assertStatus(403);
    $response->assertJsonFragment(['message' => 'KYC verification required']);
}

public function test_approved_kyc_allows_wallet_deposit()
{
    $user = User::factory()->create(['kyc_status' => 'approved']);

    $response = $this->actingAs($user)->postJson('/api/user/wallet/deposit', [
        'amount' => 1000,
        'payment_method' => 'razorpay',
    ]);

    $response->assertStatus(200); // Deposit initiated
}
```

### Test 2: Saga Compensation

```php
public function test_allocation_failure_refunds_user()
{
    $user = User::factory()->create(['kyc_status' => 'approved']);
    $payment = Payment::factory()->create(['user_id' => $user->id, 'amount' => 5000]);
    $investment = Investment::factory()->create(['user_id' => $user->id, 'payment_id' => $payment->id]);

    // Create scenario: NO inventory available
    BulkPurchase::query()->delete();

    $orchestrator = app(FinancialOrchestrator::class);

    $initialBalance = $user->wallet->balance_paise;

    // Execute saga (will fail at allocation step)
    $result = $orchestrator->executePaymentToInvestment($payment, $investment);

    $this->assertFalse($result->isSuccess());
    $this->assertEquals('Insufficient inventory', $result->getMessage());

    // Verify wallet restored
    $user->wallet->refresh();
    $this->assertEquals($initialBalance, $user->wallet->balance_paise);

    // Verify saga logged compensation
    $sagaExecution = DB::table('saga_executions')
        ->where('metadata->payment_id', $payment->id)
        ->first();

    $this->assertEquals('compensated', $sagaExecution->status);
}
```

### Test 3: Orphan Prevention

```php
public function test_cannot_create_user_investment_without_parent()
{
    $this->expectException(QueryException::class);

    // Attempt to create UserInvestment with non-existent investment_id
    UserInvestment::create([
        'user_id' => 1,
        'investment_id' => 99999, // Does not exist
        'value_allocated' => 1000,
        'units_allocated' => 10,
    ]);

    // Should fail with foreign key constraint violation
}

public function test_cannot_delete_investment_with_allocations()
{
    $investment = Investment::factory()->create();
    UserInvestment::factory()->create(['investment_id' => $investment->id]);

    $this->expectException(QueryException::class);

    // Attempt to delete investment (should fail - RESTRICT constraint)
    $investment->delete();
}
```

---

## EXPECTED OUTCOMES

**BEFORE:**
- Users could deposit money before KYC approval
- Failed allocations trapped funds in wallet (no refund)
- Orphaned allocations allowed bonus calculations without valid investments

**AFTER:**
- KYC required for ALL cash ingress (wallet deposits, payments)
- Failed allocations automatically refunded via saga compensation
- Database constraints prevent orphaned allocations
- Integrity audits detect and alert on violations

**Compliance:**
- ✅ No cash ingress before KYC (regulatory requirement)
- ✅ Full audit trail of all compliance blocks
- ✅ Automatic compensation ensures no trapped funds
- ✅ Investment chain integrity enforced at DB and app levels

**Security:**
- ✅ Cannot bypass KYC gate (enforced at 3 layers)
- ✅ Cannot distribute bonuses on invalid investments
- ✅ Cannot delete investments with active allocations
- ✅ Cannot create orphaned allocations (foreign key constraint)

---

## ROLLBACK PLAN

If issues arise:

```bash
# Rollback investment integrity constraints
php artisan migrate:rollback --step=1

# This removes:
# - Foreign keys (user_investments → investments)
# - CHECK constraints
# - Indexes
```

**Disable KYC enforcement temporarily** (emergency only):

```php
// In ComplianceGateService::enforceComplianceGate()
// Add emergency bypass (requires super-admin approval):

if (setting('emergency_kyc_bypass_enabled', false)) {
    Log::critical("EMERGENCY: KYC enforcement bypassed", [
        'enabled_by' => setting('emergency_bypass_admin_id'),
    ]);
    return; // Skip KYC check
}
```

---

**Implementation Status:** Ready for deployment
**Risk Level:** P0 - Cash ingress compliance violation
**Recommended Rollout:** Staging → Production with monitoring

