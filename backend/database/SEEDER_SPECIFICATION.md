# PHASE 1: COMPREHENSIVE SEEDER SPECIFICATION
## PreIPOsip Platform - Post-Audit Production Seeder

**Document Version:** 1.0
**Created:** 2026-01-05
**Purpose:** Zero-error, production-safe database seeding for end-to-end testing

---

## EXECUTIVE SUMMARY

This seeder is designed to populate the PreIPOsip database with a **realistic, coherent, minimal dataset** that enables:

1. **Admin testing** - Full admin panel functionality (users, KYC, investments, campaigns)
2. **User testing** - Complete user journey (signup → KYC → invest → referral → withdraw)
3. **Company testing** - Company portal access and deal management
4. **System flows** - End-to-end workflows without manual data entry

### CRITICAL CONSTRAINTS

✅ **ZERO database errors guaranteed**
✅ **Production-safe** - No truncation, no overwrites, existence checks
✅ **Idempotent** - Can be run multiple times safely
✅ **Configurable** - All business values from `settings` table
✅ **Financial integrity** - Wallet conservation, ledger accuracy
✅ **Relationship completeness** - All foreign keys satisfied

---

## SEEDING PHILOSOPHY

### What We WILL Seed:
- **System configuration** (settings, permissions, roles, feature flags)
- **Admin infrastructure** (admin users, templates, legal agreements)
- **Product catalog** (companies, products, sectors, bulk inventory)
- **Investment framework** (plans, plan features, plan-product mappings)
- **Sample data for testing** (3-5 test users, subscriptions, investments)
- **Communication templates** (email, SMS templates)
- **Content structure** (menus, pages, help center categories)

### What We WILL NOT Seed:
- **Production user data** (real customer information)
- **Actual financial transactions** (except test/demo data)
- **Sensitive records** (real KYC documents, real payment data)
- **Historical data** (past campaigns, closed subscriptions)

### Guiding Principles:
1. **Minimal but complete** - Seed only what's necessary for testing
2. **Realistic values** - Economically sensible amounts, dates, relationships
3. **Clear provenance** - Every seeded record has `seeded_at` or comment
4. **Natural keys** - Use codes, slugs, identifiers for idempotency
5. **Service layer respect** - Use services where invariants exist

---

## COMPLETE SEEDING ORDER (59 Tables)

### PHASE 1: FOUNDATION (7 tables)
**No dependencies - Safe to seed first**

| # | Table | Records | Purpose |
|---|-------|---------|---------|
| 1 | `settings` | ~50 | System configuration (fees, limits, toggles) |
| 2 | `permissions` | ~80 | Spatie permission definitions |
| 3 | `roles` | 5 | Admin, User, Company, Support, Developer |
| 4 | `sectors` | ~15 | Industry taxonomy (Technology, Healthcare, Finance) |
| 5 | `feature_flags` | ~20 | Feature toggles (KYC, Investment, Referral) |
| 6 | `kyc_rejection_templates` | ~10 | Reusable KYC rejection reasons |
| 7 | `legal_agreements` | 8 | Terms, Privacy, Risk Disclosure (v1.0) |

**Rationale:** These tables have no foreign key dependencies and are required by almost all other tables.

---

### PHASE 2: IDENTITY & ACCESS (10 tables)
**Requires: roles, permissions**

| # | Table | Records | Purpose |
|---|-------|---------|---------|
| 8 | `users` | 10 | 1 Super Admin, 2 Admins, 5 Test Users, 2 Company Reps |
| 9 | `model_has_roles` | 10 | Assign roles to users |
| 10 | `model_has_permissions` | 0 | Direct permissions (roles cover all) |
| 11 | `user_profiles` | 10 | Extended profile info for all users |
| 12 | `user_kyc` | 7 | KYC records (5 verified users, 2 pending) |
| 13 | `kyc_documents` | 14 | 2 documents per KYC (Aadhaar, PAN) |
| 14 | `wallets` | 7 | User wallets (5 test users + 2 funded) |
| 15 | `admin_ledger_entries` | 2 | Genesis entry for admin wallet |
| 16 | `user_settings` | 7 | User preferences (email notifications, 2FA) |
| 17 | `password_histories` | 0 | Empty initially (populated on password change) |

