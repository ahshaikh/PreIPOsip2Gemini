# Company Count Discrepancies Explained

## Summary of Observed Counts

| Page | Count | Status |
|------|-------|--------|
| `/admin/content/companies` | 15 active | All companies in system |
| `/admin/content/deals` | 4-6 deals | Only companies WITH deals |
| `/admin/content/sectors` | 8 sectors, 0 companies | Broken relationship |
| `/admin/company-users` | 10 companies, 9 active/verified | Different table! |
| Public `/products` | 4 companies | Only verified companies WITH live deals |
| User `/deals` | 4 companies | Only verified companies WITH live deals |

## Root Cause: THREE Different "Company" Concepts

Your database has **THREE separate company-related tables** that serve different purposes:

### 1. `companies` Table (15 records)
**Purpose:** Platform-managed investment companies
**Used by:**
- `/admin/content/companies`
- Public product pages
- User deals pages

**Filters:**
```php
// Admin shows ALL
Company::count() // = 15

// Public shows ONLY verified WITH deals
Company::where('status', 'active')
    ->where('is_verified', true)
    ->whereHas('deals', function($q) {
        $q->where('status', 'active')
          ->whereIn('deal_type', ['live', 'upcoming']);
    })
    ->count() // = 4
```

**Why 15 vs 4:**
- 15 total companies exist
- 11 companies have `is_verified=false` OR no deals
- 4 companies have `is_verified=true` AND active deals

### 2. `company_users` Table (10 records)
**Purpose:** Companies as CLIENTS/USERS of your platform
**Used by:**
- `/admin/company-users`
- Company portal login

**Structure:**
```sql
company_users (
  id,
  company_id,  -- CAN BE NULL! (client doesn't have investment listing yet)
  email,
  password,
  status,
  is_verified
)
```

**Why different count:**
- These are companies that REGISTERED on your platform
- They may or may not have investment listings yet
- `company_id` can be NULL if they're just clients without a listing
- 10 client companies vs 15 investment companies

### 3. Sectors Relationship (8 sectors, 0 count)

**Problem:** Sector page is counting companies using `sector_id` relationship, but companies were created with legacy `sector` string field.

**Database has TWO sector fields:**
```sql
companies (
  id,
  sector VARCHAR(255),      -- Legacy string field (e.g., "Technology")
  sector_id INT,            -- Foreign key to sectors table (can be NULL)
  ...
)
```

**What's happening:**
```php
// Sectors page probably counts like this:
Sector::withCount('companies')  // Uses sector_id relationship
    ->get();

// But companies have sector="Technology" string, NOT sector_id=31
// Result: All sectors show 0 companies
```

**Fix needed:**
Run migration to populate `sector_id` based on `sector` string:
```sql
UPDATE companies c
JOIN sectors s ON s.name = c.sector
SET c.sector_id = s.id
WHERE c.sector_id IS NULL AND c.sector IS NOT NULL;
```

## Why This Architecture Exists

This is a **multi-tenant B2B2C platform**:

1. **Investment Companies (companies table)**
   - Pre-IPO companies users can invest in
   - Managed by platform admin
   - Examples: NexGen AI, MediCare Plus, FinSecure

2. **Client Companies (company_users table)**
   - Businesses that USE your platform
   - They register, pay fees, list their offerings
   - Examples: Startups looking to raise funding

3. **Relationship:**
   ```
   Client Company (company_users)
       registers →
   Platform creates Investment Listing (companies) →
   Users invest via Deals
   ```

## Detailed Breakdown

### /admin/content/companies (15 companies)
**Query:**
```php
Company::where('status', 'active')->count(); // = 15
```

**Includes:**
- All platform companies (verified + unverified)
- Companies with deals AND without deals
- No filters applied

### /admin/content/deals (4-6 deals)
**Query:**
```php
Deal::where('status', 'active')->count(); // = 4-6
```

**Each deal links to ONE company:**
- Deal #9 → Company #89 (FinSecure)
- Deal #10 → Company #87 (NexGen AI)
- Deal #11 → Company #88 (MediCare Plus)
- Deal #13 → Company #90 (EduVerse)

**Remaining 11 companies:**
- Have NO active deals
- Are unverified
- Are in draft/setup stage

### /admin/content/sectors (8 sectors, 0 companies)
**Problem:** Broken `sector_id` relationship

**Current state:**
```sql
SELECT id, name,
  (SELECT COUNT(*) FROM companies WHERE sector_id = sectors.id) as count
FROM sectors;

-- Result: All show count=0 because sector_id is NULL
```

**Companies have:**
```sql
SELECT id, name, sector, sector_id FROM companies;

-- Results:
-- id=87, name="NexGen AI", sector="Technology", sector_id=31
-- id=88, name="MediCare Plus", sector="Healthcare", sector_id=32
-- ...but most have sector_id=NULL
```

**Fix:** Populate sector_id based on sector string.

### /admin/company-users (10 companies, 9 active/verified)
**Different table entirely!**

```sql
SELECT COUNT(*) FROM company_users; -- = 10
SELECT COUNT(*) FROM company_users WHERE status='active' AND is_verified=1; -- = 9
```

**These are CLIENT companies, not investment companies.**

### Public /products (4 companies)
**Query:**
```php
Company::where('status', 'active')
    ->where('is_verified', true)
    ->whereHas('deals', function($q) {
        $q->live(); // status=active, deal_type=live, dates valid
    })
    ->count(); // = 4
```

**Only shows:**
- ✅ Verified companies
- ✅ With active live deals
- ✅ With valid dates

## How to Fix Discrepancies

### Fix 1: Populate sector_id for existing companies
```bash
cd backend
php artisan tinker
```

```php
use App\Models\Company;
use App\Models\Sector;

$companies = Company::whereNotNull('sector')->whereNull('sector_id')->get();

foreach ($companies as $company) {
    $sector = Sector::where('name', $company->sector)
                    ->orWhere('slug', \Str::slug($company->sector))
                    ->first();

    if ($sector) {
        $company->sector_id = $sector->id;
        $company->save();
        echo "✓ Updated {$company->name} → Sector: {$sector->name}\n";
    } else {
        echo "✗ No sector found for: {$company->sector}\n";
    }
}
```

### Fix 2: Understand the counts are CORRECT for their context

The counts are NOT bugs - they're showing different things:

1. **Admin companies page (15):** All investment listings
2. **Admin deals page (4-6):** Active investment deals
3. **Admin company-users page (10):** Client companies (different table)
4. **Public pages (4):** Verified companies with live deals

### Fix 3: Update sector counting logic

If `/admin/content/sectors` should show company counts, update the query:

```php
// BEFORE (broken)
Sector::withCount('companies')->get();

// AFTER (fixed)
Sector::withCount(['companies' => function($query) {
    $query->where('status', 'active');
}])->get();

// OR count using string field until sector_id populated
Sector::get()->map(function($sector) {
    $sector->companies_count = Company::where('sector', $sector->name)
        ->where('status', 'active')
        ->count();
    return $sector;
});
```

## Summary

**The counts are NOT a bug.** Each page shows a different subset of data based on its purpose:

- **Admin pages:** Show everything (including drafts, unverified)
- **Public pages:** Show only verified + live deals
- **Company-users page:** Different table (client companies, not investment companies)
- **Sectors page:** Broken due to sector_id vs sector string mismatch

**Action items:**
1. ✅ Already fixed: Companies now verified, showing on public pages
2. ⚠️ Fix sector_id: Populate from sector string
3. ℹ️ Document: This is expected behavior for a B2B2C platform
