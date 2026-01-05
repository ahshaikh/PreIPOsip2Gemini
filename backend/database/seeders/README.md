# Comprehensive Database Seeder - PreIPOsip Platform (Post-Audit)

## Overview

This directory contains a **zero-error, production-safe, post-audit** comprehensive seeder system for the PreIPOsip platform. The seeder is designed to populate the database with all foundational data required for end-to-end testing and production deployment.

## Documentation

📖 **Complete Specification:** See `/backend/database/SEEDER_SPECIFICATION.md` for detailed table-by-table documentation.

## Seeder Architecture

### Main Orchestrator
- **`ComprehensiveSystemSeeder.php`** - Main entry point, orchestrates all phase seeders

### Phase Seeders
1. **`FoundationSeeder.php`** (Phase 1)
   - Settings (60+ configurations)
   - Permissions & Roles (Spatie)
   - Sectors (15 industry sectors)
   - Feature Flags (20+ toggles)
   - KYC Rejection Templates
   - Legal Agreements

2. **`IdentityAccessSeeder.php`** (Phase 2)
   - Admin Users (3)
   - Test Users (5)
   - Company Representatives (2)
   - User Profiles
   - User KYC Records
   - Wallets (with test balances)
   - Admin Ledger Genesis (₹10,00,000)
   - User Settings

3. **`CompaniesProductsSeeder.php`** (Phase 3)
   - Companies (5 with sector mapping)
   - Products (Pre-IPO shares)
   - Product Details (highlights, founders, metrics, risks)
   - Bulk Purchases (initial inventory)
   - Company Share Listings

4. **`InvestmentPlansSeeder.php`** (Phase 4)
   - Investment Plans (Plan A, B, C)
   - Plan Features
   - Plan Configurations (bonus rates, etc.)
   - Plan-Product Eligibility Mappings
   - Navigation Menus
   - Static Pages

5. **`CommunicationCampaignsSeeder.php`** (Phase 5 & 6)
   - Email Templates (10+)
   - SMS Templates (5+)
   - Canned Responses (Support)
   - KB Categories
   - Referral Campaigns (2)
   - Promotional Campaigns (3)
   - Lucky Draws (1)

6. **`UserInvestmentsSeeder.php`** (Phase 7 - OPTIONAL)
   - Test Subscriptions
   - Test Payments
   - Test Investments
   - Share Allocations
   - Wallet Transactions
   - Bonus Transactions
   - Referrals
   - **⚠️ Only runs in local/testing/development environments**

## Usage

### Run Complete Seeder
```bash
# Run all phases (skips UserInvestmentsSeeder in production)
php artisan db:seed --class=ComprehensiveSystemSeeder
```

### Run Individual Phase Seeders
```bash
# Phase 1: Foundation
php artisan db:seed --class=FoundationSeeder

# Phase 2: Identity & Access
php artisan db:seed --class=IdentityAccessSeeder

# Phase 3: Companies & Products
php artisan db:seed --class=CompaniesProductsSeeder

# Phase 4: Investment Plans
php artisan db:seed --class=InvestmentPlansSeeder

# Phase 5 & 6: Communication & Campaigns
php artisan db:seed --class=CommunicationCampaignsSeeder

# Phase 7: User Investments (TEST DATA ONLY)
php artisan db:seed --class=UserInvestmentsSeeder
```

### Fresh Database with Seeder
```bash
# WARNING: This will drop all tables and reseed
php artisan migrate:fresh --seed --seeder=ComprehensiveSystemSeeder
```

## Seeded Data Summary

### Users Created
- **Admin Users (3):**
  - `admin@preiposip.com` - Super Admin
  - `support@preiposip.com` - Support Manager
  - `kyc@preiposip.com` - KYC Reviewer

- **Test Users (5):**
  - `user1@test.com` - Wallet: ₹50,000
  - `user2@test.com` - Wallet: ₹1,00,000
  - `user3@test.com` - Wallet: ₹25,000
  - `user4@test.com` - Wallet: ₹0 (referred by User 1)
  - `user5@test.com` - Wallet: ₹0 (referred by User 2)

- **Company Representatives (2):**
  - `company1@example.com`
  - `company2@example.com`

### Default Password
🔐 **All users:** `password`

⚠️ **IMPORTANT:** Change these passwords in production!

### Companies & Products
- **5 Companies:**
  - TechCorp India (Technology)
  - HealthPlus Solutions (Healthcare)
  - FinanceHub Technologies (Financial Services)
  - EduTech Academy (Education)
  - GreenEnergy Innovations (Energy)

- **Bulk Inventory:**
  - TechCorp: 10,000 shares @ ₹500
  - HealthPlus: 5,000 shares @ ₹800
  - FinanceHub: 8,000 shares @ ₹600
  - EduTech: 3,000 shares @ ₹1,000
  - GreenEnergy: 6,000 shares @ ₹750

### Investment Plans
- **Plan A - Starter:** ₹5,000/month, 0.5% progressive bonus
- **Plan B - Growth:** ₹10,000/month, 0.75% progressive bonus
- **Plan C - Premium:** ₹25,000/month, 1.0% progressive bonus

## Safety Features

✅ **Idempotent** - Can be run multiple times without errors
✅ **Production-Safe** - Never truncates or overwrites existing data
✅ **Transactional** - All seeders wrapped in DB transactions
✅ **Foreign Key Safe** - Correct dependency order
✅ **Constraint Validated** - Respects NOT NULL, UNIQUE, CHECK constraints
✅ **Financial Integrity** - Wallet conservation, ledger accuracy
✅ **Environment Aware** - Test data only in local/testing

## Production Deployment

### DO NOT SEED USER DATA IN PRODUCTION
```bash
# Only run foundation data in production
php artisan db:seed --class=FoundationSeeder
php artisan db:seed --class=IdentityAccessSeeder  # Create admin users only
php artisan db:seed --class=CompaniesProductsSeeder
php artisan db:seed --class=InvestmentPlansSeeder
php artisan db:seed --class=CommunicationCampaignsSeeder
```

---

**Last Updated:** 2026-01-05
**Version:** 1.0 (Post-Audit)
