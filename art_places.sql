-- =====================================================================
--  art_places.sql — the Art Map's museums, art galleries and art institutes
--  (owner, 12 Nov 2026). Only adds one table; safe to run more than once.
--  Every place starts as a draft (status 'draft'); only the admin publishes
--  it in admin_art_map.php. sources (JSON list of links) and confidence are
--  shown to the admin only, never to visitors. src_ref keeps where a row came
--  from ('old-…' = one of the first 21 drafts, 'imp-N' = row N of the
--  148-place file), so an import never adds the same place twice.
-- =====================================================================
CREATE TABLE IF NOT EXISTS `art_places` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `kind`          VARCHAR(16)  NOT NULL DEFAULT 'museum',   -- museum / gallery / institute
  `status`        VARCHAR(12)  NOT NULL DEFAULT 'draft',    -- draft / published
  `name_en`       VARCHAR(200) NOT NULL DEFAULT '',
  `name_ar`       VARCHAR(200) NOT NULL DEFAULT '',
  `city_en`       VARCHAR(120) NOT NULL DEFAULT '',
  `city_ar`       VARCHAR(120) NOT NULL DEFAULT '',
  `region`        VARCHAR(20)  NOT NULL DEFAULT '',         -- one of the 13 region keys (artmap_lib.php OW_MAP_REGIONS)
  `desc_en`       TEXT NULL,
  `desc_ar`       TEXT NULL,
  `address`       VARCHAR(300) NOT NULL DEFAULT '',
  `phone`         VARCHAR(40)  NOT NULL DEFAULT '',
  `website`       VARCHAR(500) NOT NULL DEFAULT '',
  `instagram`     VARCHAR(200) NOT NULL DEFAULT '',
  `maps_url`      VARCHAR(500) NOT NULL DEFAULT '',
  `lat`           DECIMAL(9,6) NULL DEFAULT NULL,
  `lng`           DECIMAL(9,6) NULL DEFAULT NULL,
  `photo_path`    VARCHAR(200) NOT NULL DEFAULT '',         -- assets/img/places/<name>, .webp and .jpg
  `photo_credit`  VARCHAR(300) NOT NULL DEFAULT '',
  `photo_page`    VARCHAR(500) NOT NULL DEFAULT '',
  `photo_license` VARCHAR(80)  NOT NULL DEFAULT '',
  `sources`       TEXT NULL,                                 -- JSON list of links (admin only)
  `confidence`    VARCHAR(10)  NOT NULL DEFAULT 'medium',    -- high / medium / low (admin only)
  `src_ref`       VARCHAR(40)  NULL DEFAULT NULL,
  `created_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_art_places_src` (`src_ref`),
  KEY `idx_art_places_list` (`kind`, `status`, `region`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
