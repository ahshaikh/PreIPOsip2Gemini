# P3 (Low Priority) Fixes - Deployment Notes

## Overview

This document covers the deployment of 6 P3 (Low Priority) fixes from the platform audit. These fixes enhance regulatory compliance, user experience, and code quality.

## Summary of Changes

| Fix | Priority | Description | Files Changed |
|-----|----------|-------------|---------------|
| FIX 12 | P3 | Campaign Approval Database Constraint | 2 files |
| FIX 13 | P3 | TDS Reporting Module | 4 files |
| FIX 14 | P3 | User Transaction Statement Generator | 4 files |
| FIX 15 | P3 | Email Notification System (Queued Jobs) | 4 files |
| FIX 16 | P3 | Rate Limiting for Public Endpoints | 3 files |
| FIX 17 | P3 | State Machine Pattern | 2 files |

**Total:** 19 files created/modified

---

## FIX 12: Campaign Approval Database Constraint

### What Changed
- Added database CHECK constraint preventing campaigns from being active without approval
- Added model-level validation in `Campaign::boot()`
- Added `Campaign::approve()` method with audit logging
- Added composite index for query performance

### Deployment Steps

1. **Run Migration:**
   ```bash
   php artisan migrate
   ```

2. **Verify Constraint:**
   ```bash
   php artisan tinker
   >>> $campaign = Campaign::create(['is_active' => true, 'approved_at' => null]);
   # Should throw exception: "Campaign cannot be activated without approval"
   ```

3. **Admin Panel Update:**
   - Ensure campaign management UI enforces approval workflow
   - Add "Approve" button for pending campaigns

### Testing

```bash
# Test constraint enforcement
php artisan test --filter CampaignApprovalTest

# Check existing campaigns
SELECT id, title, is_active, approved_at
FROM campaigns
WHERE is_active = 1 AND approved_at IS NULL;
# Should return 0 rows
```

### Rollback

```bash
php artisan migrate:rollback --step=1
```

---

## FIX 13: TDS Reporting Module

### What Changed
- Created `tds_deductions` table with paise precision
- Created `tds_quarterly_returns` table for compliance tracking
- Added `TdsDeduction` model with monetary field helpers
- Added `TdsService` for calculations and reporting
- Created Form 16A PDF template

### Deployment Steps

1. **Run Migration:**
   ```bash
   php artisan migrate
   ```

2. **Configure TDS Settings:**
   - Update `.env` with company tax details:
   ```env
   COMPANY_TAN=ABCD12345E
   COMPANY_PAN=ABCDE1234F
   COMPANY_ADDRESS="Your Company Address"
   COMPANY_AUTHORIZED_SIGNATORY="Signatory Name"
   ```

3. **Integrate with Withdrawal Flow:**
   ```php
   // In WithdrawalController::approve()
   $tdsService = app(TdsService::class);
   $tdsCalculation = $tdsService->calculateTds($user, $withdrawal->amount, 'withdrawal');

   if ($tdsCalculation['applicable']) {
       // Record TDS deduction
       $tdsService->recordDeduction(
           $user,
           'withdrawal',
           $withdrawal->id,
           $withdrawal->amount,
           $tdsCalculation['tds_amount'],
           $tdsCalculation['tds_rate'],
           $tdsCalculation['section_code']
       );
   }
   ```

### Testing

```bash
# Test TDS calculation
php artisan tinker
>>> $service = app(App\Services\TdsService::class);
>>> $user = User::find(1);
>>> $tds = $service->calculateTds($user, 50000, 'withdrawal');
>>> dump($tds);
# Should show TDS calculation with rate and amounts

# Test Form 16A generation
>>> $pdf = $service->generateForm16A($user, '2023-24');
>>> dump($pdf);
# Should return PDF path
```

### Quarterly Tasks

Add to cron schedule:
```php
// app/Console/Kernel.php
protected function schedule(Schedule $schedule)
{
    // Generate quarterly TDS reports on 5th of month following quarter end
    $schedule->call(function () {
        $service = app(TdsService::class);
        // Generate report for previous quarter
    })->quarterly()->monthlyOn(5, '09:00');
}
```

---

## FIX 14: User Transaction Statement Generator

### What Changed
- Created `StatementGeneratorService` for PDF generation
- Created transaction statement PDF template
- Integrated with `ReportsController`
- Created console command for batch generation

### Deployment Steps

1. **Install PDF Library (if not already):**
   ```bash
   composer require barryvdh/laravel-dompdf
   ```

2. **Publish PDF Config:**
   ```bash
   php artisan vendor:publish --provider="Barryvdh\DomPDF\ServiceProvider"
   ```

3. **Test Statement Generation:**
   ```bash
   php artisan tinker
   >>> $service = app(App\Services\StatementGeneratorService::class);
   >>> $user = User::find(1);
   >>> $start = Carbon::parse('2024-01-01');
   >>> $end = Carbon::parse('2024-01-31');
   >>> $path = $service->generateStatement($user, $start, $end);
   >>> dump($path);
   # Should return path to PDF
   ```

