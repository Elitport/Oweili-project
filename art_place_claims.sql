-- =====================================================================
--  art_place_claims.sql — "Is this your place?" messages from the Art Map
--  (owner, 13 Nov 2026). Only adds one table; safe to run more than once.
--  A museum, gallery or institute sends its own photo and corrections from
--  place_claim.php. Nothing shows publicly: the admin reads each message in
--  admin_art_map.php (Admin → Waiting for approval) and approves or rejects
--  it. The photo waits in the private folder .private/place-claims/ until it
--  is approved; on approval it becomes the place's photo.
--  place_kind: 'place' = art_places.id, 'institute' = institutes.id
-- =====================================================================
CREATE TABLE IF NOT EXISTS `art_place_claims` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `place_kind`  VARCHAR(12)  NOT NULL DEFAULT 'place',
  `place_id`    INT UNSIGNED NOT NULL,
  `place_name`  VARCHAR(200) NOT NULL DEFAULT '',
  `name`        VARCHAR(120) NOT NULL DEFAULT '',
  `email`       VARCHAR(160) NOT NULL DEFAULT '',
  `phone`       VARCHAR(30)  NOT NULL DEFAULT '',
  `message`     TEXT NULL,
  `photo_file`  VARCHAR(80)  NOT NULL DEFAULT '',          -- .private/place-claims/<name>.jpg, '' = no photo
  `photo_credit` VARCHAR(200) NOT NULL DEFAULT '',
  `lang`        VARCHAR(2)   NOT NULL DEFAULT 'en',
  `status`      VARCHAR(10)  NOT NULL DEFAULT 'new',       -- new / approved / rejected
  `created_at`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `decided_at`  DATETIME     NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_claims_status` (`status`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
