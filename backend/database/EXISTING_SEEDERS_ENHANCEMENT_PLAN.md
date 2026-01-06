# Existing Seeders Enhancement Plan (Post-Audit)

## Current State Analysis

### ✅ Already Working Seeders

1. **RolesAndPermissionsSeeder** - Creates roles (super-admin, admin, user, etc.)
2. **PermissionsSeeder** - Creates all permissions
3. **SettingsSeeder** - System settings
4. **PlanSeeder** - Investment plans
5. **ProductSeeder** - Products with VALUE-BASED bulk purchases
6. **HomePageSeeder** - CMS homepage content
7. **EmailTemplateSeeder** - Email templates
8. **SmsTemplateSeeder** - SMS templates
9. **KycRejectionTemplateSeeder** - KYC rejection templates
10. **CannedResponseSeeder** - Support canned responses
11. **FaqSeeder** - FAQ content
12. **FeatureFlagSeeder** - Feature flags
13. **LegalAgreementSeeder** - Legal documents
14. **UserSeeder** - Admin and test users with profiles, KYC, wallets
15. **CmsAndBonusSeeder** - CMS and bonus configuration
16. **ContentManagementSeeder** - Content management data
17. **TestDataSetSeeder** - Test data (local/staging only)

### Current Architecture (CORRECT)

**Products Table:**
- Standalone entities (NO company_id foreign key)
- Fields: `face_value_per_unit`, `min_investment`, `expected_ipo_date`
- Uses JSON `description` field

**BulkPurchases Table (VALUE-BASED):**
```php
face_value_purchased     // Rupee value purchased
actual_cost_paid         // What admin paid
discount_percentage      // Discount %
extra_allocation_percentage  // Bonus allocation %
total_value_received     // face_value * (1 + extra_alloc/100)
value_remaining          // Tracks remaining rupee value
seller_name
purchase_date
```

**Users/Wallets:**
- UserSeeder creates admin and test users correctly
- Wallets created without balance (uses balance_paise internally)

---

## Post-Audit Requirements vs Current Coverage

### ✅ Already Covered
- [x] Settings (60+ configs)
- [x] Permissions & Roles
- [x] Admin Users
- [x] Plans
- [x] Products
- [x] Bulk Purchases (value-based)
- [x] Email/SMS Templates
- [x] Legal Agreements
- [x] Feature Flags
- [x] KYC Templates
- [x] FAQs
- [x] CMS Content

### ⚠️ Needs Enhancement
- [ ] **Sectors Table** - Not seeded (but table exists)
- [ ] **Companies Table** - Not seeded (but table exists)
- [ ] **Additional Test Users** - Only 3 users created (need 5-7 for testing)
- [ ] **Referral Campaigns** - Not seeded
- [ ] **Promotional Campaigns** - Not seeded (has OffersSeeder but may need update)
- [ ] **Lucky Draws** - Not seeded
- [ ] **KB Categories & Articles** - Has KbSeeder but may need enhancement
- [ ] **Menus** - Not seeded
- [ ] **Admin Ledger Genesis** - Not seeded

### ❌ Not Needed (Already in TestDataSetSeeder)
- Subscriptions
- Payments
- User Investments
- Bonus Transactions
- Referrals

---

## Recommended Enhancement Strategy

### Option 1: Enhance Existing Seeders (RECOMMENDED)
**Approach:** Add missing data to existing seeders or create small focused seeders for gaps

**New Seeders to Create:**
1. **SectorSeeder** - 15 industry sectors
2. **CompanySeeder** - 5 sample companies
3. **MenuSeeder** - Navigation menus (Header, Footer, Admin, User)
4. **ReferralCampaignSeeder** - 2 referral campaigns
5. **PromotionalCampaignSeeder** - Update OffersSeeder or create new
6. **LuckyDrawSeeder** - 1 monthly lucky draw config
7. **KbEnhancementSeeder** - More KB categories and articles
8. **AdminLedgerSeeder** - Genesis entries for admin accounting

**Enhanced Existing Seeders:**
- **UserSeeder** - Add 4 more test users (total 7)
- **FeatureFlagSeeder** - Ensure all 20 flags exist

