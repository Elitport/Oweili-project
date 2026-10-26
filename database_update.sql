-- =====================================================================
--  Oweili · database_update.sql — new tables for the 5 pillars
--
--  HOW TO RUN (phpMyAdmin):
--    1. Make a backup first: Export → Quick → Go.
--    2. Select the live database (check whether it is u405344949_reshaart
--       or u405344949_contact — see CLAUDE.md).
--    3. Open the SQL tab, paste this whole file, press Go.
--
--  Safe to run more than once: every table uses IF NOT EXISTS, so tables
--  that already exist are left untouched. Nothing here deletes data.
--  Needs the existing `users` and `artworks` tables (from setup.sql).
-- =====================================================================
SET NAMES utf8mb4;

-- ---------------------------------------------------------------------
--  0. Allow the new Institute Admin account type in users.role
--     (keeps every existing role: artist, collector, admin, sub_admin)
-- ---------------------------------------------------------------------
ALTER TABLE `users`
  MODIFY COLUMN `role` ENUM('artist','collector','admin','sub_admin','institute')
  NOT NULL DEFAULT 'collector';

-- ---------------------------------------------------------------------
--  0b. Drafts: an artwork stays a private draft while the artist collects
--      the 25% / 50% / 100% photos (often days apart). Existing artworks
--      automatically get 0 (= not a draft), so nothing changes for them.
-- ---------------------------------------------------------------------
ALTER TABLE `artworks` ADD COLUMN IF NOT EXISTS `is_draft` TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE `artworks` ADD INDEX IF NOT EXISTS `idx_artworks_draft` (`artist_id`, `is_draft`);

