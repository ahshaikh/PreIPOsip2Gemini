# Feature Documentation

## Overview
This document explains how the key features of the Pre-IPO SIP platform work.

---

## 1. Bonus Calculation Engine

The platform implements a 7-type bonus calculation engine that rewards users for consistent investment behavior.

### Bonus Types

#### 1.1 Consistency Bonus
Awarded for every on-time payment to encourage regular contributions.

**Configuration:**
```json
{
  "config_key": "consistency_config",
  "value": {
    "amount_per_payment": 50
  }
}
```

**Calculation:**
```
bonus = amount_per_payment × multiplier
```

**Example:** With a 1.5x multiplier from referral: 50 × 1.5 = ₹75

#### 1.2 Progressive Bonus
Increases over time to reward long-term commitment. Starts after a configurable month.

**Configuration:**
```json
{
  "config_key": "progressive_config",
  "value": {
    "rate": 0.5,
    "start_month": 4
  }
}
```

**Calculation:**
```
if (current_month >= start_month):
    months_active = current_month - start_month + 1
    bonus = months_active × (rate / 100) × payment_amount × multiplier
```

**Example:** Month 6 with ₹5000 payment:
- months_active = 6 - 4 + 1 = 3
- bonus = 3 × 0.005 × 5000 × 1.0 = ₹75

#### 1.3 Milestone Bonus
One-time bonus awarded at specific payment milestones (e.g., 6 months, 12 months).

**Configuration:**
```json
{
  "config_key": "milestone_config",
  "value": [
    {"month": 6, "amount": 1000},
    {"month": 12, "amount": 2500},
    {"month": 24, "amount": 5000}
  ]
}
```

**Calculation:**
```
if (consecutive_payments_count == milestone_month):
    bonus = milestone_amount × multiplier
```

#### 1.4 Referral Bonus
Awarded when a referred user makes their first payment.

**Flow:**
1. User A shares their referral code
2. User B registers with the code
3. User B subscribes and makes first payment
4. User A receives referral bonus

**Configuration:**
- Base amount from settings: `referral_bonus_amount`
- Active campaign multiplier applied

#### 1.5 Celebration Bonus
Special bonus for birthdays and anniversaries.

**Types:**
- Birthday bonus: Awarded on user's birthday
- Anniversary bonus: Awarded on subscription anniversary

**Configuration:**
```json
{
  "config_key": "celebration_config",
  "value": {
    "birthday_bonus": 500,
    "anniversary_bonus_per_year": 200
  }
}
```

#### 1.6 Jackpot Bonus (Lucky Draw)
Random monthly draw from eligible participants.

**Eligibility:**
- Active subscription
- On-time payment in the draw month
- KYC verified

**Process:**
1. Admin creates a Lucky Draw with prize pool
2. System collects eligible entries at month end
3. Admin executes draw
4. Winners selected randomly
5. Prizes credited to wallets

#### 1.7 Profit Share
Periodic distribution of company profits to eligible users.

**Eligibility:**
- Minimum subscription tenure (configurable)
- Active status
- KYC verified

**Calculation:**
```
user_share = (user_eligible_balance / total_eligible_pool) × profit_amount
```

### Multiplier System

All bonuses (except referral) can be multiplied based on:
1. **Referral Tier:** Users referred through campaigns get bonus multipliers
2. **Campaign Multiplier:** Active referral campaigns can boost multipliers
3. **Loyalty Multiplier:** Long-term users may get enhanced multipliers

**Storage:** `subscriptions.bonus_multiplier` field

---

## 2. Wallet System

### Architecture

The wallet system uses a double-entry ledger pattern for financial integrity.

**Models:**
- `Wallet`: Main balance holder
- `Transaction`: Individual ledger entries

### Balance Types

| Field | Description |
|-------|-------------|
| `balance` | Available funds for withdrawal |
| `locked_balance` | Funds locked for pending withdrawals |

### Operations

#### Deposit
```php
$walletService->deposit($user, $amount, 'deposit', 'Description');
```

**Process:**
1. Acquire pessimistic lock on wallet row
2. Increment balance
3. Create transaction record with balance_before/after
4. Release lock

