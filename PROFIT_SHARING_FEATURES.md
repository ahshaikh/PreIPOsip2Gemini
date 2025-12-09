# Profit Sharing Configuration Features - Complete Implementation Guide

## Overview
This document provides a comprehensive guide to all 10 profit sharing configuration features implemented in the PreIPOsip2Gemini platform.

## ✅ Implemented Features (10/10)

### 1. Profit Sharing Global Settings (Frequency, Auto-Calculate) ✅
**Location**: Settings + Database

**Description**: Configure global profit sharing behavior including frequency and automation.

**Settings Available**:
- `profit_share_frequency` - How often profit share periods occur (quarterly, monthly, annually)
- `profit_share_auto_calculate` - Automatically calculate distributions (true/false)
- `profit_share_auto_distribute` - Automatically distribute funds after calculation (true/false)

**Features**:
- Flexible frequency configuration
- Optional automation for hands-off operation
- Override settings per period if needed

**Example**:
```json
{
  "profit_share_frequency": "quarterly",
  "profit_share_auto_calculate": false,
  "profit_share_auto_distribute": false
}
```

---

### 2. Profit Share Percentage Per Plan ✅
**Location**: `plan_configs` table

**Description**: Each plan can have different profit share percentages.

**Configuration** (per plan):
```json
{
  "profit_share": {
    "percentage": 5
  }
}
```

**Examples**:
- **Gold Plan**: 10% of pool allocation
- **Silver Plan**: 5% of pool allocation
- **Bronze Plan**: 2% of pool allocation

**Features**:
- Configurable per plan via plan configs
- Used as multiplier in distribution calculation
- Allows tier-based profit sharing

---

### 3. Profit Calculation Formula Configuration ✅
**Location**: `ProfitShareService` + Settings

**Description**: Three different formula types for calculating profit distribution.

**Formula Types**:

#### A. Weighted Investment (Default)
```
User Share = (User Investment / Total Investment) × Pool × Plan %
```
- Based purely on investment amount
- Fair for proportional distribution
- Rewards higher investors

#### B. Equal Split
```
User Share = (Pool / Total Users) × Plan %
```
- Every eligible user gets equal share
- Democratic distribution
- Good for community building

#### C. Tenure-Based
```
Combined Ratio = (Investment Ratio × Investment Weight) + (Tenure Ratio × Tenure Weight)
User Share = Pool × Combined Ratio × Plan %
```
- Combines investment amount and tenure
- Rewards long-term investors
- Configurable weights (default: 70% investment, 30% tenure)

**Settings**:
- `profit_share_formula_type` - Formula to use (weighted_investment, equal_split, tenure_based)
- `profit_share_investment_weight` - Weight for investment in tenure formula (0-1)
- `profit_share_tenure_weight` - Weight for tenure in tenure formula (0-1)

**Example**:
```json
{
  "profit_share_formula_type": "tenure_based",
  "profit_share_investment_weight": 0.7,
  "profit_share_tenure_weight": 0.3
}
```

---

### 4. Eligibility Criteria (Min Months, Min Investment) ✅
**Location**: Settings + Service Logic

**Description**: Define who is eligible to receive profit sharing.

**Criteria**:
- **Min Months**: Minimum account age in months
- **Min Investment**: Minimum subscription amount
- **Active Subscription**: Require active subscription status

**Settings**:
- `profit_share_min_months` - Minimum months as member (default: 3)
- `profit_share_min_investment` - Minimum investment amount (default: 10000)
- `profit_share_require_active_subscription` - Must have active subscription (default: true)

**Example Flow**:
1. User created account 6 months ago ✅
2. User has subscription of ₹25,000 ✅
3. Subscription status is 'active' ✅
4. **User is eligible for profit sharing**

**Example (Not Eligible)**:
1. User created account 1 month ago ❌ (< 3 months)
2. User has subscription of ₹5,000 ❌ (< ₹10,000)
3. **User is NOT eligible**

---

### 5. Create Profit Share Period ✅
**API Endpoint**: `POST /api/v1/admin/profit-sharing`