-- ---------------------------------------------------------------------
--  1. ARTWORK STAGES — the 3 proof-of-process photos (25%, 50%, 100%)
--     One photo per stage per artwork.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `artwork_stages` (
  `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `artwork_id`  BIGINT UNSIGNED NOT NULL,            -- FK -> artworks.id
  `stage`       TINYINT UNSIGNED NOT NULL,           -- 25, 50 or 100
  `photo_path`  VARCHAR(500) NOT NULL DEFAULT '',
  `taken_at`    DATETIME NULL DEFAULT NULL,          -- when the photo was taken
  `created_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_stage_per_artwork` (`artwork_id`, `stage`),
  CONSTRAINT `chk_stage_value` CHECK (`stage` IN (25, 50, 100)),
  CONSTRAINT `fk_stages_artwork` FOREIGN KEY (`artwork_id`)
    REFERENCES `artworks` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
--  2. ARTWORK COLORS — colors the artist used in each artwork
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `artwork_colors` (
  `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `artwork_id`     BIGINT UNSIGNED NOT NULL,         -- FK -> artworks.id
  `color_name_en`  VARCHAR(60) NOT NULL DEFAULT '',
  `color_name_ar`  VARCHAR(60) NOT NULL DEFAULT '',
  `hex_code`       CHAR(7) NULL DEFAULT NULL,        -- optional, e.g. #ff0055
  PRIMARY KEY (`id`),
  KEY `idx_colors_artwork` (`artwork_id`),
  CONSTRAINT `chk_hex_code` CHECK (`hex_code` IS NULL OR `hex_code` REGEXP '^#[0-9A-Fa-f]{6}$'),
  CONSTRAINT `fk_colors_artwork` FOREIGN KEY (`artwork_id`)
    REFERENCES `artworks` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
--  3. INSTITUTES — art institutes directory
--     If the institute admin's account is deleted, the institute stays
--     listed (user_id becomes empty) so the admin can reassign it.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `institutes` (
  `id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`         BIGINT UNSIGNED NULL DEFAULT NULL,  -- FK -> users.id (institute admin)
  `name_en`         VARCHAR(200) NOT NULL DEFAULT '',
  `name_ar`         VARCHAR(200) NOT NULL DEFAULT '',
  `city_en`         VARCHAR(120) NOT NULL DEFAULT '',
  `city_ar`         VARCHAR(120) NOT NULL DEFAULT '',
  `description_en`  TEXT NULL,
  `description_ar`  TEXT NULL,
  `logo_path`       VARCHAR(500) NOT NULL DEFAULT '',
  `contact_email`   VARCHAR(190) NOT NULL DEFAULT '',
  `contact_phone`   VARCHAR(30) NOT NULL DEFAULT '',
  `website_url`     VARCHAR(500) NOT NULL DEFAULT '',
  `is_active`       TINYINT(1) NOT NULL DEFAULT 1,
  `created_at`      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_institutes_user` (`user_id`),
  KEY `idx_institutes_active` (`is_active`),
  CONSTRAINT `fk_institutes_user` FOREIGN KEY (`user_id`)
    REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
--  4. COURSES — offered by each institute
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `courses` (
  `id`               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `institute_id`     BIGINT UNSIGNED NOT NULL,        -- FK -> institutes.id
  `title_en`         VARCHAR(200) NOT NULL DEFAULT '',
  `title_ar`         VARCHAR(200) NOT NULL DEFAULT '',
  `description_en`   TEXT NULL,
  `description_ar`   TEXT NULL,
  `start_date`       DATE NULL DEFAULT NULL,
  `end_date`         DATE NULL DEFAULT NULL,
  `price`            DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `currency`         CHAR(3) NOT NULL DEFAULT 'SAR',
  `seats_total`      INT UNSIGNED NOT NULL DEFAULT 0,
  `seats_remaining`  INT UNSIGNED NOT NULL DEFAULT 0,
  `is_active`        TINYINT(1) NOT NULL DEFAULT 1,
  `created_at`       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_courses_institute` (`institute_id`),
  KEY `idx_courses_start` (`start_date`),
  CONSTRAINT `chk_course_seats` CHECK (`seats_remaining` <= `seats_total`),
  CONSTRAINT `chk_course_dates` CHECK (`end_date` IS NULL OR `start_date` IS NULL OR `end_date` >= `start_date`),
  CONSTRAINT `fk_courses_institute` FOREIGN KEY (`institute_id`)
    REFERENCES `institutes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
--  5. COURSE REGISTRATIONS — who signed up for each course
--     One registration per person per course.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `course_registrations` (
  `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `course_id`      BIGINT UNSIGNED NOT NULL,          -- FK -> courses.id
  `user_id`        BIGINT UNSIGNED NOT NULL,          -- FK -> users.id
  `registered_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status`         ENUM('pending','confirmed','cancelled') NOT NULL DEFAULT 'pending',
  `notes`          TEXT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_registration` (`course_id`, `user_id`),
  KEY `idx_registrations_user` (`user_id`),
  CONSTRAINT `fk_registrations_course` FOREIGN KEY (`course_id`)
    REFERENCES `courses` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_registrations_user` FOREIGN KEY (`user_id`)
    REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
--  6. ARTICLES — events, artist spotlights and art education
--     slug = clean web address, e.g. "arabic-calligraphy" (rule 7)
--     event_* columns are only filled for type = 'event'
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `articles` (
  `id`                 BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `type`               ENUM('event','artist_spotlight','art_education') NOT NULL,
  `slug`               VARCHAR(190) NOT NULL,
  `title_en`           VARCHAR(255) NOT NULL DEFAULT '',
  `title_ar`           VARCHAR(255) NOT NULL DEFAULT '',
  `body_en`            LONGTEXT NULL,
  `body_ar`            LONGTEXT NULL,
  `cover_image_path`   VARCHAR(500) NOT NULL DEFAULT '',
  `author_user_id`     BIGINT UNSIGNED NULL DEFAULT NULL,  -- FK -> users.id (Oweili team member)
  `status`             ENUM('draft','published') NOT NULL DEFAULT 'draft',
  `published_at`       DATETIME NULL DEFAULT NULL,
  `event_start_date`   DATE NULL DEFAULT NULL,
  `event_end_date`     DATE NULL DEFAULT NULL,
  `event_location_en`  VARCHAR(255) NOT NULL DEFAULT '',
  `event_location_ar`  VARCHAR(255) NOT NULL DEFAULT '',
  `created_at`         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_articles_slug` (`slug`),
  KEY `idx_articles_type_status` (`type`, `status`),
  KEY `idx_articles_event_date` (`event_start_date`),
  CONSTRAINT `fk_articles_author` FOREIGN KEY (`author_user_id`)
    REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
--  7. ARTICLE IMAGES — extra photos for each article
--     Alt text in both languages (rule 5).
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `article_images` (
  `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `article_id`  BIGINT UNSIGNED NOT NULL,            -- FK -> articles.id
  `image_path`  VARCHAR(500) NOT NULL DEFAULT '',
  `alt_en`      VARCHAR(255) NOT NULL DEFAULT '',
  `alt_ar`      VARCHAR(255) NOT NULL DEFAULT '',
  `sort_order`  INT NOT NULL DEFAULT 0,
  `created_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_article_images_article` (`article_id`, `sort_order`),
  CONSTRAINT `fk_article_images_article` FOREIGN KEY (`article_id`)
    REFERENCES `articles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
--  8. PHOTO CONSENTS — proof that a local artist approved their photo
--     Kept even if the article is deleted (article_id becomes empty),
--     because it is a legal record.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `photo_consents` (
  `id`                 BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `article_id`         BIGINT UNSIGNED NULL DEFAULT NULL,  -- FK -> articles.id
  `artist_name_en`     VARCHAR(200) NOT NULL DEFAULT '',
  `artist_name_ar`     VARCHAR(200) NOT NULL DEFAULT '',
  `artist_contact`     VARCHAR(190) NOT NULL DEFAULT '',   -- email or phone used to ask
  `consent_method`     ENUM('email','whatsapp','signed_form','in_person','other') NOT NULL,
  `consent_given_at`   DATETIME NOT NULL,
  `proof_path`         VARCHAR(500) NOT NULL DEFAULT '',   -- screenshot or signed form
  `notes`              TEXT NULL,
  `recorded_by`        BIGINT UNSIGNED NULL DEFAULT NULL,  -- FK -> users.id (team member)
  `withdrawn_at`       DATETIME NULL DEFAULT NULL,         -- set if the artist later says no
  `created_at`         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_consents_article` (`article_id`),
  CONSTRAINT `fk_consents_article` FOREIGN KEY (`article_id`)
    REFERENCES `articles` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_consents_recorder` FOREIGN KEY (`recorded_by`)
    REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---- Check afterwards: all 8 new tables should be listed ----
-- SHOW TABLES WHERE `Tables_in_<your_db>` IN ('artwork_stages','artwork_colors',
--   'institutes','courses','course_registrations','articles','article_images','photo_consents');
