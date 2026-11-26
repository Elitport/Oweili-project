-- =====================================================================
--  free_entry.sql — make free things easy to find (owner, 27 Nov 2026).
--  Only ADDS columns. Nothing is changed or deleted. Safe to run more than once.
--
--  art_places  entry_kind     '' = not set (shows nothing) / free / paid / booking
--              entry_price    the price in SAR when entry is paid
--              entry_source   the official source link (optional)
--              entry_checked  the date the entry was last checked (optional)
--  articles    free_entry     Art Events: 1 = free entry
-- =====================================================================

ALTER TABLE `art_places`
  ADD COLUMN IF NOT EXISTS `entry_kind`    VARCHAR(10)   NOT NULL DEFAULT '',
  ADD COLUMN IF NOT EXISTS `entry_price`   DECIMAL(10,2) NULL DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `entry_source`  VARCHAR(500)  NOT NULL DEFAULT '',
  ADD COLUMN IF NOT EXISTS `entry_checked` DATE          NULL DEFAULT NULL;

ALTER TABLE `articles`
  ADD COLUMN IF NOT EXISTS `free_entry` TINYINT(1) NOT NULL DEFAULT 0;
