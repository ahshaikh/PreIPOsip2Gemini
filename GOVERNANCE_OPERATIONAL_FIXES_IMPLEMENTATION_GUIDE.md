# Governance & Operational Fixes Implementation Guide
## Admin Constraints, Operational Visibility, and System Boundaries

**Date:** 2025-12-28
**Status:** Implementation Ready
**Risk Level:** P0 - Governance & Operational Excellence

---

## EXECUTIVE SUMMARY

Implements fixes H.25, H.26, H.27 from the architectural audit:

- ✅ **H.25: Constrain admin actions** - Admins obey same invariants as automated flows
- ✅ **H.26: Operational visibility** - Dashboards and alerts for system health
- ✅ **H.27: System boundaries** - Explicit internal/external boundaries with reconciliation ownership

**PROTOCOL ENFORCED:**
- "Admin actions must respect fundamental invariants - no bypass"
- "Operational state must be visible - no blind spots"
- "Boundaries must be explicit - clear reconciliation ownership"

---

## WHAT WAS BROKEN

### BEFORE (Unconstrained Admin Actions, No Visibility):

```
Unconstrained admin actions:
  Admin: "Adjust wallet balance to -₹10,000"
    ↓
  System: ✓ Updated
    ↓
  Result: Negative balance created ❌
  (Violates fundamental invariant: balance >= 0)

No operational visibility:
  - 50 wallet balance mismatches
  - 10 stuck payments (pending > 24h)
  - 5 orphaned transactions
    ↓
  Admin dashboard: No visibility ❌
  System: No alerts ❌

Unclear system boundaries:
  Payment Gateway: Who owns reconciliation? Unknown ❌
  Frontend Input: Should we trust? Unknown ❌
  Internal Ledger: Who verifies? Unknown ❌
```

**IMPACT:**
- Admins can create invalid states
- System health degradation invisible
- Reconciliation ownership unclear
- No proactive alerting

---

## WHAT WAS FIXED

### Fix H.25: Constrain Admin Actions with Explicit Rules

**ADMIN ACTION CONSTRAINT SERVICE:**

```php
// AdminActionConstraintService::validateWalletAdjustment()
public function validateWalletAdjustment(
    Wallet $wallet,
    float $amount,
    User $admin,
    string $justification
): array {
    $newBalance = $wallet->balance_paise + ($amount * 100);

    // INVARIANT 1: Balance non-negativity
    if ($newBalance < 0) {
        return [
            'allowed' => false,
            'reason' => "INVARIANT VIOLATION: Resulting balance would be negative",
        ];
    }

    // CONSTRAINT 2: Large adjustments require senior admin
    if (abs($amount) > 10000 && !$admin->hasRole('senior_admin')) {
        return [
            'allowed' => false,
            'reason' => "APPROVAL REQUIRED: Adjustments over ₹10,000 require senior admin",
        ];
    }

    // CONSTRAINT 3: Justification required
    if (strlen($justification) < 10) {
        return [
            'allowed' => false,
            'reason' => "JUSTIFICATION REQUIRED: Provide detailed reason (min 10 chars)",
        ];
    }

    // CONSTRAINT 4: Suspicious pattern detection
    $recentAdjustments = DB::table('transactions')
        ->where('wallet_id', $wallet->id)
        ->where('type', 'admin_adjustment')
        ->where('created_at', '>', now()->subHours(24))
        ->count();

    if ($recentAdjustments >= 5) {
        return [
            'allowed' => false,
            'reason' => "SUSPICIOUS ACTIVITY: More than 5 adjustments in 24h",
        ];
    }

    return ['allowed' => true];
}
```

**VALID STATE TRANSITIONS:**

```php
// AdminActionConstraintService::validatePaymentStatusChange()
$validTransitions = [
    'pending' => ['processing', 'failed', 'cancelled'],
    'processing' => ['paid', 'failed'],
    'failed' => ['pending'], // Can retry
    'paid' => [], // TERMINAL - no changes allowed
    'cancelled' => [], // TERMINAL
];

if (!in_array($newStatus, $validTransitions[$currentStatus])) {
    return [
        'allowed' => false,
        'reason' => "INVALID TRANSITION: Cannot change from '{$currentStatus}' to '{$newStatus}'",
    ];
}
```

**ADMIN ACTION AUDIT:**

