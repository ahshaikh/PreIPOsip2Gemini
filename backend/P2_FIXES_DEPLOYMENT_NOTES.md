# P2 Fixes - Deployment Notes

## Overview
All P2 (Medium Priority) fixes have been implemented and committed to the `claude/audit-preipopsip-platform-MNnCa` branch.

**Risk Level:** LOW - All changes are additive and backward-compatible

**Target:** Deploy within 2 weeks

---

## FIX 9: Laravel Policies for Authorization ✅

### What It Does
Provides resource-level authorization using Laravel's Policy system. Replaces manual permission checks with testable, reusable policy methods.

### Files Created
- `app/Policies/DealPolicy.php` (NEW)
- `app/Policies/BulkPurchasePolicy.php` (NEW)
- `app/Policies/ShareListingPolicy.php` (NEW)
- `app/Providers/AuthServiceProvider.php` (NEW)
- `bootstrap/providers.php` (MODIFIED - registered AuthServiceProvider)

### Key Features
- **DealPolicy:** Company users can only manage draft deals, admins can manage all
- **BulkPurchasePolicy:** Only admins can manage inventory, companies view their own
- **ShareListingPolicy:** Companies manage their listings, admins approve/reject
- Integrates with FIX 5 (company freeze) and FIX 6 (deal approval workflows)

### How to Use in Controllers
```php
// Instead of manual checks
if (!$user->can('deals.edit')) {
    abort(403);
}

// Use policy authorization
$this->authorize('update', $deal);
$this->authorize('approve', $deal);
```

### Testing
```bash
# 1. Test deal policy - company user
php artisan tinker
$companyUser = User::where('roles.name', 'company')->first();
auth()->login($companyUser);
$deal = Deal::where('company_id', $companyUser->companyUser->company_id)->where('status', 'draft')->first();
Gate::authorize('update', $deal); // Should pass
$otherDeal = Deal::where('company_id', '!=', $companyUser->companyUser->company_id)->first();
Gate::authorize('update', $otherDeal); // Should throw AuthorizationException

# 2. Test deal policy - admin user
$admin = User::role('admin')->first();
auth()->login($admin);
Gate::authorize('update', $deal); // Should pass
Gate::authorize('approve', $deal); // Should pass

# 3. Test bulk purchase policy
Gate::authorize('view', BulkPurchase::first()); // Admin should pass
Gate::authorize('delete', BulkPurchase::first()); // Should fail (only super-admin)

# 4. Test share listing policy
Gate::authorize('approve', CompanyShareListing::where('status', 'pending')->first()); // Admin should pass
```

### Migration Path
1. **Phase 1** (Immediate): Policies registered, controllers can optionally use them
2. **Phase 2** (Week 1): Update critical controllers to use `$this->authorize()`
3. **Phase 3** (Week 2): Remove manual permission checks, rely fully on policies
4. **Phase 4** (Week 3): Write policy tests, achieve 100% policy coverage

### Rollback Plan
```bash
# Remove AuthServiceProvider from bootstrap/providers.php
# Delete policy files
rm -f backend/app/Policies/*.php
rm -f backend/app/Providers/AuthServiceProvider.php
```

---

## FIX 10: Migrate All Monetary Fields to Integer Paise ✅

### What It Does
Adds integer `_paise` fields alongside existing decimal fields to eliminate floating-point precision errors in financial calculations. Backward-compatible approach allows gradual migration.

### Files Created
- `database/migrations/2026_01_07_100003_migrate_monetary_fields_to_paise.php` (NEW)
- `app/Models/Traits/HasMonetaryFields.php` (NEW)

### Tables Modified
1. **payments:** `amount_paise`
2. **withdrawals:** `amount_paise`, `fee_paise`, `tds_deducted_paise`, `net_amount_paise`
3. **bulk_purchases:** `face_value_purchased_paise`, `actual_cost_paid_paise`, `total_value_received_paise`, `value_remaining_paise`
4. **user_investments:** `value_allocated_paise`
5. **subscriptions:** `amount_paise`

### Migration Strategy
```
Old Way (Decimal):
  amount = 100.50 (DECIMAL(10,2))

New Way (Integer Paise):
  amount_paise = 10050 (BIGINT)
  amount = 100.50 (kept for backward compatibility)
```

### How to Use HasMonetaryFields Trait
```php
// In your model
use App\Models\Traits\HasMonetaryFields;

class Payment extends Model
{
    use HasMonetaryFields;

    protected $monetaryFields = ['amount'];
}

// Usage
$payment = new Payment();
$payment->setAmountFromRupees('amount', 100.50); // Sets amount_paise = 10050, amount = 100.50
$amount = $payment->getAmountInPaise('amount'); // Returns 10050
$amount = $payment->getAmountInRupees('amount'); // Returns 100.50
$formatted = $payment->formatAmount('amount'); // Returns "₹100.50"
```

