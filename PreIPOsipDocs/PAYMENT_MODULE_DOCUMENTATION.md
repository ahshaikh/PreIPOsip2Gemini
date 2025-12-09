# Payment Module Documentation

## Overview

The payment module supports **two payment methods**:
1. **Razorpay** - Online payment gateway (one-time and auto-debit/mandate)
2. **Manual Transfer** - Bank transfer/UPI with proof submission

## ⚡ Recent Updates

### V-FIX-1208: Complete Wallet Accounting (2025-12-08)
**Fixed critical gap in payment flow**: Payment amounts now properly flow through wallet for full transaction transparency.

**Changes:**
- ✅ Payment amount credited to wallet (transaction type: `payment_received`)
- ✅ Bonus amount credited to wallet (transaction type: `bonus_credit`)
- ✅ Total amount debited from wallet for share purchase (transaction type: `share_purchase`)
- ✅ Shares allocated from inventory
- ✅ Complete transaction trail for users and audit compliance

**Impact:**
- Users now see complete payment flow: Payment In → Bonus Added → Shares Purchased
- Wallet balances are accurate (net zero after share purchase)
- Full transaction history available
- Proper double-entry bookkeeping maintained

## ✅ Completed Components

### Backend Implementation

#### 1. **RazorpayService** (`backend/app/Services/RazorpayService.php`)
**Status**: ✅ **COMPLETE**

All methods have been fully implemented:
- ✅ `createOrder()` - Create one-time payment order
- ✅ `fetchPayment()` - Fetch payment details from Razorpay
- ✅ `capturePayment()` - Capture payment (manual capture mode)
- ✅ `refundPayment()` - Process full or partial refund
- ✅ `verifySignature()` - Verify payment signature from checkout
- ✅ `verifyWebhookSignature()` - Verify webhook signature for security
- ✅ `createOrUpdateRazorpayPlan()` - Sync plan with Razorpay
- ✅ `createRazorpaySubscription()` - Create recurring mandate

#### 2. **PaymentController** (`backend/app/Http/Controllers/Api/User/PaymentController.php`)
**Status**: ✅ **COMPLETE**

Endpoints:
- ✅ `POST /user/payment/initiate` - Initiate payment (one-time or auto-debit)
- ✅ `POST /user/payment/verify` - Verify Razorpay payment signature
- ✅ `POST /user/payment/manual` - Submit manual payment proof

#### 3. **Admin PaymentController** (`backend/app/Http/Controllers/Api/Admin/PaymentController.php`)
**Status**: ✅ **COMPLETE** (Enhanced)

Endpoints:
- ✅ `GET /admin/payments` - View all payments with filters
- ✅ `POST /admin/payments/offline` - Record offline payment
- ✅ `POST /admin/payments/{id}/approve` - Approve manual payment
- ✅ `POST /admin/payments/{id}/reject` - Reject manual payment
- ✅ `POST /admin/payments/{id}/refund` - Refund payment with Razorpay integration

**Enhancement**: Refund now includes Razorpay gateway refund processing.

#### 4. **PaymentWebhookService** (`backend/app/Services/PaymentWebhookService.php`)
**Status**: ✅ **COMPLETE**

Handles Razorpay webhook events:
- ✅ `payment.captured` - One-time payment success
- ✅ `subscription.charged` - Recurring auto-debit success
- ✅ `payment.failed` - Payment failure
- ✅ `refund.processed` - Refund confirmation

#### 5. **WebhookController** (`backend/app/Http/Controllers/Api/WebhookController.php`)
**Status**: ✅ **COMPLETE**

- ✅ Signature verification
- ✅ Event routing
- ✅ Webhook logging
- ✅ Retry mechanism

### Frontend Implementation

#### 1. **Payment UI** (`frontend/app/(user)/subscription/page.tsx`)
**Status**: ✅ **COMPLETE** (Enhanced)

Features:
- ✅ Razorpay checkout integration
- ✅ Auto-debit/mandate setup
- ✅ Manual payment modal
- ✅ **Payment verification after Razorpay success** ⭐ NEW
- ✅ Payment history display
- ✅ Invoice download

#### 2. **Manual Payment Modal** (`frontend/components/features/ManualPaymentModal.tsx`)
**Status**: ✅ **COMPLETE**

Features:
- ✅ UPI/QR code display
- ✅ Bank details display
- ✅ UTR/transaction ID input
- ✅ Screenshot upload

#### 3. **Admin Payment Management** (`frontend/app/admin/payments/page.tsx`)
**Status**: ✅ **COMPLETE**