**Request**:
```json
{
  "period_name": "Q4 2025 Profit Share",
  "start_date": "2025-10-01",
  "end_date": "2025-12-31",
  "net_profit": 5000000,
  "total_pool": 500000
}
```

**Response**:
```json
{
  "id": 1,
  "period_name": "Q4 2025 Profit Share",
  "start_date": "2025-10-01",
  "end_date": "2025-12-31",
  "net_profit": 5000000,
  "total_pool": 500000,
  "status": "pending",
  "admin_id": 1,
  "created_at": "2025-12-09T..."
}
```

**Features**:
- Manual period creation by admin
- Set custom date ranges
- Define profit pool size
- Automatically marked as 'pending'

---

### 6. Calculate Distribution Preview ✅
**API Endpoint**: `POST /api/v1/admin/profit-sharing/{id}/preview`

**Description**: Preview distribution calculations without saving to database.

**Response**:
```json
{
  "message": "Preview generated successfully",
  "distributions": [
    {
      "user_id": 1,
      "username": "john_doe",
      "investment": 50000,
      "tenure_months": 12,
      "share_percent": 5,
      "amount": 12500
    },
    {
      "user_id": 2,
      "username": "jane_smith",
      "investment": 25000,
      "tenure_months": 6,
      "share_percent": 5,
      "amount": 6250
    }
  ],
  "metadata": {
    "formula_type": "weighted_investment",
    "eligibility_criteria": {
      "min_months": 3,
      "min_investment": 10000,
      "require_active": true
    },
    "eligible_users": 2,
    "total_eligible_investment": 75000
  },
  "total_distributed": 18750
}
```

**Features**:
- No database changes (preview only)
- Shows who will receive how much
- Displays calculation metadata
- Test different formulas before committing

**Use Cases**:
- Verify calculations before distribution
- Test different formula types
- Review eligibility filtering
- Audit trail preparation

---

### 7. Approve & Distribute ✅
**API Endpoint**: `POST /api/v1/admin/profit-sharing/{id}/distribute`

**Description**: Execute the distribution and credit wallets.

**Process**:
1. Validates period status is 'calculated'
2. Loads all distributions with user details
3. Applies TDS if applicable (based on PAN and threshold)
4. Creates bonus transactions
5. Credits net amount to user wallets
6. Updates period status to 'distributed'
7. Records admin who executed

**Response**:
```json
{
  "message": "Profit share distributed successfully."
}
```

**Database Changes**:
- Creates `BonusTransaction` for each user
- Updates `Wallet` balance via `WalletService`
- Updates `user_profit_shares.bonus_transaction_id`
- Updates `profit_shares.status` to 'distributed'
- Sets `profit_shares.admin_id` to executing admin

**TDS Handling**:
- Checks if user has PAN number in KYC
- Applies TDS only if amount > threshold
- Uses settings: `profit_share_tds_rate` and `profit_share_tds_threshold`
- Records TDS deducted in bonus transaction

---

### 8. Manual Adjustments Per User ✅
**API Endpoint**: `POST /api/v1/admin/profit-sharing/{id}/adjust`

**Request**:
```json
{
  "user_id": 5,
  "amount": 15000,
  "reason": "Special achievement bonus"
}
```

**Description**: Manually adjust a user's share before distribution.

**Features**:
- Can only adjust 'calculated' periods
- Creates or updates user distribution
- Records reason in notes field
- Allows admin overrides

**Use Cases**:
- Reward exceptional performance
- Correct calculation errors
- Apply special bonuses
- Handle edge cases

---

### 9. Reverse Distribution (If Error) ✅
**API Endpoint**: `POST /api/v1/admin/profit-sharing/{id}/reverse`

**Request**:
```json
{
  "reason": "Calculation error detected - incorrect formula applied"
}
```

**Description**: Reverse an entire distribution if errors are found.

**Process**:
1. Validates period status is 'distributed'
2. Checks all users have sufficient wallet balance
3. Debits net amount from each wallet
4. Creates reversal bonus transactions
5. Updates period status to 'reversed'
6. Logs action for audit trail

**Safety Checks**:
- Only works on 'distributed' periods
- Verifies wallet balances before reversal
- Fails fast if any user has insufficient funds
- All operations in database transaction

