# Phase 2 Seeders Testing Guide (Post-Audit)

## Overview

This guide provides comprehensive testing instructions for the Phase 2 focused seeders created as part of the post-audit database seeding enhancement (Campaigns & Engagement).

**Phase 2 Seeders:**
1. **ReferralCampaignSeeder** - Seeds 2 referral campaigns (Standard & Premium)
2. **PromotionalCampaignSeeder** - Seeds 3 promotional campaigns
3. **LuckyDrawSeeder** - Seeds 1 monthly lucky draw configuration

## Prerequisites

Before testing Phase 2, ensure:
- ✅ Phase 1 seeders have been tested and verified
- ✅ UserSeeder has run successfully (creates admin users required for Phase 2)
- ✅ RolesAndPermissionsSeeder has run (creates roles for admin user checks)
- ✅ All migrations are up to date

## Testing Environment Setup

### Option 1: Fresh Database (Recommended for Complete Test)

```bash
cd /home/user/PreIPOsip2Gemini/backend

# Step 1: Create fresh database with all migrations
php artisan migrate:fresh

# Step 2: Run all seeders (includes Phase 1 + Phase 2)
php artisan db:seed

# Expected: No errors, all seeders execute successfully
```

### Option 2: Existing Database (Production-Safe Test)

```bash
cd /home/user/PreIPOsip2Gemini/backend

# Run seeders on existing database (idempotent - safe to run multiple times)
php artisan db:seed

# Expected: No errors, creates missing data, skips existing records
```

### Option 3: Run Individual Phase 2 Seeders (Granular Testing)

```bash
# Test each Phase 2 seeder individually
php artisan db:seed --class=ReferralCampaignSeeder
php artisan db:seed --class=PromotionalCampaignSeeder
php artisan db:seed --class=LuckyDrawSeeder
```

## Expected Results

### 1. ReferralCampaignSeeder Verification

**Expected Output:**
```
✓ Referral campaigns seeded successfully
  ✓ Referral campaigns seeded: 2 campaigns
```

**Verification Queries:**
```bash
php artisan tinker
```

```php
// Should return 2
\App\Models\ReferralCampaign::count();

// Check campaign details
\App\Models\ReferralCampaign::all()->map(function($c) {
    return [
        'name' => $c->name,
        'slug' => $c->slug,
        'bonus_amount' => $c->bonus_amount,
        'multiplier' => $c->multiplier,
        'is_active' => $c->is_active,
        'max_referrals' => $c->max_referrals ?? 'Unlimited',
    ];
})->toArray();

/* Expected output:
[
    [
        "name" => "Standard Referral Program",
        "slug" => "standard-referral",
        "bonus_amount" => "500.00",
        "multiplier" => "1.00",
        "is_active" => true,
        "max_referrals" => "Unlimited",
    ],
    [
        "name" => "Premium Referral Campaign",
        "slug" => "premium-referral",
        "bonus_amount" => "1500.00",
        "multiplier" => "1.50",
        "is_active" => true,
        "max_referrals" => 1000,
    ],
]
*/

// Verify date ranges
\App\Models\ReferralCampaign::all()->each(function($c) {
    echo "{$c->name}:\n";
    echo "  Start: {$c->start_date->format('Y-m-d')}\n";
    echo "  End: {$c->end_date->format('Y-m-d')}\n";
    echo "  Active: " . ($c->is_active ? "Yes" : "No") . "\n\n";
});
```

**Database Query:**
```sql
SELECT name, slug, bonus_amount, multiplier, is_active, max_referrals, start_date, end_date
FROM referral_campaigns
ORDER BY id;
-- Expected: 2 rows
```

### 2. PromotionalCampaignSeeder Verification

**Expected Output:**
```
✓ Promotional campaigns seeded successfully
  ✓ Promotional campaigns seeded: 3 campaigns
  ℹ  Note: Festival campaign is scheduled for future activation
```

