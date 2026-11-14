-- =====================================================================
--  bookings_update.sql — course booking for institutes and ticket sales
--  for partner museums (owner, 14 Nov 2026). HIDDEN behind one switch
--  (site_settings 'bookings_on', Admin → Settings) until the owner turns it on.
--  ONLY ADDS: new tables, new columns, one new account type value.
--  Nothing is deleted or renamed. Safe to run more than once.
--  Run on the live database only after a full backup.
-- =====================================================================

-- 1. Two new account types: museum (and the existing institute)
ALTER TABLE users MODIFY COLUMN role ENUM('artist','collector','admin','sub_admin','institute','museum') NOT NULL DEFAULT 'collector';

ALTER TABLE activity_log MODIFY COLUMN actor_type ENUM('artist','buyer','admin','sub_admin','institute','guest','system','museum') NOT NULL DEFAULT 'guest';

-- 2. Institutes that join through the sign-up page (the older directory rows keep 0)
ALTER TABLE institutes ADD COLUMN IF NOT EXISTS via_signup TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE institutes ADD COLUMN IF NOT EXISTS payout_note VARCHAR(500) NOT NULL DEFAULT '';

-- 3. Course details (the older directory courses keep bookable = 0 and status 'approved')
ALTER TABLE courses ADD COLUMN IF NOT EXISTS bookable TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE courses ADD COLUMN IF NOT EXISTS status VARCHAR(12) NOT NULL DEFAULT 'approved';
ALTER TABLE courses ADD COLUMN IF NOT EXISTS reject_reason VARCHAR(500) NOT NULL DEFAULT '';
ALTER TABLE courses ADD COLUMN IF NOT EXISTS course_type VARCHAR(12) NOT NULL DEFAULT 'in_person';
ALTER TABLE courses ADD COLUMN IF NOT EXISTS level VARCHAR(14) NOT NULL DEFAULT 'beginner';
ALTER TABLE courses ADD COLUMN IF NOT EXISTS learn_en TEXT NULL;
ALTER TABLE courses ADD COLUMN IF NOT EXISTS learn_ar TEXT NULL;
ALTER TABLE courses ADD COLUMN IF NOT EXISTS materials_included TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE courses ADD COLUMN IF NOT EXISTS schedule_en VARCHAR(300) NOT NULL DEFAULT '';
ALTER TABLE courses ADD COLUMN IF NOT EXISTS schedule_ar VARCHAR(300) NOT NULL DEFAULT '';
ALTER TABLE courses ADD COLUMN IF NOT EXISTS total_hours DECIMAL(6,1) NULL;
ALTER TABLE courses ADD COLUMN IF NOT EXISTS location_en VARCHAR(300) NOT NULL DEFAULT '';
ALTER TABLE courses ADD COLUMN IF NOT EXISTS location_ar VARCHAR(300) NOT NULL DEFAULT '';
ALTER TABLE courses ADD COLUMN IF NOT EXISTS meeting_link VARCHAR(500) NOT NULL DEFAULT '';
ALTER TABLE courses ADD COLUMN IF NOT EXISTS course_lang VARCHAR(6) NOT NULL DEFAULT 'ar';
ALTER TABLE courses ADD COLUMN IF NOT EXISTS age_min TINYINT UNSIGNED NULL;
ALTER TABLE courses ADD COLUMN IF NOT EXISTS age_max TINYINT UNSIGNED NULL;
ALTER TABLE courses ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- 4. A registration knows its payment and its money split
ALTER TABLE course_registrations ADD COLUMN IF NOT EXISTS ref_no VARCHAR(20) NULL;
ALTER TABLE course_registrations ADD COLUMN IF NOT EXISTS student_name VARCHAR(150) NOT NULL DEFAULT '';
ALTER TABLE course_registrations ADD COLUMN IF NOT EXISTS student_email VARCHAR(190) NOT NULL DEFAULT '';
ALTER TABLE course_registrations ADD COLUMN IF NOT EXISTS student_phone VARCHAR(30) NOT NULL DEFAULT '';
ALTER TABLE course_registrations ADD COLUMN IF NOT EXISTS amount DECIMAL(10,2) NOT NULL DEFAULT 0.00;
ALTER TABLE course_registrations ADD COLUMN IF NOT EXISTS commission_rate DECIMAL(5,2) NOT NULL DEFAULT 0.00;
ALTER TABLE course_registrations ADD COLUMN IF NOT EXISTS commission_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00;
ALTER TABLE course_registrations ADD COLUMN IF NOT EXISTS partner_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00;
ALTER TABLE course_registrations ADD COLUMN IF NOT EXISTS payment_id BIGINT UNSIGNED NULL;
ALTER TABLE course_registrations ADD COLUMN IF NOT EXISTS paid_at DATETIME NULL;
ALTER TABLE course_registrations ADD COLUMN IF NOT EXISTS cancelled_at DATETIME NULL;
ALTER TABLE course_registrations ADD COLUMN IF NOT EXISTS refund_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00;
ALTER TABLE course_registrations ADD COLUMN IF NOT EXISTS hold_until DATETIME NULL;
ALTER TABLE course_registrations ADD UNIQUE INDEX IF NOT EXISTS uq_registration_ref (ref_no);