**Response**:
```json
{
  "message": "Distribution reversed successfully."
}
```

**Example Error**:
```json
{
  "message": "Reversal failed: User 15 has insufficient funds."
}
```

---

### 10. Publish Financial Report with Visibility Controls ✅
**API Endpoint**: `POST /api/v1/admin/profit-sharing/{id}/publish-report`

**Request**:
```json
{
  "visibility": "public"
}
```

**Visibility Options**:
- `public` - Visible to everyone, includes beneficiary details
- `private` - Only visible to admins
- `partners_only` - Visible to partners/investors only

**Response**:
```json
{
  "message": "Report published successfully",
  "report": {
    "period_name": "Q4 2025 Profit Share",
    "period": {
      "start_date": "2025-10-01",
      "end_date": "2025-12-31"
    },
    "financials": {
      "net_profit": 5000000,
      "total_pool": 500000,
      "total_distributed": 487350,
      "percentage_distributed": 10.0
    },
    "statistics": {
      "total_beneficiaries": 45,
      "average_per_user": 10830,
      "highest_share": 25000,
      "lowest_share": 2500
    },
    "metadata": {
      "formula_type": "weighted_investment",
      "eligibility_criteria": {...},
      "eligible_users": 45,
      "total_eligible_investment": 2500000
    },
    "beneficiaries": [
      {"user_id": 1, "username": "john_doe", "amount": 12500},
      {"user_id": 2, "username": "jane_smith", "amount": 6250}
    ],
    "published_at": "2025-12-09T...",
    "published_by": "admin"
  }
}
```

**Privacy Controls**:
- `public`: Shows all beneficiary details including usernames
- `private`: No beneficiary list, only aggregate statistics
- `partners_only`: Shows distribution ranges instead of individual amounts

**Distribution Ranges (Private Mode)**:
```json
{
  "distribution_ranges": {
    "below_1000": 5,
    "1000_5000": 15,
    "5000_10000": 20,
    "above_10000": 5
  }
}
```

**Settings**:
- `profit_share_default_visibility` - Default visibility for reports
- `profit_share_auto_publish` - Auto-publish after distribution
- `profit_share_show_beneficiary_details` - Always show details regardless of visibility

**Get Published Report**: `GET /api/v1/admin/profit-sharing/{id}/report`

---

## API Endpoints Summary

### Profit Sharing Management
```
GET    /api/v1/admin/profit-sharing                         - List all periods
GET    /api/v1/admin/profit-sharing/{id}                    - Get period details
POST   /api/v1/admin/profit-sharing                         - Create new period
PUT    /api/v1/admin/profit-sharing/{id}                    - Update period
DELETE /api/v1/admin/profit-sharing/{id}                    - Delete period

GET    /api/v1/admin/profit-sharing-settings                - Get settings
PUT    /api/v1/admin/profit-sharing-settings                - Update settings

POST   /api/v1/admin/profit-sharing/{id}/preview            - Preview calculation
POST   /api/v1/admin/profit-sharing/{id}/calculate          - Calculate distribution
POST   /api/v1/admin/profit-sharing/{id}/distribute         - Distribute to wallets
POST   /api/v1/admin/profit-sharing/{id}/adjust             - Manual adjustment
POST   /api/v1/admin/profit-sharing/{id}/reverse            - Reverse distribution

POST   /api/v1/admin/profit-sharing/{id}/publish-report     - Publish report
GET    /api/v1/admin/profit-sharing/{id}/report             - Get published report
```

### User Endpoints
```
GET    /api/v1/user/profit-sharing                          - User's profit share history
```

---

## Database Schema

### profit_shares Table
```sql
CREATE TABLE profit_shares (
  id BIGINT PRIMARY KEY,
  period_name VARCHAR(255) UNIQUE,
  start_date DATE,
  end_date DATE,
  total_pool DECIMAL(14,2),
  net_profit DECIMAL(14,2),
  status VARCHAR (pending, calculated, distributed, cancelled, reversed),
  report_visibility VARCHAR (public, private, partners_only),
  report_url TEXT,
  calculation_metadata JSON,
  admin_id BIGINT,
  published_by BIGINT,
  published_at TIMESTAMP,
  created_at TIMESTAMP,
  updated_at TIMESTAMP
);
```

