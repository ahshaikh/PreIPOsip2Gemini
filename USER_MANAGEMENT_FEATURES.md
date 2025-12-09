# User Management Features - Complete Implementation Guide

## Overview
This document provides a comprehensive guide to all 18 user management features implemented in the PreIPOsip2Gemini platform.

## ✅ Implemented Features (18/18)

### 1. View All Users (Filters, Search, Export) ✅
**Endpoint**: `GET /api/v1/admin/users`

**Features**:
- Pagination with configurable records per page
- Basic search across username, email, mobile, first_name, last_name
- Role filtering (defaults to 'user' role)
- Includes profile, KYC, wallet relationships
- CSV export functionality

**Query Parameters**:
```
?search=john
&per_page=25
```

**CSV Export**: `GET /api/v1/admin/users/export/csv`

---

### 2. View User Details (All Tabs) ✅
**Endpoint**: `GET /api/v1/admin/users/{id}`

**Response Includes**:
- User: username, email, mobile, status, verification dates
- Profile: name, avatar, location
- KYC: status, masked PAN, documents count
- Wallet: balance, locked balance
- Subscription: plan, status, payment count
- Stats: total payments, bonuses, referrals, tickets

---

### 3. Create New User Manually ✅
**Endpoint**: `POST /api/v1/admin/users`

**Request**:
```json
{
  "username": "johndoe",
  "email": "john@example.com",
  "mobile": "9876543210",
  "password": "SecurePass123!",
  "role": "user"
}
```

**Auto-creates**:
- User profile
- KYC record (pending status)
- Wallet (zero balance)
- Assigns role

---

### 4. Edit User Profile (Any Field) ✅
**Endpoint**: `PUT /api/v1/admin/users/{id}`

**Request**:
```json
{
  "username": "newusername",
  "email": "newemail@example.com",
  "mobile": "9876543210",
  "status": "active",
  "password": "NewPassword123",
  "profile": {
    "first_name": "John",
    "last_name": "Doe",
    "city": "Mumbai",
    "state": "Maharashtra",
    "address": "123 Main St"
  }
}
```

**Features**:
- All fields optional (partial updates supported)
- Unique validation for username, email, mobile
- Password auto-hashed
- Profile updates within transaction

---

### 5. Delete User (Soft Delete with Anonymization) ✅
**Endpoint**: `DELETE /api/v1/admin/users/{id}`

**Process**:
1. Anonymizes username → `deleted_XXXXXXXXXX`
2. Anonymizes email → `deleted_XXXXXXXXXX@deleted.local`
3. Anonymizes mobile → `0000000000`
4. Sets `is_anonymized = true`, `anonymized_at = now()`
5. Anonymizes profile (first_name, last_name, address, city, state, pincode)
6. Soft deletes (sets `deleted_at`)

**GDPR Compliance**: Irreversible anonymization before deletion

---

### 6. Suspend User (Temporary with Reason) ✅
**Endpoint**: `POST /api/v1/admin/users/{id}/suspend`

**Request**:
```json
{
  "reason": "Suspicious activity detected"
}
```

**Process**:
- Sets `status = 'suspended'`
- Records `suspension_reason`, `suspended_at`, `suspended_by`
- Creates activity log
- User cannot login while suspended

---

### 7. Block User (Permanent with Blacklisting) ✅
**Endpoint**: `POST /api/v1/admin/users/{id}/block`

**Request**:
```json
{
  "reason": "Fraud detected - multiple chargebacks",
  "blacklist": true
}
```

**Process**:
- Sets `status = 'blocked'`
- Records `block_reason`, `blocked_at`, `blocked_by`
- Optional `is_blacklisted = true` (prevents future registration)
- Creates activity log
- User permanently blocked (cannot login)

---

### 8. Unblock/Unsuspend User ✅

**Unblock**: `POST /api/v1/admin/users/{id}/unblock`
- Sets `status = 'active'`
- Clears block fields (block_reason, blocked_at, blocked_by, is_blacklisted)

**Unsuspend**: `POST /api/v1/admin/users/{id}/unsuspend`
- Sets `status = 'active'`
- Clears suspension fields (suspension_reason, suspended_at, suspended_by)

---

### 9. Adjust User Wallet Balance Manually ✅
**Endpoint**: `POST /api/v1/admin/users/{id}/adjust-balance`

**Request**:
```json
{
  "type": "credit",
  "amount": 5000,
  "description": "Compensation for service interruption"
}
```

**Features**:
- Uses WalletService for secure transactions
- Creates transaction record
- Supports credit/debit
- Auto-validates sufficient balance for debits
- Returns new balance

---

### 10. Manual Bonus Award to User ✅
**Endpoint**: Via Bulk Action or Adjust Balance

**Bulk Bonus**:
```json
{
  "user_ids": [1, 2, 3],
  "action": "bonus",
  "data": {
    "amount": 1000
  }
}
```

