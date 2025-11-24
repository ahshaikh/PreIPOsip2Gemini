# API Documentation

## Base URL
All API endpoints are prefixed with: `/api/v1/`

## Authentication
The API uses Laravel Sanctum for token-based authentication. Include the token in the Authorization header:
```
Authorization: Bearer {token}
```

---

## Authentication Endpoints

### Register User
```
POST /api/v1/register
```

**Request Body:**
```json
{
  "username": "string (3-50 chars, alphanumeric)",
  "email": "string (valid email)",
  "mobile": "string (10 digits)",
  "password": "string (min 8 chars)",
  "password_confirmation": "string",
  "referral_code": "string (optional)"
}
```

**Response (201):**
```json
{
  "message": "Registration successful. Please verify your email.",
  "user": {
    "id": 1,
    "username": "johndoe",
    "email": "john@example.com"
  }
}
```

### Login
```
POST /api/v1/login
```

**Request Body:**
```json
{
  "email": "string",
  "password": "string"
}
```

**Response (200):**
```json
{
  "token": "bearer_token_string",
  "user": {
    "id": 1,
    "username": "johndoe",
    "email": "john@example.com"
  }
}
```

**Note:** If 2FA is enabled, returns `{ "requires_2fa": true, "temp_token": "..." }`

### Verify 2FA
```
POST /api/v1/login/2fa
```

**Request Body:**
```json
{
  "temp_token": "string",
  "code": "string (6 digits)"
}
```

### Logout
```
POST /api/v1/logout
```
**Headers:** `Authorization: Bearer {token}`

---

## User Profile Endpoints

### Get Profile
```
GET /api/v1/user/profile
```
**Headers:** `Authorization: Bearer {token}`

**Response (200):**
```json
{
  "id": 1,
  "username": "johndoe",
  "email": "john@example.com",
  "mobile": "9876543210",
  "profile": {
    "first_name": "John",
    "last_name": "Doe",
    "avatar_url": "https://...",
    "city": "Mumbai",
    "state": "Maharashtra"
  },
  "kyc": {
    "status": "verified|pending|submitted|rejected"
  }
}
```

### Update Profile
```
PUT /api/v1/user/profile
```

**Request Body:**
```json
{
  "first_name": "string (optional)",
  "last_name": "string (optional)",
  "city": "string (optional)",
  "state": "string (optional)",
  "date_of_birth": "YYYY-MM-DD (optional)"
}
```

---

## KYC Endpoints

### Get KYC Status
```
GET /api/v1/user/kyc
```

**Response (200):**
```json
{
  "status": "pending|submitted|verified|rejected",
  "pan_number": "****1234F",
  "submitted_at": "2024-01-15T10:30:00Z",
  "verified_at": null,
  "rejection_reason": null,
  "documents": [
    {
      "id": 1,
      "type": "pan_card",
      "status": "verified"
    }
  ]
}
```

### Submit KYC
```
POST /api/v1/user/kyc
```

**Request Body (multipart/form-data):**
```
pan_number: string (format: ABCDE1234F)
pan_document: file (image, max 5MB)
address_document: file (image, max 5MB)
selfie: file (image, max 5MB)
```

---

## Subscription Endpoints

### Get Subscription
```
GET /api/v1/user/subscription
```

**Response (200):**
```json
{
  "id": 1,
  "plan_id": 1,
  "plan_name": "Premium SIP",
  "monthly_amount": 5000,
  "status": "active|pending|paused|cancelled",
  "consecutive_payments_count": 6,
  "bonus_multiplier": 1.5,
  "starts_at": "2024-01-01T00:00:00Z",
  "next_payment_date": "2024-07-01T00:00:00Z"
}
```

### Create Subscription
```
POST /api/v1/user/subscription
```

**Request Body:**
```json
{
  "plan_id": 1,
  "monthly_amount": 5000,
  "auto_debit": true
}
```

### Change Plan
```
POST /api/v1/user/subscription/change-plan
```

**Request Body:**
```json
{
  "plan_id": 2,
  "monthly_amount": 10000
}
```

### Pause Subscription
```
POST /api/v1/user/subscription/pause
```

**Request Body:**
```json
{
  "reason": "string (optional)"
}
```

### Resume Subscription
```
POST /api/v1/user/subscription/resume
```

### Cancel Subscription
```
POST /api/v1/user/subscription/cancel
```

**Request Body:**
```json
{
  "reason": "string (required)"
}
```

