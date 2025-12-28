# System Boundaries Definition (H.27)
## Explicit Boundary Clarification and Reconciliation Ownership

**Date:** 2025-12-28
**Status:** Architectural Definition
**Purpose:** Define what is internal vs external and who owns reconciliation at each boundary

---

## EXECUTIVE SUMMARY

Defines clear system boundaries to answer:
- What is INTERNAL (owned by PreIPOsip)?
- What is EXTERNAL (owned by third parties)?
- Who owns RECONCILIATION at each boundary?
- What are TRUST ASSUMPTIONS at each boundary?

**PROTOCOL:**
- "Internal = We control state, We are source of truth"
- "External = They control state, They are source of truth (but verify)"
- "Boundary = Reconciliation required"

---

## BOUNDARY MAP

```
┌─────────────────────────────────────────────────────────────┐
│                    EXTERNAL SYSTEMS                         │
│  (Third-party owned, External source of truth)              │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐         │
│  │  Razorpay   │  │   MSG91     │  │ DigiLocker  │         │
│  │  (Payments) │  │  (SMS/OTP)  │  │    (KYC)    │         │
│  └──────┬──────┘  └──────┬──────┘  └──────┬──────┘         │
│         │                 │                 │                │
│         │ Webhooks        │ API Calls       │ API Calls      │
│         ↓                 ↓                 ↓                │
├─────────────────────────────────────────────────────────────┤
│                    SYSTEM BOUNDARY                          │
│              (Reconciliation Required)                      │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│         ┌───────────────────────────────────┐               │
│         │     PREIPOSITY INTERNAL SYSTEM    │               │
│         │   (We control, We are truth)      │               │
│         ├───────────────────────────────────┤               │
│         │                                   │               │
│         │  ┌──────────┐  ┌──────────┐      │               │
│         │  │  Wallet  │  │Investment│      │               │
│         │  │ (Ledger) │  │ (Shares) │      │               │
│         │  └──────────┘  └──────────┘      │               │
│         │                                   │               │
│         │  ┌──────────┐  ┌──────────┐      │               │
│         │  │  Bonus   │  │ Referral │      │               │
│         │  │ (Rewards)│  │ (Network)│      │               │
│         │  └──────────┘  └──────────┘      │               │
│         │                                   │               │
│         └───────────────────────────────────┘               │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

---

## BOUNDARY 1: Payment Gateway (Razorpay)

### Classification
- **Type:** EXTERNAL
- **Owner:** Razorpay
- **Source of Truth:** Razorpay (for payment status)
- **Trust Model:** Settlement-based trust (not authorization-based)

### What Crosses the Boundary
**Inbound (Razorpay → PreIPOsip):**
- Webhooks: `payment.captured`, `payment.failed`, `refund.processed`
- API Responses: Payment status, settlement status

**Outbound (PreIPOsip → Razorpay):**
- Payment creation requests
- Refund requests
- Status check requests

### Reconciliation Ownership
**Owner:** PreIPOsip (we must reconcile)

**Reconciliation Protocol:**
1. **Idempotency:** Use `payment_gateway_id` as idempotency key
2. **Webhook Verification:** Verify webhook signature
3. **Duplicate Detection:** Check if payment already processed
4. **Settlement Verification:** Verify settlement status before permanent credit
5. **Mismatch Detection:** Detect webhook misses, status mismatches, amount mismatches

**Reconciliation Service:** `PaymentReconciliationService`

**Scheduled Reconciliation:**
```bash
# Daily reconciliation of previous day's payments
php artisan reconcile:payments --date=yesterday

# Weekly full reconciliation
php artisan reconcile:payments --date=last-week --full
```

### Trust Assumptions
- ❌ **DO NOT** trust capture status alone (can be reversed)
- ✅ **DO** trust settlement status (money actually transferred)
- ✅ **DO** verify webhook signatures
- ✅ **DO** handle chargebacks/reversals

### Example Violation
```
BAD: Payment captured → Immediate permanent credit ❌
  (Payment can be reversed, chargeback can occur)

GOOD: Payment captured → Provisional credit
      Settlement confirmed → Permanent credit ✅
