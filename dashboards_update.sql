-- =====================================================================
--  Oweili · dashboards_update.sql — the database parts for the Artist,
--  Buyer and Admin dashboards and the later Bashro connection.
--  Approved by the owner on 30 Oct 2026.
--
--  Only ADDS things: new columns, new tables, new indexes. Nothing is
--  deleted or renamed, existing pages keep working. Safe to run more than
--  once (every step uses IF NOT EXISTS). Run with db_update.php (which
--  makes a full backup first) or paste into phpMyAdmin.
--
--  Permanent IDs: public_id is a UUID made by the database itself for
--  every new row (DEFAULT UUID()), so no page has to remember to make one.
--  Status of people and artworks is worked out from the existing columns
--  (see platform_lib.php), so there is only ever one place it is stored.
-- =====================================================================

SET NAMES utf8mb4;

-- ---------------------------------------------------------------------
--  People (artists, buyers, admins)
-- ---------------------------------------------------------------------
ALTER TABLE `users`
  ADD COLUMN IF NOT EXISTS `public_id`        CHAR(36)     NOT NULL DEFAULT (UUID()),
  ADD COLUMN IF NOT EXISTS `updated_at`       TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  ADD COLUMN IF NOT EXISTS `country`          VARCHAR(80)  NULL DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `preferred_lang`   ENUM('en','ar') NULL DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `art_styles`       VARCHAR(500) NULL DEFAULT NULL,      -- style ids from art_styles.js, comma separated
  ADD COLUMN IF NOT EXISTS `notify_inquiries` TINYINT(1)   NOT NULL DEFAULT 1,     -- email me about interest in my work
  ADD COLUMN IF NOT EXISTS `notify_news`      TINYINT(1)   NOT NULL DEFAULT 1;     -- email me Oweili news
ALTER TABLE `users` ADD UNIQUE INDEX IF NOT EXISTS `uq_users_public_id` (`public_id`);
ALTER TABLE `users` ADD INDEX IF NOT EXISTS `idx_users_updated` (`updated_at`);

-- ---------------------------------------------------------------------
--  Artworks
-- ---------------------------------------------------------------------
ALTER TABLE `artworks`
  ADD COLUMN IF NOT EXISTS `public_id`       CHAR(36)     NOT NULL DEFAULT (UUID()),
  ADD COLUMN IF NOT EXISTS `updated_at`      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  ADD COLUMN IF NOT EXISTS `rejected_reason` VARCHAR(500) NULL DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `rejected_at`     TIMESTAMP    NULL DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `view_count`      INT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `artworks` ADD UNIQUE INDEX IF NOT EXISTS `uq_artworks_public_id` (`public_id`);
ALTER TABLE `artworks` ADD INDEX IF NOT EXISTS `idx_artworks_updated` (`updated_at`);

-- ---------------------------------------------------------------------
--  "I'm Interested" requests (the table is also made by inquiry_lib.php)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `artwork_inquiries` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `artwork_id`  INT UNSIGNED NOT NULL,
  `artist_id`   INT UNSIGNED NOT NULL,
  `buyer_name`  VARCHAR(120) NOT NULL,
  `buyer_phone` VARCHAR(40)  NOT NULL,
  `buyer_email` VARCHAR(190) NOT NULL,
  `created_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_inq_artwork` (`artwork_id`),
  KEY `idx_inq_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
ALTER TABLE `artwork_inquiries`
  ADD COLUMN IF NOT EXISTS `public_id`   CHAR(36)        NOT NULL DEFAULT (UUID()),
  ADD COLUMN IF NOT EXISTS `buyer_id`    BIGINT UNSIGNED NULL DEFAULT NULL,      -- the buyer account, when signed in
  ADD COLUMN IF NOT EXISTS `status`      ENUM('new','contacted','in_progress','closed') NOT NULL DEFAULT 'new',
  ADD COLUMN IF NOT EXISTS `admin_notes` TEXT            NULL DEFAULT NULL,      -- private, admin only
  ADD COLUMN IF NOT EXISTS `updated_at`  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;
ALTER TABLE `artwork_inquiries` ADD UNIQUE INDEX IF NOT EXISTS `uq_inq_public_id` (`public_id`);
ALTER TABLE `artwork_inquiries` ADD INDEX IF NOT EXISTS `idx_inq_buyer` (`buyer_id`);
ALTER TABLE `artwork_inquiries` ADD INDEX IF NOT EXISTS `idx_inq_artist` (`artist_id`);
ALTER TABLE `artwork_inquiries` ADD INDEX IF NOT EXISTS `idx_inq_updated` (`updated_at`);

-- ---------------------------------------------------------------------
--  Sales (the purchases table already exists; it gets an ID and dates)
-- ---------------------------------------------------------------------
ALTER TABLE `purchases`
  ADD COLUMN IF NOT EXISTS `public_id`  CHAR(36)  NOT NULL DEFAULT (UUID()),
  ADD COLUMN IF NOT EXISTS `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;