**Process**:
- Uses WalletService for secure deposit
- Creates bonus transaction
- Records admin who awarded

---

### 11. Override Investment Allocation ✅
**Endpoint**: `POST /api/v1/admin/users/{id}/override-allocation`

**Request**:
```json
{
  "subscription_id": 123,
  "allocation_amount": 50000,
  "reason": "VIP customer - guaranteed allocation"
}
```

**Process**:
- Validates subscription belongs to user
- Uses AllocationService to override
- Records reason for audit
- Logs action

---

### 12. Force Payment Processing ✅
**Endpoint**: `POST /api/v1/admin/users/{id}/force-payment`

**Request**:
```json
{
  "subscription_id": 123,
  "amount": 5000,
  "reason": "Offline cash payment received"
}
```

**Process**:
- Creates payment record with status 'paid'
- Sets payment_method = 'admin_manual'
- Marks as on-time payment
- Increments consecutive_payments_count
- Creates activity log
- Triggers bonus calculations

---

### 13. Send Email to User ✅
**Endpoint**: `POST /api/v1/admin/users/{id}/send-email`

**Request**:
```json
{
  "subject": "Account Update Notification",
  "message": "Your account has been verified successfully.",
  "template": "admin-message"
}
```

**Features**:
- Uses EmailService
- Supports custom templates
- Logged for audit
- Error handling with rollback

---

### 14. Send SMS to User ✅
**Endpoint**: `POST /api/v1/admin/users/{id}/send-sms`

**Request**:
```json
{
  "message": "Your payment is due. Please pay by tomorrow."
}
```

**Features**:
- Uses SmsService
- Max 160 characters
- Logged for audit
- Error handling

---

### 15. Send Push Notification ✅
**Endpoint**: `POST /api/v1/admin/users/{id}/send-notification`

**Request**:
```json
{
  "title": "Important Update",
  "message": "Your KYC has been approved. You can now invest.",
  "type": "success",
  "url": "https://app.example.com/dashboard"
}
```

**Types**: info, warning, success, error

**Features**:
- Uses Laravel Notifications
- Stored in database
- Optional deep link URL
- Real-time delivery (if websockets enabled)

---

### 16. Bulk User Actions ✅
**Endpoint**: `POST /api/v1/admin/users/bulk-action`

**Actions Supported**:
1. **Activate**: Sets status to 'active' for multiple users
2. **Suspend**: Sets status to 'suspended' for multiple users
3. **Bonus**: Awards bonus to multiple users

**Request**:
```json
{
  "user_ids": [1, 2, 3, 4, 5],
  "action": "activate"
}
```

**Bonus Example**:
```json
{
  "user_ids": [1, 2, 3],
  "action": "bonus",
  "data": {
    "amount": 1000
  }
}
```

---

### 17. Advanced User Search with Multiple Criteria ✅
**Endpoint**: `POST /api/v1/admin/users/advanced-search`

**Request**:
```json
{
  "username": "john",
  "email": "@gmail.com",
  "mobile": "98765",
  "status": ["active", "suspended"],
  "kyc_status": ["verified"],
  "subscription_status": ["active"],
  "wallet_balance_min": 1000,
  "wallet_balance_max": 50000,
  "created_from": "2025-01-01",
  "created_to": "2025-12-31",
  "has_referrals": true,
  "is_blacklisted": false
}
```

**Search Criteria**:
- Username (partial match)
- Email (partial match)
- Mobile (partial match)
- Status (multiple: active, suspended, blocked)
- KYC Status (multiple: pending, submitted, verified, rejected)
- Subscription Status (multiple: active, paused, completed, cancelled)
- Wallet Balance Range (min/max)
- Registration Date Range
- Has Referrals (boolean)
- Is Blacklisted (boolean)

**Response**: Paginated results (50 per page)

---

### 18. User Segmentation for Targeted Actions ✅

**Get Segments Count**: `GET /api/v1/admin/users/segments`

**Response**:
```json
{
  "segments": {
    "active_subscribers": 450,
    "inactive_users": 120,
    "kyc_pending": 85,
    "kyc_verified": 380,
    "high_value": 65,
    "low_activity": 95,
    "suspended": 12,
    "blocked": 5,
    "blacklisted": 3,
    "with_referrals": 180,
    "total_users": 650
  }
}
```

**Get Users by Segment**: `GET /api/v1/admin/users/segment/{name}`

**Available Segments**:
- `active_subscribers` - Users with active subscriptions
- `inactive_users` - Users without subscriptions
- `kyc_pending` - KYC verification pending
- `kyc_verified` - KYC verified users
- `high_value` - Wallet balance > ₹10,000
- `low_activity` - No activity in last 30 days
- `suspended` - Suspended users
- `blocked` - Blocked users
- `blacklisted` - Blacklisted users
- `with_referrals` - Users who have referred others