```

---

## BOUNDARY 2: SMS/OTP Gateway (MSG91)

### Classification
- **Type:** EXTERNAL
- **Owner:** MSG91
- **Source of Truth:** MSG91 (for delivery status)
- **Trust Model:** Best-effort delivery

### What Crosses the Boundary
**Outbound (PreIPOsip → MSG91):**
- OTP sending requests
- SMS notification requests

**Inbound (MSG91 → PreIPOsip):**
- Delivery status webhooks (optional)

### Reconciliation Ownership
**Owner:** PreIPOsip (optional reconciliation)

**Reconciliation Protocol:**
1. **Delivery Tracking:** Log OTP attempts
2. **Failure Handling:** Retry on failure
3. **Rate Limiting:** Respect SMS limits

**Reconciliation Service:** `OtpReconciliationService` (optional)

### Trust Assumptions
- ❌ **DO NOT** assume SMS always delivered
- ✅ **DO** implement retry mechanism
- ✅ **DO** provide alternative verification methods
- ❌ **DO NOT** block critical flows on SMS delivery

### Example Violation
```
BAD: User cannot login without OTP → SMS failed → User blocked ❌

GOOD: User cannot login without OTP → SMS failed → Retry + Email OTP option ✅
```

---

## BOUNDARY 3: KYC Provider (DigiLocker)

### Classification
- **Type:** EXTERNAL
- **Owner:** DigiLocker / Government
- **Source of Truth:** DigiLocker (for document verification)
- **Trust Model:** API-based verification

### What Crosses the Boundary
**Outbound (PreIPOsip → DigiLocker):**
- Aadhaar verification requests
- PAN verification requests

**Inbound (DigiLocker → PreIPOsip):**
- Verification results
- Document data

### Reconciliation Ownership
**Owner:** PreIPOsip (we must verify)

**Reconciliation Protocol:**
1. **Result Validation:** Verify API response signature
2. **Data Consistency:** Check name/DOB consistency across documents
3. **Expiry Tracking:** Track when verification expires

**Reconciliation Service:** `KycReconciliationService`

### Trust Assumptions
- ✅ **DO** trust DigiLocker verification results
- ✅ **DO** verify API response signatures
- ❌ **DO NOT** allow manual KYC bypass without compliance approval
- ✅ **DO** store verification proof for audit

---

## BOUNDARY 4: Internal Ledger System

### Classification
- **Type:** INTERNAL
- **Owner:** PreIPOsip
- **Source of Truth:** PreIPOsip database
- **Trust Model:** Self-reconciliation

### What Crosses the Boundary
**Nothing** - This is internal

### Components
1. **Wallet Ledger:**
   - Owner: PreIPOsip
   - Source of Truth: `wallets` + `transactions` tables
   - Reconciliation: Internal balance verification

2. **Investment Ledger:**
   - Owner: PreIPOsip
   - Source of Truth: `investments` + `user_investments` tables
   - Reconciliation: Shares allocated vs shares available

3. **Bonus Ledger:**
   - Owner: PreIPOsip
   - Source of Truth: `bonuses` + `bonus_transactions` tables
   - Reconciliation: Bonus awarded vs wallet credited

### Reconciliation Ownership
**Owner:** PreIPOsip (self-reconciliation)

**Reconciliation Protocol:**
1. **Balance Verification:** SUM(transactions) = wallet.balance
2. **Allocation Verification:** SUM(user_investments) <= inventory.available
3. **Audit Trail:** All changes logged immutably

**Reconciliation Service:** `LedgerReconciliationService`

**Scheduled Reconciliation:**
```bash
# Daily ledger reconciliation
php artisan reconcile:ledgers --type=all