**Key Decisions:**
- **Admin users:** `admin@preiposip.com` (super admin), `support@preiposip.com`, `kyc@preiposip.com`
- **Test users:** `user1@test.com` through `user5@test.com` (all KYC verified, wallets funded)
- **Company users:** `company1@example.com`, `company2@example.com`
- **Wallet balances:** User1: ₹50,000, User2: ₹1,00,000, User3: ₹25,000, User4: ₹0, User5: ₹0
- **KYC states:** Users 1-5 verified, Company users pending

**Admin Ledger Genesis:**
```
Entry 1: DEBIT  - ₹10,00,000 (Initial Liability for Test Wallets)
Entry 2: CREDIT - ₹10,00,000 (Offsetting entry for balance)
```

---

### PHASE 3: COMPANIES & PRODUCTS (12 tables)
**Requires: users (admin_id, company_user_id), sectors**

| # | Table | Records | Purpose |
|---|-------|---------|---------|
| 18 | `companies` | 5 | Pre-IPO companies (TechCorp, HealthPlus, FinanceHub, EduTech, GreenEnergy) |
| 19 | `products` | 5 | 1 product per company (shares) |
| 20 | `product_highlights` | 15 | 3 highlights per product |
| 21 | `product_founders` | 10 | 2 founders per company |
| 22 | `product_funding_rounds` | 8 | Seed, Series A, Series B rounds |
| 23 | `product_key_metrics` | 15 | Revenue, Users, Growth metrics |
| 24 | `product_risk_disclosures` | 10 | 2 risks per product |
| 25 | `product_price_histories` | 10 | 2 price points per product |
| 26 | `bulk_purchases` | 5 | Admin inventory (1 bulk purchase per product) |
| 27 | `company_users` | 2 | Link company reps to companies |
| 28 | `company_documents` | 10 | Pitch decks, financials per company |
| 29 | `company_share_listings` | 5 | 1 share listing per company (approved) |

**Key Decisions:**
- **Companies:** Mix of sectors (2 Tech, 1 Healthcare, 1 Fintech, 1 GreenTech)
- **Bulk purchases:**
  - TechCorp: 10,000 shares @ ₹500/share = ₹50,00,000 (5,000 allocated, 5,000 reserved)
  - HealthPlus: 5,000 shares @ ₹800/share = ₹40,00,000 (2,000 allocated, 3,000 reserved)
  - FinanceHub: 8,000 shares @ ₹600/share = ₹48,00,000 (4,000 allocated, 4,000 reserved)
  - EduTech: 3,000 shares @ ₹1000/share = ₹30,00,000 (1,000 allocated, 2,000 reserved)
  - GreenEnergy: 6,000 shares @ ₹750/share = ₹45,00,000 (3,000 allocated, 3,000 reserved)
- **Inventory conservation:** `total_quantity = quantity_allocated + quantity_reserved`

---

### PHASE 4: INVESTMENT PLANS (7 tables)
**Requires: products**

| # | Table | Records | Purpose |
|---|-------|---------|---------|
| 30 | `plans` | 3 | Plan A (₹5,000), Plan B (₹10,000), Plan C (₹25,000) |
| 31 | `plan_features` | 12 | 4 features per plan |
| 32 | `plan_configs` | 15 | Plan-specific configs (progressive bonus rates) |
| 33 | `plan_products` | 15 | All plans eligible for all products (5 products × 3 plans) |
| 34 | `menus` | 4 | Header, Footer, Admin, User menus |
| 35 | `menu_items` | 20 | Navigation items |
| 36 | `pages` | 10 | Static pages (About, How It Works, FAQ) |

**Key Decisions:**
- **Plan A:** ₹5,000/month, 12 months, 0.5% progressive bonus
- **Plan B:** ₹10,000/month, 12 months, 0.75% progressive bonus, priority allocation
- **Plan C:** ₹25,000/month, 12 months, 1.0% progressive bonus, guaranteed allocation
- **Plan-product eligibility:** All plans can invest in all products (flexible allocation)

---

### PHASE 5: COMMUNICATION TEMPLATES (4 tables)
**Requires: none (independent)**

| # | Table | Records | Purpose |
|---|-------|---------|---------|
| 37 | `email_templates` | 15 | Welcome, KYC, Payment, Withdrawal emails |
| 38 | `sms_templates` | 10 | OTP, Payment confirmation, KYC update |
| 39 | `canned_responses` | 10 | Support quick replies |
| 40 | `kb_categories` | 5 | Help categories (Getting Started, KYC, Investment) |

**Key Templates:**
- `welcome_email` - New user onboarding
- `kyc_approved` - KYC verification success
- `kyc_rejected` - KYC rejection with reasons
- `payment_success` - SIP payment confirmation
- `withdrawal_approved` - Withdrawal processed
- `otp_verification` - OTP for email/mobile verification