#### Withdrawal
```php
// Immediate debit
$walletService->withdraw($user, $amount, 'withdrawal', 'Description', null, false);

// Lock for pending withdrawal
$walletService->withdraw($user, $amount, 'withdrawal_request', 'Description', null, true);
```

**Process (with lock):**
1. Acquire pessimistic lock
2. Check available balance
3. Decrement balance, increment locked_balance
4. Create pending transaction
5. Release lock

#### Unlock Funds
```php
$walletService->unlockFunds($user, $amount, 'reversal', 'Withdrawal cancelled');
```

**Process:**
1. Acquire lock
2. Decrement locked_balance
3. Increment balance
4. Create completed transaction

### Transaction Types

| Type | Description |
|------|-------------|
| `deposit` | Manual deposit |
| `withdrawal` | Completed withdrawal |
| `withdrawal_request` | Pending withdrawal |
| `bonus_credit` | Bonus awarded |
| `admin_adjustment` | Manual admin adjustment |
| `refund` | Payment refund |
| `reversal` | Withdrawal reversal |

### Concurrency Safety

The `WalletService` uses `lockForUpdate()` to prevent race conditions:

```php
$wallet = $user->wallet()->lockForUpdate()->first();
```

This ensures only one process can modify a wallet at a time.

---

## 3. Subscription Lifecycle

### States

```
pending → active → paused → active → cancelled
                ↳→ cancelled
```

| State | Description |
|-------|-------------|
| `pending` | Created, awaiting first payment |
| `active` | Running normally |
| `paused` | Temporarily suspended by user |
| `cancelled` | Permanently terminated |

### Payment Flow

```
1. User initiates payment
2. Razorpay order created
3. User completes payment in Razorpay
4. Webhook received: payment.captured
5. Payment record updated to 'paid'
6. Subscription status updated to 'active'
7. consecutive_payments_count incremented
8. Bonus calculation triggered
```

### Auto-Debit

For users with auto-debit enabled:

1. Scheduled job runs daily
2. Identifies subscriptions due for payment
3. Creates Razorpay subscription charge
4. Processes webhook response
5. Updates payment and subscription records

### Missed Payments

- Grace period: 7 days (configurable)
- After grace period: consecutive_payments_count resets to 0
- After 3 missed payments: subscription auto-cancelled

---

## 4. KYC Verification

### States

```
pending → submitted → verified
                   ↳→ rejected → submitted → verified
```

### Process

1. **User Submits KYC:**
   - PAN number with document
   - Address proof
   - Selfie for verification

2. **Admin Reviews:**
   - Documents visible in KYC queue
   - Can approve or reject with reason

3. **Verification:**
   - On approval: `status = 'verified'`, `verified_at = now()`
   - On rejection: `status = 'rejected'`, `rejection_reason = '...'`

### DigiLocker Integration

Optional automated verification via DigiLocker:

1. User clicks "Verify with DigiLocker"
2. Redirected to DigiLocker OAuth
3. User authorizes access
4. Callback receives verified documents
5. Auto-verification if documents match

---

## 5. Referral System

### Structure

```
User A (referrer)
   ↓ referral_code
User B (referred)
   ↓ refers
User C (chain referral)
```

### Referral Campaigns

Admins can create time-limited campaigns with:
- Custom multiplier (max 5.0x)
- Bonus amount (max ₹10,000)
- Start/end dates

**Security Caps:**
```php
private const MAX_MULTIPLIER = 5.0;
private const MAX_BONUS_AMOUNT = 10000;
```

### Bonus Application

1. User registers with referral code
2. Referral record created (status: pending)
3. User subscribes and pays
4. Referral status → active
5. Referrer receives bonus
6. Referred user's subscription gets multiplier

---

## 6. Support Ticket System

### Ticket Lifecycle

```
open → in_progress → resolved → closed
                            ↳→ reopened → in_progress
```

### Features

- **Categories:** general, payment, kyc, withdrawal, technical
- **Priority:** low, medium, high, urgent
- **Auto-assignment:** Based on category rules
- **Canned Responses:** Pre-defined admin replies
- **SLA Tracking:** Response time monitoring
- **Rating:** User can rate resolution (1-5 stars)

### Escalation Rules

1. High priority + no response in 2 hours → escalate
2. Urgent priority → immediate admin notification
3. Reopened tickets → higher priority

---

## 7. Admin Features

