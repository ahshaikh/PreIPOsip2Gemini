-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: preipo
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `activity_logs`
--

DROP TABLE IF EXISTS `activity_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `activity_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `target_type` varchar(255) DEFAULT NULL,
  `target_id` bigint(20) unsigned DEFAULT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_values`)),
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_values`)),
  PRIMARY KEY (`id`),
  KEY `activity_logs_user_id_foreign` (`user_id`),
  CONSTRAINT `activity_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `admin_action_audit`
--

DROP TABLE IF EXISTS `admin_action_audit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `admin_action_audit` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `admin_id` bigint(20) unsigned NOT NULL,
  `admin_name` varchar(255) NOT NULL,
  `admin_role` varchar(255) NOT NULL,
  `action_type` varchar(255) NOT NULL,
  `idempotency_key` varchar(255) DEFAULT NULL COMMENT 'Unique key to prevent duplicate admin actions',
  `justification` text NOT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `entity_type` varchar(255) DEFAULT NULL,
  `entity_id` bigint(20) unsigned DEFAULT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `status` varchar(255) NOT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `failed_at` timestamp NULL DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  `state_before` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`state_before`)),
  `state_after` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`state_after`)),
  `requires_approval` tinyint(1) NOT NULL DEFAULT 0,
  `approved` tinyint(1) NOT NULL DEFAULT 0,
  `approved_by` bigint(20) unsigned DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `admin_action_audit_idempotency_key_unique` (`idempotency_key`),
  KEY `admin_action_audit_admin_id_index` (`admin_id`),
  KEY `admin_action_audit_action_type_index` (`action_type`),
  KEY `admin_action_audit_status_index` (`status`),
  KEY `admin_action_audit_entity_type_entity_id_index` (`entity_type`,`entity_id`),
  KEY `admin_action_audit_created_at_index` (`created_at`),
  CONSTRAINT `check_admin_action_status` CHECK (`status` in ('pending','completed','failed'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `admin_dashboard_widgets`
--

DROP TABLE IF EXISTS `admin_dashboard_widgets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `admin_dashboard_widgets` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `admin_id` bigint(20) unsigned NOT NULL,
  `widget_type` varchar(255) NOT NULL,
  `position` int(11) NOT NULL DEFAULT 0,
  `width` int(11) NOT NULL DEFAULT 6,
  `height` int(11) NOT NULL DEFAULT 4,
  `config` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`config`)),
  `is_visible` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `admin_dashboard_widgets_admin_id_widget_type_unique` (`admin_id`,`widget_type`),
  CONSTRAINT `admin_dashboard_widgets_admin_id_foreign` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `admin_ledger_entries`
--

DROP TABLE IF EXISTS `admin_ledger_entries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `admin_ledger_entries` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `account` enum('cash','inventory','liabilities','revenue','expenses') NOT NULL,
  `type` enum('debit','credit') NOT NULL,
  `amount_paise` bigint(20) NOT NULL,
  `balance_before_paise` bigint(20) NOT NULL,
  `balance_after_paise` bigint(20) NOT NULL,
  `reference_type` varchar(255) NOT NULL,
  `reference_id` bigint(20) unsigned NOT NULL,
  `description` text NOT NULL,
  `entry_pair_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `admin_ledger_entries_account_index` (`account`),
  KEY `admin_ledger_entries_account_created_at_index` (`account`,`created_at`),
  KEY `admin_ledger_entries_reference_type_reference_id_index` (`reference_type`,`reference_id`),
  KEY `admin_ledger_entries_entry_pair_id_index` (`entry_pair_id`),
  CONSTRAINT `admin_ledger_entries_entry_pair_id_foreign` FOREIGN KEY (`entry_pair_id`) REFERENCES `admin_ledger_entries` (`id`),
  CONSTRAINT `check_balance_consistency` CHECK (`type` = 'debit' and `balance_after_paise` = `balance_before_paise` + `amount_paise` or `type` = 'credit' and `balance_after_paise` = `balance_before_paise` - `amount_paise`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `admin_preferences`
--

DROP TABLE IF EXISTS `admin_preferences`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `admin_preferences` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `admin_id` bigint(20) unsigned NOT NULL,
  `key` varchar(255) NOT NULL,
  `value` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `admin_preferences_admin_id_key_unique` (`admin_id`,`key`),
  CONSTRAINT `admin_preferences_admin_id_foreign` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `alert_root_causes`
--

DROP TABLE IF EXISTS `alert_root_causes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `alert_root_causes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `root_cause_type` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `affected_alerts_count` int(11) NOT NULL DEFAULT 0,
  `total_monetary_impact` decimal(15,2) NOT NULL DEFAULT 0.00,
  `affected_users_count` int(11) NOT NULL DEFAULT 0,
  `first_occurrence` timestamp NULL DEFAULT NULL,
  `last_occurrence` timestamp NULL DEFAULT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `is_resolved` tinyint(1) NOT NULL DEFAULT 0,
  `resolved_by` bigint(20) unsigned DEFAULT NULL,
  `resolution_notes` text DEFAULT NULL,
  `severity` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `alert_root_causes_root_cause_type_index` (`root_cause_type`),
  KEY `alert_root_causes_is_resolved_index` (`is_resolved`),
  KEY `alert_root_causes_severity_index` (`severity`),
  KEY `alert_root_causes_first_occurrence_index` (`first_occurrence`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `api_test_cases`
--

DROP TABLE IF EXISTS `api_test_cases`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `api_test_cases` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `method` varchar(255) NOT NULL,
  `endpoint` varchar(255) NOT NULL,
  `headers` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`headers`)),
  `body` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`body`)),
  `expected_response` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`expected_response`)),
  `expected_status_code` int(11) NOT NULL DEFAULT 200,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `api_test_cases_created_by_foreign` (`created_by`),
  CONSTRAINT `api_test_cases_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `api_test_results`
--

DROP TABLE IF EXISTS `api_test_results`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `api_test_results` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `test_case_id` bigint(20) unsigned NOT NULL,
  `status` varchar(255) NOT NULL,
  `response_time` int(11) DEFAULT NULL,
  `status_code` int(11) DEFAULT NULL,
  `response_body` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`response_body`)),
  `error_message` text DEFAULT NULL,
  `executed_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `api_test_results_executed_by_foreign` (`executed_by`),
  KEY `api_test_results_test_case_id_created_at_index` (`test_case_id`,`created_at`),
  CONSTRAINT `api_test_results_executed_by_foreign` FOREIGN KEY (`executed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `api_test_results_test_case_id_foreign` FOREIGN KEY (`test_case_id`) REFERENCES `api_test_cases` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `article_feedback`
--

DROP TABLE IF EXISTS `article_feedback`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `article_feedback` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `article_id` varchar(255) NOT NULL,
  `is_helpful` tinyint(1) NOT NULL,
  `comment` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `article_feedback_article_id_index` (`article_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `audit_logs`
--

DROP TABLE IF EXISTS `audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `audit_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `actor_type` varchar(255) NOT NULL COMMENT 'admin, company_user, system',
  `actor_id` bigint(20) unsigned DEFAULT NULL,
  `actor_name` varchar(255) DEFAULT NULL,
  `actor_email` varchar(255) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `action` varchar(255) NOT NULL COMMENT 'created, updated, deleted, approved, rejected, etc',
  `module` varchar(100) DEFAULT 'system',
  `description` varchar(255) NOT NULL,
  `target_type` varchar(255) DEFAULT NULL,
  `target_id` bigint(20) unsigned DEFAULT NULL,
  `target_name` varchar(255) DEFAULT NULL,
  `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'State before change' CHECK (json_valid(`old_values`)),
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'State after change' CHECK (json_valid(`new_values`)),
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Additional context' CHECK (json_valid(`metadata`)),
  `request_method` varchar(10) DEFAULT NULL,
  `request_url` varchar(255) DEFAULT NULL,
  `session_id` varchar(255) DEFAULT NULL,
  `risk_level` enum('low','medium','high','critical') NOT NULL DEFAULT 'low',
  `requires_review` tinyint(1) NOT NULL DEFAULT 0,
  `is_archived` tinyint(1) NOT NULL DEFAULT 0,
  `archived_at` timestamp NULL DEFAULT NULL,
  `retention_period` varchar(255) NOT NULL DEFAULT 'permanent' COMMENT 'permanent, 7years, etc.',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `admin_id` bigint(20) unsigned DEFAULT NULL COMMENT 'Admin responsible for audited action',
  PRIMARY KEY (`id`),
  KEY `audit_logs_actor_type_actor_id_index` (`actor_type`,`actor_id`),
  KEY `audit_logs_target_type_target_id_index` (`target_type`,`target_id`),
  KEY `audit_logs_module_action_index` (`module`,`action`),
  KEY `audit_logs_risk_level_created_at_index` (`risk_level`,`created_at`),
  KEY `audit_logs_action_index` (`action`),
  KEY `audit_logs_module_index` (`module`),
  KEY `audit_logs_created_at_index` (`created_at`),
  KEY `audit_logs_is_archived_index` (`is_archived`),
  CONSTRAINT `check_audit_logs_valid_risk_level` CHECK (`risk_level` in ('low','medium','high','critical'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `banners`
--

DROP TABLE IF EXISTS `banners`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `banners` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `variant_of` int(10) unsigned DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `content` text DEFAULT NULL,
  `link_url` varchar(255) DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `type` varchar(255) NOT NULL DEFAULT 'top_bar',
  `trigger_type` varchar(255) NOT NULL DEFAULT 'load',
  `trigger_value` int(11) NOT NULL DEFAULT 0,
  `frequency` varchar(255) NOT NULL DEFAULT 'always',
  `targeting_rules` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`targeting_rules`)),
  `style_config` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`style_config`)),
  `display_weight` int(11) NOT NULL DEFAULT 1,
  `start_at` datetime DEFAULT NULL,
  `end_at` datetime DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `start_date` datetime DEFAULT NULL,
  `end_date` datetime DEFAULT NULL,
  `display_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `benefit_audit_log`
--

DROP TABLE IF EXISTS `benefit_audit_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `benefit_audit_log` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `investment_id` bigint(20) unsigned DEFAULT NULL,
  `benefit_type` enum('promotional_campaign','referral_bonus','none') NOT NULL,
  `decision` varchar(255) NOT NULL,
  `original_amount` decimal(15,2) NOT NULL,
  `benefit_amount` decimal(15,2) NOT NULL,
  `final_amount` decimal(15,2) NOT NULL,
  `eligibility_reason` text NOT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_archived` tinyint(1) NOT NULL DEFAULT 0,
  `archived_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `benefit_audit_log_user_id_index` (`user_id`),
  KEY `benefit_audit_log_investment_id_index` (`investment_id`),
  KEY `benefit_audit_log_benefit_type_index` (`benefit_type`),
  KEY `benefit_audit_log_created_at_index` (`created_at`),
  KEY `benefit_audit_log_is_archived_index` (`is_archived`),
  CONSTRAINT `benefit_audit_log_investment_id_foreign` FOREIGN KEY (`investment_id`) REFERENCES `investments` (`id`),
  CONSTRAINT `benefit_audit_log_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `check_benefit_amount_non_negative` CHECK (`benefit_amount` >= 0),
  CONSTRAINT `check_final_amount_positive` CHECK (`final_amount` > 0),
  CONSTRAINT `check_benefit_not_exceed_original` CHECK (`benefit_amount` <= `original_amount`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `blog_categories`
--

DROP TABLE IF EXISTS `blog_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `blog_categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `color` varchar(255) NOT NULL DEFAULT '#667eea',
  `icon` varchar(255) DEFAULT NULL,
  `display_order` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `blog_categories_slug_unique` (`slug`),
  KEY `blog_categories_slug_index` (`slug`),
  KEY `blog_categories_is_active_index` (`is_active`),
  KEY `blog_categories_display_order_index` (`display_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `blog_posts`
--

DROP TABLE IF EXISTS `blog_posts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `blog_posts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `excerpt` text DEFAULT NULL,
  `seo_title` varchar(255) DEFAULT NULL,
  `seo_description` text DEFAULT NULL,
  `tags` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`tags`)),
  `featured_image` varchar(255) DEFAULT NULL,
  `author_id` bigint(20) unsigned NOT NULL,
  `category` varchar(255) DEFAULT NULL,
  `category_id` bigint(20) unsigned DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'draft',
  `is_featured` tinyint(1) NOT NULL DEFAULT 0,
  `published_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `blog_posts_slug_unique` (`slug`),
  KEY `blog_posts_author_id_foreign` (`author_id`),
  KEY `blog_posts_status_published_index` (`status`,`published_at`),
  KEY `blog_posts_slug_index` (`slug`),
  KEY `blog_posts_category_id_index` (`category_id`),
  KEY `blog_posts_is_featured_index` (`is_featured`),
  KEY `blog_posts_status_index` (`status`),
  CONSTRAINT `blog_posts_author_id_foreign` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`),
  CONSTRAINT `blog_posts_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `blog_categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `bonus_transactions`
--

DROP TABLE IF EXISTS `bonus_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bonus_transactions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `subscription_id` bigint(20) unsigned NOT NULL,
  `payment_id` bigint(20) unsigned DEFAULT NULL,
  `type` varchar(255) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `tds_deducted` decimal(10,2) NOT NULL DEFAULT 0.00,
  `multiplier_applied` decimal(5,2) NOT NULL DEFAULT 1.00,
  `base_amount` decimal(10,2) DEFAULT NULL,
  `description` varchar(255) NOT NULL DEFAULT '',
  `override_applied` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Whether a regulatory override was applied to this calculation',
  `override_id` bigint(20) unsigned DEFAULT NULL,
  `config_used` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'The actual config used for calculation (for audit verification)' CHECK (json_valid(`config_used`)),
  `override_delta` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'What the override changed from snapshot (for transparency)' CHECK (json_valid(`override_delta`)),
  `snapshot_hash_used` char(32) DEFAULT NULL COMMENT 'V-CONTRACT-HARDENING-FINAL: SHA256 hash of snapshot config at calculation time',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `bonus_transactions_user_id_foreign` (`user_id`),
  KEY `bonus_transactions_subscription_id_foreign` (`subscription_id`),
  KEY `bonus_transactions_payment_id_foreign` (`payment_id`),
  KEY `idx_override_applied` (`override_applied`),
  KEY `idx_override_id` (`override_id`),
  KEY `idx_bonus_transactions_snapshot_hash` (`snapshot_hash_used`),
  CONSTRAINT `bonus_transactions_override_id_foreign` FOREIGN KEY (`override_id`) REFERENCES `plan_regulatory_overrides` (`id`),
  CONSTRAINT `bonus_transactions_payment_id_foreign` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `bonus_transactions_subscription_id_foreign` FOREIGN KEY (`subscription_id`) REFERENCES `subscriptions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `bonus_transactions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `bonus_amount_not_zero` CHECK (`amount` <> 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `bulk_import_jobs`
--

DROP TABLE IF EXISTS `bulk_import_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bulk_import_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(255) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `total_rows` int(11) NOT NULL DEFAULT 0,
  `processed_rows` int(11) NOT NULL DEFAULT 0,
  `successful_rows` int(11) NOT NULL DEFAULT 0,
  `failed_rows` int(11) NOT NULL DEFAULT 0,
  `errors` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`errors`)),
  `notes` text DEFAULT NULL,
  `created_by` bigint(20) unsigned NOT NULL,
  `started_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `bulk_import_jobs_created_by_foreign` (`created_by`),
  KEY `bulk_import_jobs_type_status_index` (`type`,`status`),
  CONSTRAINT `bulk_import_jobs_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `bulk_purchases`
--

DROP TABLE IF EXISTS `bulk_purchases`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bulk_purchases` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint(20) unsigned NOT NULL,
  `company_id` bigint(20) unsigned NOT NULL,
  `company_share_listing_id` bigint(20) unsigned DEFAULT NULL,
  `source_type` enum('company_listing','manual_entry') NOT NULL DEFAULT 'company_listing',
  `admin_id` bigint(20) unsigned NOT NULL,
  `face_value_purchased` decimal(14,2) NOT NULL,
  `face_value_purchased_paise` bigint(20) DEFAULT NULL,
  `actual_cost_paid` decimal(14,2) NOT NULL,
  `actual_cost_paid_paise` bigint(20) DEFAULT NULL,
  `discount_percentage` decimal(5,2) NOT NULL,
  `extra_allocation_percentage` decimal(5,2) NOT NULL,
  `total_value_received` decimal(14,2) NOT NULL,
  `total_value_received_paise` bigint(20) DEFAULT NULL,
  `value_remaining` bigint(20) unsigned NOT NULL,
  `value_remaining_paise` bigint(20) DEFAULT NULL,
  `seller_name` varchar(255) DEFAULT NULL,
  `purchase_date` date NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `approved_by_admin_id` bigint(20) unsigned DEFAULT NULL,
  `platform_ledger_entry_id` bigint(20) unsigned DEFAULT NULL,
  `ledger_entry_id` bigint(20) unsigned DEFAULT NULL COMMENT 'Reference to double-entry ledger_entries table (Phase 4.1)',
  `manual_entry_reason` text DEFAULT NULL,
  `source_documentation` text DEFAULT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `bulk_purchases_admin_id_foreign` (`admin_id`),
  KEY `bulk_purchases_product_id_value_remaining_created_at_index` (`product_id`,`value_remaining`,`created_at`),
  KEY `bulk_purchases_approved_by_admin_id_foreign` (`approved_by_admin_id`),
  KEY `bulk_purchases_company_id_index` (`company_id`),
  KEY `bulk_purchases_company_share_listing_id_index` (`company_share_listing_id`),
  KEY `bulk_purchases_source_type_index` (`source_type`),
  KEY `bulk_purchases_verified_at_index` (`verified_at`),
  KEY `bulk_purchases_value_remaining_paise_index` (`value_remaining_paise`),
  KEY `idx_bp_platform_ledger` (`platform_ledger_entry_id`),
  KEY `bulk_purchases_ledger_entry_id_foreign` (`ledger_entry_id`),
  CONSTRAINT `bulk_purchases_admin_id_foreign` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`),
  CONSTRAINT `bulk_purchases_approved_by_admin_id_foreign` FOREIGN KEY (`approved_by_admin_id`) REFERENCES `users` (`id`),
  CONSTRAINT `bulk_purchases_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`),
  CONSTRAINT `bulk_purchases_company_share_listing_id_foreign` FOREIGN KEY (`company_share_listing_id`) REFERENCES `company_share_listings` (`id`),
  CONSTRAINT `bulk_purchases_ledger_entry_id_foreign` FOREIGN KEY (`ledger_entry_id`) REFERENCES `ledger_entries` (`id`) ON DELETE SET NULL,
  CONSTRAINT `bulk_purchases_platform_ledger_entry_id_foreign` FOREIGN KEY (`platform_ledger_entry_id`) REFERENCES `platform_ledger_entries` (`id`),
  CONSTRAINT `bulk_purchases_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  CONSTRAINT `check_manual_entry_provenance` CHECK (`source_type` <> 'manual_entry' or `approved_by_admin_id` is not null and `manual_entry_reason` is not null and octet_length(`manual_entry_reason`) >= 50 and `verified_at` is not null),
  CONSTRAINT `check_listing_entry_provenance` CHECK (`source_type` <> 'company_listing' or `company_share_listing_id` is not null)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `cache_locks`
--

DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `campaign_usages`
--

DROP TABLE IF EXISTS `campaign_usages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `campaign_usages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `campaign_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `applicable_type` varchar(255) NOT NULL,
  `applicable_id` bigint(20) unsigned NOT NULL,
  `original_amount` decimal(12,2) NOT NULL,
  `discount_applied` decimal(12,2) NOT NULL,
  `final_amount` decimal(12,2) NOT NULL,
  `campaign_code` varchar(255) NOT NULL,
  `campaign_snapshot` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`campaign_snapshot`)),
  `terms_accepted` tinyint(1) NOT NULL DEFAULT 0,
  `terms_accepted_at` timestamp NULL DEFAULT NULL,
  `terms_acceptance_ip` varchar(45) DEFAULT NULL,
  `disclaimer_acknowledged` tinyint(1) NOT NULL DEFAULT 0,
  `disclaimer_acknowledged_at` timestamp NULL DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `used_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `investment_id` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_campaign_application` (`campaign_id`,`applicable_type`,`applicable_id`),
  KEY `campaign_usages_applicable_type_applicable_id_index` (`applicable_type`,`applicable_id`),
  KEY `campaign_usages_campaign_id_index` (`campaign_id`),
  KEY `campaign_usages_user_id_index` (`user_id`),
  KEY `campaign_usages_campaign_code_index` (`campaign_code`),
  KEY `campaign_usages_used_at_index` (`used_at`),
  KEY `campaign_user_usage_index` (`campaign_id`,`user_id`),
  KEY `campaign_usages_terms_accepted_index` (`terms_accepted`),
  KEY `campaign_usages_disclaimer_acknowledged_index` (`disclaimer_acknowledged`),
  CONSTRAINT `campaign_usages_campaign_id_foreign` FOREIGN KEY (`campaign_id`) REFERENCES `campaigns` (`id`) ON DELETE CASCADE,
  CONSTRAINT `campaign_usages_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `campaigns`
--

DROP TABLE IF EXISTS `campaigns`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `campaigns` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `approved_by` bigint(20) unsigned DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 0,
  `is_archived` tinyint(1) NOT NULL DEFAULT 0,
  `archived_by` bigint(20) unsigned DEFAULT NULL,
  `archived_at` timestamp NULL DEFAULT NULL,
  `archive_reason` text DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `subtitle` varchar(255) DEFAULT NULL,
  `code` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `long_description` longtext DEFAULT NULL,
  `discount_type` enum('percentage','fixed_amount') NOT NULL DEFAULT 'fixed_amount',
  `discount_percent` decimal(5,2) DEFAULT NULL,
  `discount_amount` decimal(10,2) DEFAULT NULL,
  `min_investment` decimal(10,2) DEFAULT NULL,
  `max_discount` decimal(10,2) DEFAULT NULL,
  `usage_limit` int(10) unsigned DEFAULT NULL,
  `usage_count` int(10) unsigned NOT NULL DEFAULT 0,
  `user_usage_limit` int(10) unsigned DEFAULT NULL,
  `end_at` timestamp NULL DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `hero_image` varchar(255) DEFAULT NULL,
  `video_url` varchar(255) DEFAULT NULL,
  `features` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`features`)),
  `terms` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`terms`)),
  `is_featured` tinyint(1) NOT NULL DEFAULT 0,
  `start_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `starts_at` timestamp NULL DEFAULT NULL,
  `ends_at` timestamp NULL DEFAULT NULL,
  `max_usages` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `offers_code_unique` (`code`),
  KEY `offers_code_index` (`code`),
  KEY `offers_expiry_index` (`end_at`),
  KEY `offers_is_featured_index` (`is_featured`),
  KEY `campaigns_created_by_index` (`created_by`),
  KEY `campaigns_approved_by_index` (`approved_by`),
  KEY `campaigns_is_active_index` (`is_active`),
  KEY `campaigns_start_at_index` (`start_at`),
  KEY `campaigns_archived_by_foreign` (`archived_by`),
  KEY `campaigns_is_archived_index` (`is_archived`),
  KEY `campaigns_archived_at_index` (`archived_at`),
  KEY `idx_campaign_active_approved` (`is_active`,`approved_at`),
  CONSTRAINT `campaigns_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `campaigns_archived_by_foreign` FOREIGN KEY (`archived_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `campaigns_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `check_campaign_approval` CHECK (`is_active` = 0 or `is_active` = 1 and `approved_at` is not null)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `canned_responses`
--

DROP TABLE IF EXISTS `canned_responses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `canned_responses` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `content` longtext NOT NULL,
  `category` varchar(255) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `celebration_events`
--

DROP TABLE IF EXISTS `celebration_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `celebration_events` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `event_date` date NOT NULL,
  `bonus_amount_by_plan` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`bonus_amount_by_plan`)),
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `is_recurring_annually` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `channel_message_templates`
--

DROP TABLE IF EXISTS `channel_message_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `channel_message_templates` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `channel_id` bigint(20) unsigned NOT NULL,
  `template_key` varchar(255) NOT NULL,
  `template_name` varchar(255) NOT NULL,
  `template_content` text NOT NULL,
  `variables` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`variables`)),
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `subject` varchar(255) DEFAULT NULL COMMENT 'Email / notification subject',
  PRIMARY KEY (`id`),
  UNIQUE KEY `channel_message_templates_channel_id_template_key_unique` (`channel_id`,`template_key`),
  KEY `channel_message_templates_channel_id_template_key_index` (`channel_id`,`template_key`),
  CONSTRAINT `channel_message_templates_channel_id_foreign` FOREIGN KEY (`channel_id`) REFERENCES `communication_channels` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `chat_agent_status`
--

DROP TABLE IF EXISTS `chat_agent_status`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `chat_agent_status` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `agent_id` bigint(20) unsigned NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'offline',
  `active_chats_count` int(11) NOT NULL DEFAULT 0,
  `max_concurrent_chats` int(11) NOT NULL DEFAULT 5,
  `is_accepting_chats` tinyint(1) NOT NULL DEFAULT 1,
  `last_activity_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `chat_agent_status_agent_id_unique` (`agent_id`),
  KEY `chat_agent_status_status_index` (`status`),
  CONSTRAINT `chat_agent_status_agent_id_foreign` FOREIGN KEY (`agent_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `chat_typing_indicators`
--

DROP TABLE IF EXISTS `chat_typing_indicators`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `chat_typing_indicators` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `session_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `user_type` varchar(255) NOT NULL,
  `started_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `expires_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `chat_typing_indicators_session_id_user_id_unique` (`session_id`,`user_id`),
  KEY `chat_typing_indicators_user_id_foreign` (`user_id`),
  KEY `chat_typing_indicators_expires_at_index` (`expires_at`),
  CONSTRAINT `chat_typing_indicators_session_id_foreign` FOREIGN KEY (`session_id`) REFERENCES `live_chat_sessions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `chat_typing_indicators_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `communication_channels`
--

DROP TABLE IF EXISTS `communication_channels`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `communication_channels` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `channel_type` enum('email','sms','whatsapp','telegram','twitter','linkedin','in_app') NOT NULL,
  `channel_name` varchar(255) NOT NULL,
  `is_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `configuration` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`configuration`)),
  `auto_reply_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `auto_reply_message` text DEFAULT NULL,
  `available_from` time NOT NULL DEFAULT '09:00:00',
  `available_to` time NOT NULL DEFAULT '18:00:00',
  `available_days` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT '[1,2,3,4,5]' CHECK (json_valid(`available_days`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `communication_channels_channel_type_unique` (`channel_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `companies`
--

