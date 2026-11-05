-- =====================================================================
--  sizes_update.sql — standard artwork sizes (5 Nov 2026, approved by the owner)
--  ONLY ADDS columns and indexes; nothing is removed or changed. Safe to run
--  more than once (IF NOT EXISTS). Width and height in cm stay as they are.
--    size_code      e.g. '50x70' (the standard size, smaller side first)
--    size_category  xs / s / m / l / xl
--    orientation    portrait / landscape / square
-- =====================================================================
ALTER TABLE artworks
  ADD COLUMN IF NOT EXISTS size_code VARCHAR(10) NULL DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS size_category VARCHAR(4) NULL DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS orientation VARCHAR(10) NULL DEFAULT NULL;
ALTER TABLE artworks ADD INDEX IF NOT EXISTS idx_artworks_size (size_category, orientation);

ALTER TABLE commissions
  ADD COLUMN IF NOT EXISTS size_code VARCHAR(10) NULL DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS size_category VARCHAR(4) NULL DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS orientation VARCHAR(10) NULL DEFAULT NULL;

ALTER TABLE gift_requests
  ADD COLUMN IF NOT EXISTS size_code VARCHAR(10) NULL DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS size_category VARCHAR(4) NULL DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS orientation VARCHAR(10) NULL DEFAULT NULL;