**Verification Queries:**
```php
// Should return 3
\App\Models\Campaign::count();

// Check campaign details
\App\Models\Campaign::all()->map(function($c) {
    return [
        'title' => $c->title,
        'code' => $c->code,
        'discount_type' => $c->discount_type,
        'discount_value' => $c->discount_type === 'percentage' ? $c->discount_percent : $c->discount_amount,
        'min_investment' => $c->min_investment,
        'is_active' => $c->is_active,
        'is_featured' => $c->is_featured,
        'state' => $c->state,
    ];
})->toArray();

/* Expected output:
[
    [
        "title" => "New Year Investment Bonus 2026",
        "code" => "NEWYEAR2026",
        "discount_type" => "percentage",
        "discount_value" => "10.00",
        "min_investment" => "5000.00",
        "is_active" => true,
        "is_featured" => true,
        "state" => "live" or "expired" (depending on current date),
    ],
    [
        "title" => "First Investment Cashback",
        "code" => "FIRST500",
        "discount_type" => "fixed_amount",
        "discount_value" => "500.00",
        "min_investment" => "10000.00",
        "is_active" => true,
        "is_featured" => false,
        "state" => "live",
    ],
    [
        "title" => "Festival Bonus Campaign 2026",
        "code" => "FESTIVAL2026",
        "discount_type" => "percentage",
        "discount_value" => "5.00",
        "min_investment" => "5000.00",
        "is_active" => false,
        "is_featured" => false,
        "state" => "scheduled" or "paused",
    ],
]
*/

// Check features and terms (JSON arrays)
$campaign = \App\Models\Campaign::where('code', 'NEWYEAR2026')->first();
$campaign->features; // Should return array with 5 items
$campaign->terms; // Should return array with 7 items

// Verify admin relationships
\App\Models\Campaign::with(['creator', 'approver'])->get()->each(function($c) {
    echo "{$c->title}:\n";
    echo "  Created by: {$c->creator->email}\n";
    echo "  Approved by: {$c->approver->email}\n";
    echo "  Approved at: {$c->approved_at->format('Y-m-d H:i:s')}\n\n";
});

// Test scopes
\App\Models\Campaign::active()->count(); // Should return 2 (NEWYEAR2026, FIRST500)
\App\Models\Campaign::featured()->count(); // Should return 1 (NEWYEAR2026)
\App\Models\Campaign::approved()->count(); // Should return 3 (all approved)
\App\Models\Campaign::scheduled()->count(); // Should return 1 (FESTIVAL2026)
```

**Database Query:**
```sql
-- Check all campaigns
SELECT title, code, discount_type, discount_percent, discount_amount,
       min_investment, usage_limit, is_active, is_featured,
       start_at, end_at, approved_at
FROM campaigns
ORDER BY id;
-- Expected: 3 rows (or 4 if CampaignBootstrapSeeder also ran)

-- Check campaign features and terms
SELECT code, JSON_LENGTH(features) as feature_count, JSON_LENGTH(terms) as term_count
FROM campaigns;
-- Expected: NEWYEAR2026 (5 features, 7 terms), FIRST500 (5, 7), FESTIVAL2026 (5, 8)

-- Check approval workflow
SELECT c.title, u1.email as creator, u2.email as approver, c.approved_at
FROM campaigns c
LEFT JOIN users u1 ON c.created_by = u1.id
LEFT JOIN users u2 ON c.approved_by = u2.id;
-- Expected: All campaigns should have creator and approver (admin users)
```

### 3. LuckyDrawSeeder Verification

**Expected Output:**
```
✓ Lucky draws seeded successfully
  ✓ Lucky draws seeded: 1 draw
  ℹ  Prize Pool: ₹75,000 (1 × ₹25k + 1 × ₹15k + 1 × ₹10k + 10 × ₹2.5k)
  ℹ  Entry Rules: 1 entry per ₹5,000 invested + bonuses for streaks
```