DROP TABLE IF EXISTS `companies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `companies` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `cin` varchar(21) DEFAULT NULL COMMENT 'Corporate Identity Number (Ministry of Corporate Affairs)',
  `pan` varchar(10) DEFAULT NULL COMMENT 'Permanent Account Number (Income Tax Department)',
  `registration_number` varchar(50) DEFAULT NULL COMMENT 'State/ROC registration number for non-corporate entities',
  `legal_structure` enum('private_limited','public_limited','llp','partnership','sole_proprietorship','section_8_company','opc') DEFAULT NULL COMMENT 'Legal entity structure affecting disclosure requirements',
  `description` text DEFAULT NULL,
  `sector_id` bigint(20) unsigned DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `city` varchar(255) DEFAULT NULL,
  `state` varchar(255) DEFAULT NULL,
  `country` varchar(255) DEFAULT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `sector` varchar(255) DEFAULT NULL,
  `founded_year` varchar(255) DEFAULT NULL,
  `incorporation_date` date DEFAULT NULL COMMENT 'Official date of incorporation (vs founded_year marketing field)',
  `headquarters` varchar(255) DEFAULT NULL,
  `registered_office_address` varchar(500) DEFAULT NULL COMMENT 'Legal registered office address (regulatory requirement)',
  `ceo_name` varchar(255) DEFAULT NULL,
  `board_size` int(10) unsigned DEFAULT NULL COMMENT 'Total number of board members',
  `independent_directors` int(10) unsigned DEFAULT NULL COMMENT 'Number of independent directors (SEBI requirement)',
  `board_committees` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Board committees: [{"name":"Audit Committee","members":3}]' CHECK (json_valid(`board_committees`)),
  `company_secretary` varchar(255) DEFAULT NULL COMMENT 'Company Secretary name (mandatory for certain entity types)',
  `sebi_registered` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Whether company is registered with SEBI',
  `sebi_registration_number` varchar(50) DEFAULT NULL COMMENT 'SEBI registration number if applicable',
  `sebi_approval_date` date DEFAULT NULL COMMENT 'Date of SEBI approval for Pre-IPO offering',
  `sebi_approval_expiry` date DEFAULT NULL COMMENT 'Expiry date of SEBI approval (typically 12 months)',
  `regulatory_approvals` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Other regulatory approvals: [{"authority":"RBI","approval_number":"RBI/2024/123","date":"2024-01-15"}]' CHECK (json_valid(`regulatory_approvals`)),
  `employees_count` int(11) DEFAULT NULL,
  `latest_valuation` decimal(20,2) DEFAULT NULL,
  `funding_stage` varchar(255) DEFAULT NULL,
  `total_funding` decimal(20,2) DEFAULT NULL,
  `linkedin_url` varchar(255) DEFAULT NULL,
  `twitter_url` varchar(255) DEFAULT NULL,
  `facebook_url` varchar(255) DEFAULT NULL,
  `key_metrics` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`key_metrics`)),
  `investors` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`investors`)),
  `is_featured` tinyint(1) NOT NULL DEFAULT 0,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `disclosure_stage` enum('draft','submitted','under_review','clarification_required','resubmitted','approved','rejected','suspended') NOT NULL DEFAULT 'draft' COMMENT 'Current stage in disclosure approval workflow',
  `lifecycle_state` varchar(50) DEFAULT NULL,
  `governance_state_version` int(11) NOT NULL DEFAULT 1 COMMENT 'Incremented on every governance state change for snapshot binding',
  `lifecycle_state_changed_at` timestamp NULL DEFAULT NULL COMMENT 'When lifecycle state last changed',
  `lifecycle_state_changed_by` bigint(20) unsigned DEFAULT NULL,
  `lifecycle_state_change_reason` text DEFAULT NULL COMMENT 'Reason for state change (required for suspension)',
  `suspended_at` timestamp NULL DEFAULT NULL COMMENT 'When company was suspended',
  `suspended_by` bigint(20) unsigned DEFAULT NULL,
  `suspension_reason` text DEFAULT NULL COMMENT 'Public reason shown to investors',
  `suspension_internal_notes` text DEFAULT NULL COMMENT 'Admin-only suspension notes',
  `buying_enabled` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Whether investors can buy shares (controlled by lifecycle state)',
  `show_warning_banner` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Show warning banner on company profile page',
  `warning_banner_message` text DEFAULT NULL COMMENT 'Custom warning message for investors',
  `tier_1_approved_at` timestamp NULL DEFAULT NULL COMMENT 'When all Tier 1 modules were approved',
  `tier_2_approved_at` timestamp NULL DEFAULT NULL COMMENT 'When all Tier 2 modules were approved',
  `tier_3_approved_at` timestamp NULL DEFAULT NULL COMMENT 'When all Tier 3 modules were approved',
  `disclosure_submitted_at` timestamp NULL DEFAULT NULL COMMENT 'When company first submitted disclosures for review',
  `disclosure_approved_at` timestamp NULL DEFAULT NULL COMMENT 'When admin approved disclosures (company goes live)',
  `disclosure_approved_by` bigint(20) unsigned DEFAULT NULL,
  `disclosure_rejection_reason` text DEFAULT NULL COMMENT 'Admin reason for rejecting disclosures',
  `max_users_quota` int(10) unsigned NOT NULL DEFAULT 5 COMMENT 'Maximum number of company users allowed',
  `settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Company-specific configuration' CHECK (json_valid(`settings`)),
  `is_verified` tinyint(1) NOT NULL DEFAULT 0,
  `frozen_at` timestamp NULL DEFAULT NULL,
  `frozen_by_admin_id` bigint(20) unsigned DEFAULT NULL,
  `profile_completed` tinyint(1) NOT NULL DEFAULT 0,
  `profile_completion_percentage` int(11) NOT NULL DEFAULT 0,
  `disclosure_tier` enum('tier_0_pending','tier_1_upcoming','tier_2_live','tier_3_featured') NOT NULL DEFAULT 'tier_0_pending',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `field_ownership_map` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Maps each field to owning domain: issuer_truth, governance_state, or platform_assertions' CHECK (json_valid(`field_ownership_map`)),
  `last_modified_by_ip` varchar(45) DEFAULT NULL COMMENT 'IP address of last modifier (IPv4 or IPv6)',
  `last_modified_user_agent` text DEFAULT NULL COMMENT 'User agent of last modifier for audit trail',
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `companies_slug_unique` (`slug`),
  UNIQUE KEY `companies_cin_unique` (`cin`),
  UNIQUE KEY `companies_pan_unique` (`pan`),
  KEY `companies_sector_index` (`sector`),
  KEY `companies_status_index` (`status`),
  KEY `companies_disclosure_tier_index` (`disclosure_tier`),
  KEY `companies_sector_id_foreign` (`sector_id`),
  KEY `companies_frozen_by_admin_id_foreign` (`frozen_by_admin_id`),
  KEY `companies_frozen_at_index` (`frozen_at`),
  KEY `companies_disclosure_approved_by_foreign` (`disclosure_approved_by`),
  KEY `idx_companies_disclosure_stage` (`disclosure_stage`),
  KEY `idx_companies_sebi_disclosure` (`sebi_registered`,`disclosure_stage`),
  KEY `idx_companies_disclosure_submitted` (`disclosure_submitted_at`),
  KEY `companies_lifecycle_state_changed_by_foreign` (`lifecycle_state_changed_by`),
  KEY `companies_suspended_by_foreign` (`suspended_by`),
  KEY `idx_companies_lifecycle_buying` (`buying_enabled`),
  KEY `idx_companies_suspended` (`suspended_at`),
  CONSTRAINT `companies_disclosure_approved_by_foreign` FOREIGN KEY (`disclosure_approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `companies_frozen_by_admin_id_foreign` FOREIGN KEY (`frozen_by_admin_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `companies_lifecycle_state_changed_by_foreign` FOREIGN KEY (`lifecycle_state_changed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `companies_sector_id_foreign` FOREIGN KEY (`sector_id`) REFERENCES `sectors` (`id`) ON DELETE SET NULL,
  CONSTRAINT `companies_suspended_by_foreign` FOREIGN KEY (`suspended_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `company_analytics`
--

DROP TABLE IF EXISTS `company_analytics`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `company_analytics` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `date` date NOT NULL,
  `profile_views` int(11) NOT NULL DEFAULT 0,
  `document_downloads` int(11) NOT NULL DEFAULT 0,
  `financial_report_downloads` int(11) NOT NULL DEFAULT 0,
  `deal_views` int(11) NOT NULL DEFAULT 0,
  `investor_interest_clicks` int(11) NOT NULL DEFAULT 0,
  `viewer_demographics` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`viewer_demographics`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `company_analytics_company_id_date_unique` (`company_id`,`date`),
  KEY `company_analytics_date_index` (`date`),
  CONSTRAINT `company_analytics_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `company_disclosures`
--

DROP TABLE IF EXISTS `company_disclosures`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `company_disclosures` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `disclosure_module_id` bigint(20) unsigned NOT NULL,
  `disclosure_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT 'Company-provided disclosure data conforming to module JSON schema' CHECK (json_valid(`disclosure_data`)),
  `attachments` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Supporting documents: [{"file_path":"docs/business-plan.pdf","uploaded_at":"2024-01-15"}]' CHECK (json_valid(`attachments`)),
  `status` enum('draft','submitted','under_review','clarification_required','resubmitted','approved','rejected') NOT NULL DEFAULT 'draft' COMMENT 'Current lifecycle status of this disclosure',
  `visibility` varchar(20) NOT NULL DEFAULT 'public',
  `is_visible` tinyint(1) NOT NULL DEFAULT 1,
  `completion_percentage` tinyint(3) unsigned NOT NULL DEFAULT 0 COMMENT 'Auto-calculated percentage of required fields completed (0-100)',
  `is_locked` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Whether this disclosure is locked (after approval or company freeze)',
  `freshness_state` enum('current','aging','stale','unstable') DEFAULT NULL COMMENT 'Backend-computed freshness state',
  `freshness_computed_at` timestamp NULL DEFAULT NULL COMMENT 'Timestamp of last freshness computation',
  `days_since_approval` int(10) unsigned DEFAULT NULL COMMENT 'Cached days since approval for freshness calc',
  `update_count_in_window` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Approval count within stability window',
  `next_update_expected` date DEFAULT NULL COMMENT 'When next update is expected (update_required only)',
  `freshness_override` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'AUDIT-ONLY: Admin override with reason, expiry, visibility flag' CHECK (json_valid(`freshness_override`)),
  `submitted_at` timestamp NULL DEFAULT NULL COMMENT 'When company first submitted this disclosure for review',
  `submitted_by_type` varchar(255) DEFAULT NULL COMMENT 'Morph type: App\\Models\\User or App\\Models\\CompanyUser',
  `submitted_by_id` bigint(20) unsigned DEFAULT NULL COMMENT 'Morph ID: User or CompanyUser who submitted',
  `approved_at` timestamp NULL DEFAULT NULL COMMENT 'When admin approved this disclosure',
  `approved_by` bigint(20) unsigned DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL COMMENT 'Admin reason for rejecting this disclosure',
  `rejected_at` timestamp NULL DEFAULT NULL COMMENT 'When admin rejected this disclosure',
  `rejected_by` bigint(20) unsigned DEFAULT NULL,
  `version_number` int(10) unsigned NOT NULL DEFAULT 1 COMMENT 'Current version number (increments on each approved change)',
  `supersedes_disclosure_id` int(11) DEFAULT NULL COMMENT 'Previous disclosure ID that this one replaces',
  `created_from_error_report` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Whether this disclosure was created from error report',
  `error_report_id` bigint(20) unsigned DEFAULT NULL,
  `current_version_id` bigint(20) unsigned DEFAULT NULL COMMENT 'FK to disclosure_versions - current approved snapshot',
  `last_modified_at` timestamp NULL DEFAULT NULL COMMENT 'When disclosure data was last modified (not status changes)',
  `last_modified_by_type` varchar(255) DEFAULT NULL COMMENT 'Morph type: App\\Models\\User or App\\Models\\CompanyUser',
  `last_modified_by_id` bigint(20) unsigned DEFAULT NULL COMMENT 'Morph ID: User or CompanyUser who last modified',
  `last_modified_ip` varchar(45) DEFAULT NULL COMMENT 'IP address of last modifier',
  `last_modified_user_agent` text DEFAULT NULL COMMENT 'User agent of last modifier',
  `internal_notes` text DEFAULT NULL COMMENT 'Admin-only internal notes about this disclosure',
  `draft_edit_history` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Log of all edits made while in draft status' CHECK (json_valid(`draft_edit_history`)),
  `submission_notes` text DEFAULT NULL COMMENT 'Notes provided by company when submitting for review',
  `edits_during_review` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Track all edits made during admin review for audit trail' CHECK (json_valid(`edits_during_review`)),
  `edit_count_during_review` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'How many times disclosure was edited while under review',
  `last_edit_during_review_at` timestamp NULL DEFAULT NULL COMMENT 'When disclosure was last edited during review',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_company_disclosure_module` (`company_id`,`disclosure_module_id`),
  KEY `company_disclosures_approved_by_foreign` (`approved_by`),
  KEY `company_disclosures_rejected_by_foreign` (`rejected_by`),
  KEY `idx_company_disclosures_status` (`status`),
  KEY `idx_company_disclosures_company_status` (`company_id`,`status`),
  KEY `idx_company_disclosures_module_status` (`disclosure_module_id`,`status`),
  KEY `idx_company_disclosures_submitted` (`submitted_at`),
  KEY `idx_company_disclosures_locked` (`is_locked`),
  KEY `company_disclosures_current_version_id_foreign` (`current_version_id`),
  KEY `company_disclosures_error_report_id_foreign` (`error_report_id`),
  KEY `company_disclosures_submitted_by_index` (`submitted_by_type`,`submitted_by_id`),
  KEY `company_disclosures_last_modified_by_index` (`last_modified_by_type`,`last_modified_by_id`),
  KEY `idx_freshness_refresh` (`freshness_state`,`freshness_computed_at`),
  KEY `idx_status_freshness` (`status`,`freshness_state`),
  CONSTRAINT `company_disclosures_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `company_disclosures_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `company_disclosures_current_version_id_foreign` FOREIGN KEY (`current_version_id`) REFERENCES `disclosure_versions` (`id`) ON DELETE SET NULL,
  CONSTRAINT `company_disclosures_disclosure_module_id_foreign` FOREIGN KEY (`disclosure_module_id`) REFERENCES `disclosure_modules` (`id`),
  CONSTRAINT `company_disclosures_error_report_id_foreign` FOREIGN KEY (`error_report_id`) REFERENCES `disclosure_error_reports` (`id`) ON DELETE SET NULL,
  CONSTRAINT `company_disclosures_rejected_by_foreign` FOREIGN KEY (`rejected_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `company_documents`
--

DROP TABLE IF EXISTS `company_documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `company_documents` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `uploaded_by` bigint(20) unsigned NOT NULL,
  `document_type` enum('logo','banner','pitch_deck','investor_presentation','legal_document','certificate','agreement','other') NOT NULL DEFAULT 'other',
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_type` varchar(255) DEFAULT NULL,
  `file_size` bigint(20) DEFAULT NULL,
  `is_public` tinyint(1) NOT NULL DEFAULT 0,
  `status` enum('active','archived') NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `company_documents_uploaded_by_foreign` (`uploaded_by`),
  KEY `company_documents_company_id_document_type_index` (`company_id`,`document_type`),
  CONSTRAINT `company_documents_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `company_documents_uploaded_by_foreign` FOREIGN KEY (`uploaded_by`) REFERENCES `company_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `company_financial_reports`
--

DROP TABLE IF EXISTS `company_financial_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `company_financial_reports` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `uploaded_by` bigint(20) unsigned NOT NULL,
  `year` int(11) NOT NULL,
  `quarter` enum('Q1','Q2','Q3','Q4','Annual') NOT NULL DEFAULT 'Annual',
  `report_type` enum('financial_statement','balance_sheet','cash_flow','income_statement','annual_report','other') NOT NULL DEFAULT 'annual_report',
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_size` bigint(20) DEFAULT NULL,
  `status` enum('draft','published','archived') NOT NULL DEFAULT 'draft',
  `published_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `company_financial_reports_uploaded_by_foreign` (`uploaded_by`),
  KEY `company_financial_reports_company_id_year_quarter_index` (`company_id`,`year`,`quarter`),
  CONSTRAINT `company_financial_reports_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `company_financial_reports_uploaded_by_foreign` FOREIGN KEY (`uploaded_by`) REFERENCES `company_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `company_funding_rounds`
--

DROP TABLE IF EXISTS `company_funding_rounds`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `company_funding_rounds` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `round_name` varchar(255) NOT NULL,
  `amount_raised` decimal(20,2) DEFAULT NULL,
  `currency` varchar(10) NOT NULL DEFAULT 'INR',
  `valuation` decimal(20,2) DEFAULT NULL,
  `round_date` date DEFAULT NULL,
  `investors` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`investors`)),
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `company_funding_rounds_company_id_foreign` (`company_id`),
  CONSTRAINT `company_funding_rounds_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `company_investments`
--

DROP TABLE IF EXISTS `company_investments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `company_investments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `company_id` bigint(20) unsigned NOT NULL,
  `bulk_purchase_id` bigint(20) unsigned DEFAULT NULL,
  `admin_ledger_entry_id` bigint(20) unsigned DEFAULT NULL COMMENT 'Reference to AdminLedgerEntry proving cash receipt',
  `amount` decimal(15,2) NOT NULL,
  `allocated_value` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Actual value allocated from inventory',
  `disclosure_snapshot_id` bigint(20) unsigned DEFAULT NULL,
  `status` enum('pending','active','cancelled') NOT NULL DEFAULT 'pending',
  `allocation_status` varchar(255) NOT NULL DEFAULT 'unallocated' COMMENT 'unallocated, allocated, partially_allocated',
  `invested_at` timestamp NULL DEFAULT NULL,
  `idempotency_key` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `company_investments_idempotency_key_unique` (`idempotency_key`),
  KEY `company_investments_disclosure_snapshot_id_foreign` (`disclosure_snapshot_id`),
  KEY `company_investments_user_id_status_index` (`user_id`,`status`),
  KEY `company_investments_company_id_index` (`company_id`),
  KEY `company_investments_status_index` (`status`),
  KEY `company_investments_invested_at_index` (`invested_at`),
  KEY `company_investments_idempotency_key_index` (`idempotency_key`),
  KEY `company_investments_bulk_purchase_id_status_index` (`bulk_purchase_id`,`status`),
  KEY `company_investments_allocation_status_index` (`allocation_status`),
  CONSTRAINT `company_investments_bulk_purchase_id_foreign` FOREIGN KEY (`bulk_purchase_id`) REFERENCES `bulk_purchases` (`id`) ON DELETE SET NULL,
  CONSTRAINT `company_investments_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `company_investments_disclosure_snapshot_id_foreign` FOREIGN KEY (`disclosure_snapshot_id`) REFERENCES `investment_disclosure_snapshots` (`id`) ON DELETE SET NULL,
  CONSTRAINT `company_investments_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER enforce_investment_status_machine
            BEFORE UPDATE ON company_investments
            FOR EACH ROW
            BEGIN
                DECLARE valid_transition INT DEFAULT 0;

                -- Define valid transitions
                -- pending -> processing, cancelled
                -- processing -> completed, failed
                -- completed -> (no transitions, terminal)
                -- failed -> pending (retry allowed)
                -- cancelled -> (no transitions, terminal)

                IF NEW.status = OLD.status THEN
                    SET valid_transition = 1;
                ELSEIF OLD.status = 'pending' AND NEW.status IN ('processing', 'cancelled') THEN
                    SET valid_transition = 1;
                ELSEIF OLD.status = 'processing' AND NEW.status IN ('completed', 'failed') THEN
                    SET valid_transition = 1;
                ELSEIF OLD.status = 'failed' AND NEW.status = 'pending' THEN
                    SET valid_transition = 1;
                END IF;

                IF valid_transition = 0 THEN
                    SIGNAL SQLSTATE '45000'
                    SET MESSAGE_TEXT = 'STATE MACHINE VIOLATION: Invalid investment status transition.';
                END IF;
            END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `company_lifecycle_logs`
--

DROP TABLE IF EXISTS `company_lifecycle_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `company_lifecycle_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `from_state` varchar(50) NOT NULL COMMENT 'Previous lifecycle state',
  `to_state` varchar(50) NOT NULL COMMENT 'New lifecycle state',
  `trigger` varchar(50) NOT NULL COMMENT 'What triggered change: tier_approval, admin_action, system',
  `triggered_by` bigint(20) unsigned DEFAULT NULL,
  `reason` text DEFAULT NULL COMMENT 'Reason for state change',
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Additional context: tier approved, modules approved, etc.' CHECK (json_valid(`metadata`)),
  `ip_address` varchar(45) DEFAULT NULL COMMENT 'IP address of trigger',
  `user_agent` text DEFAULT NULL COMMENT 'User agent of trigger',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `company_lifecycle_logs_triggered_by_foreign` (`triggered_by`),
  KEY `idx_lifecycle_logs_company_timeline` (`company_id`,`created_at`),
  KEY `idx_lifecycle_logs_to_state` (`to_state`),
  KEY `idx_lifecycle_logs_trigger` (`trigger`),
  CONSTRAINT `company_lifecycle_logs_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `company_lifecycle_logs_triggered_by_foreign` FOREIGN KEY (`triggered_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `company_onboarding_progress`
--

DROP TABLE IF EXISTS `company_onboarding_progress`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `company_onboarding_progress` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `completed_steps` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`completed_steps`)),
  `current_step` int(11) NOT NULL DEFAULT 1,
  `total_steps` int(11) NOT NULL DEFAULT 10,
  `completion_percentage` int(11) NOT NULL DEFAULT 0,
  `started_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `is_completed` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `company_onboarding_progress_company_id_unique` (`company_id`),
  CONSTRAINT `company_onboarding_progress_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `company_qna`
--

DROP TABLE IF EXISTS `company_qna`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `company_qna` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `asked_by_name` varchar(255) DEFAULT NULL,
  `asked_by_email` varchar(255) DEFAULT NULL,
  `question` text NOT NULL,
  `answer` text DEFAULT NULL,
  `answered_by` bigint(20) unsigned DEFAULT NULL,
  `answered_at` timestamp NULL DEFAULT NULL,
  `is_public` tinyint(1) NOT NULL DEFAULT 0,
  `is_featured` tinyint(1) NOT NULL DEFAULT 0,
  `helpful_count` int(11) NOT NULL DEFAULT 0,
  `status` enum('pending','answered','archived') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `company_qna_user_id_foreign` (`user_id`),
  KEY `company_qna_answered_by_foreign` (`answered_by`),
  KEY `company_qna_company_id_status_is_public_index` (`company_id`,`status`,`is_public`),
  CONSTRAINT `company_qna_answered_by_foreign` FOREIGN KEY (`answered_by`) REFERENCES `company_users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `company_qna_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `company_qna_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `company_share_listing_activities`
--

DROP TABLE IF EXISTS `company_share_listing_activities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `company_share_listing_activities` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `listing_id` bigint(20) unsigned NOT NULL,
  `actor_id` bigint(20) unsigned DEFAULT NULL,
  `actor_type` varchar(255) NOT NULL COMMENT 'company_user or admin',
  `action` varchar(255) NOT NULL COMMENT 'submitted, viewed, approved, rejected, etc',
  `notes` text DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Changed fields, etc' CHECK (json_valid(`metadata`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `company_share_listing_activities_actor_id_foreign` (`actor_id`),
  KEY `company_share_listing_activities_listing_id_created_at_index` (`listing_id`,`created_at`),
  CONSTRAINT `company_share_listing_activities_actor_id_foreign` FOREIGN KEY (`actor_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `company_share_listing_activities_listing_id_foreign` FOREIGN KEY (`listing_id`) REFERENCES `company_share_listings` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `company_share_listings`
--

DROP TABLE IF EXISTS `company_share_listings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `company_share_listings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `submitted_by` bigint(20) unsigned NOT NULL,
  `listing_title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `total_shares_offered` decimal(15,4) NOT NULL COMMENT 'Total shares company wants to sell',
  `face_value_per_share` decimal(10,2) NOT NULL,
  `asking_price_per_share` decimal(10,2) NOT NULL COMMENT 'Price company wants',
  `total_value` decimal(20,2) NOT NULL COMMENT 'Total offering value (shares * asking price)',
  `minimum_purchase_value` decimal(15,2) DEFAULT NULL COMMENT 'Minimum platform must buy',
  `current_company_valuation` decimal(20,2) DEFAULT NULL,
  `valuation_currency` varchar(3) NOT NULL DEFAULT 'INR',
  `percentage_of_company` decimal(5,4) DEFAULT NULL COMMENT '% of company these shares represent',
  `terms_and_conditions` text DEFAULT NULL,
  `offer_valid_until` date DEFAULT NULL COMMENT 'How long offer is valid',
  `lock_in_period` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Any restrictions on resale' CHECK (json_valid(`lock_in_period`)),
  `rights_attached` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Voting rights, dividends, etc' CHECK (json_valid(`rights_attached`)),
  `documents` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Share certificates, board resolution, etc' CHECK (json_valid(`documents`)),
  `financial_documents` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Balance sheet, P&L, etc' CHECK (json_valid(`financial_documents`)),
  `status` enum('pending','under_review','approved','rejected','expired','withdrawn') NOT NULL DEFAULT 'pending',
  `reviewed_by` bigint(20) unsigned DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `admin_notes` text DEFAULT NULL COMMENT 'Admin review notes',
  `rejection_reason` text DEFAULT NULL,
  `bulk_purchase_id` bigint(20) unsigned DEFAULT NULL,
  `approved_quantity` decimal(15,4) DEFAULT NULL COMMENT 'May be less than offered',
  `approved_price` decimal(10,2) DEFAULT NULL COMMENT 'Final negotiated price',
  `discount_percentage` decimal(5,2) DEFAULT NULL COMMENT 'Discount from asking price',
  `view_count` int(11) NOT NULL DEFAULT 0 COMMENT 'How many admins viewed',
  `last_viewed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `company_share_listings_submitted_by_foreign` (`submitted_by`),
  KEY `company_share_listings_reviewed_by_foreign` (`reviewed_by`),
  KEY `company_share_listings_bulk_purchase_id_foreign` (`bulk_purchase_id`),
  KEY `company_share_listings_company_id_status_index` (`company_id`,`status`),
  KEY `company_share_listings_status_index` (`status`),
  KEY `company_share_listings_offer_valid_until_index` (`offer_valid_until`),
  CONSTRAINT `company_share_listings_bulk_purchase_id_foreign` FOREIGN KEY (`bulk_purchase_id`) REFERENCES `bulk_purchases` (`id`),
  CONSTRAINT `company_share_listings_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `company_share_listings_reviewed_by_foreign` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`),
  CONSTRAINT `company_share_listings_submitted_by_foreign` FOREIGN KEY (`submitted_by`) REFERENCES `company_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `company_snapshots`
--

DROP TABLE IF EXISTS `company_snapshots`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `company_snapshots` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `company_share_listing_id` bigint(20) unsigned DEFAULT NULL,
  `bulk_purchase_id` bigint(20) unsigned DEFAULT NULL,
  `snapshot_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`snapshot_data`)),
  `snapshot_reason` varchar(255) NOT NULL,
  `snapshot_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `snapshot_by_admin_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `company_snapshots_company_share_listing_id_foreign` (`company_share_listing_id`),
  KEY `company_snapshots_bulk_purchase_id_foreign` (`bulk_purchase_id`),
  KEY `company_snapshots_company_id_snapshot_at_index` (`company_id`,`snapshot_at`),
  KEY `company_snapshots_snapshot_by_admin_id_foreign` (`snapshot_by_admin_id`),
  CONSTRAINT `company_snapshots_bulk_purchase_id_foreign` FOREIGN KEY (`bulk_purchase_id`) REFERENCES `bulk_purchases` (`id`),
  CONSTRAINT `company_snapshots_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `company_snapshots_company_share_listing_id_foreign` FOREIGN KEY (`company_share_listing_id`) REFERENCES `company_share_listings` (`id`),
  CONSTRAINT `company_snapshots_snapshot_by_admin_id_foreign` FOREIGN KEY (`snapshot_by_admin_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `company_team_members`
--

DROP TABLE IF EXISTS `company_team_members`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `company_team_members` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `designation` varchar(255) NOT NULL,
  `bio` text DEFAULT NULL,
  `photo_path` varchar(255) DEFAULT NULL,
  `linkedin_url` varchar(255) DEFAULT NULL,
  `twitter_url` varchar(255) DEFAULT NULL,
  `display_order` int(11) NOT NULL DEFAULT 0,
  `is_key_member` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `company_team_members_company_id_foreign` (`company_id`),
  CONSTRAINT `company_team_members_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `company_updates`
--

DROP TABLE IF EXISTS `company_updates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `company_updates` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `created_by` bigint(20) unsigned NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `update_type` enum('news','milestone','funding','product_launch','partnership','other') NOT NULL DEFAULT 'news',
  `media` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`media`)),
  `is_featured` tinyint(1) NOT NULL DEFAULT 0,
  `status` enum('draft','published','archived') NOT NULL DEFAULT 'draft',
  `published_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `company_updates_company_id_foreign` (`company_id`),
  KEY `company_updates_created_by_foreign` (`created_by`),
  CONSTRAINT `company_updates_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `company_updates_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `company_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `company_user_roles`
--

DROP TABLE IF EXISTS `company_user_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `company_user_roles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `company_id` bigint(20) unsigned NOT NULL,
  `role` enum('founder','finance','legal','viewer') NOT NULL COMMENT 'User role in company',
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Role is currently active',
  `assigned_by` bigint(20) unsigned DEFAULT NULL,
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'When role was assigned',
  `revoked_at` timestamp NULL DEFAULT NULL COMMENT 'When role was revoked',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_active_user_company_role` (`user_id`,`company_id`,`is_active`),
  KEY `idx_company_user_roles_company_role` (`company_id`,`role`),
  KEY `idx_company_user_roles_user` (`user_id`),
  KEY `company_user_roles_assigned_by_foreign` (`assigned_by`),
  CONSTRAINT `company_user_roles_assigned_by_foreign` FOREIGN KEY (`assigned_by`) REFERENCES `company_users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `company_user_roles_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `company_user_roles_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `company_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Role assignments for CompanyUser (company portal)';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `company_users`
--

DROP TABLE IF EXISTS `company_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `company_users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `contact_person_name` varchar(255) NOT NULL,
  `contact_person_designation` varchar(255) DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `status` enum('pending','active','suspended','rejected') NOT NULL DEFAULT 'pending',
  `is_verified` tinyint(1) NOT NULL DEFAULT 0,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `company_users_email_unique` (`email`),
  KEY `company_users_company_id_foreign` (`company_id`),
  CONSTRAINT `company_users_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `company_versions`
--

DROP TABLE IF EXISTS `company_versions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `company_versions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `version_number` int(11) NOT NULL COMMENT 'Sequential version number per company',
  `snapshot_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT 'Complete company data snapshot' CHECK (json_valid(`snapshot_data`)),
  `changed_fields` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Array of field names that changed in this version' CHECK (json_valid(`changed_fields`)),
  `change_summary` text DEFAULT NULL COMMENT 'Human-readable summary of changes',
  `field_diffs` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Detailed field-level diffs (old vs new values)' CHECK (json_valid(`field_diffs`)),
  `is_approval_snapshot` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'True if created at deal approval',
  `deal_id` bigint(20) unsigned DEFAULT NULL,
  `is_protected` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'If true, data cannot be modified',
  `protected_at` timestamp NULL DEFAULT NULL,
  `protection_reason` text DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `created_by_type` varchar(255) NOT NULL DEFAULT 'user' COMMENT 'user, system, admin, api',
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `company_versions_company_id_version_number_unique` (`company_id`,`version_number`),
  KEY `company_versions_deal_id_foreign` (`deal_id`),
  KEY `company_versions_company_id_index` (`company_id`),
  KEY `company_versions_company_id_version_number_index` (`company_id`,`version_number`),
  KEY `company_versions_is_approval_snapshot_index` (`is_approval_snapshot`),
  KEY `company_versions_is_protected_index` (`is_protected`),
  KEY `company_versions_created_at_index` (`created_at`),
  KEY `company_versions_created_by_index` (`created_by`),
  CONSTRAINT `company_versions_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `company_versions_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `company_versions_deal_id_foreign` FOREIGN KEY (`deal_id`) REFERENCES `deals` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `company_webinars`
--

DROP TABLE IF EXISTS `company_webinars`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `company_webinars` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `created_by` bigint(20) unsigned NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `type` enum('webinar','investor_call','ama','product_demo') NOT NULL DEFAULT 'webinar',
  `scheduled_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `duration_minutes` int(11) NOT NULL DEFAULT 60,
  `meeting_link` varchar(255) DEFAULT NULL,
  `meeting_id` varchar(255) DEFAULT NULL,
  `meeting_password` varchar(255) DEFAULT NULL,
  `max_participants` int(11) DEFAULT NULL,
  `registered_count` int(11) NOT NULL DEFAULT 0,
  `speakers` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`speakers`)),
  `agenda` text DEFAULT NULL,
  `status` enum('scheduled','live','completed','cancelled') NOT NULL DEFAULT 'scheduled',
  `recording_available` tinyint(1) NOT NULL DEFAULT 0,
  `recording_url` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `company_webinars_created_by_foreign` (`created_by`),
  KEY `company_webinars_company_id_scheduled_at_status_index` (`company_id`,`scheduled_at`,`status`),
  CONSTRAINT `company_webinars_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `company_webinars_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `company_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `content_categories`
--

DROP TABLE IF EXISTS `content_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `content_categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `type` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `content_categories_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `content_items`
--

DROP TABLE IF EXISTS `content_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `content_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `subcategory_id` bigint(20) unsigned NOT NULL,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `excerpt` text DEFAULT NULL,
  `content` longtext DEFAULT NULL,
  `featured_image` varchar(255) DEFAULT NULL,
  `thumbnail` varchar(255) DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `meta_title` varchar(255) DEFAULT NULL,
  `meta_description` text DEFAULT NULL,
  `meta_keywords` varchar(255) DEFAULT NULL,
  `status` enum('draft','published','scheduled','archived') NOT NULL DEFAULT 'draft',
  `published_at` timestamp NULL DEFAULT NULL,
  `scheduled_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `views_count` int(11) NOT NULL DEFAULT 0,
  `is_featured` tinyint(1) NOT NULL DEFAULT 0,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `content_items_slug_unique` (`slug`),
  KEY `content_items_subcategory_id_foreign` (`subcategory_id`),
  KEY `content_items_created_by_foreign` (`created_by`),
  KEY `content_items_updated_by_foreign` (`updated_by`),
  KEY `content_items_status_index` (`status`),
  KEY `content_items_published_at_index` (`published_at`),
  KEY `content_items_is_featured_index` (`is_featured`),
  CONSTRAINT `content_items_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `content_items_subcategory_id_foreign` FOREIGN KEY (`subcategory_id`) REFERENCES `content_subcategories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `content_items_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `content_subcategories`
--

DROP TABLE IF EXISTS `content_subcategories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `content_subcategories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `category_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `content_subcategories_slug_unique` (`slug`),
  KEY `content_subcategories_category_id_foreign` (`category_id`),
  CONSTRAINT `content_subcategories_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `content_categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `contextual_suggestions`
--

DROP TABLE IF EXISTS `contextual_suggestions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `contextual_suggestions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `page_pattern` varchar(255) NOT NULL,
  `trigger_element` varchar(255) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` enum('tip','warning','info','success') NOT NULL DEFAULT 'tip',
  `related_articles` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`related_articles`)),
  `related_tutorials` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`related_tutorials`)),
  `action_url` varchar(255) DEFAULT NULL,
  `action_text` varchar(255) DEFAULT NULL,
  `display_conditions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`display_conditions`)),
  `max_displays` int(11) NOT NULL DEFAULT -1,
  `days_between_displays` int(11) NOT NULL DEFAULT 0,
  `priority` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `contextual_suggestions_page_pattern_is_active_index` (`page_pattern`,`is_active`),
  KEY `contextual_suggestions_priority_index` (`priority`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `daily_dispute_snapshots`
--

DROP TABLE IF EXISTS `daily_dispute_snapshots`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `daily_dispute_snapshots` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `snapshot_date` date NOT NULL,
  `plan_id` bigint(20) unsigned DEFAULT NULL,
  `total_disputes` int(10) unsigned NOT NULL DEFAULT 0,
  `open_disputes` int(10) unsigned NOT NULL DEFAULT 0,
  `under_investigation_disputes` int(10) unsigned NOT NULL DEFAULT 0,
  `resolved_disputes` int(10) unsigned NOT NULL DEFAULT 0,
  `escalated_disputes` int(10) unsigned NOT NULL DEFAULT 0,
  `total_chargeback_count` bigint(20) unsigned NOT NULL DEFAULT 0,
  `total_chargeback_amount_paise` bigint(20) unsigned NOT NULL DEFAULT 0,
  `confirmed_chargeback_count` bigint(20) unsigned NOT NULL DEFAULT 0,
  `confirmed_chargeback_amount_paise` bigint(20) unsigned NOT NULL DEFAULT 0,
  `low_severity_count` int(10) unsigned NOT NULL DEFAULT 0,
  `medium_severity_count` int(10) unsigned NOT NULL DEFAULT 0,
  `high_severity_count` int(10) unsigned NOT NULL DEFAULT 0,
  `critical_severity_count` int(10) unsigned NOT NULL DEFAULT 0,
  `category_breakdown` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`category_breakdown`)),
  `blocked_users_count` int(10) unsigned NOT NULL DEFAULT 0,
  `high_risk_users_count` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `daily_dispute_snapshots_unique_idx` (`snapshot_date`,`plan_id`),
  KEY `daily_dispute_snapshots_plan_id_foreign` (`plan_id`),
  KEY `daily_dispute_snapshots_date_idx` (`snapshot_date`),
  CONSTRAINT `daily_dispute_snapshots_plan_id_foreign` FOREIGN KEY (`plan_id`) REFERENCES `plans` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `data_export_jobs`
--

DROP TABLE IF EXISTS `data_export_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `data_export_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(255) NOT NULL,
  `format` varchar(255) NOT NULL,
  `filters` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`filters`)),
  `columns` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`columns`)),
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `file_path` varchar(255) DEFAULT NULL,
  `file_size` int(11) DEFAULT NULL,
  `record_count` int(11) NOT NULL DEFAULT 0,
  `created_by` bigint(20) unsigned NOT NULL,
  `started_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `data_export_jobs_created_by_status_index` (`created_by`,`status`),
  KEY `data_export_jobs_expires_at_index` (`expires_at`),
  CONSTRAINT `data_export_jobs_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `deal_approvals`
--

