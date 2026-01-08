# PROTOCOL 1: Required Backend Fixes

## Issue 2 & 3: CORS Configuration

### Root Cause
Backend is not sending proper CORS headers because `FRONTEND_URL` is not set in `.env` file.

### Execution Path
1. Browser makes request from `http://localhost:3000` → `http://localhost:8000/api/v1/*`
2. Browser sends OPTIONS preflight request
3. Laravel CORS middleware checks `env('FRONTEND_URL')`
4. Variable not found in `.env`, falls back to default
5. CORS headers not sent or incorrect
6. Browser blocks request
7. User sees: "No 'Access-Control-Allow-Origin' header" error

### Fix Steps

#### Step 1: Add FRONTEND_URL to backend .env
```bash
cd /home/user/PreIPOsip2Gemini/backend

# If .env doesn't exist, copy from example
if [ ! -f .env ]; then
    cp .env.example .env
    php artisan key:generate
fi

# Add or update FRONTEND_URL
echo "" >> .env
echo "# Frontend URL for CORS" >> .env
echo "FRONTEND_URL=http://localhost:3000" >> .env
```

#### Step 2: Clear Laravel config cache (if backend is running)
```bash
cd /home/user/PreIPOsip2Gemini/backend

# Clear all caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear

# Rebuild config cache
php artisan config:cache
```

#### Step 3: Restart Laravel backend server
```bash
# If running via php artisan serve:
# Ctrl+C to stop, then restart:
php artisan serve --host=0.0.0.0 --port=8000

# If running via Docker/Supervisor:
# Restart the container/service
```

#### Step 4: Verify CORS is working
Open browser console and check:
- Visit `http://localhost:3000/for-companies`
- Check Network tab
- Look for request to `http://localhost:8000/api/v1/campaigns/active`
- Response headers should include:
  - `Access-Control-Allow-Origin: http://localhost:3000`
  - `Access-Control-Allow-Credentials: true`

### Alternative Fix (if above doesn't work)

Edit `backend/config/cors.php` directly:

```php
// Line 25: Change from
'allowed_origins' => [env('FRONTEND_URL', 'http://localhost:3000')],

// To (for development only):
'allowed_origins' => ['*'],

// Or explicitly:
'allowed_origins' => ['http://localhost:3000', 'http://127.0.0.1:3000'],
```

**WARNING**: `'*'` is insecure for production. Only use in development.

### Company Login Testing

After CORS is fixed, test company login:
1. Visit `http://localhost:3000/company/login`
2. Enter credentials (must have existing company_user in DB)
3. Submit form
4. Should receive token and redirect to `/company/dashboard`

**Note**: New registrations start in 'pending' status (line 100-104 in AuthController.php).
Admin must approve via admin panel before login succeeds.

### Status Workflow
- **Pending**: Account awaiting admin approval
- **Active**: Can login successfully
- **Suspended**: Login blocked with message
- **Rejected**: Login blocked with rejection reason

---

## Frontend Fix Applied ✅

File: `frontend/lib/api.ts`
Line: 183
Change: Added `/for-companies` to publicPaths array

This prevents the redirect to `/login` when visiting the For Companies page.
