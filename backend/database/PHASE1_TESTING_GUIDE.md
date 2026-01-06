# Phase 1 Seeders Testing Guide (Post-Audit)

## Overview

This guide provides comprehensive testing instructions for the Phase 1 focused seeders created as part of the post-audit database seeding enhancement.

**Phase 1 Seeders:**
1. **SectorSeeder** - Seeds 15 industry sectors
2. **CompanySeeder** - Seeds 5 sample Pre-IPO companies
3. **MenuSeeder** - Seeds 4 navigation menus (Header, Footer, User, Admin)
4. **EnhancedUserSeeder** - Adds 4 test users + 2 company representatives

## Prerequisites

Before testing, ensure:
- ✅ Laravel environment is properly configured (.env file exists)
- ✅ Database connection is working
- ✅ All migrations are available in `database/migrations/`
- ✅ Existing seeders are not modified (we're adding, not replacing)

## Testing Environment Setup

### Option 1: Fresh Database (Recommended for Initial Test)

```bash
cd /home/user/PreIPOsip2Gemini/backend

# Step 1: Create fresh database with all migrations
php artisan migrate:fresh

# Step 2: Run all seeders (including Phase 1)
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

### Option 3: Run Individual Seeders (Granular Testing)

```bash
# Test each Phase 1 seeder individually
php artisan db:seed --class=SectorSeeder
php artisan db:seed --class=CompanySeeder
php artisan db:seed --class=MenuSeeder
php artisan db:seed --class=EnhancedUserSeeder
```

## Expected Results

### 1. SectorSeeder Verification

**Expected Output:**
```
✓ 15 industry sectors seeded successfully
```

**Verification Queries:**
```bash
php artisan tinker
```

```php
// Should return 15
\App\Models\Sector::count();

// Should show all 15 sectors
\App\Models\Sector::pluck('name', 'slug')->toArray();

/* Expected output:
[
    "technology" => "Technology",
    "healthcare" => "Healthcare",
    "finance" => "Finance",
    "e-commerce" => "E-Commerce",
    "saas" => "SaaS",
    "fintech" => "FinTech",
    "edtech" => "EdTech",
    "logistics" => "Logistics",
    "manufacturing" => "Manufacturing",
    "renewable-energy" => "Renewable Energy",
    "real-estate" => "Real Estate",
    "agriculture" => "Agriculture",
    "biotech" => "Biotech",
    "consumer-goods" => "Consumer Goods",
    "media-entertainment" => "Media & Entertainment",
]
*/

// Check a specific sector
\App\Models\Sector::where('slug', 'technology')->first();
```

**Database Query:**
```sql
SELECT * FROM sectors ORDER BY id;
-- Expected: 15 rows
```

### 2. CompanySeeder Verification

**Expected Output:**
```
✓ 5 sample Pre-IPO companies seeded successfully
```

**Verification Queries:**
```php
// Should return 5
\App\Models\Company::count();

// Check company details
\App\Models\Company::with('sector')->get()->map(function($c) {
    return [
        'name' => $c->name,
        'sector' => $c->sector,
        'sector_relation' => $c->sectorRelation->name ?? 'N/A',
        'founded_year' => $c->founded_year,
        'employees_count' => $c->employees_count,
    ];
})->toArray();

/* Expected companies:
1. TechCorp India (Technology, 2018, 250 employees)
2. HealthTech Solutions (Healthcare, 2019, 150 employees)
3. FinServe Pro (FinTech, 2017, 300 employees)
4. GreenEnergy Innovations (Renewable Energy, 2020, 200 employees)
5. EduLearn Platform (EdTech, 2021, 100 employees)
*/

// Verify sector relationships work
\App\Models\Company::where('slug', 'techcorp-india')->first()->sectorRelation;
```

**Database Query:**
```sql
SELECT c.name, c.sector, c.founded_year, c.employees_count, c.status, s.name as sector_name
FROM companies c
LEFT JOIN sectors s ON c.sector_id = s.id
ORDER BY c.id;
-- Expected: 5 rows with correct sector relationships
```

### 3. MenuSeeder Verification

**Expected Output:**
```
✓ Header Menu seeded successfully (5 items)
✓ Footer Menu seeded successfully (12 items across 3 columns)
✓ User Dashboard Menu seeded successfully (6 items)
✓ Admin Panel Menu seeded successfully (7 items)
```

**Verification Queries:**
```php
// Should return 4 menus
\App\Models\Menu::count();

// Check menu details
\App\Models\Menu::pluck('name', 'location')->toArray();
/* Expected:
[
    "header" => "Header Menu",
    "footer" => "Footer Menu",
    "user_dashboard" => "User Dashboard Menu",
    "admin_panel" => "Admin Panel Menu",
]
*/

// Check Header Menu items (should have 5 items)
$headerMenu = \App\Models\Menu::where('location', 'header')->first();
$headerMenu->items()->count(); // Should be 5
$headerMenu->items()->orderBy('order')->pluck('title', 'url')->toArray();
/* Expected:
[
    "/" => "Home",
    "/companies" => "Companies",
    "/products" => "Products",
    "/plans" => "Plans",
    "/about" => "About",
]
*/

// Check Footer Menu items (should have 12 items)
$footerMenu = \App\Models\Menu::where('location', 'footer')->first();
$footerMenu->items()->count(); // Should be 12

// Check nested structure (3 parent columns)
$footerMenu->items()->whereNull('parent_id')->count(); // Should be 3
$footerMenu->items()->whereNotNull('parent_id')->count(); // Should be 9 (3 items per column)
```

**Database Query:**
```sql
-- Check all menus
SELECT id, name, location, is_active FROM menus;
-- Expected: 4 rows

-- Check menu items count
SELECT m.name, COUNT(mi.id) as item_count
FROM menus m
LEFT JOIN menu_items mi ON m.id = mi.menu_id
GROUP BY m.id, m.name;
-- Expected: Header (5), Footer (12), User Dashboard (6), Admin Panel (7)

-- Check Footer Menu structure
SELECT
    parent.title as column_title,
    child.title as link_title,
    child.url,
    child.order
FROM menu_items parent
LEFT JOIN menu_items child ON parent.id = child.parent_id
WHERE parent.menu_id = (SELECT id FROM menus WHERE location = 'footer')
  AND parent.parent_id IS NULL
ORDER BY parent.order, child.order;
-- Expected: 3 columns with 3 items each
```

### 4. EnhancedUserSeeder Verification

**Expected Output:**
```
✓ Additional test users seeded: 4 users
✓ Company representatives seeded: 2 users
```

**Verification Queries:**
```php
// Total users (3 from UserSeeder + 4 from EnhancedUserSeeder + 2 company reps = 9)
\App\Models\User::count(); // Should be at least 9

// Check the new test users
\App\Models\User::whereIn('email', [
    'user2@preiposip.com',
    'user3@preiposip.com',
    'user4@preiposip.com',
    'user5@preiposip.com',
])->get()->map(function($u) {
    return [
        'email' => $u->email,
        'username' => $u->username,
        'has_profile' => $u->profile !== null,
        'has_kyc' => $u->kyc !== null,
        'has_wallet' => $u->wallet !== null,
        'kyc_status' => $u->kyc->status ?? 'N/A',
        'role' => $u->roles->pluck('name')->first(),
    ];
});

/* Expected:
- user2@preiposip.com: testuser2, verified KYC, role: user
- user3@preiposip.com: testuser3, verified KYC, role: user
- user4@preiposip.com: testuser4, verified KYC, role: user
- user5@preiposip.com: testuser5, pending KYC, role: user
*/

// Check company representatives
\App\Models\User::whereIn('email', [
    'company1@preiposip.com',
    'company2@preiposip.com',
])->get()->map(function($u) {
    return [
        'email' => $u->email,
        'username' => $u->username,
        'role' => $u->roles->pluck('name')->first(),
        'kyc_status' => $u->kyc->status ?? 'N/A',
    ];
});

/* Expected:
- company1@preiposip.com: companyrep1, role: company (or user if company role doesn't exist)
- company2@preiposip.com: companyrep2, role: company (or user if company role doesn't exist)
*/

// Verify all have required relations
\App\Models\User::where('email', 'LIKE', '%@preiposip.com')
    ->get()
    ->each(function($user) {
        echo "User: {$user->email}\n";
        echo "  Profile: " . ($user->profile ? "✓" : "✗") . "\n";
        echo "  KYC: " . ($user->kyc ? "✓" : "✗") . "\n";
        echo "  Wallet: " . ($user->wallet ? "✓" : "✗") . "\n";
        echo "  Role: " . ($user->roles->first()->name ?? "NONE") . "\n\n";
    });
```

**Database Query:**
```sql
-- Check all test users
SELECT
    u.email,
    u.username,
    u.status,
    up.first_name,
    up.last_name,
    uk.status as kyc_status,
    w.id as has_wallet,
    r.name as role_name
FROM users u
LEFT JOIN user_profiles up ON u.id = up.user_id
LEFT JOIN user_kycs uk ON u.id = uk.user_id
LEFT JOIN wallets w ON u.id = w.user_id
LEFT JOIN model_has_roles mr ON u.id = mr.model_id AND mr.model_type = 'App\\Models\\User'
LEFT JOIN roles r ON mr.role_id = r.id
WHERE u.email LIKE '%@preiposip.com'
ORDER BY u.id;
-- Expected: 9 rows (3 original + 4 test users + 2 company reps)
```

## Complete System Verification

After running all Phase 1 seeders, verify the complete seeding worked:

```php
// Total counts check
[
    'sectors' => \App\Models\Sector::count(),        // Expected: 15
    'companies' => \App\Models\Company::count(),     // Expected: 5
    'menus' => \App\Models\Menu::count(),            // Expected: 4
    'menu_items' => \App\Models\MenuItem::count(),   // Expected: 30 (5+12+6+7)
    'users' => \App\Models\User::count(),            // Expected: ≥ 9
    'user_profiles' => \App\Models\UserProfile::count(), // Expected: ≥ 9
    'user_kycs' => \App\Models\UserKyc::count(),     // Expected: ≥ 9
    'wallets' => \App\Models\Wallet::count(),        // Expected: ≥ 9
    'roles' => \Spatie\Permission\Models\Role::count(), // Expected: ≥ 3
]
```

## Idempotency Test

Phase 1 seeders are designed to be idempotent (safe to run multiple times). Test this:

```bash
# Run seeders twice
php artisan db:seed
php artisan db:seed

# Expected: No errors, counts should remain the same
```

```php
// Verify no duplicates were created
\App\Models\Sector::count(); // Should still be 15, not 30
\App\Models\Company::count(); // Should still be 5, not 10
\App\Models\Menu::count(); // Should still be 4, not 8
```

## Common Issues & Troubleshooting

### Issue 1: "Class 'Sector' not found"

**Cause:** Model doesn't exist or namespace issue

**Solution:**
```bash
# Check if Sector model exists
ls -la app/Models/Sector.php

# If missing, create it
php artisan make:model Sector
```

### Issue 2: "SQLSTATE[23000]: Integrity constraint violation"

**Cause:** Foreign key constraint failure (e.g., sector_id references non-existent sector)

**Solution:**
```bash
# Ensure SectorSeeder runs BEFORE CompanySeeder
# Check DatabaseSeeder.php order:
# 1. SectorSeeder
# 2. CompanySeeder (depends on sectors)
```

### Issue 3: "Field 'X' doesn't have a default value"

**Cause:** Required field missing in seeder data

**Solution:**
```bash
# Check migration to see required fields
php artisan migrate:status

# View specific migration
cat database/migrations/*_create_[table]_table.php
```

### Issue 4: Users already exist

**Cause:** EnhancedUserSeeder tries to create users that exist from previous runs

**Solution:**
- This is expected behavior - seeder skips existing users
- Verify the seeder has existence checks:
```php
if (User::where('email', $userData['email'])->exists()) {
    continue; // Skip existing
}
```

### Issue 5: "Role 'company' does not exist"

**Cause:** Company role not created in RolesAndPermissionsSeeder

**Solution:**
- EnhancedUserSeeder handles this gracefully - falls back to 'user' role
- To add company role, update RolesAndPermissionsSeeder

## Rollback Instructions

If testing reveals issues and you need to rollback:

### Option 1: Rollback Everything (Fresh Start)

```bash
php artisan migrate:fresh
# Database is now empty, re-run migrations without seeders
php artisan migrate
```

### Option 2: Rollback Only Phase 1 Data

```sql
-- Manually delete Phase 1 seeded data (production-safe)
DELETE FROM menu_items WHERE menu_id IN (SELECT id FROM menus WHERE location IN ('header', 'footer', 'user_dashboard', 'admin_panel'));
DELETE FROM menus WHERE location IN ('header', 'footer', 'user_dashboard', 'admin_panel');

DELETE FROM companies WHERE slug IN ('techcorp-india', 'healthtech-solutions', 'finserve-pro', 'greenenergy-innovations', 'edulearn-platform');

DELETE FROM sectors WHERE slug IN ('technology', 'healthcare', 'finance', 'e-commerce', 'saas', 'fintech', 'edtech', 'logistics', 'manufacturing', 'renewable-energy', 'real-estate', 'agriculture', 'biotech', 'consumer-goods', 'media-entertainment');

DELETE FROM wallets WHERE user_id IN (SELECT id FROM users WHERE email IN ('user2@preiposip.com', 'user3@preiposip.com', 'user4@preiposip.com', 'user5@preiposip.com', 'company1@preiposip.com', 'company2@preiposip.com'));

DELETE FROM user_kycs WHERE user_id IN (SELECT id FROM users WHERE email IN ('user2@preiposip.com', 'user3@preiposip.com', 'user4@preiposip.com', 'user5@preiposip.com', 'company1@preiposip.com', 'company2@preiposip.com'));

DELETE FROM user_profiles WHERE user_id IN (SELECT id FROM users WHERE email IN ('user2@preiposip.com', 'user3@preiposip.com', 'user4@preiposip.com', 'user5@preiposip.com', 'company1@preiposip.com', 'company2@preiposip.com'));

DELETE FROM model_has_roles WHERE model_id IN (SELECT id FROM users WHERE email IN ('user2@preiposip.com', 'user3@preiposip.com', 'user4@preiposip.com', 'user5@preiposip.com', 'company1@preiposip.com', 'company2@preiposip.com'));

DELETE FROM users WHERE email IN ('user2@preiposip.com', 'user3@preiposip.com', 'user4@preiposip.com', 'user5@preiposip.com', 'company1@preiposip.com', 'company2@preiposip.com');
```

## Success Criteria

Phase 1 seeding is successful if:

- ✅ All seeders execute without errors
- ✅ 15 sectors exist in database
- ✅ 5 companies exist with correct sector relationships
- ✅ 4 menus exist with correct item counts
- ✅ 9+ users exist (including new test users and company reps)
- ✅ All users have profiles, KYC records, and wallets
- ✅ Running seeders twice doesn't create duplicates
- ✅ No foreign key constraint violations

## Next Steps After Successful Testing

Once Phase 1 testing is complete and successful:

1. ✅ Mark Phase 1 as production-ready
2. 📝 Document any issues found and fixes applied
3. 🚀 Push changes to repository
4. 🔄 Proceed with Phase 2 (Campaign Seeders)

## Testing Checklist

Use this checklist when testing:

- [ ] Fresh database migration completed
- [ ] All seeders run without errors
- [ ] SectorSeeder: 15 sectors verified
- [ ] CompanySeeder: 5 companies verified with sector relationships
- [ ] MenuSeeder: 4 menus verified with correct item counts
- [ ] EnhancedUserSeeder: 4 test users + 2 company reps verified
- [ ] All users have profiles, KYC, and wallets
- [ ] Idempotency test passed (no duplicates on second run)
- [ ] Foreign key relationships verified
- [ ] No SQL errors in logs
- [ ] Database integrity maintained

---

**Status:** Phase 1 Testing Guide v1.0
**Last Updated:** 2026-01-06
**Related Files:**
- `database/seeders/SectorSeeder.php`
- `database/seeders/CompanySeeder.php`
- `database/seeders/MenuSeeder.php`
- `database/seeders/EnhancedUserSeeder.php`
- `database/seeders/DatabaseSeeder.php`
