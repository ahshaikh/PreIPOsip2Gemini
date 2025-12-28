# PreIPOsip Platform - Database Seeder Execution Order

## CRITICAL CONSTRAINTS

This seeder must obey these non-negotiable rules:
1. **Production-safe**: Additive only, never truncate or modify existing data
2. **Idempotent**: Can be run multiple times safely
3. **Admin configurable**: No hardcoded business logic values
4. **Financial integrity**: Respect ledger immutability, wallet conservation, admin solvency
5. **Relationship completeness**: Enable full end-to-end flows

---

## EXECUTION ORDER

### **PHASE 1: Foundation (Configuration & Permissions)**

#### 1.1 System Configuration
```
settings (CRITICAL - All business logic configuration)
  - Investment limits
  - Bonus percentages
  - Fee structures
  - TDS rates
  - Withdrawal limits
  - KYC requirements
  - etc.
```

#### 1.2 Authentication & Authorization
```
permissions → roles → model_has_roles → role_has_permissions
```

#### 1.3 Communication Templates
```
email_templates
sms_templates
kyc_rejection_templates
canned_responses
```

#### 1.4 Configuration Data
```
sectors
blog_categories
kb_categories
content_categories
menus → menu_items
communication_channels
sla_policies
legal_agreements → legal_agreement_versions
```

---

### **PHASE 2: Core Entities (Users, Products, Companies)**

#### 2.1 Users (Admin Genesis)
```
users (Create: superadmin, admin, test user, company user)
  ⚠️  Password: 'password' (as requested)
  ⚠️  Must assign roles via model_has_roles

user_profiles
user_kyc (Various statuses: pending, verified, rejected)
kyc_documents
```

#### 2.2 Wallets (Financial Genesis)
```
wallets (Create for all users)
  ⚠️  CRITICAL: Admin wallet must have sufficient genesis balance
  ⚠️  This is the "bank" - source of all credits
  ⚠️  User wallets start at 0 unless they have deposit transactions

⚠️  ADMIN SOLVENCY INVARIANT:
    admin_wallet.balance >= SUM(user_wallet.balance) + SUM(locked_balances)
```

#### 2.3 Products & Companies
```
companies
products (Pre-IPO companies available for investment)
  - product_highlights
  - product_founders
  - product_funding_rounds
  - product_key_metrics
  - product_risk_disclosures
  - product_price_histories
```

#### 2.4 Plans & Eligibility
```
plans (A, B, C plans with different features)
  - plan_features
  - plan_configs
  - plan_products (which products are available to which plans)
```

---

### **PHASE 3: Inventory & Company Workflow**

#### 3.1 Bulk Purchases (Inventory Source)
```
bulk_purchases (Admin purchases shares from companies)
  ⚠️  CRITICAL: This is the SOURCE of all share allocations
  ⚠️  value_remaining must be >= allocated shares
  ⚠️  MUST have company provenance

⚠️  INVENTORY CONSERVATION INVARIANT:
    bulk_purchase.value_remaining =
      bulk_purchase.total_value_received - SUM(user_investments.value_allocated)
```

#### 3.2 Deals
```
deals (Investment opportunities)
  ⚠️  Must reference existing products
  ⚠️  Must reference existing companies
```

#### 3.3 Company Portal
```
company_users
company_share_listings (Company submits shares for admin to purchase)
  ⚠️  Links to bulk_purchases when approved
```

---

### **PHASE 4: Campaigns (Referrals, Lucky Draws, Bonuses)**

#### 4.1 Referral System
```
referral_campaigns (Active campaigns with multipliers)
referrals (Referrer → Referred relationships)
  ⚠️  Must have at least one referred → referrer relationship
```

#### 4.2 Promotional Campaigns
```
campaigns (formerly offers - discounts on investments)
  ⚠️  Must have start_at, end_at, usage limits
  ⚠️  Must have created_by, approved_by (workflow)
```

#### 4.3 Lucky Draws
```
lucky_draws
  ⚠️  Will be populated with entries after payments
```

