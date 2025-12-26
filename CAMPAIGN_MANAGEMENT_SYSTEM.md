# Campaign Management System - PreIPOsip

## 📋 Overview

The **Campaign Management System** is a production-grade, data-driven platform for creating, managing, and tracking promotional campaigns (discounts, bonuses, referral offers, etc.) without requiring code deployments.

This system **replaces the old seeder-based offer management** with a fully auditable, business-controlled workflow that meets financial and regulatory compliance requirements.

---

## 🎯 Key Features

### ✅ Business-Controlled Campaign Lifecycle
- **Create** campaigns via Admin UI (no code changes)
- **Approve** campaigns with workflow tracking
- **Schedule** campaigns with start/end dates
- **Activate/Pause** campaigns in real-time
- **Monitor** usage and analytics

### ✅ Compliance & Safety
- Full audit trail of all campaign applications
- Immutable usage records (no deletion, only soft disable)
- Automatic expiration after end_at
- Atomic usage limit enforcement (prevents race conditions)
- No client-side discount calculations (server-only)

### ✅ Financial Integrity
- Campaigns auto-expire outside active window
- Usage limits enforced atomically with database locks
- All applications auditable via `campaign_usages` table
- Campaign snapshots preserved at time of use

---

## 🏗️ Architecture

### Database Schema

#### `campaigns` Table
```sql
- id: Primary key
- title, subtitle, code (unique)
- description, long_description
- discount_type: enum(percentage, fixed_amount)
- discount_percent, discount_amount
- min_investment, max_discount
- usage_limit, usage_count, user_usage_limit
- start_at, end_at (scheduling)
- is_active (activation toggle)
- created_by, approved_by, approved_at (workflow)
- features (JSON), terms (JSON)
- is_featured
- timestamps
```

#### `campaign_usages` Table
```sql
- id: Primary key
- campaign_id, user_id
- applicable_type, applicable_id (polymorphic: Investment, Subscription, etc.)
- original_amount, discount_applied, final_amount
- campaign_code, campaign_snapshot (audit trail)
- ip_address, user_agent (fraud detection)
- used_at
- unique(campaign_id, applicable_type, applicable_id) -- Prevents double application
```

### Service Layer

**`CampaignService`** - All business logic lives here:

```php
// Core Methods
- validateCampaignCode(string $code): ?Campaign
- isApplicable(Campaign $campaign, User $user, float $amount): array
- calculateDiscount(Campaign $campaign, float $amount): float
- applyCampaign(Campaign $campaign, User $user, Model $applicable, float $amount): array

// Management Methods
- approveCampaign(Campaign $campaign, User $approver): bool
- activateCampaign(Campaign $campaign): bool
- pauseCampaign(Campaign $campaign): bool

// Analytics Methods
- getCampaignStats(Campaign $campaign): array
- getUserUsageCount(Campaign $campaign, User $user): int
- getApplicableCampaigns(User $user, ?float $amount = null): Collection
```

---

## 🔄 Campaign Lifecycle

### 1. Creation (Draft State)
```
Admin creates campaign via UI
↓
Status: Draft (is_approved = false)
↓
Campaign can be edited
```

### 2. Approval Workflow
```
Admin/Manager approves campaign
↓
approved_by, approved_at set
↓
Campaign cannot be edited (unless usage_count = 0)
```

### 3. Activation
```
Admin activates campaign
↓
is_active = true
↓
Campaign visible to users (if start_at <= now < end_at)
```

### 4. Application
```
User enters campaign code at checkout
↓
System validates (CampaignService::isApplicable)
↓
Discount calculated (CampaignService::calculateDiscount)
↓
Campaign applied (CampaignService::applyCampaign)
↓
CampaignUsage record created
↓
usage_count incremented (atomic)
```

### 5. Expiration
```
Campaign reaches end_at
↓
Automatically excluded from active campaigns
↓
No code changes needed
```

---

## 📊 Campaign State Diagram

A campaign's state is **derived** from timestamps and flags:

