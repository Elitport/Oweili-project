-- =====================================================================
--  owner_update.sql — the new owner dashboard (owner, 24 Nov 2026).
--  Only ADDS: 3 new tables and 1 new column. Nothing is changed or
--  deleted. Safe to run more than once.
--
--  1. page_stats      daily counts only (no person, no internet address):
--                     day, what was seen (kind + item), language, device, count
--  2. activity_log.device  phone / tablet / computer for each new activity line
--  3. crm_people      the owner's notes on one account: stage, follow-up date, note
--  4. sent_messages   the group messages the owner sends (one row per person)
-- =====================================================================

CREATE TABLE IF NOT EXISTS `page_stats` (
  `day`     DATE         NOT NULL,
  `kind`    VARCHAR(12)  NOT NULL,              -- visit / page / step / artwork / artist / style / place / region
  `item`    VARCHAR(80)  NOT NULL DEFAULT '',   -- e.g. marketplace, 12, watercolor, riyadh/national-museum
  `lang`    CHAR(2)      NOT NULL DEFAULT 'en', -- en / ar
  `device`  VARCHAR(8)   NOT NULL DEFAULT 'computer', -- phone / tablet / computer
  `views`   INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`day`, `kind`, `item`, `lang`, `device`),
  KEY `idx_page_stats_kind` (`kind`, `day`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `activity_log` ADD COLUMN IF NOT EXISTS `device` VARCHAR(8) NULL DEFAULT NULL;

CREATE TABLE IF NOT EXISTS `crm_people` (
  `user_id`    BIGINT UNSIGNED NOT NULL,
  `stage`      VARCHAR(16)  NOT NULL DEFAULT 'new',   -- new / contacted / onboarding / active / inactive
  `follow_up`  DATE         NULL DEFAULT NULL,
  `note`       TEXT         NULL,
  `updated_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`),
  KEY `idx_crm_follow` (`follow_up`),
  KEY `idx_crm_stage` (`stage`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `sent_messages` (
  `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `batch`      CHAR(16)     NOT NULL,                  -- one group message = one batch
  `user_id`    BIGINT UNSIGNED NOT NULL,
  `kind`       VARCHAR(8)   NOT NULL DEFAULT 'service', -- news / service
  `subject`    VARCHAR(200) NOT NULL DEFAULT '',
  `body_en`    TEXT         NULL,
  `body_ar`    TEXT         NULL,
  `status`     VARCHAR(8)   NOT NULL DEFAULT 'queued',  -- queued / sent / failed / skipped
  `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `sent_at`    DATETIME     NULL DEFAULT NULL,
  KEY `idx_sent_batch` (`batch`, `status`),
  KEY `idx_sent_user` (`user_id`, `created_at`),
  KEY `idx_sent_status` (`status`, `sent_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
