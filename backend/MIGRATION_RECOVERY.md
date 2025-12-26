# Campaign Migration Recovery Guide

## Current Situation

Your database is in a partial migration state:
- ❌ Migration `000001` did NOT complete (table still has `expiry` column, not `end_at`)
- ❌ Migration `000002` failed due to duplicate index
- ❌ The seeder cannot run because it expects the new schema

## Recovery Steps

### Step 1: Check Current State

First, verify which migrations have actually run:

```sql
-- Check migration status
SELECT migration, batch FROM migrations WHERE migration LIKE '%2025_12_26%' ORDER BY id;

-- Check campaigns table structure
DESCRIBE campaigns;

-- Check if campaign_usages exists
SHOW TABLES LIKE 'campaign_usages';
```

**Expected findings:**
- `campaigns` table exists but has OLD schema (with `expiry` column)
- `campaign_usages` may exist partially or not at all
- Migration records may be present but incomplete

---

### Step 2: Clean Up Database State

Run this SQL script to reset to a clean state:

```sql
-- 1. Drop campaign_usages table if it exists (partial creation)
DROP TABLE IF EXISTS `campaign_usages`;

-- 2. Remove failed migration records
DELETE FROM `migrations`
WHERE `migration` IN (
    '2025_12_26_000001_rename_offers_to_campaigns_add_workflow_fields',
    '2025_12_26_000002_create_campaign_usages_table',
    '2025_12_26_000003_add_archival_to_campaigns',
    '2025_12_26_000004_add_terms_acceptance_to_campaign_usages'
);

-- 3. Check if campaigns table exists
SELECT COUNT(*) as has_campaigns FROM information_schema.tables
WHERE table_schema = DATABASE() AND table_name = 'campaigns';

-- If the above returns 1, the campaigns table exists
-- If it returns 0, you still have the 'offers' table

-- 4. If campaigns table exists but has old schema, we need to rename it back
-- ONLY RUN THIS if campaigns table exists with expiry column:
RENAME TABLE `campaigns` TO `offers`;

-- 5. Verify clean state
SELECT migration FROM migrations WHERE migration LIKE '%2025_12_26%';
-- This should return EMPTY (0 rows)

SHOW TABLES LIKE '%campaign%';
-- This should return EMPTY (no campaign tables)

SHOW TABLES LIKE 'offers';
-- This should return 'offers' table
```

---

### Step 3: Run Migrations Fresh

Now that the database is clean, run migrations:

```bash
cd backend

# Run all migrations
php artisan migrate

# You should see:
# ✓ 2025_12_26_000001_rename_offers_to_campaigns_add_workflow_fields
# ✓ 2025_12_26_000002_create_campaign_usages_table
# ✓ 2025_12_26_000003_add_archival_to_campaigns
# ✓ 2025_12_26_000004_add_terms_acceptance_to_campaign_usages
```

---

### Step 4: Verify Migration Success

```bash
php artisan migrate:status

# All 4 campaigns migrations should show as "Ran"
```

Verify the schema:

```sql
-- Check campaigns table has new schema
DESCRIBE campaigns;

-- Should have these columns:
-- - end_at (NOT expiry)
-- - created_by, approved_by, approved_at
-- - is_active
-- - start_at
-- - is_archived, archived_by, archived_at, archive_reason
-- - deleted_at

-- Check campaign_usages table exists
DESCRIBE campaign_usages;

-- Should have these columns:
-- - terms_accepted, terms_accepted_at, terms_acceptance_ip
-- - disclaimer_acknowledged, disclaimer_acknowledged_at
```

---

### Step 5: Run Seeder (Optional)

```bash
php artisan db:seed --class=CampaignBootstrapSeeder

# Should succeed and create one example campaign
```

---

## Alternative: Fresh Migration (Development Only)

⚠️ **WARNING: This destroys ALL database data!**

If the above doesn't work, or you're in a development environment with no important data:

```bash
cd backend

# Drop all tables and re-migrate
php artisan migrate:fresh

# Reseed necessary data
php artisan db:seed

# Seed the example campaign
php artisan db:seed --class=CampaignBootstrapSeeder
```

---

## Troubleshooting

### Issue: "RENAME TABLE fails"

If renaming campaigns → offers fails, it means the table has dependencies. Drop them first:

```sql
-- Find foreign key constraints
SELECT
    CONSTRAINT_NAME,
    TABLE_NAME,
    REFERENCED_TABLE_NAME
FROM information_schema.KEY_COLUMN_USAGE
WHERE REFERENCED_TABLE_NAME = 'campaigns'
AND TABLE_SCHEMA = DATABASE();

-- You'll likely see campaign_usages references
-- Drop campaign_usages first, then rename
DROP TABLE IF EXISTS `campaign_usages`;
RENAME TABLE `campaigns` TO `offers`;
```

### Issue: "offers table doesn't exist"

If you never had an `offers` table to begin with, skip the rename and instead:

```sql
-- Just drop campaigns table if it has wrong schema
DROP TABLE IF EXISTS `campaigns`;
DROP TABLE IF EXISTS `campaign_usages`;

-- Then run migrations fresh
```

---

## Verification Checklist

After recovery, verify:

- [ ] `offers` table no longer exists
- [ ] `campaigns` table exists with new schema (end_at, not expiry)
- [ ] `campaign_usages` table exists
- [ ] All 4 migrations show as "Ran" in `php artisan migrate:status`
- [ ] Seeder runs without errors
- [ ] Example campaign has `end_at` column populated

---

## Summary

The core issue was that migrations failed partway through, leaving the database in an inconsistent state. This guide:

1. Cleans up partial migrations
2. Resets to the original `offers` table state
3. Runs all migrations cleanly
4. Verifies success

After following these steps, your Campaign Management System should be fully operational.