4. **Schedule Monthly Statements:**
   ```php
   // app/Console/Kernel.php
   protected function schedule(Schedule $schedule)
   {
       // Generate monthly statements on 1st of each month
       $schedule->command('statements:generate-monthly')
           ->monthlyOn(1, '02:00');
   }
   ```

5. **Configure Storage:**
   - Ensure `storage/app/statements` directory is writable
   - Add to `.gitignore`: `storage/app/statements/*`

### Testing

```bash
# Manual statement generation
php artisan statements:generate-monthly --user=1

# Test cleanup
php artisan tinker
>>> $service = app(App\Services\StatementGeneratorService::class);
>>> $deleted = $service->cleanupOldStatements();
>>> dump($deleted);
```

---

## FIX 15: Email Notification System (Queued Jobs)

### What Changed
- Created `SendEmailNotification` job implementing ShouldQueue
- Created email layout template
- Created specific email templates (KYC approved, withdrawal approved, etc.)
- All emails now sent asynchronously via queue

### Deployment Steps

1. **Configure Queue Driver:**
   ```env
   # For production, use Redis or database
   QUEUE_CONNECTION=redis

   # For development
   QUEUE_CONNECTION=sync
   ```

2. **Run Queue Worker (Production):**
   ```bash
   # Using Supervisor (recommended)
   sudo supervisorctl start laravel-worker:*

   # Or manually
   php artisan queue:work --queue=emails --tries=3 --timeout=60
   ```

3. **Update Controllers to Use Queued Emails:**
   ```php
   // OLD (synchronous)
   Mail::to($user->email)->send(new KycApprovedMail($user));

   // NEW (queued)
   SendEmailNotification::kycApproved($user->email, $user->name, $user->id);
   ```

4. **Configure Email Settings:**
   ```env
   MAIL_MAILER=smtp
   MAIL_HOST=smtp.mailtrap.io
   MAIL_PORT=2525
   MAIL_USERNAME=your_username
   MAIL_PASSWORD=your_password
   MAIL_ENCRYPTION=tls
   MAIL_FROM_ADDRESS=noreply@preiposip.com
   MAIL_FROM_NAME="PreIPOsip"
   ```

### Testing

```bash
# Test email job dispatch
php artisan tinker
>>> SendEmailNotification::welcome('test@example.com', 'Test User', ['user_id' => 1]);

# Check queue
>>> DB::table('jobs')->count();

# Process queue
>>> php artisan queue:work --once

# Check failed jobs
>>> DB::table('failed_jobs')->get();
```

### Monitoring

```bash
# Monitor queue in real-time
php artisan queue:listen --verbose

# Check queue status
php artisan queue:failed

# Retry failed jobs
php artisan queue:retry all
```

---

## FIX 16: Rate Limiting for Public Endpoints

### What Changed
- Created `ThrottlePublicApi` middleware
- Applied to authentication and password reset endpoints
- Different limits per endpoint type

### Deployment Steps

1. **No migration required** - Middleware only

2. **Configure Rate Limits (Optional):**
   - Edit `ThrottlePublicApi` middleware to adjust limits:
   ```php
   // Login/Register: 5/min (default)
   // Password reset: 3/15min
   // OTP: 10/hour
   ```

3. **Clear Route Cache:**
   ```bash
   php artisan route:clear
   php artisan route:cache
   ```

### Testing

```bash
# Test rate limiting
curl -X POST http://localhost:8000/api/v1/login \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com","password":"wrong"}' \
  --repeat 6

# 6th request should return 429 Too Many Requests
```

### Monitoring

```bash
# Check rate limit hits in logs
tail -f storage/logs/laravel.log | grep "throttle"

# Monitor Redis (if using Redis cache)
redis-cli KEYS "laravel_cache:throttle:*"
```

---

## FIX 17: State Machine Pattern

### What Changed
- Created `HasStateMachine` trait
- Comprehensive documentation with examples
- Can be applied to any model with state transitions

### Deployment Steps

1. **No migration required** - Trait only

2. **Integrate with Existing Models:**

   **Example: Withdrawal Model**
   ```php
   use App\Models\Traits\HasStateMachine;

   class Withdrawal extends Model
   {
       use HasStateMachine;

       protected static $stateConfig = [
           'field' => 'status',
           'states' => ['pending', 'approved', 'completed', 'rejected', 'cancelled'],
           'transitions' => [
               'approve' => [
                   'from' => ['pending'],
                   'to' => 'approved',
                   'label' => 'Approve Withdrawal',
               ],
               // ... more transitions
           ],
       ];
   }
   ```

3. **Update Controllers:**
   ```php
   // OLD
   $withdrawal->update(['status' => 'approved']);

   // NEW
   $withdrawal->transitionTo('approved', [
       'approved_by' => auth()->id(),
   ]);
   ```

### Testing

```bash
php artisan test --filter StateTransitionTest
```

### Documentation

See `backend/docs/STATE_MACHINE_USAGE_EXAMPLE.md` for complete integration guide.

---

## General Deployment Checklist

