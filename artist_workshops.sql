-- =====================================================================
--  artist_workshops.sql — artist workshops (owner, 25 Nov 2026).
--  Built HIDDEN behind the switch "Artist workshops" (Admin → Settings →
--  Hidden for now; site_settings artist_workshops_on).
--  Only ADDS three new tables. Nothing is changed or deleted.
--  Safe to run more than once.
--
--  artist_workshop_requests  an artist asks to offer workshops; the owner
--                            approves or refuses with a reason
--  artist_workshops          one workshop of an approved artist; every new
--                            or changed workshop waits for the owner
--  workshop_bookings         "Book your seat" for a workshop; the artist
--                            sees only first names and the number of seats,
--                            Oweili handles contact and payment
-- =====================================================================

CREATE TABLE IF NOT EXISTS `artist_workshop_requests` (
  `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `artist_id`   BIGINT UNSIGNED NOT NULL,
  `subjects`    VARCHAR(500)    NOT NULL DEFAULT '',
  `experience`  TEXT            NULL,
  `plan`        TEXT            NULL,
  `mode`        VARCHAR(12)     NOT NULL DEFAULT 'in_person',   -- in_person / online / both
  `city`        VARCHAR(80)     NOT NULL DEFAULT '',
  `status`      VARCHAR(12)     NOT NULL DEFAULT 'pending',     -- pending / approved / refused
  `reason`      VARCHAR(500)    NOT NULL DEFAULT '',
  `decided_at`  DATETIME        NULL DEFAULT NULL,
  `created_at`  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_wsr_artist` (`artist_id`, `id`),
  KEY `idx_wsr_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `artist_workshops` (
  `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `artist_id`   BIGINT UNSIGNED NOT NULL,
  `title_en`    VARCHAR(200)    NOT NULL DEFAULT '',
  `title_ar`    VARCHAR(200)    NOT NULL DEFAULT '',
  `desc_en`     TEXT            NULL,
  `desc_ar`     TEXT            NULL,
  `level`       VARCHAR(14)     NOT NULL DEFAULT 'beginner',    -- beginner / intermediate / advanced / all
  `mode`        VARCHAR(12)     NOT NULL DEFAULT 'in_person',   -- in_person / online
  `city`        VARCHAR(80)     NOT NULL DEFAULT '',
  `start_date`  DATE            NULL DEFAULT NULL,
  `end_date`    DATE            NULL DEFAULT NULL,
  `schedule_en` VARCHAR(200)    NOT NULL DEFAULT '',
  `schedule_ar` VARCHAR(200)    NOT NULL DEFAULT '',
  `hours`       DECIMAL(5,1)    NOT NULL DEFAULT 0.0,
  `price`       DECIMAL(10,2)   NOT NULL DEFAULT 0.00,
  `seats`       INT UNSIGNED    NOT NULL DEFAULT 0,
  `materials`   TINYINT(1)      NOT NULL DEFAULT 0,
  `status`      VARCHAR(12)     NOT NULL DEFAULT 'pending',     -- pending / approved / refused / hidden
  `reason`      VARCHAR(500)    NOT NULL DEFAULT '',
  `decided_at`  DATETIME        NULL DEFAULT NULL,
  `created_at`  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ws_artist` (`artist_id`),
  KEY `idx_ws_status` (`status`, `start_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `workshop_bookings` (
  `id`                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `workshop_id`       BIGINT UNSIGNED NOT NULL,
  `ref_no`            VARCHAR(20)     NOT NULL,
  `name`              VARCHAR(150)    NOT NULL DEFAULT '',
  `phone`             VARCHAR(30)     NOT NULL DEFAULT '',
  `email`             VARCHAR(190)    NOT NULL DEFAULT '',
  `lang`              CHAR(2)         NOT NULL DEFAULT 'en',
  `amount`            DECIMAL(10,2)   NOT NULL DEFAULT 0.00,
  `commission_rate`   DECIMAL(5,2)    NOT NULL DEFAULT 10.00,
  `commission_amount` DECIMAL(10,2)   NOT NULL DEFAULT 0.00,
  `artist_amount`     DECIMAL(10,2)   NOT NULL DEFAULT 0.00,
  `status`            VARCHAR(12)     NOT NULL DEFAULT 'booked',  -- booked / paid / cancelled
  `pay_ref`           VARCHAR(120)    NOT NULL DEFAULT '',
  `paid_at`           DATETIME        NULL DEFAULT NULL,
  `cancelled_at`      DATETIME        NULL DEFAULT NULL,
  `created_at`        TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_wsb_ref` (`ref_no`),
  UNIQUE KEY `uq_wsb_email` (`workshop_id`, `email`),
  KEY `idx_wsb_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
