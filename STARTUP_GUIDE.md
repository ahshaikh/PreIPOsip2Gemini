# PreIPOsip Platform - Complete Startup Guide

**Last Updated**: 2026-01-09
**Session**: claude/audit-preipopsip-platform-MNnCa

---

## 🚨 Quick Start (TL;DR)

```bash
# Terminal 1 - Backend
cd backend
composer install          # One-time setup
./START_SERVER.sh        # Start Laravel on port 8000

# Terminal 2 - Frontend
cd frontend
npm install              # One-time setup
./RESTART_DEV_SERVER.sh start  # Start Next.js on port 3000
```

Then open: http://localhost:3000

---

## 📋 Complete Setup Guide

### Prerequisites

- **PHP 8.3+** with extensions: `mbstring`, `xml`, `bcmath`, `pdo_mysql`
- **Composer 2.x**
- **Node.js 18+** and **npm**
- **MySQL 8.0+** (or configured database)
- **Redis 6.0+** (optional, for queues/cache)

---

## Backend Setup (Laravel 11)

### 1. Install Dependencies

```bash
cd /home/user/PreIPOsip2Gemini/backend
composer install
```

**Expected Output:**
```
Loading composer repositories with package information
Installing dependencies from lock file
...
Generating optimized autoload files
```

If you get errors about missing extensions:
```bash
# Ubuntu/Debian
sudo apt-get install php8.3-mbstring php8.3-xml php8.3-bcmath php8.3-mysql

# macOS
brew install php@8.3
```

### 2. Configure Environment

```bash
# Copy example env file
cp .env.example .env

# Generate application key
php artisan key:generate

# Edit .env with your database credentials
nano .env  # or vim, code, etc.
```

**Required .env settings:**
```env
APP_URL=http://localhost:8000
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=preiposip
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

### 3. Run Migrations

```bash
# Create database first
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS preiposip;"

# Run migrations and seeders
php artisan migrate --seed
```

### 4. Create Storage Link

```bash
php artisan storage:link
```

This creates a symbolic link from `public/storage` to `storage/app/public`, allowing uploaded files (logos, photos) to be accessible via HTTP.

### 5. Start Backend Server

```bash
./START_SERVER.sh
```

Or manually:
```bash
php artisan serve --host=0.0.0.0 --port=8000
```

**Expected Output:**
```
🚀 Starting Laravel Backend Server...
🔧 Starting Laravel development server...
   URL: http://localhost:8000
   API Base: http://localhost:8000/api/v1

Laravel development server started: http://127.0.0.1:8000
```

**Test the API:**
```bash
curl http://localhost:8000/api/v1/plans
# Should return JSON with plans data
```

---

## Frontend Setup (Next.js 18)

### 1. Install Dependencies

```bash
cd /home/user/PreIPOsip2Gemini/frontend
npm install
```

**Expected Output:**
```
added 500 packages, and audited 501 packages in 30s
...
found 0 vulnerabilities
```

### 2. Verify Environment Configuration

```bash
cat .env.local
```

**Should contain:**
```env
# API Configuration
# IMPORTANT: Must include /api/v1 for API calls
# Image URLs automatically strip /api/v1 to access storage at root
NEXT_PUBLIC_API_URL=http://localhost:8000/api/v1

# Environment
NODE_ENV=development
```

If `.env.local` doesn't exist or is incorrect:
```bash
cat > .env.local << 'EOF'
# API Configuration
NEXT_PUBLIC_API_URL=http://localhost:8000/api/v1

# Environment
NODE_ENV=development
EOF
```

### 3. Start Frontend Server

```bash
./RESTART_DEV_SERVER.sh start
```

Or manually:
```bash
# Clear caches first
rm -rf .next .turbo node_modules/.cache

# Start dev server
npm run dev
```

**Expected Output:**
```
  ▲ Next.js 16.0.1 (Turbopack)
  - Local:        http://localhost:3000
  - Network:      http://0.0.0.0:3000

 ✓ Starting...
 ✓ Ready in 2.5s