Features:
- ✅ Payment list with filters
- ✅ Manual payment approval/rejection
- ✅ Offline payment recording
- ✅ Refund processing
- ✅ Invoice download
- ✅ Payment proof viewing
- ✅ Fraud alert display

#### 4. **Payment Gateway Settings** (`frontend/app/admin/settings/payment-gateways/page.tsx`)
**Status**: ✅ **COMPLETE**

Features:
- ✅ Razorpay credentials configuration
- ✅ Webhook secret configuration

#### 5. **Razorpay Script Loading** (`frontend/app/layout.tsx`)
**Status**: ✅ **COMPLETE** ⭐ NEW

- ✅ Razorpay checkout script loaded globally

## Payment Flows

### 1. Razorpay One-Time Payment Flow

```
User → Initiate Payment → Backend creates Order
     ↓
Razorpay Checkout Opens
     ↓
User Completes Payment
     ↓
Frontend Verifies Signature → Backend validates
     ↓
Webhook: payment.captured → Payment marked as paid
     ↓
ProcessSuccessfulPaymentJob → Bonuses + Allocations
```

### 2. Razorpay Auto-Debit (Mandate) Flow

```
User → Enable Auto-Debit → Backend creates Subscription
     ↓
Razorpay Mandate Setup
     ↓
User Approves Mandate
     ↓
Frontend Verifies → Backend validates
     ↓
Monthly Webhook: subscription.charged → Auto payment
     ↓
ProcessSuccessfulPaymentJob → Bonuses + Allocations
```

### 3. Manual Payment Flow

```
User → Select Bank Transfer
     ↓
View Bank/UPI Details
     ↓
Make Transfer → Upload Proof + UTR
     ↓
Status: pending_approval
     ↓
Admin Reviews Proof
     ↓
Admin Approves → Status: paid
     ↓
ProcessSuccessfulPaymentJob:
  1. Credit payment amount to wallet (payment_received)
  2. Calculate and credit bonuses (bonus_credit)
  3. Debit wallet for share purchase (share_purchase)
  4. Allocate shares from inventory
  5. Process referrals & lucky draws
```

### 4. Refund Flow

```
Admin → Selects Payment → Choose Refund Options
     ↓
System processes:
- Razorpay Gateway Refund (if applicable)
- Reverse Bonuses (optional)
- Reverse Allocations (optional)
- Credit wallet
     ↓
Payment status: refunded
```

## Database Schema

### payments Table
```sql
- id
- user_id
- subscription_id
- amount
- currency (INR)
- status (pending, paid, failed, refunded, pending_approval)
- payment_type (sip_installment)
- gateway (razorpay, razorpay_auto, manual_transfer, offline)
- gateway_order_id
- gateway_payment_id
- gateway_signature
- method (card, upi, netbanking)
- payment_proof_path
- refunds_payment_id
- paid_at
- is_on_time
- is_flagged
- flag_reason
- retry_count
- failure_reason
- timestamps
```

## Configuration

### Environment Variables
```env
RAZORPAY_KEY=rzp_test_xxxxx
RAZORPAY_SECRET=xxxxx
RAZORPAY_WEBHOOK_SECRET=xxxxx
```

### Database Settings (Configurable via Admin)
- `razorpay_key_id`
- `razorpay_key_secret`
- `razorpay_webhook_secret`
- `min_payment_amount` (default: 1)
- `max_payment_amount` (default: 1000000)
- `payment_grace_period_days` (default: 2)

### Bank Details (for manual payments)
- `bank_account_name`
- `bank_account_number`
- `bank_ifsc`
- `bank_upi_id`
- `bank_qr_code`

## API Endpoints

### User Endpoints
```
POST   /api/user/payment/initiate         - Initiate payment
POST   /api/user/payment/verify           - Verify payment
POST   /api/user/payment/manual           - Submit manual proof
GET    /api/user/payments/{id}/invoice    - Download invoice
```

### Admin Endpoints
```
GET    /api/admin/payments                - List payments
POST   /api/admin/payments/offline        - Record offline payment
POST   /api/admin/payments/{id}/approve   - Approve manual payment
POST   /api/admin/payments/{id}/reject    - Reject manual payment
POST   /api/admin/payments/{id}/refund    - Refund payment
GET    /api/admin/payments/{id}/invoice   - Download invoice
```

### Webhook Endpoint
```
POST   /api/webhook/razorpay              - Razorpay webhook handler
```

## Security Features

✅ **Signature Verification**
- Payment signatures verified using Razorpay SDK
- Webhook signatures verified using HMAC SHA256

