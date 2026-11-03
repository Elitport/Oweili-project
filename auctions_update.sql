-- =====================================================================
--  Oweili · auctions_update.sql — "Oweili Auctions, Coming soon" waiting
--  list (3 Nov 2026). Approved by the owner ("any database change that
--  only adds things is approved; make a full backup first").
--  Only ADDS one table. Safe to run more than once. Until it is run the
--  auctions page shows the teaser without the form, and nothing breaks.
--  One row per email (no duplicates). Unsubscribing deletes the row.
-- =====================================================================
SET NAMES utf8mb4;
CREATE TABLE IF NOT EXISTS `auction_waitlist` (
  `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `public_id`   CHAR(36)        NOT NULL DEFAULT (UUID()),
  `email`       VARCHAR(190)    NOT NULL,                 -- saved in small letters
  `name`        VARCHAR(120)    NOT NULL DEFAULT '',      -- optional
  `lang`        CHAR(2)         NOT NULL DEFAULT 'en',
  `user_id`     BIGINT UNSIGNED NULL DEFAULT NULL,        -- the account, when signed in
  `unsub_token` CHAR(32)        NOT NULL,                 -- random, only lets this one email unsubscribe (in every email)
  `created_at`  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_auction_waitlist_email` (`email`),
  UNIQUE KEY `uq_auction_waitlist_public_id` (`public_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
