# Seeder-Schema Contract Validator

## 🎯 Purpose

Catch seeder failures **BEFORE** execution by validating that all database write operations provide required columns that have no defaults.

Eliminates the slow fix→rerun→fail loop in fintech production workflows where schema constraints cannot be weakened.

## 🏗️ Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                    php artisan seed:inspect                     │
└────────────────────────┬────────────────────────────────────────┘
                         │
         ┌───────────────┴───────────────┐
         ▼                               ▼
┌──────────────────┐            ┌──────────────────┐
│ SchemaInspector  │            │ SeederCodeScanner│
│                  │            │                  │
│ • Query DB       │            │ • Parse PHP      │
│ • Extract        │            │ • Detect writes  │
│   required       │            │ • Extract        │
│   columns        │            │   provided       │
│                  │            │   columns        │
└────────┬─────────┘            └────────┬─────────┘
         │                               │
         └───────────────┬───────────────┘
                         ▼
              ┌────────────────────┐
              │ ContractValidator  │
              │                    │
              │ • Compare          │
              │ • Generate         │
              │   violations       │
              │ • Format report    │
              └─────────┬──────────┘
                        │
                        ▼
                ┌───────────────┐
                │  Exit Code    │
                │  0 = Pass     │
                │  1 = Fail     │
                └───────────────┘
```

## 📦 Components

### 1. **ContractViolation** (DTO)
Immutable value object representing a violation:
- Table name
- Seeder class and method
- Missing required columns
- Code snippet
- Line number

### 2. **SchemaInspector** (Service)
Queries `INFORMATION_SCHEMA.COLUMNS` to extract:
- Columns where `IS_NULLABLE = 'NO'`
- Columns where `COLUMN_DEFAULT IS NULL`
- Excludes auto-managed columns (`id`, timestamps)

### 3. **SeederCodeScanner** (Service)
Token-based PHP parser that detects:
- `Model::create([...])`
- `Model::firstOrCreate([...], [...])`
- `DB::table('name')->insert([...])`
- `$model->create([...])`

Extracts array keys being provided per table.

### 4. **SeederContractValidator** (Orchestrator)
Coordinates inspection and scanning:
- Compares required vs provided columns
- Generates `ContractViolation` instances
- Formats human-readable reports

### 5. **InspectSeederCommand** (Artisan)
User-facing command:
```bash
php artisan seed:inspect
```

### 6. **SeederGuard** (Integration Hook)
Guard method for `DatabaseSeeder::run()`:
```php
SeederGuard::validate();
```

## 🚀 Installation & Usage

### Step 1: Verify Files

All files should be created in:
```
backend/
├── app/
│   ├── Console/Commands/
│   │   └── InspectSeederCommand.php
│   └── Services/Seeder/
│       ├── ContractViolation.php
│       ├── SchemaInspector.php
│       ├── SeederCodeScanner.php
│       ├── SeederContractValidator.php
│       └── SeederGuard.php
└── docs/
    └── SEEDER_CONTRACT_VALIDATOR.md
```

### Step 2: Run Inspection

```bash
# Standard validation
php artisan seed:inspect

# Custom seeder path
php artisan seed:inspect --path=/custom/path/to/seeders

# JSON output (for CI/CD)
php artisan seed:inspect --format=json
```

### Step 3 (Optional): Integrate Guard

Edit `database/seeders/DatabaseSeeder.php`:

```php
<?php

namespace Database\Seeders;

use App\Services\Seeder\SeederGuard;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ✅ VALIDATE BEFORE ANY SEEDER EXECUTES
        SeederGuard::validate();

        // Proceed with seeders only if validation passed
        $this->call(RolesAndPermissionsSeeder::class);
        $this->call(SettingsSeeder::class);
        // ... rest of seeders
    }
}
```

Now `php artisan db:seed` will **automatically** validate contracts first.

## 📊 Example Output

### ✅ Success Case

```
╔═══════════════════════════════════════════════════════════════════════════╗
║              SEEDER-SCHEMA CONTRACT VALIDATOR                              ║
╚═══════════════════════════════════════════════════════════════════════════╝

🔍 Scanning database schema...
📄 Analyzing seeder files...

✅ VALIDATION PASSED

All seeders satisfy database schema constraints.
No required columns are missing.

