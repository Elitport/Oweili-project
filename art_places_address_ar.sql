-- =====================================================================
--  art_places_address_ar.sql — the Art Map address in Arabic (owner, 13 Nov 2026).
--  Only adds one column; safe to run more than once. Arabic pages show
--  address_ar when it is filled in, else the English address.
-- =====================================================================
ALTER TABLE `art_places` ADD COLUMN IF NOT EXISTS `address_ar` VARCHAR(300) NOT NULL DEFAULT '' AFTER `address`;