**Verification Queries:**
```php
// Should return 1
\App\Models\LuckyDraw::count();

// Check lucky draw details
$draw = \App\Models\LuckyDraw::first();
[
    'name' => $draw->name,
    'draw_date' => $draw->draw_date->format('Y-m-d'),
    'status' => $draw->status,
    'frequency' => $draw->frequency,
    'result_visibility' => $draw->result_visibility,
];

/* Expected output:
[
    "name" => "Monthly Lucky Draw - January 2026",
    "draw_date" => "2026-02-03" (or similar - 3 days after month end),
    "status" => "open",
    "frequency" => "monthly",
    "result_visibility" => "public",
]
*/

// Check prize structure (JSON array)
$draw->prize_structure;
/* Expected:
[
    ["rank" => 1, "amount" => 25000, "count" => 1, "description" => "First Prize - ₹25,000"],
    ["rank" => 2, "amount" => 15000, "count" => 1, "description" => "Second Prize - ₹15,000"],
    ["rank" => 3, "amount" => 10000, "count" => 1, "description" => "Third Prize - ₹10,000"],
    ["rank" => 4, "amount" => 2500, "count" => 10, "description" => "Consolation Prize - ₹2,500 each"],
]
*/

// Verify total prize pool calculation
$totalPrizePool = 0;
foreach ($draw->prize_structure as $tier) {
    $totalPrizePool += $tier['amount'] * $tier['count'];
}
echo "Total Prize Pool: ₹" . number_format($totalPrizePool) . "\n";
// Expected: Total Prize Pool: ₹75,000

// Check entry rules (JSON array)
$draw->entry_rules;
/* Expected:
[
    "min_investment" => 5000,
    "min_active_months" => 1,
    "entries_per_5k" => 1,
    "ontime_payment_bonus" => 1,
    "streak_months_required" => 6,
    "streak_bonus_entries" => 5,
    "max_entries_per_user" => 50,
]
*/

// Check draw metadata
$draw->draw_metadata;
/* Expected:
[
    "total_prize_pool" => 75000,
    "expected_participants" => 500,
    "draw_method" => "random_weighted",
    "notes" => "Monthly recurring draw for active investors",
]
*/

// Verify admin creator
$draw->creator->email; // Should return admin email (e.g., 'superadmin@preiposip.com')
```

**Database Query:**
```sql
-- Check lucky draw
SELECT name, draw_date, status, frequency, result_visibility,
       created_by, executed_by, created_at
FROM lucky_draws;
-- Expected: 1 row

-- Check prize structure
SELECT name,
       JSON_LENGTH(prize_structure) as prize_tiers,
       JSON_EXTRACT(prize_structure, '$[0].amount') as first_prize,
       JSON_EXTRACT(prize_structure, '$[3].count') as consolation_count
FROM lucky_draws;
-- Expected: 4 prize tiers, first_prize = 25000, consolation_count = 10

-- Check entry rules
SELECT name,
       JSON_EXTRACT(entry_rules, '$.min_investment') as min_investment,
       JSON_EXTRACT(entry_rules, '$.entries_per_5k') as entries_per_5k,
       JSON_EXTRACT(entry_rules, '$.max_entries_per_user') as max_entries
FROM lucky_draws;
-- Expected: min_investment = 5000, entries_per_5k = 1, max_entries = 50
```

## Complete System Verification

After running all Phase 2 seeders, verify the complete seeding worked:

```php
// Total counts check
[
    'referral_campaigns' => \App\Models\ReferralCampaign::count(),  // Expected: 2
    'campaigns' => \App\Models\Campaign::count(),                    // Expected: 3 (or 4 with CampaignBootstrapSeeder)
    'lucky_draws' => \App\Models\LuckyDraw::count(),                // Expected: 1
]

// Active campaigns check
[
    'active_referral_campaigns' => \App\Models\ReferralCampaign::where('is_active', true)->count(), // Expected: 2
    'active_promotional_campaigns' => \App\Models\Campaign::active()->count(), // Expected: 2
    'open_lucky_draws' => \App\Models\LuckyDraw::where('status', 'open')->count(), // Expected: 1
]
```

## Idempotency Test

Phase 2 seeders are designed to be idempotent (safe to run multiple times). Test this:

```bash
# Run seeders twice
php artisan db:seed --class=ReferralCampaignSeeder
php artisan db:seed --class=ReferralCampaignSeeder

php artisan db:seed --class=PromotionalCampaignSeeder
php artisan db:seed --class=PromotionalCampaignSeeder

php artisan db:seed --class=LuckyDrawSeeder
php artisan db:seed --class=LuckyDrawSeeder

# Expected: No errors, counts should remain the same
```

```php
// Verify no duplicates were created
\App\Models\ReferralCampaign::count(); // Should still be 2, not 4
\App\Models\Campaign::count(); // Should still be 3, not 6
\App\Models\LuckyDraw::count(); // Should still be 1, not 2
```