DROP TABLE IF EXISTS `deal_approvals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `deal_approvals` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `deal_id` bigint(20) unsigned NOT NULL,
  `status` enum('draft','pending_review','under_review','approved','rejected','published','archived') NOT NULL DEFAULT 'draft',
  `submitted_at` timestamp NULL DEFAULT NULL,
  `review_started_at` timestamp NULL DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `rejected_at` timestamp NULL DEFAULT NULL,
  `published_at` timestamp NULL DEFAULT NULL,
  `submitter_id` bigint(20) unsigned DEFAULT NULL,
  `reviewer_id` bigint(20) unsigned DEFAULT NULL,
  `approver_id` bigint(20) unsigned DEFAULT NULL,
  `rejected_by` bigint(20) unsigned DEFAULT NULL,
  `publisher_id` bigint(20) unsigned DEFAULT NULL,
  `submission_notes` text DEFAULT NULL,
  `review_notes` text DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `checklist_items` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`checklist_items`)),
  `sla_hours` int(11) NOT NULL DEFAULT 168,
  `sla_deadline` timestamp NULL DEFAULT NULL,
  `is_overdue` tinyint(1) NOT NULL DEFAULT 0,
  `days_pending` int(11) DEFAULT NULL,
  `company_version_id` bigint(20) unsigned DEFAULT NULL,
  `snapshot_created` tinyint(1) NOT NULL DEFAULT 0,
  `priority` enum('low','normal','high','urgent') NOT NULL DEFAULT 'normal',
  `is_expedited` tinyint(1) NOT NULL DEFAULT 0,
  `expedited_reason` text DEFAULT NULL,
  `compliance_checks` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`compliance_checks`)),
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `deal_approvals_submitter_id_foreign` (`submitter_id`),
  KEY `deal_approvals_reviewer_id_foreign` (`reviewer_id`),
  KEY `deal_approvals_approver_id_foreign` (`approver_id`),
  KEY `deal_approvals_rejected_by_foreign` (`rejected_by`),
  KEY `deal_approvals_publisher_id_foreign` (`publisher_id`),
  KEY `deal_approvals_company_version_id_foreign` (`company_version_id`),
  KEY `deal_approvals_status_sla_deadline_index` (`status`,`sla_deadline`),
  KEY `deal_approvals_status_submitted_at_index` (`status`,`submitted_at`),
  KEY `deal_approvals_deal_id_status_index` (`deal_id`,`status`),
  KEY `deal_approvals_status_approved_at_index` (`status`,`approved_at`),
  CONSTRAINT `deal_approvals_approver_id_foreign` FOREIGN KEY (`approver_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `deal_approvals_company_version_id_foreign` FOREIGN KEY (`company_version_id`) REFERENCES `company_versions` (`id`) ON DELETE SET NULL,
  CONSTRAINT `deal_approvals_deal_id_foreign` FOREIGN KEY (`deal_id`) REFERENCES `deals` (`id`) ON DELETE CASCADE,
  CONSTRAINT `deal_approvals_publisher_id_foreign` FOREIGN KEY (`publisher_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `deal_approvals_rejected_by_foreign` FOREIGN KEY (`rejected_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `deal_approvals_reviewer_id_foreign` FOREIGN KEY (`reviewer_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `deal_approvals_submitter_id_foreign` FOREIGN KEY (`submitter_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `deals`
--

DROP TABLE IF EXISTS `deals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `deals` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint(20) unsigned DEFAULT NULL,
  `company_id` bigint(20) unsigned NOT NULL,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `sector` varchar(255) NOT NULL,
  `deal_type` enum('live','upcoming','closed') NOT NULL DEFAULT 'upcoming',
  `min_investment` decimal(15,2) DEFAULT NULL,
  `max_investment` decimal(15,2) DEFAULT NULL,
  `valuation` decimal(20,2) DEFAULT NULL,
  `valuation_currency` varchar(3) NOT NULL DEFAULT 'INR',
  `share_price` decimal(10,2) DEFAULT NULL,
  `deal_opens_at` timestamp NULL DEFAULT NULL,
  `deal_closes_at` timestamp NULL DEFAULT NULL,
  `days_remaining` int(11) DEFAULT NULL,
  `highlights` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`highlights`)),
  `documents` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`documents`)),
  `video_url` varchar(255) DEFAULT NULL,
  `status` enum('draft','active','paused','closed') NOT NULL DEFAULT 'draft',
  `approved_by_admin_id` bigint(20) unsigned DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `rejected_by_admin_id` bigint(20) unsigned DEFAULT NULL,
  `rejected_at` timestamp NULL DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `is_featured` tinyint(1) NOT NULL DEFAULT 0,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `deals_slug_unique` (`slug`),
  KEY `deals_product_id_foreign` (`product_id`),
  KEY `deals_deal_type_index` (`deal_type`),
  KEY `deals_status_index` (`status`),
  KEY `deals_sector_index` (`sector`),
  KEY `deals_company_id_foreign` (`company_id`),
  KEY `deals_approved_by_admin_id_foreign` (`approved_by_admin_id`),
  KEY `deals_rejected_by_admin_id_foreign` (`rejected_by_admin_id`),
  KEY `deals_approved_at_index` (`approved_at`),
  KEY `deals_rejected_at_index` (`rejected_at`),
  CONSTRAINT `deals_approved_by_admin_id_foreign` FOREIGN KEY (`approved_by_admin_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `deals_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`),
  CONSTRAINT `deals_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `deals_rejected_by_admin_id_foreign` FOREIGN KEY (`rejected_by_admin_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `disclosure_approvals`
--

DROP TABLE IF EXISTS `disclosure_approvals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `disclosure_approvals` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_disclosure_id` bigint(20) unsigned NOT NULL,
  `company_id` bigint(20) unsigned NOT NULL,
  `disclosure_module_id` bigint(20) unsigned NOT NULL,
  `request_type` enum('initial_submission','resubmission','revision','correction') NOT NULL COMMENT 'Type of approval request',
  `requested_by` bigint(20) unsigned NOT NULL,
  `requested_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT 'When approval was requested',
  `submission_notes` text DEFAULT NULL COMMENT 'Company notes explaining submission/changes',
  `disclosure_version_number` int(10) unsigned NOT NULL COMMENT 'Version number at time of this approval request',
  `disclosure_version_id` bigint(20) unsigned DEFAULT NULL,
  `status` enum('pending','under_review','clarification_required','approved','rejected','revoked') NOT NULL DEFAULT 'pending' COMMENT 'Current status of this approval attempt',
  `reviewed_by` bigint(20) unsigned DEFAULT NULL,
  `review_started_at` timestamp NULL DEFAULT NULL COMMENT 'When admin started review',
  `review_completed_at` timestamp NULL DEFAULT NULL COMMENT 'When admin completed review (approved/rejected/clarification)',
  `review_duration_minutes` int(10) unsigned DEFAULT NULL COMMENT 'How long review took (for SLA tracking)',
  `decision_notes` text DEFAULT NULL COMMENT 'Admin explanation of decision (required for rejection)',
  `checklist_completed` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Approval checklist items verified: [{"item":"Verify revenue","checked":true,"notes":"Bank statement provided"}]' CHECK (json_valid(`checklist_completed`)),
  `identified_issues` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Issues found during review: [{"field":"revenue_streams","issue":"Missing Q4 data","severity":"high"}]' CHECK (json_valid(`identified_issues`)),
  `clarifications_requested` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Number of clarifications requested during this approval cycle',
  `clarifications_due_date` timestamp NULL DEFAULT NULL COMMENT 'Deadline for company to answer clarifications',
  `all_clarifications_answered` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Whether all clarifications have been answered',
  `approval_conditions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Conditions attached to approval: [{"condition":"Must update quarterly","due":"2024-04-01"}]' CHECK (json_valid(`approval_conditions`)),
  `conditional_approval_expires_at` timestamp NULL DEFAULT NULL COMMENT 'When conditional approval expires (if applicable)',
  `is_revoked` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Whether this approval has been revoked',
  `revoked_by` bigint(20) unsigned DEFAULT NULL,
  `revoked_at` timestamp NULL DEFAULT NULL COMMENT 'When approval was revoked',
  `revocation_reason` text DEFAULT NULL COMMENT 'REQUIRED: Reason for revoking approval (regulatory requirement)',
  `investor_notification_required` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Whether investors must be notified of revocation',
  `sla_due_date` timestamp NULL DEFAULT NULL COMMENT 'SLA deadline for admin to complete review (typically 5 business days)',
  `sla_breached` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Whether SLA was breached',
  `business_days_to_review` int(10) unsigned DEFAULT NULL COMMENT 'Business days from submission to decision (SLA metric)',
  `sebi_compliance_status` varchar(50) DEFAULT NULL COMMENT 'SEBI compliance flag: "compliant", "delayed", "non_compliant"',
  `approval_stage` int(10) unsigned NOT NULL DEFAULT 1 COMMENT 'Approval stage (1=primary review, 2=secondary, etc.) - Future use',
  `approval_chain` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Multi-approver chain (Future): [{"stage":1,"approver_id":5,"status":"approved","date":"2024-01-18"}]' CHECK (json_valid(`approval_chain`)),
  `internal_notes` text DEFAULT NULL COMMENT 'Admin-only internal notes (not visible to company)',
  `reminder_count` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Reminders sent to admin for pending review',
  `last_reminder_at` timestamp NULL DEFAULT NULL COMMENT 'When last reminder sent to admin',
  `requested_by_ip` varchar(45) DEFAULT NULL COMMENT 'IP address when approval requested',
  `reviewed_by_ip` varchar(45) DEFAULT NULL COMMENT 'IP address when review completed',
  `requested_by_user_agent` text DEFAULT NULL COMMENT 'User agent when approval requested',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `disclosure_approvals_disclosure_module_id_foreign` (`disclosure_module_id`),
  KEY `disclosure_approvals_disclosure_version_id_foreign` (`disclosure_version_id`),
  KEY `disclosure_approvals_revoked_by_foreign` (`revoked_by`),
  KEY `idx_approvals_status` (`status`),
  KEY `idx_approvals_disclosure_timeline` (`company_disclosure_id`,`created_at`),
  KEY `idx_approvals_company_status` (`company_id`,`status`),
  KEY `idx_approvals_reviewer_status` (`reviewed_by`,`status`),
  KEY `idx_approvals_sla` (`sla_due_date`),
  KEY `idx_approvals_sla_breach` (`sla_breached`,`status`),
  KEY `idx_approvals_revoked` (`is_revoked`),
  KEY `disclosure_approvals_requested_by_foreign` (`requested_by`),
  CONSTRAINT `disclosure_approvals_company_disclosure_id_foreign` FOREIGN KEY (`company_disclosure_id`) REFERENCES `company_disclosures` (`id`) ON DELETE CASCADE,
  CONSTRAINT `disclosure_approvals_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `disclosure_approvals_disclosure_module_id_foreign` FOREIGN KEY (`disclosure_module_id`) REFERENCES `disclosure_modules` (`id`),
  CONSTRAINT `disclosure_approvals_disclosure_version_id_foreign` FOREIGN KEY (`disclosure_version_id`) REFERENCES `disclosure_versions` (`id`) ON DELETE SET NULL,
  CONSTRAINT `disclosure_approvals_requested_by_foreign` FOREIGN KEY (`requested_by`) REFERENCES `company_users` (`id`),
  CONSTRAINT `disclosure_approvals_reviewed_by_foreign` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `disclosure_approvals_revoked_by_foreign` FOREIGN KEY (`revoked_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `disclosure_change_log`
--

DROP TABLE IF EXISTS `disclosure_change_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `disclosure_change_log` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_disclosure_id` bigint(20) unsigned NOT NULL,
  `company_id` bigint(20) unsigned NOT NULL,
  `change_type` enum('created','draft_updated','submitted','approved','rejected','error_reported','clarification_added','clarification_answered') NOT NULL COMMENT 'Type of change that occurred',
  `change_summary` text NOT NULL COMMENT 'Human-readable summary of change',
  `changed_fields` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'List of fields that changed' CHECK (json_valid(`changed_fields`)),
  `field_diffs` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Before/after values for changed fields' CHECK (json_valid(`field_diffs`)),
  `changed_by` bigint(20) unsigned DEFAULT NULL,
  `changed_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT 'When change occurred',
  `change_reason` varchar(500) DEFAULT NULL COMMENT 'Reason for change (if provided)',
  `is_material_change` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Whether change is material enough to notify investors',
  `investor_notification_priority` enum('none','low','medium','high','critical') NOT NULL DEFAULT 'none' COMMENT 'Priority level for investor notification',
  `version_before` int(11) DEFAULT NULL COMMENT 'Disclosure version before change',
  `version_after` int(11) DEFAULT NULL COMMENT 'Disclosure version after change',
  `is_visible_to_investors` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Whether change should be visible in change history',
  `investor_visible_at` timestamp NULL DEFAULT NULL COMMENT 'When change became visible to investors (may be delayed for admin review)',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `disclosure_change_log_changed_by_foreign` (`changed_by`),
  KEY `disclosure_change_log_company_id_changed_at_index` (`company_id`,`changed_at`),
  KEY `disclosure_change_log_company_disclosure_id_changed_at_index` (`company_disclosure_id`,`changed_at`),
  KEY `disclosure_change_log_change_type_index` (`change_type`),
  KEY `dcl_material_priority_idx` (`is_material_change`,`investor_notification_priority`),
  CONSTRAINT `disclosure_change_log_changed_by_foreign` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `disclosure_change_log_company_disclosure_id_foreign` FOREIGN KEY (`company_disclosure_id`) REFERENCES `company_disclosures` (`id`) ON DELETE CASCADE,
  CONSTRAINT `disclosure_change_log_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `disclosure_clarifications`
--

DROP TABLE IF EXISTS `disclosure_clarifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `disclosure_clarifications` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_disclosure_id` bigint(20) unsigned NOT NULL,
  `company_id` bigint(20) unsigned NOT NULL,
  `disclosure_module_id` bigint(20) unsigned NOT NULL,
  `parent_id` bigint(20) unsigned DEFAULT NULL,
  `thread_depth` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Nesting level: 0=root, 1=reply, 2=reply-to-reply, etc.',
  `question_subject` varchar(255) NOT NULL COMMENT 'Brief subject line: "Revenue Growth Clarification"',
  `question_body` text NOT NULL COMMENT 'Detailed question from admin',
  `question_type` enum('missing_data','inconsistency','insufficient_detail','verification','compliance','other') NOT NULL DEFAULT 'other' COMMENT 'Category of clarification request',
  `asked_by` bigint(20) unsigned NOT NULL,
  `asked_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT 'When question was asked',
  `issuer_response_due_at` timestamp NULL DEFAULT NULL COMMENT 'Deadline for issuer to respond (5 business days from asked_at)',
  `issuer_response_overdue` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'True if issuer has not responded by due date',
  `field_path` varchar(500) DEFAULT NULL COMMENT 'JSON path to specific field: "disclosure_data.revenue_streams[0].percentage"',
  `highlighted_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Snapshot of problematic data: {"revenue_streams":[{"name":"Subscriptions","percentage":120}]}' CHECK (json_valid(`highlighted_data`)),
  `suggested_fix` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Admin suggestion for correction (optional)' CHECK (json_valid(`suggested_fix`)),
  `answer_body` text DEFAULT NULL COMMENT 'Company response to clarification request',
  `answered_by_type` varchar(255) DEFAULT NULL COMMENT 'Morph type: App\\Models\\User or App\\Models\\CompanyUser',
  `answered_by_id` bigint(20) unsigned DEFAULT NULL COMMENT 'Morph ID: User or CompanyUser who answered',
  `answered_at` timestamp NULL DEFAULT NULL COMMENT 'When company answered',
  `admin_review_due_at` timestamp NULL DEFAULT NULL COMMENT 'Deadline for admin to review answer (3 business days from answered_at)',
  `admin_review_overdue` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'True if admin has not reviewed by due date',
  `escalated_at` timestamp NULL DEFAULT NULL COMMENT 'When clarification was escalated due to timeout',
  `escalation_reason` varchar(500) DEFAULT NULL COMMENT 'Why clarification was escalated',
  `escalated_to_admin_id` bigint(20) unsigned DEFAULT NULL COMMENT 'Admin user ID clarification was escalated to',
  `is_expired` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Clarification expired, no longer active',
  `expired_at` timestamp NULL DEFAULT NULL COMMENT 'When clarification was marked as expired',
  `expiry_reason` varchar(500) DEFAULT NULL COMMENT 'Why clarification expired',
  `supporting_documents` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Documents uploaded with answer: [{"file_path":"docs/revenue-proof.pdf","description":"Bank statement"}]' CHECK (json_valid(`supporting_documents`)),
  `status` enum('open','answered','accepted','disputed','withdrawn') NOT NULL DEFAULT 'open' COMMENT 'Current status of clarification',
  `resolution_notes` text DEFAULT NULL COMMENT 'Admin notes on acceptance/dispute of answer',
  `resolved_by` bigint(20) unsigned DEFAULT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL COMMENT 'When admin resolved (accepted/disputed) the clarification',
  `priority` enum('low','medium','high','critical') NOT NULL DEFAULT 'medium' COMMENT 'Urgency of clarification (affects SLA)',
  `due_date` timestamp NULL DEFAULT NULL COMMENT 'Deadline for company to respond (typically 7 days)',
  `is_blocking` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Whether approval is blocked until this is resolved',
  `internal_notes` text DEFAULT NULL COMMENT 'Admin-only internal notes (not visible to company)',
  `is_visible_to_company` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Whether company can see this clarification (for internal admin discussions)',
  `reminder_count` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'How many reminder emails sent to company',
  `last_reminder_at` timestamp NULL DEFAULT NULL COMMENT 'When last reminder was sent',
  `asked_by_ip` varchar(45) DEFAULT NULL COMMENT 'IP address of admin when question created',
  `answered_by_ip` varchar(45) DEFAULT NULL COMMENT 'IP address of company user when answered',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `disclosure_clarifications_disclosure_module_id_foreign` (`disclosure_module_id`),
  KEY `disclosure_clarifications_asked_by_foreign` (`asked_by`),
  KEY `disclosure_clarifications_resolved_by_foreign` (`resolved_by`),
  KEY `idx_clarifications_status` (`status`),
  KEY `idx_clarifications_disclosure_status` (`company_disclosure_id`,`status`),
  KEY `idx_clarifications_company_due` (`company_id`,`status`,`due_date`),
  KEY `idx_clarifications_thread` (`parent_id`),
  KEY `idx_clarifications_blocking` (`is_blocking`,`status`),
  KEY `idx_clarifications_due` (`due_date`),
  KEY `disclosure_clarifications_issuer_response_due_at_index` (`issuer_response_due_at`),
  KEY `disclosure_clarifications_admin_review_due_at_index` (`admin_review_due_at`),
  KEY `disclosure_clarifications_issuer_response_overdue_status_index` (`issuer_response_overdue`,`status`),
  KEY `disclosure_clarifications_admin_review_overdue_status_index` (`admin_review_overdue`,`status`),
  KEY `disclosure_clarifications_answered_by_index` (`answered_by_type`,`answered_by_id`),
  CONSTRAINT `disclosure_clarifications_asked_by_foreign` FOREIGN KEY (`asked_by`) REFERENCES `users` (`id`),
  CONSTRAINT `disclosure_clarifications_company_disclosure_id_foreign` FOREIGN KEY (`company_disclosure_id`) REFERENCES `company_disclosures` (`id`) ON DELETE CASCADE,
  CONSTRAINT `disclosure_clarifications_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `disclosure_clarifications_disclosure_module_id_foreign` FOREIGN KEY (`disclosure_module_id`) REFERENCES `disclosure_modules` (`id`),
  CONSTRAINT `disclosure_clarifications_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `disclosure_clarifications` (`id`) ON DELETE CASCADE,
  CONSTRAINT `disclosure_clarifications_resolved_by_foreign` FOREIGN KEY (`resolved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `disclosure_documents`
--

DROP TABLE IF EXISTS `disclosure_documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `disclosure_documents` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `disclosure_event_id` bigint(20) unsigned NOT NULL,
  `company_disclosure_id` bigint(20) unsigned NOT NULL,
  `file_name` varchar(255) NOT NULL COMMENT 'Original filename with extension',
  `storage_path` varchar(500) NOT NULL COMMENT 'Path in storage (e.g., disclosure-documents/company-123/file.pdf)',
  `mime_type` varchar(100) NOT NULL COMMENT 'File MIME type (e.g., application/pdf)',
  `file_size` bigint(20) unsigned NOT NULL COMMENT 'File size in bytes',
  `file_hash` varchar(64) DEFAULT NULL COMMENT 'SHA256 hash for integrity verification',
  `document_type` varchar(100) DEFAULT NULL COMMENT 'Document classification: financial_statement, legal_document, etc',
  `description` text DEFAULT NULL COMMENT 'Optional description provided by uploader',
  `uploaded_by_type` varchar(255) NOT NULL,
  `uploaded_by_id` bigint(20) unsigned NOT NULL,
  `uploaded_by_name` varchar(255) NOT NULL COMMENT 'Cached uploader name (denormalized)',
  `is_public` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Whether document is visible to investors (after approval)',
  `visibility` enum('company','platform','public') NOT NULL DEFAULT 'company' COMMENT 'Who can access this document',
  `uploaded_from_ip` varchar(45) DEFAULT NULL COMMENT 'IP address of uploader',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT 'When document was uploaded (immutable)',
  PRIMARY KEY (`id`),
  KEY `idx_disclosure_documents_uploader` (`uploaded_by_type`,`uploaded_by_id`),
  KEY `idx_disclosure_docs_thread_time` (`company_disclosure_id`,`created_at`),
  KEY `idx_disclosure_docs_event` (`disclosure_event_id`),
  KEY `idx_disclosure_docs_storage` (`storage_path`),
  KEY `idx_disclosure_docs_hash` (`file_hash`),
  CONSTRAINT `disclosure_documents_company_disclosure_id_foreign` FOREIGN KEY (`company_disclosure_id`) REFERENCES `company_disclosures` (`id`) ON DELETE CASCADE,
  CONSTRAINT `disclosure_documents_disclosure_event_id_foreign` FOREIGN KEY (`disclosure_event_id`) REFERENCES `disclosure_events` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `disclosure_error_reports`
--

DROP TABLE IF EXISTS `disclosure_error_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `disclosure_error_reports` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_disclosure_id` bigint(20) unsigned NOT NULL,
  `company_id` bigint(20) unsigned NOT NULL,
  `reported_by` bigint(20) unsigned DEFAULT NULL,
  `reported_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT 'When error was reported',
  `error_description` text NOT NULL COMMENT 'Description of what was wrong',
  `correction_reason` text NOT NULL COMMENT 'Why correction is needed',
  `original_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT 'Snapshot of approved data with error' CHECK (json_valid(`original_data`)),
  `corrected_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT 'Proposed corrected data' CHECK (json_valid(`corrected_data`)),
  `admin_notes` text DEFAULT NULL COMMENT 'Admin response to error report',
  `admin_reviewed_by` bigint(20) unsigned DEFAULT NULL,
  `admin_reviewed_at` timestamp NULL DEFAULT NULL COMMENT 'When admin reviewed error report',
  `ip_address` varchar(45) DEFAULT NULL COMMENT 'IP address of reporter',
  `user_agent` text DEFAULT NULL COMMENT 'User agent of reporter',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `disclosure_error_reports_reported_by_foreign` (`reported_by`),
  KEY `disclosure_error_reports_admin_reviewed_by_foreign` (`admin_reviewed_by`),
  KEY `idx_error_reports_company_timeline` (`company_id`,`reported_at`),
  KEY `idx_error_reports_disclosure` (`company_disclosure_id`),
  KEY `idx_error_reports_reviewed` (`admin_reviewed_at`),
  CONSTRAINT `disclosure_error_reports_admin_reviewed_by_foreign` FOREIGN KEY (`admin_reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `disclosure_error_reports_company_disclosure_id_foreign` FOREIGN KEY (`company_disclosure_id`) REFERENCES `company_disclosures` (`id`) ON DELETE CASCADE,
  CONSTRAINT `disclosure_error_reports_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `disclosure_error_reports_reported_by_foreign` FOREIGN KEY (`reported_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `disclosure_events`
--

DROP TABLE IF EXISTS `disclosure_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `disclosure_events` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_disclosure_id` bigint(20) unsigned NOT NULL,
  `event_type` enum('submission','clarification','response','approval','status_change','rejection') NOT NULL COMMENT 'Type of timeline event',
  `actor_type` varchar(255) NOT NULL,
  `actor_id` bigint(20) unsigned NOT NULL,
  `actor_name` varchar(255) NOT NULL COMMENT 'Cached name for display (denormalized for performance)',
  `message` text DEFAULT NULL COMMENT 'Event message or description',
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Additional structured data: clarification_id, status transitions, etc' CHECK (json_valid(`metadata`)),
  `disclosure_clarification_id` bigint(20) unsigned DEFAULT NULL,
  `status_from` varchar(50) DEFAULT NULL COMMENT 'Previous status (for status_change events)',
  `status_to` varchar(50) DEFAULT NULL COMMENT 'New status (for status_change events)',
  `ip_address` varchar(45) DEFAULT NULL COMMENT 'IP address of actor',
  `user_agent` text DEFAULT NULL COMMENT 'User agent string',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT 'When event occurred (immutable)',
  PRIMARY KEY (`id`),
  KEY `idx_disclosure_events_actor` (`actor_type`,`actor_id`),
  KEY `disclosure_events_disclosure_clarification_id_foreign` (`disclosure_clarification_id`),
  KEY `idx_disclosure_events_thread_time` (`company_disclosure_id`,`created_at`),
  KEY `idx_disclosure_events_type_time` (`event_type`,`created_at`),
  KEY `idx_disclosure_events_actor_type` (`actor_type`),
  CONSTRAINT `disclosure_events_company_disclosure_id_foreign` FOREIGN KEY (`company_disclosure_id`) REFERENCES `company_disclosures` (`id`) ON DELETE CASCADE,
  CONSTRAINT `disclosure_events_disclosure_clarification_id_foreign` FOREIGN KEY (`disclosure_clarification_id`) REFERENCES `disclosure_clarifications` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `disclosure_modules`
--

DROP TABLE IF EXISTS `disclosure_modules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `disclosure_modules` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL COMMENT 'Unique code: business_model, financials, risks, governance, legal',
  `name` varchar(255) NOT NULL COMMENT 'Display name: "Business Model & Operations"',
  `description` text DEFAULT NULL COMMENT 'Admin-facing description of what this module captures',
  `category` enum('governance','financial','legal','operational') NOT NULL DEFAULT 'operational' COMMENT 'Disclosure requirement category for UI grouping',
  `help_text` text DEFAULT NULL COMMENT 'Company-facing instructions for filling out this module',
  `is_required` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Whether companies must complete this module to submit',
  `tier` tinyint(3) unsigned NOT NULL DEFAULT 1 COMMENT 'Approval tier: 1=Basic (public visibility), 2=Financials (buying), 3=Advanced (trust)',
  `document_type` enum('update_required','version_controlled') DEFAULT NULL COMMENT 'Determines freshness calculation logic',
  `expected_update_days` int(10) unsigned DEFAULT NULL COMMENT 'For update_required: days before document becomes stale',
  `stability_window_days` int(10) unsigned DEFAULT NULL COMMENT 'For version_controlled: window for measuring change frequency',
  `max_changes_per_window` int(10) unsigned NOT NULL DEFAULT 2 COMMENT 'For version_controlled: changes above this = unstable',
  `freshness_weight` decimal(3,2) NOT NULL DEFAULT 1.00 COMMENT 'Contribution weight to pillar vitality (1.00 = full weight)',
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Whether this module is currently in use (for deprecation)',
  `display_order` int(10) unsigned NOT NULL DEFAULT 999 COMMENT 'Order in which modules appear in company disclosure flow',
  `icon` varchar(50) DEFAULT NULL COMMENT 'Icon identifier for frontend display (e.g., "building", "chart-line")',
  `color` varchar(20) DEFAULT NULL COMMENT 'Color code for frontend theming (e.g., "blue", "#3B82F6")',
  `json_schema` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT 'JSON Schema v7 defining structure, validation rules, required fields' CHECK (json_valid(`json_schema`)),
  `default_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Default/template data structure for new disclosures (optional)' CHECK (json_valid(`default_data`)),
  `sebi_category` varchar(100) DEFAULT NULL COMMENT 'Maps to SEBI disclosure category (for regulatory reporting)',
  `regulatory_references` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'References to SEBI regulations: [{"regulation":"ICDR","section":"26(1)","description":"..."}]' CHECK (json_valid(`regulatory_references`)),
  `requires_admin_approval` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Whether changes to this module require admin approval',
  `min_approval_reviews` int(10) unsigned NOT NULL DEFAULT 1 COMMENT 'Minimum number of admin reviews required (future: multi-approver)',
  `approval_checklist` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Checklist items admin must verify: ["Verify revenue figures", "Check risk disclosures"]' CHECK (json_valid(`approval_checklist`)),
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `disclosure_modules_code_unique` (`code`),
  KEY `disclosure_modules_created_by_foreign` (`created_by`),
  KEY `disclosure_modules_updated_by_foreign` (`updated_by`),
  KEY `idx_disclosure_modules_active` (`is_active`,`is_required`,`display_order`),
  KEY `idx_disclosure_modules_sebi` (`sebi_category`),
  KEY `idx_disclosure_modules_tier` (`tier`),
  KEY `idx_disclosure_modules_category_tier` (`category`,`tier`,`is_active`),
  CONSTRAINT `disclosure_modules_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `disclosure_modules_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `disclosure_versions`
--

DROP TABLE IF EXISTS `disclosure_versions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `disclosure_versions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_disclosure_id` bigint(20) unsigned NOT NULL,
  `company_id` bigint(20) unsigned NOT NULL,
  `disclosure_module_id` bigint(20) unsigned NOT NULL,
  `version_number` int(10) unsigned NOT NULL COMMENT 'Sequential version number (1, 2, 3...) per disclosure',
  `version_hash` varchar(64) NOT NULL COMMENT 'SHA-256 hash of disclosure_data for tamper detection',
  `disclosure_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT 'IMMUTABLE: Full snapshot of disclosure data at this version' CHECK (json_valid(`disclosure_data`)),
  `attachments` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'IMMUTABLE: Supporting documents at this version' CHECK (json_valid(`attachments`)),
  `changes_summary` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'What changed from previous version: {"revenue_streams":"Updated Q3 data","customer_segments":"Added Enterprise"}' CHECK (json_valid(`changes_summary`)),
  `change_reason` text DEFAULT NULL COMMENT 'Company-provided reason for this change (required for v2+)',
  `is_locked` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'IMMUTABILITY FLAG: Always true, prevents any modifications',
  `locked_at` timestamp NULL DEFAULT NULL COMMENT 'When this version was locked (set on creation)',
  `approved_at` timestamp NULL DEFAULT NULL COMMENT 'When admin approved this version',
  `approved_by` bigint(20) unsigned DEFAULT NULL COMMENT 'Admin who approved this version (REQUIRED)',
  `approval_notes` text DEFAULT NULL COMMENT 'Admin notes from approval review',
  `was_investor_visible` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Whether this version was ever visible to investors (for liability tracking)',
  `first_investor_view_at` timestamp NULL DEFAULT NULL COMMENT 'When first investor viewed this version (for disclosure timing compliance)',
  `investor_view_count` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'How many times investors viewed this version (materiality assessment)',
  `linked_transactions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Investor purchases made under this version: [{"transaction_id":123,"date":"2024-01-15"}]' CHECK (json_valid(`linked_transactions`)),
  `sebi_filing_reference` varchar(100) DEFAULT NULL COMMENT 'Reference to SEBI filing (if this version was filed)',
  `sebi_filed_at` timestamp NULL DEFAULT NULL COMMENT 'When this version was filed with SEBI',
  `certification` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Digital signature/certification: {"signed_by":"CEO","signature_hash":"...","timestamp":"..."}' CHECK (json_valid(`certification`)),
  `created_by_ip` varchar(45) DEFAULT NULL COMMENT 'IP address when version was created',
  `created_by_user_agent` text DEFAULT NULL COMMENT 'User agent when version was created',
  `created_by_type` varchar(255) DEFAULT NULL COMMENT 'Morph type: App\\Models\\User or App\\Models\\CompanyUser',
  `created_by_id` bigint(20) unsigned DEFAULT NULL COMMENT 'Morph ID: User or CompanyUser who created this version',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_disclosure_version_number` (`company_disclosure_id`,`version_number`),
  KEY `disclosure_versions_disclosure_module_id_foreign` (`disclosure_module_id`),
  KEY `idx_disclosure_versions_lookup` (`company_disclosure_id`,`version_number`),
  KEY `idx_disclosure_versions_company_timeline` (`company_id`,`approved_at`),
  KEY `idx_disclosure_versions_hash` (`version_hash`),
  KEY `idx_disclosure_versions_investor_visible` (`was_investor_visible`),
  KEY `idx_disclosure_versions_sebi` (`sebi_filing_reference`),
  KEY `disclosure_versions_created_by_index` (`created_by_type`,`created_by_id`),
  CONSTRAINT `disclosure_versions_company_disclosure_id_foreign` FOREIGN KEY (`company_disclosure_id`) REFERENCES `company_disclosures` (`id`),
  CONSTRAINT `disclosure_versions_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`),
  CONSTRAINT `disclosure_versions_disclosure_module_id_foreign` FOREIGN KEY (`disclosure_module_id`) REFERENCES `disclosure_modules` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `disputes`
--

DROP TABLE IF EXISTS `disputes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `disputes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `raised_by_user_id` bigint(20) unsigned DEFAULT NULL,
  `status` enum('open','under_investigation','resolved','closed','escalated') NOT NULL DEFAULT 'open',
  `severity` enum('low','medium','high','critical') NOT NULL DEFAULT 'medium',
  `category` enum('financial_disclosure','investment_processing','kyc_verification','fund_transfer','platform_service','company_conduct','investor_conduct','other') NOT NULL DEFAULT 'other',
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `evidence` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Documents, screenshots, etc.' CHECK (json_valid(`evidence`)),
  `resolution` text DEFAULT NULL,
  `admin_notes` text DEFAULT NULL,
  `assigned_to_admin_id` bigint(20) unsigned DEFAULT NULL,
  `opened_at` timestamp NULL DEFAULT NULL,
  `investigation_started_at` timestamp NULL DEFAULT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `closed_at` timestamp NULL DEFAULT NULL,
  `blocks_investment` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Whether this dispute should block new investments',
  `requires_platform_freeze` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Whether this dispute requires freezing company operations',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `disputes_raised_by_user_id_foreign` (`raised_by_user_id`),
  KEY `disputes_company_id_status_index` (`company_id`,`status`),
  KEY `disputes_user_id_status_index` (`user_id`,`status`),
  KEY `disputes_status_index` (`status`),
  KEY `disputes_severity_index` (`severity`),
  KEY `disputes_assigned_to_admin_id_index` (`assigned_to_admin_id`),
  CONSTRAINT `disputes_assigned_to_admin_id_foreign` FOREIGN KEY (`assigned_to_admin_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `disputes_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `disputes_raised_by_user_id_foreign` FOREIGN KEY (`raised_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `disputes_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `email_logs`
--

DROP TABLE IF EXISTS `email_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `email_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `email_template_id` bigint(20) unsigned DEFAULT NULL,
  `template_slug` varchar(255) NOT NULL,
  `to_email` varchar(255) NOT NULL,
  `recipient_name` varchar(255) DEFAULT NULL,
  `subject` varchar(255) NOT NULL,
  `body` longtext NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'queued',
  `provider` varchar(255) DEFAULT NULL,
  `provider_message_id` varchar(255) DEFAULT NULL,
  `provider_response` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`provider_response`)),
  `sent_at` timestamp NULL DEFAULT NULL,
  `delivered_at` timestamp NULL DEFAULT NULL,
  `opened_at` timestamp NULL DEFAULT NULL,
  `clicked_at` timestamp NULL DEFAULT NULL,
  `bounced_at` timestamp NULL DEFAULT NULL,
  `complained_at` timestamp NULL DEFAULT NULL,
  `ip_address` varchar(255) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `open_count` int(11) NOT NULL DEFAULT 0,
  `click_count` int(11) NOT NULL DEFAULT 0,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `error_message` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `email_logs_user_id_created_at_index` (`user_id`,`created_at`),
  KEY `email_logs_email_template_id_created_at_index` (`email_template_id`,`created_at`),
  KEY `email_logs_status_created_at_index` (`status`,`created_at`),
  KEY `email_logs_provider_message_id_index` (`provider_message_id`),
  KEY `email_logs_sent_at_index` (`sent_at`),
  KEY `email_logs_opened_at_index` (`opened_at`),
  CONSTRAINT `email_logs_email_template_id_foreign` FOREIGN KEY (`email_template_id`) REFERENCES `email_templates` (`id`) ON DELETE SET NULL,
  CONSTRAINT `email_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `email_templates`
--

DROP TABLE IF EXISTS `email_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `email_templates` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `body` longtext NOT NULL,
  `variables` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`variables`)),
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email_templates_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `error_logs`
--

