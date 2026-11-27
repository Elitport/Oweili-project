-- =====================================================================
--  Oweili — identity checks at sign-up (owner, 27 Nov 2026)
--  Only adds. Safe to run more than once.
--
--  identity_checks   one row per artist or institute account: the identity
--                    document (artists: Saudi national ID, Iqama or passport,
--                    with type, number, expiry and a photo; institutes: the
--                    Saudi National Address certificate), the review link,
--                    the consent time and the owner's decision.
--                    The document number is ENCRYPTED (AES-256-GCM); the
--                    photos are encrypted files outside the public folder.
--                    Kept after approval and after the account closes for
--                    the years set in Owner settings (default 5), then the
--                    files and the number are deleted automatically.
--                    The name and email are copied here, so the record still
--                    says whose it was after the account is deleted.
--  identity_photos   institutes: 2 to 5 photos of the premises or of
--                    students' work (encrypted files, private)
-- =====================================================================

CREATE TABLE IF NOT EXISTS `identity_checks` (
  `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`        BIGINT UNSIGNED NULL DEFAULT NULL,
  `role`           VARCHAR(12)     NOT NULL DEFAULT 'artist',   -- artist / institute
  `person_name`    VARCHAR(150)    NOT NULL DEFAULT '',
  `person_email`   VARCHAR(190)    NOT NULL DEFAULT '',
  `id_type`        VARCHAR(12)     NOT NULL DEFAULT '',         -- national_id / iqama / passport (artists)
  `id_number_enc`  TEXT            NULL,                         -- encrypted, never shown in a list
  `id_last4`       VARCHAR(4)      NOT NULL DEFAULT '',
  `id_expiry`      DATE            NULL DEFAULT NULL,
  `id_file`        VARCHAR(80)     NOT NULL DEFAULT '',          -- encrypted photo of the document (artists)
  `address_file`   VARCHAR(80)     NOT NULL DEFAULT '',          -- encrypted National Address certificate (institutes)
  `review_link`    VARCHAR(255)    NOT NULL DEFAULT '',          -- Instagram or website, for the owner's review only
  `consent_at`     DATETIME        NULL DEFAULT NULL,            -- the required tick at sign-up
  `status`         VARCHAR(10)     NOT NULL DEFAULT 'pending',   -- pending / approved / refused
  `reason`         VARCHAR(500)    NOT NULL DEFAULT '',
  `decided_at`     DATETIME        NULL DEFAULT NULL,
  `closed_at`      DATETIME        NULL DEFAULT NULL,            -- the account was closed or refused: the keeping clock starts
  `purged_at`      DATETIME        NULL DEFAULT NULL,            -- the files and the number were deleted
  `created_at`     TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_idc_user` (`user_id`),
  KEY `idx_idc_status` (`status`),
  KEY `idx_idc_closed` (`closed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `identity_photos` (
  `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `check_id`   BIGINT UNSIGNED NOT NULL,
  `file_name`  VARCHAR(80)     NOT NULL,
  `created_at` TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_idp_check` (`check_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
