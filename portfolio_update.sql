-- =====================================================================
--  Oweili · live database fixes found by the end-to-end test (9 Nov 2026)
--  Only adds; safe to run more than once. Run on the live database after a full backup.
--  1. artworks.sold_at (from financial_setup.sql, which was never run on the live database)
--  2. portfolio_images: the live table is an older version with user_id; the code uses artist_id
--     and sort_order. Both are added and the old user_id is copied into artist_id (user_id stays)
-- =====================================================================
ALTER TABLE `artworks` ADD COLUMN IF NOT EXISTS `sold_at` TIMESTAMP NULL DEFAULT NULL;
ALTER TABLE `portfolio_images` ADD COLUMN IF NOT EXISTS `artist_id` BIGINT UNSIGNED NULL DEFAULT NULL AFTER `id`;
ALTER TABLE `portfolio_images` ADD COLUMN IF NOT EXISTS `sort_order` INT NOT NULL DEFAULT 0;
ALTER TABLE `portfolio_images` ADD INDEX IF NOT EXISTS `idx_portfolio_artist` (`artist_id`);
UPDATE `portfolio_images` SET `artist_id` = `user_id` WHERE `artist_id` IS NULL;