```
Draft      → is_approved = false
Scheduled  → approved + start_at > now
Live       → approved + is_active + start_at <= now < end_at
Paused     → approved + is_active = false
Expired    → end_at < now
```

**No fragile status enums** - state is computed dynamically.

---

## 🛠️ API Endpoints

### Admin APIs (`/api/v1/admin/campaigns`)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/` | List all campaigns (with filters) |
| POST | `/` | Create new campaign |
| GET | `/{campaign}` | Get campaign details + stats |
| PUT | `/{campaign}` | Update campaign (if editable) |
| POST | `/{campaign}/approve` | Approve campaign |
| POST | `/{campaign}/activate` | Activate campaign |
| POST | `/{campaign}/pause` | Pause campaign |
| GET | `/{campaign}/analytics` | Get usage analytics |
| GET | `/{campaign}/usages` | Get all usage records |

### User APIs (`/api/v1/campaigns`)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/active` | List active campaigns |
| GET | `/applicable` | Get campaigns applicable to user |
| GET | `/{id}` | Get campaign details |
| POST | `/validate` | Validate campaign code + calculate discount |
| GET | `/my/usages` | Get user's campaign usage history |

---

## 💻 Usage Examples

### Admin: Create Campaign

**Request:**
```http
POST /api/v1/admin/campaigns
Authorization: Bearer {admin_token}
Content-Type: application/json

{
  "title": "Diwali Special Offer",
  "subtitle": "Get 10% extra units",
  "code": "DIWALI10",
  "description": "Celebrate Diwali with 10% bonus on investments",
  "discount_type": "percentage",
  "discount_percent": 10.00,
  "min_investment": 25000.00,
  "max_discount": 5000.00,
  "usage_limit": 500,
  "user_usage_limit": 1,
  "start_at": "2025-10-20T00:00:00Z",
  "end_at": "2025-11-05T23:59:59Z",
  "features": [
    "10% extra units on all investments",
    "Valid on deals worth ₹25,000+",
    "Limited to 500 users"
  ],
  "terms": [
    "Valid only during Diwali period",
    "Cannot be combined with other offers",
    "Subject to availability"
  ],
  "is_featured": true
}
```

**Response:**
```json
{
  "message": "Campaign created successfully",
  "campaign": {
    "id": 5,
    "code": "DIWALI10",
    "state": "draft",
    "is_approved": false,
    "can_be_approved": true,
    ...
  }
}
```

### User: Validate Campaign

**Request:**
```http
POST /api/v1/campaigns/validate
Authorization: Bearer {user_token}
Content-Type: application/json

{
  "code": "WELCOME500",
  "amount": 15000.00
}
```

**Response:**
```json
{
  "valid": true,
  "message": "Campaign code is valid",
  "campaign": {
    "id": 1,
    "code": "WELCOME500",
    "title": "Welcome Bonus - New Investors",
    "discount_type": "fixed_amount",
    "discount_amount": 500.00,
    ...
  },
  "discount": 500.00,
  "final_amount": 14500.00
}
```

### User: Apply Campaign at Checkout

**Request:**
```http
POST /api/v1/user/investments
Authorization: Bearer {user_token}
Content-Type: application/json

{
  "deal_id": 3,
  "subscription_id": 12,
  "shares_allocated": 100,
  "campaign_code": "WELCOME500"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Investment created successfully.",
  "investment": { ... },
  "original_amount": 15000.00,
  "final_amount": 14500.00,
  "discount_applied": 500.00,
  "campaign_code": "WELCOME500"
}
```

---

## 🧪 Testing

### Unit Tests (CampaignService)

```php
// tests/Unit/Services/CampaignServiceTest.php

// Test Cases:
✅ Campaign code validation
✅ isApplicable - expired campaign
✅ isApplicable - usage limit reached
✅ isApplicable - user usage limit reached
✅ isApplicable - minimum investment not met
✅ calculateDiscount - percentage type
✅ calculateDiscount - fixed amount type
✅ calculateDiscount - max discount cap
✅ applyCampaign - success
✅ applyCampaign - duplicate application (idempotent)
✅ applyCampaign - race condition handling
✅ Campaign approval workflow
✅ Campaign activation/pause
```

