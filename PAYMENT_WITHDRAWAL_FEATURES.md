# Payment & Withdrawal Configuration Features

## Overview

This document provides comprehensive documentation for all 17 Payment & Withdrawal configuration features implemented in the PreIPOsip2Gemini application. These features enable complete control over payment processing, gateway configuration, withdrawal management, and financial analytics.

## Table of Contents

1. [Payment Gateway Setup](#1-payment-gateway-setup)
2. [Payment Methods Configuration](#2-payment-methods-configuration)
3. [Auto-Debit Configuration](#3-auto-debit-configuration)
4. [View All Payments with Filters](#4-view-all-payments-with-filters)
5. [View Payment Details](#5-view-payment-details)
6. [Manual Payment Entry](#6-manual-payment-entry)
7. [Refund Payment](#7-refund-payment)
8. [Handle Failed Payments](#8-handle-failed-payments)
9. [Withdrawal Settings](#9-withdrawal-settings)
10. [Withdrawal Fee Tiers](#10-withdrawal-fee-tiers)
11. [View Withdrawal Queue](#11-view-withdrawal-queue)
12. [View Withdrawal Details](#12-view-withdrawal-details)
13. [Approve/Reject/Process Withdrawal](#13-approverejectprocess-withdrawal)
14. [Bulk Withdrawal Processing](#14-bulk-withdrawal-processing)
15. [Withdrawal Analytics](#15-withdrawal-analytics)
16. [Payment Analytics](#16-payment-analytics)
17. [Export Functionality](#17-export-functionality)

---

## 1. Payment Gateway Setup

Configure multiple payment gateways (Razorpay, Stripe, Paytm) for processing payments.

### API Endpoint
```
GET  /api/v1/admin/payment-gateways
PUT  /api/v1/admin/payment-gateways
```

### Get Gateway Settings

**Request:**
```bash
curl -X GET "https://api.example.com/api/v1/admin/payment-gateways" \
  -H "Authorization: Bearer {token}"
```

**Response:**
```json
{
  "gateways": {
    "razorpay": {
      "enabled": true,
      "key": "rzp_live_xxxxxxxxxx",
      "secret_set": true
    },
    "stripe": {
      "enabled": false,
      "key": "",
      "secret_set": false
    },
    "paytm": {
      "enabled": false
    }
  }
}
```

### Update Gateway Settings

**Request:**
```bash
curl -X PUT "https://api.example.com/api/v1/admin/payment-gateways" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "gateway": "razorpay",
    "enabled": true,
    "key": "rzp_live_xxxxxxxxxx",
    "secret": "secret_key_here"
  }'
```

**Response:**
```json
{
  "message": "Gateway settings updated successfully"
}
```

### Configuration Settings

All gateway settings are stored in the `settings` table:

- `payment_gateway_razorpay_enabled` (boolean)
- `payment_gateway_razorpay_key` (string)
- `payment_gateway_razorpay_secret` (string)
- `payment_gateway_stripe_enabled` (boolean)
- `payment_gateway_stripe_key` (string)
- `payment_gateway_stripe_secret` (string)
- `payment_gateway_paytm_enabled` (boolean)

---

## 2. Payment Methods Configuration

Enable/disable payment methods and configure transaction fees.

### API Endpoint
```
GET  /api/v1/admin/payment-methods
PUT  /api/v1/admin/payment-methods
```

### Get Payment Methods

**Request:**
```bash
curl -X GET "https://api.example.com/api/v1/admin/payment-methods" \
  -H "Authorization: Bearer {token}"
```

**Response:**
```json
{
  "methods": {
    "upi": {
      "enabled": true,
      "fee": 0,
      "fee_percent": 0
    },
    "card": {
      "enabled": true,
      "fee": 0,
      "fee_percent": 2
    },
    "netbanking": {
      "enabled": true,
      "fee": 0,
      "fee_percent": 1
    },
    "wallet": {
      "enabled": true
    }
  }
}
```

### Update Payment Method

**Request:**
```bash
curl -X PUT "https://api.example.com/api/v1/admin/payment-methods" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "method": "card",
    "enabled": true,
    "fee": 10,
    "fee_percent": 2.5
  }'
```

**Response:**
```json
{
  "message": "Payment method settings updated successfully"
}
```

### Fee Calculation

Fees are calculated as: `total_fee = flat_fee + (amount × fee_percent / 100)`

Example:
- Amount: ₹10,000
- Flat Fee: ₹10
- Percentage Fee: 2%
- Total Fee: ₹10 + (₹10,000 × 0.02) = ₹210

---

## 3. Auto-Debit Configuration

Configure automatic payment debit settings for subscriptions.

### API Endpoint
```
GET  /api/v1/admin/auto-debit-config
PUT  /api/v1/admin/auto-debit-config
```

### Get Auto-Debit Config

**Request:**
```bash
curl -X GET "https://api.example.com/api/v1/admin/auto-debit-config" \
  -H "Authorization: Bearer {token}"
```

**Response:**
```json
{
  "config": {
    "enabled": true,
    "max_retries": 3,
    "retry_interval_days": 1,
    "reminder_days": 3,
    "suspend_after_max_retries": true
  }
}
```

### Update Auto-Debit Config

**Request:**
```bash
curl -X PUT "https://api.example.com/api/v1/admin/auto-debit-config" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "enabled": true,
    "max_retries": 5,
    "retry_interval_days": 2,
    "reminder_days": 5,
    "suspend_after_max_retries": true
  }'
```

### Auto-Debit Flow

1. **Reminder**: Sent 3 days before payment due date (configurable)
2. **Initial Debit Attempt**: On the due date
3. **Retry Attempts**: Up to 3 retries (configurable) with 1-day intervals
4. **Suspension**: Subscription suspended if all retries fail

---

## 4. View All Payments with Filters

View and filter all payment transactions in the system.

### API Endpoint
```
GET  /api/v1/admin/payments
```

### Request with Filters

**Request:**
```bash
curl -X GET "https://api.example.com/api/v1/admin/payments?status=paid&flagged=true&search=john&page=1" \
  -H "Authorization: Bearer {token}"
```

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "user": {
        "id": 10,
        "username": "john_doe",
        "email": "john@example.com"
      },
      "subscription": {
        "plan": {
          "id": 1,
          "name": "Gold Plan",
          "monthly_amount": 5000
        }
      },
      "amount": 5000,
      "status": "paid",
      "gateway": "razorpay",
      "method": "upi",
      "payment_method": "upi",
      "is_flagged": true,
      "flag_reason": "Unusual payment pattern",
      "paid_at": "2025-12-09T10:30:00Z",
      "created_at": "2025-12-09T10:25:00Z"
    }
  ],
  "current_page": 1,
  "per_page": 20,
  "total": 150
}
```

### Available Filters

- `status`: Filter by payment status (pending, paid, failed, refunded, all)
- `flagged`: Show only flagged payments (true/false)
- `search`: Search by username or gateway_payment_id
- `page`: Pagination

---

## 5. View Payment Details

Get detailed information about a specific payment.

### API Endpoint
```
GET  /api/v1/admin/payments/{payment}
```

### Request

**Request:**
```bash
curl -X GET "https://api.example.com/api/v1/admin/payments/1" \
  -H "Authorization: Bearer {token}"
```

**Response:**
```json
{
  "payment": {
    "id": 1,
    "user": {
      "id": 10,
      "username": "john_doe",
      "email": "john@example.com",
      "mobile": "9876543210"
    },
    "subscription": {
      "plan": {
        "id": 1,
        "name": "Gold Plan",
        "monthly_amount": 5000
      }
    },
    "amount": 5000,
    "currency": "INR",
    "status": "paid",
    "gateway": "razorpay",
    "gateway_order_id": "order_xyz123",
    "gateway_payment_id": "pay_abc456",
    "method": "upi",
    "payment_method": "upi",
    "payment_metadata": {
      "vpa": "user@upi",
      "bank": "ICICI"
    },
    "paid_at": "2025-12-09T10:30:00Z",
    "is_on_time": true,
    "is_flagged": false,
    "bonuses": [
      {
        "id": 1,
        "type": "on_time_payment",
        "gross_amount": 250,
        "net_amount": 250
      }
    ]
  },
  "transactions": [
    {
      "id": 1,
      "transaction_id": "uuid-here",
      "type": "deposit",
      "amount": 5000,
      "description": "Payment for subscription",
      "created_at": "2025-12-09T10:30:00Z"
    }
  ]
}
```

---

## 6. Manual Payment Entry

Record offline payments made by users.

### API Endpoint
```
POST  /api/v1/admin/payments/offline
```

### Request

**Request:**
```bash
curl -X POST "https://api.example.com/api/v1/admin/payments/offline" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "user_id": 10,
    "amount": 5000,
    "payment_date": "2025-12-09",
    "reference_id": "NEFT123456",
    "method": "bank_transfer"
  }'
```

**Response:**
```json
{
  "message": "Offline payment recorded and processed."
}
```

### Validation Rules

- `user_id`: Required, must exist
- `amount`: Required, between min_payment_amount and max_payment_amount
- `payment_date`: Required, valid date
- `reference_id`: Required, unique identifier
- `method`: Required

### Processing

Offline payments trigger the same processing as online payments:
1. Allocate funds to investment plans
2. Award applicable bonuses
3. Update subscription status
4. Create wallet transactions

---

## 7. Refund Payment

Refund paid payments with optional bonus/allocation reversal.

### API Endpoint
```
POST  /api/v1/admin/payments/{payment}/refund
```

### Request

**Request:**
```bash
curl -X POST "https://api.example.com/api/v1/admin/payments/1/refund" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "reason": "User requested refund",
    "reverse_bonuses": true,
    "reverse_allocations": true
  }'
```

**Response:**
```json
{
  "message": "Payment refunded and all actions reversed."
}
```

### Refund Process

**If `reverse_bonuses` is true:**
1. Find all bonuses linked to the payment
2. Create negative bonus transactions
3. Debit bonus amounts from user wallet

**If `reverse_allocations` is true:**
1. Reverse plan allocations
2. Update investment records

**Gateway Refund:**
- For Razorpay/Stripe payments, gateway refund is initiated
- Refund amount credited to user's wallet
- Payment status changed to 'refunded'

**Important:**
- Only `paid` payments can be refunded
- Refund window: Configurable via `payment_refund_allowed_days` setting (default: 30 days)

---

## 8. Handle Failed Payments

Manage and retry failed payments.

### API Endpoints
```
GET   /api/v1/admin/payments/failed
POST  /api/v1/admin/payments/{payment}/retry
```

### View Failed Payments

**Request:**
```bash
curl -X GET "https://api.example.com/api/v1/admin/payments/failed?retry_count=2" \
  -H "Authorization: Bearer {token}"
```

**Response:**
```json
{
  "data": [
    {
      "id": 5,
      "user": {
        "id": 15,
        "username": "jane_doe",
        "email": "jane@example.com"
      },
      "subscription": {
        "plan": {
          "name": "Silver Plan"
        }
      },
      "amount": 3000,
      "status": "failed",
      "retry_count": 2,
      "failure_reason": "Insufficient funds",
      "created_at": "2025-12-08T10:00:00Z"
    }
  ],
  "current_page": 1,
  "per_page": 25
}
```

### Retry Failed Payment

**Request:**
```bash
curl -X POST "https://api.example.com/api/v1/admin/payments/5/retry" \
  -H "Authorization: Bearer {token}"
```

**Response:**
```json
{
  "message": "Payment retry initiated"
}
```

### Retry Logic

1. Retry job is queued for asynchronous processing
2. Gateway charges the payment method again
3. On success: Payment marked as paid, subscription updated
4. On failure: Retry count incremented
5. Max retries: Configurable via `auto_debit_max_retries` (default: 3)

---

## 9. Withdrawal Settings

Configure withdrawal processing settings.

### API Endpoint
```
GET  /api/v1/admin/withdrawal-settings
PUT  /api/v1/admin/withdrawal-settings
```

### Get Withdrawal Settings

**Request:**
```bash
curl -X GET "https://api.example.com/api/v1/admin/withdrawal-settings" \
  -H "Authorization: Bearer {token}"
```

**Response:**
```json
{
  "settings": {
    "enabled": true,
    "min_amount": 1000,
    "auto_approval_max_amount": 5000,
    "tds_rate": 0.10,
    "tds_threshold": 5000,
    "processing_days": 3,
    "priority_processing_enabled": true,
    "priority_threshold": 50000,
    "bulk_processing_limit": 50
  }
}
```

### Update Withdrawal Settings

**Request:**
```bash
curl -X PUT "https://api.example.com/api/v1/admin/withdrawal-settings" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "enabled": true,
    "min_amount": 500,
    "auto_approval_max_amount": 10000,
    "tds_rate": 0.10,
    "tds_threshold": 5000,
    "processing_days": 2,
    "priority_processing_enabled": true,
    "priority_threshold": 100000,
    "bulk_processing_limit": 100
  }'
```

### Setting Descriptions

- **enabled**: Enable/disable withdrawal functionality
- **min_amount**: Minimum withdrawal amount
- **auto_approval_max_amount**: Max amount for auto-approval (trusted users only)
- **tds_rate**: TDS percentage (0.10 = 10%)
- **tds_threshold**: Amount above which TDS is deducted
- **processing_days**: Standard processing time
- **priority_processing_enabled**: Enable priority processing for high-value withdrawals
- **priority_threshold**: Amount threshold for priority processing
- **bulk_processing_limit**: Max withdrawals per bulk operation

---

## 10. Withdrawal Fee Tiers

Configure tiered fee structure for withdrawals.

### API Endpoint
```
GET  /api/v1/admin/withdrawal-fee-tiers
PUT  /api/v1/admin/withdrawal-fee-tiers/{tier}
```

### Get Fee Tiers

**Request:**
```bash
curl -X GET "https://api.example.com/api/v1/admin/withdrawal-fee-tiers" \
  -H "Authorization: Bearer {token}"
```

**Response:**
```json
{
  "tiers": [
    {
      "tier": 1,
      "max_amount": 5000,
      "flat_fee": 0,
      "percent_fee": 0
    },
    {
      "tier": 2,
      "max_amount": 25000,
      "flat_fee": 10,
      "percent_fee": 0.5
    },
    {
      "tier": 3,
      "max_amount": 100000,
      "flat_fee": 25,
      "percent_fee": 1
    },
    {
      "tier": 4,
      "max_amount": null,
      "flat_fee": 50,
      "percent_fee": 1.5
    }
  ]
}
```

### Update Fee Tier

**Request:**
```bash
curl -X PUT "https://api.example.com/api/v1/admin/withdrawal-fee-tiers/2" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "max_amount": 30000,
    "flat_fee": 15,
    "percent_fee": 0.75
  }'
```

### Fee Calculation Example

**Withdrawal Amount: ₹15,000**

Tier 2 applies (₹5,001 - ₹25,000):
- Flat Fee: ₹10
- Percentage Fee: 0.5% of ₹15,000 = ₹75
- **Total Fee: ₹85**
- **Net Amount: ₹14,915**

**TDS Calculation (if applicable):**
- If amount > ₹5,000 and user has PAN:
- TDS: 10% of ₹15,000 = ₹1,500
- **Final Net Amount: ₹13,415**

---

## 11. View Withdrawal Queue

View and filter withdrawal requests.

### API Endpoint
```
GET  /api/v1/admin/withdrawal-queue
```

### Request

**Request:**
```bash
curl -X GET "https://api.example.com/api/v1/admin/withdrawal-queue?status=pending&page=1" \
  -H "Authorization: Bearer {token}"
```

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "user": {
        "id": 10,
        "username": "john_doe"
      },
      "amount": 15000,
      "fee": 85,
      "tds_deducted": 1500,
      "net_amount": 13415,
      "status": "pending",
      "priority": "normal",
      "bank_details": {
        "account_number": "****3456",
        "ifsc": "ICIC0001234",
        "bank_name": "ICICI Bank"
      },
      "created_at": "2025-12-09T09:00:00Z"
    }
  ],
  "current_page": 1,
  "per_page": 25
}
```

### Status Values

- **pending**: Awaiting admin approval
- **approved**: Approved, ready for processing
- **completed**: Bank transfer completed
- **rejected**: Rejected by admin
- **cancelled**: Cancelled by user

---

## 12. View Withdrawal Details

Get detailed information about a specific withdrawal.

### API Endpoint
```
GET  /api/v1/admin/withdrawal-queue/{withdrawal}
```

### Request

**Request:**
```bash
curl -X GET "https://api.example.com/api/v1/admin/withdrawal-queue/1" \
  -H "Authorization: Bearer {token}"
```

**Response:**
```json
{
  "withdrawal": {
    "id": 1,
    "user": {
      "id": 10,
      "username": "john_doe",
      "email": "john@example.com",
      "mobile": "9876543210"
    },
    "wallet": {
      "id": 10,
      "balance": 50000,
      "locked_balance": 15000
    },
    "amount": 15000,
    "fee": 85,
    "tds_deducted": 1500,
    "net_amount": 13415,
    "status": "approved",
    "priority": "normal",
    "fee_breakdown": {
      "tier": 2,
      "flat_fee": 10,
      "percent_fee": 75,
      "total_fee": 85
    },
    "bank_details": {
      "account_number": "1234567890",
      "account_holder": "John Doe",
      "ifsc": "ICIC0001234",
      "bank_name": "ICICI Bank",
      "branch": "Main Branch"
    },
    "admin": {
      "id": 1,
      "username": "admin",
      "email": "admin@example.com"
    },
    "approved_at": "2025-12-09T10:00:00Z",
    "admin_notes": "Verified bank details",
    "created_at": "2025-12-09T09:00:00Z"
  },
  "transactions": [
    {
      "id": 5,
      "transaction_id": "uuid-here",
      "type": "withdrawal",
      "amount": -15000,
      "status": "pending",
      "description": "Withdrawal request",
      "created_at": "2025-12-09T09:00:00Z"
    }
  ]
}
```

---

## 13. Approve/Reject/Process Withdrawal

Manage withdrawal approval workflow.

### API Endpoints
```
POST  /api/v1/admin/withdrawal-queue/{withdrawal}/approve
POST  /api/v1/admin/withdrawal-queue/{withdrawal}/reject
POST  /api/v1/admin/withdrawal-queue/{withdrawal}/complete
```

### Approve Withdrawal

**Request:**
```bash
curl -X POST "https://api.example.com/api/v1/admin/withdrawal-queue/1/approve" \
  -H "Authorization: Bearer {token}"
```

**Response:**
```json
{
  "message": "Withdrawal approved. Ready for processing."
}
```

### Reject Withdrawal

**Request:**
```bash
curl -X POST "https://api.example.com/api/v1/admin/withdrawal-queue/1/reject" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "reason": "Insufficient KYC documentation"
  }'
```

**Response:**
```json
{
  "message": "Withdrawal rejected and funds returned to user."
}
```

### Complete Withdrawal

**Request:**
```bash
curl -X POST "https://api.example.com/api/v1/admin/withdrawal-queue/1/complete" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "utr_number": "UTR123456789"
  }'
```

**Response:**
```json
{
  "message": "Withdrawal marked as complete."
}
```

### Workflow

```
pending → approve → approved → complete → completed
   ↓
 reject → rejected (funds unlocked)
```

---

## 14. Bulk Withdrawal Processing

Process multiple withdrawals in one operation.

### API Endpoints
```
POST  /api/v1/admin/withdrawal-queue/bulk-approve
POST  /api/v1/admin/withdrawal-queue/bulk-reject
POST  /api/v1/admin/withdrawal-queue/bulk-complete
```

### Bulk Approve

**Request:**
```bash
curl -X POST "https://api.example.com/api/v1/admin/withdrawal-queue/bulk-approve" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "withdrawal_ids": [1, 2, 3, 4, 5]
  }'
```

**Response:**
```json
{
  "message": "Bulk approval completed",
  "results": {
    "success": [1, 2, 3, 5],
    "failed": [
      {
        "id": 4,
        "reason": "Not in pending status"
      }
    ]
  }
}
```

### Bulk Reject

**Request:**
```bash
curl -X POST "https://api.example.com/api/v1/admin/withdrawal-queue/bulk-reject" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "withdrawal_ids": [10, 11, 12],
    "reason": "KYC verification pending"
  }'
```

### Bulk Complete

**Request:**
```bash
curl -X POST "https://api.example.com/api/v1/admin/withdrawal-queue/bulk-complete" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "withdrawals": [
      {
        "id": 20,
        "utr_number": "UTR111111"
      },
      {
        "id": 21,
        "utr_number": "UTR222222"
      }
    ]
  }'
```

### Limits

- Maximum bulk operations: Configurable via `withdrawal_bulk_processing_limit` (default: 50)
- All operations are wrapped in database transactions

---

## 15. Withdrawal Analytics

Comprehensive analytics for withdrawal operations.

### API Endpoint
```
GET  /api/v1/admin/withdrawal-analytics
```

### Request

**Request:**
```bash
curl -X GET "https://api.example.com/api/v1/admin/withdrawal-analytics?start_date=2025-11-01&end_date=2025-12-09" \
  -H "Authorization: Bearer {token}"
```

**Response:**
```json
{
  "analytics": {
    "total_withdrawals": 150,
    "total_amount": 2500000,
    "total_fees": 12500,
    "total_tds": 125000,
    "net_disbursed": 2362500,
    "by_status": [
      {
        "status": "completed",
        "count": 100,
        "total_amount": 2000000,
        "total_net": 1850000
      },
      {
        "status": "pending",
        "count": 30,
        "total_amount": 400000,
        "total_net": 370000
      },
      {
        "status": "rejected",
        "count": 20,
        "total_amount": 100000,
        "total_net": 92500
      }
    ],
    "by_priority": [
      {
        "priority": "normal",
        "count": 130,
        "total": 1800000
      },
      {
        "priority": "high",
        "count": 20,
        "total": 700000
      }
    ],
    "daily_trend": [
      {
        "date": "2025-12-01",
        "count": 5,
        "total_requested": 85000,
        "total_disbursed": 78500
      },
      {
        "date": "2025-12-02",
        "count": 8,
        "total_requested": 125000,
        "total_disbursed": 115000
      }
    ],
    "avg_processing_time": 48.5,
    "pending_queue": {
      "count": 30,
      "total_amount": 400000
    },
    "approved_queue": {
      "count": 15,
      "total_amount": 250000
    },
    "completion_rate": {
      "total_requests": 150,
      "completed": 100,
      "rejected": 20
    }
  }
}
```

### Key Metrics

- **Total Withdrawals**: Number of withdrawal requests
- **Total Amount**: Sum of all requested amounts
- **Total Fees**: Sum of all processing fees
- **Total TDS**: Sum of all TDS deductions
- **Net Disbursed**: Actual amount paid to users
- **Avg Processing Time**: Average hours from request to completion
- **Completion Rate**: Success vs. rejection percentage

---

## 16. Payment Analytics

Comprehensive analytics for payment operations.

### API Endpoint
```
GET  /api/v1/admin/payments/analytics
```

### Request

**Request:**
```bash
curl -X GET "https://api.example.com/api/v1/admin/payments/analytics?start_date=2025-11-01&end_date=2025-12-09" \
  -H "Authorization: Bearer {token}"
```

**Response:**
```json
{
  "analytics": {
    "total_payments": 500,
    "total_amount": 25000000,
    "by_status": [
      {
        "status": "paid",
        "count": 450,
        "total": 23000000
      },
      {
        "status": "failed",
        "count": 40,
        "total": 1800000
      },
      {
        "status": "refunded",
        "count": 10,
        "total": 200000
      }
    ],
    "by_gateway": [
      {
        "gateway": "razorpay",
        "count": 400,
        "total": 20000000
      },
      {
        "gateway": "offline",
        "count": 50,
        "total": 3000000
      }
    ],
    "by_method": [
      {
        "method": "upi",
        "count": 300,
        "total": 15000000
      },
      {
        "method": "card",
        "count": 100,
        "total": 5000000
      },
      {
        "method": "netbanking",
        "count": 50,
        "total": 3000000
      }
    ],
    "daily_trend": [
      {
        "date": "2025-12-01",
        "count": 15,
        "total": 750000
      },
      {
        "date": "2025-12-02",
        "count": 18,
        "total": 900000
      }
    ],
    "success_rate": {
      "total": 500,
      "successful": 450,
      "failed": 40
    },
    "avg_payment_amount": 51111.11,
    "refunded_payments": 10,
    "refunded_amount": 200000
  }
}
```

### Insights

- **Success Rate**: (successful / total) × 100 = 90%
- **Gateway Performance**: Compare success rates across gateways
- **Method Preferences**: Most used payment methods
- **Daily Trends**: Track payment patterns over time

---

## 17. Export Functionality

Export payment and withdrawal data to CSV.

### API Endpoints
```
GET  /api/v1/admin/payments/export
GET  /api/v1/admin/withdrawal-queue/export
```

### Export Payments

**Request:**
```bash
curl -X GET "https://api.example.com/api/v1/admin/payments/export?start_date=2025-11-01&end_date=2025-12-09&status=paid" \
  -H "Authorization: Bearer {token}" \
  -o payments_export.csv
```

**CSV Format:**
```csv
ID,User,Email,Plan,Amount,Status,Gateway,Method,Paid At,Created At
1,john_doe,john@example.com,Gold Plan,5000,paid,razorpay,upi,2025-12-09 10:30:00,2025-12-09 10:25:00
2,jane_doe,jane@example.com,Silver Plan,3000,paid,offline,bank_transfer,2025-12-09 11:00:00,2025-12-09 10:50:00
```

### Export Withdrawals

**Request:**
```bash
curl -X GET "https://api.example.com/api/v1/admin/withdrawal-queue/export?status=completed" \
  -H "Authorization: Bearer {token}" \
  -o withdrawals_export.csv
```

**CSV Format:**
```csv
ID,User,Email,Amount,Fee,TDS,Net Amount,Status,Priority,UTR,Created At,Processed At
1,john_doe,john@example.com,15000,85,1500,13415,completed,normal,UTR123456,2025-12-09 09:00:00,2025-12-09 12:00:00
```

---

## Database Schema

### Payments Table (New Fields)

```sql
ALTER TABLE payments ADD COLUMN payment_method VARCHAR(50) AFTER method;
ALTER TABLE payments ADD COLUMN payment_metadata JSON AFTER gateway_signature;
ALTER TABLE payments ADD COLUMN refunded_at TIMESTAMP NULL AFTER paid_at;
ALTER TABLE payments ADD COLUMN refunded_by INT NULL AFTER refunded_at;
```

### Withdrawals Table (New Fields)

```sql
ALTER TABLE withdrawals ADD COLUMN priority VARCHAR(20) DEFAULT 'normal' AFTER status;
ALTER TABLE withdrawals ADD COLUMN fee_breakdown JSON NULL AFTER fee;
ALTER TABLE withdrawals ADD COLUMN approved_at TIMESTAMP NULL AFTER admin_id;
ALTER TABLE withdrawals ADD COLUMN processed_at TIMESTAMP NULL AFTER approved_at;
ALTER TABLE withdrawals ADD COLUMN admin_notes TEXT NULL AFTER rejection_reason;
```

---

## Settings Reference

### Payment Gateway Settings

| Key | Type | Default | Description |
|-----|------|---------|-------------|
| payment_gateway_razorpay_enabled | boolean | true | Enable Razorpay |
| payment_gateway_razorpay_key | string | '' | Razorpay API Key |
| payment_gateway_razorpay_secret | string | '' | Razorpay Secret |
| payment_gateway_stripe_enabled | boolean | false | Enable Stripe |
| payment_gateway_paytm_enabled | boolean | false | Enable Paytm |

### Payment Method Settings

| Key | Type | Default | Description |
|-----|------|---------|-------------|
| payment_method_upi_enabled | boolean | true | Enable UPI |
| payment_method_upi_fee | number | 0 | UPI flat fee |
| payment_method_upi_fee_percent | number | 0 | UPI percentage fee |
| payment_method_card_enabled | boolean | true | Enable Cards |
| payment_method_card_fee | number | 0 | Card flat fee |
| payment_method_card_fee_percent | number | 2 | Card percentage fee |

### Auto-Debit Settings

| Key | Type | Default | Description |
|-----|------|---------|-------------|
| auto_debit_enabled | boolean | true | Enable auto-debit |
| auto_debit_max_retries | number | 3 | Max retry attempts |
| auto_debit_retry_interval_days | number | 1 | Days between retries |
| auto_debit_reminder_days | number | 3 | Reminder before due date |
| auto_debit_suspend_after_max_retries | boolean | true | Suspend on failure |

### Withdrawal Fee Tiers

| Key | Type | Default | Description |
|-----|------|---------|-------------|
| withdrawal_fee_tier_1_max | number | 5000 | Tier 1 max amount |
| withdrawal_fee_tier_1_flat | number | 0 | Tier 1 flat fee |
| withdrawal_fee_tier_1_percent | number | 0 | Tier 1 percentage |
| withdrawal_fee_tier_2_max | number | 25000 | Tier 2 max amount |
| withdrawal_fee_tier_2_flat | number | 10 | Tier 2 flat fee |
| withdrawal_fee_tier_2_percent | number | 0.5 | Tier 2 percentage |

### Withdrawal Configuration

| Key | Type | Default | Description |
|-----|------|---------|-------------|
| withdrawal_enabled | boolean | true | Enable withdrawals |
| min_withdrawal_amount | number | 1000 | Minimum amount |
| withdrawal_processing_days | number | 3 | Standard processing |
| withdrawal_priority_threshold | number | 50000 | Priority threshold |
| withdrawal_bulk_processing_limit | number | 50 | Bulk operation limit |

---

## Permissions

All admin routes require appropriate permissions:

**Payment Permissions:**
- `payments.view` - View payments
- `payments.manage_config` - Manage gateway/method settings
- `payments.offline_entry` - Record offline payments
- `payments.refund` - Process refunds
- `payments.approve` - Approve/reject manual payments
- `payments.retry` - Retry failed payments

**Withdrawal Permissions:**
- `withdrawals.view_queue` - View withdrawal queue
- `withdrawals.manage_config` - Manage settings/fees
- `withdrawals.approve` - Approve withdrawals
- `withdrawals.reject` - Reject withdrawals
- `withdrawals.complete` - Complete withdrawals

---

## Testing

### Test Gateway Configuration

```bash
# Update Razorpay settings
curl -X PUT "http://localhost:8000/api/v1/admin/payment-gateways" \
  -H "Authorization: Bearer {token}" \
  -d '{"gateway":"razorpay","enabled":true,"key":"test_key"}'
```

### Test Withdrawal Fee Calculation

```php
// Test in Tinker
php artisan tinker

$withdrawal = Withdrawal::find(1);
// Check fee_breakdown field for tier calculation
$withdrawal->fee_breakdown;
```

### Test Bulk Operations

```bash
# Bulk approve 5 withdrawals
curl -X POST "http://localhost:8000/api/v1/admin/withdrawal-queue/bulk-approve" \
  -H "Authorization: Bearer {token}" \
  -d '{"withdrawal_ids":[1,2,3,4,5]}'
```

---

## Troubleshooting

### Issue: Gateway credentials not saving

**Solution:** Check Settings model and cache:
```php
Cache::forget('settings');
Setting::where('key', 'payment_gateway_razorpay_key')->first();
```

### Issue: Bulk operations timing out

**Solution:** Reduce `withdrawal_bulk_processing_limit`:
```sql
UPDATE settings SET value = '25' WHERE key = 'withdrawal_bulk_processing_limit';
```

### Issue: TDS not calculating correctly

**Solution:** Verify user has PAN and amount > threshold:
```php
$user->kyc->pan_number; // Must exist
$amount > setting('tds_threshold', 5000); // Must be true
```

### Issue: Export generating empty file

**Solution:** Check date filters and permissions:
```bash
# Remove date filters for testing
curl -X GET "http://localhost:8000/api/v1/admin/payments/export"
```

---

## Code References

- **PaymentController**: `backend/app/Http/Controllers/Api/Admin/PaymentController.php`
- **WithdrawalController**: `backend/app/Http/Controllers/Api/Admin/WithdrawalController.php`
- **Payment Model**: `backend/app/Models/Payment.php`
- **Withdrawal Model**: `backend/app/Models/Withdrawal.php`
- **AutoDebitService**: `backend/app/Services/AutoDebitService.php`
- **WithdrawalService**: `backend/app/Services/WithdrawalService.php`
- **Migration**: `backend/database/migrations/2025_12_09_120000_enhance_payment_withdrawal_configuration.php`
- **Routes**: `backend/routes/api.php` (lines 492-536)

---

## Summary

All 17 Payment & Withdrawal features have been successfully implemented:

✅ 1. Payment gateway setup (Razorpay, Stripe, Paytm)
✅ 2. Payment methods configuration (UPI, Card, NetBanking, Wallet)
✅ 3. Auto-debit configuration (retries, reminders, suspension)
✅ 4. View all payments with filters
✅ 5. View payment details
✅ 6. Manual payment entry (offline payments)
✅ 7. Refund payment (with bonus/allocation reversal)
✅ 8. Handle failed payments (retry management)
✅ 9. Withdrawal settings configuration
✅ 10. Withdrawal fee tiers (4-tier structure)
✅ 11. View withdrawal queue
✅ 12. View withdrawal details
✅ 13. Approve/Reject/Process withdrawal
✅ 14. Bulk withdrawal processing
✅ 15. Withdrawal analytics
✅ 16. Payment analytics
✅ 17. Export functionality (CSV for payments & withdrawals)

The system provides comprehensive control over payment processing, gateway management, withdrawal operations, and financial analytics with robust validation, security, and audit trails.