---

### PHASE 6: CAMPAIGNS & INCENTIVES (5 tables)
**Requires: users, products**

| # | Table | Records | Purpose |
|---|-------|---------|---------|
| 41 | `referral_campaigns` | 2 | Standard Referral (₹500), Premium Referral (₹1,000) |
| 42 | `campaigns` | 3 | New Year Offer (10% off), First Investment (5% cashback) |
| 43 | `lucky_draws` | 1 | Monthly Draw (Prize pool ₹50,000) |
| 44 | `profit_shares` | 0 | Empty (created when profit is distributed) |
| 45 | `celebration_events` | 2 | Birthday Bonus (₹100), Anniversary Bonus (1% of invested) |

**Key Decisions:**
- **Referral campaigns:** Active, unlimited redemptions, valid for 1 year
- **Promotional campaigns:** New Year (10% discount), First Investment (₹500 cashback)
- **Lucky draw:** Monthly, 1st prize ₹25,000, 2nd ₹15,000, 3rd ₹10,000
- **Campaign liability tracking:** All campaigns tracked in `admin_ledger_entries` and `benefit_audit_log`

---

### PHASE 7: USER INVESTMENTS (TEST DATA) (8 tables)
**Requires: users, products, plans, bulk_purchases**

| # | Table | Records | Purpose |
|---|-------|---------|---------|
| 46 | `subscriptions` | 5 | User1→Plan A, User2→Plan B, User3→Plan C, User4→Plan A, User5→Plan B |
| 47 | `payments` | 10 | 2 payments per subscription (Month 1, Month 2) |
| 48 | `transactions` | 14 | Payment credits + bonus credits (ledger) |
| 49 | `investments` | 5 | Top-level investment records |
| 50 | `user_investments` | 10 | Share allocations (2 products per user) |
| 51 | `bonus_transactions` | 5 | Progressive bonuses for User1, User2 |
| 52 | `referrals` | 2 | User4 referred by User1, User5 referred by User2 |
| 53 | `campaign_usages` | 3 | Campaign redemptions with liability tracking |

**Investment Scenario:**
- **User1 (Plan A):** Invested ₹10,000 (2 months × ₹5,000), allocated to TechCorp + HealthPlus
- **User2 (Plan B):** Invested ₹20,000 (2 months × ₹10,000), allocated to FinanceHub + EduTech
- **User3 (Plan C):** Invested ₹50,000 (2 months × ₹25,000), allocated to GreenEnergy + TechCorp
- **User4 (Plan A):** Invested ₹5,000 (1 month), referred by User1 (referral bonus: ₹500)
- **User5 (Plan B):** Invested ₹10,000 (1 month), referred by User2 (referral bonus: ₹1,000)

**Transaction Ledger Example (User1):**
```
Tx1: CREDIT  ₹5,000 (Payment Month 1) - Balance: ₹50,000 → ₹55,000
Tx2: DEBIT   ₹5,000 (Investment TechCorp) - Balance: ₹55,000 → ₹50,000
Tx3: CREDIT  ₹5,000 (Payment Month 2) - Balance: ₹50,000 → ₹55,000
Tx4: DEBIT   ₹5,000 (Investment HealthPlus) - Balance: ₹55,000 → ₹50,000
Tx5: CREDIT  ₹50 (Progressive Bonus 0.5% × ₹10,000) - Balance: ₹50,000 → ₹50,050
```

**Inventory Reconciliation:**
- **TechCorp bulk purchase:** 10,000 shares
  - User1: 10 shares (₹5,000 / ₹500)
  - User3: 40 shares (₹20,000 / ₹500)
  - **Total allocated:** 50 shares, **Reserved:** 9,950 shares ✅

---

### PHASE 8: SUPPORT & CONTENT (8 tables)
**Requires: users**

| # | Table | Records | Purpose |
|---|-------|---------|---------|
| 54 | `support_tickets` | 3 | Sample tickets (KYC query, Investment help, Withdrawal delay) |
| 55 | `support_messages` | 6 | 2 messages per ticket (user question + agent reply) |
| 56 | `kb_articles` | 10 | Help articles (How to invest, KYC guide, etc.) |
| 57 | `blog_posts` | 5 | Sample blog content (Pre-IPO guide, Market trends) |
| 58 | `banners` | 3 | Homepage banner, Investment CTA, Referral promo |
| 59 | `faqs` | 10 | Common questions (account, KYC, investment, withdrawal) |