DROP TABLE IF EXISTS `error_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `error_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `level` varchar(255) NOT NULL DEFAULT 'error',
  `message` varchar(255) NOT NULL,
  `exception` text DEFAULT NULL,
  `stack_trace` text DEFAULT NULL,
  `file` varchar(255) DEFAULT NULL,
  `line` int(11) DEFAULT NULL,
  `url` varchar(255) DEFAULT NULL,
  `method` varchar(255) DEFAULT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `ip_address` varchar(255) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `context` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`context`)),
  `is_resolved` tinyint(1) NOT NULL DEFAULT 0,
  `resolution_notes` text DEFAULT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `resolved_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `error_logs_user_id_foreign` (`user_id`),
  KEY `error_logs_resolved_by_foreign` (`resolved_by`),
  KEY `error_logs_level_created_at_index` (`level`,`created_at`),
  KEY `error_logs_is_resolved_index` (`is_resolved`),
  CONSTRAINT `error_logs_resolved_by_foreign` FOREIGN KEY (`resolved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `error_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `faqs`
--

DROP TABLE IF EXISTS `faqs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `faqs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `question` varchar(255) NOT NULL,
  `answer` text NOT NULL,
  `category` varchar(255) NOT NULL DEFAULT 'general',
  `display_order` int(11) NOT NULL DEFAULT 0,
  `is_published` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `category_id` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `feature_flags`
--

DROP TABLE IF EXISTS `feature_flags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `feature_flags` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `is_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `rollout_percentage` int(11) NOT NULL DEFAULT 0,
  `target_users` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`target_users`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Master enable/disable switch',
  `percentage` tinyint(3) unsigned NOT NULL DEFAULT 100 COMMENT 'Rollout percentage (0–100)',
  PRIMARY KEY (`id`),
  UNIQUE KEY `feature_flags_key_unique` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `fund_locks`
--

DROP TABLE IF EXISTS `fund_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fund_locks` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `lock_type` enum('withdrawal','investment_hold','penalty_hold','manual') NOT NULL,
  `lockable_type` varchar(255) NOT NULL,
  `lockable_id` bigint(20) unsigned NOT NULL,
  `amount_paise` bigint(20) NOT NULL,
  `status` enum('active','released','expired') NOT NULL DEFAULT 'active',
  `locked_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `released_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `locked_by` bigint(20) unsigned DEFAULT NULL,
  `released_by` bigint(20) unsigned DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fund_locks_user_id_status_index` (`user_id`,`status`),
  KEY `fund_locks_lockable_type_lockable_id_index` (`lockable_type`,`lockable_id`),
  KEY `fund_locks_status_expires_at_index` (`status`,`expires_at`),
  CONSTRAINT `fund_locks_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `generated_reports`
--

DROP TABLE IF EXISTS `generated_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `generated_reports` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `report_type` varchar(50) NOT NULL,
  `format` enum('pdf','excel','csv') NOT NULL DEFAULT 'pdf',
  `date_range` varchar(100) NOT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `file_size` bigint(20) unsigned DEFAULT NULL,
  `status` enum('pending','ready','failed') NOT NULL DEFAULT 'pending',
  `error_message` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `generated_reports_user_id_index` (`user_id`),
  KEY `generated_reports_status_index` (`status`),
  KEY `generated_reports_user_id_report_type_index` (`user_id`,`report_type`),
  KEY `generated_reports_created_at_index` (`created_at`),
  CONSTRAINT `generated_reports_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `help_tooltips`
--

DROP TABLE IF EXISTS `help_tooltips`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `help_tooltips` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `element_id` varchar(255) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `position` enum('top','bottom','left','right','auto') NOT NULL DEFAULT 'auto',
  `page_url` varchar(255) DEFAULT NULL,
  `user_role` enum('all','user','admin','company') NOT NULL DEFAULT 'all',
  `conditions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`conditions`)),
  `icon` varchar(255) DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `video_url` varchar(255) DEFAULT NULL,
  `show_once` tinyint(1) NOT NULL DEFAULT 0,
  `dismissible` tinyint(1) NOT NULL DEFAULT 1,
  `auto_hide_seconds` int(11) DEFAULT NULL,
  `priority` int(11) NOT NULL DEFAULT 0,
  `learn_more_url` varchar(255) DEFAULT NULL,
  `cta_text` varchar(255) DEFAULT NULL,
  `cta_url` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `help_tooltips_element_id_unique` (`element_id`),
  KEY `help_tooltips_page_url_is_active_index` (`page_url`,`is_active`),
  KEY `help_tooltips_user_role_index` (`user_role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `investment_denial_log`
--

DROP TABLE IF EXISTS `investment_denial_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `investment_denial_log` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `blockers` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT 'Array of guard blockers that prevented investment' CHECK (json_valid(`blockers`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `session_id` varchar(255) DEFAULT NULL,
  `attempted_amount` decimal(15,2) DEFAULT NULL,
  `denial_source` varchar(255) NOT NULL DEFAULT 'buy_enablement_guard' COMMENT 'Which service/guard denied the investment',
  `company_state` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Snapshot of company state at denial time' CHECK (json_valid(`company_state`)),
  `user_state` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Snapshot of user state at denial time' CHECK (json_valid(`user_state`)),
  `user_notified` tinyint(1) NOT NULL DEFAULT 0,
  `user_notified_at` timestamp NULL DEFAULT NULL,
  `resolved` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Whether the blocking issues were later resolved',
  `resolved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `investment_denial_log_company_id_created_at_index` (`company_id`,`created_at`),
  KEY `investment_denial_log_user_id_created_at_index` (`user_id`,`created_at`),
  KEY `investment_denial_log_created_at_index` (`created_at`),
  KEY `investment_denial_log_denial_source_index` (`denial_source`),
  KEY `investment_denial_log_resolved_created_at_index` (`resolved`,`created_at`),
  CONSTRAINT `investment_denial_log_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `investment_denial_log_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `investment_disclosure_snapshots`
--

DROP TABLE IF EXISTS `investment_disclosure_snapshots`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `investment_disclosure_snapshots` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `investment_id` bigint(20) unsigned DEFAULT NULL COMMENT 'Investment this snapshot is bound to (nullable for pre-purchase snapshots)',
  `user_id` bigint(20) unsigned NOT NULL COMMENT 'Investor who saw this snapshot',
  `company_id` bigint(20) unsigned NOT NULL COMMENT 'Company being invested in',
  `snapshot_timestamp` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'Exact moment snapshot was captured',
  `snapshot_trigger` varchar(50) NOT NULL DEFAULT 'investment_purchase' COMMENT 'What triggered snapshot: investment_purchase, company_view, etc.',
  `disclosure_snapshot` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT 'All company disclosures visible at snapshot time: {disclosure_id: {module, status, data, version_id}}' CHECK (json_valid(`disclosure_snapshot`)),
  `metrics_snapshot` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Platform metrics at snapshot time: {completeness, financial_band, etc.}' CHECK (json_valid(`metrics_snapshot`)),
  `risk_flags_snapshot` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Active risk flags at snapshot time: [{flag_type, severity, description}]' CHECK (json_valid(`risk_flags_snapshot`)),
  `valuation_context_snapshot` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Valuation context at snapshot time: {peer_median, context_band, etc.}' CHECK (json_valid(`valuation_context_snapshot`)),
  `governance_snapshot` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Phase 2 governance state: {lifecycle_state, buying_enabled, governance_state_version, tier_approvals, suspension_status}' CHECK (json_valid(`governance_snapshot`)),
  `public_page_view_snapshot` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Complete public company page data investor saw: {disclosures, platform_context, warnings, etc.}' CHECK (json_valid(`public_page_view_snapshot`)),
  `acknowledgements_snapshot` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Status of all risk acknowledgements at snapshot time' CHECK (json_valid(`acknowledgements_snapshot`)),
  `acknowledgements_granted` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Specific acknowledgements investor granted during investment flow' CHECK (json_valid(`acknowledgements_granted`)),
  `disclosure_versions_map` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT 'Exact version IDs investor saw: {disclosure_id: version_id}' CHECK (json_valid(`disclosure_versions_map`)),
  `was_under_review` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Were any disclosures under admin review at snapshot time',
  `company_lifecycle_state` varchar(50) DEFAULT NULL COMMENT 'Company lifecycle state at snapshot time',
  `buying_enabled_at_snapshot` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Was buying enabled when snapshot taken',
  `investor_notes` text DEFAULT NULL COMMENT 'Investor can add notes about what influenced their decision',
  `viewed_documents` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Which disclosure attachments investor opened' CHECK (json_valid(`viewed_documents`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `session_id` varchar(100) DEFAULT NULL,
  `is_immutable` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Once captured, snapshot CANNOT be modified',
  `hash_algorithm` varchar(20) NOT NULL DEFAULT 'sha256' COMMENT 'Hash algorithm used: sha256, sha512, etc.',
  `snapshot_hash` varchar(64) DEFAULT NULL COMMENT 'SHA-256 hash of disclosure_snapshot (computed from database value)',
  `locked_at` timestamp NULL DEFAULT NULL COMMENT 'When snapshot was locked (after investment confirmed)',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `investment_disclosure_snapshots_investment_id_index` (`investment_id`),
  KEY `investment_disclosure_snapshots_user_id_company_id_index` (`user_id`,`company_id`),
  KEY `investment_disclosure_snapshots_snapshot_timestamp_index` (`snapshot_timestamp`),
  KEY `ids_trigger_ts_ids` (`snapshot_trigger`,`snapshot_timestamp`),
  KEY `investment_disclosure_snapshots_company_id_foreign` (`company_id`),
  KEY `idx_user_snapshot_history` (`user_id`,`snapshot_timestamp`),
  KEY `idx_snapshot_hash` (`snapshot_hash`),
  CONSTRAINT `investment_disclosure_snapshots_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `investment_disclosure_snapshots_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `investment_snapshots`
--

DROP TABLE IF EXISTS `investment_snapshots`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `investment_snapshots` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `investment_id` bigint(20) unsigned NOT NULL,
  `company_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `snapshot_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT 'When snapshot was captured',
  `snapshot_type` varchar(50) NOT NULL DEFAULT 'investment_creation' COMMENT 'Type of snapshot: investment_creation, material_change, platform_update',
  `company_snapshot` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT 'Full company data at snapshot time' CHECK (json_valid(`company_snapshot`)),
  `platform_context_snapshot` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT 'Platform context (visibility, tier, buying status) at snapshot time' CHECK (json_valid(`platform_context_snapshot`)),
  `disclosure_snapshot` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT 'Disclosure data investor saw at investment time' CHECK (json_valid(`disclosure_snapshot`)),
  `risk_acknowledgements_snapshot` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT 'Risk acknowledgements investor agreed to' CHECK (json_valid(`risk_acknowledgements_snapshot`)),
  `deal_snapshot` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Deal/offering data at snapshot time' CHECK (json_valid(`deal_snapshot`)),
  `investment_amount` decimal(15,2) NOT NULL COMMENT 'Amount invested',
  `shares_allocated` int(11) DEFAULT NULL COMMENT 'Shares allocated at snapshot time',
  `price_per_share` decimal(15,2) DEFAULT NULL COMMENT 'Price per share at snapshot time',
  `idempotency_key` varchar(100) DEFAULT NULL COMMENT 'Idempotency key from original investment',
  `ip_address` varchar(45) DEFAULT NULL COMMENT 'IP address of investor at investment time',
  `user_agent` text DEFAULT NULL COMMENT 'User agent at investment time',
  `is_immutable` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Snapshot cannot be modified (always true)',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `investment_snapshots_snapshot_at_index` (`snapshot_at`),
  KEY `investment_snapshots_company_id_snapshot_at_index` (`company_id`,`snapshot_at`),
  KEY `investment_snapshots_user_id_snapshot_at_index` (`user_id`,`snapshot_at`),
  KEY `investment_snapshots_investment_id_index` (`investment_id`),
  CONSTRAINT `investment_snapshots_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `investment_snapshots_investment_id_foreign` FOREIGN KEY (`investment_id`) REFERENCES `investments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `investment_snapshots_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `investments`
--

DROP TABLE IF EXISTS `investments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `investments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `platform_context_snapshot_id` bigint(20) unsigned DEFAULT NULL COMMENT 'Reference to platform context snapshot at investment time',
  `user_id` bigint(20) unsigned NOT NULL,
  `subscription_id` bigint(20) unsigned NOT NULL,
  `deal_id` bigint(20) unsigned NOT NULL,
  `company_id` bigint(20) unsigned DEFAULT NULL,
  `investment_code` varchar(255) NOT NULL,
  `shares_allocated` int(11) NOT NULL,
  `price_per_share` decimal(15,2) NOT NULL,
  `total_amount` decimal(15,2) NOT NULL,
  `status` enum('pending','active','exited','cancelled') NOT NULL DEFAULT 'pending',
  `allocation_status` enum('pending','processing','completed','failed') NOT NULL DEFAULT 'pending',
  `allocated_at` timestamp NULL DEFAULT NULL,
  `allocation_error` text DEFAULT NULL,
  `invested_at` timestamp NULL DEFAULT NULL,
  `exited_at` timestamp NULL DEFAULT NULL,
  `exit_price_per_share` decimal(15,2) DEFAULT NULL,
  `exit_amount` decimal(15,2) DEFAULT NULL,
  `profit_loss` decimal(15,2) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `investments_investment_code_unique` (`investment_code`),
  KEY `investments_company_id_foreign` (`company_id`),
  KEY `investments_user_id_status_index` (`user_id`,`status`),
  KEY `investments_subscription_id_index` (`subscription_id`),
  KEY `investments_deal_id_index` (`deal_id`),
  KEY `investments_status_index` (`status`),
  KEY `investments_invested_at_index` (`invested_at`),
  KEY `investments_allocation_status_index` (`allocation_status`),
  KEY `investments_platform_context_snapshot_id_index` (`platform_context_snapshot_id`),
  CONSTRAINT `investments_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE SET NULL,
  CONSTRAINT `investments_deal_id_foreign` FOREIGN KEY (`deal_id`) REFERENCES `deals` (`id`) ON DELETE CASCADE,
  CONSTRAINT `investments_platform_context_snapshot_id_foreign` FOREIGN KEY (`platform_context_snapshot_id`) REFERENCES `platform_context_snapshots` (`id`) ON DELETE SET NULL,
  CONSTRAINT `investments_subscription_id_foreign` FOREIGN KEY (`subscription_id`) REFERENCES `subscriptions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `investments_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `investor_acknowledgement_log`
--

DROP TABLE IF EXISTS `investor_acknowledgement_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `investor_acknowledgement_log` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `company_id` bigint(20) unsigned NOT NULL,
  `event_type` enum('acknowledgement_requested','acknowledgement_granted','acknowledgement_expired','acknowledgement_renewed','investment_blocked_missing_ack') NOT NULL,
  `acknowledgements_status` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`acknowledgements_status`)),
  `event_details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `investor_acknowledgement_log_company_id_foreign` (`company_id`),
  KEY `investor_acknowledgement_log_user_id_company_id_index` (`user_id`,`company_id`),
  KEY `investor_acknowledgement_log_event_type_index` (`event_type`),
  KEY `investor_acknowledgement_log_created_at_index` (`created_at`),
  CONSTRAINT `investor_acknowledgement_log_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `investor_acknowledgement_log_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `investor_interests`
--

DROP TABLE IF EXISTS `investor_interests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `investor_interests` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `investor_email` varchar(255) DEFAULT NULL,
  `investor_name` varchar(255) DEFAULT NULL,
  `investor_phone` varchar(255) DEFAULT NULL,
  `interest_level` enum('low','medium','high') NOT NULL DEFAULT 'medium',
  `investment_range_min` decimal(15,2) DEFAULT NULL,
  `investment_range_max` decimal(15,2) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `status` enum('pending','contacted','qualified','not_interested') NOT NULL DEFAULT 'pending',
  `admin_notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `investor_interests_user_id_foreign` (`user_id`),
  KEY `investor_interests_company_id_status_index` (`company_id`,`status`),
  CONSTRAINT `investor_interests_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `investor_interests_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `investor_journey_transitions`
--

DROP TABLE IF EXISTS `investor_journey_transitions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `investor_journey_transitions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `journey_id` bigint(20) unsigned NOT NULL,
  `from_state` varchar(30) NOT NULL,
  `to_state` varchar(30) NOT NULL,
  `transition_type` varchar(30) NOT NULL,
  `was_valid_transition` tinyint(1) NOT NULL DEFAULT 1,
  `validation_result` varchar(50) DEFAULT NULL,
  `state_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`state_data`)),
  `acknowledgements_at_transition` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`acknowledgements_at_transition`)),
  `snapshot_id_at_transition` bigint(20) unsigned DEFAULT NULL,
  `triggered_by` varchar(30) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `transitioned_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `investor_journey_transitions_journey_id_transitioned_at_index` (`journey_id`,`transitioned_at`),
  KEY `investor_journey_transitions_from_state_to_state_index` (`from_state`,`to_state`),
  CONSTRAINT `investor_journey_transitions_journey_id_foreign` FOREIGN KEY (`journey_id`) REFERENCES `investor_journeys` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER prevent_journey_transition_update
            BEFORE UPDATE ON investor_journey_transitions
            FOR EACH ROW
            BEGIN
                SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'IMMUTABILITY VIOLATION: investor_journey_transitions cannot be updated. Create a new record instead.';
            END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER prevent_journey_transition_delete
            BEFORE DELETE ON investor_journey_transitions
            FOR EACH ROW
            BEGIN
                SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'IMMUTABILITY VIOLATION: investor_journey_transitions cannot be deleted. Records are permanent for audit purposes.';
            END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `investor_journeys`
--

DROP TABLE IF EXISTS `investor_journeys`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `investor_journeys` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `company_id` bigint(20) unsigned NOT NULL,
  `current_state` varchar(30) NOT NULL DEFAULT 'initiated',
  `state_entered_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `journey_token` varchar(64) NOT NULL,
  `journey_started_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `journey_completed_at` timestamp NULL DEFAULT NULL,
  `is_complete` tinyint(1) NOT NULL DEFAULT 0,
  `completion_type` varchar(20) DEFAULT NULL,
  `platform_snapshot_id` bigint(20) unsigned DEFAULT NULL,
  `investment_snapshot_id` bigint(20) unsigned DEFAULT NULL,
  `snapshot_bound_at` timestamp NULL DEFAULT NULL,
  `acknowledged_risks` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`acknowledged_risks`)),
  `risks_acknowledged_at` timestamp NULL DEFAULT NULL,
  `accepted_terms` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`accepted_terms`)),
  `terms_accepted_at` timestamp NULL DEFAULT NULL,
  `company_investment_id` bigint(20) unsigned DEFAULT NULL,
  `block_reason` varchar(255) DEFAULT NULL,
  `block_code` varchar(50) DEFAULT NULL,
  `blocked_at` timestamp NULL DEFAULT NULL,
  `session_id` varchar(100) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `device_fingerprint` varchar(100) DEFAULT NULL,
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_expired` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `investor_journeys_user_id_company_id_journey_token_unique` (`user_id`,`company_id`,`journey_token`),
  UNIQUE KEY `investor_journeys_journey_token_unique` (`journey_token`),
  KEY `investor_journeys_company_id_foreign` (`company_id`),
  KEY `investor_journeys_user_id_company_id_is_complete_index` (`user_id`,`company_id`,`is_complete`),
  KEY `investor_journeys_current_state_index` (`current_state`),
  KEY `investor_journeys_journey_token_index` (`journey_token`),
  KEY `investor_journeys_expires_at_is_expired_index` (`expires_at`,`is_expired`),
  CONSTRAINT `investor_journeys_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `investor_journeys_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER enforce_journey_state_machine
            BEFORE UPDATE ON investor_journeys
            FOR EACH ROW
            BEGIN
                DECLARE valid_transition INT DEFAULT 0;

                -- Define valid transitions
                -- initiated -> viewing
                -- viewing -> acknowledging
                -- acknowledging -> reviewing
                -- reviewing -> confirming
                -- confirming -> processing
                -- processing -> invested
                -- ANY -> blocked (always allowed)
                -- ANY -> abandoned (always allowed)

                IF NEW.current_state = OLD.current_state THEN
                    -- No state change, allow
                    SET valid_transition = 1;
                ELSEIF NEW.current_state IN ('blocked', 'abandoned') THEN
                    -- Emergency exits always allowed
                    SET valid_transition = 1;
                ELSEIF OLD.current_state = 'initiated' AND NEW.current_state = 'viewing' THEN
                    SET valid_transition = 1;
                ELSEIF OLD.current_state = 'viewing' AND NEW.current_state = 'acknowledging' THEN
                    SET valid_transition = 1;
                ELSEIF OLD.current_state = 'acknowledging' AND NEW.current_state = 'reviewing' THEN
                    SET valid_transition = 1;
                ELSEIF OLD.current_state = 'reviewing' AND NEW.current_state = 'confirming' THEN
                    SET valid_transition = 1;
                ELSEIF OLD.current_state = 'confirming' AND NEW.current_state = 'processing' THEN
                    SET valid_transition = 1;
                ELSEIF OLD.current_state = 'processing' AND NEW.current_state = 'invested' THEN
                    SET valid_transition = 1;
                END IF;

                -- Terminal states cannot transition
                IF OLD.current_state IN ('invested', 'blocked', 'abandoned') AND OLD.current_state != NEW.current_state THEN
                    SIGNAL SQLSTATE '45000'
                    SET MESSAGE_TEXT = 'STATE MACHINE VIOLATION: Cannot transition from terminal state.';
                END IF;

                IF valid_transition = 0 THEN
                    SIGNAL SQLSTATE '45000'
                    SET MESSAGE_TEXT = 'STATE MACHINE VIOLATION: Invalid journey state transition. States must follow sequence.';
                END IF;
            END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `investor_risk_acknowledgements`
--

DROP TABLE IF EXISTS `investor_risk_acknowledgements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `investor_risk_acknowledgements` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `company_id` bigint(20) unsigned NOT NULL,
  `acknowledgement_type` enum('illiquidity','no_guarantee','platform_non_advisory','material_changes') NOT NULL,
  `acknowledged_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `session_id` varchar(100) DEFAULT NULL,
  `investment_id` bigint(20) unsigned DEFAULT NULL,
  `acknowledgement_text_shown` text DEFAULT NULL,
  `platform_context_snapshot_id` bigint(20) unsigned DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `is_expired` tinyint(1) NOT NULL DEFAULT 0,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `investor_risk_acknowledgements_company_id_foreign` (`company_id`),
  KEY `fk_ira_investment` (`investment_id`),
  KEY `fk_ira_platform_snapshot` (`platform_context_snapshot_id`),
  KEY `idx_ira_user_company_type` (`user_id`,`company_id`,`acknowledgement_type`),
  KEY `idx_ira_acknowledged_at` (`acknowledged_at`),
  KEY `idx_ira_expires_at` (`expires_at`),
  CONSTRAINT `fk_ira_investment` FOREIGN KEY (`investment_id`) REFERENCES `investments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_ira_platform_snapshot` FOREIGN KEY (`platform_context_snapshot_id`) REFERENCES `platform_context_snapshots` (`id`) ON DELETE SET NULL,
  CONSTRAINT `investor_risk_acknowledgements_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `investor_risk_acknowledgements_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `investor_view_history`
--

DROP TABLE IF EXISTS `investor_view_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `investor_view_history` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `company_id` bigint(20) unsigned NOT NULL,
  `viewed_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT 'When investor viewed this company',
  `view_type` varchar(50) NOT NULL COMMENT 'Type of view: profile, disclosure, metrics',
  `disclosure_snapshot` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'IDs and versions of disclosures viewed' CHECK (json_valid(`disclosure_snapshot`)),
  `metrics_snapshot` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Platform metrics at time of view' CHECK (json_valid(`metrics_snapshot`)),
  `risk_flags_snapshot` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Risk flags visible at time of view' CHECK (json_valid(`risk_flags_snapshot`)),
  `was_under_review` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Whether data was under review at view time',
  `data_as_of` timestamp NULL DEFAULT NULL COMMENT 'Timestamp of data viewed',
  `session_id` varchar(100) DEFAULT NULL COMMENT 'Session ID for grouping related views',
  `ip_address` varchar(45) DEFAULT NULL COMMENT 'IP address of viewer',
  `user_agent` text DEFAULT NULL COMMENT 'User agent string',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `investor_view_history_user_id_company_id_viewed_at_index` (`user_id`,`company_id`,`viewed_at`),
  KEY `investor_view_history_company_id_index` (`company_id`),
  KEY `investor_view_history_viewed_at_index` (`viewed_at`),
  CONSTRAINT `investor_view_history_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `investor_view_history_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `ip_whitelist`
--

DROP TABLE IF EXISTS `ip_whitelist`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ip_whitelist` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `ip_address` varchar(255) NOT NULL COMMENT 'The whitelisted IP address.',
  `description` varchar(255) NOT NULL COMMENT 'A short description for why this IP is whitelisted.',
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Status for quick lookups and querying.',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ip_whitelist_ip_address_unique` (`ip_address`),
  KEY `ip_whitelist_is_active_index` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `job_batches`
--

DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `job_executions`
--

DROP TABLE IF EXISTS `job_executions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `job_executions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `job_class` varchar(255) NOT NULL,
  `idempotency_key` varchar(255) NOT NULL,
  `job_queue` varchar(255) DEFAULT NULL,
  `status` varchar(255) NOT NULL,
  `started_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `failed_at` timestamp NULL DEFAULT NULL,
  `result` text DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  `stack_trace` text DEFAULT NULL,
  `input_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`input_data`)),
  `attempt_number` int(11) NOT NULL DEFAULT 1,
  `max_attempts` int(11) NOT NULL DEFAULT 3,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_job_idempotency` (`job_class`,`idempotency_key`),
  KEY `job_executions_status_index` (`status`),
  KEY `job_executions_started_at_index` (`started_at`),
  KEY `job_executions_created_at_index` (`created_at`),
  CONSTRAINT `check_job_execution_status` CHECK (`status` in ('pending','processing','completed','failed'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `job_state_tracking`
--

DROP TABLE IF EXISTS `job_state_tracking`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `job_state_tracking` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `workflow_type` varchar(255) NOT NULL,
  `workflow_id` varchar(255) NOT NULL,
  `entity_id` bigint(20) unsigned NOT NULL,
  `current_state` varchar(255) NOT NULL,
  `previous_state` varchar(255) DEFAULT NULL,
  `completed_steps` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`completed_steps`)),
  `pending_steps` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`pending_steps`)),
  `failed_steps` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`failed_steps`)),
  `total_steps` int(11) NOT NULL DEFAULT 0,
  `completed_steps_count` int(11) NOT NULL DEFAULT 0,
  `completion_percentage` decimal(5,2) NOT NULL DEFAULT 0.00,
  `started_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `expected_completion_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `is_stuck` tinyint(1) NOT NULL DEFAULT 0,
  `stuck_reason` varchar(255) DEFAULT NULL,
  `stuck_detected_at` timestamp NULL DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_workflow_entity` (`workflow_type`,`entity_id`),
  KEY `job_state_tracking_current_state_index` (`current_state`),
  KEY `job_state_tracking_is_stuck_index` (`is_stuck`),
  KEY `job_state_tracking_started_at_index` (`started_at`),
  KEY `job_state_tracking_last_updated_at_index` (`last_updated_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) unsigned NOT NULL,
  `reserved_at` int(10) unsigned DEFAULT NULL,
  `available_at` int(10) unsigned NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `journey_acknowledgement_bindings`
--

DROP TABLE IF EXISTS `journey_acknowledgement_bindings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `journey_acknowledgement_bindings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `journey_id` bigint(20) unsigned NOT NULL,
  `acknowledgement_type` varchar(50) NOT NULL,
  `acknowledgement_key` varchar(100) NOT NULL,
  `acknowledgement_version` varchar(20) DEFAULT NULL,
  `journey_state_at_ack` varchar(30) NOT NULL,
  `transition_id` bigint(20) unsigned DEFAULT NULL,
  `snapshot_id_at_ack` bigint(20) unsigned DEFAULT NULL,
  `snapshot_hash` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`snapshot_hash`)),
  `acknowledgement_text` text DEFAULT NULL,
  `explicit_consent` tinyint(1) NOT NULL DEFAULT 0,
  `acknowledged_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ja_bind_journey_ack_key_idx` (`journey_id`,`acknowledgement_key`),
  KEY `ja_bind_journey_ack_type_idx` (`journey_id`,`acknowledgement_type`),
  CONSTRAINT `journey_acknowledgement_bindings_journey_id_foreign` FOREIGN KEY (`journey_id`) REFERENCES `investor_journeys` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER prevent_ack_binding_update
            BEFORE UPDATE ON journey_acknowledgement_bindings
            FOR EACH ROW
            BEGIN
                SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'IMMUTABILITY VIOLATION: journey_acknowledgement_bindings cannot be updated. Acknowledgements are permanent records.';
            END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER prevent_ack_binding_delete
            BEFORE DELETE ON journey_acknowledgement_bindings
            FOR EACH ROW
            BEGIN
                SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'IMMUTABILITY VIOLATION: journey_acknowledgement_bindings cannot be deleted. Acknowledgements are permanent records.';
            END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `kb_article_views`
--

DROP TABLE IF EXISTS `kb_article_views`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `kb_article_views` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `kb_article_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `kb_article_views_kb_article_id_created_at_index` (`kb_article_id`,`created_at`),
  KEY `kb_article_views_created_at_index` (`created_at`),
  CONSTRAINT `kb_article_views_kb_article_id_foreign` FOREIGN KEY (`kb_article_id`) REFERENCES `kb_articles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `kb_articles`
--

DROP TABLE IF EXISTS `kb_articles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `kb_articles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `kb_category_id` bigint(20) unsigned NOT NULL,
  `author_id` bigint(20) unsigned NOT NULL,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `summary` text DEFAULT NULL,
  `last_updated` date DEFAULT NULL,
  `content` text NOT NULL,
  `featured_image` varchar(255) DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'draft',
  `published_at` timestamp NULL DEFAULT NULL,
  `seo_meta` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`seo_meta`)),
  `views` int(11) NOT NULL DEFAULT 0,
  `helpful_yes` int(11) NOT NULL DEFAULT 0,
  `helpful_no` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `kb_articles_slug_unique` (`slug`),
  KEY `kb_articles_kb_category_id_foreign` (`kb_category_id`),
  KEY `kb_articles_author_id_foreign` (`author_id`),
  FULLTEXT KEY `ft_search` (`title`,`content`,`summary`),
  CONSTRAINT `kb_articles_author_id_foreign` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`),
  CONSTRAINT `kb_articles_kb_category_id_foreign` FOREIGN KEY (`kb_category_id`) REFERENCES `kb_categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `kb_categories`
--

DROP TABLE IF EXISTS `kb_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `kb_categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `icon` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `parent_id` bigint(20) unsigned DEFAULT NULL,
  `display_order` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `kb_categories_slug_unique` (`slug`),
  KEY `kb_categories_parent_id_foreign` (`parent_id`),
  CONSTRAINT `kb_categories_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `kb_categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `kyc_documents`
--

DROP TABLE IF EXISTS `kyc_documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `kyc_documents` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_kyc_id` bigint(20) unsigned NOT NULL,
  `doc_type` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `mime_type` varchar(255) NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `processing_status` varchar(255) DEFAULT NULL,
  `verified_by` bigint(20) unsigned DEFAULT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  `verification_notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `kyc_documents_user_kyc_id_foreign` (`user_kyc_id`),
  KEY `kyc_documents_verified_by_foreign` (`verified_by`),
  CONSTRAINT `kyc_documents_user_kyc_id_foreign` FOREIGN KEY (`user_kyc_id`) REFERENCES `user_kyc` (`id`) ON DELETE CASCADE,
  CONSTRAINT `kyc_documents_verified_by_foreign` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `kyc_rejection_templates`
