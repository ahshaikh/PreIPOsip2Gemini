# Exhaustive PreIPO SIP Platform Seeder

## Overview

The `ExhaustivePreIPOSeeder` is a comprehensive database seeder that creates realistic, production-like test data covering **ALL** features and tables of the PreIPO SIP platform.

## What This Seeder Creates

### 1. **System Configuration** (Settings, Feature Flags, IP Whitelist)
- 30+ system settings covering all modules
- 6 feature flags for A/B testing
- IP whitelist for admin access

### 2. **Roles & Permissions**
- 7 Roles: Super Admin, Admin, KYC Officer, Support Agent, Content Manager, Finance Manager, User
- 80+ granular permissions
- Role-permission mappings

### 3. **Legal & Compliance**
- 5 legal documents (Terms, Privacy, Cookie Policy, Investment Disclaimer, Refund Policy)
- Version tracking for each document
- Audit trail support

### 4. **Products (Pre-IPO Companies)**
- **20 products** with complete details:
  - 5 detailed products with full company profiles
  - 15 additional products with basic info
- For detailed products:
  - Company highlights (4-5 per product)
  - Founder profiles with LinkedIn links
  - Funding round history (2-3 rounds each)
  - Key business metrics
  - Risk disclosures (3+ per product)
  - 12 months of price history

### 5. **Investment Plans**
- **4 plans**: Starter (₹2,500), Growth (₹5,000), Premium (₹10,000), Elite (₹25,000)
- Plan features (5-9 per plan)
- Bonus configurations (progressive, milestone, consistency)
- Plan configs for bonus calculations

### 6. **CMS Content (Public/Visitor Data)**
- 4 pages (Home, About, How It Works, Contact)
- 5 FAQs across different categories
- 2 blog posts
- Menus and navigation

### 7. **Communication Templates**
- 3 email templates (Welcome, Payment Success, KYC Approved)
- 2 SMS templates (OTP, Payment Reminder)
- 2 KYC rejection templates
- 2 canned responses for support

### 8. **Admin Users** (5 admin accounts)
- **Super Admin**: `superadmin@preiposip.com` / `password123`
- **Admin**: `admin@preiposip.com` / `password123`
- **KYC Officer**: `kyc@preiposip.com` / `password123`
- **Support Agent**: `support@preiposip.com` / `password123`
- **Finance Manager**: `finance@preiposip.com` / `password123`

### 9. **Regular Users** (155 total users covering ALL scenarios)

#### User Scenarios:
- **20 New Signups** (no KYC) - `new_signup1@test.com` to `new_signup20@test.com`
- **15 KYC Pending** (submitted, awaiting verification) - `kyc_pending1@test.com` to `kyc_pending15@test.com`
- **10 KYC Rejected** (rejected documents) - `kyc_rejected1@test.com` to `kyc_rejected10@test.com`
- **25 KYC Approved** (verified, no subscription) - `kyc_approved_no_sub1@test.com` to `kyc_approved_no_sub25@test.com`
- **50 Active Subscribers** (active SIPs, various plans) - `active_subscriber1@test.com` to `active_subscriber50@test.com`
- **10 Paused Subscribers** (temporarily paused) - `paused_subscriber1@test.com` to `paused_subscriber10@test.com`
- **15 Cancelled Subscribers** (churned users) - `cancelled_subscriber1@test.com` to `cancelled_subscriber15@test.com`
- **5 Whale Users** (high-value investors) - `whale_user1@test.com` to `whale_user5@test.com`
- **5 Suspended Users** (account suspended) - `suspended1@test.com` to `suspended5@test.com`

**All regular user passwords**: `password123`

### 10. **Bulk Purchases** (Inventory Management)
- 2-3 bulk purchases per product (40-60 total)
- Discount and extra allocation tracking
- Remaining inventory tracking

### 11. **Subscriptions & Payments**
- Subscriptions for 70% of verified users
- 3-12 payments per subscription
- 80% on-time payments, 20% late
- Razorpay gateway integration mock data

### 12. **User Investments**
- 200+ investment allocations
- Linked to payments and bulk purchases
- Product allocations with units and values

### 13. **Wallets & Transactions**
- Wallet for every regular user (155 wallets)
- 5-15 transactions per user
- Deposits, bonuses, withdrawals

### 14. **Withdrawals**
- 30 withdrawal requests
- Various statuses: pending, approved, rejected, completed
- Bank account details

### 15. **Bonuses**
- Progressive bonuses (2% of payment)
- Milestone bonuses (at 6, 12, 24, 36 months)
- TDS deductions (10%)

### 16. **Referrals**
- 1 active referral campaign
- Referral chains (0-5 referrals per user)
- Completed and pending referrals

### 17. **Lucky Draws**
- 1 completed lucky draw
- 50 entries from active subscribers
- 3 winners with prizes (₹50k, ₹25k, ₹10k)

### 18. **Profit Sharing**
- 1 distributed profit share (Q4 2024)
- 30 user distributions
- ₹5M total pool from ₹10M net profit

### 19. **Support Tickets**
- 40 support tickets
- Various categories and priorities
- 2-5 messages per ticket (user + admin)
- Assigned to support agents

### 20. **Activity Logs**
- 10-30 logs per user
- 14 different action types
- 90-day history
- IP addresses and user agents

