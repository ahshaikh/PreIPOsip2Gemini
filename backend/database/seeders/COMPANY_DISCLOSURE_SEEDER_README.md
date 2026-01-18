# Company Disclosure System Seeder Documentation

## Overview

The `CompanyDisclosureSystemSeeder` is a comprehensive, production-ready database seeder that creates a complete end-to-end Company Disclosure & Investment ecosystem. It generates realistic, schema-aware test data covering all workflows and UI states.

## What Gets Seeded

### 1. Companies (5 Distinct States)

| Company | State | Description | Use Case |
|---------|-------|-------------|----------|
| **NexGen AI Solutions** | Draft | Company filling out initial disclosures, not yet submitted | Test draft workflow, partial data entry |
| **MediCare Plus HealthTech** | Live - Limited | Tier 1 disclosures approved (business model, board management) | Visible to investors but NOT investable (no financial data) |
| **FinSecure Digital Lending** | Live - Investable | Tier 1 + Tier 2 approved (including financial performance) | **BUYING ENABLED** - Complete investment flow testing |
| **EduVerse Learning Platform** | Live - Fully Disclosed | All tiers approved (business, financials, risks, legal, governance) | Premium listing with complete disclosure package |
| **GreenPower Energy Solutions** | Suspended | Previously approved, now frozen for compliance issues | Test suspension workflow and frozen state |

### 2. Disclosure Modules (Per Company)

Each company has different levels of disclosure completeness:

#### Draft Company (NexGen AI)
- ✓ Business Model: 65% complete, draft status
- ✗ Other modules: Not started

#### Live-Limited Company (MediCare Plus)
- ✓ Business Model: Version 1, approved, immutable snapshot created
- ✓ Board & Management: Version 1, approved, immutable snapshot created
- ⏳ Financial Performance: Submitted, under admin review (has clarifications)
- ✗ Risk Factors: Not started
- ✗ Legal & Compliance: Not started

#### Live-Investable Company (FinSecure)
- ✓ Business Model: **Version 2**, approved (demonstrates version updates)
- ✓ Financial Performance: Version 1, approved (**TIER 2 - ENABLES BUYING**)
- ✓ Risk Factors: Version 1, approved
- ⏳ Legal & Compliance: Not seeded (for brevity, can be added)

### 3. Clarification Cycles

**Company:** MediCare Plus
**Module:** Financial Performance (under review)

#### Clarification Thread Example:

**Q1** (Admin → Company): Revenue Growth Rate Clarification
- **Type:** verification
- **Priority:** high
- **Blocking:** Yes
- **Field:** `disclosure_data.revenue.breakdown[3].amount`
- **Question:** Q4 revenue jumped 13.6% QoQ vs 5-10% in Q1-Q3. Explain drivers.

**A1** (Company → Admin):
- Detailed 3-factor explanation with supporting documents
- Attachments: Corporate contracts, seasonal trends, government contract

**Q2** (Admin → Company): Follow-up on Corporate Revenue Sustainability
- **Thread Depth:** 1 (reply to Q1)
- **Type:** insufficient_detail
- **Question:** Clarify one-time vs recurring revenue split

**A2** (Company → Admin):
- Breakdown: 25% one-time, 75% recurring
- Projected monthly recurring revenue: ₹45 Lakhs

### 4. Platform Context & Scoring

Each company receives platform-generated metrics (read-only for companies):

| Company | Completeness | Financial Health | Governance Quality | Risk Intensity | Disclosed Risks |
|---------|-------------|------------------|-------------------|----------------|-----------------|
| NexGen AI | 35% | Insufficient Data | Insufficient Data | Insufficient Data | 0 |
| MediCare Plus | 60% | Moderate | Standard | Moderate | 0 |
| FinSecure | 85% | Healthy | Strong | Moderate | 7 (2 critical) |
| EduVerse | 100% | Strong | Exemplary | Low | 12 (1 critical) |
| GreenPower | 80% | Moderate | Standard | High | 8 (3 critical) |

**Platform Risk Flags:**
- FinSecure: "High NPA Concentration" (45% in manufacturing sector)
- GreenPower: (Can be extended with additional flags)

### 5. Investors & Risk Acknowledgements

**Investors Created:** 3 test investors
- investor1@example.com
- investor2@example.com
- investor3@example.com

**All have:**
- Verified KYC status
- Wallets created
- Password: `password`

**Risk Acknowledgements (for FinSecure only):**
Each investor has acknowledged all 4 risk types:
1. Illiquidity risk
2. No guarantee risk
3. Platform non-advisory risk
4. Material changes risk

### 6. Immutable Version Snapshots

**Purpose:** Regulatory compliance and investor protection

