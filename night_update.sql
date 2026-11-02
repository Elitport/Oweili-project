-- =====================================================================
--  Oweili · night_update.sql — the database parts for (2 Nov 2026):
--    Gallery variety: curated collections, Artist of the week, Verified artist
--    Oweili Gifts: gift requests
--    Private commissions: requests, private files, confidentiality acceptances
--    Orders and escrow: READY BUT SWITCHED OFF (no payment provider connected)
--  Approved by the owner on 2 Nov 2026 ("any database change on this list is
--  approved, as long as it only adds things ... full backup first").
--
--  Only ADDS things: new tables and new columns. Nothing is changed, renamed
--  or deleted. Safe to run more than once (IF NOT EXISTS). Until it is run,
--  every page keeps working as before and the new parts stay hidden.
-- =====================================================================

SET NAMES utf8mb4;

-- ---------------------------------------------------------------------
--  Small settings the admin changes on the site (Artist of the week,
--  commission rate, payments on/off). One row per setting.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `site_settings` (
  `setting_key`   VARCHAR(64)  NOT NULL,
  `setting_value` TEXT         NULL,
  `updated_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
--  "Verified artist" badge, given by the admin after checking an artist
-- ---------------------------------------------------------------------
ALTER TABLE `users`
  ADD COLUMN IF NOT EXISTS `is_verified_artist` TINYINT(1) NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS `verified_artist_at` DATETIME   NULL DEFAULT NULL;

-- ---------------------------------------------------------------------
--  Curated collections made in admin ("Under 1,000 SAR", "Calligraphy",
--  "Graduation gifts"). A collection shows the artworks picked by hand,
--  plus (optionally) every visible artwork that matches its rule.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `collections` (
  `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `slug`        VARCHAR(120)    NOT NULL,
  `title_en`    VARCHAR(150)    NOT NULL DEFAULT '',
  `title_ar`    VARCHAR(150)    NOT NULL DEFAULT '',
  `intro_en`    VARCHAR(500)    NOT NULL DEFAULT '',
  `intro_ar`    VARCHAR(500)    NOT NULL DEFAULT '',
  `rule_max_price` DECIMAL(10,2) NULL DEFAULT NULL,      -- e.g. 1000 = "Under 1,000 SAR"
  `rule_min_price` DECIMAL(10,2) NULL DEFAULT NULL,
  `rule_type`   VARCHAR(60)     NOT NULL DEFAULT '',     -- an art type id, e.g. calligraphy
  `is_active`   TINYINT(1)      NOT NULL DEFAULT 1,
  `sort_order`  INT             NOT NULL DEFAULT 0,
  `created_at`  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_collections_slug` (`slug`),
  KEY `idx_collections_active` (`is_active`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `collection_items` (
  `collection_id` BIGINT UNSIGNED NOT NULL,
  `artwork_id`    BIGINT UNSIGNED NOT NULL,
  `sort_order`    INT             NOT NULL DEFAULT 0,
  `added_at`      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`collection_id`, `artwork_id`),
  KEY `idx_collection_items_artwork` (`artwork_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
--  Oweili Gifts: "Gift a painting instead of flowers". A request form,
--  followed up by hand by the admin. No payment.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `gift_requests` (
  `id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `public_id`       CHAR(36)        NOT NULL DEFAULT (UUID()),
  `user_id`         BIGINT UNSIGNED NULL DEFAULT NULL,      -- the buyer's account, when signed in
  `occasion`        VARCHAR(30)     NOT NULL DEFAULT 'other',
  `mode`            ENUM('ready','custom') NOT NULL DEFAULT 'ready',
  `artwork_id`      BIGINT UNSIGNED NULL DEFAULT NULL,      -- a ready artwork from the Gallery
  `custom_brief`    TEXT            NULL,                   -- what a custom piece should be
  `budget_sar`      DECIMAL(10,2)   NULL DEFAULT NULL,
  `wrapping`        TINYINT(1)      NOT NULL DEFAULT 1,
  `card_message`    VARCHAR(500)    NOT NULL DEFAULT '',
  `delivery_date`   DATE            NULL DEFAULT NULL,
  `surprise`        TINYINT(1)      NOT NULL DEFAULT 1,     -- the recipient never sees the price
  `recipient_name`  VARCHAR(150)    NOT NULL DEFAULT '',
  `recipient_phone` VARCHAR(30)     NOT NULL DEFAULT '',
  `recipient_city`  VARCHAR(100)    NOT NULL DEFAULT '',
  `recipient_address` VARCHAR(600)  NOT NULL DEFAULT '',
  `buyer_name`      VARCHAR(150)    NOT NULL DEFAULT '',
  `buyer_email`     VARCHAR(190)    NOT NULL DEFAULT '',
  `buyer_phone`     VARCHAR(30)     NOT NULL DEFAULT '',
  `lang`            CHAR(2)         NOT NULL DEFAULT 'en',
  `status`          ENUM('new','contacted','in_progress','done','cancelled') NOT NULL DEFAULT 'new',
  `admin_note`      TEXT            NULL,
  `created_at`      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_gift_public_id` (`public_id`),
  KEY `idx_gift_status` (`status`, `created_at`),
  KEY `idx_gift_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
--  Private commissions (portraits and personal photos), confidential.
--  Reference photos live in a PRIVATE folder (.private/commissions/),
--  never in uploads/, opened only through commission_file.php.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `commissions` (
  `id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `public_id`       CHAR(36)        NOT NULL DEFAULT (UUID()),
  `ref_no`          VARCHAR(20)     NOT NULL,                -- e.g. C-2026-7KQ4, shown to the buyer
  `access_hash`     CHAR(64)        NOT NULL,                -- SHA-256 of the buyer's private link key
  `user_id`         BIGINT UNSIGNED NULL DEFAULT NULL,       -- the buyer's account, when signed in
  `buyer_name`      VARCHAR(150)    NOT NULL DEFAULT '',
  `buyer_email`     VARCHAR(190)    NOT NULL DEFAULT '',
  `buyer_phone`     VARCHAR(30)     NOT NULL DEFAULT '',
  `lang`            CHAR(2)         NOT NULL DEFAULT 'en',
  `subject`         VARCHAR(30)     NOT NULL DEFAULT 'portrait',
  `brief`           TEXT            NULL,
  `art_style`       VARCHAR(60)     NOT NULL DEFAULT '',
  `size_note`       VARCHAR(120)    NOT NULL DEFAULT '',
  `budget_sar`      DECIMAL(10,2)   NULL DEFAULT NULL,
  `wanted_by`       DATE            NULL DEFAULT NULL,
  `may_show`        TINYINT(1)      NOT NULL DEFAULT 0,      -- buyer agreed in writing that it may be shown
  `may_show_text`   VARCHAR(300)    NOT NULL DEFAULT '',     -- the words the buyer typed
  `may_show_at`     DATETIME        NULL DEFAULT NULL,
  `artist_id`       BIGINT UNSIGNED NULL DEFAULT NULL,       -- the assigned artist
  `assigned_at`     DATETIME        NULL DEFAULT NULL,
  `status`          ENUM('requested','assigned','quoted','quote_accepted','draft_sent','draft_approved','final_sent','delivered','cancelled') NOT NULL DEFAULT 'requested',
  `quote_sar`       DECIMAL(10,2)   NULL DEFAULT NULL,
  `quote_days`      INT UNSIGNED    NULL DEFAULT NULL,
  `quote_note`      VARCHAR(1000)   NOT NULL DEFAULT '',
  `quoted_at`       DATETIME        NULL DEFAULT NULL,
  `buyer_note`      VARCHAR(1000)   NOT NULL DEFAULT '',     -- the buyer's last message (changes asked for)
  `delivered_at`    DATETIME        NULL DEFAULT NULL,
  `photos_deleted_at` DATETIME      NULL DEFAULT NULL,       -- reference photos removed (30 days after delivery)
  `admin_note`      TEXT            NULL,
  `created_at`      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_commission_public_id` (`public_id`),
  UNIQUE KEY `uq_commission_ref` (`ref_no`),
  KEY `idx_commission_status` (`status`, `created_at`),
  KEY `idx_commission_artist` (`artist_id`),
  KEY `idx_commission_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `commission_files` (
  `id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `commission_id`   BIGINT UNSIGNED NOT NULL,
  `kind`            ENUM('reference','draft','final') NOT NULL,
  `file_name`       VARCHAR(80)     NOT NULL,                -- random name inside .private/commissions/
  `mime`            VARCHAR(40)     NOT NULL DEFAULT 'image/jpeg',
  `bytes`           INT UNSIGNED    NOT NULL DEFAULT 0,
  `uploaded_by`     VARCHAR(20)     NOT NULL DEFAULT 'buyer', -- buyer / artist / admin
  `created_at`      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at`      DATETIME        NULL DEFAULT NULL,       -- the file is gone, the row stays as a record
  PRIMARY KEY (`id`),
  KEY `idx_commission_files` (`commission_id`, `kind`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `commission_nda` (
  `id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `commission_id`   BIGINT UNSIGNED NOT NULL,
  `artist_id`       BIGINT UNSIGNED NOT NULL,
  `version`         VARCHAR(20)     NOT NULL DEFAULT '1',
  `typed_name`      VARCHAR(150)    NOT NULL DEFAULT '',     -- the artist types their name to accept
  `accepted_at`     DATETIME        NOT NULL,
  `ip_code`         CHAR(16)        NOT NULL DEFAULT '',     -- monthly one-way code of the address (never the address)
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_commission_nda` (`commission_id`, `artist_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
--  Orders and escrow — READY BUT SWITCHED OFF. No payment provider is
--  connected. Money is held by Oweili and released to the artist after the
--  buyer confirms receipt, or 7 days after delivery with no complaint,
--  minus Oweili's commission (site_settings 'commission_rate').
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `orders` (
  `id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `public_id`       CHAR(36)        NOT NULL DEFAULT (UUID()),
  `order_no`        VARCHAR(20)     NOT NULL,                -- e.g. O-2026-7KQ4M9
  `kind`            ENUM('artwork','gift','commission','course','workshop') NOT NULL,
  `ref_id`          BIGINT UNSIGNED NULL DEFAULT NULL,       -- artwork / gift / commission / course id
  `buyer_user_id`   BIGINT UNSIGNED NULL DEFAULT NULL,
  `buyer_name`      VARCHAR(150)    NOT NULL DEFAULT '',
  `buyer_email`     VARCHAR(190)    NOT NULL DEFAULT '',
  `buyer_phone`     VARCHAR(30)     NOT NULL DEFAULT '',
  `artist_id`       BIGINT UNSIGNED NULL DEFAULT NULL,       -- who is paid in the end (none for courses)
  `title`           VARCHAR(255)    NOT NULL DEFAULT '',
  `amount`          DECIMAL(12,2)   NOT NULL DEFAULT 0.00,   -- what the buyer pays (before VAT)
  `vat_amount`      DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
  `commission_rate` DECIMAL(5,2)    NOT NULL DEFAULT 15.00,  -- copied from the setting when the order is made
  `commission_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `artist_amount`   DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
  `currency`        CHAR(3)         NOT NULL DEFAULT 'SAR',
  `status`          ENUM('created','paid','shipped','delivered','complaint','released','refunded','cancelled') NOT NULL DEFAULT 'created',
  `gateway`         VARCHAR(30)     NOT NULL DEFAULT '',     -- later: moyasar / tap / hyperpay
  `gateway_ref`     VARCHAR(120)    NOT NULL DEFAULT '',
  `paid_at`         DATETIME        NULL DEFAULT NULL,
  `shipped_at`      DATETIME        NULL DEFAULT NULL,
  `delivered_at`    DATETIME        NULL DEFAULT NULL,
  `confirmed_at`    DATETIME        NULL DEFAULT NULL,       -- the buyer pressed "I received it"
  `release_due_at`  DATETIME        NULL DEFAULT NULL,       -- delivered_at + 7 days
  `released_at`     DATETIME        NULL DEFAULT NULL,
  `created_at`      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_orders_public_id` (`public_id`),
  UNIQUE KEY `uq_orders_no` (`order_no`),
  KEY `idx_orders_status` (`status`, `release_due_at`),
  KEY `idx_orders_buyer` (`buyer_user_id`),
  KEY `idx_orders_artist` (`artist_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `order_events` (
  `id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id`        BIGINT UNSIGNED NOT NULL,
  `from_status`     VARCHAR(20)     NOT NULL DEFAULT '',
  `to_status`       VARCHAR(20)     NOT NULL DEFAULT '',
  `actor_type`      VARCHAR(20)     NOT NULL DEFAULT 'system',
  `actor_id`        BIGINT UNSIGNED NULL DEFAULT NULL,
  `note`            VARCHAR(500)    NOT NULL DEFAULT '',
  `created_at`      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_order_events` (`order_id`, `id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `order_complaints` (
  `id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id`        BIGINT UNSIGNED NOT NULL,
  `buyer_user_id`   BIGINT UNSIGNED NULL DEFAULT NULL,
  `reason`          TEXT            NULL,
  `status`          ENUM('open','released','refunded') NOT NULL DEFAULT 'open',
  `resolution_note` VARCHAR(1000)   NOT NULL DEFAULT '',
  `created_at`      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `resolved_at`     DATETIME        NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_order_complaints` (`order_id`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- payouts (made by dashboards_update.sql) gains the order it pays for
ALTER TABLE `payouts`
  ADD COLUMN IF NOT EXISTS `order_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `note`     VARCHAR(500)    NOT NULL DEFAULT '';