## Common Issues & Troubleshooting

### Issue 1: "No admin user found. Skipping..."

**Cause:** UserSeeder hasn't run yet, or no admin users exist

**Solution:**
```bash
# Run UserSeeder first
php artisan db:seed --class=UserSeeder

# Then run Phase 2 seeders
php artisan db:seed --class=PromotionalCampaignSeeder
```

### Issue 2: "SQLSTATE[23000]: Integrity constraint violation - Duplicate entry for key 'campaigns.code'"

**Cause:** Campaign with same code already exists (e.g., from CampaignBootstrapSeeder)

**Solution:**
- This is expected behavior - the seeder uses updateOrCreate and will update existing campaigns
- If you want fresh campaigns, delete existing ones first:
```sql
DELETE FROM campaigns WHERE code IN ('NEWYEAR2026', 'FIRST500', 'FESTIVAL2026');
```

### Issue 3: "Prize pool must be positive"

**Cause:** LuckyDraw model validation failing on prize_structure

**Solution:**
- Check that prize_structure array has valid amount and count values
- Ensure no negative amounts or zero counts
```php
// Verify prize structure
$draw = \App\Models\LuckyDraw::first();
foreach ($draw->prize_structure as $tier) {
    if ($tier['amount'] <= 0 || $tier['count'] <= 0) {
        echo "Invalid tier: " . json_encode($tier) . "\n";
    }
}
```

### Issue 4: "Class 'App\Models\ReferralCampaign' not found"

**Cause:** Model doesn't exist or namespace issue

**Solution:**
```bash
# Check if model exists
ls -la app/Models/ReferralCampaign.php

# If missing, it should be created - check migrations have run
php artisan migrate:status
```

### Issue 5: JSON fields not casting properly

**Cause:** Model casts not configured correctly

**Solution:**
```php
// Check model casts
$campaign = \App\Models\Campaign::first();
var_dump($campaign->features); // Should be array, not string

// If it's a string, model casts may be missing
// Verify in app/Models/Campaign.php:
// protected $casts = ['features' => 'array', 'terms' => 'array'];
```

## Rollback Instructions

If testing reveals issues and you need to rollback:

### Option 1: Rollback Everything (Fresh Start)

```bash
php artisan migrate:fresh
# Database is now empty, re-run migrations without seeders
php artisan migrate
```

### Option 2: Rollback Only Phase 2 Data

```sql
-- Manually delete Phase 2 seeded data (production-safe)
DELETE FROM lucky_draws WHERE name = 'Monthly Lucky Draw - January 2026';

DELETE FROM campaigns WHERE code IN ('NEWYEAR2026', 'FIRST500', 'FESTIVAL2026');

DELETE FROM referral_campaigns WHERE slug IN ('standard-referral', 'premium-referral');
```

## Testing Campaign Business Logic

### Test Promotional Campaign Discount Calculation

```php
$campaign = \App\Models\Campaign::where('code', 'NEWYEAR2026')->first();

// Test percentage discount
$investmentAmount = 50000;
$discount = ($campaign->discount_percent / 100) * $investmentAmount;
$cappedDiscount = min($discount, $campaign->max_discount);

echo "Investment: ₹{$investmentAmount}\n";
echo "Discount (10%): ₹{$discount}\n";
echo "Capped Discount: ₹{$cappedDiscount}\n";
// Expected: Investment: ₹50,000, Discount: ₹5,000, Capped: ₹2,500

// Test fixed amount discount
$campaign2 = \App\Models\Campaign::where('code', 'FIRST500')->first();
echo "Fixed Discount: ₹{$campaign2->discount_amount}\n";
// Expected: ₹500
```

### Test Referral Campaign Bonus Calculation

```php
$campaign = \App\Models\ReferralCampaign::where('slug', 'premium-referral')->first();

$baseBonus = $campaign->bonus_amount;
$multipliedBonus = $baseBonus * $campaign->multiplier;

echo "Base Bonus: ₹{$baseBonus}\n";
echo "Multiplier: {$campaign->multiplier}x\n";
echo "Final Bonus: ₹{$multipliedBonus}\n";
// Expected: Base: ₹1,500, Multiplier: 1.5x, Final: ₹2,250
```