**Examples:**
- **MediCare Plus Business Model v1:** 47 investor views, locked at approval
- **FinSecure Business Model v2:** 156 investor views, shows version evolution
- **FinSecure Financials v1:** 203 investor views, audited by Big 4 firm

**Immutability Features:**
- SHA-256 hash for tamper detection
- `is_locked = true` flag
- Approval notes from admin
- Investor view tracking
- Document attachment snapshots

## How to Run

### Prerequisites

1. **Run dependency seeders first:**
   ```bash
   php artisan db:seed --class=DisclosureModuleSeeder
   php artisan db:seed --class=SectorSeeder
   php artisan db:seed --class=RolesAndPermissionsSeeder
   ```

2. **Ensure models exist:**
   - All disclosure-related models
   - Platform context models
   - User, Company, Sector models

### Execution

```bash
# Fresh database + full system seed
php artisan migrate:fresh
php artisan db:seed --class=DisclosureModuleSeeder
php artisan db:seed --class=SectorSeeder
php artisan db:seed --class=RolesAndPermissionsSeeder
php artisan db:seed --class=CompanyDisclosureSystemSeeder

# Or run standalone (if dependencies already seeded)
php artisan db:seed --class=CompanyDisclosureSystemSeeder
```

### Expected Output

```
🚀 Starting Company Disclosure System Seeder...
📦 Phase 1: Seeding Companies...
  ✓ Created: NexGen AI Solutions [draft]
  ✓ Created: MediCare Plus HealthTech [live_limited]
  ✓ Created: FinSecure Digital Lending [live_investable]
  ✓ Created: EduVerse Learning Platform [live_full]
  ✓ Created: GreenPower Energy Solutions [suspended]
👥 Phase 2: Seeding Company Users...
  ✓ Created company user for: NexGen AI Solutions
  ...
📋 Phase 3: Seeding Company Disclosures...
  ✓ Created draft disclosure for: NexGen AI Solutions
  ✓ Created live-limited disclosures for: MediCare Plus HealthTech
  ✓ Created live-investable disclosures for: FinSecure Digital Lending
  ...
💬 Phase 4: Seeding Clarification Cycles...
  ✓ Created clarification cycle for: MediCare Plus HealthTech
📊 Phase 5: Seeding Platform Context & Scores...
  ✓ Created platform context for: NexGen AI Solutions
  ...
👨‍💼 Phase 6: Seeding Investors & Risk Acknowledgements...
  ✓ Created 3 investors with risk acknowledgements
💰 Phase 7: Seeding Transactions & Snapshots...
  ✓ Skipping transaction/snapshot seeding (requires deals and subscriptions setup)
✅ Company Disclosure System Seeder completed successfully!
```

## Testing Scenarios

### 1. Draft Workflow
**Company:** NexGen AI Solutions
**Test:** Company filling out initial disclosures
- Navigate to company portal → disclosures
- Edit business model disclosure (65% complete)
- Submit for admin review
- Verify status changes to "submitted"

### 2. Admin Review & Clarifications
**Company:** MediCare Plus HealthTech
**Test:** Admin-Company Q&A cycle
- Admin dashboard → Disclosure Queue
- Open financial disclosure (under review)
- View existing clarifications (2 rounds)
- Add new clarification or approve disclosure

### 3. Investment Flow (BUYING ENABLED)
**Company:** FinSecure Digital Lending
**Test:** Complete investor purchase journey
1. Log in as investor1@example.com
2. Browse company → View all disclosures (business, financials, risks)
3. Click "Invest" button (enabled because Tier 2 approved)
4. Complete risk acknowledgements (should auto-fill as already acknowledged)
5. Create investment → Verify snapshot is captured
6. View immutable snapshot showing exact data at purchase time

### 4. Version Management
**Company:** FinSecure Digital Lending
**Test:** Disclosure versioning and change tracking
- View business model disclosure
- Notice "Version 2" indicator
- Click version history
- Compare v1 vs v2
- Verify changes_summary shows updated fields
- Verify investor view counts (v2 has 156 views)

### 5. Platform Context Display
**Company:** Any
**Test:** Platform-generated metrics visibility
- Investor view: See completeness score, health bands, risk flags
- Company view: See same data but cannot edit (read-only)
- Admin view: See calculation methodology, can recalculate

### 6. Suspended Company Handling
**Company:** GreenPower Energy Solutions
**Test:** Frozen state enforcement
- Try to edit disclosures → Should be blocked (frozen_at is set)
- Investor view → Should show "Trading Suspended" banner
- Admin view → Can view freeze reason and potentially unfreeze

## Data Quality Standards