--

DROP TABLE IF EXISTS `kyc_rejection_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `kyc_rejection_templates` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `reason` text NOT NULL,
  `category` varchar(255) NOT NULL,
  `message` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `kyc_verification_notes`
--

DROP TABLE IF EXISTS `kyc_verification_notes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `kyc_verification_notes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_kyc_id` bigint(20) unsigned NOT NULL,
  `admin_id` bigint(20) unsigned NOT NULL,
  `note` text NOT NULL COMMENT 'Admin note or comment about the KYC submission',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_verification_notes_kyc_id` (`user_kyc_id`),
  KEY `idx_verification_notes_admin_id` (`admin_id`),
  KEY `idx_verification_notes_created_at` (`created_at`),
  CONSTRAINT `kyc_verification_notes_admin_id_foreign` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `kyc_verification_notes_user_kyc_id_foreign` FOREIGN KEY (`user_kyc_id`) REFERENCES `user_kyc` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `ledger_accounts`
--

DROP TABLE IF EXISTS `ledger_accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ledger_accounts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL COMMENT 'Unique account code (e.g., BANK, INVENTORY)',
  `name` varchar(255) NOT NULL COMMENT 'Human-readable account name',
  `type` enum('ASSET','LIABILITY','EQUITY','INCOME','EXPENSE') NOT NULL COMMENT 'Account type per standard accounting',
  `is_system` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'System accounts cannot be deleted',
  `description` text DEFAULT NULL COMMENT 'Detailed description of account purpose',
  `normal_balance` varchar(10) NOT NULL DEFAULT 'DEBIT' COMMENT 'Normal balance direction: DEBIT or CREDIT',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ledger_accounts_code_unique` (`code`),
  KEY `ledger_accounts_type_index` (`type`),
  KEY `ledger_accounts_is_system_index` (`is_system`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `ledger_entries`
--

DROP TABLE IF EXISTS `ledger_entries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ledger_entries` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `reference_type` varchar(100) NOT NULL COMMENT 'Type of business event (e.g., bulk_purchase, user_deposit)',
  `reference_id` bigint(20) unsigned NOT NULL COMMENT 'ID of the related business entity',
  `description` text DEFAULT NULL COMMENT 'Human-readable description of the entry',
  `entry_date` date NOT NULL COMMENT 'Date of the business event',
  `created_by` bigint(20) unsigned DEFAULT NULL COMMENT 'Admin/user who created this entry',
  `is_reversal` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'True if this entry reverses another entry',
  `reverses_entry_id` bigint(20) unsigned DEFAULT NULL COMMENT 'ID of the entry being reversed (if is_reversal=true)',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `ledger_entries_reference_type_reference_id_index` (`reference_type`,`reference_id`),
  KEY `ledger_entries_entry_date_index` (`entry_date`),
  KEY `ledger_entries_is_reversal_index` (`is_reversal`),
  KEY `ledger_entries_reverses_entry_id_foreign` (`reverses_entry_id`),
  CONSTRAINT `ledger_entries_reverses_entry_id_foreign` FOREIGN KEY (`reverses_entry_id`) REFERENCES `ledger_entries` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `ledger_lines`
--

DROP TABLE IF EXISTS `ledger_lines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ledger_lines` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `ledger_entry_id` bigint(20) unsigned NOT NULL COMMENT 'Parent journal entry',
  `ledger_account_id` bigint(20) unsigned NOT NULL COMMENT 'Account being debited or credited',
  `direction` enum('DEBIT','CREDIT') NOT NULL COMMENT 'Whether this line debits or credits the account',
  `amount_paise` bigint(20) unsigned NOT NULL DEFAULT 0 COMMENT 'Amount in paise (integer) - 1 rupee = 100 paise',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `ledger_lines_ledger_entry_id_index` (`ledger_entry_id`),
  KEY `ledger_lines_ledger_account_id_index` (`ledger_account_id`),
  KEY `ledger_lines_direction_index` (`direction`),
  CONSTRAINT `ledger_lines_ledger_account_id_foreign` FOREIGN KEY (`ledger_account_id`) REFERENCES `ledger_accounts` (`id`),
  CONSTRAINT `ledger_lines_ledger_entry_id_foreign` FOREIGN KEY (`ledger_entry_id`) REFERENCES `ledger_entries` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `legal_agreement_audit_trail`
--

DROP TABLE IF EXISTS `legal_agreement_audit_trail`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `legal_agreement_audit_trail` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `legal_agreement_id` bigint(20) unsigned NOT NULL,
  `event_type` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `changes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`changes`)),
  `version` varchar(255) DEFAULT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `user_name` varchar(255) DEFAULT NULL,
  `ip_address` varchar(255) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `is_archived` tinyint(1) NOT NULL DEFAULT 0,
  `archived_at` timestamp NULL DEFAULT NULL,
  `retention_period` varchar(255) NOT NULL DEFAULT 'permanent' COMMENT 'Legal record retention',
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `legal_agreement_audit_trail_legal_agreement_id_index` (`legal_agreement_id`),
  KEY `legal_agreement_audit_trail_event_type_index` (`event_type`),
  KEY `legal_agreement_audit_trail_user_id_index` (`user_id`),
  KEY `legal_agreement_audit_trail_created_at_index` (`created_at`),
  KEY `legal_agreement_audit_trail_is_archived_index` (`is_archived`),
  CONSTRAINT `legal_agreement_audit_trail_legal_agreement_id_foreign` FOREIGN KEY (`legal_agreement_id`) REFERENCES `legal_agreements` (`id`) ON DELETE CASCADE,
  CONSTRAINT `legal_agreement_audit_trail_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `legal_agreement_versions`
--

DROP TABLE IF EXISTS `legal_agreement_versions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `legal_agreement_versions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `legal_agreement_id` bigint(20) unsigned NOT NULL,
  `content_hash` varchar(64) DEFAULT NULL,
  `version` varchar(255) NOT NULL,
  `content` longtext NOT NULL,
  `change_summary` text DEFAULT NULL,
  `status` enum('draft','review','active','archived','superseded') NOT NULL DEFAULT 'draft',
  `effective_date` date DEFAULT NULL,
  `acceptance_count` int(11) NOT NULL DEFAULT 0,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `version_number` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `legal_agreement_versions_created_by_foreign` (`created_by`),
  KEY `legal_agreement_versions_legal_agreement_id_index` (`legal_agreement_id`),
  KEY `legal_agreement_versions_legal_agreement_id_version_index` (`legal_agreement_id`,`version`),
  CONSTRAINT `legal_agreement_versions_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `legal_agreement_versions_legal_agreement_id_foreign` FOREIGN KEY (`legal_agreement_id`) REFERENCES `legal_agreements` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `legal_agreements`
--

DROP TABLE IF EXISTS `legal_agreements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `legal_agreements` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(255) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `content` longtext NOT NULL,
  `version` varchar(255) NOT NULL,
  `status` enum('draft','review','active','archived','superseded') NOT NULL DEFAULT 'draft',
  `effective_date` date DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `require_signature` tinyint(1) NOT NULL DEFAULT 0,
  `is_template` tinyint(1) NOT NULL DEFAULT 0,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `legal_agreements_created_by_foreign` (`created_by`),
  KEY `legal_agreements_updated_by_foreign` (`updated_by`),
  KEY `legal_agreements_type_index` (`type`),
  KEY `legal_agreements_status_index` (`status`),
  KEY `legal_agreements_type_status_index` (`type`,`status`),
  CONSTRAINT `legal_agreements_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `legal_agreements_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lifecycle_state_transitions`
--

DROP TABLE IF EXISTS `lifecycle_state_transitions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lifecycle_state_transitions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `from_state` varchar(50) NOT NULL COMMENT 'Source state code',
  `to_state` varchar(50) NOT NULL COMMENT 'Target state code',
  `trigger` varchar(100) NOT NULL COMMENT 'What causes this transition',
  `conditions` text DEFAULT NULL COMMENT 'Additional conditions for transition',
  `requires_admin_approval` tinyint(1) NOT NULL DEFAULT 0,
  `is_reversible` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `lifecycle_state_transitions_from_state_to_state_index` (`from_state`,`to_state`),
  KEY `lifecycle_state_transitions_trigger_index` (`trigger`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lifecycle_states`
--

DROP TABLE IF EXISTS `lifecycle_states`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lifecycle_states` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL COMMENT 'Programmatic state code',
  `label` varchar(255) NOT NULL COMMENT 'Human-readable label',
  `description` text DEFAULT NULL,
  `allows_buying` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Can investors purchase shares',
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Is this state currently usable',
  `display_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `lifecycle_states_code_unique` (`code`),
  KEY `lifecycle_states_code_index` (`code`),
  KEY `lifecycle_states_is_active_display_order_index` (`is_active`,`display_order`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `live_chat_messages`
--

DROP TABLE IF EXISTS `live_chat_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `live_chat_messages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `session_id` bigint(20) unsigned NOT NULL,
  `sender_id` bigint(20) unsigned NOT NULL,
  `sender_type` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` varchar(255) NOT NULL DEFAULT 'text',
  `attachments` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`attachments`)),
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `live_chat_messages_sender_id_foreign` (`sender_id`),
  KEY `live_chat_messages_session_id_created_at_index` (`session_id`,`created_at`),
  CONSTRAINT `live_chat_messages_sender_id_foreign` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `live_chat_messages_session_id_foreign` FOREIGN KEY (`session_id`) REFERENCES `live_chat_sessions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `live_chat_sessions`
--

DROP TABLE IF EXISTS `live_chat_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `live_chat_sessions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `session_code` varchar(255) NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `agent_id` bigint(20) unsigned DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'waiting',
  `subject` varchar(255) DEFAULT NULL,
  `initial_message` text DEFAULT NULL,
  `unread_user_count` int(11) NOT NULL DEFAULT 0,
  `unread_agent_count` int(11) NOT NULL DEFAULT 0,
  `user_rating` tinyint(3) unsigned DEFAULT NULL,
  `user_feedback` text DEFAULT NULL,
  `started_at` timestamp NULL DEFAULT NULL,
  `closed_at` timestamp NULL DEFAULT NULL,
  `closed_by_type` varchar(255) DEFAULT NULL,
  `closed_by_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `live_chat_sessions_session_code_unique` (`session_code`),
  KEY `live_chat_sessions_user_id_foreign` (`user_id`),
  KEY `live_chat_sessions_closed_by_id_foreign` (`closed_by_id`),
  KEY `live_chat_sessions_status_created_at_index` (`status`,`created_at`),
  KEY `live_chat_sessions_agent_id_index` (`agent_id`),
  CONSTRAINT `live_chat_sessions_agent_id_foreign` FOREIGN KEY (`agent_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `live_chat_sessions_closed_by_id_foreign` FOREIGN KEY (`closed_by_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `live_chat_sessions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lucky_draw_entries`
--

DROP TABLE IF EXISTS `lucky_draw_entries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lucky_draw_entries` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `lucky_draw_id` bigint(20) unsigned NOT NULL,
  `payment_id` bigint(20) unsigned NOT NULL,
  `base_entries` int(11) NOT NULL DEFAULT 0,
  `bonus_entries` int(11) NOT NULL DEFAULT 0,
  `is_winner` tinyint(1) NOT NULL DEFAULT 0,
  `prize_rank` int(11) DEFAULT NULL,
  `prize_amount` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `lucky_draw_entries_user_id_lucky_draw_id_unique` (`user_id`,`lucky_draw_id`),
  KEY `lucky_draw_entries_lucky_draw_id_foreign` (`lucky_draw_id`),
  KEY `lucky_draw_entries_payment_id_foreign` (`payment_id`),
  CONSTRAINT `lucky_draw_entries_lucky_draw_id_foreign` FOREIGN KEY (`lucky_draw_id`) REFERENCES `lucky_draws` (`id`) ON DELETE CASCADE,
  CONSTRAINT `lucky_draw_entries_payment_id_foreign` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `lucky_draw_entries_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lucky_draws`
--

DROP TABLE IF EXISTS `lucky_draws`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lucky_draws` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `draw_date` date NOT NULL,
  `prize_structure` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`prize_structure`)),
  `frequency` varchar(255) NOT NULL DEFAULT 'monthly',
  `entry_rules` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`entry_rules`)),
  `status` varchar(255) NOT NULL DEFAULT 'open',
  `result_visibility` varchar(255) NOT NULL DEFAULT 'public',
  `certificate_template` varchar(255) DEFAULT NULL,
  `draw_video_url` varchar(255) DEFAULT NULL,
  `draw_metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`draw_metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `executed_by` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `lucky_draws_created_by_foreign` (`created_by`),
  KEY `lucky_draws_executed_by_foreign` (`executed_by`),
  CONSTRAINT `lucky_draws_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `lucky_draws_executed_by_foreign` FOREIGN KEY (`executed_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `menu_items`
--

DROP TABLE IF EXISTS `menu_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `menu_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `menu_id` bigint(20) unsigned NOT NULL,
  `label` varchar(255) NOT NULL,
  `url` varchar(255) NOT NULL,
  `parent_id` bigint(20) unsigned DEFAULT NULL,
  `display_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `menu_items_menu_id_foreign` (`menu_id`),
  KEY `menu_items_parent_id_foreign` (`parent_id`),
  CONSTRAINT `menu_items_menu_id_foreign` FOREIGN KEY (`menu_id`) REFERENCES `menus` (`id`) ON DELETE CASCADE,
  CONSTRAINT `menu_items_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `menu_items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `menus`
--

DROP TABLE IF EXISTS `menus`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `menus` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `menus_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=235 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `model_has_permissions`
--

DROP TABLE IF EXISTS `model_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `model_has_permissions` (
  `permission_id` bigint(20) unsigned NOT NULL,
  `model_type` varchar(255) NOT NULL,
  `model_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`model_id`,`model_type`),
  KEY `model_has_permissions_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `model_has_roles`
--

DROP TABLE IF EXISTS `model_has_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `model_has_roles` (
  `role_id` bigint(20) unsigned NOT NULL,
  `model_type` varchar(255) NOT NULL,
  `model_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`role_id`,`model_id`,`model_type`),
  KEY `model_has_roles_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notifications` (
  `id` char(36) NOT NULL,
  `type` varchar(255) NOT NULL,
  `notifiable_type` varchar(255) NOT NULL,
  `notifiable_id` bigint(20) unsigned NOT NULL,
  `data` text NOT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `notifications_notifiable_type_notifiable_id_index` (`notifiable_type`,`notifiable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `operational_dashboards`
--

DROP TABLE IF EXISTS `operational_dashboards`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `operational_dashboards` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `dashboard_name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `widgets` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`widgets`)),
  `allowed_roles` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`allowed_roles`)),
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `display_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `operational_dashboards_dashboard_name_unique` (`dashboard_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `otps`
--

DROP TABLE IF EXISTS `otps`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `otps` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `type` varchar(255) NOT NULL,
  `otp_code` varchar(255) NOT NULL,
  `attempts` int(11) NOT NULL DEFAULT 0,
  `blocked` tinyint(1) NOT NULL DEFAULT 0,
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_sent_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `otps_user_id_foreign` (`user_id`),
  CONSTRAINT `otps_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `outbound_message_queue`
--

DROP TABLE IF EXISTS `outbound_message_queue`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `outbound_message_queue` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `channel_id` bigint(20) unsigned NOT NULL,
  `recipient_identifier` varchar(255) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `message_content` text NOT NULL,
  `attachments` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`attachments`)),
  `support_ticket_id` bigint(20) unsigned DEFAULT NULL,
  `scheduled_at` timestamp NULL DEFAULT NULL,
  `priority` enum('low','medium','high') NOT NULL DEFAULT 'medium',
  `status` enum('pending','sending','sent','failed','cancelled') NOT NULL DEFAULT 'pending',
  `sent_at` timestamp NULL DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  `retry_count` int(11) NOT NULL DEFAULT 0,
  `max_retries` int(11) NOT NULL DEFAULT 3,
  `external_message_id` varchar(255) DEFAULT NULL,
  `delivered` tinyint(1) NOT NULL DEFAULT 0,
  `delivered_at` timestamp NULL DEFAULT NULL,
  `read` tinyint(1) NOT NULL DEFAULT 0,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `subject` varchar(255) DEFAULT NULL COMMENT 'Optional subject (email / notification)',
  `message_metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Provider-specific metadata payload' CHECK (json_valid(`message_metadata`)),
  PRIMARY KEY (`id`),
  KEY `outbound_message_queue_user_id_foreign` (`user_id`),
  KEY `outbound_message_queue_status_scheduled_at_index` (`status`,`scheduled_at`),
  KEY `outbound_message_queue_channel_id_status_index` (`channel_id`,`status`),
  KEY `outbound_message_queue_support_ticket_id_index` (`support_ticket_id`),
  CONSTRAINT `outbound_message_queue_channel_id_foreign` FOREIGN KEY (`channel_id`) REFERENCES `communication_channels` (`id`) ON DELETE CASCADE,
  CONSTRAINT `outbound_message_queue_support_ticket_id_foreign` FOREIGN KEY (`support_ticket_id`) REFERENCES `support_tickets` (`id`) ON DELETE SET NULL,
  CONSTRAINT `outbound_message_queue_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `page_blocks`
--

DROP TABLE IF EXISTS `page_blocks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `page_blocks` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `page_id` bigint(20) unsigned NOT NULL,
  `type` varchar(255) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `config` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`config`)),
  `display_order` int(11) NOT NULL DEFAULT 0,
  `container_width` varchar(255) NOT NULL DEFAULT 'full',
  `background_type` varchar(255) NOT NULL DEFAULT 'none',
  `background_config` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`background_config`)),
  `spacing` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`spacing`)),
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `visibility` varchar(255) NOT NULL DEFAULT 'always',
  `variant` varchar(255) DEFAULT NULL,
  `views_count` int(11) NOT NULL DEFAULT 0,
  `clicks_count` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `page_blocks_page_id_display_order_index` (`page_id`,`display_order`),
  KEY `page_blocks_type_index` (`type`),
  KEY `page_blocks_is_active_index` (`is_active`),
  CONSTRAINT `page_blocks_page_id_foreign` FOREIGN KEY (`page_id`) REFERENCES `pages` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `page_versions`
--

DROP TABLE IF EXISTS `page_versions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `page_versions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `page_id` bigint(20) unsigned NOT NULL,
  `author_id` bigint(20) unsigned DEFAULT NULL,
  `version` int(11) NOT NULL DEFAULT 1,
  `title` varchar(255) NOT NULL,
  `content` longtext NOT NULL,
  `change_summary` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `version_number` int(11) DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `page_versions_page_id_foreign` (`page_id`),
  KEY `page_versions_author_id_foreign` (`author_id`),
  CONSTRAINT `page_versions_author_id_foreign` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `page_versions_page_id_foreign` FOREIGN KEY (`page_id`) REFERENCES `pages` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `pages`
--

DROP TABLE IF EXISTS `pages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `content` longtext DEFAULT NULL,
  `seo_meta` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`seo_meta`)),
  `status` varchar(255) NOT NULL DEFAULT 'draft',
  `current_version` int(11) NOT NULL DEFAULT 1,
  `require_user_acceptance` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pages_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `password_histories`
--

DROP TABLE IF EXISTS `password_histories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `password_histories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `password_histories_user_id_created_at_index` (`user_id`,`created_at`),
  CONSTRAINT `password_histories_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `payments`
--

DROP TABLE IF EXISTS `payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `subscription_id` bigint(20) unsigned NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `amount_paise` bigint(20) DEFAULT NULL,
  `currency` varchar(3) NOT NULL DEFAULT 'INR',
  `expected_currency` varchar(3) NOT NULL DEFAULT 'INR',
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `payment_type` varchar(255) NOT NULL DEFAULT 'sip_installment',
  `gateway` varchar(255) DEFAULT NULL,
  `gateway_order_id` varchar(255) DEFAULT NULL,
  `gateway_payment_id` varchar(255) DEFAULT NULL,
  `gateway_signature` text DEFAULT NULL,
  `payment_metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`payment_metadata`)),
  `method` varchar(255) DEFAULT NULL,
  `payment_method` varchar(255) DEFAULT NULL,
  `payment_proof_path` varchar(255) DEFAULT NULL,
  `refunds_payment_id` bigint(20) unsigned DEFAULT NULL,
  `paid_at` timestamp NULL DEFAULT NULL,
  `settled_at` timestamp NULL DEFAULT NULL,
  `settlement_id` varchar(255) DEFAULT NULL,
  `settlement_status` varchar(255) NOT NULL DEFAULT 'pending',
  `refunded_at` timestamp NULL DEFAULT NULL,
  `chargeback_initiated_at` timestamp NULL DEFAULT NULL,
  `chargeback_confirmed_at` timestamp NULL DEFAULT NULL,
  `chargeback_gateway_id` varchar(255) DEFAULT NULL,
  `chargeback_reason` text DEFAULT NULL,
  `chargeback_amount_paise` bigint(20) NOT NULL DEFAULT 0,
  `refund_amount_paise` bigint(20) NOT NULL DEFAULT 0,
  `refund_gateway_id` varchar(255) DEFAULT NULL,
  `is_on_time` tinyint(1) NOT NULL DEFAULT 0,
  `is_flagged` tinyint(1) NOT NULL DEFAULT 0,
  `flag_reason` text DEFAULT NULL,
  `retry_count` int(11) NOT NULL DEFAULT 0,
  `failure_reason` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `refunded_by` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `payments_gateway_payment_id_unique` (`gateway_payment_id`),
  UNIQUE KEY `payments_chargeback_gateway_id_unique` (`chargeback_gateway_id`),
  UNIQUE KEY `payments_refund_gateway_id_unique` (`refund_gateway_id`),
  KEY `payments_user_id_foreign` (`user_id`),
  KEY `payments_subscription_id_foreign` (`subscription_id`),
  KEY `payments_refunds_payment_id_foreign` (`refunds_payment_id`),
  KEY `payments_gateway_order_id_index` (`gateway_order_id`),
  KEY `payments_gateway_payment_id_index` (`gateway_payment_id`),
  KEY `payments_paid_at_index` (`paid_at`),
  KEY `payments_status_paid_at_index` (`status`,`paid_at`),
  KEY `payments_refunded_by_foreign` (`refunded_by`),
  KEY `payments_amount_paise_index` (`amount_paise`),
  KEY `payments_settlement_id_index` (`settlement_id`),
  CONSTRAINT `payments_refunded_by_foreign` FOREIGN KEY (`refunded_by`) REFERENCES `users` (`id`),
  CONSTRAINT `payments_refunds_payment_id_foreign` FOREIGN KEY (`refunds_payment_id`) REFERENCES `payments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `payments_subscription_id_foreign` FOREIGN KEY (`subscription_id`) REFERENCES `subscriptions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `payments_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `payment_amount_must_be_positive` CHECK (`amount` >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `performance_metrics`
--

DROP TABLE IF EXISTS `performance_metrics`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `performance_metrics` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `metric_type` varchar(255) NOT NULL,
  `endpoint` varchar(255) DEFAULT NULL,
  `value` double NOT NULL,
  `unit` varchar(255) NOT NULL DEFAULT 'ms',
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `recorded_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `performance_metrics_metric_type_recorded_at_index` (`metric_type`,`recorded_at`),
  KEY `performance_metrics_endpoint_index` (`endpoint`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `permissions`
--

DROP TABLE IF EXISTS `permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `permissions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `guard_name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permissions_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `personal_access_tokens`
--

DROP TABLE IF EXISTS `personal_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) NOT NULL,
  `tokenable_id` bigint(20) unsigned NOT NULL,
  `name` text NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`),
  KEY `personal_access_tokens_expires_at_index` (`expires_at`),
  KEY `pat_tokenable_index` (`tokenable_type`,`tokenable_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `pillar_vitality_snapshots`
--

DROP TABLE IF EXISTS `pillar_vitality_snapshots`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pillar_vitality_snapshots` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `pillar` enum('governance','financial','legal','operational') NOT NULL,
  `vitality_state` enum('healthy','needs_attention','at_risk') NOT NULL,
  `current_count` int(10) unsigned NOT NULL DEFAULT 0,
  `aging_count` int(10) unsigned NOT NULL DEFAULT 0,
  `stale_count` int(10) unsigned NOT NULL DEFAULT 0,
  `unstable_count` int(10) unsigned NOT NULL DEFAULT 0,
  `total_count` int(10) unsigned NOT NULL DEFAULT 0,
  `vitality_drivers` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Artifacts causing vitality degradation with explanations' CHECK (json_valid(`vitality_drivers`)),
  `computed_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_pillar_vitality_unique` (`company_id`,`pillar`,`computed_at`),
  KEY `idx_company_vitality_history` (`company_id`,`computed_at`),
  CONSTRAINT `pillar_vitality_snapshots_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `plan_configs`
--

DROP TABLE IF EXISTS `plan_configs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `plan_configs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `plan_id` bigint(20) unsigned NOT NULL,
  `config_key` varchar(255) NOT NULL,
  `value` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`value`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `plan_configs_plan_id_config_key_unique` (`plan_id`,`config_key`),
  CONSTRAINT `plan_configs_plan_id_foreign` FOREIGN KEY (`plan_id`) REFERENCES `plans` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `plan_features`
--

DROP TABLE IF EXISTS `plan_features`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `plan_features` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `plan_id` bigint(20) unsigned NOT NULL,
  `feature_text` varchar(255) NOT NULL,
  `icon` varchar(255) DEFAULT NULL,
  `display_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `plan_features_plan_id_foreign` (`plan_id`),
  CONSTRAINT `plan_features_plan_id_foreign` FOREIGN KEY (`plan_id`) REFERENCES `plans` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `plan_products`
--

DROP TABLE IF EXISTS `plan_products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `plan_products` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `plan_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `discount_percentage` decimal(5,2) NOT NULL DEFAULT 0.00 COMMENT 'Extra discount for this plan',
  `min_investment_override` decimal(15,2) DEFAULT NULL COMMENT 'Override product min_investment',
  `max_investment_override` decimal(15,2) DEFAULT NULL COMMENT 'Override product max_investment',
  `is_featured` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Featured product for this plan',
  `priority` int(11) NOT NULL DEFAULT 0 COMMENT 'Display order for this plan',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `plan_products_plan_id_product_id_unique` (`plan_id`,`product_id`),
  KEY `plan_products_plan_id_is_featured_index` (`plan_id`,`is_featured`),
  KEY `plan_products_product_id_plan_id_index` (`product_id`,`plan_id`),
  CONSTRAINT `plan_products_plan_id_foreign` FOREIGN KEY (`plan_id`) REFERENCES `plans` (`id`) ON DELETE CASCADE,
  CONSTRAINT `plan_products_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `plan_regulatory_overrides`
--

DROP TABLE IF EXISTS `plan_regulatory_overrides`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `plan_regulatory_overrides` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `plan_id` bigint(20) unsigned NOT NULL,
  `override_scope` enum('progressive_config','milestone_config','consistency_config','welcome_bonus_config','referral_tiers','multiplier_cap','global_rate_adjust','full_config') NOT NULL,
  `active_scope_key` varchar(100) GENERATED ALWAYS AS (case when `revoked_at` is null then concat(`plan_id`,':',`override_scope`) else NULL end) VIRTUAL COMMENT 'V-CONTRACT-HARDENING-FINAL: Generated column for unique active override enforcement',
  `override_payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT 'JSON payload to merge/replace in bonus calculation' CHECK (json_valid(`override_payload`)),
  `reason` text NOT NULL COMMENT 'Business/regulatory reason for this override',
  `regulatory_reference` varchar(255) NOT NULL COMMENT 'Regulatory order/circular reference (e.g., SEBI/HO/2026/001)',
  `approved_by_admin_id` bigint(20) unsigned NOT NULL,
  `effective_from` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT 'When this override becomes active',
  `expires_at` timestamp NULL DEFAULT NULL COMMENT 'When this override expires (null = permanent until revoked)',
  `revoked_at` timestamp NULL DEFAULT NULL,
  `revoked_by_admin_id` bigint(20) unsigned DEFAULT NULL,
  `revocation_reason` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_active_override_per_plan_scope` (`active_scope_key`),
  KEY `plan_regulatory_overrides_approved_by_admin_id_foreign` (`approved_by_admin_id`),
  KEY `plan_regulatory_overrides_revoked_by_admin_id_foreign` (`revoked_by_admin_id`),
  KEY `idx_active_overrides` (`plan_id`,`effective_from`,`expires_at`),
  KEY `idx_scope_effective` (`override_scope`,`effective_from`),
  KEY `plan_regulatory_overrides_override_scope_index` (`override_scope`),
  CONSTRAINT `plan_regulatory_overrides_approved_by_admin_id_foreign` FOREIGN KEY (`approved_by_admin_id`) REFERENCES `users` (`id`),
  CONSTRAINT `plan_regulatory_overrides_plan_id_foreign` FOREIGN KEY (`plan_id`) REFERENCES `plans` (`id`) ON DELETE CASCADE,
  CONSTRAINT `plan_regulatory_overrides_revoked_by_admin_id_foreign` FOREIGN KEY (`revoked_by_admin_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `plans`
--

DROP TABLE IF EXISTS `plans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `plans` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `razorpay_plan_id` varchar(255) DEFAULT NULL,
  `monthly_amount` decimal(10,2) NOT NULL,
  `duration_months` int(11) NOT NULL DEFAULT 36,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `is_featured` tinyint(1) NOT NULL DEFAULT 0,
  `display_order` int(11) NOT NULL DEFAULT 0,
  `available_from` timestamp NULL DEFAULT NULL,
  `available_until` timestamp NULL DEFAULT NULL,
  `max_subscriptions_per_user` int(11) NOT NULL DEFAULT 1,
  `allow_pause` tinyint(1) NOT NULL DEFAULT 1,
  `max_pause_count` int(11) NOT NULL DEFAULT 3,
  `max_pause_duration_months` int(11) NOT NULL DEFAULT 3,
  `min_investment` decimal(15,2) DEFAULT NULL,
  `max_investment` decimal(15,2) DEFAULT NULL,
  `billing_cycle` varchar(255) DEFAULT NULL,
  `trial_period_days` int(11) NOT NULL DEFAULT 0,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `plans_slug_unique` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `platform_company_metrics`
--

DROP TABLE IF EXISTS `platform_company_metrics`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `platform_company_metrics` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `disclosure_completeness_score` decimal(5,2) NOT NULL COMMENT '0-100: % of disclosure fields completed',
  `total_fields` int(11) NOT NULL COMMENT 'Total number of disclosure fields',
  `completed_fields` int(11) NOT NULL COMMENT 'Number of completed fields',
  `missing_critical_fields` int(11) NOT NULL COMMENT 'Count of missing critical/required fields',
  `financial_health_band` enum('insufficient_data','concerning','moderate','healthy','strong') NOT NULL COMMENT 'Platform assessment band based on disclosed financials',
  `financial_health_factors` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Factors contributing to band (transparency)' CHECK (json_valid(`financial_health_factors`)),
  `governance_quality_band` enum('insufficient_data','basic','standard','strong','exemplary') NOT NULL COMMENT 'Platform assessment based on disclosed governance practices',
  `governance_quality_factors` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Factors contributing to band' CHECK (json_valid(`governance_quality_factors`)),
  `risk_intensity_band` enum('insufficient_data','low','moderate','high','very_high') NOT NULL COMMENT 'Platform assessment based on disclosed risk factors',
  `disclosed_risk_count` int(11) NOT NULL COMMENT 'Total number of disclosed risks',
  `critical_risk_count` int(11) NOT NULL COMMENT 'Number of high/critical severity risks',
  `valuation_context` enum('insufficient_data','below_peers','at_peers','above_peers','premium') DEFAULT NULL COMMENT 'Comparative context vs peer group, NOT a recommendation',
  `valuation_context_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Peer comparison data for transparency' CHECK (json_valid(`valuation_context_data`)),
  `last_disclosure_update` timestamp NULL DEFAULT NULL COMMENT 'When company last updated disclosures',
  `last_platform_review` timestamp NULL DEFAULT NULL COMMENT 'When platform last recalculated metrics',
  `is_under_admin_review` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Whether disclosures are currently under admin review',
  `calculation_version` varchar(50) NOT NULL COMMENT 'Version of calculation algorithm used',
  `calculation_metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Full calculation methodology for audit trail' CHECK (json_valid(`calculation_metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `platform_company_metrics_company_id_index` (`company_id`),
  KEY `platform_company_metrics_financial_health_band_index` (`financial_health_band`),
  KEY `platform_company_metrics_governance_quality_band_index` (`governance_quality_band`),
  KEY `platform_company_metrics_risk_intensity_band_index` (`risk_intensity_band`),
  KEY `pcm_disclosure_review_idx` (`last_disclosure_update`,`last_platform_review`),
  CONSTRAINT `platform_company_metrics_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `platform_context_approval_logs`
--

DROP TABLE IF EXISTS `platform_context_approval_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `platform_context_approval_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `approval_request_id` bigint(20) unsigned NOT NULL,
  `action` varchar(30) NOT NULL,
  `actor_user_id` bigint(20) unsigned NOT NULL,
  `actor_role` varchar(50) NOT NULL,
  `context` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`context`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `platform_context_approval_logs_approval_request_id_index` (`approval_request_id`),
  KEY `platform_context_approval_logs_actor_user_id_index` (`actor_user_id`),
  KEY `platform_context_approval_logs_action_index` (`action`),
  CONSTRAINT `platform_context_approval_logs_actor_user_id_foreign` FOREIGN KEY (`actor_user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `platform_context_approval_logs_approval_request_id_foreign` FOREIGN KEY (`approval_request_id`) REFERENCES `platform_context_approval_requests` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `platform_context_approval_requests`
--

DROP TABLE IF EXISTS `platform_context_approval_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `platform_context_approval_requests` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `action_type` varchar(50) NOT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'pending_approval',
  `maker_user_id` bigint(20) unsigned NOT NULL,
  `maker_role` varchar(50) NOT NULL,
  `initiated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `maker_reason` text NOT NULL,
  `maker_ip` varchar(45) DEFAULT NULL,
  `maker_user_agent` text DEFAULT NULL,
  `checker_user_id` bigint(20) unsigned DEFAULT NULL,
  `checker_role` varchar(50) DEFAULT NULL,
  `checker_decision` varchar(20) DEFAULT NULL,
  `checker_reason` text DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `checker_ip` varchar(45) DEFAULT NULL,
  `checker_user_agent` text DEFAULT NULL,
  `proposed_changes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`proposed_changes`)),
  `current_state` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`current_state`)),
  `supporting_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`supporting_data`)),
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_expired` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `platform_context_approval_requests_company_id_status_index` (`company_id`,`status`),
  KEY `pc_approval_company_action_status_idx` (`company_id`,`action_type`,`status`),
  KEY `platform_context_approval_requests_maker_user_id_index` (`maker_user_id`),
  KEY `platform_context_approval_requests_checker_user_id_index` (`checker_user_id`),
  KEY `platform_context_approval_requests_status_expires_at_index` (`status`,`expires_at`),
  CONSTRAINT `platform_context_approval_requests_checker_user_id_foreign` FOREIGN KEY (`checker_user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `platform_context_approval_requests_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `platform_context_approval_requests_maker_user_id_foreign` FOREIGN KEY (`maker_user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `platform_context_authority`