✅ **Idempotency**
- Duplicate webhook events are detected and skipped
- Prevents double-crediting

✅ **Authorization**
- Users can only access their own payments
- Admin permissions checked for management operations

✅ **Amount Validation**
- Min/max payment limits enforced
- Validation on both frontend and backend

✅ **Fraud Detection**
- Payments can be flagged for manual review
- Flag reasons tracked

## Testing Checklist

### Razorpay Payment
- [ ] Create subscription
- [ ] Initiate one-time payment
- [ ] Complete payment on Razorpay
- [ ] Verify signature validation
- [ ] Confirm webhook processing
- [ ] Check bonus calculation
- [ ] Verify allocation

### Auto-Debit
- [ ] Enable auto-debit during payment
- [ ] Approve mandate on Razorpay
- [ ] Verify subscription creation
- [ ] Test recurring charge (webhook simulation)

### Manual Payment
- [ ] View bank/UPI details
- [ ] Submit payment proof
- [ ] Admin receives notification
- [ ] Admin approves payment
- [ ] Payment marked as paid
- [ ] Bonuses calculated

### Refund
- [ ] Admin initiates refund
- [ ] Razorpay refund processed
- [ ] Bonuses reversed (if selected)
- [ ] Allocations reversed (if selected)
- [ ] Wallet credited
- [ ] Payment status updated

### Edge Cases
- [ ] Payment failure handling
- [ ] Webhook retry mechanism
- [ ] Duplicate webhook detection
- [ ] Invalid signature rejection
- [ ] Offline payment recording

## Deployment Notes

### Razorpay Configuration
1. Create Razorpay account at https://razorpay.com
2. Get API keys from Dashboard
3. Configure webhook URL: `https://yourdomain.com/api/webhook/razorpay`
4. Set webhook secret in environment

### Database Migration
```bash
php artisan migrate
```

### Testing
```bash
# Use Razorpay test keys
RAZORPAY_KEY=rzp_test_xxxxx
RAZORPAY_SECRET=xxxxx

# Test webhooks locally using ngrok
ngrok http 8000
# Set webhook URL to ngrok URL
```

## Files Modified/Created

### Modified Files
1. ✅ `backend/app/Services/RazorpayService.php` - Completed missing methods
2. ✅ `backend/app/Http/Controllers/Api/Admin/PaymentController.php` - Added Razorpay refund
3. ✅ `backend/app/Jobs/ProcessSuccessfulPaymentJob.php` - Added complete wallet accounting
4. ✅ `frontend/app/(user)/subscription/page.tsx` - Added payment verification
5. ✅ `frontend/app/layout.tsx` - Added Razorpay script

### Existing Complete Files
- `backend/app/Models/Payment.php`
- `backend/app/Http/Controllers/Api/User/PaymentController.php`
- `backend/app/Services/PaymentWebhookService.php`
- `backend/app/Services/WalletService.php`
- `backend/app/Services/AllocationService.php`
- `backend/app/Http/Controllers/Api/WebhookController.php`
- `frontend/components/features/ManualPaymentModal.tsx`
- `frontend/app/admin/payments/page.tsx`
- `frontend/app/admin/settings/payment-gateways/page.tsx`

### Documentation Files
- `PAYMENT_MODULE_DOCUMENTATION.md` - Complete payment module documentation
- `MANUAL_PAYMENT_FLOW_ANALYSIS.md` - Detailed flow analysis and issue identification

## Support

For Razorpay API documentation: https://razorpay.com/docs/api/

For issues or questions, refer to the main README.md or contact the development team.

## Transaction Types

After the wallet accounting fix, the following transaction types are used:

### Payment Flow Transactions
- `payment_received` - Payment amount credited to wallet
- `bonus_credit` - Bonus amount credited to wallet
- `share_purchase` - Total amount debited from wallet for shares

### Other Transaction Types
- `refund` - Refund credited to wallet
- `withdrawal_request` - Withdrawal amount locked
- `reversal` - Reversed withdrawal/bonus
- `admin_adjustment` - Manual admin correction

### Example Transaction Timeline
```
Payment: ₹10,000 | Bonus: ₹1,000

Wallet Transactions:
1. +₹10,000 | payment_received | "Payment received for SIP installment #123"
2. +₹1,000  | bonus_credit     | "SIP Bonus"
3. -₹11,000 | share_purchase   | "Share purchase from Payment #123"

Balance: ₹0 (all funds used for share purchase)
Shares: ₹11,000 worth allocated
```

---

**Last Updated**: 2025-12-08
**Module Status**: ✅ **COMPLETE WITH WALLET ACCOUNTING**
