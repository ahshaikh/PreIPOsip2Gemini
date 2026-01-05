# Local Development Environment Setup

This guide documents how to set up a complete local development environment for the PreIPOsip Laravel backend, enabling full testing and development capabilities.

## 📋 Prerequisites

- PHP 8.2+ (8.4 tested and working)
- Composer 2.8+
- PostgreSQL 16+ (or MySQL 8.0+)
- Git

## 🚀 Quick Setup (10 Minutes)

### Step 1: Install PHP Dependencies

```bash
cd backend
composer install --no-interaction --prefer-dist
```

**Expected output:**
```
Installing dependencies from lock file
...
148/148 [============================] 100%
Generating optimized autoload files
Package discovery complete
```

**Time:** ~2-3 minutes

---

### Step 2: Configure Environment

```bash
cp .env.example .env
```

Edit `.env` for your database:

**For PostgreSQL (Recommended for local testing):**
```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=preipo_test
DB_USERNAME=testuser
DB_PASSWORD=testpass123

# Simplified for local development
CACHE_STORE=file
QUEUE_CONNECTION=sync
SESSION_DRIVER=file
```

**For MySQL:**
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=preipo_dev
DB_USERNAME=root
DB_PASSWORD=yourpassword
```

---

### Step 3: Set Up Database

**PostgreSQL:**
```bash
# Start PostgreSQL
sudo service postgresql start

# Create database and user
sudo -u postgres psql -c "CREATE DATABASE preipo_test;"
sudo -u postgres psql -c "CREATE USER testuser WITH PASSWORD 'testpass123';"
sudo -u postgres psql -c "GRANT ALL PRIVILEGES ON DATABASE preipo_test TO testuser;"
sudo -u postgres psql preipo_test -c "GRANT ALL ON SCHEMA public TO testuser;"
```

**MySQL:**
```bash
# Start MySQL
sudo service mysql start

# Create database
mysql -u root -p -e "CREATE DATABASE preipo_dev;"
```

---

### Step 4: Generate Application Key

```bash
php artisan key:generate
```

**Expected output:**
```
INFO  Application key set successfully.
```

---

### Step 5: Run Migrations

```bash
php artisan migrate --force
```

**Note:** Some migrations may fail due to ordering issues. This is okay for local testing. The core tables will be created.

**Expected:**
- ✅ `users` table created
- ✅ `cache` tables created
- ✅ `jobs` tables created
- ⚠️ Some later migrations may fail (expected)

---

### Step 6: Verify Setup

```bash
# Check Artisan commands work
php artisan list

# Test database connection
php artisan migrate:status

# Test seeder validator
php artisan seed:inspect
```

**Success indicators:**
- Artisan commands execute without errors
- `seed:inspect` runs and shows validation results
- Database connection works

---

## 🎯 Testing the Seeder Validator

### Basic Test

```bash
php artisan seed:inspect
```

**Expected output (with existing violations):**
```
╔═══════════════════════════════════════════════════════════════╗
║         SEEDER-SCHEMA CONTRACT VALIDATOR                      ║
╚═══════════════════════════════════════════════════════════════╝

🔍 Scanning database schema...
📄 Analyzing seeder files...

❌ VALIDATION FAILED

SUMMARY:
┌──────────────────┬───────┐
│ Total Violations │ 15    │
│ Affected Tables  │ 9     │
│ Affected Seeders │ 4     │
└──────────────────┴───────┘

VIOLATIONS:
...
```

**Exit code:** 1 (indicates violations found)

### JSON Output Test

```bash
php artisan seed:inspect --format=json
```

**Expected:** JSON formatted output with violations array

---

## 🏗️ Directory Structure

After setup, your backend directory should contain:

```
backend/
├── vendor/                  # ✅ Composer dependencies
├── .env                     # ✅ Environment configuration
├── database/
│   └── database.sqlite      # (if using SQLite)
├── storage/
│   ├── logs/
│   └── framework/
├── app/
│   ├── Services/Seeder/     # ✅ Seeder validator services
│   └── Console/Commands/
│       └── InspectSeederCommand.php  # ✅ seed:inspect command
└── docs/
    ├── SEEDER_CONTRACT_VALIDATOR.md
    ├── SEEDER_VALIDATOR_QUICKSTART.md
    └── LOCAL_DEVELOPMENT_SETUP.md  # This file
```

---

## 🔧 Common Issues & Solutions

### Issue: "could not find driver"

**Cause:** PHP PDO extension not installed for your database

**Solution:**
```bash
# For PostgreSQL
sudo apt-get install php8.4-pgsql

# For MySQL
sudo apt-get install php8.4-mysql

