SET FOREIGN_KEY_CHECKS=0;

USE `radaptor_app`;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `adminmenu_tree` (
  `node_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `lft` int(10) unsigned NOT NULL,
  `rgt` int(10) unsigned NOT NULL,
  `parent_id` int(10) unsigned NOT NULL,
  `node_name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_hungarian_ci DEFAULT NULL,
  `node_type` varchar(16) CHARACTER SET utf8mb3 COLLATE utf8mb3_hungarian_ci NOT NULL,
  `page_id` int(10) unsigned DEFAULT NULL,
  `url` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_hungarian_ci DEFAULT NULL,
  PRIMARY KEY (`node_id`),
  KEY `lft` (`lft`),
  KEY `rgt` (`rgt`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `attributes` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `resource_name` varchar(32) CHARACTER SET utf8mb3 COLLATE utf8mb3_hungarian_ci DEFAULT NULL,
  `resource_id` int(10) unsigned NOT NULL DEFAULT 0,
  `param_name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_hungarian_ci NOT NULL DEFAULT '',
  `param_value` text CHARACTER SET utf8mb3 COLLATE utf8mb3_hungarian_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `param` (`resource_name`,`resource_id`,`param_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `blog` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `slug` varchar(255) DEFAULT NULL,
  `title` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_hungarian_ci DEFAULT NULL,
  `content` text CHARACTER SET utf8mb3 COLLATE utf8mb3_hungarian_ci DEFAULT NULL,
  `__content` text CHARACTER SET utf8mb3 COLLATE utf8mb3_hungarian_ci DEFAULT NULL,
  `description` text DEFAULT NULL,
  `__description` text DEFAULT NULL,
  `date` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `comments` (
  `comment_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `subject_type` varchar(64) DEFAULT NULL,
  `subject_id` int(10) unsigned DEFAULT NULL,
  `parent_comment_id` int(10) unsigned DEFAULT NULL,
  `comment` text CHARACTER SET utf8mb3 COLLATE utf8mb3_hungarian_ci DEFAULT NULL,
  `__comment` text CHARACTER SET utf8mb3 COLLATE utf8mb3_hungarian_ci DEFAULT NULL,
  PRIMARY KEY (`comment_id`),
  KEY `index2` (`subject_type`,`subject_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `config_app` (
  `config_key` varchar(191) NOT NULL,
  `value` text NOT NULL,
  `updated_by_user_id` int(10) unsigned DEFAULT NULL,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`config_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci COMMENT='__noaudit';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `config_user` (
  `user_id` int(10) unsigned NOT NULL,
  `config_key` varchar(255) NOT NULL,
  `value` text CHARACTER SET utf8mb3 COLLATE utf8mb3_hungarian_ci NOT NULL,
  PRIMARY KEY (`user_id`,`config_key`),
  CONSTRAINT `fk_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `custom_queries` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_hungarian_ci DEFAULT NULL,
  `query` text CHARACTER SET utf8mb3 COLLATE utf8mb3_hungarian_ci DEFAULT NULL,
  `owner_id` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `email_outbox` (
  `outbox_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `message_uid` varchar(64) NOT NULL,
  `send_mode` enum('transactional','bulk') NOT NULL,
  `template_version_id` bigint(20) unsigned DEFAULT NULL,
  `subject` text DEFAULT NULL,
  `html_body` longtext DEFAULT NULL,
  `text_body` longtext DEFAULT NULL,
  `status` enum('queued','processing','rendered','sent','partial_failed','failed') NOT NULL DEFAULT 'queued',
  `requested_by_type` varchar(32) NOT NULL,
  `requested_by_id` int(11) DEFAULT NULL,
  `metadata_json` longtext DEFAULT NULL,
  `scheduled_at` datetime DEFAULT NULL,
  `sent_at` datetime DEFAULT NULL,
  `last_error_code` varchar(64) DEFAULT NULL,
  `last_error_message` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`outbox_id`),
  UNIQUE KEY `uq_email_outbox_message_uid` (`message_uid`),
  KEY `idx_email_outbox_status_created` (`status`,`created_at`),
  KEY `fk_email_outbox_template_version` (`template_version_id`),
  KEY `idx_email_outbox_status_created_outbox` (`status`,`created_at`,`outbox_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci COMMENT='__noaudit';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `email_outbox_recipients` (
  `recipient_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `outbox_id` bigint(20) unsigned NOT NULL,
  `recipient_type` enum('to','cc','bcc') NOT NULL DEFAULT 'to',
  `recipient_email` varchar(320) NOT NULL,
  `recipient_name` varchar(255) DEFAULT NULL,
  `context_json` longtext DEFAULT NULL,
  `status` enum('queued','sent','failed') NOT NULL DEFAULT 'queued',
  `sent_at` datetime DEFAULT NULL,
  `last_error_code` varchar(64) DEFAULT NULL,
  `last_error_message` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`recipient_id`),
  KEY `idx_email_outbox_recipients_outbox` (`outbox_id`,`status`),
  CONSTRAINT `fk_email_outbox_recipients_outbox` FOREIGN KEY (`outbox_id`) REFERENCES `email_outbox` (`outbox_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci COMMENT='__noaudit';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `email_queue_archive` (
  `archive_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `source_table` varchar(64) NOT NULL,
  `job_id` varchar(64) NOT NULL,
  `job_type` varchar(128) NOT NULL,
  `payload_json` longtext NOT NULL,
  `requested_by_type` varchar(32) NOT NULL,
  `requested_by_id` int(11) DEFAULT NULL,
  `priority` varchar(16) DEFAULT NULL,
  `attempts` int(11) NOT NULL DEFAULT 0,
  `completed_at` datetime NOT NULL,
  `created_at` datetime NOT NULL,
  `archived_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`archive_id`),
  KEY `idx_email_queue_archive_ttl` (`archived_at`),
  KEY `idx_email_queue_archive_job_type_archived` (`job_type`,`archived_at`),
  KEY `idx_email_queue_archive_source_archived` (`source_table`,`archived_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci COMMENT='__noaudit';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `email_queue_dead_letter` (
  `dead_letter_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `source_table` varchar(64) NOT NULL,
  `job_id` varchar(64) NOT NULL,
  `job_type` varchar(128) NOT NULL,
  `payload_json` longtext NOT NULL,
  `requested_by_type` varchar(32) NOT NULL,
  `requested_by_id` int(11) DEFAULT NULL,
  `priority` varchar(16) DEFAULT NULL,
  `attempts` int(11) NOT NULL DEFAULT 0,
  `error_code` varchar(64) DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `dead_lettered_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`dead_letter_id`),
  KEY `idx_email_queue_dead_letter_ttl` (`dead_lettered_at`),
  KEY `idx_email_queue_dead_job_type_dead_lettered` (`job_type`,`dead_lettered_at`),
  KEY `idx_email_queue_dead_source_dead_lettered` (`source_table`,`dead_lettered_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci COMMENT='__noaudit';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `email_queue_transactional` (
  `job_id` varchar(64) NOT NULL,
  `job_type` varchar(128) NOT NULL,
  `payload_json` longtext NOT NULL,
  `requested_by_type` varchar(32) NOT NULL,
  `requested_by_id` int(11) DEFAULT NULL,
  `status` enum('pending','reserved','retry_wait') NOT NULL DEFAULT 'pending',
  `attempts` int(11) NOT NULL DEFAULT 0,
  `run_after_utc` datetime NOT NULL DEFAULT current_timestamp(),
  `reserved_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`job_id`),
  KEY `idx_email_queue_transactional_ready` (`status`,`run_after_utc`,`job_id`),
  KEY `idx_email_queue_transactional_reserved` (`status`,`reserved_at`,`job_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci COMMENT='__noaudit';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `i18n_build_state` (
  `locale` varchar(10) NOT NULL,
  `catalog_hash` char(32) NOT NULL,
  `built_at` datetime NOT NULL,
  PRIMARY KEY (`locale`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='__noaudit';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `i18n_messages` (
  `domain` varchar(100) NOT NULL,
  `key` varchar(255) NOT NULL,
  `context` varchar(100) NOT NULL DEFAULT '',
  `source_text` text NOT NULL DEFAULT '',
  `source_hash` char(32) NOT NULL DEFAULT '',
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  PRIMARY KEY (`domain`,`key`,`context`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='__noaudit';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `i18n_tm_entries` (
  `tm_id` int(11) NOT NULL AUTO_INCREMENT,
  `source_locale` varchar(10) NOT NULL,
  `target_locale` varchar(10) NOT NULL,
  `source_text_normalized` text NOT NULL,
  `source_text_raw` text NOT NULL,
  `target_text` text NOT NULL,
  `domain` varchar(100) NOT NULL DEFAULT '',
  `source_key` varchar(255) NOT NULL DEFAULT '',
  `context` varchar(100) NOT NULL DEFAULT '',
  `source_hash` char(32) NOT NULL,
  `usage_count` int(11) NOT NULL DEFAULT 0,
  `quality_score` enum('manual','approved','imported','mt') NOT NULL DEFAULT 'mt',
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`tm_id`),
  KEY `idx_tm_lookup` (`source_locale`,`target_locale`,`source_hash`),
  KEY `idx_tm_signature` (`source_locale`,`target_locale`,`source_hash`,`domain`,`source_key`,`context`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='__noaudit';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `i18n_translations` (
  `domain` varchar(100) NOT NULL,
  `key` varchar(255) NOT NULL,
  `context` varchar(100) NOT NULL DEFAULT '',
  `locale` varchar(10) NOT NULL,
  `text` text NOT NULL DEFAULT '',
  `human_reviewed` tinyint(1) NOT NULL DEFAULT 0,
  `source_hash_snapshot` char(32) NOT NULL DEFAULT '',
  PRIMARY KEY (`domain`,`key`,`context`,`locale`),
  CONSTRAINT `fk_i18n_translations_messages` FOREIGN KEY (`domain`, `key`, `context`) REFERENCES `i18n_messages` (`domain`, `key`, `context`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='__noaudit';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `mainmenu_tree` (
  `node_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `lft` int(10) unsigned NOT NULL,
  `rgt` int(10) unsigned NOT NULL,
  `parent_id` int(10) unsigned NOT NULL,
  `node_name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_hungarian_ci DEFAULT NULL,
  `node_type` varchar(16) CHARACTER SET utf8mb3 COLLATE utf8mb3_hungarian_ci NOT NULL,
  `page_id` int(10) unsigned DEFAULT NULL,
  `url` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_hungarian_ci DEFAULT NULL,
  PRIMARY KEY (`node_id`),
  KEY `lft` (`lft`),
  KEY `rgt` (`rgt`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `mediacontainer_vfs_files` (
  `file_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `md5_hash` varchar(32) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `storage_folder_id` int(10) unsigned DEFAULT NULL,
  `filesize` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`file_id`),
  KEY `md5_hash` (`md5_hash`),
  KEY `storage_folder_id` (`storage_folder_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `migration_hash` varchar(32) NOT NULL,
  `module` varchar(100) NOT NULL DEFAULT 'framework',
  `migration_name` varchar(255) NOT NULL,
  `applied_at` datetime NOT NULL,
  PRIMARY KEY (`migration_hash`),
  UNIQUE KEY `uq_module_filename` (`module`,`migration_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci COMMENT='__noaudit';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `project_versions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `project_id` int(10) unsigned NOT NULL,
  `version` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_hungarian_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `resource_acl` (
  `acl_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `resource_id` int(10) unsigned NOT NULL,
  `subject_type` enum('user','usergroup') CHARACTER SET utf8mb3 COLLATE utf8mb3_hungarian_ci NOT NULL,
  `subject_id` int(10) unsigned DEFAULT NULL,
  `allow_view` tinyint(1) NOT NULL DEFAULT 0,
  `allow_edit` tinyint(1) NOT NULL DEFAULT 0,
  `allow_delete` tinyint(1) NOT NULL DEFAULT 0,
  `allow_publish` tinyint(1) NOT NULL DEFAULT 0,
  `allow_list` tinyint(1) NOT NULL DEFAULT 0,
  `allow_create` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`acl_id`),
  UNIQUE KEY `lookup` (`resource_id`,`subject_type`,`subject_id`),
  KEY `subject` (`subject_type`,`subject_id`),
  KEY `resource` (`resource_id`),
  CONSTRAINT `fk_resource_id` FOREIGN KEY (`resource_id`) REFERENCES `resource_tree` (`node_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `resource_tree` (
  `node_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `lft` int(10) unsigned NOT NULL COMMENT '__noaudit',
  `rgt` int(10) unsigned NOT NULL COMMENT '__noaudit',
  `parent_id` int(10) unsigned NOT NULL,
  `node_type` enum('webpage','folder','file','domain','root') CHARACTER SET utf8mb3 COLLATE utf8mb3_hungarian_ci DEFAULT NULL,
  `resource_name` varchar(128) CHARACTER SET utf8mb3 COLLATE utf8mb3_hungarian_ci DEFAULT NULL,
  `catcher_page` int(10) unsigned DEFAULT NULL,
  `is_inheriting_acl` tinyint(1) NOT NULL DEFAULT 1,
  `path` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_hungarian_ci NOT NULL DEFAULT '/',
  `last_modified` int(10) unsigned DEFAULT NULL COMMENT '__noaudit',
  PRIMARY KEY (`node_id`),
  UNIQUE KEY `pathIndex` (`resource_name`,`path`),
  KEY `lft` (`lft`),
  KEY `node_type` (`node_type`),
  KEY `rgt` (`rgt`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `richtext` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `content_type` enum('article','blog','info') CHARACTER SET utf8mb3 COLLATE utf8mb3_hungarian_ci NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_hungarian_ci DEFAULT NULL,
  `title` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_hungarian_ci DEFAULT NULL,
  `content` text CHARACTER SET utf8mb3 COLLATE utf8mb3_hungarian_ci DEFAULT NULL,
  `__content` text CHARACTER SET utf8mb3 COLLATE utf8mb3_hungarian_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  KEY `content_type` (`content_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `roles_tree` (
  `node_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `lft` int(10) unsigned NOT NULL,
  `rgt` int(10) unsigned NOT NULL,
  `parent_id` int(10) unsigned NOT NULL,
  `title` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_hungarian_ci NOT NULL,
  `description` text CHARACTER SET utf8mb3 COLLATE utf8mb3_hungarian_ci DEFAULT NULL,
  `role` varchar(32) CHARACTER SET utf8mb3 COLLATE utf8mb3_hungarian_ci DEFAULT NULL,
  PRIMARY KEY (`node_id`),
  UNIQUE KEY `role` (`role`),
  KEY `lft` (`lft`),
  KEY `rgt` (`rgt`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `seeds` (
  `module` varchar(120) NOT NULL,
  `seed_class` varchar(255) NOT NULL,
  `kind` varchar(20) NOT NULL,
  `version` varchar(100) NOT NULL,
  `applied_at` datetime NOT NULL,
  PRIMARY KEY (`module`,`seed_class`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci COMMENT='__noaudit';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tag_connections` (
  `rowid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `context` varchar(64) NOT NULL DEFAULT '',
  `connected_id` int(10) unsigned NOT NULL,
  `tag_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `context_id` (`context`,`connected_id`,`tag_id`),
  UNIQUE KEY `context_connected_tag` (`context`,`connected_id`,`tag_id`),
  KEY `context_id_connected_id` (`context`,`connected_id`),
  KEY `idx_context_connected` (`context`,`connected_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tags` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `context` varchar(64) NOT NULL DEFAULT '',
  `slug` varchar(255) NOT NULL DEFAULT '',
  `name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_hungarian_ci DEFAULT NULL,
  `__description` text CHARACTER SET utf8mb3 COLLATE utf8mb3_hungarian_ci DEFAULT NULL,
  `description` text CHARACTER SET utf8mb3 COLLATE utf8mb3_hungarian_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_tags_context_slug` (`context`,`slug`),
  UNIQUE KEY `name` (`name`,`context`),
  KEY `idx_context` (`context`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `templatemonster_analytics` (
  `id` varchar(12) CHARACTER SET utf8mb3 COLLATE utf8mb3_hungarian_ci NOT NULL,
  `item` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_hungarian_ci DEFAULT NULL,
  `item_shortname` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_hungarian_ci DEFAULT NULL,
  `url` text CHARACTER SET utf8mb3 COLLATE utf8mb3_hungarian_ci DEFAULT NULL,
  `user` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_hungarian_ci DEFAULT NULL,
  `thumbnail` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_hungarian_ci DEFAULT NULL COMMENT '__noaudit',
  `sales` int(10) DEFAULT NULL,
  `rating` decimal(4,2) DEFAULT NULL,
  `rating_decimal` decimal(4,2) DEFAULT NULL,
  `cost` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_hungarian_ci DEFAULT NULL,
  `uploaded_on` datetime DEFAULT NULL,
  `last_update` datetime DEFAULT NULL,
  `tags` text CHARACTER SET utf8mb3 COLLATE utf8mb3_hungarian_ci DEFAULT NULL,
  `category` text CHARACTER SET utf8mb3 COLLATE utf8mb3_hungarian_ci DEFAULT NULL,
  `live_preview_url` text CHARACTER SET utf8mb3 COLLATE utf8mb3_hungarian_ci DEFAULT NULL COMMENT '__noaudit',
  `live_demo_url` text CHARACTER SET utf8mb3 COLLATE utf8mb3_hungarian_ci DEFAULT NULL,
  `sales60days` int(11) DEFAULT NULL COMMENT '__noaudit',
  `sales30days` int(11) DEFAULT NULL COMMENT '__noaudit',
  `sales10days` int(11) DEFAULT NULL COMMENT '__noaudit',
  `sales7days` int(11) DEFAULT NULL COMMENT '__noaudit',
  `sales3days` int(11) DEFAULT NULL COMMENT '__noaudit',
  PRIMARY KEY (`id`),
  KEY `sales60days` (`sales60days`),
  KEY `sales30days` (`sales30days`),
  KEY `sales10days` (`sales10days`),
  KEY `sales7days` (`sales7days`),
  KEY `sales3days` (`sales3days`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `themeforest_analytics` (
  `id` varchar(12) CHARACTER SET utf8mb3 COLLATE utf8mb3_hungarian_ci NOT NULL,
  `item` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_hungarian_ci DEFAULT NULL,
  `item_shortname` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_hungarian_ci DEFAULT NULL,
  `url` text CHARACTER SET utf8mb3 COLLATE utf8mb3_hungarian_ci DEFAULT NULL,
  `user` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_hungarian_ci DEFAULT NULL,
  `thumbnail` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_hungarian_ci DEFAULT NULL COMMENT '__noaudit',
  `sales` int(10) DEFAULT NULL,
  `rating` decimal(4,2) DEFAULT NULL,
  `rating_decimal` decimal(4,2) DEFAULT NULL,
  `cost` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_hungarian_ci DEFAULT NULL,
  `uploaded_on` datetime DEFAULT NULL,
  `last_update` datetime DEFAULT NULL,
  `tags` text CHARACTER SET utf8mb3 COLLATE utf8mb3_hungarian_ci DEFAULT NULL,
  `category` text CHARACTER SET utf8mb3 COLLATE utf8mb3_hungarian_ci DEFAULT NULL,
  `live_preview_url` text CHARACTER SET utf8mb3 COLLATE utf8mb3_hungarian_ci DEFAULT NULL COMMENT '__noaudit',
  `live_demo_url` text CHARACTER SET utf8mb3 COLLATE utf8mb3_hungarian_ci DEFAULT NULL,
  `sales60days` int(11) DEFAULT NULL COMMENT '__noaudit',
  `sales30days` int(11) DEFAULT NULL COMMENT '__noaudit',
  `sales10days` int(11) DEFAULT NULL COMMENT '__noaudit',
  `sales7days` int(11) DEFAULT NULL COMMENT '__noaudit',
  `sales3days` int(11) DEFAULT NULL COMMENT '__noaudit',
  PRIMARY KEY (`id`),
  KEY `sales60days` (`sales60days`),
  KEY `sales30days` (`sales30days`),
  KEY `sales10days` (`sales10days`),
  KEY `sales7days` (`sales7days`),
  KEY `sales3days` (`sales3days`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `usergroups_roles_mapping` (
  `role_id` int(10) unsigned NOT NULL,
  `usergroup_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`usergroup_id`,`role_id`),
  KEY `fk2_roles` (`role_id`),
  CONSTRAINT `fk2_roles` FOREIGN KEY (`role_id`) REFERENCES `roles_tree` (`node_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_usergroups` FOREIGN KEY (`usergroup_id`) REFERENCES `usergroups_tree` (`node_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `usergroups_tree` (
  `node_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `lft` int(10) unsigned NOT NULL,
  `rgt` int(10) unsigned NOT NULL,
  `parent_id` int(10) unsigned NOT NULL,
  `is_system_group` tinyint(1) DEFAULT 0,
  `title` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_hungarian_ci NOT NULL,
  `description` text CHARACTER SET utf8mb3 COLLATE utf8mb3_hungarian_ci DEFAULT NULL,
  PRIMARY KEY (`node_id`),
  UNIQUE KEY `title` (`title`),
  KEY `lft` (`lft`),
  KEY `rgt` (`rgt`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `user_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(32) CHARACTER SET utf8mb3 COLLATE utf8mb3_hungarian_ci NOT NULL DEFAULT '',
  `is_active` tinyint(1) NOT NULL DEFAULT 0,
  `last_seen` timestamp NOT NULL DEFAULT current_timestamp() COMMENT '__noaudit',
  `timezone` varchar(64) DEFAULT NULL COMMENT 'IANA timezone identifier (for example Europe/Budapest, America/New_York)',
  `locale` varchar(10) NOT NULL DEFAULT 'en_US' COMMENT 'Preferred locale for UI (e.g. en_US, hu_HU)',
  `password` varchar(128) CHARACTER SET utf8mb3 COLLATE utf8mb3_hungarian_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users_roles_mapping` (
  `user_id` int(10) unsigned NOT NULL,
  `role_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`user_id`,`role_id`),
  UNIQUE KEY `mapping` (`user_id`,`role_id`),
  KEY `roles` (`role_id`),
  CONSTRAINT `fk_roles` FOREIGN KEY (`role_id`) REFERENCES `roles_tree` (`node_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_users` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users_usergroups_mapping` (
  `user_id` int(10) unsigned NOT NULL,
  `usergroup_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`user_id`,`usergroup_id`),
  KEY `fk_usergroup_id` (`usergroup_id`),
  CONSTRAINT `fk_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_usergroup_id` FOREIGN KEY (`usergroup_id`) REFERENCES `usergroups_tree` (`node_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `widget_connections` (
  `connection_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `page_id` int(10) unsigned NOT NULL,
  `slot_name` varchar(64) NOT NULL,
  `widget_name` varchar(64) NOT NULL,
  `seq` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`connection_id`),
  KEY `seq` (`seq`),
  KEY `NewIndex1` (`page_id`,`slot_name`),
  CONSTRAINT `fk_resource` FOREIGN KEY (`page_id`) REFERENCES `resource_tree` (`node_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `wrapbootstrap_analytics` (
  `id` varchar(12) CHARACTER SET utf8mb3 COLLATE utf8mb3_hungarian_ci NOT NULL,
  `item` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_hungarian_ci DEFAULT NULL,
  `item_shortname` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_hungarian_ci DEFAULT NULL,
  `url` text CHARACTER SET utf8mb3 COLLATE utf8mb3_hungarian_ci DEFAULT NULL,
  `user` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_hungarian_ci DEFAULT NULL,
  `thumbnail` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_hungarian_ci DEFAULT NULL COMMENT '__noaudit',
  `sales` int(10) DEFAULT NULL,
  `rating` decimal(4,2) DEFAULT NULL,
  `rating_decimal` decimal(4,2) DEFAULT NULL,
  `cost` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_hungarian_ci DEFAULT NULL,
  `uploaded_on` datetime DEFAULT NULL,
  `last_update` datetime DEFAULT NULL,
  `tags` text CHARACTER SET utf8mb3 COLLATE utf8mb3_hungarian_ci DEFAULT NULL,
  `category` text CHARACTER SET utf8mb3 COLLATE utf8mb3_hungarian_ci DEFAULT NULL,
  `live_preview_url` text CHARACTER SET utf8mb3 COLLATE utf8mb3_hungarian_ci DEFAULT NULL COMMENT '__noaudit',
  `live_demo_url` text CHARACTER SET utf8mb3 COLLATE utf8mb3_hungarian_ci DEFAULT NULL,
  `sales60days` int(11) DEFAULT NULL COMMENT '__noaudit',
  `sales30days` int(11) DEFAULT NULL COMMENT '__noaudit',
  `sales10days` int(11) DEFAULT NULL COMMENT '__noaudit',
  `sales7days` int(11) DEFAULT NULL COMMENT '__noaudit',
  `sales3days` int(11) DEFAULT NULL COMMENT '__noaudit',
  PRIMARY KEY (`id`),
  KEY `sales60days` (`sales60days`),
  KEY `sales30days` (`sales30days`),
  KEY `sales10days` (`sales10days`),
  KEY `sales7days` (`sales7days`),
  KEY `sales3days` (`sales3days`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;



USE `radaptor_app_test`;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `adminmenu_tree` (
  `node_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `lft` int(10) unsigned NOT NULL,
  `rgt` int(10) unsigned NOT NULL,
  `parent_id` int(10) unsigned NOT NULL,
  `node_name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_hungarian_ci DEFAULT NULL,
  `node_type` varchar(16) CHARACTER SET utf8mb3 COLLATE utf8mb3_hungarian_ci NOT NULL,
  `page_id` int(10) unsigned DEFAULT NULL,
  `url` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_hungarian_ci DEFAULT NULL,
  PRIMARY KEY (`node_id`),
  KEY `lft` (`lft`),
  KEY `rgt` (`rgt`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `attributes` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `resource_name` varchar(32) CHARACTER SET utf8mb3 COLLATE utf8mb3_hungarian_ci DEFAULT NULL,
  `resource_id` int(10) unsigned NOT NULL DEFAULT 0,
  `param_name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_hungarian_ci NOT NULL DEFAULT '',
  `param_value` text CHARACTER SET utf8mb3 COLLATE utf8mb3_hungarian_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `param` (`resource_name`,`resource_id`,`param_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `blog` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `slug` varchar(255) DEFAULT NULL,
  `title` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_hungarian_ci DEFAULT NULL,
  `content` text CHARACTER SET utf8mb3 COLLATE utf8mb3_hungarian_ci DEFAULT NULL,
  `__content` text CHARACTER SET utf8mb3 COLLATE utf8mb3_hungarian_ci DEFAULT NULL,
  `description` text DEFAULT NULL,
  `__description` text DEFAULT NULL,
  `date` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `comments` (
  `comment_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `subject_type` varchar(64) DEFAULT NULL,
  `subject_id` int(10) unsigned DEFAULT NULL,
  `parent_comment_id` int(10) unsigned DEFAULT NULL,
  `comment` text CHARACTER SET utf8mb3 COLLATE utf8mb3_hungarian_ci DEFAULT NULL,
  `__comment` text CHARACTER SET utf8mb3 COLLATE utf8mb3_hungarian_ci DEFAULT NULL,
  PRIMARY KEY (`comment_id`),
  KEY `index2` (`subject_type`,`subject_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `config_app` (
  `config_key` varchar(191) NOT NULL,
  `value` text NOT NULL,
  `updated_by_user_id` int(10) unsigned DEFAULT NULL,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`config_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci COMMENT='__noaudit';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `config_user` (
  `user_id` int(10) unsigned NOT NULL,
  `config_key` varchar(255) NOT NULL,
  `value` text CHARACTER SET utf8mb3 COLLATE utf8mb3_hungarian_ci NOT NULL,
  PRIMARY KEY (`user_id`,`config_key`),
  CONSTRAINT `fk_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `custom_queries` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_hungarian_ci DEFAULT NULL,
  `query` text CHARACTER SET utf8mb3 COLLATE utf8mb3_hungarian_ci DEFAULT NULL,
  `owner_id` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `email_outbox` (
  `outbox_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `message_uid` varchar(64) NOT NULL,
  `send_mode` enum('transactional','bulk') NOT NULL,
  `template_version_id` bigint(20) unsigned DEFAULT NULL,
  `subject` text DEFAULT NULL,
  `html_body` longtext DEFAULT NULL,
  `text_body` longtext DEFAULT NULL,
  `status` enum('queued','processing','rendered','sent','partial_failed','failed') NOT NULL DEFAULT 'queued',
  `requested_by_type` varchar(32) NOT NULL,
  `requested_by_id` int(11) DEFAULT NULL,
  `metadata_json` longtext DEFAULT NULL,
  `scheduled_at` datetime DEFAULT NULL,
  `sent_at` datetime DEFAULT NULL,
  `last_error_code` varchar(64) DEFAULT NULL,
  `last_error_message` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`outbox_id`),
  UNIQUE KEY `uq_email_outbox_message_uid` (`message_uid`),
  KEY `idx_email_outbox_status_created` (`status`,`created_at`),
  KEY `fk_email_outbox_template_version` (`template_version_id`),
  KEY `idx_email_outbox_status_created_outbox` (`status`,`created_at`,`outbox_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci COMMENT='__noaudit';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `email_outbox_recipients` (
  `recipient_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `outbox_id` bigint(20) unsigned NOT NULL,
  `recipient_type` enum('to','cc','bcc') NOT NULL DEFAULT 'to',
  `recipient_email` varchar(320) NOT NULL,
  `recipient_name` varchar(255) DEFAULT NULL,
  `context_json` longtext DEFAULT NULL,
  `status` enum('queued','sent','failed') NOT NULL DEFAULT 'queued',
  `sent_at` datetime DEFAULT NULL,
  `last_error_code` varchar(64) DEFAULT NULL,
  `last_error_message` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`recipient_id`),
  KEY `idx_email_outbox_recipients_outbox` (`outbox_id`,`status`),
  CONSTRAINT `fk_email_outbox_recipients_outbox` FOREIGN KEY (`outbox_id`) REFERENCES `email_outbox` (`outbox_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci COMMENT='__noaudit';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `email_queue_archive` (
  `archive_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `source_table` varchar(64) NOT NULL,
  `job_id` varchar(64) NOT NULL,
  `job_type` varchar(128) NOT NULL,
  `payload_json` longtext NOT NULL,
  `requested_by_type` varchar(32) NOT NULL,
  `requested_by_id` int(11) DEFAULT NULL,
  `priority` varchar(16) DEFAULT NULL,
  `attempts` int(11) NOT NULL DEFAULT 0,
  `completed_at` datetime NOT NULL,
  `created_at` datetime NOT NULL,
  `archived_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`archive_id`),
  KEY `idx_email_queue_archive_ttl` (`archived_at`),
  KEY `idx_email_queue_archive_job_type_archived` (`job_type`,`archived_at`),
  KEY `idx_email_queue_archive_source_archived` (`source_table`,`archived_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci COMMENT='__noaudit';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `email_queue_dead_letter` (
  `dead_letter_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `source_table` varchar(64) NOT NULL,
  `job_id` varchar(64) NOT NULL,
  `job_type` varchar(128) NOT NULL,
  `payload_json` longtext NOT NULL,
  `requested_by_type` varchar(32) NOT NULL,
  `requested_by_id` int(11) DEFAULT NULL,
  `priority` varchar(16) DEFAULT NULL,
  `attempts` int(11) NOT NULL DEFAULT 0,
  `error_code` varchar(64) DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `dead_lettered_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`dead_letter_id`),
  KEY `idx_email_queue_dead_letter_ttl` (`dead_lettered_at`),
  KEY `idx_email_queue_dead_job_type_dead_lettered` (`job_type`,`dead_lettered_at`),
  KEY `idx_email_queue_dead_source_dead_lettered` (`source_table`,`dead_lettered_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci COMMENT='__noaudit';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `email_queue_transactional` (
  `job_id` varchar(64) NOT NULL,
  `job_type` varchar(128) NOT NULL,
  `payload_json` longtext NOT NULL,
  `requested_by_type` varchar(32) NOT NULL,
  `requested_by_id` int(11) DEFAULT NULL,
  `status` enum('pending','reserved','retry_wait') NOT NULL DEFAULT 'pending',
  `attempts` int(11) NOT NULL DEFAULT 0,
  `run_after_utc` datetime NOT NULL DEFAULT current_timestamp(),
  `reserved_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`job_id`),
  KEY `idx_email_queue_transactional_ready` (`status`,`run_after_utc`,`job_id`),
  KEY `idx_email_queue_transactional_reserved` (`status`,`reserved_at`,`job_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci COMMENT='__noaudit';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `i18n_build_state` (
  `locale` varchar(10) NOT NULL,
  `catalog_hash` char(32) NOT NULL,
  `built_at` datetime NOT NULL,
  PRIMARY KEY (`locale`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='__noaudit';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `i18n_messages` (
  `domain` varchar(100) NOT NULL,
  `key` varchar(255) NOT NULL,
  `context` varchar(100) NOT NULL DEFAULT '',
  `source_text` text NOT NULL DEFAULT '',
  `source_hash` char(32) NOT NULL DEFAULT '',
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  PRIMARY KEY (`domain`,`key`,`context`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='__noaudit';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `i18n_tm_entries` (
  `tm_id` int(11) NOT NULL AUTO_INCREMENT,
  `source_locale` varchar(10) NOT NULL,
  `target_locale` varchar(10) NOT NULL,
  `source_text_normalized` text NOT NULL,
  `source_text_raw` text NOT NULL,
  `target_text` text NOT NULL,
  `domain` varchar(100) NOT NULL DEFAULT '',
  `source_key` varchar(255) NOT NULL DEFAULT '',
  `context` varchar(100) NOT NULL DEFAULT '',
  `source_hash` char(32) NOT NULL,
  `usage_count` int(11) NOT NULL DEFAULT 0,
  `quality_score` enum('manual','approved','imported','mt') NOT NULL DEFAULT 'mt',
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`tm_id`),
  KEY `idx_tm_lookup` (`source_locale`,`target_locale`,`source_hash`),
  KEY `idx_tm_signature` (`source_locale`,`target_locale`,`source_hash`,`domain`,`source_key`,`context`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='__noaudit';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `i18n_translations` (
  `domain` varchar(100) NOT NULL,
  `key` varchar(255) NOT NULL,
  `context` varchar(100) NOT NULL DEFAULT '',
  `locale` varchar(10) NOT NULL,
  `text` text NOT NULL DEFAULT '',
  `human_reviewed` tinyint(1) NOT NULL DEFAULT 0,
  `source_hash_snapshot` char(32) NOT NULL DEFAULT '',
  PRIMARY KEY (`domain`,`key`,`context`,`locale`),
  CONSTRAINT `fk_i18n_translations_messages` FOREIGN KEY (`domain`, `key`, `context`) REFERENCES `i18n_messages` (`domain`, `key`, `context`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='__noaudit';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `mainmenu_tree` (
  `node_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `lft` int(10) unsigned NOT NULL,
  `rgt` int(10) unsigned NOT NULL,
  `parent_id` int(10) unsigned NOT NULL,
  `node_name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_hungarian_ci DEFAULT NULL,
  `node_type` varchar(16) CHARACTER SET utf8mb3 COLLATE utf8mb3_hungarian_ci NOT NULL,
  `page_id` int(10) unsigned DEFAULT NULL,
  `url` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_hungarian_ci DEFAULT NULL,
  PRIMARY KEY (`node_id`),
  KEY `lft` (`lft`),
  KEY `rgt` (`rgt`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `mediacontainer_vfs_files` (
  `file_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `md5_hash` varchar(32) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `storage_folder_id` int(10) unsigned DEFAULT NULL,
  `filesize` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`file_id`),
  KEY `md5_hash` (`md5_hash`),
  KEY `storage_folder_id` (`storage_folder_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `migration_hash` varchar(32) NOT NULL,
  `module` varchar(100) NOT NULL DEFAULT 'framework',
  `migration_name` varchar(255) NOT NULL,
  `applied_at` datetime NOT NULL,
  PRIMARY KEY (`migration_hash`),
  UNIQUE KEY `uq_module_filename` (`module`,`migration_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci COMMENT='__noaudit';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `project_versions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `project_id` int(10) unsigned NOT NULL,
  `version` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_hungarian_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `resource_acl` (
  `acl_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `resource_id` int(10) unsigned NOT NULL,
  `subject_type` enum('user','usergroup') CHARACTER SET utf8mb3 COLLATE utf8mb3_hungarian_ci NOT NULL,
  `subject_id` int(10) unsigned DEFAULT NULL,
  `allow_view` tinyint(1) NOT NULL DEFAULT 0,
  `allow_edit` tinyint(1) NOT NULL DEFAULT 0,
  `allow_delete` tinyint(1) NOT NULL DEFAULT 0,
  `allow_publish` tinyint(1) NOT NULL DEFAULT 0,
  `allow_list` tinyint(1) NOT NULL DEFAULT 0,
  `allow_create` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`acl_id`),
  UNIQUE KEY `lookup` (`resource_id`,`subject_type`,`subject_id`),
  KEY `subject` (`subject_type`,`subject_id`),
  KEY `resource` (`resource_id`),
  CONSTRAINT `fk_resource_id` FOREIGN KEY (`resource_id`) REFERENCES `resource_tree` (`node_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `resource_tree` (
  `node_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `lft` int(10) unsigned NOT NULL COMMENT '__noaudit',
  `rgt` int(10) unsigned NOT NULL COMMENT '__noaudit',
  `parent_id` int(10) unsigned NOT NULL,
  `node_type` enum('webpage','folder','file','domain','root') CHARACTER SET utf8mb3 COLLATE utf8mb3_hungarian_ci DEFAULT NULL,
  `resource_name` varchar(128) CHARACTER SET utf8mb3 COLLATE utf8mb3_hungarian_ci DEFAULT NULL,
  `catcher_page` int(10) unsigned DEFAULT NULL,
  `is_inheriting_acl` tinyint(1) NOT NULL DEFAULT 1,
  `path` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_hungarian_ci NOT NULL DEFAULT '/',
  `last_modified` int(10) unsigned DEFAULT NULL COMMENT '__noaudit',
  PRIMARY KEY (`node_id`),
  UNIQUE KEY `pathIndex` (`resource_name`,`path`),
  KEY `lft` (`lft`),
  KEY `node_type` (`node_type`),
  KEY `rgt` (`rgt`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `richtext` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `content_type` enum('article','blog','info') CHARACTER SET utf8mb3 COLLATE utf8mb3_hungarian_ci NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_hungarian_ci DEFAULT NULL,
  `title` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_hungarian_ci DEFAULT NULL,
  `content` text CHARACTER SET utf8mb3 COLLATE utf8mb3_hungarian_ci DEFAULT NULL,
  `__content` text CHARACTER SET utf8mb3 COLLATE utf8mb3_hungarian_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  KEY `content_type` (`content_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `roles_tree` (
  `node_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `lft` int(10) unsigned NOT NULL,
  `rgt` int(10) unsigned NOT NULL,
  `parent_id` int(10) unsigned NOT NULL,
  `title` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_hungarian_ci NOT NULL,
  `description` text CHARACTER SET utf8mb3 COLLATE utf8mb3_hungarian_ci DEFAULT NULL,
  `role` varchar(32) CHARACTER SET utf8mb3 COLLATE utf8mb3_hungarian_ci DEFAULT NULL,
  PRIMARY KEY (`node_id`),
  UNIQUE KEY `role` (`role`),
  KEY `lft` (`lft`),
  KEY `rgt` (`rgt`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `seeds` (
  `module` varchar(120) NOT NULL,
  `seed_class` varchar(255) NOT NULL,
  `kind` varchar(20) NOT NULL,
  `version` varchar(100) NOT NULL,
  `applied_at` datetime NOT NULL,
  PRIMARY KEY (`module`,`seed_class`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci COMMENT='__noaudit';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tag_connections` (
  `rowid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `context` varchar(64) NOT NULL DEFAULT '',
  `connected_id` int(10) unsigned NOT NULL,
  `tag_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `context_id` (`context`,`connected_id`,`tag_id`),
  UNIQUE KEY `context_connected_tag` (`context`,`connected_id`,`tag_id`),
  KEY `context_id_connected_id` (`context`,`connected_id`),
  KEY `idx_context_connected` (`context`,`connected_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tags` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `context` varchar(64) NOT NULL DEFAULT '',
  `slug` varchar(255) NOT NULL DEFAULT '',
  `name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_hungarian_ci DEFAULT NULL,
  `__description` text CHARACTER SET utf8mb3 COLLATE utf8mb3_hungarian_ci DEFAULT NULL,
  `description` text CHARACTER SET utf8mb3 COLLATE utf8mb3_hungarian_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_tags_context_slug` (`context`,`slug`),
  UNIQUE KEY `name` (`name`,`context`),
  KEY `idx_context` (`context`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `templatemonster_analytics` (
  `id` varchar(12) CHARACTER SET utf8mb3 COLLATE utf8mb3_hungarian_ci NOT NULL,
  `item` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_hungarian_ci DEFAULT NULL,
  `item_shortname` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_hungarian_ci DEFAULT NULL,
  `url` text CHARACTER SET utf8mb3 COLLATE utf8mb3_hungarian_ci DEFAULT NULL,
  `user` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_hungarian_ci DEFAULT NULL,
  `thumbnail` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_hungarian_ci DEFAULT NULL COMMENT '__noaudit',
  `sales` int(10) DEFAULT NULL,
  `rating` decimal(4,2) DEFAULT NULL,
  `rating_decimal` decimal(4,2) DEFAULT NULL,
  `cost` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_hungarian_ci DEFAULT NULL,
  `uploaded_on` datetime DEFAULT NULL,
  `last_update` datetime DEFAULT NULL,
  `tags` text CHARACTER SET utf8mb3 COLLATE utf8mb3_hungarian_ci DEFAULT NULL,
  `category` text CHARACTER SET utf8mb3 COLLATE utf8mb3_hungarian_ci DEFAULT NULL,
  `live_preview_url` text CHARACTER SET utf8mb3 COLLATE utf8mb3_hungarian_ci DEFAULT NULL COMMENT '__noaudit',
  `live_demo_url` text CHARACTER SET utf8mb3 COLLATE utf8mb3_hungarian_ci DEFAULT NULL,
  `sales60days` int(11) DEFAULT NULL COMMENT '__noaudit',
  `sales30days` int(11) DEFAULT NULL COMMENT '__noaudit',
  `sales10days` int(11) DEFAULT NULL COMMENT '__noaudit',
  `sales7days` int(11) DEFAULT NULL COMMENT '__noaudit',
  `sales3days` int(11) DEFAULT NULL COMMENT '__noaudit',
  PRIMARY KEY (`id`),
  KEY `sales60days` (`sales60days`),
  KEY `sales30days` (`sales30days`),
  KEY `sales10days` (`sales10days`),
  KEY `sales7days` (`sales7days`),
  KEY `sales3days` (`sales3days`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `themeforest_analytics` (
  `id` varchar(12) CHARACTER SET utf8mb3 COLLATE utf8mb3_hungarian_ci NOT NULL,
  `item` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_hungarian_ci DEFAULT NULL,
  `item_shortname` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_hungarian_ci DEFAULT NULL,
  `url` text CHARACTER SET utf8mb3 COLLATE utf8mb3_hungarian_ci DEFAULT NULL,
  `user` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_hungarian_ci DEFAULT NULL,
  `thumbnail` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_hungarian_ci DEFAULT NULL COMMENT '__noaudit',
  `sales` int(10) DEFAULT NULL,
  `rating` decimal(4,2) DEFAULT NULL,
  `rating_decimal` decimal(4,2) DEFAULT NULL,
  `cost` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_hungarian_ci DEFAULT NULL,
  `uploaded_on` datetime DEFAULT NULL,
  `last_update` datetime DEFAULT NULL,
  `tags` text CHARACTER SET utf8mb3 COLLATE utf8mb3_hungarian_ci DEFAULT NULL,
  `category` text CHARACTER SET utf8mb3 COLLATE utf8mb3_hungarian_ci DEFAULT NULL,
  `live_preview_url` text CHARACTER SET utf8mb3 COLLATE utf8mb3_hungarian_ci DEFAULT NULL COMMENT '__noaudit',
  `live_demo_url` text CHARACTER SET utf8mb3 COLLATE utf8mb3_hungarian_ci DEFAULT NULL,
  `sales60days` int(11) DEFAULT NULL COMMENT '__noaudit',
  `sales30days` int(11) DEFAULT NULL COMMENT '__noaudit',
  `sales10days` int(11) DEFAULT NULL COMMENT '__noaudit',
  `sales7days` int(11) DEFAULT NULL COMMENT '__noaudit',
  `sales3days` int(11) DEFAULT NULL COMMENT '__noaudit',
  PRIMARY KEY (`id`),
  KEY `sales60days` (`sales60days`),
  KEY `sales30days` (`sales30days`),
  KEY `sales10days` (`sales10days`),
  KEY `sales7days` (`sales7days`),
  KEY `sales3days` (`sales3days`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `usergroups_roles_mapping` (
  `role_id` int(10) unsigned NOT NULL,
  `usergroup_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`usergroup_id`,`role_id`),
  KEY `fk2_roles` (`role_id`),
  CONSTRAINT `fk2_roles` FOREIGN KEY (`role_id`) REFERENCES `roles_tree` (`node_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_usergroups` FOREIGN KEY (`usergroup_id`) REFERENCES `usergroups_tree` (`node_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `usergroups_tree` (
  `node_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `lft` int(10) unsigned NOT NULL,
  `rgt` int(10) unsigned NOT NULL,
  `parent_id` int(10) unsigned NOT NULL,
  `is_system_group` tinyint(1) DEFAULT 0,
  `title` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_hungarian_ci NOT NULL,
  `description` text CHARACTER SET utf8mb3 COLLATE utf8mb3_hungarian_ci DEFAULT NULL,
  PRIMARY KEY (`node_id`),
  UNIQUE KEY `title` (`title`),
  KEY `lft` (`lft`),
  KEY `rgt` (`rgt`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `user_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(32) CHARACTER SET utf8mb3 COLLATE utf8mb3_hungarian_ci NOT NULL DEFAULT '',
  `is_active` tinyint(1) NOT NULL DEFAULT 0,
  `last_seen` timestamp NOT NULL DEFAULT current_timestamp() COMMENT '__noaudit',
  `timezone` varchar(64) DEFAULT NULL COMMENT 'IANA timezone identifier (for example Europe/Budapest, America/New_York)',
  `locale` varchar(10) NOT NULL DEFAULT 'en_US' COMMENT 'Preferred locale for UI (e.g. en_US, hu_HU)',
  `password` varchar(128) CHARACTER SET utf8mb3 COLLATE utf8mb3_hungarian_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users_roles_mapping` (
  `user_id` int(10) unsigned NOT NULL,
  `role_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`user_id`,`role_id`),
  UNIQUE KEY `mapping` (`user_id`,`role_id`),
  KEY `roles` (`role_id`),
  CONSTRAINT `fk_roles` FOREIGN KEY (`role_id`) REFERENCES `roles_tree` (`node_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_users` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users_usergroups_mapping` (
  `user_id` int(10) unsigned NOT NULL,
  `usergroup_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`user_id`,`usergroup_id`),
  KEY `fk_usergroup_id` (`usergroup_id`),
  CONSTRAINT `fk_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_usergroup_id` FOREIGN KEY (`usergroup_id`) REFERENCES `usergroups_tree` (`node_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `widget_connections` (
  `connection_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `page_id` int(10) unsigned NOT NULL,
  `slot_name` varchar(64) NOT NULL,
  `widget_name` varchar(64) NOT NULL,
  `seq` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`connection_id`),
  KEY `seq` (`seq`),
  KEY `NewIndex1` (`page_id`,`slot_name`),
  CONSTRAINT `fk_resource` FOREIGN KEY (`page_id`) REFERENCES `resource_tree` (`node_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `wrapbootstrap_analytics` (
  `id` varchar(12) CHARACTER SET utf8mb3 COLLATE utf8mb3_hungarian_ci NOT NULL,
  `item` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_hungarian_ci DEFAULT NULL,
  `item_shortname` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_hungarian_ci DEFAULT NULL,
  `url` text CHARACTER SET utf8mb3 COLLATE utf8mb3_hungarian_ci DEFAULT NULL,
  `user` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_hungarian_ci DEFAULT NULL,
  `thumbnail` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_hungarian_ci DEFAULT NULL COMMENT '__noaudit',
  `sales` int(10) DEFAULT NULL,
  `rating` decimal(4,2) DEFAULT NULL,
  `rating_decimal` decimal(4,2) DEFAULT NULL,
  `cost` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_hungarian_ci DEFAULT NULL,
  `uploaded_on` datetime DEFAULT NULL,
  `last_update` datetime DEFAULT NULL,
  `tags` text CHARACTER SET utf8mb3 COLLATE utf8mb3_hungarian_ci DEFAULT NULL,
  `category` text CHARACTER SET utf8mb3 COLLATE utf8mb3_hungarian_ci DEFAULT NULL,
  `live_preview_url` text CHARACTER SET utf8mb3 COLLATE utf8mb3_hungarian_ci DEFAULT NULL COMMENT '__noaudit',
  `live_demo_url` text CHARACTER SET utf8mb3 COLLATE utf8mb3_hungarian_ci DEFAULT NULL,
  `sales60days` int(11) DEFAULT NULL COMMENT '__noaudit',
  `sales30days` int(11) DEFAULT NULL COMMENT '__noaudit',
  `sales10days` int(11) DEFAULT NULL COMMENT '__noaudit',
  `sales7days` int(11) DEFAULT NULL COMMENT '__noaudit',
  `sales3days` int(11) DEFAULT NULL COMMENT '__noaudit',
  PRIMARY KEY (`id`),
  KEY `sales60days` (`sales60days`),
  KEY `sales30days` (`sales30days`),
  KEY `sales10days` (`sales10days`),
  KEY `sales7days` (`sales7days`),
  KEY `sales3days` (`sales3days`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;



SET FOREIGN_KEY_CHECKS=1;