--

DROP TABLE IF EXISTS `platform_context_authority`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `platform_context_authority` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `context_type` varchar(100) NOT NULL COMMENT 'Type: metric, risk_flag, valuation_context, etc.',
  `owning_domain` varchar(255) NOT NULL DEFAULT 'platform' COMMENT 'Always "platform" - companies cannot own',
  `is_company_writable` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Can companies write this context? ALWAYS FALSE',
  `is_platform_managed` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Is this managed by platform? ALWAYS TRUE',
  `calculation_frequency` enum('on_approval','hourly','daily','weekly','on_demand') NOT NULL DEFAULT 'on_approval' COMMENT 'When should this context be recalculated',
  `effective_from` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'When this authority rule took effect',
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `platform_context_authority_context_type_unique` (`context_type`),
  KEY `platform_context_authority_context_type_index` (`context_type`),
  KEY `pca_write_managed_idx` (`is_company_writable`,`is_platform_managed`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `platform_context_snapshots`
--

DROP TABLE IF EXISTS `platform_context_snapshots`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `platform_context_snapshots` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `snapshot_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT 'When this snapshot was taken',
  `snapshot_trigger` varchar(100) NOT NULL COMMENT 'What triggered snapshot: company_update, tier_approval, admin_action, scheduled_refresh',
  `triggered_by_user_id` bigint(20) unsigned DEFAULT NULL COMMENT 'User ID if triggered by user action',
  `actor_type` varchar(20) NOT NULL DEFAULT 'system' COMMENT 'Actor type: system, admin, automated_job',
  `lifecycle_state` varchar(50) NOT NULL COMMENT 'Company lifecycle state at snapshot time',
  `buying_enabled` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Whether buying was enabled at snapshot time',
  `governance_state_version` int(11) NOT NULL COMMENT 'Governance state version from companies table',
  `is_suspended` tinyint(1) NOT NULL DEFAULT 0,
  `suspension_reason` varchar(500) DEFAULT NULL,
  `tier_1_approved` tinyint(1) NOT NULL DEFAULT 0,
  `tier_1_approved_at` timestamp NULL DEFAULT NULL,
  `tier_2_approved` tinyint(1) NOT NULL DEFAULT 0,
  `tier_2_approved_at` timestamp NULL DEFAULT NULL,
  `tier_3_approved` tinyint(1) NOT NULL DEFAULT 0,
  `tier_3_approved_at` timestamp NULL DEFAULT NULL,
  `is_frozen` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Disclosure freeze active',
  `freeze_reason` varchar(500) DEFAULT NULL,
  `is_under_investigation` tinyint(1) NOT NULL DEFAULT 0,
  `investigation_reason` varchar(500) DEFAULT NULL,
  `platform_risk_score` decimal(5,2) DEFAULT NULL COMMENT 'Platform-calculated risk score (0-100)',
  `risk_level` varchar(20) DEFAULT NULL COMMENT 'low, medium, high, critical',
  `compliance_score` decimal(5,2) DEFAULT NULL COMMENT 'Platform-calculated compliance score (0-100)',
  `risk_flags` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Array of active risk flags' CHECK (json_valid(`risk_flags`)),
  `has_material_changes` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Whether material changes exist since last investor snapshot',
  `material_changes_summary` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Summary of material changes' CHECK (json_valid(`material_changes_summary`)),
  `last_material_change_at` timestamp NULL DEFAULT NULL,
  `admin_notes` text DEFAULT NULL COMMENT 'Admin notes about company at snapshot time',
  `admin_judgments` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Explicit admin judgments vs automated platform analysis' CHECK (json_valid(`admin_judgments`)),
  `full_context_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT 'Complete platform context at snapshot time' CHECK (json_valid(`full_context_data`)),
  `is_locked` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Snapshot is immutable once locked',
  `locked_at` timestamp NULL DEFAULT NULL,
  `supersedes_snapshot_id` bigint(20) unsigned DEFAULT NULL COMMENT 'Previous snapshot ID that this one supersedes',
  `valid_from` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'Snapshot is valid from this time',
  `valid_until` timestamp NULL DEFAULT NULL COMMENT 'Snapshot is valid until this time (null = current)',
  `is_current` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Whether this is the current active snapshot',
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(500) DEFAULT NULL,
  `audit_metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Additional audit information' CHECK (json_valid(`audit_metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `platform_context_snapshots_triggered_by_user_id_foreign` (`triggered_by_user_id`),
  KEY `platform_context_snapshots_supersedes_snapshot_id_foreign` (`supersedes_snapshot_id`),
  KEY `platform_context_snapshots_company_id_is_current_index` (`company_id`,`is_current`),
  KEY `platform_context_snapshots_company_id_snapshot_at_index` (`company_id`,`snapshot_at`),
  KEY `platform_context_snapshots_valid_from_valid_until_index` (`valid_from`,`valid_until`),
  KEY `platform_context_snapshots_snapshot_trigger_index` (`snapshot_trigger`),
  KEY `platform_context_snapshots_company_id_index` (`company_id`),
  KEY `platform_context_snapshots_snapshot_at_index` (`snapshot_at`),
  KEY `platform_context_snapshots_valid_until_index` (`valid_until`),
  KEY `platform_context_snapshots_is_current_index` (`is_current`),
  CONSTRAINT `platform_context_snapshots_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `platform_context_snapshots_supersedes_snapshot_id_foreign` FOREIGN KEY (`supersedes_snapshot_id`) REFERENCES `platform_context_snapshots` (`id`) ON DELETE SET NULL,
  CONSTRAINT `platform_context_snapshots_triggered_by_user_id_foreign` FOREIGN KEY (`triggered_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER prevent_locked_snapshot_update
            BEFORE UPDATE ON platform_context_snapshots
            FOR EACH ROW
            BEGIN
                -- Allow updating is_current and valid_until (for superseding)
                -- Block all other updates if locked
                IF OLD.is_locked = 1 THEN
                    IF OLD.lifecycle_state != NEW.lifecycle_state
                       OR OLD.buying_enabled != NEW.buying_enabled
                       OR OLD.risk_level != NEW.risk_level
                       OR OLD.compliance_score != NEW.compliance_score
                       OR OLD.full_context_data != NEW.full_context_data
                    THEN
                        SIGNAL SQLSTATE '45000'
                        SET MESSAGE_TEXT = 'IMMUTABILITY VIOLATION: Locked platform_context_snapshots cannot have core fields updated.';
                    END IF;
                END IF;
            END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER prevent_snapshot_delete
            BEFORE DELETE ON platform_context_snapshots
            FOR EACH ROW
            BEGIN
                IF OLD.is_locked = 1 THEN
                    SIGNAL SQLSTATE '45000'
                    SET MESSAGE_TEXT = 'IMMUTABILITY VIOLATION: Locked platform_context_snapshots cannot be deleted.';
                END IF;
            END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `platform_context_versions`
--

DROP TABLE IF EXISTS `platform_context_versions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `platform_context_versions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `context_type` varchar(100) NOT NULL,
  `version_code` varchar(50) NOT NULL COMMENT 'e.g., v1.0.0, v2.1.3',
  `changelog` text DEFAULT NULL COMMENT 'What changed in this version',
  `calculation_logic` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Serialized calculation rules for reproducibility' CHECK (json_valid(`calculation_logic`)),
  `effective_from` timestamp NOT NULL DEFAULT current_timestamp(),
  `effective_until` timestamp NULL DEFAULT NULL COMMENT 'When this version was superseded',
  `is_current` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `platform_context_versions_context_type_version_code_index` (`context_type`,`version_code`),
  KEY `platform_context_versions_context_type_is_current_index` (`context_type`,`is_current`),
  KEY `platform_context_versions_effective_from_index` (`effective_from`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `platform_governance_log`
--

DROP TABLE IF EXISTS `platform_governance_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `platform_governance_log` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `action_type` varchar(100) NOT NULL COMMENT 'Type: lifecycle_transition, buying_toggle, suspension, reactivation, tier_approval',
  `from_state` varchar(50) DEFAULT NULL COMMENT 'Previous lifecycle state',
  `to_state` varchar(50) DEFAULT NULL COMMENT 'New lifecycle state',
  `buying_enabled_before` tinyint(1) DEFAULT NULL,
  `buying_enabled_after` tinyint(1) DEFAULT NULL,
  `decision_reason` text DEFAULT NULL COMMENT 'Why platform made this decision',
  `decision_criteria` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Criteria evaluated (tier completion, flags, etc.)' CHECK (json_valid(`decision_criteria`)),
  `decided_by` bigint(20) unsigned DEFAULT NULL,
  `approval_request_id` bigint(20) unsigned DEFAULT NULL,
  `is_automated` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Was this automated or manual admin action',
  `decided_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `is_immutable` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Governance decisions are permanent record',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `platform_governance_log_decided_by_foreign` (`decided_by`),
  KEY `platform_governance_log_company_id_action_type_decided_at_index` (`company_id`,`action_type`,`decided_at`),
  KEY `platform_governance_log_action_type_index` (`action_type`),
  KEY `platform_governance_log_decided_at_index` (`decided_at`),
  KEY `platform_governance_log_approval_request_id_index` (`approval_request_id`),
  CONSTRAINT `platform_governance_log_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `platform_governance_log_decided_by_foreign` FOREIGN KEY (`decided_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `platform_ledger_entries`
--

DROP TABLE IF EXISTS `platform_ledger_entries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `platform_ledger_entries` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `type` enum('debit','credit') NOT NULL,
  `amount_paise` bigint(20) NOT NULL,
  `balance_before_paise` bigint(20) NOT NULL,
  `balance_after_paise` bigint(20) NOT NULL,
  `currency` varchar(3) NOT NULL DEFAULT 'INR',
  `source_type` varchar(50) NOT NULL,
  `source_id` bigint(20) unsigned NOT NULL,
  `description` varchar(500) NOT NULL,
  `entry_pair_id` bigint(20) unsigned DEFAULT NULL,
  `actor_id` bigint(20) unsigned DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_platform_ledger_source` (`source_type`,`source_id`),
  KEY `idx_platform_ledger_type` (`type`),
  KEY `idx_platform_ledger_created` (`created_at`),
  KEY `idx_platform_ledger_pair` (`entry_pair_id`),
  CONSTRAINT `platform_ledger_entries_entry_pair_id_foreign` FOREIGN KEY (`entry_pair_id`) REFERENCES `platform_ledger_entries` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `platform_risk_flags`
--

DROP TABLE IF EXISTS `platform_risk_flags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `platform_risk_flags` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `flag_type` varchar(100) NOT NULL COMMENT 'Type of risk flag detected',
  `severity` enum('info','low','medium','high','critical') NOT NULL COMMENT 'Flag severity level',
  `category` enum('financial','governance','legal','disclosure_quality','market','operational') NOT NULL COMMENT 'Risk category',
  `description` text NOT NULL COMMENT 'Human-readable description of the flag',
  `detection_logic` text NOT NULL COMMENT 'How this flag was detected (transparency)',
  `supporting_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Data points that triggered this flag' CHECK (json_valid(`supporting_data`)),
  `context` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Additional context for investors' CHECK (json_valid(`context`)),
  `disclosure_id` bigint(20) unsigned DEFAULT NULL,
  `disclosure_field_path` varchar(255) DEFAULT NULL COMMENT 'Specific field that triggered flag',
  `status` enum('active','resolved','dismissed','superseded') NOT NULL DEFAULT 'active',
  `detected_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT 'When flag was first detected',
  `resolved_at` timestamp NULL DEFAULT NULL COMMENT 'When flag was resolved',
  `resolution_notes` text DEFAULT NULL COMMENT 'How flag was resolved',
  `is_visible_to_investors` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Whether flag should be shown to investors',
  `investor_message` text DEFAULT NULL COMMENT 'Investor-friendly explanation of flag',
  `detection_version` varchar(50) NOT NULL COMMENT 'Version of detection algorithm',
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Additional metadata for audit trail' CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `platform_risk_flags_disclosure_id_foreign` (`disclosure_id`),
  KEY `platform_risk_flags_company_id_index` (`company_id`),
  KEY `platform_risk_flags_company_id_status_index` (`company_id`,`status`),
  KEY `platform_risk_flags_company_id_category_severity_index` (`company_id`,`category`,`severity`),
  KEY `platform_risk_flags_flag_type_index` (`flag_type`),
  KEY `platform_risk_flags_detected_at_index` (`detected_at`),
  CONSTRAINT `platform_risk_flags_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `platform_risk_flags_disclosure_id_foreign` FOREIGN KEY (`disclosure_id`) REFERENCES `company_disclosures` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `platform_valuation_context`
--

DROP TABLE IF EXISTS `platform_valuation_context`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `platform_valuation_context` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `peer_group_name` varchar(255) NOT NULL COMMENT 'Name of peer group used for comparison',
  `peer_company_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT 'IDs of companies in peer group' CHECK (json_valid(`peer_company_ids`)),
  `peer_count` int(11) NOT NULL COMMENT 'Number of companies in peer group',
  `peer_selection_criteria` text NOT NULL COMMENT 'How peers were selected (transparency)',
  `company_valuation` decimal(15,2) DEFAULT NULL COMMENT 'Current valuation (if disclosed)',
  `peer_median_valuation` decimal(15,2) DEFAULT NULL COMMENT 'Median valuation of peer group',
  `peer_p25_valuation` decimal(15,2) DEFAULT NULL COMMENT '25th percentile peer valuation',
  `peer_p75_valuation` decimal(15,2) DEFAULT NULL COMMENT '75th percentile peer valuation',
  `company_revenue_multiple` decimal(8,2) DEFAULT NULL COMMENT 'Company valuation / revenue (if available)',
  `peer_median_revenue_multiple` decimal(8,2) DEFAULT NULL COMMENT 'Peer median revenue multiple',
  `company_revenue_growth_rate` decimal(8,2) DEFAULT NULL COMMENT 'Company YoY revenue growth %',
  `peer_median_revenue_growth` decimal(8,2) DEFAULT NULL COMMENT 'Peer median growth rate',
  `liquidity_outlook` enum('insufficient_data','limited_market','developing_market','active_market','liquid_market') DEFAULT NULL COMMENT 'Platform assessment of market liquidity, NOT a prediction',
  `liquidity_factors` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Factors affecting liquidity assessment' CHECK (json_valid(`liquidity_factors`)),
  `recent_transaction_count` int(11) NOT NULL DEFAULT 0 COMMENT 'Number of transactions in last 90 days',
  `recent_avg_transaction_size` decimal(15,2) DEFAULT NULL COMMENT 'Average transaction size',
  `bid_ask_spread_percentage` decimal(5,2) DEFAULT NULL COMMENT 'Current bid-ask spread %',
  `calculated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT 'When this context was calculated',
  `data_as_of` timestamp NULL DEFAULT NULL COMMENT 'Date of underlying data',
  `is_stale` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Whether data needs recalculation',
  `calculation_version` varchar(50) NOT NULL COMMENT 'Version of calculation methodology',
  `methodology_notes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Full methodology for transparency' CHECK (json_valid(`methodology_notes`)),
  `data_sources` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Sources of comparative data' CHECK (json_valid(`data_sources`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `platform_valuation_context_company_id_index` (`company_id`),
  KEY `platform_valuation_context_peer_group_name_index` (`peer_group_name`),
  KEY `platform_valuation_context_calculated_at_is_stale_index` (`calculated_at`,`is_stale`),
  CONSTRAINT `platform_valuation_context_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `product_audits`
--

DROP TABLE IF EXISTS `product_audits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_audits` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint(20) unsigned NOT NULL,
  `action` enum('created','updated','activated','deactivated','price_updated','compliance_updated','deleted','restored') NOT NULL,
  `changed_fields` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Array of field names that changed' CHECK (json_valid(`changed_fields`)),
  `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Previous values of changed fields' CHECK (json_valid(`old_values`)),
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'New values of changed fields' CHECK (json_valid(`new_values`)),
  `change_description` text DEFAULT NULL COMMENT 'Human-readable description of changes',
  `is_critical` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Marks critical changes (price, status, SEBI approval, etc.)',
  `critical_fields` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Array of critical fields that changed' CHECK (json_valid(`critical_fields`)),
  `performed_by` bigint(20) unsigned DEFAULT NULL,
  `performed_by_type` varchar(255) NOT NULL DEFAULT 'user' COMMENT 'user, system, admin, api',
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `request_id` varchar(255) DEFAULT NULL COMMENT 'UUID for request tracing',
  `request_url` text DEFAULT NULL,
  `request_method` varchar(10) DEFAULT NULL,
  `compliance_notes` text DEFAULT NULL COMMENT 'Notes for compliance tracking',
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Additional contextual data' CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `product_audits_action_index` (`action`),
  KEY `product_audits_product_id_index` (`product_id`),
  KEY `product_audits_is_critical_index` (`is_critical`),
  KEY `product_audits_performed_by_index` (`performed_by`),
  KEY `product_audits_created_at_index` (`created_at`),
  KEY `product_audits_product_id_created_at_index` (`product_id`,`created_at`),
  KEY `product_audits_product_id_action_index` (`product_id`,`action`),
  KEY `product_audits_is_critical_created_at_index` (`is_critical`,`created_at`),
  CONSTRAINT `product_audits_performed_by_foreign` FOREIGN KEY (`performed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `product_audits_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `product_founders`
--

DROP TABLE IF EXISTS `product_founders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_founders` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `title` varchar(255) NOT NULL,
  `photo_url` varchar(255) DEFAULT NULL,
  `linkedin_url` varchar(255) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `display_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `product_founders_product_id_foreign` (`product_id`),
  CONSTRAINT `product_founders_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `product_funding_rounds`
--

DROP TABLE IF EXISTS `product_funding_rounds`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_funding_rounds` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint(20) unsigned NOT NULL,
  `round_name` varchar(255) NOT NULL,
  `date` date NOT NULL,
  `amount` decimal(14,2) NOT NULL,
  `valuation` decimal(14,2) NOT NULL,
  `investors` text NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `product_funding_rounds_product_id_foreign` (`product_id`),
  CONSTRAINT `product_funding_rounds_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `product_highlights`
--

DROP TABLE IF EXISTS `product_highlights`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_highlights` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint(20) unsigned NOT NULL,
  `content` varchar(255) NOT NULL,
  `display_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `product_highlights_product_id_foreign` (`product_id`),
  CONSTRAINT `product_highlights_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `product_key_metrics`
--

DROP TABLE IF EXISTS `product_key_metrics`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_key_metrics` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint(20) unsigned NOT NULL,
  `metric_name` varchar(255) NOT NULL,
  `value` varchar(255) NOT NULL,
  `unit` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `product_key_metrics_product_id_foreign` (`product_id`),
  CONSTRAINT `product_key_metrics_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `product_price_histories`
--

DROP TABLE IF EXISTS `product_price_histories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_price_histories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint(20) unsigned NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `recorded_at` date NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `product_price_histories_product_id_recorded_at_unique` (`product_id`,`recorded_at`),
  CONSTRAINT `product_price_histories_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `product_risk_disclosures`
--

DROP TABLE IF EXISTS `product_risk_disclosures`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_risk_disclosures` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint(20) unsigned NOT NULL,
  `risk_category` varchar(255) NOT NULL,
  `severity` varchar(255) NOT NULL,
  `risk_title` varchar(255) NOT NULL,
  `risk_description` text NOT NULL,
  `display_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `product_risk_disclosures_product_id_foreign` (`product_id`),
  CONSTRAINT `product_risk_disclosures_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `products` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `sector` varchar(255) DEFAULT NULL,
  `face_value_per_unit` decimal(10,2) NOT NULL,
  `current_market_price` decimal(10,2) DEFAULT NULL,
  `last_price_update` timestamp NULL DEFAULT NULL,
  `auto_update_price` tinyint(1) NOT NULL DEFAULT 0,
  `price_api_endpoint` varchar(255) DEFAULT NULL,
  `min_investment` decimal(10,2) NOT NULL,
  `expected_ipo_date` date DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'active',
  `eligibility_mode` enum('all_plans','specific_plans','premium_only') NOT NULL DEFAULT 'all_plans' COMMENT 'Controls which plans can access this product',
  `sebi_approval_number` varchar(255) DEFAULT NULL,
  `sebi_approval_date` date DEFAULT NULL,
  `compliance_notes` text DEFAULT NULL,
  `regulatory_warnings` text DEFAULT NULL,
  `is_featured` tinyint(1) NOT NULL DEFAULT 0,
  `display_order` int(11) NOT NULL DEFAULT 0,
  `description` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`description`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `products_slug_unique` (`slug`),
  KEY `products_slug_index` (`slug`),
  KEY `products_company_id_index` (`company_id`),
  CONSTRAINT `products_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `profit_shares`
--

DROP TABLE IF EXISTS `profit_shares`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `profit_shares` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `period_name` varchar(255) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `total_pool` decimal(14,2) NOT NULL,
  `net_profit` decimal(14,2) NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `report_visibility` varchar(255) NOT NULL DEFAULT 'private',
  `report_url` text DEFAULT NULL,
  `calculation_metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`calculation_metadata`)),
  `admin_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `published_by` bigint(20) unsigned DEFAULT NULL,
  `published_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `profit_shares_period_name_unique` (`period_name`),
  KEY `profit_shares_admin_id_foreign` (`admin_id`),
  KEY `profit_shares_published_by_foreign` (`published_by`),
  CONSTRAINT `profit_shares_admin_id_foreign` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`),
  CONSTRAINT `profit_shares_published_by_foreign` FOREIGN KEY (`published_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `promotional_material_downloads`
--

DROP TABLE IF EXISTS `promotional_material_downloads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `promotional_material_downloads` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `promotional_material_id` bigint(20) unsigned NOT NULL,
  `ip_address` varchar(255) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `promotional_material_downloads_user_id_index` (`user_id`),
  KEY `promotional_material_downloads_promotional_material_id_index` (`promotional_material_id`),
  KEY `promotional_material_downloads_created_at_index` (`created_at`),
  CONSTRAINT `promotional_material_downloads_promotional_material_id_foreign` FOREIGN KEY (`promotional_material_id`) REFERENCES `promotional_materials` (`id`) ON DELETE CASCADE,
  CONSTRAINT `promotional_material_downloads_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `promotional_materials`
--

DROP TABLE IF EXISTS `promotional_materials`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `promotional_materials` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `category` varchar(255) NOT NULL,
  `type` varchar(255) NOT NULL,
  `file_url` varchar(255) NOT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `file_size` bigint(20) unsigned DEFAULT NULL,
  `thumbnail_url` varchar(255) DEFAULT NULL,
  `preview_url` varchar(255) DEFAULT NULL,
  `dimensions` varchar(255) DEFAULT NULL,
  `download_count` int(10) unsigned NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `promotional_materials_category_index` (`category`),
  KEY `promotional_materials_type_index` (`type`),
  KEY `promotional_materials_is_active_index` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `protocol1_alerts`
--

DROP TABLE IF EXISTS `protocol1_alerts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `protocol1_alerts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `severity` enum('CRITICAL','HIGH','MEDIUM','LOW') NOT NULL COMMENT 'Alert severity',
  `title` varchar(255) NOT NULL COMMENT 'Alert title',
  `message` text NOT NULL COMMENT 'Alert message',
  `alert_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT 'Full alert context and violation details' CHECK (json_valid(`alert_data`)),
  `is_acknowledged` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Has admin acknowledged alert?',
  `acknowledged_by` bigint(20) unsigned DEFAULT NULL,
  `acknowledged_at` timestamp NULL DEFAULT NULL COMMENT 'When alert was acknowledged',
  `admin_notes` text DEFAULT NULL COMMENT 'Admin notes on resolution',
  `resolution_status` enum('pending','investigating','resolved','escalated') NOT NULL DEFAULT 'pending',
  `resolved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `protocol1_alerts_acknowledged_by_foreign` (`acknowledged_by`),
  KEY `idx_alert_queue` (`is_acknowledged`,`severity`,`created_at`),
  KEY `idx_resolution_status_date` (`resolution_status`,`created_at`),
  KEY `protocol1_alerts_severity_index` (`severity`),
  KEY `protocol1_alerts_is_acknowledged_index` (`is_acknowledged`),
  KEY `protocol1_alerts_resolution_status_index` (`resolution_status`),
  CONSTRAINT `protocol1_alerts_acknowledged_by_foreign` FOREIGN KEY (`acknowledged_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Protocol-1 alerts - critical violations and anomalies requiring admin attention';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `protocol1_violation_log`
--

DROP TABLE IF EXISTS `protocol1_violation_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `protocol1_violation_log` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `protocol_version` varchar(20) NOT NULL DEFAULT '1.0.0',
  `rule_id` varchar(100) NOT NULL COMMENT 'Rule identifier (e.g., RULE_1_1_SUSPENSION)',
  `rule_name` varchar(255) DEFAULT NULL COMMENT 'Human-readable rule name',
  `severity` enum('CRITICAL','HIGH','MEDIUM','LOW') NOT NULL COMMENT 'Violation severity',
  `message` text NOT NULL COMMENT 'Violation description',
  `actor_type` varchar(50) NOT NULL COMMENT 'Actor type: issuer, admin_judgment, investor, system_enforcement, etc.',
  `action` varchar(100) NOT NULL COMMENT 'Action attempted (e.g., submit_disclosure, create_investment)',
  `company_id` bigint(20) unsigned DEFAULT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `violation_details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT 'Full violation data structure' CHECK (json_valid(`violation_details`)),
  `context_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Request context: IP, user agent, URL, etc.' CHECK (json_valid(`context_data`)),
  `was_blocked` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Was action blocked?',
  `enforcement_mode` enum('strict','lenient','monitor') NOT NULL DEFAULT 'strict' COMMENT 'Enforcement mode at time of violation',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_actor_date` (`actor_type`,`created_at`),
  KEY `idx_company_severity_date` (`company_id`,`severity`,`created_at`),
  KEY `idx_rule_date` (`rule_id`,`created_at`),
  KEY `idx_severity_blocked_date` (`severity`,`was_blocked`,`created_at`),
  KEY `protocol1_violation_log_protocol_version_index` (`protocol_version`),
  KEY `protocol1_violation_log_rule_id_index` (`rule_id`),
  KEY `protocol1_violation_log_severity_index` (`severity`),
  KEY `protocol1_violation_log_actor_type_index` (`actor_type`),
  KEY `protocol1_violation_log_action_index` (`action`),
  KEY `protocol1_violation_log_company_id_index` (`company_id`),
  KEY `protocol1_violation_log_user_id_index` (`user_id`),
  KEY `protocol1_violation_log_was_blocked_index` (`was_blocked`),
  CONSTRAINT `protocol1_violation_log_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `protocol1_violation_log_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Protocol-1 governance violation log - comprehensive audit trail for all rule violations';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `push_logs`
--

DROP TABLE IF EXISTS `push_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `push_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `device_token` varchar(255) NOT NULL,
  `device_type` varchar(255) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `body` text NOT NULL,
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`data`)),
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `provider` varchar(255) DEFAULT NULL,
  `provider_message_id` varchar(255) DEFAULT NULL,
  `provider_response` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`provider_response`)),
  `error_message` text DEFAULT NULL,
  `sent_at` timestamp NULL DEFAULT NULL,
  `delivered_at` timestamp NULL DEFAULT NULL,
  `opened_at` timestamp NULL DEFAULT NULL,
  `failed_at` timestamp NULL DEFAULT NULL,
  `priority` varchar(255) NOT NULL DEFAULT 'normal',
  `ttl` int(11) DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `action_url` varchar(255) DEFAULT NULL,
  `badge_count` int(11) DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `push_logs_user_id_created_at_index` (`user_id`,`created_at`),
  KEY `push_logs_status_created_at_index` (`status`,`created_at`),
  KEY `push_logs_device_token_created_at_index` (`device_token`,`created_at`),
  KEY `push_logs_device_type_index` (`device_type`),
  KEY `push_logs_provider_message_id_index` (`provider_message_id`),
  KEY `push_logs_sent_at_index` (`sent_at`),
  KEY `push_logs_opened_at_index` (`opened_at`),
  CONSTRAINT `push_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `reconciliation_alerts`
--

DROP TABLE IF EXISTS `reconciliation_alerts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `reconciliation_alerts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `alert_type` varchar(255) NOT NULL,
  `severity` varchar(255) NOT NULL,
  `entity_type` varchar(255) NOT NULL,
  `entity_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `expected_value` decimal(15,2) DEFAULT NULL,
  `actual_value` decimal(15,2) DEFAULT NULL,
  `discrepancy` decimal(15,2) DEFAULT NULL,
  `description` text NOT NULL,
  `root_cause` varchar(255) DEFAULT NULL,
  `resolved` tinyint(1) NOT NULL DEFAULT 0,
  `resolved_by` bigint(20) unsigned DEFAULT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `resolution_notes` text DEFAULT NULL,
  `auto_fix_attempted` tinyint(1) NOT NULL DEFAULT 0,
  `auto_fix_successful` tinyint(1) NOT NULL DEFAULT 0,
  `auto_fix_attempted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `root_cause_identified_at` timestamp NULL DEFAULT NULL,
  `root_cause_identified_by` bigint(20) unsigned DEFAULT NULL,
  `root_cause_group` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `reconciliation_alerts_alert_type_index` (`alert_type`),
  KEY `reconciliation_alerts_severity_index` (`severity`),
  KEY `reconciliation_alerts_entity_type_entity_id_index` (`entity_type`,`entity_id`),
  KEY `reconciliation_alerts_resolved_index` (`resolved`),
  KEY `reconciliation_alerts_created_at_index` (`created_at`),
  KEY `reconciliation_alerts_root_cause_index` (`root_cause`),
  KEY `reconciliation_alerts_root_cause_group_index` (`root_cause_group`),
  CONSTRAINT `check_reconciliation_severity` CHECK (`severity` in ('low','medium','high','critical'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `reconciliation_logs`
--

DROP TABLE IF EXISTS `reconciliation_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `reconciliation_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `run_date` date NOT NULL,
  `run_time` time NOT NULL,
  `success` tinyint(1) NOT NULL DEFAULT 0,
  `error_count` int(11) NOT NULL DEFAULT 0,
  `warning_count` int(11) NOT NULL DEFAULT 0,
  `checks_performed` int(11) NOT NULL DEFAULT 0,
  `duration_seconds` int(11) NOT NULL DEFAULT 0,
  `errors` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`errors`)),
  `warnings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`warnings`)),
  `stats` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`stats`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `reconciliation_logs_run_date_success_index` (`run_date`,`success`),
  KEY `reconciliation_logs_created_at_index` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `redirects`