### User Management

- **List/Search:** Filter by status, search by email/mobile
- **Detail View:** Controlled data exposure, masked PAN
- **Balance Adjustment:** Credit/debit with audit trail
- **Suspend/Activate:** With reason logging
- **Bulk Actions:** Mass activate, suspend, or bonus award
- **Import/Export:** CSV upload with validation

### Dashboard Metrics

Real-time aggregations:
- Total users, new registrations
- Active subscriptions, MRR
- Payment volume (daily, monthly)
- Pending queues (KYC, withdrawal)
- Support ticket stats

### Audit Trail

All admin actions logged:
- Action type
- Target resource
- Before/after values
- IP address
- Timestamp

---

## 8. Security Features

### Authentication

- **Sanctum Tokens:** Stateless API authentication
- **2FA:** TOTP-based two-factor authentication
- **Recovery Codes:** Backup codes for 2FA recovery
- **Session Management:** Token expiration, concurrent session limits

### Authorization

- **Role-Based:** admin, super-admin, user
- **Permission-Based:** Fine-grained permissions per action
- **IP Whitelist:** Admin routes restricted to whitelisted IPs

### Input Validation

- **Financial Amounts:** Strict decimal validation
- **File Uploads:** Type, size, content validation
- **Color Inputs:** Hex format regex validation
- **GA IDs:** Format validation for analytics IDs

### Rate Limiting

| Endpoint | Limit |
|----------|-------|
| Login | 5/min |
| Registration | 3/min |
| Withdrawal | 5/min |
| API General | 60/min |

### Webhook Security

- **Signature Verification:** HMAC validation for Razorpay
- **Replay Prevention:** Timestamp validation
- **Idempotency:** Duplicate event handling

---

## 9. Notification System

### Channels

- **Email:** Transactional emails via configured provider
- **SMS:** OTP, alerts via Twilio/MSG91
- **Push:** Browser push notifications
- **In-App:** Database-stored notifications

### Templates

Customizable templates for:
- Welcome email
- Payment confirmation
- KYC status updates
- Bonus credited
- Withdrawal processed
- Password reset

### Preferences

Users can configure:
- Email notifications (on/off per type)
- SMS notifications
- Push notifications
- Marketing communications

---

## 10. Reporting System

### Available Reports

1. **Financial Summary:**
   - Revenue by period
   - Payment method breakdown
   - Refund statistics

2. **User Analytics:**
   - Registration trends
   - Retention metrics
   - Churn analysis

3. **Product Performance:**
   - Subscription by plan
   - Bonus distribution
   - Referral effectiveness

4. **Inventory Summary:**
   - Product stock levels
   - Allocation status
   - Bulk purchase tracking

### Export Formats

- PDF (formatted reports)
- CSV (raw data)
- Excel (with charts)

---

## Architecture Diagrams

### Payment Flow

```
┌──────────┐     ┌──────────┐     ┌───────────┐
│  User    │────▶│ Frontend │────▶│  Backend  │
└──────────┘     └──────────┘     └───────────┘
                      │                 │
                      │    initiate     │
                      │────────────────▶│
                      │                 │
                      │   order_id      │
                      │◀────────────────│
                      │                 │
                      ▼                 │
              ┌──────────────┐         │
              │   Razorpay   │         │
              │   Checkout   │         │
              └──────────────┘         │
                      │                 │
                      │   webhook       │
                      │────────────────▶│
                      │                 │
                      │   verify        │
                      │────────────────▶│
                      │                 │
                      │   success       │
                      │◀────────────────│
```

### Bonus Calculation Flow

```
┌────────────┐
│  Payment   │
│  Captured  │
└─────┬──────┘
      │
      ▼
┌────────────────────┐
│ BonusCalculator    │
│ Service            │
└─────┬──────────────┘
      │
      ├──▶ Calculate Consistency Bonus
      │
      ├──▶ Calculate Progressive Bonus (if eligible)
      │
      ├──▶ Check Milestone Bonus
      │
      ├──▶ Apply Multiplier
      │
      ▼
┌────────────────────┐
│ WalletService      │
│ deposit()          │
└─────┬──────────────┘
      │
      ▼
┌────────────────────┐
│ Transaction        │
│ Created            │
└────────────────────┘
```
