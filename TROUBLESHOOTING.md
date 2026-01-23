# Troubleshooting Guide

## Issue 1: Admin Company Detail Page - "Cannot read properties of undefined (reading 'is_visible_public')"

### Root Cause
Next.js development server is serving cached JavaScript bundle that doesn't include the latest code changes.

### Solution

**Option A: Use the automated fix script**
```bash
bash /home/user/PreIPOsip2Gemini/fix-frontend-cache.sh
```

**Option B: Manual steps**
```bash
# 1. Stop Next.js dev server
pkill -f "next dev"

# 2. Clear Next.js cache
cd /home/user/PreIPOsip2Gemini/frontend
rm -rf .next
rm -rf node_modules/.cache

# 3. Restart dev server
npm run dev

# 4. Hard refresh browser (Ctrl+Shift+R or Cmd+Shift+R)
```

### Verification
After restarting:
- Visit: http://localhost:3000/admin/companies/[any-id]
- Page should load without errors
- If still errors, check browser console and ensure you're not looking at cached error

---

## Issue 2: Disclosure Pending Page - "Failed to load pending disclosures"

### Root Cause
There are likely NO pending disclosure submissions in the database yet.

### Solution

**Check if any disclosures exist:**

If you have companies in your database, they need to submit disclosures first:

1. **Company submits disclosures** (via Company Portal):
   ```
   Company Login → http://localhost:3000/company/disclosures
   Submit Tier 1, 2, or 3 disclosure modules
   ```

2. **Admin reviews disclosures**:
   ```
   Admin Panel → Companies → Disclosure Reviews
   OR
   Direct: http://localhost:3000/admin/disclosures/pending
   ```

### Testing Without Real Company Submissions

If you want to test the disclosure review flow without companies:

**Option 1: Seed test data** (if seeder exists)
```bash
cd /home/user/PreIPOsip2Gemini/backend
php artisan db:seed --class=DisclosureSeeder
```

**Option 2: Create test disclosure manually** (via Tinker)
```bash
cd /home/user/PreIPOsip2Gemini/backend
php artisan tinker
```

```php
$company = \App\Models\Company::first();
$module = \App\Models\DisclosureModule::where('tier', 2)->first();

\App\Models\CompanyDisclosure::create([
    'company_id' => $company->id,
    'disclosure_module_id' => $module->id,
    'status' => 'submitted',
    'disclosure_data' => ['test' => 'data'],
    'version_number' => 1,
    'submitted_at' => now(),
    'completion_percentage' => 100,
]);
```

### Verification
After adding test data:
- Visit: http://localhost:3000/admin/disclosures/pending
- Should see list of pending disclosures
- Click "Review" to approve/reject

---

## Issue 3: Deals Page Shows No Companies (After Tier 2 Filter)

### Root Cause
The deals listing now ONLY shows companies that have:
- `buying_enabled = true`
- `lifecycle_state IN ('live_investable', 'live_fully_disclosed')`

These require **Tier 2 disclosure approval**.

### Solution

**To make a company appear in deals:**

1. **Submit Tier 2 disclosures** (as company):
   ```
   Company Portal → Disclosures → Submit all required Tier 2 modules
   ```

2. **Approve Tier 2 disclosures** (as admin):
   ```
   Admin Panel → Companies → Disclosure Reviews → Approve all Tier 2 modules
   ```

3. **Auto-transition happens**:
   - `lifecycle_state`: `live_limited` → `live_investable`
   - `buying_enabled`: `false` → `true`
   - Company appears in `/deals`
   - Investment form becomes visible

### Temporary Workaround (for testing only)

If you need to quickly test without going through disclosure approval:

```bash
cd /home/user/PreIPOsip2Gemini/backend
php artisan tinker
```

```php
$company = \App\Models\Company::find(90); // Replace with your company ID
$company->buying_enabled = true;
$company->lifecycle_state = 'live_investable';
$company->tier_2_approved_at = now();
$company->save();
```

**⚠️ WARNING:** This bypasses the proper disclosure review workflow. Use only for testing!

---

## General Debugging Tips

### Check Backend is Running
```bash
# Should see Laravel server running on port 8000
curl http://localhost:8000/api/v1/health
```

### Check Frontend is Running
```bash
# Should see Next.js on port 3000
curl http://localhost:3000
```

### Check Backend Logs
```bash
tail -f /home/user/PreIPOsip2Gemini/backend/storage/logs/laravel.log
```

### Clear All Caches
```bash
# Backend
cd /home/user/PreIPOsip2Gemini/backend
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Frontend
cd /home/user/PreIPOsip2Gemini/frontend
rm -rf .next
rm -rf node_modules/.cache
```

---

## Expected Workflow

### For Companies to Accept Investments:

```
1. Company created
   ↓
2. Company submits Tier 1 disclosures
   ↓
3. Admin approves Tier 1
   ↓
   lifecycle_state: draft → live_limited
   (Company visible but buying disabled)
   ↓
4. Company submits Tier 2 disclosures
   ↓
5. Admin approves Tier 2
   ↓
   lifecycle_state: live_limited → live_investable
   buying_enabled: false → true
   (Company appears in /deals, investment form visible)
   ↓
6. Company submits Tier 3 disclosures
   ↓
7. Admin approves Tier 3
   ↓
   lifecycle_state: live_investable → live_fully_disclosed
   (Trust badge, maximum transparency)
```

### For Investors to Invest:

```
1. User visits /deals
   ↓
2. Only sees Tier 2+ approved companies
   ↓
3. Clicks on company
   ↓
4. Sees investment form (if buying_enabled = true)
   ↓
5. Completes investment
```
