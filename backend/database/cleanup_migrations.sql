-- Cleanup script for failed campaign migrations
-- Run this in your MySQL client or phpMyAdmin

-- Step 1: Drop the partially created campaign_usages table
DROP TABLE IF EXISTS `campaign_usages`;

-- Step 2: Remove the failed migration records from migrations table
DELETE FROM `migrations`
WHERE `migration` IN (
    '2025_12_26_000002_create_campaign_usages_table',
    '2025_12_26_000003_add_archival_to_campaigns',
    '2025_12_26_000004_add_terms_acceptance_to_campaign_usages'
);

-- Step 3: Verify the cleanup
SELECT * FROM `migrations` WHERE `migration` LIKE '%2025_12_26%';

-- You should only see:
-- 2025_12_26_000001_rename_offers_to_campaigns_add_workflow_fields
-- This means you're ready to run: php artisan migrate