### Integration Tests

```php
// tests/Feature/CampaignTest.php

✅ Admin can create campaign
✅ Admin can approve campaign
✅ Admin can activate campaign
✅ User can list active campaigns
✅ User can validate campaign code
✅ User can apply campaign at checkout
✅ Campaign usage limits enforced
✅ Campaign auto-expires after end_at
✅ Analytics endpoints return correct stats
```

---

## 🚀 Deployment Guide

### 1. Run Migrations

```bash
cd backend
php artisan migrate
```

This will:
- Rename `offers` → `campaigns`
- Add workflow fields (created_by, approved_by, etc.)
- Create `campaign_usages` table

### 2. Run Bootstrap Seeder (Optional)

```bash
php artisan db:seed --class=CampaignBootstrapSeeder
```

This creates ONE example campaign. **All other campaigns should be created via Admin UI.**

### 3. Clear Cache

```bash
php artisan route:cache
php artisan config:cache
```

### 4. Verify Routes

```bash
php artisan route:list | grep campaign
```

Expected output:
```
GET     /api/v1/campaigns/active
GET     /api/v1/campaigns/applicable
POST    /api/v1/campaigns/validate
GET     /api/v1/admin/campaigns
POST    /api/v1/admin/campaigns
...
```

---

## 👥 Team Workflows

### For Business/Marketing Teams

**Creating a New Campaign:**

1. Navigate to `/admin/campaigns`
2. Click "Create Campaign"
3. Fill in campaign details:
   - Title, code, description
   - Discount type & amount
   - Usage limits
   - Start/End dates
   - Features & terms
4. Save as Draft
5. Request approval from manager
6. Once approved, activate campaign
7. Monitor analytics in real-time

**Pausing a Campaign:**

1. Go to campaign details
2. Click "Pause Campaign"
3. Campaign immediately stops accepting new applications
4. Can re-activate anytime

### For Developers

**Adding New Applicable Types:**

To allow campaigns on new entities (e.g., Withdrawal):

```php
// In WithdrawalController.php
use App\Services\CampaignService;

protected $campaignService;

public function store(Request $request) {
    // ... withdrawal logic ...

    if ($campaignCode = $request->input('campaign_code')) {
        $campaign = $this->campaignService->validateCampaignCode($campaignCode);
        if ($campaign) {
            $result = $this->campaignService->applyCampaign(
                $campaign,
                $user,
                $withdrawal, // Polymorphic applicable
                $withdrawalAmount
            );
        }
    }
}
```

The `campaign_usages` table automatically supports any model via polymorphic relationships.

---

## 📈 Analytics & Reporting

### Campaign Performance Metrics

**Available via `/api/v1/admin/campaigns/{id}/analytics`:**

- Total usage count
- Unique users count
- Total discount given
- Average discount per use
- Usage by day (last 30 days)
- Top users by usage
- Remaining usage capacity
- Conversion rate (views → applications)

### Export Capabilities

```php
// Future enhancement: Export campaign data
Route::get('/admin/campaigns/{id}/export', [CampaignController::class, 'export']);
```

---

## 🔒 Security & Compliance

### Compliance Checklist

✅ All campaigns auto-expire after `end_at`
✅ No campaign applied outside active window
✅ Usage limits enforced atomically
✅ All applications auditable in `campaign_usages`
✅ No deletion of campaign/usage data
✅ Campaign snapshots preserved at application time
✅ IP address & user agent logged for fraud detection
✅ Unique constraint prevents double application
✅ Server-side discount calculation only
✅ Full workflow tracking (creator, approver)

### Audit Trail

Every campaign application creates an immutable record:

```sql
SELECT
    cu.id,
    cu.used_at,
    u.email as user_email,
    c.code as campaign_code,
    cu.original_amount,
    cu.discount_applied,
    cu.final_amount,
    cu.applicable_type,
    cu.applicable_id,
    cu.ip_address
FROM campaign_usages cu
JOIN users u ON cu.user_id = u.id
JOIN campaigns c ON cu.campaign_id = c.id
WHERE cu.campaign_id = ?
ORDER BY cu.used_at DESC;
```

---

## 🎓 Best Practices

### DO ✅

- Create campaigns via Admin UI
- Set appropriate usage limits
- Use scheduled campaigns for timed offers
- Monitor analytics regularly
- Test campaigns in staging first
- Use clear, unique campaign codes
- Set end_at dates for all campaigns
- Approve campaigns before activating

### DON'T ❌

- Use seeders for live campaigns
- Delete campaign records (disable instead)
- Calculate discounts on frontend
- Skip approval workflow
- Share campaign codes publicly before start_at
- Create campaigns without usage limits
- Modify active campaigns with existing usage
- Bypass service layer validations

---

## 📚 Additional Resources

### File Structure

```
backend/
├── app/
│   ├── Models/
│   │   ├── Campaign.php              # Campaign model with derived state
│   │   ├── CampaignUsage.php         # Usage tracking model
│   │   └── User.php                  # Updated with campaign relationships
│   ├── Services/
│   │   └── CampaignService.php       # Core domain logic
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Api/Admin/CampaignController.php  # Admin CRUD
│   │   │   └── Api/User/CampaignController.php   # User-facing APIs
│   │   └── Requests/Admin/
│   │       ├── StoreCampaignRequest.php
│   │       └── UpdateCampaignRequest.php
│   └── database/
│       ├── migrations/
│       │   ├── 2025_12_26_000001_rename_offers_to_campaigns_add_workflow_fields.php
│       │   └── 2025_12_26_000002_create_campaign_usages_table.php
│       └── seeders/
│           └── CampaignBootstrapSeeder.php
└── routes/
    └── api.php                       # Campaign routes registered
```

### Related Documentation

- [Laravel Service Container](https://laravel.com/docs/11.x/container)
- [Laravel Database Transactions](https://laravel.com/docs/11.x/database#database-transactions)
- [Laravel Eloquent Relationships](https://laravel.com/docs/11.x/eloquent-relationships)
- [PreIPOsip Admin Guide](./docs/ADMIN_GUIDE.md)

---

## 🐛 Troubleshooting

### Campaign Not Showing to Users

**Check:**
1. Is campaign approved? (`approved_at IS NOT NULL`)
2. Is campaign active? (`is_active = true`)
3. Is current time within start_at and end_at?
4. Has usage limit been reached?

**Debug Query:**
```sql
SELECT id, code, is_active, start_at, end_at, approved_at, usage_count, usage_limit
FROM campaigns
WHERE code = 'YOUR_CODE';
```

### Discount Not Applied

**Check:**
1. Is minimum investment met?
2. Has user exceeded user_usage_limit?
3. Was campaign_code sent in request?
4. Check application logs for validation errors

**Debug:**
```bash
tail -f storage/logs/laravel.log | grep -i campaign
```

### Campaign Usage Count Not Incrementing

This indicates a transaction failure. Check:
1. Database logs for lock timeouts
2. Application logs for exceptions
3. campaign_usages table for failed inserts

---

## 📞 Support

For issues or questions:
- **Tech Team:** Create issue in GitHub
- **Business Team:** Contact system admin
- **Compliance:** Review audit logs at `/admin/campaigns/{id}/usages`

---

## 🎉 Success Criteria

✅ **Campaigns can be created without deployments**
✅ **Business teams operate campaigns independently**
✅ **All usage is auditable**
✅ **No manual discount calculations**
✅ **System is safe for financial/regulatory scrutiny**
✅ **Developers don't touch campaign logic during routine operations**

---

**Built with ❤️ for PreIPOsip**
**Version:** 1.0.0
**Last Updated:** December 26, 2025
