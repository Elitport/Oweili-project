-- =====================================================================
-- courses_update.sql — Art Institutes and "Book your seat" (owner, 15 Nov 2026)
-- Only changes what the feature needs; safe to run more than once.
--  1. course_registrations.user_id may be empty: a visitor books a seat with
--     name, email and phone, without an account (one seat held until it is
--     paid or cancelled)
--  2. course_leads: "Notify me" for an institute with no courses yet
--     (name, email, phone), for the owner and Bashro Sales
-- =====================================================================
ALTER TABLE course_registrations MODIFY COLUMN user_id BIGINT UNSIGNED NULL;

CREATE TABLE IF NOT EXISTS course_leads (
  id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  institute_id  BIGINT UNSIGNED NULL,            -- institutes.id (the older institutes and signed-up ones)
  place_id      BIGINT UNSIGNED NULL,            -- art_places.id (Art Map institutes)
  name          VARCHAR(150) NOT NULL,
  email         VARCHAR(190) NOT NULL DEFAULT '',
  phone         VARCHAR(30)  NOT NULL,
  lang          CHAR(2)      NOT NULL DEFAULT 'en',
  status        ENUM('new','contacted','closed') NOT NULL DEFAULT 'new',
  created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_course_leads_inst (institute_id),
  KEY idx_course_leads_place (place_id),
  KEY idx_course_leads_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