```

---

## ✅ Verification Checklist

### Backend Health Check

1. **Server Running**:
   ```bash
   curl http://localhost:8000/api/v1/plans
   ```
   ✅ Should return JSON (not 404 or connection refused)

2. **Storage Accessible**:
   ```bash
   ls -la public/storage
   ```
   ✅ Should show symbolic link to `../../storage/app/public`

3. **Database Connected**:
   ```bash
   php artisan migrate:status
   ```
   ✅ Should show migration table with ran migrations

### Frontend Health Check

1. **Homepage Loads**:
   Open http://localhost:3000
   ✅ Should see homepage (not blank or error)

2. **No Console Errors**:
   - Press F12 to open browser console
   - Refresh page
   ✅ No red errors about "Network Error" or "404"

3. **API Calls Working**:
   Check console for:
   ```
   [API INTERCEPTOR] Running for: GET /public/banners
   ```
   ✅ Should NOT see "Failed to construct 'URL'"

4. **Images Loading** (if company has logo):
   - Navigate to http://localhost:3000/company/profile
   - Check that logo displays or shows proper error fallback
   ✅ Image URL should be `http://localhost:8000/storage/company-logos/...`

---

## 🐛 Common Issues & Solutions

### Issue 1: "Network Error" on frontend

**Symptoms:**
```
Network Error
Unable to connect to server. Please check your internet connection.
```

**Cause:** Backend not running or wrong API URL

**Solution:**
```bash
# Check backend is running
curl http://localhost:8000/api/v1/plans

# If connection refused:
cd backend
./START_SERVER.sh

# Verify .env.local has /api/v1
cat frontend/.env.local | grep NEXT_PUBLIC_API_URL
# Should show: NEXT_PUBLIC_API_URL=http://localhost:8000/api/v1
```

---

### Issue 2: All API endpoints return 404

**Symptoms:**
```
404 Not Found
http://localhost:8000/plans
http://localhost:8000/public/banners
```

**Cause:** Missing `/api/v1` in NEXT_PUBLIC_API_URL

**Solution:**
```bash
# Update frontend/.env.local
echo "NEXT_PUBLIC_API_URL=http://localhost:8000/api/v1" > frontend/.env.local

# Restart frontend
cd frontend
./RESTART_DEV_SERVER.sh start
```

---

### Issue 3: "undefined/storage/..." image URLs

**Symptoms:**
```
Failed to parse src "undefined/storage/company-logos/..."
```

**Cause:** Stale build cache or missing env var

**Solution:**
```bash
cd frontend

# Clear all caches
rm -rf .next .turbo node_modules/.cache

# Verify env file
cat .env.local

# Restart dev server
npm run dev
```

---

### Issue 4: "vendor/autoload.php not found"

**Symptoms:**
```
PHP Fatal error: Failed opening required 'vendor/autoload.php'
```

**Cause:** Composer dependencies not installed

**Solution:**
```bash
cd backend
composer install

# If composer not found:
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

---

### Issue 5: "Class 'SomeModel' not found"

**Cause:** Autoload cache stale

**Solution:**
```bash
cd backend
composer dump-autoload
php artisan config:clear
php artisan cache:clear
```

---

### Issue 6: Port 8000 already in use

**Symptoms:**
```
Address already in use
```

**Solution:**
```bash
# Find process using port 8000
lsof -i :8000

# Kill the process (replace PID)
kill -9 <PID>