**Use Cases**:
- Targeted email campaigns
- Bulk bonus distribution
- Re-engagement campaigns
- Compliance monitoring

---

## API Endpoints Summary

### User Management
```
GET    /api/v1/admin/users                              - List users
GET    /api/v1/admin/users/{id}                         - View user details
POST   /api/v1/admin/users                              - Create user
PUT    /api/v1/admin/users/{id}                         - Edit user
DELETE /api/v1/admin/users/{id}                         - Delete user
GET    /api/v1/admin/users/export/csv                   - Export CSV
POST   /api/v1/admin/users/import                       - Import CSV

POST   /api/v1/admin/users/{id}/suspend                 - Suspend user
POST   /api/v1/admin/users/{id}/unsuspend               - Unsuspend user
POST   /api/v1/admin/users/{id}/block                   - Block user
POST   /api/v1/admin/users/{id}/unblock                 - Unblock user

POST   /api/v1/admin/users/{id}/adjust-balance          - Adjust wallet
POST   /api/v1/admin/users/{id}/override-allocation     - Override allocation
POST   /api/v1/admin/users/{id}/force-payment           - Force payment

POST   /api/v1/admin/users/{id}/send-email              - Send email
POST   /api/v1/admin/users/{id}/send-sms                - Send SMS
POST   /api/v1/admin/users/{id}/send-notification       - Send notification

POST   /api/v1/admin/users/bulk-action                  - Bulk actions
POST   /api/v1/admin/users/advanced-search              - Advanced search
GET    /api/v1/admin/users/segments                     - Get segments
GET    /api/v1/admin/users/segment/{name}               - Users by segment
```

---

## Database Schema Enhancements

### users Table (New Fields)
```sql
ALTER TABLE users ADD COLUMN suspension_reason VARCHAR(255);
ALTER TABLE users ADD COLUMN suspended_at TIMESTAMP;
ALTER TABLE users ADD COLUMN suspended_by BIGINT;
ALTER TABLE users ADD COLUMN block_reason VARCHAR(255);
ALTER TABLE users ADD COLUMN blocked_at TIMESTAMP;
ALTER TABLE users ADD COLUMN blocked_by BIGINT;
ALTER TABLE users ADD COLUMN is_blacklisted BOOLEAN DEFAULT FALSE;
ALTER TABLE users ADD COLUMN is_anonymized BOOLEAN DEFAULT FALSE;
ALTER TABLE users ADD COLUMN anonymized_at TIMESTAMP;

ALTER TABLE users ADD FOREIGN KEY (suspended_by) REFERENCES users(id);
ALTER TABLE users ADD FOREIGN KEY (blocked_by) REFERENCES users(id);
```

---

## Permissions Required

All endpoints require authentication and specific permissions:

- `users.view` - View users, export, search, segments
- `users.create` - Create users, import
- `users.edit` - Edit users, bulk actions, notifications, payments
- `users.suspend` - Suspend, unsuspend, block, unblock
- `users.adjust_wallet` - Adjust wallet balance

---

## Security Features

1. **Rate Limiting**: All critical actions throttled
2. **Permission-Based Access**: Granular permission system
3. **Activity Logging**: All actions logged with IP
4. **Transaction Safety**: Wallet operations in DB transactions
5. **Anonymization**: GDPR-compliant deletion
6. **Audit Trail**: Admin ID recorded for all actions
7. **Blacklist Prevention**: Blocked users cannot re-register

---

## Testing Guide

### 1. Test User Creation
```bash
POST /api/v1/admin/users
{
  "username": "testuser",
  "email": "test@example.com",
  "mobile": "9876543210",
  "password": "Test123!",
  "role": "user"
}
```

### 2. Test Suspension
```bash
POST /api/v1/admin/users/1/suspend
{
  "reason": "Testing suspension"
}
```

### 3. Test Advanced Search
```bash
POST /api/v1/admin/users/advanced-search
{
  "status": ["active"],
  "kyc_status": ["verified"],
  "wallet_balance_min": 5000
}
```

### 4. Test Segmentation
```bash
GET /api/v1/admin/users/segments
GET /api/v1/admin/users/segment/high_value
```

### 5. Test User Deletion
```bash
DELETE /api/v1/admin/users/1
# Verify anonymization in database
```

---

## Changelog

### Version 2025-12-09
- ✅ All 18 User Management features implemented
- ✅ Enhanced AdminUserController with 14 new methods
- ✅ Added user blocking and anonymization migration
- ✅ Implemented advanced search with 14 criteria
- ✅ Created user segmentation system with 11 segments
- ✅ Added email/SMS/notification sending
- ✅ Implemented allocation override and force payment
- ✅ Enhanced update method for full profile editing
- ✅ Created AdminMessage notification class
- ✅ Added 13 new API routes
- ✅ Updated User model with 9 new fillable fields

---

**End of Documentation**