ALTER TABLE `purchases` ADD UNIQUE INDEX IF NOT EXISTS `uq_purchases_public_id` (`public_id`);

-- ---------------------------------------------------------------------
--  Favorites: who saved which artwork, and when
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `favorites` (
  `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `user_id`    BIGINT UNSIGNED NOT NULL,
  `artwork_id` BIGINT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_fav_user_artwork` (`user_id`, `artwork_id`),
  KEY `idx_fav_artwork` (`artwork_id`),
  KEY `idx_fav_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
--  Activity log: who did what and when (artists, buyers, admin)
--  ip_hash is a one-way code of the visitor's address that changes every
--  month: enough to slow down abuse, never the address itself (PDPL)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `activity_log` (
  `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `public_id`   CHAR(36)        NOT NULL DEFAULT (UUID()),
  `actor_type`  ENUM('artist','buyer','admin','sub_admin','institute','guest','system') NOT NULL DEFAULT 'guest',
  `actor_id`    BIGINT UNSIGNED NULL DEFAULT NULL,
  `action`      VARCHAR(60)     NOT NULL,
  `target_type` VARCHAR(30)     NULL DEFAULT NULL,
  `target_id`   BIGINT UNSIGNED NULL DEFAULT NULL,
  `details`     VARCHAR(500)    NULL DEFAULT NULL,
  `ip_hash`     CHAR(64)        NULL DEFAULT NULL,
  `created_at`  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_log_public_id` (`public_id`),
  KEY `idx_log_actor` (`actor_type`, `actor_id`, `created_at`),
  KEY `idx_log_target` (`target_type`, `target_id`),
  KEY `idx_log_action` (`action`, `created_at`),
  KEY `idx_log_ip` (`ip_hash`, `action`, `created_at`),
  KEY `idx_log_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
--  Ready for later (payments come last, in Sprint 4): empty for now
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `shipping_addresses` (
  `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `public_id`   CHAR(36)        NOT NULL DEFAULT (UUID()),
  `user_id`     BIGINT UNSIGNED NOT NULL,
  `full_name`   VARCHAR(150)    NOT NULL,
  `phone`       VARCHAR(40)     NOT NULL,
  `country`     VARCHAR(80)     NOT NULL,
  `city`        VARCHAR(100)    NOT NULL,
  `district`    VARCHAR(100)    NULL DEFAULT NULL,
  `street`      VARCHAR(255)    NOT NULL,
  `postal_code` VARCHAR(20)     NULL DEFAULT NULL,
  `is_default`  TINYINT(1)      NOT NULL DEFAULT 0,
  `status`      ENUM('active','archived') NOT NULL DEFAULT 'active',
  `created_at`  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_ship_public_id` (`public_id`),
  KEY `idx_ship_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `payouts` (
  `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `public_id`   CHAR(36)        NOT NULL DEFAULT (UUID()),
  `artist_id`   BIGINT UNSIGNED NOT NULL,
  `amount`      DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
  `currency`    CHAR(3)         NOT NULL DEFAULT 'SAR',
  `status`      ENUM('pending','paid','failed','cancelled') NOT NULL DEFAULT 'pending',
  `reference`   VARCHAR(120)    NULL DEFAULT NULL,
  `created_at`  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_payout_public_id` (`public_id`),
  KEY `idx_payout_artist` (`artist_id`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
