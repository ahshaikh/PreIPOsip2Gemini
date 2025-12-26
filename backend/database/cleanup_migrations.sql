-- ============================================
-- Campaign Migration Cleanup & Recovery Script
-- ============================================
-- Run this script to clean up failed migration attempts
-- Then run: php artisan migrate

-- Step 1: Check current state
SELECT '=== Current Migration Records ===' as '';
SELECT migration, batch FROM migrations WHERE migration LIKE '%2025_12_26%' ORDER BY id;

SELECT '=== Current Tables ===' as '';
SHOW TABLES LIKE '%campaign%';
SHOW TABLES LIKE 'offers';

-- Step 2: Drop partially created tables
SELECT '=== Cleaning up partial tables ===' as '';
DROP TABLE IF EXISTS `campaign_usages`;

-- Step 3: Check if campaigns table exists with old schema
SELECT '=== Checking campaigns table schema ===' as '';
SELECT COLUMN_NAME FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
AND TABLE_NAME = 'campaigns'
AND COLUMN_NAME IN ('expiry', 'end_at');

-- If you see 'expiry' above, campaigns table has OLD schema
-- If you see 'end_at' above, campaigns table has NEW schema

-- Step 4: Rename campaigns back to offers if it has old schema
-- ONLY UNCOMMENT AND RUN if the above query shows 'expiry':
-- RENAME TABLE `campaigns` TO `offers`;

-- Step 5: Remove migration records
SELECT '=== Removing failed migration records ===' as '';
DELETE FROM `migrations`
WHERE `migration` IN (
    '2025_12_26_000001_rename_offers_to_campaigns_add_workflow_fields',
    '2025_12_26_000002_create_campaign_usages_table',
    '2025_12_26_000003_add_archival_to_campaigns',
    '2025_12_26_000004_add_terms_acceptance_to_campaign_usages'
);

-- Step 6: Verify cleanup
SELECT '=== Verification ===' as '';
SELECT 'Migration records after cleanup:' as '';
SELECT migration FROM migrations WHERE migration LIKE '%2025_12_26%';
-- Should return EMPTY (0 rows)

SELECT 'Campaign-related tables:' as '';
SHOW TABLES LIKE '%campaign%';
-- Should return EMPTY (no campaign tables)

SELECT 'Offers table:' as '';
SHOW TABLES LIKE 'offers';
-- Should return 'offers' if you had data

-- Step 7: Ready to migrate
SELECT '=== Next Steps ===' as '';
SELECT 'Database is now clean. Run: php artisan migrate' as instruction;