### user_profit_shares Table
```sql
CREATE TABLE user_profit_shares (
  id BIGINT PRIMARY KEY,
  user_id BIGINT,
  profit_share_id BIGINT,
  amount DECIMAL(10,2),
  bonus_transaction_id BIGINT,
  notes TEXT,
  created_at TIMESTAMP,
  updated_at TIMESTAMP
);
```

---

## Settings Configuration

All settings stored in `settings` table with group `profit_share_config`:

| Setting Key | Default | Type | Description |
|------------|---------|------|-------------|
| `profit_share_frequency` | `quarterly` | string | Distribution frequency |
| `profit_share_auto_calculate` | `false` | boolean | Auto-calculate distributions |
| `profit_share_auto_distribute` | `false` | boolean | Auto-distribute after calculation |
| `profit_share_min_months` | `3` | number | Min months to be eligible |
| `profit_share_min_investment` | `10000` | number | Min investment amount |
| `profit_share_require_active_subscription` | `true` | boolean | Require active subscription |
| `profit_share_formula_type` | `weighted_investment` | string | Calculation formula |
| `profit_share_investment_weight` | `0.7` | number | Investment weight (tenure formula) |
| `profit_share_tenure_weight` | `0.3` | number | Tenure weight (tenure formula) |
| `profit_share_default_visibility` | `private` | string | Default report visibility |
| `profit_share_auto_publish` | `false` | boolean | Auto-publish reports |
| `profit_share_show_beneficiary_details` | `false` | boolean | Show beneficiary details |
| `profit_share_tds_enabled` | `true` | boolean | Apply TDS deduction |
| `profit_share_tds_rate` | `0.10` | number | TDS rate (10%) |
| `profit_share_tds_threshold` | `5000` | number | TDS threshold amount |

---

## Typical Workflow

### Complete Profit Share Cycle

```
1. CREATE PERIOD
   POST /api/v1/admin/profit-sharing
   {
     "period_name": "Q4 2025",
     "start_date": "2025-10-01",
     "end_date": "2025-12-31",
     "net_profit": 5000000,
     "total_pool": 500000
   }

   ✅ Status: pending

2. PREVIEW CALCULATION
   POST /api/v1/admin/profit-sharing/1/preview

   ✅ Review: 45 users, ₹487,350 total

3. CALCULATE (Save to DB)
   POST /api/v1/admin/profit-sharing/1/calculate

   ✅ Status: calculated

4. MANUAL ADJUSTMENT (Optional)
   POST /api/v1/admin/profit-sharing/1/adjust
   {
     "user_id": 5,
     "amount": 15000,
     "reason": "Top performer bonus"
   }

   ✅ User 5 amount updated

5. DISTRIBUTE
   POST /api/v1/admin/profit-sharing/1/distribute

   ✅ Status: distributed
   ✅ Wallets credited
   ✅ TDS applied where applicable

6. PUBLISH REPORT
   POST /api/v1/admin/profit-sharing/1/publish-report
   {
     "visibility": "public"
   }

   ✅ Report published and accessible
```

### Reversal Workflow (If Needed)

```
1. REVERSE DISTRIBUTION
   POST /api/v1/admin/profit-sharing/1/reverse
   {
     "reason": "Calculation error - incorrect formula"
   }

   ✅ Status: reversed
   ✅ Wallets debited
   ✅ Reversal transactions created

2. FIX AND RECALCULATE
   - Update settings if needed
   - Change period back to 'pending' manually
   - Repeat calculation and distribution
```

---

## Testing Guide

### 1. Test Period Creation
1. Navigate to Admin → Profit Sharing
2. Click "Create New Period"
3. Fill in period details
4. Verify period appears with 'pending' status

### 2. Test Eligibility Criteria
1. Update settings: min_months = 6, min_investment = 20000
2. Create test users with various criteria
3. Run preview calculation
4. Verify only eligible users appear

### 3. Test Formula Types
1. Create period with ₹100,000 pool
2. Preview with `weighted_investment` formula
3. Preview with `equal_split` formula
4. Preview with `tenure_based` formula
5. Compare results and distributions

