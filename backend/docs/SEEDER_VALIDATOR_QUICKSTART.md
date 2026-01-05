# Seeder Contract Validator - Quick Start Guide

## 🚀 5-Minute Setup

### 1. Run Validation

```bash
cd backend
php artisan seed:inspect
```

### 2. Interpret Results

**✅ Green (Success):**
```
✅ VALIDATION PASSED
All seeders satisfy database schema constraints.
→ Safe to run: php artisan db:seed
```
**Action:** Proceed with seeding.

---

**❌ Red (Failure):**
```
❌ VALIDATION FAILED
Total Violations: 5
Affected Tables: 3
```
**Action:** Fix violations before seeding.

---

### 3. Fix Violations

**Example violation:**
```
TABLE: users
SEEDER: UserSeeder::run (line 42)
MISSING: email_verified_at, status
CODE: User::create(['name' => 'Test', 'email' => 'test@test.com']);
```

**Fix:**
```php
User::create([
    'name' => 'Test',
    'email' => 'test@test.com',
    'email_verified_at' => now(),  // ✅ Add this
    'status' => 'active',          // ✅ Add this
]);
```

### 4. Revalidate

```bash
php artisan seed:inspect
```

Repeat until validation passes.

---

## 🛡️ Auto-Protection (Optional)

Add to `database/seeders/DatabaseSeeder.php`:

```php
use App\Services\Seeder\SeederGuard;

public function run(): void
{
    SeederGuard::validate(); // ✅ Add this line

    $this->call(RolesAndPermissionsSeeder::class);
    // ... rest of seeders
}
```

Now `php artisan db:seed` automatically validates first.

---

## 📋 Common Issues

| Error | Fix |
|-------|-----|
| "Database connection failed" | Run `php artisan migrate` first |
| "No seeder found for table X" | Create seeder OR add defaults to migration |
| False positive for factories | Ignore (static analysis limitation) |

---

## 🎯 When to Use

| Scenario | Command |
|----------|---------|
| Before running seeders | `php artisan seed:inspect` |
| After schema changes | `php artisan seed:inspect` |
| In CI/CD pipeline | `php artisan seed:inspect --format=json` |
| Before production deploy | `php artisan seed:inspect` |

---

## 📚 Full Documentation

See: `backend/docs/SEEDER_CONTRACT_VALIDATOR.md`

---

## ⚡ Pro Tips

1. **Run after every migration:** Ensure seeders stay in sync with schema
2. **Add to pre-commit hook:** Catch issues before push
3. **Use in CI/CD:** Block merges with seeder violations
4. **JSON format for automation:** `--format=json` for scripts

---

## 🆘 Emergency: Bypass Validation

**Only in development emergencies:**

```php
// In DatabaseSeeder.php, temporarily comment out:
// SeederGuard::validate(); // TODO: Fix violations

// Then run seeders
php artisan db:seed

// IMPORTANT: Uncomment and fix violations before committing!
```

**Never deploy with validation bypassed.**