→ Safe to run: php artisan db:seed
```

### ❌ Failure Case

```
╔═══════════════════════════════════════════════════════════════════════════╗
║              SEEDER-SCHEMA CONTRACT VALIDATOR                              ║
╚═══════════════════════════════════════════════════════════════════════════╝

🔍 Scanning database schema...
📄 Analyzing seeder files...

❌ VALIDATION FAILED

CRITICAL: Seeders are missing required columns.

SUMMARY:
┌─────────────────────┬───────┐
│ Metric              │ Count │
├─────────────────────┼───────┤
│ Total Violations    │ 3     │
│ Affected Tables     │ 2     │
│ Affected Seeders    │ 2     │
└─────────────────────┴───────┘

VIOLATIONS:

────────────────────────────────────────────────────────────────────────────
TABLE: users (2 violation(s))
────────────────────────────────────────────────────────────────────────────

┌─────────────────────────────────────────────────────────
│ TABLE: users
│ SEEDER: UserSeeder::run (line 42)
│ MISSING REQUIRED COLUMNS: email_verified_at, status
│ CODE:
│   User::create([
│       'name' => 'Admin User',
│       'email' => 'admin@preiposip.com',
│       'password' => Hash::make('password'),
│   ]);
└─────────────────────────────────────────────────────────

────────────────────────────────────────────────────────────────────────────
TABLE: plans (1 violation(s))
────────────────────────────────────────────────────────────────────────────

┌─────────────────────────────────────────────────────────
│ TABLE: plans
│ SEEDER: PlanSeeder::seedBasicPlans (line 89)
│ MISSING REQUIRED COLUMNS: category_id, visibility
│ CODE:
│   Plan::create([
│       'name' => 'Gold Plan',
│       'amount' => 5000,
│       'duration_months' => 12,
│   ]);
└─────────────────────────────────────────────────────────

═══════════════════════════════════════════════════════════════════════════

RESOLUTION STEPS:
  1. Review each violation above
  2. Add missing columns to seeder create/insert arrays
  3. OR add database defaults (NOT recommended for fintech)
  4. Re-run: php artisan seed:inspect

⚠️  WARNING: Running seeders with these violations will cause database errors.
```

## 🔧 Resolution Workflow

### Example Violation

**Table:** `users`
**Missing:** `email_verified_at`, `status`
**Seeder:** `UserSeeder::run (line 42)`

### ✅ Fix 1: Add Missing Columns (Recommended)

```php
// Before (FAILS)
User::create([
    'name' => 'Admin User',
    'email' => 'admin@preiposip.com',
    'password' => Hash::make('password'),
]);

// After (PASSES)
User::create([
    'name' => 'Admin User',
    'email' => 'admin@preiposip.com',
    'password' => Hash::make('password'),
    'email_verified_at' => now(),           // ✅ Added
    'status' => 'active',                   // ✅ Added
]);
```

### ⚠️ Fix 2: Add Database Default (NOT Recommended for Fintech)

```php
// Migration (NOT RECOMMENDED)
$table->string('status')->default('pending'); // Weakens contract
```

**Why NOT recommended:**
- Violates "Zero Hardcoded Values" philosophy
- Hides business logic in schema instead of application layer
- Makes seeder behavior implicit and harder to audit

### ✅ Fix 3: Make Column Nullable (If Truly Optional)

```php
// Migration (ONLY if column is genuinely optional)
$table->string('middle_name')->nullable();
```

## 🎯 CI/CD Integration

### GitHub Actions

```yaml
name: Validate Seeders

on: [push, pull_request]

jobs:
  validate-seeders:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: 8.3

      - name: Install Dependencies
        run: composer install --no-dev

      - name: Setup Database
        run: |
          php artisan migrate --force

      - name: Validate Seeder Contracts
        run: php artisan seed:inspect --format=json
```

### GitLab CI

```yaml
seeder-validation:
  stage: test
  script:
    - composer install --no-dev
    - php artisan migrate --force
    - php artisan seed:inspect --format=json
  only:
    - merge_requests
    - main