### Test Lucky Draw Entry Calculation

```php
$draw = \App\Models\LuckyDraw::first();
$rules = $draw->entry_rules;

$investmentAmount = 50000;
$hasStreak = true;
$hasOntimePayment = true;

$baseEntries = floor($investmentAmount / 5000) * $rules['entries_per_5k'];
$streakBonus = $hasStreak ? $rules['streak_bonus_entries'] : 0;
$ontimeBonus = $hasOntimePayment ? $rules['ontime_payment_bonus'] : 0;

$totalEntries = min($baseEntries + $streakBonus + $ontimeBonus, $rules['max_entries_per_user']);

echo "Investment: ₹{$investmentAmount}\n";
echo "Base Entries: {$baseEntries}\n";
echo "Streak Bonus: {$streakBonus}\n";
echo "Ontime Bonus: {$ontimeBonus}\n";
echo "Total Entries (capped): {$totalEntries}\n";
// Expected: Investment: ₹50,000, Base: 10, Streak: 5, Ontime: 1, Total: 16
```

## Success Criteria

Phase 2 seeding is successful if:

- ✅ All seeders execute without errors
- ✅ 2 referral campaigns exist with correct bonus amounts and multipliers
- ✅ 3 promotional campaigns exist with correct discount types and values
- ✅ 1 lucky draw exists with 4 prize tiers totaling ₹75,000
- ✅ All campaigns have valid admin user references (created_by, approved_by)
- ✅ JSON fields (features, terms, prize_structure, entry_rules) are properly cast to arrays
- ✅ Running seeders twice doesn't create duplicates
- ✅ No foreign key constraint violations
- ✅ Campaign states are correctly calculated (live, scheduled, expired, etc.)

## Next Steps After Successful Testing

Once Phase 2 testing is complete and successful:

1. ✅ Mark Phase 2 as production-ready
2. 📝 Document any issues found and fixes applied
3. 🔄 Run full seeding test (Phase 1 + Phase 2 together)
4. 📊 Verify complete system seeding with all modules

## Full System Test (Phase 1 + Phase 2)

To verify the complete seeding system:

```bash
# Fresh database test
php artisan migrate:fresh
php artisan db:seed

# Verify all counts
php artisan tinker
```

```php
[
    // Phase 1
    'sectors' => \App\Models\Sector::count(),        // 15
    'companies' => \App\Models\Company::count(),     // 5
    'menus' => \App\Models\Menu::count(),            // 4
    'menu_items' => \App\Models\MenuItem::count(),   // 30
    'users' => \App\Models\User::count(),            // ≥ 9

    // Phase 2
    'referral_campaigns' => \App\Models\ReferralCampaign::count(), // 2
    'campaigns' => \App\Models\Campaign::count(),                   // 3-4
    'lucky_draws' => \App\Models\LuckyDraw::count(),               // 1

    // Existing seeders
    'roles' => \Spatie\Permission\Models\Role::count(),  // ≥ 3
    'plans' => \App\Models\Plan::count(),                // ≥ 3
    'products' => \App\Models\Product::count(),          // ≥ 3
]
```

## Testing Checklist

Use this checklist when testing:

- [ ] Fresh database migration completed
- [ ] All seeders run without errors
- [ ] ReferralCampaignSeeder: 2 campaigns verified
- [ ] PromotionalCampaignSeeder: 3 campaigns verified with admin references
- [ ] LuckyDrawSeeder: 1 draw verified with ₹75,000 prize pool
- [ ] JSON fields properly cast to arrays
- [ ] Campaign business logic tested (discounts, bonuses, entries)
- [ ] Idempotency test passed (no duplicates on second run)
- [ ] Foreign key relationships verified
- [ ] No SQL errors in logs
- [ ] Database integrity maintained

---

**Status:** Phase 2 Testing Guide v1.0
**Last Updated:** 2026-01-06
**Related Files:**
- `database/seeders/ReferralCampaignSeeder.php`
- `database/seeders/PromotionalCampaignSeeder.php`
- `database/seeders/LuckyDrawSeeder.php`
- `database/seeders/DatabaseSeeder.php`
