-- DOMPIS CONS DATABASE BASELINE
-- Captured from the audited MariaDB schema on 2026-09-09.
-- Contains schema plus whitelisted reference masters only. It does not contain
-- users, projects, LOPs, BOQ, evidences, prices, uploads, or other business data.
--
-- Two migrations intentionally remain pending after this baseline is loaded:
--   2026_08_31_120000_add_performance_indexes_for_pm_dashboard
--   2026_09_07_090000_drop_role_enum_from_users_table
-- Do not execute them while MIGRATIONS_FROZEN=true.

/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `approvals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `approvals` (
  `id_approval` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `evidence_id` bigint(20) unsigned NOT NULL,
  `pm_id` bigint(20) unsigned NOT NULL,
  `is_approved` tinyint(1) NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_approval`),
  KEY `approvals_evidence_id_foreign` (`evidence_id`),
  KEY `approvals_pm_id_foreign` (`pm_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `baut_generates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `baut_generates` (
  `id_baut_generate` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `pt2_lop_id` bigint(20) unsigned NOT NULL,
  `pt2_project_id` bigint(20) unsigned DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'draft',
  `field_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`field_values`)),
  `boq_snapshot` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`boq_snapshot`)),
  `opm_slot_count` int(10) unsigned NOT NULL DEFAULT 1,
  `photo_slots` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`photo_slots`)),
  `generated_file_path` varchar(255) DEFAULT NULL,
  `generated_at` timestamp NULL DEFAULT NULL,
  `generated_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_baut_generate`),
  KEY `baut_generates_pt2_lop_id_index` (`pt2_lop_id`),
  KEY `baut_generates_status_index` (`status`),
  KEY `baut_generates_pt2_project_id_foreign` (`pt2_project_id`),
  KEY `baut_generates_generated_by_foreign` (`generated_by`),
  KEY `baut_generates_updated_by_foreign` (`updated_by`),
  CONSTRAINT `baut_generates_generated_by_foreign` FOREIGN KEY (`generated_by`) REFERENCES `users` (`id_user`) ON DELETE SET NULL,
  CONSTRAINT `baut_generates_pt2_lop_id_foreign` FOREIGN KEY (`pt2_lop_id`) REFERENCES `pt2_lops` (`id_pt2_lop`) ON DELETE CASCADE,
  CONSTRAINT `baut_generates_pt2_project_id_foreign` FOREIGN KEY (`pt2_project_id`) REFERENCES `pt2_projects` (`id_pt2_project`) ON DELETE SET NULL,
  CONSTRAINT `baut_generates_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id_user`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `boq_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `boq_items` (
  `id_boq` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `project_id` bigint(20) unsigned NOT NULL,
  `lop_id` bigint(20) unsigned DEFAULT NULL,
  `designator_id` bigint(20) unsigned DEFAULT NULL,
  `designator` varchar(255) DEFAULT NULL,
  `item_name` text NOT NULL,
  `unit` varchar(50) DEFAULT NULL,
  `quantity_plan` int(11) DEFAULT NULL,
  `unit_price` varchar(100) DEFAULT NULL,
  `total_price` varchar(100) DEFAULT NULL,
  `quantity_actual` int(11) DEFAULT NULL,
  `actual_reason` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id_boq`),
  UNIQUE KEY `boq_lop_designator_unique` (`lop_id`,`designator_id`),
  UNIQUE KEY `boq_lop_designator_code_unique` (`lop_id`,`designator`),
  KEY `boq_items_project_id_foreign` (`project_id`),
  KEY `fk_boq_designator` (`designator_id`),
  KEY `idx_boq_items_lop_designator` (`lop_id`,`designator_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `customers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `customers` (
  `id_customer` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `customer_code` varchar(50) NOT NULL,
  `customer_name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_customer`),
  UNIQUE KEY `customers_customer_code_unique` (`customer_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `designator_package_prices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `designator_package_prices` (
  `id_price` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `designator_id` bigint(20) unsigned NOT NULL,
  `package_id` bigint(20) unsigned NOT NULL,
  `price` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_price`),
  UNIQUE KEY `unique_designator_package` (`designator_id`,`package_id`),
  KEY `idx_dpp_designator_package` (`designator_id`,`package_id`,`id_price`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `designators`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `designators` (
  `id_designator` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` bigint(20) unsigned DEFAULT NULL,
  `designator` varchar(100) NOT NULL,
  `item_name` varchar(500) NOT NULL,
  `unit` varchar(50) NOT NULL,
  `type` enum('material','jasa') DEFAULT NULL,
  `pair_code` varchar(100) DEFAULT NULL,
  `progress_category` varchar(50) NOT NULL DEFAULT 'OTHER',
  `requires_finishing_evidence` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_designator`),
  KEY `designators_customer_id_index` (`customer_id`),
  CONSTRAINT `designators_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id_customer`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `dismantles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dismantles` (
  `id_dismantel` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `project_id` bigint(20) NOT NULL,
  `category` varchar(255) NOT NULL,
  `item_name` varchar(255) NOT NULL,
  `qty` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_dismantel`),
  KEY `fk_dismantles_project_id` (`project_id`),
  CONSTRAINT `fk_dismantles_project_id` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id_project`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `dismantles_pt2`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dismantles_pt2` (
  `id_dismantle_pt2` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `pt2_project_id` bigint(20) unsigned NOT NULL,
  `pt2_lop_id` bigint(20) unsigned NOT NULL,
  `category` varchar(255) DEFAULT NULL,
  `item_name` varchar(255) DEFAULT NULL,
  `qty` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_dismantle_pt2`),
  KEY `pt2_project_id` (`pt2_project_id`),
  KEY `pt2_lop_id` (`pt2_lop_id`),
  CONSTRAINT `dismantles_pt2_ibfk_1` FOREIGN KEY (`pt2_project_id`) REFERENCES `pt2_projects` (`id_pt2_project`) ON DELETE CASCADE,
  CONSTRAINT `dismantles_pt2_ibfk_2` FOREIGN KEY (`pt2_lop_id`) REFERENCES `pt2_lops` (`id_pt2_lop`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `evidence_files`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `evidence_files` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `evidence_revision_histories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `evidence_revision_histories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `evidence_id` bigint(20) unsigned NOT NULL,
  `project_id` bigint(20) unsigned NOT NULL,
  `reviewed_by` bigint(20) unsigned DEFAULT NULL,
  `stage` varchar(255) DEFAULT NULL,
  `evidence_type` varchar(255) DEFAULT NULL,
  `review_note` text DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'rejected',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `evidence_revision_histories_evidence_id_foreign` (`evidence_id`),
  KEY `evidence_revision_histories_project_id_foreign` (`project_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `evidences`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `evidences` (
  `id_evidence` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `project_id` bigint(20) unsigned NOT NULL,
  `boq_item_id` bigint(20) unsigned DEFAULT NULL,
  `uploaded_by` bigint(20) unsigned NOT NULL,
  `stage` varchar(50) NOT NULL,
  `evidence_type` varchar(100) DEFAULT NULL,
  `file_path` varchar(255) NOT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `review_note` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_evidence`),
  KEY `evidences_project_id_foreign` (`project_id`),
  KEY `evidences_boq_item_id_foreign` (`boq_item_id`),
  KEY `evidences_uploaded_by_foreign` (`uploaded_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
  UNIQUE KEY `uuid` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `gis_cad_exports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `gis_cad_exports` (
  `id_gis_cad_export` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` char(36) NOT NULL,
  `source_type` varchar(255) NOT NULL,
  `site_survey_id` bigint(20) unsigned DEFAULT NULL,
  `project_id` bigint(20) DEFAULT NULL,
  `template` varchar(255) NOT NULL DEFAULT 'standard_fttx',
  `original_file_name` varchar(255) DEFAULT NULL,
  `uploaded_file_path` varchar(255) DEFAULT NULL,
  `dataset_path` varchar(255) DEFAULT NULL,
  `dxf_path` varchar(255) DEFAULT NULL,
  `bom_path` varchar(255) DEFAULT NULL,
  `disk` varchar(255) NOT NULL DEFAULT 'public',
  `status` varchar(255) NOT NULL DEFAULT 'draft',
  `current_stage` varchar(255) DEFAULT NULL,
  `points_count` int(10) unsigned DEFAULT NULL,
  `polylines_count` int(10) unsigned DEFAULT NULL,
  `utm_zone` varchar(255) DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  `requested_by` bigint(20) unsigned NOT NULL,
  `started_at` timestamp NULL DEFAULT NULL,
  `finished_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_gis_cad_export`),
  UNIQUE KEY `gis_cad_exports_uuid_unique` (`uuid`),
  KEY `gis_cad_exports_source_type_index` (`source_type`),
  KEY `gis_cad_exports_site_survey_id_index` (`site_survey_id`),
  KEY `gis_cad_exports_project_id_index` (`project_id`),
  KEY `gis_cad_exports_status_index` (`status`),
  KEY `gis_cad_exports_requested_by_index` (`requested_by`),
  CONSTRAINT `fk_gis_cad_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id_project`) ON DELETE SET NULL,
  CONSTRAINT `fk_gis_cad_requested_by` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id_user`) ON DELETE CASCADE,
  CONSTRAINT `fk_gis_cad_site_survey` FOREIGN KEY (`site_survey_id`) REFERENCES `site_surveys` (`id_site_surveys`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `import_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `import_logs` (
  `id_importlog` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(255) DEFAULT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `uploaded_by` bigint(20) unsigned DEFAULT NULL,
  `total_rows` int(11) NOT NULL DEFAULT 0,
  `valid_rows` int(11) NOT NULL DEFAULT 0,
  `invalid_rows` int(11) NOT NULL DEFAULT 0,
  `errors` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`errors`)),
  `imported` int(11) NOT NULL DEFAULT 0,
  `project_imported` int(11) NOT NULL DEFAULT 0,
  `project_updated` int(11) NOT NULL DEFAULT 0,
  `lop_imported` int(11) NOT NULL DEFAULT 0,
  `lop_updated` int(11) NOT NULL DEFAULT 0,
  `updated` int(11) NOT NULL DEFAULT 0,
  `skipped` int(11) NOT NULL DEFAULT 0,
  `status` varchar(255) NOT NULL DEFAULT 'success',
  `progress` int(11) NOT NULL DEFAULT 0,
  `message` text DEFAULT NULL,
  `started_at` timestamp NULL DEFAULT NULL,
  `finished_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_importlog`),
  KEY `fk_import_logs_uploaded_by` (`uploaded_by`),
  CONSTRAINT `fk_import_logs_uploaded_by` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id_user`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `import_processes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `import_processes` (
  `id_import` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` char(36) NOT NULL,
  `import_type` varchar(50) NOT NULL,
  `project_type` varchar(30) DEFAULT NULL,
  `customer_id` bigint(20) unsigned DEFAULT NULL,
  `original_file_name` varchar(255) NOT NULL,
  `stored_file_path` varchar(500) NOT NULL,
  `disk` varchar(50) NOT NULL DEFAULT 'local',
  `status` varchar(30) NOT NULL DEFAULT 'queued',
  `current_stage` varchar(100) DEFAULT NULL,
  `progress` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `total_rows` int(10) unsigned NOT NULL DEFAULT 0,
  `processed_rows` int(10) unsigned NOT NULL DEFAULT 0,
  `valid_rows` int(10) unsigned NOT NULL DEFAULT 0,
  `invalid_rows` int(10) unsigned NOT NULL DEFAULT 0,
  `created_count` int(10) unsigned NOT NULL DEFAULT 0,
  `updated_count` int(10) unsigned NOT NULL DEFAULT 0,
  `unchanged_count` int(10) unsigned NOT NULL DEFAULT 0,
  `skipped_count` int(10) unsigned NOT NULL DEFAULT 0,
  `summary` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`summary`)),
  `error_message` text DEFAULT NULL,
  `uploaded_by` bigint(20) unsigned DEFAULT NULL,
  `started_at` timestamp NULL DEFAULT NULL,
  `finished_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_import`),
  UNIQUE KEY `uq_import_uuid` (`uuid`),
  KEY `idx_import_type_status` (`import_type`,`status`),
  KEY `idx_import_status` (`status`),
  KEY `idx_import_uploaded_by` (`uploaded_by`),
  KEY `idx_import_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `import_processes_errors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `import_processes_errors` (
  `id_error` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `import_process_id` bigint(20) unsigned NOT NULL,
  `row_number` int(10) unsigned DEFAULT NULL,
  `pid_sap` varchar(150) DEFAULT NULL,
  `id_ihld` varchar(150) DEFAULT NULL,
  `nama_lop` varchar(255) DEFAULT NULL,
  `error_code` varchar(100) DEFAULT NULL,
  `message` text NOT NULL,
  `row_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`row_data`)),
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_error`),
  KEY `idx_import_error_process` (`import_process_id`),
  KEY `idx_import_error_row` (`import_process_id`,`row_number`),
  KEY `idx_import_error_code` (`error_code`),
  CONSTRAINT `fk_import_process_error` FOREIGN KEY (`import_process_id`) REFERENCES `import_processes` (`id_import`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `kendala_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `kendala_categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `sort_order` int(10) unsigned NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `kendala_categories_name_unique` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `lact_generates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lact_generates` (
  `id_lact_generate` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `pt2_lop_id` bigint(20) unsigned NOT NULL,
  `pt2_project_id` bigint(20) unsigned DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'draft',
  `field_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`field_values`)),
  `boq_snapshot` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`boq_snapshot`)),
  `opm_slot_count` int(10) unsigned NOT NULL DEFAULT 1,
  `photo_slots` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`photo_slots`)),
  `generated_file_path` varchar(255) DEFAULT NULL,
  `generated_at` timestamp NULL DEFAULT NULL,
  `generated_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_lact_generate`),
  KEY `lact_generates_pt2_lop_id_index` (`pt2_lop_id`),
  KEY `lact_generates_status_index` (`status`),
  KEY `lact_generates_pt2_project_id_foreign` (`pt2_project_id`),
  KEY `lact_generates_generated_by_foreign` (`generated_by`),
  KEY `lact_generates_updated_by_foreign` (`updated_by`),
  CONSTRAINT `lact_generates_generated_by_foreign` FOREIGN KEY (`generated_by`) REFERENCES `users` (`id_user`) ON DELETE SET NULL,
  CONSTRAINT `lact_generates_pt2_lop_id_foreign` FOREIGN KEY (`pt2_lop_id`) REFERENCES `pt2_lops` (`id_pt2_lop`) ON DELETE CASCADE,
  CONSTRAINT `lact_generates_pt2_project_id_foreign` FOREIGN KEY (`pt2_project_id`) REFERENCES `pt2_projects` (`id_pt2_project`) ON DELETE SET NULL,
  CONSTRAINT `lact_generates_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id_user`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `lop_golive_submissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lop_golive_submissions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `lop_id` bigint(20) unsigned NOT NULL,
  `capture_valins_path` varchar(255) DEFAULT NULL,
  `abd_valid4_path` varchar(255) DEFAULT NULL,
  `kml_path` varchar(255) DEFAULT NULL,
  `mancore_path` varchar(255) DEFAULT NULL,
  `mancore_input_type` enum('photo','excel') DEFAULT NULL,
  `submitted_by` bigint(20) unsigned DEFAULT NULL,
  `submitted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `lop_golive_submissions_lop_id_unique` (`lop_id`),
  KEY `lop_golive_submissions_submitted_by_foreign` (`submitted_by`),
  CONSTRAINT `lop_golive_submissions_lop_id_foreign` FOREIGN KEY (`lop_id`) REFERENCES `lops` (`id_lop`) ON DELETE CASCADE,
  CONSTRAINT `lop_golive_submissions_submitted_by_foreign` FOREIGN KEY (`submitted_by`) REFERENCES `users` (`id_user`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `lop_golive_verifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lop_golive_verifications` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `lop_id` bigint(20) unsigned NOT NULL,
  `capture_uim_path` varchar(255) DEFAULT NULL,
  `verified_by` bigint(20) unsigned DEFAULT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `lop_golive_verifications_lop_id_unique` (`lop_id`),
  KEY `lop_golive_verifications_verified_by_foreign` (`verified_by`),
  CONSTRAINT `lop_golive_verifications_lop_id_foreign` FOREIGN KEY (`lop_id`) REFERENCES `lops` (`id_lop`) ON DELETE CASCADE,
  CONSTRAINT `lop_golive_verifications_verified_by_foreign` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id_user`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `lop_kronologis`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lop_kronologis` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `lop_id` bigint(20) unsigned NOT NULL,
  `project_id` int(10) unsigned NOT NULL,
  `stage_code` varchar(50) NOT NULL,
  `event_date` date NOT NULL,
  `note` text NOT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `lop_kronologis_lop_id_stage_code_index` (`lop_id`,`stage_code`),
  KEY `lop_kronologis_project_id_index` (`project_id`),
  KEY `lop_kronologis_created_by_foreign` (`created_by`),
  CONSTRAINT `lop_kronologis_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id_user`) ON DELETE SET NULL,
  CONSTRAINT `lop_kronologis_lop_id_foreign` FOREIGN KEY (`lop_id`) REFERENCES `lops` (`id_lop`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `lop_measurement_checks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lop_measurement_checks` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `lop_id` bigint(20) unsigned NOT NULL,
  `item_key` varchar(30) NOT NULL,
  `is_not_applicable` tinyint(1) NOT NULL DEFAULT 0,
  `note` text DEFAULT NULL,
  `evidence_id` bigint(20) unsigned DEFAULT NULL,
  `checked_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `lop_measurement_checks_lop_id_item_key_unique` (`lop_id`,`item_key`),
  KEY `lop_measurement_checks_evidence_id_foreign` (`evidence_id`),
  KEY `lop_measurement_checks_checked_by_foreign` (`checked_by`),
  CONSTRAINT `lop_measurement_checks_checked_by_foreign` FOREIGN KEY (`checked_by`) REFERENCES `users` (`id_user`) ON DELETE SET NULL,
  CONSTRAINT `lop_measurement_checks_evidence_id_foreign` FOREIGN KEY (`evidence_id`) REFERENCES `evidences` (`id_evidence`) ON DELETE SET NULL,
  CONSTRAINT `lop_measurement_checks_lop_id_foreign` FOREIGN KEY (`lop_id`) REFERENCES `lops` (`id_lop`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `lop_stage_histories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lop_stage_histories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `lop_id` bigint(20) unsigned NOT NULL,
  `stage_code` varchar(50) NOT NULL,
  `entered_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `completed_by` bigint(20) unsigned DEFAULT NULL,
  `note` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `lop_stage_histories_lop_id_stage_code_index` (`lop_id`,`stage_code`),
  KEY `lop_stage_histories_stage_code_foreign` (`stage_code`),
  KEY `lop_stage_histories_completed_by_foreign` (`completed_by`),
  CONSTRAINT `lop_stage_histories_completed_by_foreign` FOREIGN KEY (`completed_by`) REFERENCES `users` (`id_user`) ON DELETE SET NULL,
  CONSTRAINT `lop_stage_histories_lop_id_foreign` FOREIGN KEY (`lop_id`) REFERENCES `lops` (`id_lop`) ON DELETE CASCADE,
  CONSTRAINT `lop_stage_histories_stage_code_foreign` FOREIGN KEY (`stage_code`) REFERENCES `project_stages` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `lops`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lops` (
  `id_lop` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `project_id` bigint(20) unsigned DEFAULT NULL,
  `id_ihld` varchar(100) DEFAULT NULL,
  `lop_name` varchar(255) NOT NULL,
  `pid_sap` varchar(100) DEFAULT NULL,
  `program_sap` varchar(150) DEFAULT NULL,
  `tematik` varchar(150) DEFAULT NULL,
  `sto` varchar(50) DEFAULT NULL,
  `branch` varchar(100) DEFAULT NULL,
  `batch` varchar(255) DEFAULT NULL,
  `no_sp` varchar(255) DEFAULT NULL,
  `tgl_sp` date DEFAULT NULL,
  `tgl_toc` date DEFAULT NULL,
  `tahun_order` year(4) DEFAULT NULL,
  `start_tgl` date DEFAULT NULL,
  `wo_smile` varchar(100) DEFAULT NULL,
  `nilai_material` varchar(100) DEFAULT NULL,
  `nilai_jasa` varchar(100) DEFAULT NULL,
  `nilai_total` varchar(100) DEFAULT NULL,
  `odp_8` int(11) DEFAULT 0,
  `odp_16` int(11) DEFAULT 0,
  `total_port` int(11) DEFAULT 0,
  `plan_tiang` varchar(100) DEFAULT NULL,
  `realisasi_tiang` varchar(100) DEFAULT NULL,
  `plan_kabel` varchar(100) DEFAULT NULL,
  `realisasi_kabel` varchar(100) DEFAULT NULL,
  `plan_galian` varchar(100) DEFAULT NULL,
  `real_galian` varchar(100) DEFAULT NULL,
  `status_progress` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'inisiasi',
  `status_progress_before_hold` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sdi_approval_status` varchar(30) NOT NULL DEFAULT 'pending',
  `is_golive` tinyint(1) NOT NULL DEFAULT 0,
  `golive_evidence_path` varchar(255) DEFAULT NULL,
  `golive_at` timestamp NULL DEFAULT NULL,
  `permit_category_id` bigint(20) unsigned DEFAULT NULL,
  `perizinan_completed_at` timestamp NULL DEFAULT NULL,
  `nama_waspang` varchar(150) DEFAULT NULL,
  `nik_waspang` varchar(50) DEFAULT NULL,
  `nama_admin` varchar(150) DEFAULT NULL,
  `nik_admin` varchar(50) DEFAULT NULL,
  `mitra_name` varchar(150) DEFAULT NULL,
  `est_prep` date DEFAULT NULL,
  `est_izin` date DEFAULT NULL,
  `est_delivery` date DEFAULT NULL,
  `est_instalasi` date DEFAULT NULL,
  `est_golive` date DEFAULT NULL,
  `mapping_status` enum('auto_matched','manual_mapped','unmapped') DEFAULT 'unmapped',
  `package_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_lop`),
  KEY `idx_lops_package` (`package_id`),
  KEY `idx_lops_project` (`project_id`),
  KEY `lops_status_progress_foreign` (`status_progress`),
  KEY `lops_status_progress_before_hold_foreign` (`status_progress_before_hold`),
  KEY `lops_sdi_approval_status_idx` (`sdi_approval_status`),
  KEY `lops_is_golive_idx` (`is_golive`),
  KEY `lops_permit_category_id_foreign` (`permit_category_id`),
  CONSTRAINT `lops_permit_category_id_foreign` FOREIGN KEY (`permit_category_id`) REFERENCES `permit_categories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `lops_status_progress_before_hold_foreign` FOREIGN KEY (`status_progress_before_hold`) REFERENCES `project_stages` (`code`) ON DELETE SET NULL,
  CONSTRAINT `lops_status_progress_foreign` FOREIGN KEY (`status_progress`) REFERENCES `project_stages` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `mancores_pt2`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mancores_pt2` (
  `id_mancore_pt2` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `pt2_project_id` bigint(20) unsigned NOT NULL,
  `pt2_lop_id` bigint(20) unsigned NOT NULL,
  `odp_label` varchar(255) DEFAULT NULL,
  `odc_label` varchar(255) DEFAULT NULL,
  `distribusi_core` varchar(255) DEFAULT NULL,
  `feeder_core` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_mancore_pt2`),
  KEY `pt2_project_id` (`pt2_project_id`),
  KEY `pt2_lop_id` (`pt2_lop_id`),
  CONSTRAINT `mancores_pt2_ibfk_1` FOREIGN KEY (`pt2_project_id`) REFERENCES `pt2_projects` (`id_pt2_project`) ON DELETE CASCADE,
  CONSTRAINT `mancores_pt2_ibfk_2` FOREIGN KEY (`pt2_lop_id`) REFERENCES `pt2_lops` (`id_pt2_lop`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `measurements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `measurements` (
  `id_measurement` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `evidence_id` bigint(20) unsigned NOT NULL,
  `type` enum('OPM','OTDR','Galian') NOT NULL,
  `value` varchar(50) DEFAULT NULL,
  `threshold_status` enum('OK','NOK') NOT NULL DEFAULT 'OK',
  PRIMARY KEY (`id_measurement`),
  KEY `measurements_evidence_id_foreign` (`evidence_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notifications` (
  `id_notification` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `project_id` bigint(20) unsigned DEFAULT NULL,
  `type` enum('reject','approved','reminder','ready_ut','new_order','kendala') NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `redirect_url` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_notification`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `packages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `packages` (
  `id_package` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` bigint(20) unsigned DEFAULT NULL,
  `package_code` varchar(50) NOT NULL,
  `package_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_package`),
  KEY `fk_packages_customer_id` (`customer_id`),
  CONSTRAINT `fk_packages_customer_id` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id_customer`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `permit_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `permit_categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `sort_order` int(10) unsigned NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permit_categories_name_unique` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `pro_assign`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pro_assign` (
  `id_proassign` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `project_id` bigint(20) unsigned NOT NULL,
  `waspang_id` bigint(20) DEFAULT NULL,
  `teknisi_id` bigint(20) DEFAULT NULL,
  `assigned_by` bigint(20) DEFAULT NULL,
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_proassign`),
  KEY `idx_pro_assign_project` (`project_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `project_activity_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `project_activity_logs` (
  `id_project_activity` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `project_id` bigint(20) unsigned DEFAULT NULL,
  `lop_id` bigint(20) unsigned DEFAULT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `target_user_id` bigint(20) unsigned DEFAULT NULL,
  `evidence_id` bigint(20) unsigned DEFAULT NULL,
  `activity_type` varchar(100) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `stage` varchar(100) DEFAULT NULL,
  `status_before` varchar(100) DEFAULT NULL,
  `status_after` varchar(100) DEFAULT NULL,
  `meta` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`meta`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_project_activity`),
  KEY `project_activity_logs_project_id_index` (`project_id`),
  KEY `project_activity_logs_lop_id_index` (`lop_id`),
  KEY `project_activity_logs_user_id_index` (`user_id`),
  KEY `project_activity_logs_activity_type_index` (`activity_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `project_issues`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `project_issues` (
  `id_project_issues` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `project_id` bigint(20) unsigned NOT NULL,
  `lop_id` bigint(20) unsigned DEFAULT NULL,
  `stage_code` varchar(50) DEFAULT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `issue_type` varchar(100) DEFAULT NULL,
  `kendala_category_id` bigint(20) unsigned DEFAULT NULL,
  `description` text NOT NULL,
  `photo_path` varchar(255) DEFAULT NULL,
  `photo_paths` longtext DEFAULT NULL CHECK (json_valid(`photo_paths`)),
  `status` enum('kendala','open','resolved') NOT NULL DEFAULT 'kendala',
  `resolution_note` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_project_issues`),
  KEY `project_issues_project_id_index` (`project_id`),
  KEY `project_issues_lop_id_index` (`lop_id`),
  KEY `project_issues_user_id_index` (`user_id`),
  KEY `project_issues_status_index` (`status`),
  KEY `project_issues_kendala_category_id_index` (`kendala_category_id`),
  KEY `project_issues_stage_code_index` (`stage_code`),
  CONSTRAINT `project_issues_kendala_category_id_foreign` FOREIGN KEY (`kendala_category_id`) REFERENCES `kendala_categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `project_stages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `project_stages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL,
  `label` varchar(100) NOT NULL,
  `phase_group` varchar(50) DEFAULT NULL,
  `sequence` int(10) unsigned DEFAULT NULL,
  `color` varchar(30) DEFAULT NULL,
  `is_pause_type` tinyint(1) NOT NULL DEFAULT 0,
  `is_terminal` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `project_stages_code_unique` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `projects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `projects` (
  `id_project` bigint(20) NOT NULL AUTO_INCREMENT,
  `customer_id` bigint(20) unsigned DEFAULT NULL,
  `pid` varchar(100) DEFAULT NULL,
  `pid_sap` varchar(100) DEFAULT NULL,
  `project_name` varchar(255) NOT NULL,
  `program` varchar(150) DEFAULT NULL,
  `branch` varchar(255) DEFAULT NULL,
  `sto` varchar(20) DEFAULT NULL,
  `mitra_name` varchar(100) DEFAULT NULL,
  `jenis_eksekusi` enum('plan','survey','ogp','finish') NOT NULL DEFAULT 'plan',
  `kml_file` varchar(255) DEFAULT NULL,
  `kml_lat` decimal(10,8) DEFAULT NULL,
  `kml_lng` decimal(11,8) DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `location_address` varchar(255) DEFAULT NULL,
  `map_note` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `execution_type` enum('kemitraan','swakelola','turnkey') DEFAULT NULL,
  PRIMARY KEY (`id_project`),
  KEY `projects_customer_id_index` (`customer_id`),
  CONSTRAINT `projects_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id_customer`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `pt2_assignments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pt2_assignments` (
  `id_pt2_assignment` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `pt2_project_id` bigint(20) unsigned NOT NULL,
  `pt2_lop_id` bigint(20) unsigned NOT NULL,
  `teknisi_id` bigint(20) unsigned DEFAULT NULL,
  `assigned_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_pt2_assignment`),
  KEY `pt2_project_id` (`pt2_project_id`),
  KEY `idx_pt2_assign_lop` (`pt2_lop_id`),
  CONSTRAINT `pt2_assignments_ibfk_1` FOREIGN KEY (`pt2_project_id`) REFERENCES `pt2_projects` (`id_pt2_project`) ON DELETE CASCADE,
  CONSTRAINT `pt2_assignments_ibfk_2` FOREIGN KEY (`pt2_lop_id`) REFERENCES `pt2_lops` (`id_pt2_lop`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `pt2_boq_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pt2_boq_items` (
  `id_pt2_boq` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `pt2_project_id` bigint(20) unsigned NOT NULL,
  `pt2_lop_id` bigint(20) unsigned NOT NULL,
  `designator_id` bigint(20) unsigned DEFAULT NULL,
  `designator` varchar(100) DEFAULT NULL,
  `item_name` text DEFAULT NULL,
  `unit` varchar(50) DEFAULT NULL,
  `quantity_plan` double(10,2) DEFAULT 0.00,
  `quantity_actual` double(10,2) DEFAULT 0.00,
  `unit_price` double(20,2) DEFAULT 0.00,
  `total_price` double(20,2) DEFAULT 0.00,
  `actual_reason` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_pt2_boq`),
  UNIQUE KEY `pt2_boq_lop_designator_unique` (`pt2_lop_id`,`designator_id`),
  UNIQUE KEY `pt2_boq_lop_designator_code_unique` (`pt2_lop_id`,`designator`),
  KEY `pt2_project_id` (`pt2_project_id`),
  KEY `idx_pt2_boq_lop` (`pt2_lop_id`),
  CONSTRAINT `pt2_boq_items_ibfk_1` FOREIGN KEY (`pt2_project_id`) REFERENCES `pt2_projects` (`id_pt2_project`) ON DELETE CASCADE,
  CONSTRAINT `pt2_boq_items_ibfk_2` FOREIGN KEY (`pt2_lop_id`) REFERENCES `pt2_lops` (`id_pt2_lop`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `pt2_evidences`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pt2_evidences` (
  `id_pt2_evidence` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `pt2_project_id` bigint(20) unsigned NOT NULL,
  `pt2_lop_id` bigint(20) unsigned NOT NULL,
  `pt2_boq_id` bigint(20) unsigned DEFAULT NULL,
  `uploaded_by` bigint(20) unsigned DEFAULT NULL,
  `stage` varchar(50) NOT NULL,
  `evidence_type` varchar(100) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `latitude` varchar(50) DEFAULT NULL,
  `longitude` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` varchar(50) DEFAULT 'pending',
  `review_note` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_pt2_evidence`),
  KEY `pt2_project_id` (`pt2_project_id`),
  KEY `pt2_lop_id` (`pt2_lop_id`),
  KEY `pt2_boq_id` (`pt2_boq_id`),
  CONSTRAINT `pt2_evidences_ibfk_1` FOREIGN KEY (`pt2_project_id`) REFERENCES `pt2_projects` (`id_pt2_project`) ON DELETE CASCADE,
  CONSTRAINT `pt2_evidences_ibfk_2` FOREIGN KEY (`pt2_lop_id`) REFERENCES `pt2_lops` (`id_pt2_lop`) ON DELETE CASCADE,
  CONSTRAINT `pt2_evidences_ibfk_3` FOREIGN KEY (`pt2_boq_id`) REFERENCES `pt2_boq_items` (`id_pt2_boq`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `pt2_lops`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pt2_lops` (
  `id_pt2_lop` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `pt2_project_id` bigint(20) unsigned NOT NULL,
  `id_ihld` varchar(100) DEFAULT NULL,
  `lop_name` varchar(255) DEFAULT NULL,
  `pid_sap` varchar(100) DEFAULT NULL,
  `tematik` varchar(150) DEFAULT NULL,
  `sto` varchar(50) DEFAULT NULL,
  `branch` varchar(100) DEFAULT NULL,
  `batch` varchar(100) DEFAULT NULL,
  `no_sp` varchar(100) DEFAULT NULL,
  `tgl_sp` date DEFAULT NULL,
  `tgl_toc` date DEFAULT NULL,
  `mitra_name` varchar(150) DEFAULT NULL,
  `status_progress` varchar(50) DEFAULT 'preparation',
  `package_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_golive` tinyint(1) NOT NULL DEFAULT 0,
  `sdi_approval_status` varchar(50) DEFAULT NULL,
  `golive_evidence_path` varchar(255) DEFAULT NULL,
  `golive_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_pt2_lop`),
  UNIQUE KEY `uq_pt2_project_ihld` (`pt2_project_id`,`id_ihld`),
  KEY `idx_pt2_lops_project` (`pt2_project_id`),
  KEY `idx_pt2_lops_branch` (`branch`),
  CONSTRAINT `pt2_lops_ibfk_1` FOREIGN KEY (`pt2_project_id`) REFERENCES `pt2_projects` (`id_pt2_project`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `pt2_projects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pt2_projects` (
  `id_pt2_project` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` bigint(20) unsigned DEFAULT 1,
  `pid` varchar(100) DEFAULT NULL,
  `pid_sap` varchar(100) DEFAULT NULL,
  `project_name` varchar(255) DEFAULT NULL,
  `program` varchar(150) DEFAULT NULL,
  `branch` varchar(100) DEFAULT NULL,
  `sto` varchar(50) DEFAULT NULL,
  `mitra_name` varchar(150) DEFAULT NULL,
  `execution_type` varchar(50) DEFAULT 'kemitraan',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_pt2_project`),
  KEY `idx_pt2_projects_pid_sap` (`customer_id`,`pid_sap`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `role_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `role_permissions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `role_id` bigint(20) unsigned NOT NULL,
  `permission` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `role_permissions_role_id_permission_unique` (`role_id`,`permission`),
  CONSTRAINT `role_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id_roles`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `roles` (
  `id_roles` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_roles`),
  UNIQUE KEY `roles_code_unique` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `site_survey_points`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `site_survey_points` (
  `id_site_survey_points` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `site_survey_id` bigint(20) unsigned NOT NULL,
  `type` varchar(255) NOT NULL,
  `catuan_type` varchar(255) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `latitude` decimal(10,7) NOT NULL,
  `longitude` decimal(10,7) NOT NULL,
  `photo_path` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `order_index` int(11) NOT NULL DEFAULT 0,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_site_survey_points`),
  KEY `site_survey_points_site_survey_id_type_index` (`site_survey_id`,`type`),
  CONSTRAINT `site_survey_points_site_survey_id_foreign` FOREIGN KEY (`site_survey_id`) REFERENCES `site_surveys` (`id_site_surveys`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `site_survey_routes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `site_survey_routes` (
  `id_site_survey_routes` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `site_survey_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL DEFAULT 'Rute Kabel',
  `path` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`path`)),
  `distance_meters` decimal(12,2) DEFAULT NULL,
  `order_index` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_site_survey_routes`),
  KEY `site_survey_routes_site_survey_id_index` (`site_survey_id`),
  CONSTRAINT `site_survey_routes_site_survey_id_foreign` FOREIGN KEY (`site_survey_id`) REFERENCES `site_surveys` (`id_site_surveys`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `site_surveys`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `site_surveys` (
  `id_site_surveys` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `project_id` bigint(20) DEFAULT NULL,
  `project_name` varchar(255) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `surveyor_id` bigint(20) unsigned NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'draft',
  `notes` text DEFAULT NULL,
  `ending_site_lat` decimal(10,7) DEFAULT NULL,
  `ending_site_lng` decimal(10,7) DEFAULT NULL,
  `ending_site_name` varchar(255) DEFAULT NULL,
  `kml_path` varchar(255) DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_site_surveys`),
  KEY `site_surveys_project_id_index` (`project_id`),
  KEY `site_surveys_surveyor_id_index` (`surveyor_id`),
  KEY `site_surveys_status_index` (`status`),
  CONSTRAINT `site_surveys_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id_project`) ON DELETE SET NULL,
  CONSTRAINT `site_surveys_surveyor_id_foreign` FOREIGN KEY (`surveyor_id`) REFERENCES `users` (`id_user`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `surveys_pt2`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `surveys_pt2` (
  `id_survey_pt2` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `pt2_project_id` bigint(20) unsigned NOT NULL,
  `pt2_lop_id` bigint(20) unsigned NOT NULL,
  `has_kendala` tinyint(1) DEFAULT 0,
  `kendala_note` text DEFAULT NULL,
  `pm_approval_status` enum('pending','approved','rejected') DEFAULT 'pending',
  `mode` varchar(255) DEFAULT NULL,
  `sub_mode_a` varchar(255) DEFAULT NULL,
  `fixed_jasa_price` decimal(15,2) DEFAULT NULL,
  `odp_name` varchar(255) DEFAULT NULL,
  `distribusi` varchar(255) DEFAULT NULL,
  `core_ex` varchar(255) DEFAULT NULL,
  `power_out` varchar(255) DEFAULT NULL,
  `power_in_feeder` varchar(255) DEFAULT NULL,
  `tipe_kabel` varchar(255) DEFAULT NULL,
  `kesimpulan` text DEFAULT NULL,
  `detail_data` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_survey_pt2`),
  KEY `pt2_project_id` (`pt2_project_id`),
  KEY `pt2_lop_id` (`pt2_lop_id`),
  CONSTRAINT `surveys_pt2_ibfk_1` FOREIGN KEY (`pt2_project_id`) REFERENCES `pt2_projects` (`id_pt2_project`) ON DELETE CASCADE,
  CONSTRAINT `surveys_pt2_ibfk_2` FOREIGN KEY (`pt2_lop_id`) REFERENCES `pt2_lops` (`id_pt2_lop`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `telegram_webhook_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `telegram_webhook_events` (
  `id_tele_webhook` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `event_type` varchar(255) NOT NULL,
  `recipient_type` varchar(255) NOT NULL,
  `recipient_user_id` bigint(20) unsigned DEFAULT NULL,
  `recipient_role` varchar(255) DEFAULT NULL,
  `project_id` bigint(20) unsigned DEFAULT NULL,
  `lop_id` bigint(20) unsigned DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`payload`)),
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `delivered_at` timestamp NULL DEFAULT NULL,
  `pushed_at` timestamp NULL DEFAULT NULL,
  `push_attempts` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `push_error` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_tele_webhook`),
  KEY `telegram_webhook_events_status_created_at_index` (`status`,`created_at`),
  KEY `telegram_webhook_events_event_type_index` (`event_type`),
  KEY `telegram_webhook_events_recipient_user_id_index` (`recipient_user_id`),
  KEY `telegram_webhook_events_recipient_role_index` (`recipient_role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id_user` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `nik` varchar(255) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','waspang','pm','teknisi','sdi','sdi_surveyor','superadmin','tif','super_tif') DEFAULT NULL,
  `role_id` bigint(20) unsigned DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `last_login_at` timestamp NULL DEFAULT NULL,
  `last_activity_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id_user`),
  KEY `users_role_id_foreign` (`role_id`),
  CONSTRAINT `users_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id_roles`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1,'0001_01_01_000000_create_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (2,'0001_01_01_000001_create_cache_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (3,'0001_01_01_000002_create_jobs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (4,'2026_04_30_111212_create_projects_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (5,'2026_04_30_111917_create_boq_items_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (6,'2026_04_30_112057_create_project_assignments_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (7,'2026_04_30_112207_create_evidences_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (8,'2026_04_30_113123_create_measurements_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (9,'2026_04_30_113131_create_approvals_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (10,'2026_05_12_043147_add_designator_to_boq_items_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (11,'2026_05_19_095335_create_evidence_files_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (12,'2026_05_26_090717_create_notifications_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (13,'2026_05_28_061642_add_redirect_url_to_notifications_table',3);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (14,'2026_05_29_042238_add_updated_at_to_evidences_table',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (15,'2026_05_31_091544_create_evidence_revision_histories_table',5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (16,'2026_06_03_092613_add_status_to_users_table',6);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (17,'2026_06_06_132000_add_nik_to_users_table',7);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (18,'2026_06_06_134646_add_kml_file_to_projects_table',8);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (19,'2026_06_10_092337_add_sp_fields_to_lops_table',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (20,'2026_06_20_112617_add_photo_path_to_project_issues_table',10);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (21,'2026_07_01_000000_create_customers_table',10);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (22,'2026_07_01_010000_add_customer_id_to_projects_table',11);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (23,'2026_07_01_020000_add_customer_id_to_designators_table',12);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (24,'2026_09_06_070000_create_roles_table',13);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (25,'2026_09_06_070100_seed_roles_table',14);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (26,'2026_09_06_070300_create_role_permissions_table',15);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (27,'2026_09_06_070200_add_role_id_to_users_table',16);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (28,'2026_09_08_080000_add_login_tracking_to_users_table',17);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (29,'2026_09_08_080100_seed_officer_role',17);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (30,'2026_09_08_090000_create_project_stages_table',18);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (31,'2026_09_08_090100_create_kendala_categories_table',19);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (32,'2026_09_08_090200_create_permit_categories_table',20);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (33,'2026_09_08_090400_add_kendala_category_and_photos_to_project_issues_table',21);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (34,'2026_09_08_090500_create_lop_measurement_checks_table',22);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (35,'2026_09_08_090600_create_lop_stage_histories_table',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (36,'2026_09_08_090700_create_lop_golive_submissions_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (37,'2026_09_08_090800_create_lop_golive_verifications_table',25);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (38,'2026_09_08_090900_drop_pt2_mancores_and_pt2_surveys_tables',26);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (39,'2026_09_08_090300_convert_lops_status_progress_to_project_stages',27);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (40,'2026_09_08_091000_create_lop_kronologis_table',28);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (41,'2026_09_08_091100_add_persiapan_baru_columns_to_lops_and_project_issues',29);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (42,'2026_09_08_091200_widen_evidences_stage_column',30);
-- These migrations were missing from the live migration ledger even though
-- their schema changes are already present in this baseline.
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (43,'2026_08_20_100000_create_site_surveys_table',31);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (44,'2026_08_20_100001_create_site_survey_points_table',31);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (45,'2026_08_20_100002_create_site_survey_routes_table',31);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (46,'2026_08_22_000000_create_baut_generates_table',31);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (47,'2026_08_24_000000_create_lact_generates_table',31);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (48,'2026_08_25_000000_create_telegram_webhook_events_table',31);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (49,'2026_08_27_010000_create_gis_cad_exports_table',31);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (50,'2026_09_01_090000_add_superadmin_and_tif_roles_to_users_table',31);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (51,'2026_09_02_090000_add_super_tif_role_to_users_table',31);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (52,'2026_09_04_063000_add_push_tracking_to_telegram_webhook_events_table',31);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (53,'2026_09_10_120000_consolidate_regular_lop_statuses',32);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (54,'2026_09_10_121000_drop_legacy_project_status_columns',33);

INSERT INTO `roles` (`id_roles`, `code`, `name`, `description`, `created_at`, `updated_at`) VALUES (1,'admin','Approval','Approval project & eviden regular (PT 3), akses dashboard Admin penuh.','2026-09-05 10:35:47','2026-09-05 10:35:47');
INSERT INTO `roles` (`id_roles`, `code`, `name`, `description`, `created_at`, `updated_at`) VALUES (2,'superadmin','Super Admin','Seperti Admin, ditambah akses User Management. Tidak melihat menu Inbox.','2026-09-05 10:35:47','2026-09-05 10:35:47');
INSERT INTO `roles` (`id_roles`, `code`, `name`, `description`, `created_at`, `updated_at`) VALUES (3,'waspang','Inputer','Input progress & upload eviden project regular (PT 3) di lapangan.','2026-09-05 10:35:47','2026-09-05 10:35:47');
INSERT INTO `roles` (`id_roles`, `code`, `name`, `description`, `created_at`, `updated_at`) VALUES (4,'pm','PM','Project Manager — memantau & mengelola project yang di-assign ke tim.','2026-09-05 10:35:47','2026-09-05 10:35:47');
INSERT INTO `roles` (`id_roles`, `code`, `name`, `description`, `created_at`, `updated_at`) VALUES (5,'tif','TIF','Sama seperti PM, tanpa akses ke Program Konstruksi Eksternal.','2026-09-05 10:35:47','2026-09-05 10:35:47');
INSERT INTO `roles` (`id_roles`, `code`, `name`, `description`, `created_at`, `updated_at`) VALUES (6,'super_tif','Super TIF','Seperti Admin, tanpa Program/Project Konstruksi Eksternal (EKSBIS) & tanpa User Management.','2026-09-05 10:35:47','2026-09-05 10:35:47');
INSERT INTO `roles` (`id_roles`, `code`, `name`, `description`, `created_at`, `updated_at`) VALUES (7,'teknisi','Inputer PT2','Input progress & upload eviden project PT2.','2026-09-05 10:35:47','2026-09-05 10:35:47');
INSERT INTO `roles` (`id_roles`, `code`, `name`, `description`, `created_at`, `updated_at`) VALUES (8,'sdi','SDI','Approval & monitoring hasil site survey.','2026-09-05 10:35:47','2026-09-05 10:35:47');
INSERT INTO `roles` (`id_roles`, `code`, `name`, `description`, `created_at`, `updated_at`) VALUES (9,'sdi_surveyor','SDI Surveyor','Input hasil survey lapangan (fitur ini sekarang menyatu ke role Waspang; dipertahankan untuk akun lama).','2026-09-05 10:35:47','2026-09-05 10:35:47');
INSERT INTO `roles` (`id_roles`, `code`, `name`, `description`, `created_at`, `updated_at`) VALUES (10,'officer','Officer','Menu sama seperti Admin (kecuali Master Designator, Bulk Import Data, Approval Eviden), ditambah akses User Management terbatas (tidak bisa ubah username/password user lain, tanpa Log Activity).','2026-09-05 11:45:41','2026-09-05 11:45:41');
INSERT INTO `customers` (`id_customer`, `customer_code`, `customer_name`, `description`, `is_active`, `created_at`, `updated_at`) VALUES (1,'TIF','Telkom Infrastructure',NULL,1,'2026-07-01 06:27:57','2026-07-01 06:27:57');
INSERT INTO `customers` (`id_customer`, `customer_code`, `customer_name`, `description`, `is_active`, `created_at`, `updated_at`) VALUES (2,'MITRATEL','Mitratel',NULL,1,'2026-07-01 06:27:57','2026-07-01 06:27:57');
INSERT INTO `customers` (`id_customer`, `customer_code`, `customer_name`, `description`, `is_active`, `created_at`, `updated_at`) VALUES (3,'MYREP','MyRepublic',NULL,1,'2026-07-01 06:27:57','2026-07-01 06:27:57');
INSERT INTO `customers` (`id_customer`, `customer_code`, `customer_name`, `description`, `is_active`, `created_at`, `updated_at`) VALUES (4,'ASIANET','AsiaNet',NULL,1,'2026-07-01 06:27:57','2026-07-01 06:27:57');
INSERT INTO `customers` (`id_customer`, `customer_code`, `customer_name`, `description`, `is_active`, `created_at`, `updated_at`) VALUES (5,'LA','Lintas Arta',NULL,1,'2026-07-20 08:23:09','2026-07-20 08:23:09');
INSERT INTO `customers` (`id_customer`, `customer_code`, `customer_name`, `description`, `is_active`, `created_at`, `updated_at`) VALUES (6,'JLM','Jala Lintas Media',NULL,1,'2026-07-20 08:23:09','2026-07-20 08:23:09');
INSERT INTO `project_stages` (`id`, `code`, `label`, `phase_group`, `sequence`, `color`, `is_pause_type`, `is_terminal`, `is_active`, `description`, `created_at`, `updated_at`) VALUES (1,'inisiasi','Inisiasi','persiapan',1,'slate',0,0,1,NULL,'2026-09-08 02:53:24','2026-09-08 02:53:24');
INSERT INTO `project_stages` (`id`, `code`, `label`, `phase_group`, `sequence`, `color`, `is_pause_type`, `is_terminal`, `is_active`, `description`, `created_at`, `updated_at`) VALUES (2,'survey','Survey','persiapan',2,'slate',0,0,1,NULL,'2026-09-08 02:53:24','2026-09-08 02:53:24');
INSERT INTO `project_stages` (`id`, `code`, `label`, `phase_group`, `sequence`, `color`, `is_pause_type`, `is_terminal`, `is_active`, `description`, `created_at`, `updated_at`) VALUES (3,'drm','Proses DRM','persiapan',3,'slate',0,0,1,NULL,'2026-09-08 02:53:24','2026-09-08 02:53:24');
INSERT INTO `project_stages` (`id`, `code`, `label`, `phase_group`, `sequence`, `color`, `is_pause_type`, `is_terminal`, `is_active`, `description`, `created_at`, `updated_at`) VALUES (4,'perizinan','Perizinan','persiapan',4,'amber',0,0,1,NULL,'2026-09-08 02:53:24','2026-09-08 02:53:24');
INSERT INTO `project_stages` (`id`, `code`, `label`, `phase_group`, `sequence`, `color`, `is_pause_type`, `is_terminal`, `is_active`, `description`, `created_at`, `updated_at`) VALUES (5,'material_delivery','Material Delivery','persiapan',5,'slate',0,0,1,NULL,'2026-09-08 02:53:24','2026-09-08 02:53:24');
INSERT INTO `project_stages` (`id`, `code`, `label`, `phase_group`, `sequence`, `color`, `is_pause_type`, `is_terminal`, `is_active`, `description`, `created_at`, `updated_at`) VALUES (6,'persiapan_instalasi','Persiapan Instalasi','persiapan_instalasi',6,'blue',0,0,1,NULL,'2026-09-08 02:53:24','2026-09-08 02:53:24');
INSERT INTO `project_stages` (`id`, `code`, `label`, `phase_group`, `sequence`, `color`, `is_pause_type`, `is_terminal`, `is_active`, `description`, `created_at`, `updated_at`) VALUES (7,'instalasi','Instalasi','instalasi',7,'blue',0,0,1,NULL,'2026-09-08 02:53:24','2026-09-08 02:53:24');
INSERT INTO `project_stages` (`id`, `code`, `label`, `phase_group`, `sequence`, `color`, `is_pause_type`, `is_terminal`, `is_active`, `description`, `created_at`, `updated_at`) VALUES (8,'pengukuran','Pengukuran','pengukuran',8,'indigo',0,0,1,NULL,'2026-09-08 02:53:24','2026-09-08 02:53:24');
INSERT INTO `project_stages` (`id`, `code`, `label`, `phase_group`, `sequence`, `color`, `is_pause_type`, `is_terminal`, `is_active`, `description`, `created_at`, `updated_at`) VALUES (9,'finishing','Finishing','finishing',9,'yellow',0,0,1,NULL,'2026-09-08 02:53:24','2026-09-08 06:15:54');
INSERT INTO `project_stages` (`id`, `code`, `label`, `phase_group`, `sequence`, `color`, `is_pause_type`, `is_terminal`, `is_active`, `description`, `created_at`, `updated_at`) VALUES (10,'fi_ogp_golive','FI-OGP Golive','golive',10,'purple',0,0,1,NULL,'2026-09-08 02:53:24','2026-09-08 02:53:24');
INSERT INTO `project_stages` (`id`, `code`, `label`, `phase_group`, `sequence`, `color`, `is_pause_type`, `is_terminal`, `is_active`, `description`, `created_at`, `updated_at`) VALUES (11,'golive','Golive','golive',11,'green',0,0,1,NULL,'2026-09-08 02:53:24','2026-09-08 02:53:24');
INSERT INTO `project_stages` (`id`, `code`, `label`, `phase_group`, `sequence`, `color`, `is_pause_type`, `is_terminal`, `is_active`, `description`, `created_at`, `updated_at`) VALUES (12,'hold','Hold','pause',NULL,'orange',1,0,1,NULL,'2026-09-08 02:53:24','2026-09-08 02:53:24');
INSERT INTO `project_stages` (`id`, `code`, `label`, `phase_group`, `sequence`, `color`, `is_pause_type`, `is_terminal`, `is_active`, `description`, `created_at`, `updated_at`) VALUES (13,'drop','Drop','pause',NULL,'red',0,1,1,NULL,'2026-09-08 02:53:24','2026-09-08 02:53:24');
INSERT INTO `kendala_categories` (`id`, `name`, `sort_order`, `is_active`, `created_at`, `updated_at`) VALUES (1,'SEWA RECURRING',1,1,'2026-09-08 02:53:24','2026-09-08 02:53:24');
INSERT INTO `kendala_categories` (`id`, `name`, `sort_order`, `is_active`, `created_at`, `updated_at`) VALUES (2,'ODC FULL',2,1,'2026-09-08 02:53:24','2026-09-08 02:53:24');
INSERT INTO `kendala_categories` (`id`, `name`, `sort_order`, `is_active`, `created_at`, `updated_at`) VALUES (3,'TERCOVER ALPRO EKSISTING',3,1,'2026-09-08 02:53:24','2026-09-08 02:53:24');
INSERT INTO `kendala_categories` (`id`, `name`, `sort_order`, `is_active`, `created_at`, `updated_at`) VALUES (4,'CLUSTER BELUM SIAP',4,1,'2026-09-08 02:53:24','2026-09-08 02:53:24');
INSERT INTO `kendala_categories` (`id`, `name`, `sort_order`, `is_active`, `created_at`, `updated_at`) VALUES (5,'MATERIAL',5,1,'2026-09-08 02:53:24','2026-09-08 02:53:24');
INSERT INTO `kendala_categories` (`id`, `name`, `sort_order`, `is_active`, `created_at`, `updated_at`) VALUES (6,'PERIZINAN',6,1,'2026-09-08 02:53:24','2026-09-08 02:53:24');
INSERT INTO `kendala_categories` (`id`, `name`, `sort_order`, `is_active`, `created_at`, `updated_at`) VALUES (7,'CORE FEEDER HABIS',7,1,'2026-09-08 02:53:24','2026-09-08 02:53:24');
INSERT INTO `kendala_categories` (`id`, `name`, `sort_order`, `is_active`, `created_at`, `updated_at`) VALUES (8,'CORE DISTRIBUSI HABIS',8,1,'2026-09-08 02:53:24','2026-09-08 02:53:24');
INSERT INTO `kendala_categories` (`id`, `name`, `sort_order`, `is_active`, `created_at`, `updated_at`) VALUES (9,'MINI OLT PENUH',9,1,'2026-09-08 02:53:24','2026-09-08 02:53:24');
INSERT INTO `kendala_categories` (`id`, `name`, `sort_order`, `is_active`, `created_at`, `updated_at`) VALUES (10,'MENUNGGU PEKERJAAN LAIN',10,1,'2026-09-08 02:53:24','2026-09-08 02:53:24');
INSERT INTO `kendala_categories` (`id`, `name`, `sort_order`, `is_active`, `created_at`, `updated_at`) VALUES (11,'WAITING MINI OLT CSF',11,1,'2026-09-08 02:53:24','2026-09-08 02:53:24');
INSERT INTO `kendala_categories` (`id`, `name`, `sort_order`, `is_active`, `created_at`, `updated_at`) VALUES (12,'BENCANA ALAM',12,1,'2026-09-08 02:53:24','2026-09-08 02:53:24');
INSERT INTO `kendala_categories` (`id`, `name`, `sort_order`, `is_active`, `created_at`, `updated_at`) VALUES (13,'PELANGGAN BATAL',13,1,'2026-09-08 02:53:24','2026-09-08 02:53:24');
INSERT INTO `kendala_categories` (`id`, `name`, `sort_order`, `is_active`, `created_at`, `updated_at`) VALUES (14,'DUPLIKAT ORDER',14,1,'2026-09-08 02:53:24','2026-09-08 02:53:24');
INSERT INTO `kendala_categories` (`id`, `name`, `sort_order`, `is_active`, `created_at`, `updated_at`) VALUES (15,'DEMAND RENDAH',15,1,'2026-09-08 02:53:24','2026-09-08 02:53:24');
INSERT INTO `kendala_categories` (`id`, `name`, `sort_order`, `is_active`, `created_at`, `updated_at`) VALUES (16,'NEED REPAIR CORE EXISTING',16,1,'2026-09-08 02:53:24','2026-09-08 02:53:24');
INSERT INTO `kendala_categories` (`id`, `name`, `sort_order`, `is_active`, `created_at`, `updated_at`) VALUES (17,'REDESIGN >10%',17,1,'2026-09-08 02:53:24','2026-09-08 02:53:24');
INSERT INTO `kendala_categories` (`id`, `name`, `sort_order`, `is_active`, `created_at`, `updated_at`) VALUES (18,'PENOLAKAN PERIZINAN',18,1,'2026-09-08 02:53:24','2026-09-08 02:53:24');
INSERT INTO `kendala_categories` (`id`, `name`, `sort_order`, `is_active`, `created_at`, `updated_at`) VALUES (19,'PERMIT SITE',19,1,'2026-09-08 02:53:24','2026-09-08 02:53:24');
INSERT INTO `kendala_categories` (`id`, `name`, `sort_order`, `is_active`, `created_at`, `updated_at`) VALUES (20,'KENDALA BUDGET',20,1,'2026-09-08 02:53:24','2026-09-08 02:53:24');
INSERT INTO `kendala_categories` (`id`, `name`, `sort_order`, `is_active`, `created_at`, `updated_at`) VALUES (21,'CRQ / CRA',21,1,'2026-09-08 02:53:24','2026-09-08 02:53:24');
INSERT INTO `kendala_categories` (`id`, `name`, `sort_order`, `is_active`, `created_at`, `updated_at`) VALUES (22,'READINESS SITE',22,1,'2026-09-08 02:53:24','2026-09-08 02:53:24');
INSERT INTO `kendala_categories` (`id`, `name`, `sort_order`, `is_active`, `created_at`, `updated_at`) VALUES (23,'COMMCASE',23,1,'2026-09-08 02:53:24','2026-09-08 02:53:24');
INSERT INTO `kendala_categories` (`id`, `name`, `sort_order`, `is_active`, `created_at`, `updated_at`) VALUES (24,'BUTUH MINI OLT',24,1,'2026-09-08 02:53:24','2026-09-08 02:53:24');
INSERT INTO `kendala_categories` (`id`, `name`, `sort_order`, `is_active`, `created_at`, `updated_at`) VALUES (25,'KENDALA TANAM TIANG',25,1,'2026-09-08 02:53:24','2026-09-08 02:53:24');
INSERT INTO `kendala_categories` (`id`, `name`, `sort_order`, `is_active`, `created_at`, `updated_at`) VALUES (26,'OVER CPP',26,1,'2026-09-08 02:53:24','2026-09-08 02:53:24');
INSERT INTO `kendala_categories` (`id`, `name`, `sort_order`, `is_active`, `created_at`, `updated_at`) VALUES (27,'PROVIDER LAIN/LOKAL',27,1,'2026-09-08 02:53:24','2026-09-08 02:53:24');
INSERT INTO `kendala_categories` (`id`, `name`, `sort_order`, `is_active`, `created_at`, `updated_at`) VALUES (28,'CROSSING KAI',28,1,'2026-09-08 02:53:24','2026-09-08 02:53:24');
INSERT INTO `kendala_categories` (`id`, `name`, `sort_order`, `is_active`, `created_at`, `updated_at`) VALUES (29,'KOMPENSASI TINGGI',29,1,'2026-09-08 02:53:24','2026-09-08 02:53:24');
INSERT INTO `kendala_categories` (`id`, `name`, `sort_order`, `is_active`, `created_at`, `updated_at`) VALUES (30,'NEED INSERT MODUL OLT',30,1,'2026-09-08 02:53:24','2026-09-08 02:53:24');
INSERT INTO `kendala_categories` (`id`, `name`, `sort_order`, `is_active`, `created_at`, `updated_at`) VALUES (31,'REDAMAN TINGGI',31,1,'2026-09-08 02:53:24','2026-09-08 02:53:24');
INSERT INTO `kendala_categories` (`id`, `name`, `sort_order`, `is_active`, `created_at`, `updated_at`) VALUES (32,'BELUM ADA PKS DEVELOPER',32,1,'2026-09-08 02:53:24','2026-09-08 02:53:24');
INSERT INTO `permit_categories` (`id`, `name`, `sort_order`, `is_active`, `created_at`, `updated_at`) VALUES (1,'NO ISSUE',1,1,'2026-09-08 02:53:24','2026-09-08 02:53:24');
INSERT INTO `permit_categories` (`id`, `name`, `sort_order`, `is_active`, `created_at`, `updated_at`) VALUES (2,'PERIZINAN PU NASIONAL',2,1,'2026-09-08 02:53:24','2026-09-08 02:53:24');
INSERT INTO `permit_categories` (`id`, `name`, `sort_order`, `is_active`, `created_at`, `updated_at`) VALUES (3,'PERIZINAN PU PROVINSI',3,1,'2026-09-08 02:53:24','2026-09-08 02:53:24');
INSERT INTO `permit_categories` (`id`, `name`, `sort_order`, `is_active`, `created_at`, `updated_at`) VALUES (4,'PERIZINAN PU KABUPATEN',4,1,'2026-09-08 02:53:24','2026-09-08 02:53:24');
INSERT INTO `permit_categories` (`id`, `name`, `sort_order`, `is_active`, `created_at`, `updated_at`) VALUES (5,'PERIZINAN PU KOTA',5,1,'2026-09-08 02:53:24','2026-09-08 02:53:24');
INSERT INTO `permit_categories` (`id`, `name`, `sort_order`, `is_active`, `created_at`, `updated_at`) VALUES (6,'PERIZINAN WARGA/RT/RW',6,1,'2026-09-08 02:53:24','2026-09-08 02:53:24');
INSERT INTO `permit_categories` (`id`, `name`, `sort_order`, `is_active`, `created_at`, `updated_at`) VALUES (7,'PERIZINAN KOMPLEK/CLUSTER',7,1,'2026-09-08 02:53:24','2026-09-08 02:53:24');
INSERT INTO `permit_categories` (`id`, `name`, `sort_order`, `is_active`, `created_at`, `updated_at`) VALUES (8,'PERIZINAN KELURAHAN / KECAMATAN',8,1,'2026-09-08 02:53:24','2026-09-08 02:53:24');
INSERT INTO `permit_categories` (`id`, `name`, `sort_order`, `is_active`, `created_at`, `updated_at`) VALUES (9,'PERIZINAN ADAT SETEMPAT',9,1,'2026-09-08 02:53:24','2026-09-08 02:53:24');
INSERT INTO `permit_categories` (`id`, `name`, `sort_order`, `is_active`, `created_at`, `updated_at`) VALUES (10,'PERIZINAN GEDUNG/HRB',10,1,'2026-09-08 02:53:24','2026-09-08 02:53:24');
INSERT INTO `permit_categories` (`id`, `name`, `sort_order`, `is_active`, `created_at`, `updated_at`) VALUES (11,'PERIZINAN PRIVATE AREA/KAWASAN KHUSUS',11,1,'2026-09-08 02:53:24','2026-09-08 02:53:24');
INSERT INTO `permit_categories` (`id`, `name`, `sort_order`, `is_active`, `created_at`, `updated_at`) VALUES (12,'PERIZINAN INSTANSI',12,1,'2026-09-08 02:53:24','2026-09-08 02:53:24');
