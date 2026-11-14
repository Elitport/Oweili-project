-- =====================================================================
--  institutes_coords.sql — map coordinates for the older institutes list (owner, 14 Nov 2026).
--  Only adds two columns; safe to run more than once. The Art Map puts a pin on the map
--  for every institute that has both. Same type as art_places.lat / lng.
-- =====================================================================
ALTER TABLE `institutes` ADD COLUMN IF NOT EXISTS `lat` DECIMAL(9,6) NULL DEFAULT NULL;
ALTER TABLE `institutes` ADD COLUMN IF NOT EXISTS `lng` DECIMAL(9,6) NULL DEFAULT NULL;