### Testing
```bash
# 1. Run migration
php artisan migrate

# 2. Verify data migration
mysql -u user -p database -e "
SELECT id, amount, amount_paise,
       ROUND(amount * 100) as calculated_paise,
       CASE WHEN amount_paise = ROUND(amount * 100) THEN 'OK' ELSE 'MISMATCH' END as status
FROM payments
LIMIT 10;"

# 3. Test trait in tinker
php artisan tinker
$payment = new Payment();
$payment->setAmountFromRupees('amount', 100.50);
echo $payment->getAmountInPaise('amount'); // Should be 10050
echo $payment->getAmountInRupees('amount'); // Should be 100.50

# 4. Test precision
$payment1 = new Payment();
$payment1->amount = 100.33; // Old way
$payment1->save();

$payment2 = new Payment();
$payment2->setAmountFromPaise('amount', 10033); // New way
$payment2->save();

// Both should have identical values, but paise is authoritative
```

### Gradual Migration Plan
1. **Phase 1** (Week 1): Migration run, both fields co-exist
2. **Phase 2** (Week 2): Update WalletService to use paise internally
3. **Phase 3** (Week 3): Update AllocationService to use paise
4. **Phase 4** (Week 4): Update PaymentService to use paise
5. **Phase 5** (Month 2): Update all remaining services
6. **Phase 6** (Month 3): Remove decimal fields (breaking change migration)

### Rollback Plan
```bash
# Rollback migration (data loss acceptable if caught early)
php artisan migrate:rollback --step=1

# If data has been written to paise fields, manual intervention required
# Run this SQL to restore decimal fields:
UPDATE payments SET amount = amount_paise / 100 WHERE amount_paise IS NOT NULL;
```

### Known Issues
- **Race Condition:** If code writes to `amount` directly without using trait, `amount_paise` won't be updated
- **Mitigation:** Gradually migrate code to use `setAmountFromRupees()` or ensure trait is used
- **Future Fix:** Make paise field non-nullable and remove decimal field

---

## FIX 11: Audit Logging for All State Transitions ✅

### What It Does
Automatically logs all state changes (status, is_verified, is_active, etc.) to the `audit_logs` table for regulatory compliance. Non-blocking and easy to integrate.

### Files Created
- `app/Models/Traits/LogsStateChanges.php` (NEW)

### Key Features
- Logs old value → new value transitions
- Captures actor (user or system), timestamp, context metadata
- Records creation events with initial state
- Provides `getStateChangeHistory()` and `getFieldChangeHistory()` methods
- Non-blocking - errors logged without failing model saves
- Customizable per-model via `$stateFields` and `$includeInStateLog`

### How to Use LogsStateChanges Trait
```php
// In your model
use App\Models\Traits\LogsStateChanges;

class Deal extends Model
{
    use LogsStateChanges;

    // Define which fields to track (defaults to ['status'])
    protected static $stateFields = ['status', 'is_featured'];

    // Optional: Include additional context in logs
    protected static $includeInStateLog = ['company_id', 'product_id'];

    // Optional: Custom audit identifier (instead of #{id})
    public function getIdentifierForAudit(): string
    {
        return $this->title;
    }
}

// Usage
$deal = Deal::find(1);
$deal->status = 'active'; // From 'draft'
$deal->save();
// Automatically logs: "Deal 'My Deal Title': status changed from 'draft' to 'active'"

// Query state history
$history = $deal->getStateChangeHistory(); // All state changes
$statusHistory = $deal->getFieldChangeHistory('status'); // Only status changes
```

### Testing
```bash
# 1. Add trait to a model (e.g., Deal)
# Edit backend/app/Models/Deal.php and add:
use App\Models\Traits\LogsStateChanges;
protected static $stateFields = ['status'];

# 2. Test in tinker
php artisan tinker
$deal = Deal::first();
$oldStatus = $deal->status;
$deal->status = 'active';
$deal->save();

# 3. Verify audit log was created
AuditLog::where('action', 'Deal.state_change')
    ->where('metadata->model_id', $deal->id)
    ->latest()
    ->first();

# Expected output:
{
  "action": "Deal.state_change",
  "actor_id": 1,
  "actor_type": "App\\Models\\User",
  "description": "Deal 'My Deal': status changed from 'draft' to 'active'",
  "old_values": {"status": "draft"},
  "new_values": {"status": "active"},
  "metadata": {
    "model_type": "App\\Models\\Deal",
    "model_id": 1,
    "field": "status",
    "transition": "draft → active"
  }
}

# 4. Test creation logging
$newDeal = Deal::create([...]);
AuditLog::where('action', 'Deal.created')
    ->where('metadata->model_id', $newDeal->id)
    ->first();

# 5. Test history methods
$deal->getStateChangeHistory(); // All state changes
$deal->getFieldChangeHistory('status'); // Only status changes
```

### Models That Should Use This Trait
Priority order for adoption:

**High Priority (Regulatory Requirement):**
1. `Company` - is_verified, status
2. `Deal` - status (integrates with FIX 6 approval workflow)
3. `CompanyShareListing` - status (integrates with FIX 5 company freeze)
4. `BulkPurchase` - payment_status
5. `Payment` - status
6. `Withdrawal` - status

**Medium Priority:**
7. `User` - is_active, email_verified_at
8. `Subscription` - status
9. `UserInvestment` - is_reversed
10. `KYC` - status