### Pre-Deployment

- [ ] Backup database
- [ ] Run tests: `php artisan test`
- [ ] Clear all caches:
  ```bash
  php artisan config:clear
  php artisan cache:clear
  php artisan route:clear
  php artisan view:clear
  ```

### Deployment

- [ ] Pull latest code
- [ ] Run migrations: `php artisan migrate`
- [ ] Install dependencies: `composer install --no-dev`
- [ ] Optimize:
  ```bash
  php artisan config:cache
  php artisan route:cache
  php artisan view:cache
  ```
- [ ] Restart queue workers: `sudo supervisorctl restart laravel-worker:*`
- [ ] Restart PHP-FPM: `sudo systemctl restart php8.3-fpm`

### Post-Deployment

- [ ] Verify migrations applied
- [ ] Test critical flows (login, payment, withdrawal)
- [ ] Monitor error logs: `tail -f storage/logs/laravel.log`
- [ ] Monitor queue: `php artisan queue:monitor`
- [ ] Check email delivery

### Rollback Plan

If issues occur:

```bash
# Rollback migrations
php artisan migrate:rollback --step=2

# Clear caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear

# Revert to previous git commit
git checkout [previous-commit-hash]
composer install
```

---

## Environment Variables Required

Add to `.env`:

```env
# TDS Configuration (FIX 13)
COMPANY_TAN=ABCD12345E
COMPANY_PAN=ABCDE1234F
COMPANY_ADDRESS="Your Company Address"
COMPANY_AUTHORIZED_SIGNATORY="Authorized Signatory Name"

# Email Configuration (FIX 15)
QUEUE_CONNECTION=redis
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_FROM_ADDRESS=noreply@preiposip.com
MAIL_FROM_NAME="PreIPOsip"

# Queue Configuration
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

---

## Performance Impact

| Fix | Performance Impact | Notes |
|-----|-------------------|-------|
| FIX 12 | Minimal | Single CHECK constraint |
| FIX 13 | Low | Only affects withdrawal flow |
| FIX 14 | Low | PDF generation queued |
| FIX 15 | **Positive** | Non-blocking email sending |
| FIX 16 | Minimal | Redis-based rate limiting |
| FIX 17 | Minimal | In-memory state validation |

**Overall:** Positive impact on user experience due to queued jobs.

---

## Monitoring & Alerts

### Key Metrics to Monitor

1. **Queue Health (FIX 15):**
   - Queue depth: `SELECT COUNT(*) FROM jobs WHERE queue = 'emails'`
   - Failed jobs: `SELECT COUNT(*) FROM failed_jobs`
   - Alert if failed jobs > 10

2. **Rate Limiting (FIX 16):**
   - Monitor 429 responses
   - Alert if 429 rate > 5% of traffic

3. **TDS Deductions (FIX 13):**
   - Daily TDS total
   - Quarterly compliance reports

4. **Statement Generation (FIX 14):**
   - Success rate of monthly batch
   - Storage usage

---

## Support & Troubleshooting

### Common Issues

**Issue: Queue Not Processing**
```bash
# Check queue worker status
sudo supervisorctl status laravel-worker:*

# Restart queue worker
sudo supervisorctl restart laravel-worker:*

# Check for errors
tail -f storage/logs/laravel.log
```

**Issue: Emails Not Sending**
```bash
# Check email queue
php artisan queue:work emails --once

# Test email configuration
php artisan tinker
>>> Mail::raw('Test', function($msg) {
       $msg->to('test@example.com')->subject('Test');
   });
```

**Issue: PDF Generation Fails**
```bash
# Check PDF library
composer show barryvdh/laravel-dompdf

# Check storage permissions
ls -la storage/app/statements

# Test PDF generation
php artisan tinker
>>> $service = app(App\Services\StatementGeneratorService::class);
>>> $user = User::find(1);
>>> $pdf = $service->generateStatement($user, now()->subMonth(), now());
```

---

## Audit Trail

All P3 fixes include comprehensive audit logging:

```sql
-- View all state transitions
SELECT * FROM audit_logs
WHERE action LIKE '%.state_transition'
ORDER BY created_at DESC
LIMIT 100;

-- View TDS deductions
SELECT * FROM audit_logs
WHERE action = 'email.sent'
ORDER BY created_at DESC
LIMIT 100;

-- View campaign approvals
SELECT * FROM audit_logs
WHERE action = 'campaign.approved'
ORDER BY created_at DESC;
```

---

## Success Criteria

✅ All migrations applied without errors
✅ Queue workers processing emails successfully
✅ Rate limiting preventing abuse
✅ PDF statements generating correctly
✅ TDS calculations accurate
✅ State transitions enforced
✅ No performance degradation
✅ All tests passing

---

## Contact

For issues or questions:
- **Email:** support@preiposip.com
- **Documentation:** `/backend/docs/`
- **Logs:** `storage/logs/laravel.log`

---

**Document Version:** 1.0
**Last Updated:** 2026-01-07
**Author:** Claude (Platform Audit Implementation)