--

DROP TABLE IF EXISTS `redirects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `redirects` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `from_url` varchar(255) NOT NULL,
  `to_url` varchar(255) NOT NULL,
  `status_code` int(11) NOT NULL DEFAULT 301,
  `hit_count` bigint(20) unsigned NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `redirects_from_url_unique` (`from_url`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `referral_campaigns`
--

DROP TABLE IF EXISTS `referral_campaigns`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `referral_campaigns` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `start_date` datetime NOT NULL,
  `end_date` datetime NOT NULL,
  `multiplier` decimal(5,2) NOT NULL DEFAULT 1.00,
  `bonus_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `slug` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `starts_at` timestamp NULL DEFAULT NULL,
  `ends_at` timestamp NULL DEFAULT NULL,
  `max_referrals` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `referrals`
--

DROP TABLE IF EXISTS `referrals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `referrals` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `referrer_id` bigint(20) unsigned NOT NULL,
  `referred_id` bigint(20) unsigned NOT NULL,
  `referral_campaign_id` bigint(20) unsigned DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `referrals_referrer_id_referred_id_unique` (`referrer_id`,`referred_id`),
  UNIQUE KEY `referrals_referred_id_unique` (`referred_id`),
  KEY `referrals_referral_campaign_id_foreign` (`referral_campaign_id`),
  KEY `referrals_referrer_status_index` (`referrer_id`,`status`),
  KEY `referrals_referred_index` (`referred_id`),
  CONSTRAINT `referrals_referral_campaign_id_foreign` FOREIGN KEY (`referral_campaign_id`) REFERENCES `referral_campaigns` (`id`) ON DELETE SET NULL,
  CONSTRAINT `referrals_referred_id_foreign` FOREIGN KEY (`referred_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `referrals_referrer_id_foreign` FOREIGN KEY (`referrer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `report_runs`
--

DROP TABLE IF EXISTS `report_runs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `report_runs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `scheduled_report_id` bigint(20) unsigned NOT NULL,
  `status` varchar(255) NOT NULL,
  `file_path` text DEFAULT NULL,
  `file_size` int(11) DEFAULT NULL,
  `error_details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`error_details`)),
  `started_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `report_runs_scheduled_report_id_foreign` (`scheduled_report_id`),
  CONSTRAINT `report_runs_scheduled_report_id_foreign` FOREIGN KEY (`scheduled_report_id`) REFERENCES `scheduled_reports` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `reports`
--

DROP TABLE IF EXISTS `reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `reports` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `type` enum('market_analysis','research','white_paper','case_study','guide') NOT NULL DEFAULT 'research',
  `file_path` varchar(255) NOT NULL,
  `cover_image` varchar(255) DEFAULT NULL,
  `file_size` int(11) DEFAULT NULL,
  `pages` int(11) DEFAULT NULL,
  `access_level` enum('public','registered','premium','admin') NOT NULL DEFAULT 'registered',
  `requires_subscription` tinyint(1) NOT NULL DEFAULT 0,
  `author` varchar(255) DEFAULT NULL,
  `published_date` timestamp NULL DEFAULT NULL,
  `tags` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`tags`)),
  `downloads_count` int(11) NOT NULL DEFAULT 0,
  `rating` decimal(3,2) DEFAULT NULL,
  `status` enum('draft','published','archived') NOT NULL DEFAULT 'draft',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `filters` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`filters`)),
  `columns` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`columns`)),
  PRIMARY KEY (`id`),
  UNIQUE KEY `reports_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `role_has_permissions`
--

DROP TABLE IF EXISTS `role_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `role_has_permissions` (
  `permission_id` bigint(20) unsigned NOT NULL,
  `role_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`role_id`),
  KEY `role_has_permissions_role_id_foreign` (`role_id`),
  CONSTRAINT `role_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `role_has_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `roles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `guard_name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `saga_executions`
--

DROP TABLE IF EXISTS `saga_executions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `saga_executions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `saga_id` char(36) NOT NULL,
  `status` enum('initiated','executing','completed','failed','compensated','manually_resolved') NOT NULL DEFAULT 'initiated',
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`metadata`)),
  `steps_total` int(11) NOT NULL DEFAULT 0,
  `steps_completed` int(11) NOT NULL DEFAULT 0,
  `failure_step` int(11) DEFAULT NULL,
  `failure_reason` text DEFAULT NULL,
  `resolution_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`resolution_data`)),
  `resolved_by` bigint(20) unsigned DEFAULT NULL,
  `initiated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `completed_at` timestamp NULL DEFAULT NULL,
  `failed_at` timestamp NULL DEFAULT NULL,
  `compensated_at` timestamp NULL DEFAULT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `saga_executions_saga_id_unique` (`saga_id`),
  KEY `saga_executions_resolved_by_foreign` (`resolved_by`),
  KEY `saga_executions_saga_id_index` (`saga_id`),
  KEY `saga_executions_status_index` (`status`),
  KEY `saga_executions_status_failed_at_index` (`status`,`failed_at`),
  CONSTRAINT `saga_executions_resolved_by_foreign` FOREIGN KEY (`resolved_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `saga_steps`
--

DROP TABLE IF EXISTS `saga_steps`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `saga_steps` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `saga_execution_id` bigint(20) unsigned NOT NULL,
  `step_number` int(11) NOT NULL,
  `operation_class` varchar(255) NOT NULL,
  `status` enum('completed','failed') NOT NULL DEFAULT 'completed',
  `compensation_status` enum('not_compensated','compensated','compensation_failed') NOT NULL DEFAULT 'not_compensated',
  `result_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`result_data`)),
  `compensation_error` text DEFAULT NULL,
  `executed_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `compensated_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `saga_steps_saga_execution_id_index` (`saga_execution_id`),
  KEY `saga_steps_saga_execution_id_step_number_index` (`saga_execution_id`,`step_number`),
  CONSTRAINT `saga_steps_saga_execution_id_foreign` FOREIGN KEY (`saga_execution_id`) REFERENCES `saga_executions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `scheduled_reports`
--

DROP TABLE IF EXISTS `scheduled_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `scheduled_reports` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `report_type` varchar(255) NOT NULL,
  `frequency` varchar(255) NOT NULL,
  `parameters` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`parameters`)),
  `recipients` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`recipients`)),
  `format` varchar(255) NOT NULL DEFAULT 'pdf',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `last_run_at` timestamp NULL DEFAULT NULL,
  `next_run_at` timestamp NULL DEFAULT NULL,
  `created_by` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `scheduled_reports_created_by_foreign` (`created_by`),
  CONSTRAINT `scheduled_reports_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `scheduled_tasks`
--

DROP TABLE IF EXISTS `scheduled_tasks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `scheduled_tasks` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `command` varchar(255) NOT NULL,
  `expression` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `parameters` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`parameters`)),
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `last_run_at` timestamp NULL DEFAULT NULL,
  `last_run_status` varchar(255) DEFAULT NULL,
  `last_run_output` text DEFAULT NULL,
  `last_run_duration` int(11) DEFAULT NULL,
  `next_run_at` timestamp NULL DEFAULT NULL,
  `run_count` int(11) NOT NULL DEFAULT 0,
  `failure_count` int(11) NOT NULL DEFAULT 0,
  `created_by` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `scheduled_tasks_created_by_foreign` (`created_by`),
  KEY `scheduled_tasks_is_active_next_run_at_index` (`is_active`,`next_run_at`),
  CONSTRAINT `scheduled_tasks_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sectors`
--

DROP TABLE IF EXISTS `sectors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sectors` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `icon` varchar(255) DEFAULT NULL,
  `color` varchar(255) DEFAULT NULL,
  `companies_count` int(11) NOT NULL DEFAULT 0,
  `deals_count` int(11) NOT NULL DEFAULT 0,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sectors_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `security_audit_log`
--

DROP TABLE IF EXISTS `security_audit_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `security_audit_log` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `event_type` varchar(50) NOT NULL,
  `severity` varchar(20) NOT NULL DEFAULT 'warning',
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `actor_type` varchar(30) NOT NULL DEFAULT 'user',
  `resource_type` varchar(50) DEFAULT NULL,
  `resource_id` bigint(20) unsigned DEFAULT NULL,
  `details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`details`)),
  `message` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `request_method` varchar(10) DEFAULT NULL,
  `request_path` text DEFAULT NULL,
  `session_id` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `security_audit_log_event_type_created_at_index` (`event_type`,`created_at`),
  KEY `security_audit_log_user_id_created_at_index` (`user_id`,`created_at`),
  KEY `security_audit_log_resource_type_resource_id_index` (`resource_type`,`resource_id`),
  KEY `security_audit_log_severity_created_at_index` (`severity`,`created_at`),
  KEY `security_audit_log_ip_address_index` (`ip_address`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(255) NOT NULL,
  `value` longtext DEFAULT NULL,
  `type` varchar(255) NOT NULL DEFAULT 'string',
  `group` varchar(255) NOT NULL DEFAULT 'system',
  `description` varchar(255) DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `settings_key_unique` (`key`),
  KEY `settings_updated_by_foreign` (`updated_by`),
  CONSTRAINT `settings_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=431 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `share_allocation_logs`
--

DROP TABLE IF EXISTS `share_allocation_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `share_allocation_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `bulk_purchase_id` bigint(20) unsigned NOT NULL,
  `allocatable_type` varchar(255) NOT NULL,
  `allocatable_id` bigint(20) unsigned NOT NULL,
  `value_allocated` decimal(15,2) NOT NULL,
  `units_allocated` decimal(15,4) DEFAULT NULL,
  `inventory_before` decimal(15,2) NOT NULL,
  `inventory_after` decimal(15,2) NOT NULL,
  `admin_ledger_entry_id` bigint(20) unsigned DEFAULT NULL COMMENT 'Link to cash receipt entry',
  `company_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `allocated_by` bigint(20) unsigned DEFAULT NULL,
  `is_immutable` tinyint(1) NOT NULL DEFAULT 1,
  `locked_at` timestamp NULL DEFAULT NULL,
  `is_reversed` tinyint(1) NOT NULL DEFAULT 0,
  `reversed_at` timestamp NULL DEFAULT NULL,
  `reversal_reason` varchar(255) DEFAULT NULL,
  `reversal_log_id` bigint(20) unsigned DEFAULT NULL COMMENT 'ID of compensating log entry',
  `ip_address` varchar(255) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `share_allocation_logs_user_id_foreign` (`user_id`),
  KEY `share_allocation_logs_allocated_by_foreign` (`allocated_by`),
  KEY `share_allocation_logs_bulk_purchase_id_is_reversed_index` (`bulk_purchase_id`,`is_reversed`),
  KEY `share_allocation_logs_allocatable_type_allocatable_id_index` (`allocatable_type`,`allocatable_id`),
  KEY `share_allocation_logs_company_id_created_at_index` (`company_id`,`created_at`),
  KEY `share_allocation_logs_admin_ledger_entry_id_index` (`admin_ledger_entry_id`),
  CONSTRAINT `share_allocation_logs_allocated_by_foreign` FOREIGN KEY (`allocated_by`) REFERENCES `users` (`id`),
  CONSTRAINT `share_allocation_logs_bulk_purchase_id_foreign` FOREIGN KEY (`bulk_purchase_id`) REFERENCES `bulk_purchases` (`id`) ON DELETE CASCADE,
  CONSTRAINT `share_allocation_logs_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`),
  CONSTRAINT `share_allocation_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER prevent_allocation_log_update
            BEFORE UPDATE ON share_allocation_logs
            FOR EACH ROW
            BEGIN
                -- Allow only reversal marking (is_reversed and reversal fields)
                IF OLD.value_allocated != NEW.value_allocated
                   OR OLD.bulk_purchase_id != NEW.bulk_purchase_id
                   OR OLD.allocatable_id != NEW.allocatable_id
                   OR OLD.inventory_before != NEW.inventory_before
                   OR OLD.inventory_after != NEW.inventory_after
                THEN
                    SIGNAL SQLSTATE '45000'
                    SET MESSAGE_TEXT = 'IMMUTABILITY VIOLATION: Core fields of share_allocation_logs cannot be updated. Only reversal marking is allowed.';
                END IF;
            END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER prevent_allocation_log_delete
            BEFORE DELETE ON share_allocation_logs
            FOR EACH ROW
            BEGIN
                SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'IMMUTABILITY VIOLATION: share_allocation_logs cannot be deleted. Mark as reversed instead.';
            END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `sla_policies`
--

DROP TABLE IF EXISTS `sla_policies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sla_policies` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `ticket_category` varchar(255) DEFAULT NULL,
  `ticket_priority` enum('low','medium','high') DEFAULT NULL,
  `response_time_hours` int(11) NOT NULL DEFAULT 24,
  `resolution_time_hours` int(11) NOT NULL DEFAULT 72,
  `business_hours_only` tinyint(1) NOT NULL DEFAULT 0,
  `work_start_time` time NOT NULL DEFAULT '09:00:00',
  `work_end_time` time NOT NULL DEFAULT '18:00:00',
  `working_days` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT '[1,2,3,4,5]' CHECK (json_valid(`working_days`)),
  `auto_escalate` tinyint(1) NOT NULL DEFAULT 1,
  `escalation_threshold_percent` int(11) NOT NULL DEFAULT 80,
  `priority_order` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sla_policies_ticket_category_ticket_priority_index` (`ticket_category`,`ticket_priority`),
  KEY `sla_policies_is_active_index` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sms_logs`
--

DROP TABLE IF EXISTS `sms_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sms_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `sms_template_id` bigint(20) unsigned DEFAULT NULL,
  `recipient_mobile` varchar(255) NOT NULL,
  `recipient_name` varchar(255) DEFAULT NULL,
  `template_slug` varchar(255) DEFAULT NULL,
  `dlt_template_id` varchar(255) DEFAULT NULL,
  `message` text NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'queued',
  `provider` varchar(255) DEFAULT NULL,
  `provider_message_id` varchar(255) DEFAULT NULL,
  `provider_response` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`provider_response`)),
  `error_message` text DEFAULT NULL,
  `gateway_message_id` varchar(255) DEFAULT NULL,
  `sent_at` timestamp NULL DEFAULT NULL,
  `delivered_at` timestamp NULL DEFAULT NULL,
  `failed_at` timestamp NULL DEFAULT NULL,
  `credits_used` decimal(8,2) DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sms_logs_user_id_created_at_index` (`user_id`,`created_at`),
  KEY `sms_logs_sms_template_id_created_at_index` (`sms_template_id`,`created_at`),
  KEY `sms_logs_status_created_at_index` (`status`,`created_at`),
  KEY `sms_logs_provider_message_id_index` (`provider_message_id`),
  KEY `sms_logs_sent_at_index` (`sent_at`),
  CONSTRAINT `sms_logs_sms_template_id_foreign` FOREIGN KEY (`sms_template_id`) REFERENCES `sms_templates` (`id`) ON DELETE SET NULL,
  CONSTRAINT `sms_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sms_templates`
--

DROP TABLE IF EXISTS `sms_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sms_templates` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `slug` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `content` longtext NOT NULL,
  `variables` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`variables`)),
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `body` text DEFAULT NULL,
  `dlt_template_id` varchar(255) DEFAULT NULL COMMENT 'DLT template ID (India compliance)',
  PRIMARY KEY (`id`),
  UNIQUE KEY `sms_templates_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `stuck_state_alerts`
--

DROP TABLE IF EXISTS `stuck_state_alerts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stuck_state_alerts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `alert_type` varchar(255) NOT NULL,
  `severity` varchar(255) NOT NULL,
  `entity_type` varchar(255) NOT NULL,
  `entity_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `stuck_state` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `root_cause` varchar(255) DEFAULT NULL,
  `stuck_duration_seconds` int(11) NOT NULL,
  `stuck_since` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `auto_resolvable` tinyint(1) NOT NULL DEFAULT 0,
  `auto_resolution_action` varchar(255) DEFAULT NULL,
  `auto_resolved` tinyint(1) NOT NULL DEFAULT 0,
  `auto_resolved_at` timestamp NULL DEFAULT NULL,
  `requires_manual_review` tinyint(1) NOT NULL DEFAULT 0,
  `reviewed` tinyint(1) NOT NULL DEFAULT 0,
  `reviewed_by` bigint(20) unsigned DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `resolution_notes` text DEFAULT NULL,
  `escalated` tinyint(1) NOT NULL DEFAULT 0,
  `escalated_at` timestamp NULL DEFAULT NULL,
  `escalated_to` bigint(20) unsigned DEFAULT NULL,
  `admin_notified` tinyint(1) NOT NULL DEFAULT 0,
  `user_notified` tinyint(1) NOT NULL DEFAULT 0,
  `admin_notified_at` timestamp NULL DEFAULT NULL,
  `user_notified_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `root_cause_identified_at` timestamp NULL DEFAULT NULL,
  `root_cause_identified_by` bigint(20) unsigned DEFAULT NULL,
  `root_cause_group` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `stuck_state_alerts_entity_type_entity_id_index` (`entity_type`,`entity_id`),
  KEY `stuck_state_alerts_severity_index` (`severity`),
  KEY `stuck_state_alerts_reviewed_index` (`reviewed`),
  KEY `stuck_state_alerts_escalated_index` (`escalated`),
  KEY `stuck_state_alerts_created_at_index` (`created_at`),
  KEY `stuck_state_alerts_root_cause_index` (`root_cause`),
  KEY `stuck_state_alerts_root_cause_group_index` (`root_cause_group`),
  CONSTRAINT `check_stuck_alert_severity` CHECK (`severity` in ('low','medium','high','critical'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `subscriptions`
--

DROP TABLE IF EXISTS `subscriptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `subscriptions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `plan_id` bigint(20) unsigned NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `amount_paise` bigint(20) DEFAULT NULL,
  `subscription_code` varchar(255) NOT NULL,
  `razorpay_subscription_id` varchar(255) DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'active',
  `is_auto_debit` tinyint(1) NOT NULL DEFAULT 0,
  `bonus_contract_snapshot` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`bonus_contract_snapshot`)),
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `next_payment_date` date NOT NULL,
  `bonus_multiplier` decimal(5,2) NOT NULL DEFAULT 1.00,
  `progressive_config` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Immutable: Progressive bonus rules at subscription time' CHECK (json_valid(`progressive_config`)),
  `milestone_config` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Immutable: Milestone bonus rules at subscription time' CHECK (json_valid(`milestone_config`)),
  `consistency_config` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Immutable: Consistency/cashback rules at subscription time' CHECK (json_valid(`consistency_config`)),
  `welcome_bonus_config` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Immutable: Welcome bonus rules (first payment only)' CHECK (json_valid(`welcome_bonus_config`)),
  `referral_tiers` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Immutable: Referral tier multipliers at subscription time' CHECK (json_valid(`referral_tiers`)),
  `celebration_bonus_config` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Immutable: Celebration event bonus rules' CHECK (json_valid(`celebration_bonus_config`)),
  `lucky_draw_entries` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Immutable: Lucky draw entry rules per payment' CHECK (json_valid(`lucky_draw_entries`)),
  `config_snapshot_at` timestamp NULL DEFAULT NULL COMMENT 'When the bonus config was snapshotted',
  `config_snapshot_version` varchar(32) DEFAULT NULL COMMENT 'Version hash of snapshotted config for integrity verification',
  `consecutive_payments_count` int(11) NOT NULL DEFAULT 0,
  `pause_count` int(11) NOT NULL DEFAULT 0,
  `pause_start_date` date DEFAULT NULL,
  `pause_end_date` date DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `cancellation_reason` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `subscriptions_subscription_code_unique` (`subscription_code`),
  KEY `subscriptions_user_id_foreign` (`user_id`),
  KEY `subscriptions_plan_id_foreign` (`plan_id`),
  KEY `subscriptions_amount_paise_index` (`amount_paise`),
  CONSTRAINT `subscriptions_plan_id_foreign` FOREIGN KEY (`plan_id`) REFERENCES `plans` (`id`),
  CONSTRAINT `subscriptions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER enforce_subscription_snapshot_immutability
            BEFORE UPDATE ON subscriptions
            FOR EACH ROW
            BEGIN
                -- Only enforce if snapshot was previously set (not initial creation)
                IF OLD.config_snapshot_at IS NOT NULL THEN
                    
                    IF NOT (COALESCE(CAST(OLD.progressive_config AS CHAR), '') <=> COALESCE(CAST(NEW.progressive_config AS CHAR), '')) THEN
                        SIGNAL SQLSTATE '45000'
                        SET MESSAGE_TEXT = 'IMMUTABILITY VIOLATION: Cannot modify progressive_config after snapshot creation. Subscription snapshots are immutable contracts.';
                    END IF;
                

                    IF NOT (COALESCE(CAST(OLD.milestone_config AS CHAR), '') <=> COALESCE(CAST(NEW.milestone_config AS CHAR), '')) THEN
                        SIGNAL SQLSTATE '45000'
                        SET MESSAGE_TEXT = 'IMMUTABILITY VIOLATION: Cannot modify milestone_config after snapshot creation. Subscription snapshots are immutable contracts.';
                    END IF;
                

                    IF NOT (COALESCE(CAST(OLD.consistency_config AS CHAR), '') <=> COALESCE(CAST(NEW.consistency_config AS CHAR), '')) THEN
                        SIGNAL SQLSTATE '45000'
                        SET MESSAGE_TEXT = 'IMMUTABILITY VIOLATION: Cannot modify consistency_config after snapshot creation. Subscription snapshots are immutable contracts.';
                    END IF;
                

                    IF NOT (COALESCE(CAST(OLD.welcome_bonus_config AS CHAR), '') <=> COALESCE(CAST(NEW.welcome_bonus_config AS CHAR), '')) THEN
                        SIGNAL SQLSTATE '45000'
                        SET MESSAGE_TEXT = 'IMMUTABILITY VIOLATION: Cannot modify welcome_bonus_config after snapshot creation. Subscription snapshots are immutable contracts.';
                    END IF;
                

                    IF NOT (COALESCE(CAST(OLD.referral_tiers AS CHAR), '') <=> COALESCE(CAST(NEW.referral_tiers AS CHAR), '')) THEN
                        SIGNAL SQLSTATE '45000'
                        SET MESSAGE_TEXT = 'IMMUTABILITY VIOLATION: Cannot modify referral_tiers after snapshot creation. Subscription snapshots are immutable contracts.';
                    END IF;
                

                    IF NOT (COALESCE(CAST(OLD.celebration_bonus_config AS CHAR), '') <=> COALESCE(CAST(NEW.celebration_bonus_config AS CHAR), '')) THEN
                        SIGNAL SQLSTATE '45000'
                        SET MESSAGE_TEXT = 'IMMUTABILITY VIOLATION: Cannot modify celebration_bonus_config after snapshot creation. Subscription snapshots are immutable contracts.';
                    END IF;
                

                    IF NOT (COALESCE(CAST(OLD.lucky_draw_entries AS CHAR), '') <=> COALESCE(CAST(NEW.lucky_draw_entries AS CHAR), '')) THEN
                        SIGNAL SQLSTATE '45000'
                        SET MESSAGE_TEXT = 'IMMUTABILITY VIOLATION: Cannot modify lucky_draw_entries after snapshot creation. Subscription snapshots are immutable contracts.';
                    END IF;
                

                    IF NOT (OLD.config_snapshot_version <=> NEW.config_snapshot_version) THEN
                        SIGNAL SQLSTATE '45000'
                        SET MESSAGE_TEXT = 'IMMUTABILITY VIOLATION: Cannot modify config_snapshot_version after snapshot creation. Subscription snapshots are immutable contracts.';
                    END IF;
                

                    IF NOT (OLD.config_snapshot_at <=> NEW.config_snapshot_at) THEN
                        SIGNAL SQLSTATE '45000'
                        SET MESSAGE_TEXT = 'IMMUTABILITY VIOLATION: Cannot modify config_snapshot_at after snapshot creation. Subscription snapshots are immutable contracts.';
                    END IF;
                
                END IF;
            END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `support_messages`
--

DROP TABLE IF EXISTS `support_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `support_messages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `support_ticket_id` bigint(20) unsigned NOT NULL,
  `channel_id` bigint(20) unsigned DEFAULT NULL,
  `external_message_id` varchar(255) DEFAULT NULL,
  `channel_metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`channel_metadata`)),
  `user_id` bigint(20) unsigned NOT NULL,
  `is_admin_reply` tinyint(1) NOT NULL DEFAULT 0,
  `is_internal` tinyint(1) NOT NULL DEFAULT 0,
  `mentioned_users` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`mentioned_users`)),
  `message` text NOT NULL,
  `attachments` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`attachments`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `support_messages_support_ticket_id_foreign` (`support_ticket_id`),
  KEY `support_messages_user_id_foreign` (`user_id`),
  KEY `support_messages_channel_id_foreign` (`channel_id`),
  CONSTRAINT `support_messages_channel_id_foreign` FOREIGN KEY (`channel_id`) REFERENCES `communication_channels` (`id`) ON DELETE SET NULL,
  CONSTRAINT `support_messages_support_ticket_id_foreign` FOREIGN KEY (`support_ticket_id`) REFERENCES `support_tickets` (`id`) ON DELETE CASCADE,
  CONSTRAINT `support_messages_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `support_tickets`
--

DROP TABLE IF EXISTS `support_tickets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `support_tickets` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `ticket_code` varchar(255) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `category` varchar(255) NOT NULL,
  `priority` varchar(255) NOT NULL DEFAULT 'medium',
  `status` varchar(255) NOT NULL DEFAULT 'open',
  `preferred_channel_id` bigint(20) unsigned DEFAULT NULL,
  `sla_hours` int(11) NOT NULL DEFAULT 24,
  `assigned_to` bigint(20) unsigned DEFAULT NULL,
  `resolved_by` bigint(20) unsigned DEFAULT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `closed_at` timestamp NULL DEFAULT NULL,
  `rating` tinyint(3) unsigned DEFAULT NULL,
  `rating_feedback` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `currently_viewing_by` bigint(20) unsigned DEFAULT NULL,
  `viewing_started_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `support_tickets_ticket_code_unique` (`ticket_code`),
  KEY `support_tickets_resolved_by_foreign` (`resolved_by`),
  KEY `support_tickets_user_status_index` (`user_id`,`status`),
  KEY `support_tickets_status_created_index` (`status`,`created_at`),
  KEY `support_tickets_assigned_index` (`assigned_to`),
  KEY `support_tickets_category_index` (`category`),
  KEY `support_tickets_currently_viewing_by_foreign` (`currently_viewing_by`),
  KEY `support_tickets_preferred_channel_id_foreign` (`preferred_channel_id`),
  CONSTRAINT `support_tickets_assigned_to_foreign` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `support_tickets_currently_viewing_by_foreign` FOREIGN KEY (`currently_viewing_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `support_tickets_preferred_channel_id_foreign` FOREIGN KEY (`preferred_channel_id`) REFERENCES `communication_channels` (`id`) ON DELETE SET NULL,
  CONSTRAINT `support_tickets_resolved_by_foreign` FOREIGN KEY (`resolved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `support_tickets_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `system_health_checks`
--

DROP TABLE IF EXISTS `system_health_checks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `system_health_checks` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `check_name` varchar(255) NOT NULL,
  `status` varchar(255) NOT NULL,
  `message` text DEFAULT NULL,
  `details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`details`)),
  `response_time` int(11) DEFAULT NULL,
  `checked_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `system_health_checks_check_name_checked_at_index` (`check_name`,`checked_at`),
  KEY `system_health_checks_status_index` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `system_health_metrics`
--

DROP TABLE IF EXISTS `system_health_metrics`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `system_health_metrics` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `metric_name` varchar(255) NOT NULL,
  `category` varchar(255) NOT NULL,
  `severity` varchar(255) NOT NULL,
  `current_value` decimal(15,2) DEFAULT NULL,
  `threshold_warning` decimal(15,2) DEFAULT NULL,
  `threshold_critical` decimal(15,2) DEFAULT NULL,
  `unit` varchar(255) DEFAULT NULL,
  `is_healthy` tinyint(1) NOT NULL DEFAULT 1,
  `health_message` text DEFAULT NULL,
  `last_checked_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `unhealthy_since` timestamp NULL DEFAULT NULL,
  `details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`details`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `system_health_metrics_metric_name_index` (`metric_name`),
  KEY `system_health_metrics_category_index` (`category`),
  KEY `system_health_metrics_severity_index` (`severity`),
  KEY `system_health_metrics_is_healthy_index` (`is_healthy`),
  KEY `system_health_metrics_last_checked_at_index` (`last_checked_at`),
  CONSTRAINT `check_health_severity` CHECK (`severity` in ('info','warning','error','critical'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tds_deductions`
--

DROP TABLE IF EXISTS `tds_deductions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tds_deductions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `transaction_id` bigint(20) unsigned NOT NULL,
  `financial_year` varchar(10) NOT NULL,
  `quarter` tinyint(4) DEFAULT NULL,
  `transaction_type` varchar(255) NOT NULL,
  `gross_amount_paise` bigint(20) NOT NULL DEFAULT 0,
  `gross_amount` decimal(15,2) NOT NULL,
  `tds_amount_paise` bigint(20) NOT NULL DEFAULT 0,
  `tds_amount` decimal(15,2) NOT NULL,
  `tds_rate` decimal(5,2) NOT NULL,
  `section_code` varchar(20) NOT NULL DEFAULT '194',
  `net_amount_paise` bigint(20) DEFAULT NULL,
  `net_amount` decimal(15,2) DEFAULT NULL,
  `pan_number` varchar(255) DEFAULT NULL,
  `pan_available` tinyint(1) NOT NULL DEFAULT 1,
  `pan_verified` tinyint(1) NOT NULL DEFAULT 0,
  `deduction_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `deposit_date` date DEFAULT NULL,
  `challan_number` varchar(50) DEFAULT NULL,
  `bsr_code` varchar(10) DEFAULT NULL,
  `certificate_number` varchar(50) DEFAULT NULL,
  `certificate_date` date DEFAULT NULL,
  `certificate_path` varchar(255) DEFAULT NULL,
  `status` enum('pending','deposited','filed','certified') NOT NULL DEFAULT 'pending',
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `remarks` text DEFAULT NULL,
  `form_16a_reference` varchar(255) DEFAULT NULL,
  `form_16a_generated_at` timestamp NULL DEFAULT NULL,
  `is_archived` tinyint(1) NOT NULL DEFAULT 0,
  `archived_at` timestamp NULL DEFAULT NULL,
  `retention_period` varchar(255) NOT NULL DEFAULT '7years' COMMENT 'Income Tax Act retention',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tds_deductions_transaction_id_foreign` (`transaction_id`),
  KEY `tds_deductions_user_id_financial_year_index` (`user_id`,`financial_year`),
  KEY `tds_deductions_financial_year_index` (`financial_year`),
  KEY `tds_deductions_transaction_type_index` (`transaction_type`),
  KEY `tds_deductions_deducted_at_index` (`deduction_date`),
  KEY `tds_deductions_form_16a_reference_index` (`form_16a_reference`),
  KEY `tds_deductions_is_archived_index` (`is_archived`),
  KEY `idx_tds_fy_quarter` (`financial_year`,`quarter`),
  KEY `idx_tds_deduction_date` (`deduction_date`),
  KEY `idx_tds_status` (`status`),
  KEY `idx_tds_section_code` (`section_code`),
  CONSTRAINT `tds_deductions_transaction_id_foreign` FOREIGN KEY (`transaction_id`) REFERENCES `transactions` (`id`),
  CONSTRAINT `tds_deductions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `check_tds_amount_positive` CHECK (`tds_amount` > 0),
  CONSTRAINT `check_gross_amount_positive` CHECK (`gross_amount` > 0),
  CONSTRAINT `check_tds_not_exceed_gross` CHECK (`tds_amount` <= `gross_amount`),
  CONSTRAINT `check_tds_rate_valid` CHECK (`tds_rate` >= 0 and `tds_rate` <= 30)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tds_quarterly_returns`
--

DROP TABLE IF EXISTS `tds_quarterly_returns`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tds_quarterly_returns` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `financial_year` varchar(10) NOT NULL,
  `quarter` tinyint(4) NOT NULL,
  `return_type` enum('24Q','26Q','27Q') NOT NULL,
  `due_date` date NOT NULL,
  `filed_date` date DEFAULT NULL,
  `acknowledgement_number` varchar(50) DEFAULT NULL,
  `total_deductees` int(11) NOT NULL,
  `total_tds_paise` bigint(20) NOT NULL,
  `total_tds` decimal(15,2) NOT NULL,
  `status` enum('pending','filed','revised','rectified') NOT NULL DEFAULT 'pending',
  `return_file_path` varchar(255) DEFAULT NULL,
  `ack_file_path` varchar(255) DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tds_quarterly_returns_financial_year_quarter_return_type_unique` (`financial_year`,`quarter`,`return_type`),
  KEY `tds_quarterly_returns_status_index` (`status`),
  KEY `tds_quarterly_returns_due_date_index` (`due_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `ticket_agent_activity`