**Low Priority:**
11. All other models with state fields

### Migration Path
1. **Phase 1** (Week 1): Add trait to Deal, CompanyShareListing, BulkPurchase
2. **Phase 2** (Week 2): Add trait to Payment, Withdrawal, Company
3. **Phase 3** (Week 3): Add trait to User, Subscription, UserInvestment
4. **Phase 4** (Month 2): Add trait to all remaining models

### Performance Considerations
- **Impact:** Each state change adds 1 INSERT to audit_logs table
- **Mitigation:** Audit logs table has indexes on model_type, model_id, created_at
- **Monitoring:** Monitor audit_logs table size, archive old logs quarterly
- **Optimization:** Consider async logging via queue for high-volume models (future enhancement)

### Rollback Plan
```bash
# Remove trait from models
# No database changes required - audit logs remain for historical record
```

---

## Deployment Checklist

### Pre-Deployment
- [ ] Review all policy files for company-specific logic
- [ ] Test monetary migration on staging database
- [ ] Verify audit_logs table has sufficient storage
- [ ] Run migrations in staging: `php artisan migrate`
- [ ] Test sample policy authorizations
- [ ] Test sample monetary conversions
- [ ] Test sample state change logging
- [ ] Clear all caches: `php artisan cache:clear && php artisan config:clear`

### Deployment Steps
1. **Backup Database**
   ```bash
   mysqldump -u user -p database > backup_before_p2_fixes.sql
   ```

2. **Pull Latest Code**
   ```bash
   git pull origin claude/audit-preipopsip-platform-MNnCa
   ```

3. **Run Migrations**
   ```bash
   php artisan migrate
   ```

4. **Clear Caches**
   ```bash
   php artisan config:clear
   php artisan cache:clear
   php artisan view:clear
   php artisan route:clear
   ```

5. **Restart Services**
   ```bash
   sudo systemctl restart php8.3-fpm
   sudo systemctl restart nginx
   sudo supervisorctl restart laravel-worker:*
   ```

### Post-Deployment Verification
- [ ] Verify policies work for admin and company users
- [ ] Verify monetary paise fields populated correctly
- [ ] Verify state change logs are being created
- [ ] Check audit logs for any policy authorization failures
- [ ] Monitor error logs for 24 hours: `tail -f storage/logs/laravel.log`

### Monitoring Commands
```bash
# Watch for policy authorization errors
tail -f storage/logs/laravel.log | grep -i "authorization\|policy"

# Check monetary field migration success
mysql -u user -p database -e "
SELECT
  (SELECT COUNT(*) FROM payments WHERE amount_paise IS NULL) as payments_null,
  (SELECT COUNT(*) FROM withdrawals WHERE amount_paise IS NULL) as withdrawals_null,
  (SELECT COUNT(*) FROM bulk_purchases WHERE value_remaining_paise IS NULL) as bulk_null;"

# Check state change audit logs
mysql -u user -p database -e "
SELECT action, COUNT(*) as count
FROM audit_logs
WHERE action LIKE '%.state_change'
  AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
GROUP BY action
ORDER BY count DESC
LIMIT 10;"

# Monitor audit_logs table size
mysql -u user -p database -e "
SELECT
  COUNT(*) as total_logs,
  COUNT(*) FILTER (WHERE created_at > DATE_SUB(NOW(), INTERVAL 7 DAYS)) as logs_last_7d,
  COUNT(*) FILTER (WHERE action LIKE '%.state_change') as state_change_logs,
  COUNT(*) FILTER (WHERE action LIKE '%.created') as creation_logs
FROM audit_logs;"
```

---

## Success Metrics

After deployment, verify:
- ✅ Policies registered and working for all resources
- ✅ 100% of monetary fields have paise equivalents populated
- ✅ State changes being logged for key models (Deal, Company, BulkPurchase)
- ✅ 0 policy authorization errors in logs (false failures)
- ✅ 0 monetary precision errors (check reconciliation)
- ✅ Audit logs table growing appropriately (~1000-5000 logs/day expected)

---

## Rollback Procedure (Emergency Only)

If critical issues arise:

```bash
# 1. Rollback migration (only if data integrity issues found immediately)
php artisan migrate:rollback --step=1

# 2. Remove AuthServiceProvider registration
# Edit backend/bootstrap/providers.php and remove AuthServiceProvider line

# 3. Revert code changes
git revert 88d1fbd

# 4. Clear caches
php artisan config:clear && php artisan cache:clear

# 5. Restart services
sudo systemctl restart php8.3-fpm nginx
sudo supervisorctl restart laravel-worker:*

# 6. Restore database backup if monetary data corrupted
mysql -u user -p database < backup_before_p2_fixes.sql
```

---

## Support Contacts

For issues during deployment:
- **Backend Team:** Check `AUDIT_REPORT_PRODUCT_LIFECYCLE.md` for detailed fix specifications
- **Logs:** `storage/logs/laravel.log`
- **Database:** Check migration status with `php artisan migrate:status`
- **Policies:** Test with `php artisan tinker` using `Gate::authorize()`