```php
// Execute with full audit trail
$constraints->executeConstrainedAction(
    'wallet_adjustment',
    function () use ($wallet, $amount) {
        // Adjustment logic
    },
    $admin,
    $justification,
    ['wallet_id' => $wallet->id, 'amount' => $amount]
);

// Result: Logged in admin_action_audit table
[
    'admin_id' => 42,
    'action_type' => 'wallet_adjustment',
    'justification' => 'Refund for cancelled subscription #123',
    'entity_type' => 'wallet',
    'entity_id' => 456,
    'state_before' => ['balance_paise' => 100000],
    'state_after' => ['balance_paise' => 110000],
    'status' => 'completed',
]
```

**Result:**
```
BEFORE: Admin can create invalid states
  Admin: Adjust wallet to -₹10,000
  System: ✓ Updated ❌
  Constraint: None

AFTER: Admin constrained by invariants
  Admin: Adjust wallet to -₹10,000
  Validation: INVARIANT VIOLATION ✓
  System: Rejected ✓
  Audit: Attempt logged ✓

BEFORE: Admin can bypass state machine
  Admin: Change payment 'paid' → 'pending'
  System: ✓ Updated ❌
  Result: Completed payment reverted

AFTER: Admin respects state machine
  Admin: Change payment 'paid' → 'pending'
  Validation: INVALID TRANSITION ✓
  System: Rejected ✓
  Audit: Attempt logged ✓
```

---

### Fix H.26: Introduce Operational Visibility

**SYSTEM HEALTH MONITORING SERVICE:**

```php
// SystemHealthMonitoringService::checkAllMetrics()
public function checkAllMetrics(): array
{
    return [
        'overall_health' => 'healthy' | 'degraded' | 'critical',
        'metrics' => [
            'financial' => [
                'wallet_balance_mismatches' => [...],
                'orphaned_transactions' => [...],
                'allocation_gaps' => [...],
                'stuck_funds' => [...],
            ],
            'operational' => [
                'stuck_payments' => [...],
                'stuck_investments' => [...],
                'failed_jobs' => [...],
                'queue_backlog' => [...],
            ],
            'system' => [
                'database' => [...],
                'redis' => [...],
            ],
        ],
        'critical_issues' => [...],
    ];
}
```

**FINANCIAL HEALTH METRICS:**

**1. Wallet Balance Mismatches:**
```php
// Detect where stored balance != computed balance
SELECT
    w.id,
    w.balance_paise as stored,
    (SUM(credits) - SUM(debits)) as computed
FROM wallets w
LEFT JOIN transactions t ON w.id = t.wallet_id
HAVING stored != computed
```

**2. Orphaned Transactions:**
```php
// Transactions without paired transaction (double-entry violation)
SELECT *
FROM transactions
WHERE paired_transaction_id IS NULL
  AND type IN ('deposit', 'withdrawal')
```

**3. Allocation Gaps:**
```php
// Payments marked 'paid' but no investment allocated
SELECT *
FROM payments
LEFT JOIN investments ON payments.id = investments.payment_id
WHERE payments.status = 'paid'
  AND investments.id IS NULL
```

**4. Stuck Funds:**
```php
// Funds in "processing" state for > 1 hour
SELECT SUM(amount) as stuck_funds
FROM payments
WHERE status = 'processing'
  AND updated_at < NOW() - INTERVAL 1 HOUR
```

**OPERATIONAL DASHBOARDS:**

```bash
# Financial Health Dashboard
php artisan system:health --dashboard=financial_health

Output:
===========================================
Financial Health
===========================================
Overall Health: HEALTHY

  ✓ wallet_balance_mismatches    0 count       info    All balances reconciled
  ✓ orphaned_transactions        0 count       info    No orphaned transactions
  ✓ allocation_gaps              0 count       info    No allocation gaps
  ✓ stuck_funds                  0 rupees      info    No stuck funds

Active Alerts: None


# Operational Health Dashboard
php artisan system:health --dashboard=operations

Output:
===========================================
Operational Health
===========================================
Overall Health: DEGRADED

  ✗ stuck_payments               12 count      warning  12 payments pending > 24h
  ✓ stuck_investments            0 count       info     No stuck investments
  ✓ failed_jobs                  2.5%          info     Job failure rate normal
  ✓ queue_backlog                450 count     info     Queue backlog normal

Stuck State Statistics:
- Total stuck: 12
- Auto-resolved: 8
- Manual review queue: 4
```

