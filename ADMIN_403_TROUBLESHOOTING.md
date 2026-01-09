# Admin 403 Error Troubleshooting Guide

## Problem
Admin routes returning **403 Forbidden** error:
- `http://localhost:8000/api/v1/admin/dashboard` → 403
- `http://localhost:8000/api/v1/admin/notifications/system` → 403

## Quick Diagnosis

### Option 1: Run Automated Test Script
```bash
cd backend
./test_admin_403.sh
```

### Option 2: Manual Testing

#### Step 1: Test Admin Login
```bash
curl -X POST http://localhost:8000/api/v1/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "YOUR_ADMIN_EMAIL",
    "password": "YOUR_ADMIN_PASSWORD"
  }'
```

**Expected:** Returns `{"token": "..."}` with status 200
**Save the token** for next steps.

#### Step 2: Test Admin Dashboard
```bash
curl -X GET http://localhost:8000/api/v1/admin/dashboard \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -v
```

**If you get 403**, proceed to Step 3.

#### Step 3: Check Laravel Logs
```bash
tail -50 backend/storage/logs/laravel.log | grep -A 5 'ADMIN-IP-CHECK'
```

## Common Causes of 403

### Cause 1: IP Whitelist Blocking ⚠️

**Symptom:** Logs show `[ADMIN-IP-CHECK] BLOCKED: IP X.X.X.X not in whitelist`

**Solution A - Disable IP Whitelist (for development):**
```sql
-- Run in MySQL
USE your_database_name;
UPDATE ip_whitelists SET is_active = 0 WHERE is_active = 1;
```

**Solution B - Add Your IP to Whitelist:**
```sql
INSERT INTO ip_whitelists (ip_address, description, is_active, created_at, updated_at)
VALUES ('127.0.0.1', 'Localhost', 1, NOW(), NOW());
```

**Solution C - Clear IP Whitelist Cache:**
```bash
cd backend
php artisan cache:forget ip_whitelist.active
```

### Cause 2: Missing Admin Role 👤

**Symptom:** No IP check logs, but still 403 error

**Check User Roles:**
```sql
-- Find your user ID
SELECT id, email FROM users WHERE email = 'YOUR_ADMIN_EMAIL';

-- Check roles for that user (replace USER_ID)
SELECT r.name
FROM roles r
JOIN model_has_roles mhr ON r.id = mhr.role_id
WHERE mhr.model_type = 'App\\Models\\User'
  AND mhr.model_id = USER_ID;
```

**Expected:** Should return 'admin' or 'super-admin'

**Fix - Assign Admin Role:**
```bash
cd backend
php artisan tinker

# In tinker:
$user = App\Models\User::where('email', 'YOUR_EMAIL')->first();
$user->assignRole('admin');
exit
```

### Cause 3: Wrong Token Type 🔑

**Symptom:** Token works for user routes but not admin routes

**Check Token Type:**
```sql
-- Find tokens for your email
SELECT
    u.email,
    u.id as user_id,
    pat.tokenable_type,
    pat.name as token_name,
    pat.created_at
FROM users u
JOIN personal_access_tokens pat ON pat.tokenable_id = u.id
WHERE u.email = 'YOUR_EMAIL'
  AND pat.tokenable_type = 'App\\Models\\User'
ORDER BY pat.created_at DESC
LIMIT 5;
```

**Expected:** `tokenable_type` should be `App\Models\User`, NOT `App\Models\CompanyUser`

**Fix:** Login again using the user login endpoint (not company login):
```bash
curl -X POST http://localhost:8000/api/v1/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "ADMIN_EMAIL",
    "password": "ADMIN_PASSWORD"
  }'
```

## Applied Fixes in Latest Commit

### 1. **Middleware Order Correction** (`routes/api.php:464`)
```php
// BEFORE (wrong order):
Route::prefix('admin')->middleware(['auth:sanctum', 'role:admin|super-admin', 'admin.ip'])

// AFTER (correct order):
Route::prefix('admin')->middleware(['auth:sanctum', 'admin.ip', 'role:admin|super-admin'])
```
**Why:** IP whitelist must be checked BEFORE role check per security policy.

### 2. **Diagnostic Logging** (`app/Http/Middleware/AdminIpRestriction.php`)
Added comprehensive logging:
- `[ADMIN-IP-CHECK] Middleware triggered` - Shows when middleware runs
- `[ADMIN-IP-CHECK] IP whitelist is empty` - Whitelist disabled
- `[ADMIN-IP-CHECK] BLOCKED` - IP not allowed

### 3. **Auth Group Closure** (`routes/api.php:459`)
Properly closed `auth:sanctum + mfa.verified` group before admin routes to prevent:
- Double auth:sanctum wrapping
- Unwanted MFA requirement on admin routes

## Verification Steps

After applying fixes:

1. **Clear route cache:**
```bash
cd backend
php artisan route:clear
php artisan cache:clear
```

2. **Restart Laravel server:**
```bash
php artisan serve
```

3. **Test admin endpoint:**
```bash
curl -X GET http://localhost:8000/api/v1/admin/dashboard \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -v
```

4. **Check logs in real-time:**
```bash
tail -f backend/storage/logs/laravel.log | grep --line-buffered 'ADMIN'
```

## Still Having Issues?

If 403 persists after all checks:

1. **Verify admin route registration:**
```bash
php artisan route:list --path=admin/dashboard
```

2. **Check middleware stack:**
Look for the line with `/admin/dashboard` and verify middleware shows:
```
auth:sanctum, admin.ip, role:admin|super-admin
```

3. **Test with Postman/Insomnia:**
- Set request header: `Authorization: Bearer YOUR_TOKEN`
- Check response body for specific error message

4. **Enable debug mode:**
In `.env` file:
```env
APP_DEBUG=true
LOG_LEVEL=debug
```

5. **Check for other middleware conflicts:**
```bash
grep -r "admin" app/Http/Middleware/ | grep -v ".php:"
```

## Contact

If issue persists, provide:
1. Laravel log output (last 50 lines)
2. HTTP response body from curl request
3. Output of `SELECT * FROM ip_whitelists WHERE is_active = 1;`
4. Output of role check SQL query above
