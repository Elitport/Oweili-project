-- =====================================================================
--  Oweili · certificates_update.sql — the Certificate of Authenticity.
--  Approved by the owner on 1 Nov 2026 ("any database change is approved
--  as long as it only adds things").
--
--  Only ADDS things: new columns on artworks and one new table. Nothing is
--  changed or deleted. Safe to run more than once (IF NOT EXISTS). Until it
--  is run, every page works as before and no certificates are made.
-- =====================================================================

SET NAMES utf8mb4;

-- ---------------------------------------------------------------------
--  Artwork details for the certificate + the artist's declaration
-- ---------------------------------------------------------------------
ALTER TABLE `artworks`
  ADD COLUMN IF NOT EXISTS `medium`         VARCHAR(150)    NULL DEFAULT NULL,   -- e.g. "Oil on canvas"
  ADD COLUMN IF NOT EXISTS `width_cm`       DECIMAL(7,1)    NULL DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `height_cm`      DECIMAL(7,1)    NULL DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `year_made`      SMALLINT UNSIGNED NULL DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `edition_type`   ENUM('original','limited') NULL DEFAULT NULL,  -- one of a kind, or a limited print
  ADD COLUMN IF NOT EXISTS `edition_number` INT UNSIGNED    NULL DEFAULT NULL,   -- e.g. 3 (of 50)
  ADD COLUMN IF NOT EXISTS `edition_size`   INT UNSIGNED    NULL DEFAULT NULL,   -- e.g. 50
  ADD COLUMN IF NOT EXISTS `declared_at`    DATETIME        NULL DEFAULT NULL;   -- when the artist confirmed "my original work, made by my own hand"

-- ---------------------------------------------------------------------
--  Certificates: one per approved artwork. The details are kept as they
--  were when the certificate was issued (snapshot), so the certificate
--  never changes by accident. Never deleted: a certificate can only be
--  cancelled, with a reason.
--  owner_*: ready for later (the buyer's name once a sale is made).
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `certificates` (
  `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `cert_no`        VARCHAR(24)     NOT NULL,                   -- e.g. OW-2026-7KQ4-M9XD (printed, typed on the verify page)
  `public_id`      CHAR(36)        NOT NULL DEFAULT (UUID()),
  `artwork_id`     BIGINT UNSIGNED NOT NULL,
  `artist_id`      BIGINT UNSIGNED NOT NULL,
  `snapshot`       MEDIUMTEXT      NOT NULL,                   -- the certified details (JSON)
  `status`         ENUM('valid','cancelled') NOT NULL DEFAULT 'valid',
  `issued_at`      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `issued_by`      BIGINT UNSIGNED NULL DEFAULT NULL,
  `cancelled_at`   TIMESTAMP       NULL DEFAULT NULL,
  `cancel_reason`  VARCHAR(500)    NULL DEFAULT NULL,
  `cancelled_by`   BIGINT UNSIGNED NULL DEFAULT NULL,
  `owner_user_id`  BIGINT UNSIGNED NULL DEFAULT NULL,          -- later: the buyer's account
  `owner_name`     VARCHAR(200)    NULL DEFAULT NULL,          -- later: the buyer's name (never shown on the public verify page)
  `owner_added_at` TIMESTAMP       NULL DEFAULT NULL,
  `created_at`     TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_certificates_no` (`cert_no`),
  UNIQUE KEY `uq_certificates_public_id` (`public_id`),
  KEY `idx_certificates_artwork` (`artwork_id`),
  KEY `idx_certificates_artist` (`artist_id`),
  KEY `idx_certificates_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