---

## Payment Endpoints

### Initiate Payment
```
POST /api/v1/user/payment/initiate
```

**Request Body:**
```json
{
  "subscription_id": 1
}
```

**Response (200):**
```json
{
  "order_id": "order_ABC123",
  "amount": 500000,
  "currency": "INR",
  "razorpay_key": "rzp_live_xxx"
}
```

### Verify Payment
```
POST /api/v1/user/payment/verify
```

**Request Body:**
```json
{
  "razorpay_order_id": "order_ABC123",
  "razorpay_payment_id": "pay_XYZ789",
  "razorpay_signature": "signature_string"
}
```

---

## Wallet Endpoints

### Get Wallet
```
GET /api/v1/user/wallet
```

**Response (200):**
```json
{
  "balance": 15000.50,
  "locked_balance": 2000.00,
  "available_balance": 15000.50,
  "total_deposited": 50000.00,
  "total_withdrawn": 35000.00
}
```

### Request Withdrawal
```
POST /api/v1/user/wallet/withdraw
```

**Request Body:**
```json
{
  "amount": 5000,
  "bank_account_id": 1
}
```

**Response (201):**
```json
{
  "message": "Withdrawal request submitted",
  "withdrawal": {
    "id": 1,
    "amount": 5000,
    "net_amount": 4950,
    "status": "pending"
  }
}
```

---

## Bonus & Rewards Endpoints

### Get Bonuses
```
GET /api/v1/user/bonuses
```

**Response (200):**
```json
{
  "data": [
    {
      "id": 1,
      "type": "consistency|progressive|milestone|referral|celebration|jackpot|profit_share",
      "amount": 500,
      "multiplier_applied": 1.5,
      "created_at": "2024-01-15T10:30:00Z"
    }
  ],
  "summary": {
    "total_earned": 15000,
    "by_type": {
      "consistency": 3000,
      "progressive": 5000,
      "milestone": 2000,
      "referral": 5000
    }
  }
}
```

### Get Referrals
```
GET /api/v1/user/referrals
```

**Response (200):**
```json
{
  "referral_code": "ABC123XYZ",
  "referral_link": "https://app.com/ref/ABC123XYZ",
  "total_referrals": 5,
  "active_referrals": 3,
  "pending_referrals": 2,
  "total_earnings": 2500,
  "referrals": [
    {
      "id": 1,
      "username": "referreduser",
      "status": "active",
      "bonus_earned": 500,
      "joined_at": "2024-01-10T00:00:00Z"
    }
  ]
}
```

---

## Support Ticket Endpoints

### List Tickets
```
GET /api/v1/user/support-tickets
```

**Query Parameters:**
- `status`: filter by status (open, in_progress, resolved, closed)
- `page`: pagination

### Create Ticket
```
POST /api/v1/user/support-tickets
```

**Request Body:**
```json
{
  "subject": "string",
  "message": "string",
  "category": "general|payment|kyc|withdrawal|technical",
  "priority": "low|medium|high"
}
```

### Reply to Ticket
```
POST /api/v1/user/support-tickets/{id}/reply
```

**Request Body:**
```json
{
  "message": "string"
}
```

---

## Admin Endpoints

### Dashboard
```
GET /api/v1/admin/dashboard
```

**Response (200):**
```json
{
  "users": {
    "total": 10000,
    "active": 8500,
    "new_today": 25
  },
  "subscriptions": {
    "total": 5000,
    "active": 4200,
    "mrr": 2100000
  },
  "payments": {
    "today": 150000,
    "this_month": 3500000,
    "pending_count": 15
  },
  "kyc_queue": {
    "pending": 45
  },
  "withdrawal_queue": {
    "pending": 12
  }
}
```

### User Management

#### List Users
```
GET /api/v1/admin/users
```

**Query Parameters:**
- `search`: search by username, email, or mobile
- `status`: filter by status
- `page`: pagination

#### Get User Details
```
GET /api/v1/admin/users/{id}
```

**Response (200):**
```json
{
  "id": 1,
  "username": "johndoe",
  "email": "john@example.com",
  "status": "active",
  "profile": {...},
  "kyc": {
    "status": "verified",
    "pan_number": "****1234F"
  },
  "wallet": {
    "balance": 5000,
    "locked_balance": 0
  },
  "stats": {
    "total_payments": 6,
    "total_bonuses": 1500,
    "referral_count": 3,
    "open_tickets": 0
  }
}
```