### Option 2: Use My New Seeders Selectively
**Keep these (after fixing):**
- FoundationSeeder → BUT extract only Settings portion (rest is duplicate)
- CommunicationCampaignsSeeder → For campaigns/lucky draws

**Replace/Remove:**
- IdentityAccessSeeder → Use existing UserSeeder
- CompaniesProductsSeeder → Create simpler CompanySeeder
- InvestmentPlansSeeder → Use existing PlanSeeder
- UserInvestmentsSeeder → Use existing TestDataSetSeeder

---

## Action Plan

### Phase 1: Fix Critical Gaps (High Priority)
1. Create **SectorSeeder** ✨ NEW
2. Create **CompanySeeder** ✨ NEW (must match actual schema)
3. Create **MenuSeeder** ✨ NEW
4. Enhance **UserSeeder** to add more test users

### Phase 2: Campaign & Engagement Features
5. Create **ReferralCampaignSeeder** ✨ NEW
6. Update/Create **CampaignSeeder** (promotional campaigns)
7. Create **LuckyDrawSeeder** ✨ NEW

### Phase 3: Nice-to-Have
8. Enhance **KbSeeder** with more content
9. Create **AdminLedgerSeeder** for accounting genesis

---

## Implementation Notes

### Critical Schema Rules
1. **Products** - NO company_id! Products are standalone.
2. **BulkPurchases** - Use VALUE-BASED fields (face_value_purchased, value_remaining)
3. **Companies** - Required fields: name, slug, sector (string), employees_count
4. **FeatureFlags** - Required fields: key, name, is_enabled, is_active

### Seeder Best Practices
- Use `updateOrCreate()` for idempotency
- Check existence before creating
- Use transactions for atomicity
- Environment-aware (local/staging/production)
- Follow existing naming conventions

---

## File Structure

```
database/seeders/
├── DatabaseSeeder.php (orchestrator - UPDATE)
├── [Existing Seeders - KEEP]
│   ├── RolesAndPermissionsSeeder.php
│   ├── SettingsSeeder.php
│   ├── UserSeeder.php (ENHANCE)
│   ├── ProductSeeder.php
│   ├── PlanSeeder.php
│   ├── FeatureFlagSeeder.php (VERIFY)
│   ├── EmailTemplateSeeder.php
│   ├── SmsTemplateSeeder.php
│   └── ... (others)
│
└── [New Focused Seeders - CREATE]
    ├── SectorSeeder.php ✨
    ├── CompanySeeder.php ✨
    ├── MenuSeeder.php ✨
    ├── ReferralCampaignSeeder.php ✨
    ├── CampaignSeeder.php ✨
    └── LuckyDrawSeeder.php ✨
```

---

## Testing Strategy

1. Test on fresh database:
   ```bash
   php artisan migrate:fresh
   php artisan db:seed
   ```

2. Verify critical data:
   - [ ] 3 roles exist (super-admin, admin, user)
   - [ ] 3+ admin users exist
   - [ ] 15 sectors exist
   - [ ] 5 companies exist
   - [ ] 3+ plans exist
   - [ ] 3+ products exist with bulk purchases
   - [ ] 20 feature flags exist
   - [ ] Menus exist (header, footer, admin, user)

3. Test idempotency:
   ```bash
   php artisan db:seed  # Run again - should not error
   ```

---

## Next Steps

1. ✅ Commit current fixes (FoundationSeeder, CompaniesProductsSeeder partial fixes)
2. Create SectorSeeder (15 sectors)
3. Create CompanySeeder (5 companies - match actual schema!)
4. Create MenuSeeder (4 menus with items)
5. Enhance UserSeeder (add 4 more test users)
6. Create ReferralCampaignSeeder
7. Create/Update CampaignSeeder
8. Create LuckyDrawSeeder
9. Update DatabaseSeeder orchestrator
10. Test complete seeding flow

---

**Status:** Ready to implement Phase 1 focused seeders
**Estimated effort:** 4-6 small, focused seeders vs 1 large monolithic seeder
**Risk:** Low - building on existing working foundation