--

DROP TABLE IF EXISTS `ticket_agent_activity`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ticket_agent_activity` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `support_ticket_id` bigint(20) unsigned NOT NULL,
  `agent_id` bigint(20) unsigned NOT NULL,
  `activity_type` enum('viewing','typing','editing_reply','assigned','transferred','status_changed','escalated') NOT NULL,
  `activity_data` text DEFAULT NULL,
  `activity_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_ticket_activity_ticket_time` (`support_ticket_id`,`activity_at`),
  KEY `idx_ticket_activity_agent_time` (`agent_id`,`activity_at`),
  KEY `idx_ticket_activity_composite` (`support_ticket_id`,`agent_id`,`activity_type`),
  CONSTRAINT `ticket_agent_activity_agent_id_foreign` FOREIGN KEY (`agent_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ticket_agent_activity_support_ticket_id_foreign` FOREIGN KEY (`support_ticket_id`) REFERENCES `support_tickets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `ticket_assignments`
--

DROP TABLE IF EXISTS `ticket_assignments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ticket_assignments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `support_ticket_id` bigint(20) unsigned NOT NULL,
  `assigned_to_user_id` bigint(20) unsigned NOT NULL,
  `assigned_by_user_id` bigint(20) unsigned DEFAULT NULL,
  `role` enum('primary','collaborator') NOT NULL DEFAULT 'primary',
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_ticket_assign_role` (`support_ticket_id`,`assigned_to_user_id`,`role`),
  KEY `ticket_assignments_assigned_by_user_id_foreign` (`assigned_by_user_id`),
  KEY `idx_ticket_assign_user` (`assigned_to_user_id`),
  KEY `idx_ticket_assign_ticket_role` (`support_ticket_id`,`role`),
  CONSTRAINT `ticket_assignments_assigned_by_user_id_foreign` FOREIGN KEY (`assigned_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ticket_assignments_assigned_to_user_id_foreign` FOREIGN KEY (`assigned_to_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ticket_assignments_support_ticket_id_foreign` FOREIGN KEY (`support_ticket_id`) REFERENCES `support_tickets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `ticket_sla_tracking`
--

DROP TABLE IF EXISTS `ticket_sla_tracking`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ticket_sla_tracking` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `support_ticket_id` bigint(20) unsigned NOT NULL,
  `sla_policy_id` bigint(20) unsigned DEFAULT NULL,
  `response_due_at` timestamp NULL DEFAULT NULL,
  `first_responded_at` timestamp NULL DEFAULT NULL,
  `response_sla_breached` tinyint(1) NOT NULL DEFAULT 0,
  `response_time_minutes` int(11) DEFAULT NULL,
  `resolution_due_at` timestamp NULL DEFAULT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `resolution_sla_breached` tinyint(1) NOT NULL DEFAULT 0,
  `resolution_time_minutes` int(11) DEFAULT NULL,
  `escalated` tinyint(1) NOT NULL DEFAULT 0,
  `escalated_at` timestamp NULL DEFAULT NULL,
  `escalated_to_user_id` bigint(20) unsigned DEFAULT NULL,
  `escalation_reason` text DEFAULT NULL,
  `paused_at` timestamp NULL DEFAULT NULL,
  `total_paused_minutes` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ticket_sla_tracking_sla_policy_id_foreign` (`sla_policy_id`),
  KEY `ticket_sla_tracking_escalated_to_user_id_foreign` (`escalated_to_user_id`),
  KEY `ticket_sla_tracking_support_ticket_id_index` (`support_ticket_id`),
  KEY `idx_sla_breach` (`response_sla_breached`,`resolution_sla_breached`),
  KEY `ticket_sla_tracking_escalated_index` (`escalated`),
  KEY `ticket_sla_tracking_response_due_at_index` (`response_due_at`),
  KEY `ticket_sla_tracking_resolution_due_at_index` (`resolution_due_at`),
  CONSTRAINT `ticket_sla_tracking_escalated_to_user_id_foreign` FOREIGN KEY (`escalated_to_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ticket_sla_tracking_sla_policy_id_foreign` FOREIGN KEY (`sla_policy_id`) REFERENCES `sla_policies` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ticket_sla_tracking_support_ticket_id_foreign` FOREIGN KEY (`support_ticket_id`) REFERENCES `support_tickets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `ticket_watchers`
--

DROP TABLE IF EXISTS `ticket_watchers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ticket_watchers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `support_ticket_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `notify_on_update` tinyint(1) NOT NULL DEFAULT 1,
  `notify_on_internal_note` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_ticket_watcher` (`support_ticket_id`,`user_id`),
  KEY `idx_ticket_watcher_user` (`user_id`),
  CONSTRAINT `ticket_watchers_support_ticket_id_foreign` FOREIGN KEY (`support_ticket_id`) REFERENCES `support_tickets` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ticket_watchers_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `transactions`
--

DROP TABLE IF EXISTS `transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `transactions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `transaction_id` char(36) NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `wallet_id` bigint(20) unsigned NOT NULL,
  `type` varchar(255) NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'completed',
  `amount_paise` bigint(20) NOT NULL DEFAULT 0,
  `balance_before_paise` bigint(20) NOT NULL DEFAULT 0,
  `balance_after_paise` bigint(20) NOT NULL DEFAULT 0,
  `tds_deducted_paise` bigint(20) NOT NULL DEFAULT 0,
  `description` text NOT NULL,
  `reference_type` varchar(255) DEFAULT NULL,
  `reference_id` bigint(20) unsigned DEFAULT NULL,
  `idempotency_key` varchar(255) DEFAULT NULL COMMENT 'Idempotency key for admin adjustments and manual operations',
  `is_reversed` tinyint(1) NOT NULL DEFAULT 0,
  `reversed_by_transaction_id` bigint(20) unsigned DEFAULT NULL,
  `reversed_at` timestamp NULL DEFAULT NULL,
  `reversal_reason` text DEFAULT NULL,
  `paired_transaction_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `transactions_transaction_id_unique` (`transaction_id`),
  KEY `transactions_wallet_id_foreign` (`wallet_id`),
  KEY `transactions_reference_type_reference_id_index` (`reference_type`,`reference_id`),
  KEY `transactions_user_id_created_at_index` (`user_id`,`created_at`),
  KEY `transactions_is_reversed_index` (`is_reversed`),
  KEY `transactions_paired_transaction_id_index` (`paired_transaction_id`),
  KEY `idx_transactions_idempotency` (`idempotency_key`),
  CONSTRAINT `transactions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `transactions_wallet_id_foreign` FOREIGN KEY (`wallet_id`) REFERENCES `wallets` (`id`) ON DELETE CASCADE,
  CONSTRAINT `check_amount_positive` CHECK (`amount_paise` > 0),
  CONSTRAINT `check_balance_non_negative` CHECK (`balance_after_paise` >= 0),
  CONSTRAINT `check_balance_conservation` CHECK (`type` in ('deposit','bonus_credit','refund','reversal','interest') and `balance_after_paise` = `balance_before_paise` + `amount_paise` or `type` in ('withdrawal','withdrawal_request','investment','tds_deduction','subscription_payment','chargeback') and `balance_after_paise` = `balance_before_paise` - `amount_paise` or `type` = 'admin_adjustment')
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tutorial_steps`
--

DROP TABLE IF EXISTS `tutorial_steps`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tutorial_steps` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tutorial_id` bigint(20) unsigned NOT NULL,
  `step_number` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `target_element` varchar(255) DEFAULT NULL,
  `highlight_style` enum('pulse','glow','border','none') NOT NULL DEFAULT 'pulse',
  `position` enum('top','bottom','left','right','center','modal') NOT NULL DEFAULT 'center',
  `offset_x` int(11) NOT NULL DEFAULT 0,
  `offset_y` int(11) NOT NULL DEFAULT 0,
  `image_url` varchar(255) DEFAULT NULL,
  `video_url` varchar(255) DEFAULT NULL,
  `gif_url` varchar(255) DEFAULT NULL,
  `requires_action` tinyint(1) NOT NULL DEFAULT 0,
  `action_type` varchar(255) DEFAULT NULL,
  `action_target` varchar(255) DEFAULT NULL,
  `action_validation` text DEFAULT NULL,
  `can_skip` tinyint(1) NOT NULL DEFAULT 1,
  `next_button_text` varchar(255) NOT NULL DEFAULT 'Next',
  `back_button_text` varchar(255) NOT NULL DEFAULT 'Back',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tutorial_steps_tutorial_id_step_number_unique` (`tutorial_id`,`step_number`),
  KEY `tutorial_steps_tutorial_id_step_number_index` (`tutorial_id`,`step_number`),
  CONSTRAINT `tutorial_steps_tutorial_id_foreign` FOREIGN KEY (`tutorial_id`) REFERENCES `tutorials` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tutorials`
--

DROP TABLE IF EXISTS `tutorials`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tutorials` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `slug` varchar(255) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `content` longtext DEFAULT NULL,
  `category` varchar(100) NOT NULL,
  `thumbnail_url` varchar(255) DEFAULT NULL,
  `thumbnail` varchar(255) DEFAULT NULL,
  `video_url` varchar(255) DEFAULT NULL,
  `user_role` enum('all','user','company','admin') NOT NULL DEFAULT 'all',
  `difficulty` enum('beginner','intermediate','advanced') NOT NULL DEFAULT 'beginner',
  `estimated_minutes` int(11) DEFAULT NULL,
  `duration_minutes` int(11) DEFAULT NULL,
  `auto_launch` tinyint(1) NOT NULL DEFAULT 0,
  `trigger_page` varchar(255) DEFAULT NULL,
  `trigger_conditions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`trigger_conditions`)),
  `steps` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`steps`)),
  `resources` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`resources`)),
  `tags` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`tags`)),
  `views_count` int(10) unsigned NOT NULL DEFAULT 0,
  `completions_count` int(10) unsigned NOT NULL DEFAULT 0,
  `avg_completion_rate` decimal(5,2) NOT NULL DEFAULT 0.00,
  `likes_count` int(10) unsigned NOT NULL DEFAULT 0,
  `rating` decimal(3,2) DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `is_featured` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `status` enum('draft','published','archived') NOT NULL DEFAULT 'published',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tutorials_slug_unique` (`slug`),
  KEY `tutorials_category_is_active_index` (`category`,`is_active`),
  KEY `tutorials_status_is_active_index` (`status`,`is_active`),
  KEY `tutorials_is_featured_sort_order_index` (`is_featured`,`sort_order`),
  KEY `tutorials_category_index` (`category`),
  KEY `tutorials_sort_order_index` (`sort_order`),
  KEY `tutorials_is_featured_index` (`is_featured`),
  KEY `tutorials_is_active_index` (`is_active`),
  KEY `tutorials_status_index` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `unified_inbox_messages`
--

DROP TABLE IF EXISTS `unified_inbox_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `unified_inbox_messages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `channel_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `sender_identifier` varchar(255) NOT NULL,
  `sender_name` varchar(255) DEFAULT NULL,
  `message_content` text NOT NULL,
  `attachments` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`attachments`)),
  `direction` enum('inbound','outbound') NOT NULL,
  `support_ticket_id` bigint(20) unsigned DEFAULT NULL,
  `ticket_created` tinyint(1) NOT NULL DEFAULT 0,
  `status` enum('pending','processing','processed','failed') NOT NULL DEFAULT 'pending',
  `processing_error` text DEFAULT NULL,
  `replied` tinyint(1) NOT NULL DEFAULT 0,
  `replied_at` timestamp NULL DEFAULT NULL,
  `replied_by_user_id` bigint(20) unsigned DEFAULT NULL,
  `raw_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`raw_data`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `processed_at` timestamp NULL DEFAULT NULL,
  `failed_at` timestamp NULL DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  `message_metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`message_metadata`)),
  `external_message_id` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `unified_inbox_messages_replied_by_user_id_foreign` (`replied_by_user_id`),
  KEY `unified_inbox_messages_channel_id_created_at_index` (`channel_id`,`created_at`),
  KEY `unified_inbox_messages_user_id_channel_id_index` (`user_id`,`channel_id`),
  KEY `unified_inbox_messages_sender_identifier_channel_id_index` (`sender_identifier`,`channel_id`),
  KEY `unified_inbox_messages_status_created_at_index` (`status`,`created_at`),
  KEY `unified_inbox_messages_support_ticket_id_index` (`support_ticket_id`),
  CONSTRAINT `unified_inbox_messages_channel_id_foreign` FOREIGN KEY (`channel_id`) REFERENCES `communication_channels` (`id`) ON DELETE CASCADE,
  CONSTRAINT `unified_inbox_messages_replied_by_user_id_foreign` FOREIGN KEY (`replied_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `unified_inbox_messages_support_ticket_id_foreign` FOREIGN KEY (`support_ticket_id`) REFERENCES `support_tickets` (`id`) ON DELETE CASCADE,
  CONSTRAINT `unified_inbox_messages_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `user_channel_preferences`
--

DROP TABLE IF EXISTS `user_channel_preferences`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_channel_preferences` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `channel_id` bigint(20) unsigned NOT NULL,
  `channel_identifier` varchar(255) NOT NULL,
  `verified` tinyint(1) NOT NULL DEFAULT 0,
  `verified_at` timestamp NULL DEFAULT NULL,
  `verification_token` varchar(255) DEFAULT NULL,
  `notifications_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_channel_identifier_unique` (`user_id`,`channel_id`,`channel_identifier`),
  KEY `user_channel_preferences_channel_id_foreign` (`channel_id`),
  KEY `user_channel_preferences_user_id_index` (`user_id`),
  CONSTRAINT `user_channel_preferences_channel_id_foreign` FOREIGN KEY (`channel_id`) REFERENCES `communication_channels` (`id`) ON DELETE CASCADE,
  CONSTRAINT `user_channel_preferences_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `user_consents`
--

DROP TABLE IF EXISTS `user_consents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_consents` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `consent_type` varchar(255) NOT NULL,
  `consent_version` varchar(255) NOT NULL DEFAULT '1.0',
  `consent_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`consent_data`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `granted_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `revoked_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `consented_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_consents_user_id_consent_type_index` (`user_id`,`consent_type`),
  KEY `user_consents_granted_at_index` (`granted_at`),
  KEY `user_consents_revoked_at_index` (`revoked_at`),
  CONSTRAINT `user_consents_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `user_devices`
--

DROP TABLE IF EXISTS `user_devices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_devices` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `device_token` varchar(255) NOT NULL,
  `device_type` varchar(255) DEFAULT NULL,
  `device_name` varchar(255) DEFAULT NULL,
  `device_model` varchar(255) DEFAULT NULL,
  `os_version` varchar(255) DEFAULT NULL,
  `app_version` varchar(255) DEFAULT NULL,
  `provider` varchar(255) NOT NULL DEFAULT 'fcm',
  `platform` varchar(255) DEFAULT NULL,
  `browser` varchar(255) DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `last_active_at` timestamp NULL DEFAULT NULL,
  `registered_at` timestamp NULL DEFAULT NULL,
  `token_refreshed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_devices_device_token_unique` (`device_token`),
  KEY `user_devices_user_id_index` (`user_id`),
  KEY `user_devices_user_id_is_active_index` (`user_id`,`is_active`),
  KEY `user_devices_device_type_index` (`device_type`),
  KEY `user_devices_provider_index` (`provider`),
  CONSTRAINT `user_devices_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `user_dismissed_suggestions`
--

DROP TABLE IF EXISTS `user_dismissed_suggestions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_dismissed_suggestions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `contextual_suggestion_id` bigint(20) unsigned NOT NULL,
  `display_count` int(11) NOT NULL DEFAULT 1,
  `first_displayed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_displayed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `dismissed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_suggestion_unique` (`user_id`,`contextual_suggestion_id`),
  KEY `user_dismissed_suggestions_contextual_suggestion_id_foreign` (`contextual_suggestion_id`),
  KEY `user_dismissed_suggestions_user_id_index` (`user_id`),
  CONSTRAINT `user_dismissed_suggestions_contextual_suggestion_id_foreign` FOREIGN KEY (`contextual_suggestion_id`) REFERENCES `contextual_suggestions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `user_dismissed_suggestions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `user_help_interactions`
--

DROP TABLE IF EXISTS `user_help_interactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_help_interactions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `session_id` varchar(255) DEFAULT NULL,
  `interaction_type` enum('tooltip_viewed','tooltip_dismissed','tutorial_started','tutorial_completed','tutorial_abandoned','help_searched','article_clicked','video_watched') NOT NULL,
  `element_id` varchar(255) DEFAULT NULL,
  `page_url` varchar(255) NOT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `duration_seconds` int(11) DEFAULT NULL,
  `interacted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_help_interactions_user_id_interaction_type_index` (`user_id`,`interaction_type`),
  KEY `user_help_interactions_interacted_at_index` (`interacted_at`),
  KEY `user_help_interactions_element_id_index` (`element_id`),
  CONSTRAINT `user_help_interactions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `user_investments`
--

DROP TABLE IF EXISTS `user_investments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_investments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `payment_id` bigint(20) unsigned NOT NULL,
  `subscription_id` bigint(20) unsigned NOT NULL,
  `bulk_purchase_id` bigint(20) unsigned NOT NULL,
  `units_allocated` decimal(14,4) NOT NULL,
  `value_allocated` decimal(15,2) DEFAULT NULL,
  `value_allocated_paise` bigint(20) DEFAULT NULL,
  `source` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `shares` int(11) DEFAULT NULL,
  `price_per_share` decimal(10,2) DEFAULT NULL,
  `total_amount` decimal(12,2) DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'active',
  `is_reversed` tinyint(1) NOT NULL DEFAULT 0,
  `reversed_at` timestamp NULL DEFAULT NULL,
  `reversal_reason` varchar(255) DEFAULT NULL,
  `reversal_source` varchar(32) DEFAULT NULL COMMENT 'Explicit reversal source: refund, chargeback, admin_correction, allocation_failure',
  `allocated_at` timestamp NULL DEFAULT NULL,
  `exited_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_investments_product_id_foreign` (`product_id`),
  KEY `user_investments_user_id_product_id_index` (`user_id`,`product_id`),
  KEY `user_investments_payment_id_index` (`payment_id`),
  KEY `user_investments_bulk_purchase_id_index` (`bulk_purchase_id`),
  KEY `user_investments_subscription_id_index` (`subscription_id`),
  KEY `user_investments_value_allocated_paise_index` (`value_allocated_paise`),
  KEY `idx_user_investments_reversal_source` (`reversal_source`),
  CONSTRAINT `user_investments_bulk_purchase_id_foreign` FOREIGN KEY (`bulk_purchase_id`) REFERENCES `bulk_purchases` (`id`),
  CONSTRAINT `user_investments_payment_id_foreign` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `user_investments_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  CONSTRAINT `user_investments_subscription_id_foreign` FOREIGN KEY (`subscription_id`) REFERENCES `subscriptions` (`id`),
  CONSTRAINT `user_investments_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `user_kyc`
--

DROP TABLE IF EXISTS `user_kyc`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_kyc` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `pan_number` varchar(255) DEFAULT NULL,
  `aadhaar_number` varchar(255) DEFAULT NULL,
  `demat_account` varchar(255) DEFAULT NULL,
  `bank_account` varchar(255) DEFAULT NULL,
  `bank_ifsc` varchar(255) DEFAULT NULL,
  `bank_name` varchar(255) DEFAULT NULL,
  `bank_branch` varchar(100) DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `is_aadhaar_verified` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'TRUE if Aadhaar/identity has been verified (via DigiLocker or manual)',
  `aadhaar_verified_at` timestamp NULL DEFAULT NULL COMMENT 'Timestamp when Aadhaar was verified',
  `aadhaar_verification_source` varchar(255) DEFAULT NULL COMMENT 'Source of verification: digilocker, manual, api',
  `is_pan_verified` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'TRUE if PAN has been verified via API or manual check',
  `pan_verified_at` timestamp NULL DEFAULT NULL COMMENT 'Timestamp when PAN was verified',
  `pan_verification_source` varchar(255) DEFAULT NULL COMMENT 'Source of verification: api, manual',
  `is_bank_verified` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'TRUE if bank account has been verified',
  `bank_verified_at` timestamp NULL DEFAULT NULL COMMENT 'Timestamp when bank account was verified',
  `bank_verification_source` varchar(255) DEFAULT NULL COMMENT 'Source of verification: api, manual, penny_drop',
  `is_demat_verified` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'TRUE if demat account has been verified (optional)',
  `demat_verified_at` timestamp NULL DEFAULT NULL COMMENT 'Timestamp when demat was verified',
  `rejection_reason` text DEFAULT NULL,
  `resubmission_instructions` text DEFAULT NULL COMMENT 'Instructions for user when resubmission is required',
  `verified_by` bigint(20) unsigned DEFAULT NULL,
  `verification_checklist` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Admin verification checklist (JSON format)' CHECK (json_valid(`verification_checklist`)),
  `verified_at` timestamp NULL DEFAULT NULL,
  `submitted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_kyc_user_id_foreign` (`user_id`),
  KEY `user_kyc_verified_by_foreign` (`verified_by`),
  KEY `idx_user_kyc_status` (`status`),
  KEY `idx_user_kyc_verification_flags` (`is_aadhaar_verified`,`is_pan_verified`,`is_bank_verified`),
  KEY `user_kyc_status_index` (`status`),
  CONSTRAINT `user_kyc_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `user_kyc_verified_by_foreign` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `user_legal_acceptances`
--

DROP TABLE IF EXISTS `user_legal_acceptances`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_legal_acceptances` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `page_id` bigint(20) unsigned DEFAULT NULL,
  `legal_agreement_id` bigint(20) unsigned DEFAULT NULL,
  `document_type` varchar(255) DEFAULT NULL,
  `page_version` int(11) NOT NULL,
  `accepted_version` varchar(255) DEFAULT NULL,
  `ip_address` varchar(255) NOT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `accepted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_legal_acceptances_page_id_foreign` (`page_id`),
  KEY `user_legal_acceptances_legal_agreement_id_index` (`legal_agreement_id`),
  KEY `user_legal_acceptances_document_type_index` (`document_type`),
  KEY `user_legal_acceptances_user_id_document_type_index` (`user_id`,`document_type`),
  CONSTRAINT `user_legal_acceptances_legal_agreement_id_foreign` FOREIGN KEY (`legal_agreement_id`) REFERENCES `legal_agreements` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `user_notification_preferences`
--

DROP TABLE IF EXISTS `user_notification_preferences`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_notification_preferences` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `notification_type` varchar(255) NOT NULL,
  `email_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `sms_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `push_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `in_app_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `user_id` bigint(20) unsigned NOT NULL,
  `preference_key` varchar(255) NOT NULL,
  `is_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_notification_preferences_user_id_preference_key_unique` (`user_id`,`preference_key`),
  CONSTRAINT `user_notification_preferences_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `user_profiles`
--

DROP TABLE IF EXISTS `user_profiles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_profiles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `first_name` varchar(255) DEFAULT NULL,
  `middle_name` varchar(255) DEFAULT NULL,
  `last_name` varchar(255) DEFAULT NULL,
  `mother_name` varchar(255) DEFAULT NULL,
  `wife_name` varchar(255) DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `gender` varchar(255) DEFAULT NULL,
  `occupation` varchar(255) DEFAULT NULL,
  `education` varchar(255) DEFAULT NULL,
  `social_links` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`social_links`)),
  `address_line_1` text DEFAULT NULL,
  `address_line_2` text DEFAULT NULL,
  `city` varchar(255) DEFAULT NULL,
  `state` varchar(255) DEFAULT NULL,
  `pincode` varchar(255) DEFAULT NULL,
  `country` varchar(255) NOT NULL DEFAULT 'India',
  `avatar_url` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `address` text DEFAULT NULL COMMENT 'User residential / correspondence address',
  `preferences` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'User preference metadata (UI, comms, etc)' CHECK (json_valid(`preferences`)),
  PRIMARY KEY (`id`),
  KEY `user_profiles_user_id_foreign` (`user_id`),
  CONSTRAINT `user_profiles_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `user_profit_shares`
--

DROP TABLE IF EXISTS `user_profit_shares`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_profit_shares` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `profit_share_id` bigint(20) unsigned NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `bonus_transaction_id` bigint(20) unsigned DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_profit_shares_user_id_foreign` (`user_id`),
  KEY `user_profit_shares_profit_share_id_foreign` (`profit_share_id`),
  KEY `user_profit_shares_bonus_transaction_id_foreign` (`bonus_transaction_id`),
  CONSTRAINT `user_profit_shares_bonus_transaction_id_foreign` FOREIGN KEY (`bonus_transaction_id`) REFERENCES `bonus_transactions` (`id`),
  CONSTRAINT `user_profit_shares_profit_share_id_foreign` FOREIGN KEY (`profit_share_id`) REFERENCES `profit_shares` (`id`) ON DELETE CASCADE,
  CONSTRAINT `user_profit_shares_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `user_settings`
--

DROP TABLE IF EXISTS `user_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`settings`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_settings_user_id_index` (`user_id`),
  CONSTRAINT `user_settings_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `user_tutorial_progress`
--

DROP TABLE IF EXISTS `user_tutorial_progress`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_tutorial_progress` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `tutorial_id` bigint(20) unsigned NOT NULL,
  `current_step` int(11) NOT NULL DEFAULT 1,
  `total_steps` int(11) NOT NULL DEFAULT 1,
  `steps_completed` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`steps_completed`)),
  `completed` tinyint(1) NOT NULL DEFAULT 0,
  `completed_at` timestamp NULL DEFAULT NULL,
  `started_at` timestamp NULL DEFAULT NULL,
  `last_activity_at` timestamp NULL DEFAULT NULL,
  `time_spent_seconds` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_tutorial_progress_user_id_tutorial_id_unique` (`user_id`,`tutorial_id`),
  KEY `user_tutorial_progress_user_id_completed_index` (`user_id`,`completed`),
  KEY `user_tutorial_progress_tutorial_id_completed_index` (`tutorial_id`,`completed`),
  KEY `user_tutorial_progress_completed_index` (`completed`),
  KEY `user_tutorial_progress_last_activity_at_index` (`last_activity_at`),
  CONSTRAINT `user_tutorial_progress_tutorial_id_foreign` FOREIGN KEY (`tutorial_id`) REFERENCES `tutorials` (`id`) ON DELETE CASCADE,
  CONSTRAINT `user_tutorial_progress_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `google_id` varchar(255) DEFAULT NULL,
  `username` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `mobile` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `mobile_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `two_factor_secret` text DEFAULT NULL,
  `two_factor_recovery_codes` text DEFAULT NULL,
  `two_factor_confirmed_at` timestamp NULL DEFAULT NULL,
  `referral_code` varchar(255) NOT NULL,
  `referred_by` bigint(20) unsigned DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `risk_score` smallint(5) unsigned NOT NULL DEFAULT 0,
  `is_blocked` tinyint(1) NOT NULL DEFAULT 0,
  `blocked_reason` text DEFAULT NULL,
  `last_risk_update_at` timestamp NULL DEFAULT NULL,
  `suspension_reason` varchar(255) DEFAULT NULL,
  `suspended_at` timestamp NULL DEFAULT NULL,
  `avatar_url` varchar(255) DEFAULT NULL,
  `last_login_at` timestamp NULL DEFAULT NULL,
  `last_login_ip` varchar(255) DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `suspended_by` bigint(20) unsigned DEFAULT NULL,
  `block_reason` varchar(255) DEFAULT NULL,
  `blocked_at` timestamp NULL DEFAULT NULL,
  `blocked_by` bigint(20) unsigned DEFAULT NULL,
  `is_blacklisted` tinyint(1) NOT NULL DEFAULT 0,
  `is_anonymized` tinyint(1) NOT NULL DEFAULT 0,
  `anonymized_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_username_unique` (`username`),
  UNIQUE KEY `users_email_unique` (`email`),
  UNIQUE KEY `users_mobile_unique` (`mobile`),
  UNIQUE KEY `users_referral_code_unique` (`referral_code`),
  UNIQUE KEY `users_google_id_unique` (`google_id`),
  KEY `users_referred_by_foreign` (`referred_by`),
  KEY `users_suspended_by_foreign` (`suspended_by`),
  KEY `users_blocked_by_foreign` (`blocked_by`),
  KEY `users_is_blocked_idx` (`is_blocked`),
  KEY `users_risk_profile_idx` (`is_blocked`,`risk_score`),
  CONSTRAINT `users_blocked_by_foreign` FOREIGN KEY (`blocked_by`) REFERENCES `users` (`id`),
  CONSTRAINT `users_referred_by_foreign` FOREIGN KEY (`referred_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `users_suspended_by_foreign` FOREIGN KEY (`suspended_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `wallets`
--

DROP TABLE IF EXISTS `wallets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wallets` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `balance_paise` bigint(20) NOT NULL DEFAULT 0,
  `locked_balance_paise` bigint(20) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `wallets_user_id_foreign` (`user_id`),
  CONSTRAINT `wallets_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `balance_paise_must_be_positive` CHECK (`balance_paise` >= 0),
  CONSTRAINT `locked_balance_paise_must_be_positive` CHECK (`locked_balance_paise` >= 0)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `webhook_logs`
--

DROP TABLE IF EXISTS `webhook_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `webhook_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `event_type` varchar(255) NOT NULL,
  `webhook_id` varchar(255) DEFAULT NULL,
  `payload` text NOT NULL,
  `headers` text DEFAULT NULL,
  `status` enum('pending','processing','success','failed','max_retries_reached') NOT NULL DEFAULT 'pending',
  `retry_count` int(11) NOT NULL DEFAULT 0,
  `max_retries` int(11) NOT NULL DEFAULT 5,
  `response` text DEFAULT NULL,
  `response_code` int(11) DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  `next_retry_at` timestamp NULL DEFAULT NULL,
  `processed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `webhook_logs_status_next_retry_at_index` (`status`,`next_retry_at`),
  KEY `webhook_logs_created_at_status_index` (`created_at`,`status`),
  KEY `webhook_logs_webhook_id_index` (`webhook_id`),
  KEY `webhook_logs_status_index` (`status`),
  KEY `webhook_logs_next_retry_at_index` (`next_retry_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `webinar_registrations`
--

DROP TABLE IF EXISTS `webinar_registrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `webinar_registrations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `webinar_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `attendee_name` varchar(255) NOT NULL,
  `attendee_email` varchar(255) NOT NULL,
  `attendee_phone` varchar(255) DEFAULT NULL,
  `questions` text DEFAULT NULL,
  `attended` tinyint(1) NOT NULL DEFAULT 0,
  `attended_at` timestamp NULL DEFAULT NULL,
  `status` enum('registered','confirmed','cancelled') NOT NULL DEFAULT 'registered',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `webinar_registrations_webinar_id_attendee_email_unique` (`webinar_id`,`attendee_email`),
  KEY `webinar_registrations_user_id_foreign` (`user_id`),
  CONSTRAINT `webinar_registrations_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `webinar_registrations_webinar_id_foreign` FOREIGN KEY (`webinar_id`) REFERENCES `company_webinars` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `withdrawals`
--

DROP TABLE IF EXISTS `withdrawals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `withdrawals` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `wallet_id` bigint(20) unsigned NOT NULL,
  `amount_paise` bigint(20) NOT NULL DEFAULT 0,
  `fee_paise` bigint(20) NOT NULL DEFAULT 0,
  `fee_breakdown` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`fee_breakdown`)),
  `tds_deducted_paise` bigint(20) NOT NULL DEFAULT 0,
  `net_amount_paise` bigint(20) NOT NULL DEFAULT 0,
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `funds_locked` tinyint(1) NOT NULL DEFAULT 0,
  `funds_locked_at` timestamp NULL DEFAULT NULL,
  `funds_unlocked_at` timestamp NULL DEFAULT NULL,
  `priority` varchar(255) NOT NULL DEFAULT 'normal',
  `bank_details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`bank_details`)),
  `admin_id` bigint(20) unsigned DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `processed_at` timestamp NULL DEFAULT NULL,
  `utr_number` varchar(255) DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `admin_notes` text DEFAULT NULL,
  `idempotency_key` varchar(255) DEFAULT NULL COMMENT 'Unique key to prevent duplicate withdrawal requests',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `bank_account_number` varchar(255) DEFAULT NULL,
  `bank_ifsc` varchar(255) DEFAULT NULL,
  `bank_name` varchar(255) DEFAULT NULL,
  `account_holder_name` varchar(255) DEFAULT NULL,
  `requested_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `withdrawals_wallet_id_foreign` (`wallet_id`),
  KEY `withdrawals_admin_id_foreign` (`admin_id`),
  KEY `idx_withdrawals_idempotency_key` (`idempotency_key`),
  KEY `idx_withdrawal_locked` (`user_id`,`funds_locked`),
  CONSTRAINT `withdrawals_admin_id_foreign` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`),
  CONSTRAINT `withdrawals_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `withdrawals_wallet_id_foreign` FOREIGN KEY (`wallet_id`) REFERENCES `wallets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-02-24  0:52:09