#### Adjust User Balance
```
POST /api/v1/admin/users/{id}/adjust-balance
```

**Request Body:**
```json
{
  "type": "credit|debit",
  "amount": 1000,
  "description": "Promotional credit"
}
```

#### Suspend User
```
POST /api/v1/admin/users/{id}/suspend
```

**Request Body:**
```json
{
  "reason": "Policy violation"
}
```

#### Bulk Actions
```
POST /api/v1/admin/users/bulk-action
```

**Request Body:**
```json
{
  "user_ids": [1, 2, 3],
  "action": "activate|suspend|bonus",
  "data": {
    "amount": 500
  }
}
```

### KYC Queue

#### List KYC Queue
```
GET /api/v1/admin/kyc-queue
```

#### Approve KYC
```
POST /api/v1/admin/kyc-queue/{id}/approve
```

#### Reject KYC
```
POST /api/v1/admin/kyc-queue/{id}/reject
```

**Request Body:**
```json
{
  "reason": "Document not clear",
  "template_id": 1
}
```

### Withdrawal Queue

#### List Withdrawal Queue
```
GET /api/v1/admin/withdrawal-queue
```

#### Approve Withdrawal
```
POST /api/v1/admin/withdrawal-queue/{id}/approve
```

#### Complete Withdrawal
```
POST /api/v1/admin/withdrawal-queue/{id}/complete
```

**Request Body:**
```json
{
  "utr_number": "UTR123456789"
}
```

#### Reject Withdrawal
```
POST /api/v1/admin/withdrawal-queue/{id}/reject
```

**Request Body:**
```json
{
  "reason": "Invalid bank details"
}
```

### Referral Campaigns

#### List Campaigns
```
GET /api/v1/admin/referral-campaigns
```

#### Create Campaign
```
POST /api/v1/admin/referral-campaigns
```

**Request Body:**
```json
{
  "name": "Diwali Bonus Campaign",
  "start_date": "2024-10-15",
  "end_date": "2024-11-15",
  "multiplier": 2.0,
  "bonus_amount": 500,
  "is_active": true
}
```

**Validation:**
- `multiplier`: max 5.0
- `bonus_amount`: max 10000

### Theme & SEO Settings

#### Update Theme
```
POST /api/v1/admin/settings/theme
```

**Request Body (multipart/form-data):**
```
primary_color: #FF5733 (hex color)
secondary_color: #00FF00 (hex color)
font_family: Inter, sans-serif
logo: file (png, jpg, jpeg, svg, max 2MB)
favicon: file (png, ico, max 512KB)
```

#### Update SEO
```
POST /api/v1/admin/settings/seo
```

**Request Body:**
```json
{
  "robots_txt": "User-agent: *\nAllow: /",
  "meta_title_suffix": " | MyCompany",
  "google_analytics_id": "G-ABCDE12345"
}
```

**Validation:**
- `google_analytics_id`: format G-XXXXXXXXXX or UA-XXXXXXXX-X

---

## Webhooks

### Razorpay Webhook
```
POST /api/v1/webhooks/razorpay
```

**Headers:**
- `X-Razorpay-Signature`: HMAC signature for verification

**Handled Events:**
- `payment.captured`
- `payment.failed`
- `order.paid`
- `subscription.charged`
- `subscription.halted`

---

## Error Responses

### Validation Error (422)
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "email": ["The email field is required."],
    "password": ["The password must be at least 8 characters."]
  }
}
```

### Unauthorized (401)
```json
{
  "message": "Unauthenticated."
}
```

### Forbidden (403)
```json
{
  "message": "You do not have permission to perform this action."
}
```

### Not Found (404)
```json
{
  "message": "Resource not found."
}
```

### Server Error (500)
```json
{
  "message": "An error occurred. Please try again later."
}
```

---

## Rate Limiting

| Endpoint | Limit |
|----------|-------|
| Login | 5 requests per minute |
| Register | 3 requests per minute |
| Withdrawal Request | 5 requests per minute |
| General API | 60 requests per minute |

---

## Pagination

Paginated endpoints return:
```json
{
  "data": [...],
  "current_page": 1,
  "last_page": 10,
  "per_page": 25,
  "total": 250,
  "links": {
    "first": "...",
    "last": "...",
    "prev": null,
    "next": "..."
  }
}
```

Use `?page=2` query parameter for pagination.