```

## 🧪 Testing the Validator

### Manual Test: Create Intentional Violation

1. **Create a test migration** with required field:

```bash
php artisan make:migration add_test_validation_table
```

```php
// Migration
Schema::create('test_validations', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('required_field'); // NO default, NOT nullable
    $table->timestamps();
});
```

2. **Create a seeder** that omits the field:

```bash
php artisan make:seeder TestValidationSeeder
```

```php
// Seeder
public function run(): void
{
    DB::table('test_validations')->insert([
        'name' => 'Test Record',
        // Missing: 'required_field'
    ]);
}
```

3. **Run migrations and validation**:

```bash
php artisan migrate
php artisan seed:inspect
```

4. **Expected output**: Violation detected for `test_validations.required_field`

5. **Fix and revalidate**:

```php
// Fixed seeder
DB::table('test_validations')->insert([
    'name' => 'Test Record',
    'required_field' => 'test value', // ✅ Added
]);
```

```bash
php artisan seed:inspect  # Should pass
```

## 🚨 Important Notes

### What This Validates

✅ **Validates:**
- Required columns (NOT NULL, no default)
- Static column assignments in seeders
- Model::create(), DB::table()->insert(), firstOrCreate()

❌ **Does NOT Validate:**
- Runtime-computed values
- Dynamic column assignment
- Columns with database defaults
- Nullable columns
- Auto-increment/timestamp columns

### Limitations

1. **Static Analysis Only**: Cannot detect columns assigned via:
   ```php
   $data = ['name' => 'test'];
   if ($condition) {
       $data['status'] = 'active';
   }
   Model::create($data);
   ```

2. **Model Table Resolution**: Uses Laravel naming conventions (`User` → `users`). Custom table names in models may not be detected.

3. **Factory Usage**: Seeders using factories may show false positives if the scanner can't parse factory definitions.

### Workarounds

**For dynamic assignments**, add a comment:
```php
// @seeder-contract-ignore
Model::create($dynamicData);
```

Then manually verify via:
```bash
php artisan db:seed --class=SpecificSeeder
```

## 📚 Advanced Usage

### Validate Specific Seeder Path

```bash
php artisan seed:inspect --path=database/seeders/production
```

### Programmatic Validation

```php
use App\Services\Seeder\SeederContractValidator;

$validator = new SeederContractValidator();
$violations = $validator->validate();

if (!empty($violations)) {
    foreach ($violations as $violation) {
        Log::error('Seeder violation', $violation->toArray());
    }
}
```

### Non-Throwing Check

```php
use App\Services\Seeder\SeederGuard;

if (!SeederGuard::check()) {
    $report = SeederGuard::report();
    Mail::to('devops@preiposip.com')->send(new SeederValidationFailed($report));
}
```

## 🎓 Philosophy

This validator enforces the **"Contract-Driven Seeding"** principle:

> Database schema defines a contract.
> Seeders must explicitly fulfill that contract.
> No assumptions. No defaults. No surprises.

Perfect for:
- **Fintech platforms** where data integrity is non-negotiable
- **Regulated industries** requiring audit trails
- **Production deployments** where seeder failures are unacceptable
- **Large teams** where schema changes must be coordinated with seeders

## 🆘 Troubleshooting

### "Database connection failed"

**Cause:** Database not configured or migrations not run.

**Fix:**
```bash
php artisan migrate
php artisan seed:inspect
```

### "No seeder found for table 'X'"

**Cause:** Table has required columns but no seeder writes to it.

**Fix:** Either:
1. Create a seeder for that table, or
2. Add defaults to the migration (if appropriate)

### "False positive for factory-based seeders"

**Cause:** Scanner can't parse factory definitions.

**Fix:** Run actual seeder to verify:
```bash
php artisan db:seed --class=SuspectedSeeder
```

If it works, the validator has a false positive (acceptable for static analysis).

## 📝 Changelog

### v1.0.0 (2026-01-05)
- Initial implementation
- Support for Model::create(), firstOrCreate(), DB::table()->insert()
- Text and JSON output formats
- SeederGuard integration hook
- Comprehensive violation reporting

## 📄 License

Internal tool for PreIPOsip platform. Not for external distribution.

## 👥 Support

For issues or questions:
- **Internal**: #engineering-backend on Slack
- **Documentation**: `backend/docs/SEEDER_CONTRACT_VALIDATOR.md`
- **Code**: `backend/app/Services/Seeder/`