# Hourly wallet reconciliation
php artisan reconcile:ledgers --type=wallets
```

### Trust Assumptions
- ✅ **DO** trust database as source of truth
- ✅ **DO** verify internal consistency
- ❌ **DO NOT** allow external systems to modify internal ledgers directly
- ✅ **DO** use database transactions for atomicity

---

## BOUNDARY 5: User Interface (Frontend)

### Classification
- **Type:** SEMI-EXTERNAL (we control code, user controls browser)
- **Owner:** PreIPOsip (code) / User (execution environment)
- **Source of Truth:** Backend API
- **Trust Model:** Zero trust

### What Crosses the Boundary
**Outbound (Backend → Frontend):**
- API responses (JSON)
- Authentication tokens

**Inbound (Frontend → Backend):**
- API requests
- User input data

### Reconciliation Ownership
**Owner:** Backend (we must validate)

**Reconciliation Protocol:**
1. **Input Validation:** Validate ALL user input at API level
2. **Authentication:** Verify JWT tokens
3. **Authorization:** Check permissions for every action
4. **Rate Limiting:** Prevent abuse

**Reconciliation Service:** API middleware validation

### Trust Assumptions
- ❌ **DO NOT** trust frontend data
- ❌ **DO NOT** trust client-side validation
- ✅ **DO** validate EVERY request at API level
- ✅ **DO** use CSRF protection
- ✅ **DO** sanitize user input

### Example Violation
```
BAD: Frontend says "user has ₹10,000" → Backend accepts without verification ❌

GOOD: Frontend says "user has ₹10,000" → Backend queries database to verify ✅
```

---

## RECONCILIATION OWNERSHIP MATRIX

| Boundary | System A | System B | Owner | Service | Frequency |
|----------|----------|----------|-------|---------|-----------|
| Payment Gateway | PreIPOsip | Razorpay | PreIPOsip | PaymentReconciliationService | Daily |
| SMS Gateway | PreIPOsip | MSG91 | PreIPOsip | OtpReconciliationService | Optional |
| KYC Provider | PreIPOsip | DigiLocker | PreIPOsip | KycReconciliationService | On-demand |
| Wallet Ledger | Wallet | Transactions | PreIPOsip | LedgerReconciliationService | Hourly |
| Investment Ledger | Investments | Inventory | PreIPOsip | AllocationReconciliationService | Daily |
| User Input | Frontend | Backend | Backend | API Validation | Real-time |

---

## TRUST MODEL SUMMARY

### Authorization-Based Trust (AVOID)
```
Payment authorized → Trust immediately ❌
  ↓
Problem: Can be reversed, chargebacks possible
```

### Settlement-Based Trust (PREFER)
```
Payment authorized → Provisional credit
  ↓
Settlement confirmed → Permanent credit ✓
  ↓
Chargeback occurs → Reversal ✓
```

### Zero Trust (Frontend)
```
Frontend says X → Never trust directly ❌
  ↓
Backend verifies X → Trust after verification ✓
```

---

## RECONCILIATION FAILURE PROTOCOL

### Level 1: Automatic Resolution
- **Action:** Auto-fix via reconciliation service
- **Examples:** Webhook missed, balance drift < ₹100
- **Approval:** None required
- **Logging:** Full audit trail

### Level 2: Manual Review
- **Action:** Create alert, notify admin
- **Examples:** Balance mismatch > ₹1,000, suspicious pattern
- **Approval:** Admin review required
- **Logging:** Admin action audit

### Level 3: Compliance Escalation
- **Action:** Freeze affected accounts, escalate to compliance
- **Examples:** Fraud detected, regulatory violation
- **Approval:** Compliance team approval
- **Logging:** Regulatory audit trail

---

## IMPLEMENTATION CHECKLIST

**For Each External Integration:**
- [ ] Define boundary clearly (internal vs external)
- [ ] Identify source of truth
- [ ] Implement reconciliation service
- [ ] Schedule regular reconciliation
- [ ] Define trust assumptions
- [ ] Document failure protocol
- [ ] Implement idempotency
- [ ] Add monitoring/alerting

**For Each Internal System:**
- [ ] Define ownership
- [ ] Implement self-reconciliation
- [ ] Add consistency checks
- [ ] Enforce invariants
- [ ] Log all changes immutably

---

**Status:** Architectural Definition Complete
**Next Steps:** Implement reconciliation services for all boundaries
**Review Frequency:** Quarterly or when adding new integrations