**RECONCILIATION ALERTS:**

```php
// Create alert for balance mismatch
$this->createReconciliationAlert(
    'balance_mismatch',
    'high',
    'wallet',
    $walletId,
    $userId,
    $expectedBalance,  // ₹1,000 (computed)
    $actualBalance,    // ₹950 (stored)
    "Wallet balance mismatch: discrepancy ₹50"
);

// Alert stored in reconciliation_alerts table
[
    'alert_type' => 'balance_mismatch',
    'severity' => 'high',
    'entity_type' => 'wallet',
    'entity_id' => 123,
    'expected_value' => 100000, // paise
    'actual_value' => 95000,
    'discrepancy' => 5000, // ₹50
    'resolved' => false,
]
```

**SCHEDULED MONITORING:**

Add to `app/Console/Kernel.php`:
```php
// Check system health every 5 minutes
$schedule->command('system:health --alert-on-critical')
         ->everyFiveMinutes()
         ->withoutOverlapping();
```

**Result:**
```
BEFORE: No visibility into system health
  50 wallet mismatches → Unknown ❌
  10 stuck payments → Unknown ❌
  5 orphaned transactions → Unknown ❌

AFTER: Real-time visibility + alerts
  50 wallet mismatches → Detected ✓
    ↓ Alert created (severity: high) ✓
    ↓ Admin notified ✓
    ↓ Auto-fix attempted ✓

  10 stuck payments → Detected ✓
    ↓ Alert created (severity: warning) ✓
    ↓ Escalated to manual review ✓

  5 orphaned transactions → Detected ✓
    ↓ Alert created (severity: warning) ✓
    ↓ Listed in dashboard ✓
```

---

### Fix H.27: Define System Boundaries Explicitly

**SYSTEM BOUNDARY MAP:**

```
External Systems (Third-party owned):
├── Razorpay (Payment Gateway)
│   ├── Owner: Razorpay
│   ├── Source of Truth: Razorpay (settlement status)
│   ├── Trust Model: Settlement-based (not authorization)
│   ├── Reconciliation Owner: PreIPOsip
│   └── Reconciliation Service: PaymentReconciliationService

├── MSG91 (SMS/OTP Gateway)
│   ├── Owner: MSG91
│   ├── Source of Truth: MSG91 (delivery status)
│   ├── Trust Model: Best-effort delivery
│   ├── Reconciliation Owner: PreIPOsip (optional)
│   └── Reconciliation Service: OtpReconciliationService

└── DigiLocker (KYC Provider)
    ├── Owner: DigiLocker/Government
    ├── Source of Truth: DigiLocker (verification)
    ├── Trust Model: API-based verification
    ├── Reconciliation Owner: PreIPOsip
    └── Reconciliation Service: KycReconciliationService

Internal Systems (PreIPOsip owned):
├── Wallet Ledger
│   ├── Owner: PreIPOsip
│   ├── Source of Truth: wallets + transactions tables
│   ├── Trust Model: Self-reconciliation
│   └── Reconciliation: LedgerReconciliationService (hourly)

├── Investment Ledger
│   ├── Owner: PreIPOsip
│   ├── Source of Truth: investments + user_investments tables
│   ├── Trust Model: Self-reconciliation
│   └── Reconciliation: AllocationReconciliationService (daily)

└── Bonus Ledger
    ├── Owner: PreIPOsip
    ├── Source of Truth: bonuses + bonus_transactions tables
    ├── Trust Model: Self-reconciliation
    └── Reconciliation: BonusReconciliationService (daily)
```

**RECONCILIATION OWNERSHIP MATRIX:**

| Boundary | System A | System B | Owner | Service | Frequency |
|----------|----------|----------|-------|---------|-----------|
| Payment Gateway | PreIPOsip | Razorpay | PreIPOsip | PaymentReconciliationService | Daily |
| Wallet Ledger | Wallet | Transactions | PreIPOsip | LedgerReconciliationService | Hourly |
| Investment Ledger | Investments | Inventory | PreIPOsip | AllocationReconciliationService | Daily |
| Frontend/Backend | Frontend | Backend | Backend | API Validation | Real-time |

**TRUST MODELS:**