### 4. Test Distribution
1. Calculate period
2. Verify distributions table populated
3. Execute distribution
4. Check user wallets for credits
5. Verify bonus transactions created

### 5. Test Manual Adjustment
1. Calculate period (don't distribute yet)
2. Adjust user's amount
3. Re-distribute
4. Verify adjusted amount credited

### 6. Test Reversal
1. Distribute period
2. Reverse with reason
3. Check wallet balances deducted
4. Verify reversal transactions created

### 7. Test Report Publishing
1. Distribute period
2. Publish with 'public' visibility
3. Verify report accessible
4. Check beneficiary details shown
5. Change to 'private' visibility
6. Verify details hidden

---

## Security Considerations

1. **Permission Protection**: All admin endpoints require `bonuses.manage_config` permission
2. **Status Validation**: Only correct status transitions allowed (pending → calculated → distributed)
3. **Transaction Safety**: All distributions use database transactions
4. **Audit Trail**: All actions logged with admin ID and timestamps
5. **Balance Verification**: Reversal checks wallet balances before deducting
6. **Visibility Controls**: Report access respects visibility settings
7. **TDS Compliance**: Automatic TDS deduction for tax compliance

---

## Troubleshooting

### Calculation Fails
**Check**:
- Total pool is positive
- At least one eligible user exists
- Eligibility criteria not too strict

### Distribution Fails
**Check**:
- Period status is 'calculated'
- Distributions exist in database
- WalletService is functioning

### Reversal Fails
**Check**:
- Period status is 'distributed'
- All users have sufficient balance
- No concurrent wallet operations

### Report Not Visible
**Check**:
- Report has been published
- Visibility settings correct
- User has appropriate permissions

---

## Calculation Examples

### Example 1: Weighted Investment Formula
**Setup**:
- Pool: ₹100,000
- User A: ₹50,000 investment, 5% plan share
- User B: ₹30,000 investment, 5% plan share
- User C: ₹20,000 investment, 10% plan share

**Calculation**:
- Total Investment: ₹100,000
- User A: (50,000 / 100,000) × 100,000 × 0.05 = ₹2,500
- User B: (30,000 / 100,000) × 100,000 × 0.05 = ₹1,500
- User C: (20,000 / 100,000) × 100,000 × 0.10 = ₹2,000
- **Total Distributed: ₹6,000**

### Example 2: Equal Split Formula
**Setup**:
- Pool: ₹90,000
- 3 eligible users all with 5% plan share

**Calculation**:
- User A: (90,000 / 3) × 0.05 = ₹1,500
- User B: (90,000 / 3) × 0.05 = ₹1,500
- User C: (90,000 / 3) × 0.05 = ₹1,500
- **Total Distributed: ₹4,500**

### Example 3: Tenure-Based Formula
**Setup**:
- Pool: ₹100,000
- Investment Weight: 70%, Tenure Weight: 30%
- User A: ₹60,000 investment (60%), 18 months tenure (60%)
- User B: ₹40,000 investment (40%), 12 months tenure (40%)
- Both have 5% plan share

**Calculation**:
- User A Combined: (0.6 × 0.7) + (0.6 × 0.3) = 0.42 + 0.18 = 0.60
- User A Share: 100,000 × 0.60 × 0.05 = ₹3,000
- User B Combined: (0.4 × 0.7) + (0.4 × 0.3) = 0.28 + 0.12 = 0.40
- User B Share: 100,000 × 0.40 × 0.05 = ₹2,000
- **Total Distributed: ₹5,000**

---

## Changelog

### Version 2025-12-09
- ✅ All 10 Profit Sharing features implemented
- ✅ Enhanced ProfitShareService with 3 formula types
- ✅ Added preview calculation (non-destructive)
- ✅ Implemented configurable eligibility criteria
- ✅ Added comprehensive settings management
- ✅ Implemented report publishing with visibility controls
- ✅ Added calculation metadata tracking
- ✅ Created migration for new fields (5) and settings (15)
- ✅ Enhanced ProfitShareController with 4 new methods
- ✅ Added 4 new API routes
- ✅ Updated ProfitShare model with new fields and relationships

---

**End of Documentation**
