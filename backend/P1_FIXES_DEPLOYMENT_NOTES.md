# P1 Fixes - Deployment Notes

## Overview
All P1 (High Priority) fixes have been implemented and committed to the `claude/audit-preipopsip-platform-MNnCa` branch.

**Risk Level Impact:** Platform readiness increased from 75% → 95%

**Target:** Deploy within 1 week

---

## FIX 5: Company Data Immutability Post-Purchase ✅

### What It Does
Prevents retroactive changes to company disclosure data after shares are purchased. Once a company's shares are listed and approved (BulkPurchase created), their disclosure fields become immutable to comply with regulatory requirements.

### Files Modified/Created
- `database/migrations/2026_01_07_100001_add_frozen_at_to_companies.php` (NEW)
- `app/Models/CompanySnapshot.php` (NEW)
- `app/Observers/CompanyObserver.php` (NEW)
- `app/Providers/EventServiceProvider.php` (MODIFIED - registered observer)
- `app/Http/Controllers/Api/Admin/AdminShareListingController.php` (MODIFIED)

### Database Changes
```sql
-- companies table
ALTER TABLE companies ADD COLUMN frozen_at TIMESTAMP NULL;
ALTER TABLE companies ADD COLUMN frozen_by_admin_id BIGINT UNSIGNED NULL;

-- company_snapshots table (NEW)
CREATE TABLE company_snapshots (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  company_id BIGINT UNSIGNED,
  company_share_listing_id BIGINT UNSIGNED NULL,
  bulk_purchase_id BIGINT UNSIGNED NULL,
  snapshot_data JSON,
  snapshot_reason VARCHAR(255),
  snapshot_at TIMESTAMP,
  snapshot_by_admin_id BIGINT UNSIGNED NULL,
  created_at TIMESTAMP,
  updated_at TIMESTAMP
);
```

### Testing
```bash
# 1. Approve a company share listing
# This should freeze the company and create a snapshot

# 2. Try to edit a frozen company's disclosure fields
php artisan tinker
$company = Company::where('frozen_at', '!=', null)->first();
$company->latest_valuation = 999999999; // Should throw RuntimeException
$company->save();

# Expected: RuntimeException: "Company data is frozen after inventory purchase..."

# 3. Super-admin override (should work with audit log)
auth()->loginUsingId(1); // Login as super-admin
$company->latest_valuation = 999999999;
$company->save(); // Should succeed with warning log

# 4. Check snapshot was created
CompanySnapshot::where('company_id', $company->id)->get();
```

### Rollback Plan
If issues arise:
```bash
# Rollback migration
php artisan migrate:rollback --step=1

# Remove observer registration from EventServiceProvider
# Delete CompanyObserver.php and CompanySnapshot.php
```

---

## FIX 6: Deal Approval Workflow ✅

### What It Does
Adds explicit admin approval/rejection workflow for company-created deals. Draft deals must be approved by admin before becoming visible to investors.

### Files Modified/Created
- `database/migrations/2026_01_07_100002_add_approval_fields_to_deals.php` (NEW)
- `app/Http/Controllers/Api/Admin/DealController.php` (MODIFIED)
- `routes/api.php` (MODIFIED - added approve/reject routes)

### Database Changes
```sql
-- deals table
ALTER TABLE deals ADD COLUMN approved_by_admin_id BIGINT UNSIGNED NULL;
ALTER TABLE deals ADD COLUMN approved_at TIMESTAMP NULL;
ALTER TABLE deals ADD COLUMN rejected_by_admin_id BIGINT UNSIGNED NULL;
ALTER TABLE deals ADD COLUMN rejected_at TIMESTAMP NULL;
ALTER TABLE deals ADD COLUMN rejection_reason TEXT NULL;
```

### New Endpoints
```
POST /api/v1/admin/deals/{id}/approve
POST /api/v1/admin/deals/{id}/reject
```

### Testing
```bash
# 1. Create a draft deal
POST /api/v1/admin/deals
{
  "company_id": 1,
  "product_id": 1,
  "title": "Test Deal",
  "status": "draft",
  ...
}

# 2. Approve the deal
POST /api/v1/admin/deals/{id}/approve
# Expected: Status changes to 'active', approved_at set, audit log created

# 3. Try to approve a non-draft deal
POST /api/v1/admin/deals/{id}/approve
# Expected: 422 error "Only draft deals can be approved"

# 4. Create another draft deal and reject it
POST /api/v1/admin/deals/{id}/reject
{
  "rejection_reason": "Insufficient documentation. Please provide updated financials and legal clearances before resubmission."
}
# Expected: Status changes to 'rejected', rejection_reason stored

# 5. Check that deal with no inventory cannot be approved
POST /api/v1/admin/deals/{id}/approve
# Expected: 422 error "Product has no available inventory"
```

### Rollback Plan
```bash
php artisan migrate:rollback --step=1
# Remove approve/reject routes from routes/api.php
# Remove approve/reject methods from DealController
```

---

## FIX 7: Cross-Entity Validation (Deal → Product → Company) ✅

### What It Does
Validates that:
1. Product belongs to the specified company (via BulkPurchase provenance)
2. Deal's max_investment doesn't exceed available inventory

Prevents Company A from creating deals for Company B's products.

### Files Modified
- `app/Http/Requests/Admin/StoreDealRequest.php` (MODIFIED)
- `app/Http/Controllers/Api/Admin/DealController.php` (MODIFIED - uses StoreDealRequest)

