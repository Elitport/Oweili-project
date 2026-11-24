-- =====================================================================
--  collector_update.sql — the new collector dashboard (owner, 24 Nov 2026).
--  Only ADDS one new table. Nothing is changed or deleted.
--  Safe to run more than once.
--
--  artist_follows   a signed-in collector follows an artist.
--                   Artists see only how many follow them, never who.
-- =====================================================================

CREATE TABLE IF NOT EXISTS `artist_follows` (
  `collector_id` BIGINT UNSIGNED NOT NULL,
  `artist_id`    BIGINT UNSIGNED NOT NULL,
  `created_at`   TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`collector_id`, `artist_id`),
  KEY `idx_follows_artist` (`artist_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