-- 5. Partner museums on the Art Map
ALTER TABLE art_places ADD COLUMN IF NOT EXISTS partner TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE art_places ADD COLUMN IF NOT EXISTS ticket_commission DECIMAL(5,2) NOT NULL DEFAULT 10.00;
ALTER TABLE art_places ADD COLUMN IF NOT EXISTS payout_rule VARCHAR(16) NOT NULL DEFAULT 'after_visit';
ALTER TABLE art_places ADD COLUMN IF NOT EXISTS payout_days SMALLINT UNSIGNED NOT NULL DEFAULT 7;
ALTER TABLE art_places ADD COLUMN IF NOT EXISTS payout_note VARCHAR(500) NOT NULL DEFAULT '';
ALTER TABLE art_places ADD COLUMN IF NOT EXISTS tickets_url VARCHAR(500) NOT NULL DEFAULT '';
ALTER TABLE art_places ADD COLUMN IF NOT EXISTS user_id BIGINT UNSIGNED NULL;

-- 6. Ticket types (adult, child, family …) and the dates a museum sells
CREATE TABLE IF NOT EXISTS museum_ticket_types (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  place_id INT UNSIGNED NOT NULL,
  name_en VARCHAR(120) NOT NULL DEFAULT '',
  name_ar VARCHAR(120) NOT NULL DEFAULT '',
  price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  people TINYINT UNSIGNED NOT NULL DEFAULT 1,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  sort_order SMALLINT NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_mtt_place (place_id, is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS museum_ticket_dates (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  place_id INT UNSIGNED NOT NULL,
  visit_date DATE NOT NULL,
  capacity INT UNSIGNED NOT NULL DEFAULT 0,
  sold INT UNSIGNED NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (id),
  UNIQUE KEY uq_mtd_place_date (place_id, visit_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. One row per purchase (one QR code covers every person on it)
CREATE TABLE IF NOT EXISTS museum_tickets (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  ref_no VARCHAR(20) NOT NULL,
  qr_token CHAR(40) NOT NULL,
  place_id INT UNSIGNED NOT NULL,
  date_id INT UNSIGNED NOT NULL,
  visit_date DATE NOT NULL,
  user_id BIGINT UNSIGNED NULL,
  buyer_name VARCHAR(150) NOT NULL DEFAULT '',
  buyer_email VARCHAR(190) NOT NULL DEFAULT '',
  buyer_phone VARCHAR(30) NOT NULL DEFAULT '',
  lines_json TEXT NULL,
  people INT UNSIGNED NOT NULL DEFAULT 0,
  amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  commission_rate DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  commission_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  partner_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  status VARCHAR(12) NOT NULL DEFAULT 'pending',
  payment_id BIGINT UNSIGNED NULL,
  paid_at DATETIME NULL,
  used_at DATETIME NULL,
  used_by BIGINT UNSIGNED NULL,
  cancelled_at DATETIME NULL,
  refund_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  hold_until DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_mt_ref (ref_no),
  UNIQUE KEY uq_mt_qr (qr_token),
  KEY idx_mt_place (place_id, visit_date),
  KEY idx_mt_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Every payment try for a booking (course or ticket), any provider
CREATE TABLE IF NOT EXISTS booking_payments (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  token CHAR(32) NOT NULL,
  kind VARCHAR(10) NOT NULL,
  ref_id BIGINT UNSIGNED NOT NULL,
  user_id BIGINT UNSIGNED NULL,
  amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  currency CHAR(3) NOT NULL DEFAULT 'SAR',
  gateway VARCHAR(12) NOT NULL DEFAULT '',
  test_mode TINYINT(1) NOT NULL DEFAULT 1,
  status VARCHAR(12) NOT NULL DEFAULT 'created',
  gateway_ref VARCHAR(120) NOT NULL DEFAULT '',
  note VARCHAR(500) NOT NULL DEFAULT '',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  paid_at DATETIME NULL,
  refunded_at DATETIME NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_bp_token (token),
  KEY idx_bp_ref (kind, ref_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Money owed to institutes and museums (separate from the artists' payouts table)
CREATE TABLE IF NOT EXISTS partner_payouts (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  payee_kind VARCHAR(10) NOT NULL,
  payee_id BIGINT UNSIGNED NOT NULL,
  source_kind VARCHAR(10) NOT NULL,
  source_id BIGINT UNSIGNED NOT NULL,
  amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  commission DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  due_on DATE NULL,
  status VARCHAR(12) NOT NULL DEFAULT 'pending',
  reference VARCHAR(120) NOT NULL DEFAULT '',
  paid_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_pp_source (source_kind, source_id),
  KEY idx_pp_payee (payee_kind, payee_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
