# Company Verification Workflow

## Overview

Companies MUST be verified (`is_verified=true`) to appear on public and user-facing pages. This is a **security and quality control measure** to ensure only properly reviewed companies are shown to investors.

---

## How Verification Works

### 1. Normal Workflow (Going Forward)

**When a company is created:**
1. Admin goes to `/admin/content/companies`
2. Clicks "Add Company" button
3. Fills in company details
4. **IMPORTANT:** Checks the "Verified Company" checkbox
5. Saves the company

**When editing an existing company:**
1. Admin goes to `/admin/content/companies`
2. Clicks "Edit" on a company row
3. Toggles the "Verified Company" checkbox
4. Saves changes

**Backend Processing:**
```php
// File: backend/app/Http/Controllers/Api/Admin/CompanyController.php:142-144
if (isset($data['is_verified'])) {
    $sensitiveFields['is_verified'] = $data['is_verified'];
    unset($data['is_verified']);
}

// Explicit admin-only update
$company->update($sensitiveFields);
```

**Result:**
- Company with `is_verified=true` → Shows on public pages
- Company with `is_verified=false` → Hidden from public, only in admin

---

### 2. One-Time Fix Script (For Existing Data)

**Purpose:** Fix companies created BEFORE verification checkbox was added.

**When to use:**
- You have existing companies with active deals
- They're not showing on public pages
- They have `is_verified=false` in database

**How to run:**
```bash
cd backend
php fix_verify_companies.php
```

**What it does:**
1. Finds all companies with `is_verified=false` but have active deals
2. Lists them for admin review
3. Asks for confirmation
4. Sets `is_verified=true` for approved companies

**Example output:**
```
=== VERIFYING COMPANIES WITH ACTIVE DEALS ===

Found 4 unverified companies with active deals:

Company #87: NexGen AI Solutions
  Status: active
  Verified: NO
  Active deals: 1

Company #88: MediCare Plus HealthTech
  Status: active
  Verified: NO
  Active deals: 1

...

Do you want to verify these companies? (yes/no): yes

✓ Verified: NexGen AI Solutions
✓ Verified: MediCare Plus HealthTech
...

=== COMPLETE ===
Verified 4 companies.
```

---

## Why Verification is Required

### Security & Quality Control

**Without verification check:**
- Any company added to database appears publicly
- Test companies show up on live site
- Incomplete profiles visible to investors
- Spam/fake companies could be listed

**With verification check:**
- Admin explicitly approves each company
- Test data stays hidden
- Quality control before public visibility
- Compliance with regulatory requirements

### Where Verification is Checked

**Public Pages:**
```php
// File: backend/app/Http/Controllers/Api/Public/CompanyProfileController.php:72-73
Company::where('status', 'active')
    ->where('is_verified', true)  // ← BLOCKS unverified companies
    ->whereHas('deals', ...)
```

**User Pages:**
```php
// File: backend/app/Http/Controllers/Api/Investor/InvestorCompanyController.php:41-42
Company::where('status', 'active')
    ->where('is_verified', true)  // ← BLOCKS unverified companies
    ->whereHas('deals', ...)
```

**Admin Pages:**
```php
// File: backend/app/Http/Controllers/Api/Admin/CompanyController.php:15
Company::query();  // NO filter - shows ALL companies
```

Admin sees everything, users only see verified.

---

## Frontend Changes Made

### Admin UI Updates

**File:** `frontend/app/admin/content/companies/page.tsx`

**1. Added `is_verified` to form state (line 39):**
```typescript
const [formData, setFormData] = useState({
  ...
  is_featured: false,
  is_verified: false, // NEW: Verification checkbox
  status: 'active',
});
```

**2. Added checkbox in form dialog (after line 288):**
```tsx
<div className="space-y-2 flex items-center">
  <input
    type="checkbox"
    id="is_verified"
    checked={formData.is_verified}
    onChange={(e) => setFormData({ ...formData, is_verified: e.target.checked })}
    className="mr-2"
  />
  <Label htmlFor="is_verified">
    Verified Company (Required for public visibility)
  </Label>
</div>
```

**3. Added "Verified" column in table (line 352):**
```tsx
<TableHead>Verified</TableHead>
```

**4. Added verification status badge (line 372):**
```tsx
<TableCell>
  {company.is_verified ? (
    <Badge variant="default" className="bg-green-600">✓ Verified</Badge>
  ) : (
    <Badge variant="destructive">✗ Not Verified</Badge>
  )}
</TableCell>
```

---

## Testing

### Test Verification Workflow

**1. Create a new company (unverified):**
```
1. Go to http://localhost:3000/admin/content/companies
2. Click "Add Company"
3. Fill in details
4. Leave "Verified Company" UNCHECKED
5. Save
6. Go to http://localhost:3000/products
7. Verify: Company does NOT appear
```

**2. Verify the company:**
```
1. Go back to /admin/content/companies
2. Click "Edit" on the company
3. CHECK "Verified Company" checkbox
4. Save
5. Go to http://localhost:3000/products
6. Verify: Company NOW appears
```

**3. Bulk verify existing companies:**
```bash
cd backend
php fix_verify_companies.php
# Type 'yes' to confirm
# Restart frontend
# Go to http://localhost:3000/products
# Verify: Companies with deals now appear
```

---

## Summary

**Two Ways to Verify Companies:**

| Method | When to Use | How |
|--------|-------------|-----|
| **Admin UI Checkbox** | Normal workflow going forward | Check "Verified Company" when creating/editing |
| **Fix Script** | One-time fix for existing data | Run `php fix_verify_companies.php` |

**Key Points:**
- ✅ Only verified companies show on public/user pages
- ✅ Admin panel shows ALL companies (verified + unverified)
- ✅ Verification is an admin-only permission
- ✅ Required for regulatory compliance
- ✅ Prevents test/incomplete data from going public

**Files Modified:**
- Frontend: `app/admin/content/companies/page.tsx` (added checkbox + badge)
- Backend: Already had support in `CompanyController.php:116`
- Script: `fix_verify_companies.php` (bulk verification tool)

**Next Steps:**
1. Use admin UI checkbox for ALL new companies
2. Run fix script once to verify existing companies
3. Never run fix script again (use UI going forward)