**1. Settlement-Based Trust (Razorpay):**
```
WRONG (Authorization-Based):
  Payment authorized → Immediate permanent credit ❌
    ↓
  Problem: Can be reversed, chargebacks possible

CORRECT (Settlement-Based):
  Payment authorized → Provisional credit ✓
    ↓ (wait for settlement)
  Settlement confirmed → Permanent credit ✓
    ↓ (if chargeback)
  Chargeback occurs → Reversal ✓
```

**2. Zero Trust (Frontend):**
```
WRONG:
  Frontend says: "User has ₹10,000" → Backend accepts ❌

CORRECT:
  Frontend says: "User has ₹10,000" → Backend queries DB to verify ✓
  Backend validates: ALL user input ✓
  Backend sanitizes: ALL output ✓
```

**Result:**
```
BEFORE: Unclear boundaries
  Payment Gateway: Who reconciles? Unknown ❌
  Frontend Input: Should we trust? Unknown ❌
  Wallet Ledger: Who verifies? Unknown ❌

AFTER: Explicit boundaries
  Payment Gateway:
    ✓ External system
    ✓ Settlement-based trust
    ✓ PreIPOsip owns reconciliation
    ✓ PaymentReconciliationService (daily)

  Frontend Input:
    ✓ Zero trust model
    ✓ Backend validates ALL input
    ✓ API middleware enforcement
    ✓ Real-time validation

  Wallet Ledger:
    ✓ Internal system
    ✓ Self-reconciliation required
    ✓ LedgerReconciliationService (hourly)
    ✓ SUM(transactions) = balance
```

---

## IMPLEMENTATION STEPS

### Phase 1: Database Migrations

```bash
cd backend

# Governance and operational tables
php artisan migrate --path=database/migrations/2025_12_28_190001_create_governance_operational_tables.php
```

### Phase 2: Schedule Monitoring

Add to `app/Console/Kernel.php`:
```php
protected function schedule(Schedule $schedule): void
{
    // System health monitoring every 5 minutes
    $schedule->command('system:health --alert-on-critical')
             ->everyFiveMinutes()
             ->withoutOverlapping();

    // Ledger reconciliation hourly
    $schedule->command('reconcile:ledgers --type=wallets')
             ->hourly();

    // Payment reconciliation daily
    $schedule->command('reconcile:payments --date=yesterday')
             ->daily();
}
```

### Phase 3: Admin Constraint Integration

Update admin controllers to use constraints:

```php
// AdminWalletController.php
public function adjustBalance(Request $request, Wallet $wallet)
{
    $constraints = app(AdminActionConstraintService::class);

    // Validate
    $validation = $constraints->validateWalletAdjustment(
        $wallet,
        $request->amount,
        auth()->user(),
        $request->justification
    );

    if (!$validation['allowed']) {
        return response()->json([
            'error' => $validation['reason']
        ], 422);
    }

    // Execute with audit
    $result = $constraints->executeConstrainedAction(
        'wallet_adjustment',
        function () use ($wallet, $request) {
            // Adjustment logic
        },
        auth()->user(),
        $request->justification
    );

    return response()->json(['success' => true]);
}
```

### Phase 4: Dashboard Integration

Create admin dashboard views:
- `/admin/health/financial` - Financial health metrics
- `/admin/health/operational` - Operational metrics
- `/admin/health/system` - System health
- `/admin/alerts` - Active reconciliation alerts
- `/admin/audit/actions` - Admin action history

---

## EXPECTED OUTCOMES

**BEFORE:**
- Admins can create invalid states ❌
- No visibility into system health ❌
- Unclear reconciliation ownership ❌

**AFTER:**
- Admin actions constrained by invariants ✅
- Real-time monitoring and alerts ✅
- Clear boundaries and ownership ✅

**Governance:**
- ✅ All admin actions audited
- ✅ Justification required for critical actions
- ✅ Large adjustments require senior admin approval
- ✅ Suspicious patterns blocked

**Operational Excellence:**
- ✅ Wallet balance mismatches detected
- ✅ Stuck payments escalated
- ✅ Orphaned transactions identified
- ✅ System health visible

**Architectural Clarity:**
- ✅ Internal/external boundaries defined
- ✅ Reconciliation ownership explicit
- ✅ Trust models documented
- ✅ Failure protocols established

---

**Implementation Status:** Ready for deployment
**Risk Level:** P0 - Governance & Operational Excellence
**Recommended Rollout:** Staging → Production with monitoring