#### 4.4 Profit Sharing
```
profit_shares (Quarterly profit distribution)
  ⚠️  Will be populated after users have investments
```

---

### **PHASE 5: Subscriptions & Payments (Financial)**

#### 5.1 Subscriptions
```
subscriptions (Users subscribe to plans)
  ⚠️  Must reference existing users and plans
  ⚠️  Status: active, paused, cancelled, completed
```

#### 5.2 Payments (⚠️  IMMUTABLE FINANCIAL RECORDS)
```
payments (User deposits via Razorpay/manual)
  ⚠️  CRITICAL: Each payment triggers a chain reaction:
     1. Payment record created (immutable)
     2. Wallet transaction created (immutable)
     3. Wallet balance updated (conservation law)
     4. Share allocation (if investment payment)
     5. Bonus calculation (if applicable)
     6. Lucky draw entry (if applicable)

  ⚠️  STATUS FLOW:
     pending → processing → success → (possibly) refunded

  ⚠️  MUST respect payment-to-wallet transaction mapping
```

---

### **PHASE 6: Investments & Allocations (⚠️  CRITICAL FINANCIAL)**

#### 6.1 Investment Records
```
investments (High-level investment tracking)
  ⚠️  Links user → subscription → deal → company
```

#### 6.2 User Share Allocations
```
user_investments (Granular share allocations)
  ⚠️  CRITICAL INVARIANTS:
     - Must deduct from bulk_purchase.value_remaining
     - Must reference payment_id (source of funds)
     - If is_reversed=true, must add back to bulk_purchase

  ⚠️  ALLOCATION INTEGRITY:
     SUM(user_investments.value_allocated WHERE product_id=X)
       <= bulk_purchases.total_value_received WHERE product_id=X
```

---

### **PHASE 7: Transactions & Ledger (⚠️  IMMUTABLE RECORDS)**

#### 7.1 Wallet Transactions
```
transactions (⚠️  APPEND-ONLY LEDGER)
  ⚠️  CRITICAL: NEVER UPDATE OR DELETE
  ⚠️  Every wallet balance change MUST have a transaction record

  ⚠️  TRANSACTION TYPES:
     - deposit (payment success → wallet credit)
     - credit (admin credit, bonus)
     - debit (investment, withdrawal)
     - withdrawal (wallet → bank)
     - bonus (referral, consistency, milestone)
     - refund (payment reversal)
     - fee (withdrawal fee, platform fee)
     - tds (tax deduction)

  ⚠️  BALANCE CONSERVATION:
     balance_after = balance_before ± amount

  ⚠️  REFERENCE TRACKING:
     reference_type, reference_id (polymorphic)
       → Payment, Withdrawal, BonusTransaction
```

#### 7.2 Bonus Transactions
```
bonus_transactions (Bonus awards)
  ⚠️  Types: progressive, milestone, consistency, referral
  ⚠️  Must have corresponding wallet transaction
  ⚠️  tds_deducted field for tax compliance
```

#### 7.3 Lucky Draw Entries
```
lucky_draw_entries (After payments with bonus entries)
  ⚠️  base_entries = payment based
  ⚠️  bonus_entries = streak/multiplier based
```

#### 7.4 Withdrawals
```
withdrawals (User withdraws from wallet to bank)
  ⚠️  Must have sufficient wallet balance
  ⚠️  Must deduct fee and TDS
  ⚠️  Must create wallet transaction
```

---

### **PHASE 8: Campaign Usages & Profit Distribution**

#### 8.1 Campaign Usage Tracking
```
campaign_usages (Polymorphic - tracks discount usage)
  ⚠️  applicable_type, applicable_id (Payment, Investment, etc.)
  ⚠️  Tracks: original_amount, discount_applied, final_amount
  ⚠️  Must respect usage_limit on campaign
```

#### 8.2 Profit Share Distribution
```
user_profit_shares (After profit_shares are processed)
  ⚠️  Must have corresponding bonus_transaction
  ⚠️  Must respect profit share eligibility rules
```

---

### **PHASE 9: Support & Content (Can be seeded anytime)**