### ✅ Realistic Data
- No "foo", "bar", or "lorem ipsum"
- All companies have actual business descriptions
- Financial numbers are internally consistent (quarters sum to annual)
- Revenue percentages total 100%
- Realistic Indian company details (CIN, PAN, addresses)

### ✅ Schema Compliance
- All required fields populated
- Nullable fields appropriately used
- Foreign keys properly set
- Enum values match migration definitions
- JSON data conforms to module schemas

### ✅ Immutability Enforcement
- Version records have `is_locked = true`
- SHA-256 hashes generated
- Timestamps preserved
- No soft deletes on versions (permanent retention)

### ✅ Audit Trail
- All changes tracked with timestamps
- User IDs captured (admin, company users)
- IP addresses and user agents logged (where applicable)
- Change reasons provided for version updates

## Customization & Extension

### Add More Companies

```php
// In seedCompanies() method, add to $companiesData array:
[
    'name' => 'Your Company Name',
    'slug' => 'your-company-slug',
    'description' => '...',
    // ... full company details
    'disclosure_stage' => 'draft', // or 'approved', 'suspended'
    'state_key' => 'your_state_key',
]
```

### Seed Additional Modules

```php
// In seedDisclosuresForLiveFullCompany() method:
$legalModule = $this->modules->get('legal_compliance');
if ($legalModule) {
    $disclosure = CompanyDisclosure::create([
        'company_id' => $company->id,
        'disclosure_module_id' => $legalModule->id,
        'disclosure_data' => [
            // Legal & compliance data
        ],
        'status' => 'approved',
        // ... other fields
    ]);

    // Create version snapshot
    DisclosureVersion::create([
        // ... version data
    ]);
}
```

### Enable Transaction Seeding

Uncomment and implement in `seedTransactionsAndSnapshots()`:

```php
// 1. Create deals
$deal = Deal::create([
    'company_id' => $investableCompany->id,
    'product_id' => $product->id,
    'title' => 'FinSecure Series D Round',
    'deal_type' => 'live',
    'min_investment' => 5000000, // ₹50K
    // ...
]);

// 2. Create subscriptions for investors
$subscription = Subscription::create([
    'user_id' => $investor->id,
    'plan_id' => $plan->id,
    // ...
]);

// 3. Create investment
$investment = Investment::create([
    'user_id' => $investor->id,
    'company_id' => $investableCompany->id,
    'deal_id' => $deal->id,
    'subscription_id' => $subscription->id,
    'shares_allocated' => 100,
    'price_per_share' => 500000, // ₹5000 per share
    'total_amount' => 50000000, // ₹5L
    'status' => 'active',
    'invested_at' => now(),
]);

// 4. Create IMMUTABLE snapshot
$snapshot = InvestmentDisclosureSnapshot::create([
    'investment_id' => $investment->id,
    'user_id' => $investor->id,
    'company_id' => $investableCompany->id,
    'snapshot_timestamp' => now(),
    'snapshot_trigger' => 'investment_purchase',
    'disclosure_snapshot' => [
        // Full snapshot of all disclosures investor saw
    ],
    'metrics_snapshot' => [
        // Platform metrics at purchase time
    ],
    'risk_flags_snapshot' => [
        // Active risk flags at purchase time
    ],
    'is_immutable' => true,
    'locked_at' => now(),
]);
```

## Troubleshooting

### Error: "No sectors found"
**Solution:** Run `SectorSeeder` before this seeder.

### Error: "No disclosure modules found"
**Solution:** Run `DisclosureModuleSeeder` before this seeder.

### Error: "Class 'DisclosureModule' not found"
**Solution:** Ensure all models are created in `app/Models/`.

### Error: Foreign key constraint fails
**Solution:** Check migration execution order. Platform context tables depend on companies and disclosure tables.

### Warning: Duplicate slug
**Solution:** Seeder is idempotent. Uses `updateOrCreate` to handle re-runs safely.

## Maintenance Notes

### Idempotency
The seeder uses `updateOrCreate` extensively, making it safe to re-run without creating duplicates.

### Performance
- Uses DB transactions for atomic execution
- Seeding 5 companies + full workflow takes ~5-10 seconds
- Can be optimized with batch inserts if needed

### Production Use
**⚠️ WARNING:** This is a TEST data seeder.
**DO NOT** run on production databases without:
1. Reviewing all seeded data
2. Changing default passwords
3. Adjusting company details to match real entities
4. Ensuring regulatory compliance

## Support & Contribution

For questions or enhancements:
1. Read this documentation thoroughly
2. Check migration files for schema details
3. Review `DisclosureModuleSeeder` for module definitions
4. Consult project's governance protocol documentation

---

**Version:** 1.0.0
**Last Updated:** January 2026
**Author:** Phase 6 Implementation Team