### 21. **Notifications**
- 5-15 notifications per user (50 users)
- Mix of read and unread
- 30-day notification history

---

## How to Run

### Option 1: Run Only Exhaustive Seeder

```bash
php artisan db:seed --class=ExhaustivePreIPOSeeder
```

### Option 2: Include in Database Seeder

Edit `/database/seeders/DatabaseSeeder.php` and add:

```php
// For development/staging only
if (App::environment(['local', 'staging'])) {
    $this->call(ExhaustivePreIPOSeeder::class);
}
```

Then run:

```bash
php artisan db:seed
```

### Option 3: Fresh Migration + Seeding

```bash
php artisan migrate:fresh --seed
# Then run the exhaustive seeder
php artisan db:seed --class=ExhaustivePreIPOSeeder
```

---

## Data Distribution Summary

| Category | Count | Notes |
|----------|-------|-------|
| **Admin Users** | 5 | All roles covered |
| **Regular Users** | 155 | All user states |
| **Products** | 20 | 5 detailed, 15 basic |
| **Plans** | 4 | All price tiers |
| **Subscriptions** | ~108 | 70% of verified users |
| **Payments** | ~648-1296 | 3-12 per subscription |
| **Bulk Purchases** | 40-60 | 2-3 per product |
| **User Investments** | 200 | Top payments |
| **Wallets** | 155 | 1 per regular user |
| **Transactions** | 775-2325 | 5-15 per wallet |
| **Withdrawals** | 30 | Various statuses |
| **Bonuses** | 300+ | Progressive + milestone |
| **Referrals** | ~125 | 0-5 per user |
| **Lucky Draw Entries** | 50 | Active subscribers |
| **Support Tickets** | 40 | With messages |
| **Activity Logs** | 1550-4650 | 10-30 per user |
| **Notifications** | 250-750 | 5-15 per user (50 users) |

---

## Testing Scenarios

### Visitor/Public Testing
- Browse products (20 available)
- View plans (4 plans)
- Read FAQs (5 questions)
- Read blog (2 posts)
- View legal documents (5 documents)

### User Journey Testing

#### 1. New User Registration
- Email: `new_signup1@test.com` / Password: `password123`
- Status: No KYC

#### 2. KYC Submission
- Email: `kyc_pending1@test.com` / Password: `password123`
- Status: KYC submitted, pending verification

#### 3. KYC Rejected
- Email: `kyc_rejected1@test.com` / Password: `password123`
- Status: KYC rejected

#### 4. Active Investor
- Email: `active_subscriber1@test.com` / Password: `password123`
- Status: KYC verified, active subscription, payments, investments

#### 5. Whale User
- Email: `whale_user1@test.com` / Password: `password123`
- Status: High-value investor with multiple referrals

### Admin Testing

#### Super Admin
- Email: `superadmin@preiposip.com` / Password: `password123`
- Access: All permissions

#### KYC Officer
- Email: `kyc@preiposip.com` / Password: `password123`
- Access: KYC verification only

#### Support Agent
- Email: `support@preiposip.com` / Password: `password123`
- Access: Support tickets only

#### Finance Manager
- Email: `finance@preiposip.com` / Password: `password123`
- Access: Payments, withdrawals, bonuses, profit sharing

---

## Database Size Estimation

Running this seeder will create approximately:
- **Total Records**: ~6,000 - 10,000 records
- **Database Size**: ~50-100 MB (depending on migrations)
- **Seeding Time**: ~30-60 seconds (on average hardware)

---

## Important Notes

1. **Environment Safety**: This seeder uses transactions and can be safely run in local/staging environments. DO NOT run in production without understanding the implications.

2. **Idempotency**: This seeder is NOT idempotent. Running it multiple times will create duplicate data. Always run `php artisan migrate:fresh` before reseeding.

3. **Foreign Key Dependencies**: The seeder respects all foreign key constraints and creates data in the correct order.

4. **Realistic Data**: All data is realistic and production-like, suitable for:
   - Development
   - QA testing
   - Demo presentations
   - Load testing preparation
   - Feature testing

5. **Password Security**: All test passwords are `password123`. Change this in production seeders.

---

## Extending the Seeder

To add more data or modify scenarios:

1. Open `/database/seeders/ExhaustivePreIPOSeeder.php`
2. Locate the relevant `seed*()` method
3. Modify the data arrays or counts
4. Run the seeder again

Example - Add more products:

```php
// In seedProducts() method, change:
for ($i = 6; $i <= 20; $i++) {
    // ... to ...
for ($i = 6; $i <= 50; $i++) {
```

---

## Troubleshooting

### Error: Foreign Key Constraint Fails
**Solution**: Run `php artisan migrate:fresh` first to reset the database.

### Error: Duplicate Entry
**Solution**: The database already has data. Either clear it or run `migrate:fresh`.

### Error: Class Not Found
**Solution**: Run `composer dump-autoload` to regenerate the autoload files.

### Seeder Takes Too Long
**Solution**: Reduce the counts in user scenarios or limit payment history.

---

## Credits

Created for PreIPO SIP Platform
Version: 1.0.0
Last Updated: December 2, 2025

For questions or issues, contact the development team.
