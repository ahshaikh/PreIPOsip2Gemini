# P0 Fixes - Deployment Notes

## FIX 3: Reconciliation Service - Cron Setup

### Manual Cron Configuration (if Laravel Scheduler not configured)

Add to crontab:
```bash
# Run reconciliation daily at 2:00 AM
0 2 * * * cd /path/to/backend && php artisan reconciliation:run >> /var/log/reconciliation.log 2>&1
```

### Laravel Scheduler Configuration

If using Laravel's task scheduler, add to your scheduler configuration:

**For Laravel 10:**
In `app/Console/Kernel.php`:
```php
protected function schedule(Schedule $schedule)
{
    $schedule->command('reconciliation:run')
        ->dailyAt('02:00')
        ->name('daily-reconciliation')
        ->onFailure(function () {
            // Alert on failure
        });
}
```

**For Laravel 11:**
In `routes/console.php` or `bootstrap/app.php`:
```php
Schedule::command('reconciliation:run')
    ->dailyAt('02:00')
    ->name('daily-reconciliation');
```

### Manual Run

Test the reconciliation:
```bash
php artisan reconciliation:run
```

Force run even if already run today:
```bash
php artisan reconciliation:run --force
```

---

## All P0 Fixes Summary

### FIX 1: Wallet Locking ✅
- **File:** `app/Services/WalletService.php`
- **Methods Added:** `lockFunds()`, `unlockFunds()`, `debitLockedFunds()`
- **Status:** Complete

### FIX 2: BulkPurchase Immutability ✅
- **Files:**
  - `app/Observers/BulkPurchaseObserver.php`
  - `app/Providers/EventServiceProvider.php`
- **Status:** Complete

### FIX 3: Reconciliation Service ✅
- **Files:**
  - `app/Services/ReconciliationService.php`
  - `app/Console/Commands/RunReconciliation.php`
  - `database/migrations/2026_01_07_000001_create_reconciliation_logs_table.php`
- **Status:** Complete (needs scheduler configuration)

### FIX 4: Saga Execution Tracking ✅
- **File:** `app/Services/PaymentAllocationSaga.php`
- **Features:**
  - Crash-safe payment processing
  - Automatic rollback on failure
  - Manual resolution dashboard
  - Saga recovery on startup
- **Status:** Complete

---

## Testing Commands

### Test Wallet Locking
```bash
php artisan tinker
$user = User::first();
$walletService = app(App\Services\WalletService::class);
$walletService->lockFunds($user, 1000, 'Test lock');
// Check: $user->wallet->locked_balance_paise should be 100000
```

### Test BulkPurchase Immutability
```bash
php artisan tinker
$bulk = BulkPurchase::first();
$bulk->face_value_purchased = 99999; // This should throw RuntimeException
$bulk->save();
```

### Test Reconciliation
```bash
php artisan reconciliation:run
```

### Test Saga
```bash
php artisan tinker
$payment = Payment::where('status', 'paid')->first();
$saga = app(App\Services\PaymentAllocationSaga::class);
$result = $saga->execute($payment);
```

---

## Deployment Checklist

- [ ] Run migrations: `php artisan migrate`
- [ ] Clear config cache: `php artisan config:clear`
- [ ] Test wallet locking in staging
- [ ] Test BulkPurchase edit protection
- [ ] Run manual reconciliation: `php artisan reconciliation:run --force`
- [ ] Review reconciliation results
- [ ] Configure cron for daily reconciliation at 2 AM
- [ ] Monitor logs for saga failures
- [ ] Set up admin alerts for reconciliation failures
- [ ] Document manual saga resolution procedures