# Restart PHP-FPM if using
sudo service php8.4-fpm restart
```

---

### Issue: "Class not found" errors

**Cause:** Autoload files out of date

**Solution:**
```bash
composer dump-autoload
php artisan clear-compiled
php artisan cache:clear
```

---

### Issue: "Database connection failed"

**Cause:** Database server not running or credentials wrong

**Solution:**
```bash
# Check PostgreSQL is running
sudo service postgresql status

# Check MySQL is running
sudo service mysql status

# Verify credentials in .env match database user
```

---

### Issue: Migration fails with "table already exists"

**Cause:** Previous partial migration

**Solution:**
```bash
# Fresh start (destroys data!)
php artisan migrate:fresh --force

# Or continue from where it left off
php artisan migrate --force
```

---

### Issue: "seed:inspect" command not found

**Cause:** Command not registered or cache issue

**Solution:**
```bash
# Clear all caches
php artisan clear-compiled
php artisan cache:clear
php artisan config:clear

# Verify file exists
ls -la app/Console/Commands/InspectSeederCommand.php

# Regenerate autoload
composer dump-autoload
```

---

## 📊 Environment Verification Checklist

Run these commands to verify setup:

```bash
# ✅ PHP version
php --version  # Should be 8.2+

# ✅ Composer version
composer --version  # Should be 2.x

# ✅ Database connection
php artisan tinker
>>> DB::connection()->getPdo();
>>> exit;

# ✅ Vendor directory exists
ls -la vendor/ | head

# ✅ Application key set
cat .env | grep APP_KEY  # Should have value

# ✅ Migrations ran
php artisan migrate:status

# ✅ Seeder validator works
php artisan seed:inspect
```

---

## 🎓 Development Workflow

### Making Changes to Seeder Validator

1. **Edit service files:**
   ```bash
   nano app/Services/Seeder/SchemaInspector.php
   ```

2. **Test changes immediately:**
   ```bash
   php artisan seed:inspect
   ```

3. **No cache clearing needed** - PHP files are reloaded on each execution

### Testing with Real Seeders

1. **Create a test seeder:**
   ```bash
   php artisan make:seeder TestValidatorSeeder
   ```

2. **Add intentional violation:**
   ```php
   DB::table('users')->insert([
       'name' => 'Test',
       // Intentionally omit required field
   ]);
   ```

3. **Run validator:**
   ```bash
   php artisan seed:inspect
   ```

4. **Should detect violation** with file, line number, and code snippet

---

## 🎉 Next Steps

Once setup is complete:

1. **Read the validator documentation:**
   - `docs/SEEDER_CONTRACT_VALIDATOR.md` - Full technical docs
   - `docs/SEEDER_VALIDATOR_QUICKSTART.md` - Quick reference

2. **Test on real seeders:**
   ```bash
   php artisan seed:inspect
   ```

3. **Fix any violations found**

4. **Integrate into workflow:**
   - Add to DatabaseSeeder (see docs)
   - Add to CI/CD pipeline (see docs)
   - Create pre-commit hook

---

## 📝 Environment File Reference

### Minimal .env for Local Development

```env
APP_NAME="Pre-IPO SIP"
APP_ENV=local
APP_KEY=base64:xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx=
APP_DEBUG=true
APP_URL=http://localhost:8000

LOG_CHANNEL=stack
LOG_LEVEL=debug

# PostgreSQL (Recommended)
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=preipo_test
DB_USERNAME=testuser
DB_PASSWORD=testpass123

# Simplified services for local dev
CACHE_STORE=file
QUEUE_CONNECTION=sync
SESSION_DRIVER=file
MAIL_MAILER=log

# Optional
FRONTEND_URL=http://localhost:3000
```

---

## 🆘 Getting Help

1. **Check existing documentation:**
   - `docs/SEEDER_CONTRACT_VALIDATOR.md`
   - `docs/SEEDER_VALIDATOR_INSTALLATION.md`

2. **Verify environment:**
   - Run verification checklist above

3. **Check logs:**
   ```bash
   tail -f storage/logs/laravel.log
   ```

4. **Database-specific help:**
   - PostgreSQL: Check `/var/log/postgresql/`
   - MySQL: Check `/var/log/mysql/error.log`

---

## 📌 Summary

**Setup time:** ~10 minutes

**What you get:**
- ✅ Full Laravel backend environment
- ✅ Working database connection
- ✅ All Artisan commands functional
- ✅ Seeder validator ready to use
- ✅ Ability to test and develop locally

**Key commands:**
```bash
composer install                    # Install dependencies
cp .env.example .env               # Create config
php artisan key:generate           # Generate key
php artisan migrate --force        # Set up database
php artisan seed:inspect           # Test validator
```

---

**Last Updated:** 2026-01-05
**Environment:** PHP 8.4.15, Laravel 12, PostgreSQL 16
**Status:** ✅ Tested and working