---

## SEEDER IMPLEMENTATION STRATEGY

### Orchestration Approach:
```php
DatabaseSeeder::run()
├── FoundationSeeder (Phase 1)
├── IdentityAccessSeeder (Phase 2)
├── CompaniesProductsSeeder (Phase 3)
├── InvestmentPlansSeeder (Phase 4)
├── CommunicationSeeder (Phase 5)
├── CampaignsSeeder (Phase 6)
├── UserInvestmentsSeeder (Phase 7) [OPTIONAL - ENV controlled]
└── SupportContentSeeder (Phase 8) [OPTIONAL]
```

### Idempotency Guards:
```php
// Example: Settings
if (!Setting::where('key', 'platform_name')->exists()) {
    Setting::create(['key' => 'platform_name', 'value' => 'PreIPOsip']);
}

// Example: Users
$admin = User::firstOrCreate(
    ['email' => 'admin@preiposip.com'],
    ['name' => 'Super Admin', 'password' => Hash::make('password')]
);

// Example: Plans
Plan::updateOrCreate(
    ['code' => 'PLAN_A'],
    ['name' => 'Plan A', 'amount' => 5000, ...]
);
```

### Error Handling:
```php
DB::transaction(function () {
    // Seed phase 1
    // Seed phase 2
    // ...
    // If any error occurs, entire seeder rolls back
});
```

---

## CONFIGURATION VALUES (settings table)

### System Configuration
```
platform_name = "PreIPOsip"
platform_url = "https://preiposip.com"
support_email = "support@preiposip.com"
maintenance_mode = false
```

### Investment Configuration
```
min_investment_amount = 5000
max_investment_amount = 1000000
allow_partial_exits = true
exit_penalty_percentage = 2.0
```

### Bonus Configuration
```
enable_progressive_bonus = true
enable_milestone_bonus = true
enable_referral_bonus = true
progressive_bonus_calculation = "monthly"
```

### KYC Configuration
```
kyc_required_for_investment = true
kyc_auto_approval_enabled = false
kyc_document_expiry_days = 365
```

### Withdrawal Configuration
```
min_withdrawal_amount = 500
max_withdrawal_per_day = 100000
withdrawal_processing_fee_percentage = 1.0
withdrawal_auto_approval_threshold = 10000
```

### Referral Configuration
```
referral_bonus_amount = 500
referral_minimum_investment = 5000
referral_max_level = 3
```

---

## VALIDATION CHECKLIST

Before marking seeder as complete, verify:

### Financial Integrity
- [ ] All wallet balances >= 0
- [ ] All transaction ledgers balanced (balance_after = balance_before ± amount)
- [ ] Admin ledger balanced (total debits = total credits)
- [ ] Inventory conservation (bulk_purchases.total_quantity = allocated + reserved)
- [ ] Campaign liability tracked in admin_ledger_entries

### Referential Integrity
- [ ] All foreign keys have parent records
- [ ] No orphaned records (child without parent)
- [ ] Circular dependencies handled (users.referred_by nullable)

### Constraint Satisfaction
- [ ] All NOT NULL fields populated
- [ ] All UNIQUE constraints satisfied (no duplicates)
- [ ] All CHECK constraints validated (balances, TDS rates, etc.)
- [ ] All ENUM fields use valid values

### Data Quality
- [ ] Realistic values (amounts, dates, names)
- [ ] Coherent relationships (investment amounts match allocations)
- [ ] Timestamps in correct order (created_at <= updated_at)
- [ ] Status values reflect actual state

### Idempotency
- [ ] Seeder can run multiple times without errors
- [ ] No duplicate key violations
- [ ] Existence checks before insert
- [ ] Natural keys used where possible

---

## APPENDIX: ASSUMED DEFAULTS

### User Passwords (Test Only)
All test users: `password` (hashed with bcrypt)

### Timestamps
All records: `created_at` = `updated_at` = current timestamp

### Soft Deletes
All seeded records: `deleted_at` = NULL (active)

### Status Defaults
- Users: `status = 'active'`
- KYC: `status = 'verified'` (test users), `'pending'` (company users)
- Subscriptions: `status = 'active'`
- Payments: `status = 'completed'`
- Investments: `status = 'active'`

### Currency
All amounts in INR (Indian Rupees), stored in paise (integer) where applicable

---

**END OF SPECIFICATION**

This document provides the complete blueprint for building a zero-error, production-safe seeder for the PreIPOsip platform.