#### 9.1 Knowledge Base
```
kb_articles
  - article_feedback
  - kb_article_views
```

#### 9.2 Support System
```
support_tickets
  - support_messages
  - ticket_sla_tracking
```

#### 9.3 Content Management
```
pages
  - page_versions
  - page_blocks
blog_posts
faqs
tutorials
  - tutorial_steps
  - user_tutorial_progress
```

---

## ASSUMPTIONS & PRECONDITIONS

### What is ASSUMED to already exist:
- ✅ Database is migrated (all tables exist)
- ✅ Laravel application is configured
- ✅ Razorpay credentials are configured (for payment gateway references)

### What is being SEEDED:
- ✅ Admin users (superadmin, admin)
- ✅ Test users (with various KYC states)
- ✅ Company users (for company portal testing)
- ✅ System configuration (settings table)
- ✅ Plans (A, B, C with features)
- ✅ Products (Pre-IPO companies)
- ✅ Companies (for deals)
- ✅ Bulk purchases (inventory source)
- ✅ Sample subscriptions, payments, investments
- ✅ Sample transactions (to demonstrate flow)
- ✅ Communication templates
- ✅ Support system data (categories, canned responses)

### What is intentionally NOT SEEDED:
- ❌ Real user data (GDPR/privacy)
- ❌ Real payment records (financial compliance)
- ❌ Real withdrawal records (banking compliance)
- ❌ Production bulk purchase records (financial audit)
- ❌ Audit logs (generated by system usage)
- ❌ Email/SMS logs (generated by system)
- ❌ Webhook logs (generated by integrations)
- ❌ Performance metrics (generated by monitoring)
- ❌ Error logs (generated by system)

---

## FINANCIAL INVARIANTS THAT MUST BE RESPECTED

### 1. **Admin Wallet Solvency**
```sql
-- Admin wallet must have enough balance to cover all user balances
admin_wallet.balance >= SUM(user_wallets.balance) + SUM(user_wallets.locked_balance)
```

### 2. **Wallet Balance Conservation**
```sql
-- Every wallet change must have a transaction record
wallet.balance = SUM(transactions.amount WHERE type IN ('deposit', 'credit', 'bonus'))
                - SUM(transactions.amount WHERE type IN ('debit', 'withdrawal', 'fee'))
```

### 3. **Inventory Conservation**
```sql
-- Shares allocated cannot exceed shares purchased
bulk_purchase.value_remaining =
  bulk_purchase.total_value_received - SUM(user_investments.value_allocated WHERE bulk_purchase_id=X)
```

### 4. **Transaction Immutability**
```sql
-- Transactions are APPEND-ONLY
-- Use is_reversed flag, never UPDATE or DELETE
```

### 5. **Payment-to-Wallet Mapping**
```sql
-- Every successful payment must have a corresponding wallet transaction
payment.status = 'success' → EXISTS(transaction WHERE reference_type='Payment' AND reference_id=payment.id)
```

---

## SEEDER SAFETY CHECKS

Before each seeding operation, check:
1. ✅ Does the record already exist? (idempotency)
2. ✅ Are all foreign key references valid?
3. ✅ Are all financial invariants satisfied?
4. ✅ Is the data economically coherent?

After seeding, verify:
1. ✅ Admin wallet solvency
2. ✅ Inventory conservation
3. ✅ No negative balances
4. ✅ All relationships satisfied

---

## SEED DATA VOLUME

### Minimal (For basic testing):
- 1 superadmin, 1 admin, 3 test users, 1 company user
- 3 plans (A, B, C)
- 5 products
- 2 companies
- 3 bulk purchases
- 2 subscriptions
- 3 payments
- 2 investments

### Standard (For full testing):
- 2 superadmins, 3 admins, 10 test users, 3 company users
- 3 plans with all features
- 10 products (various sectors)
- 5 companies (various stages)
- 10 bulk purchases (different products)
- 10 subscriptions (various statuses)
- 20 payments (various statuses, types)
- 15 investments
- 10 bonus transactions
- 5 withdrawals (various statuses)

---

## NEXT STEP

Create the actual seeder implementation following this exact order.