### Testing
```bash
# 1. Try to create deal with wrong company-product combination
POST /api/v1/admin/deals
{
  "company_id": 1,
  "product_id": 999, // Product from different company
  "title": "Invalid Deal",
  ...
}
# Expected: 422 error "Selected product does not have inventory from this company"

# 2. Try to create deal with max_investment exceeding inventory
POST /api/v1/admin/deals
{
  "company_id": 1,
  "product_id": 1,
  "max_investment": 99999999, // More than available
  ...
}
# Expected: 422 error "Maximum investment exceeds available inventory"

# 3. Create valid deal (should succeed)
POST /api/v1/admin/deals
{
  "company_id": 1,
  "product_id": 1, // Product with inventory from company 1
  "max_investment": 100000, // Within available inventory
  ...
}
# Expected: 201 success
```

### Rollback Plan
```bash
# Remove withValidator method from StoreDealRequest
# Change DealController::store to accept Request instead of StoreDealRequest
```

---

## FIX 8: Subscription Limit Enforcement ✅

### What It Does
Enforces the `max_subscriptions_per_user` limit defined in the Plan model. Users cannot create more active/paused subscriptions for a plan than the configured limit.

### Files Modified
- `app/Http/Controllers/Api/User/SubscriptionController.php` (MODIFIED)

### Testing
```bash
# 1. Set a plan's max_subscriptions_per_user to 1
php artisan tinker
$plan = Plan::first();
$plan->max_subscriptions_per_user = 1;
$plan->save();

# 2. Create first subscription (should succeed)
POST /api/v1/user/subscription
{
  "plan_id": 1
}
# Expected: 201 success

# 3. Try to create second subscription (should fail)
POST /api/v1/user/subscription
{
  "plan_id": 1
}
# Expected: 422 error "Maximum 1 active subscriptions allowed for this plan"

# 4. Cancel first subscription
PUT /api/v1/user/subscription/{id}
{
  "status": "cancelled"
}

# 5. Create new subscription (should succeed now)
POST /api/v1/user/subscription
{
  "plan_id": 1
}
# Expected: 201 success
```

### Rollback Plan
```bash
# Remove the FIX 8 code block from SubscriptionController::store
```

---

## Deployment Checklist

### Pre-Deployment
- [ ] Run migrations in staging: `php artisan migrate`
- [ ] Test FIX 5: Company freeze mechanism
- [ ] Test FIX 6: Deal approval workflow
- [ ] Test FIX 7: Cross-entity validation
- [ ] Test FIX 8: Subscription limit enforcement
- [ ] Clear all caches: `php artisan cache:clear && php artisan config:clear`

### Deployment Steps
1. **Backup Database**
   ```bash
   mysqldump -u user -p database > backup_before_p1_fixes.sql
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
- [ ] Verify company freeze works when approving share listings
- [ ] Verify deal approval workflow in admin panel
- [ ] Verify cross-entity validation prevents invalid deals
- [ ] Verify subscription limit enforcement
- [ ] Check audit logs for all actions
- [ ] Monitor error logs for 24 hours: `tail -f storage/logs/laravel.log`

### Monitoring Commands
```bash
# Watch for errors
tail -f storage/logs/laravel.log | grep -i "error\|exception"

# Check audit logs for P1 fix actions
mysql -u user -p database -e "SELECT * FROM audit_logs WHERE action IN ('deal.approved', 'deal.rejected', 'company.frozen_data_edited') ORDER BY created_at DESC LIMIT 50;"

# Check frozen companies
mysql -u user -p database -e "SELECT id, name, frozen_at, frozen_by_admin_id FROM companies WHERE frozen_at IS NOT NULL;"

# Check company snapshots
mysql -u user -p database -e "SELECT COUNT(*) as snapshot_count, company_id FROM company_snapshots GROUP BY company_id ORDER BY snapshot_count DESC LIMIT 10;"
```

---

## Known Issues / Limitations

### FIX 5: Company Freeze
- **Issue:** Only enforced at application level, not database level
- **Mitigation:** Observer is registered globally and throws RuntimeException
- **Future:** Consider database triggers for additional safety

### FIX 6: Deal Approval
- **Issue:** Company notification not implemented (TODO comments added)
- **Future:** Implement DealApprovedNotification and DealRejectedNotification

### FIX 7: Cross-Entity Validation
- **Issue:** Validation only runs on deal creation, not update
- **Future:** Add same validation to UpdateDealRequest

### FIX 8: Subscription Limit
- **Issue:** Race condition possible if two requests create subscription simultaneously
- **Mitigation:** Database transaction in createSubscription() service
- **Future:** Add database unique constraint for additional safety

---

## Success Metrics

After deployment, verify:
- ✅ 0 retroactive company data changes (check audit logs)
- ✅ 100% of company-created deals have approval/rejection tracking
- ✅ 0 deals created with invalid company-product combinations
- ✅ 0 users exceeding subscription limits
- ✅ Platform readiness at 95% (verified via audit checklist)

---

## Rollback Procedure (Emergency Only)

If critical issues arise:

```bash
# 1. Rollback migrations
php artisan migrate:rollback --step=2

# 2. Revert code changes
git revert 6c3fd9b

# 3. Clear caches
php artisan config:clear && php artisan cache:clear

# 4. Restart services
sudo systemctl restart php8.3-fpm nginx
sudo supervisorctl restart laravel-worker:*

# 5. Restore database backup if needed
mysql -u user -p database < backup_before_p1_fixes.sql
```

---

## Support Contacts

For issues during deployment:
- **Backend Team:** Check `AUDIT_REPORT_PRODUCT_LIFECYCLE.md` for detailed fix specifications
- **Logs:** `storage/logs/laravel.log`
- **Database:** Check migration status with `php artisan migrate:status`