# Or use the start script which handles this
./START_SERVER.sh
```

---

### Issue 7: Turbopack build errors after code changes

**Symptoms:**
```
Parsing ecmascript source code failed
Expression expected
```

**Cause:** Stale Turbopack cache

**Solution:**
```bash
cd frontend
./RESTART_DEV_SERVER.sh start  # Clears cache and restarts
```

---

## 📁 Project Structure Reference

```
PreIPOsip2Gemini/
├── backend/              # Laravel 11 API
│   ├── app/
│   │   ├── Http/Controllers/Api/  # API endpoints
│   │   ├── Models/                # Eloquent models
│   │   └── Services/              # Business logic
│   ├── routes/api.php    # API route definitions
│   ├── storage/
│   │   └── app/public/   # Uploaded files (logos, photos)
│   ├── .env.example      # Environment template
│   └── START_SERVER.sh   # Backend startup script ✨
│
└── frontend/             # Next.js 18 UI
    ├── app/
    │   ├── (public)/     # Public pages
    │   ├── (user)/       # User dashboard
    │   ├── admin/        # Admin panel
    │   └── company/      # Company portal ✨
    ├── lib/
    │   ├── api.ts        # Main API client
    │   └── companyApi.ts # Company API client ✨
    ├── .env.local        # Environment config ✨
    └── RESTART_DEV_SERVER.sh  # Frontend startup script ✨
```

---

## 🔧 Helper Scripts

### Backend

**`backend/START_SERVER.sh`** - Start Laravel server
- Checks for vendor dependencies
- Creates .env if missing
- Handles port conflicts
- Shows server status

### Frontend

**`frontend/RESTART_DEV_SERVER.sh`** - Start Next.js server
- Clears all caches (.next, .turbo, etc.)
- Verifies .env.local exists
- Validates code integrity
- Can auto-start with `./RESTART_DEV_SERVER.sh start`

---

## 🎯 Development Workflow

### Daily Startup

1. **Start Backend** (Terminal 1):
   ```bash
   cd backend
   ./START_SERVER.sh
   ```

2. **Start Frontend** (Terminal 2):
   ```bash
   cd frontend
   ./RESTART_DEV_SERVER.sh start
   ```

3. **Open Browser**: http://localhost:3000

### After Git Pull

```bash
# Backend - check for new dependencies
cd backend
composer install
php artisan migrate

# Frontend - check for new dependencies
cd frontend
npm install
./RESTART_DEV_SERVER.sh  # Clear caches
```

### After Env Changes

```bash
# Backend
cd backend
php artisan config:clear
php artisan cache:clear
# Restart server (Ctrl+C, then ./START_SERVER.sh)

# Frontend
cd frontend
./RESTART_DEV_SERVER.sh start  # Auto-clears cache and restarts
```

---

## 📞 Getting Help

### Logs to Check

**Backend Logs:**
```bash
tail -f backend/storage/logs/laravel.log

in PowerShell
Get-Content backend\storage\logs\laravel.log -Tail 100 -Wait

```

**Frontend Console:**
- Open browser DevTools (F12)
- Check Console tab for errors
- Check Network tab for failed requests

### Diagnostic Commands

```bash
# Backend health
curl -v http://localhost:8000/api/v1/plans

# Frontend env
cat frontend/.env.local

# Process check
ps aux | grep -E "(php|node)"

in PowerShell

Get-Process | Where-Object {
  $_.ProcessName -match 'php|node'
}


# Port check
lsof -i :8000  # Backend
lsof -i :3000  # Frontend

in PowerShell
Get-NetTCPConnection -LocalPort 8000
Get-NetTCPConnection -LocalPort 3000

```

---

## ✅ Success Indicators

When everything is working correctly, you should see:

1. **Backend Terminal:**
   ```
   Laravel development server started: http://127.0.0.1:8000
   [Thu Jan  9 17:30:00 2026] Accepted
   ```

2. **Frontend Terminal:**
   ```
   ✓ Compiled / in 500ms (2000 modules)
   ```

3. **Browser Console:**
   ```
   [API INTERCEPTOR] Running for: GET /public/banners
   ```
   (No red errors)

4. **Homepage:** Loads with no "Network Error" toast

---

**Created By**: Claude (Session: claude/audit-preipopsip-platform-MNnCa)
**Related Docs**: CACHE_ISSUE_FIX.md, COMPANY_PROFILE_IMAGE_FIX_AUDIT.md
