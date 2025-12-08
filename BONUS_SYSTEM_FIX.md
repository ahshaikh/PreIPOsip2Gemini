# Bonus System Bug Fix - Complete Guide

## The Problem

Users were not receiving bonuses (welcome, milestone, loyalty, cashback) even after completing successful payments. The "My Bonuses" page showed ₹0 for all bonus types.

## Root Cause

The `PaymentController::verify()` method was marking payments as 'paid' but never dispatching the `ProcessSuccessfulPaymentJob`, which is responsible for:
- Calculating and awarding bonuses
- Crediting wallet with payment + bonus amounts
- Allocating shares
- Processing referrals

## The Fix

**Commit:** `122333d` - fix: Critical bonus system bug - bonuses not awarded on user payments

**File Changed:** `backend/app/Http/Controllers/Api/User/PaymentController.php`

Added line 219:
```php
ProcessSuccessfulPaymentJob::dispatch($payment);
```

## Queue Configuration

The bonus system uses Laravel queues. Ensure the queue worker is running:

### Development
```bash
php artisan queue:work
```

### Production (Supervisor)
Create `/etc/supervisor/conf.d/laravel-worker.conf`:
```ini
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/path/to/storage/logs/worker.log
stopwaitsecs=3600
```

Then:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start laravel-worker:*
```

## Backfilling Missing Bonuses

For users who completed payments BEFORE this fix, run the backfill command:

### Dry Run (Preview)
```bash
php artisan bonuses:backfill --dry-run
```

### Process All Missing Bonuses
```bash
php artisan bonuses:backfill
```

### Process Specific Payment
```bash
php artisan bonuses:backfill --payment-id=123
```

## Verification Steps

### 1. Check If Bonuses Are Being Awarded

After a user completes a payment:

```sql
-- Check if bonus was created
SELECT * FROM bonus_transactions
WHERE user_id = ?
ORDER BY created_at DESC
LIMIT 5;

-- Check if wallet was credited
SELECT * FROM transactions
WHERE user_id = ?
AND type IN ('payment_received', 'bonus_credit', 'share_purchase')
ORDER BY created_at DESC
LIMIT 10;
```

### 2. Monitor Queue Jobs

```bash
# Watch queue in real-time
php artisan queue:work --verbose

# Check failed jobs
php artisan queue:failed
```

### 3. Test New Payment Flow

1. Login as test user
2. Subscribe to a plan
3. Complete payment via Razorpay (test mode)
4. Check "My Bonuses" page - should show welcome bonus (default ₹500)
5. Check database:
   ```sql
   SELECT * FROM bonus_transactions WHERE user_id = ?;
   ```

## Bonus Types & When They're Awarded

| Bonus Type | When Awarded | Default Amount | Setting Key |
|-----------|--------------|----------------|-------------|
| **Welcome Bonus** | First payment only | ₹500 | `welcome_bonus_enabled` |
| **Milestone Bonus** | At 6, 12, 24, 36 months | Configurable per plan | `milestone_bonus_enabled` |
| **Progressive/Loyalty** | Every payment after month 3 | Increases monthly | `progressive_bonus_enabled` |
| **Consistency/Cashback** | On-time payments | Fixed per payment | `consistency_bonus_enabled` |
| **Referral Bonus** | When referred user pays | Configurable | `referral_bonus_enabled` |
| **Celebration Bonus** | Birthdays/Anniversaries | Configurable | `celebration_bonus_enabled` |
| **Lucky Draw** | Random selection | Varies | `lucky_draw_enabled` |

## Settings Verification

Check all bonus settings are enabled:

```sql
SELECT * FROM settings
WHERE key LIKE '%bonus%'
ORDER BY key;
```

All should be set to `'true'` (string). If missing, run:

```bash
php artisan db:seed --class=SettingsSeeder
```

## Common Issues

### Issue: Bonuses still showing ₹0 after fix

**Check:**
1. Is queue worker running? `php artisan queue:work`
2. Are jobs failing? `php artisan queue:failed`
3. Did you backfill old payments? `php artisan bonuses:backfill`

### Issue: Welcome bonus not awarded

**Check:**
1. Is this the user's first payment?
   ```sql
   SELECT COUNT(*) FROM payments WHERE user_id = ? AND status = 'paid';
   ```
2. Is welcome bonus enabled?
   ```sql
   SELECT value FROM settings WHERE key = 'welcome_bonus_enabled';
   ```

### Issue: Queue jobs not processing

**Check:**
1. Queue driver in `.env`: `QUEUE_CONNECTION=database`
2. Jobs table exists: `php artisan queue:table && php artisan migrate`
3. No stuck jobs: `SELECT * FROM jobs ORDER BY id DESC LIMIT 10;`

## Testing in Production

### Before Deployment
```bash
# 1. Run migrations
php artisan migrate

# 2. Verify settings
php artisan tinker
>>> setting('welcome_bonus_enabled')
=> "true"

# 3. Restart queue workers
sudo supervisorctl restart laravel-worker:*
```

### After Deployment
```bash
# 1. Backfill historical bonuses
php artisan bonuses:backfill --dry-run
php artisan bonuses:backfill

# 2. Monitor queue
tail -f storage/logs/worker.log

# 3. Verify first new payment
# Complete a test payment and check bonus_transactions table
```

## Support

If bonuses are still not working after following this guide:

1. Check Laravel logs: `storage/logs/laravel.log`
2. Check queue logs: `storage/logs/worker.log`
3. Run diagnostics:
   ```bash
   php artisan queue:monitor
   php artisan queue:failed
   ```
4. Verify payment flow end-to-end with logging enabled

---

**Last Updated:** 2025-12-08
**Fix Version:** v1.0
**Related Commits:** 122333d
