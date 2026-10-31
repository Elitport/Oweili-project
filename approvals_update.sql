-- =====================================================================
--  Oweili · approvals_update.sql — "Nothing goes public without the
--  admin's approval". One new table that keeps an artist's changes to
--  something visitors can already see (a live artwork, an approved
--  profile) and a sub-admin's request to publish an article, until the
--  admin approves or rejects them (admin.php → Waiting for approval).
--
--  Only ADDS one table. Nothing is changed or deleted. Safe to run more
--  than once (IF NOT EXISTS). Until it is run, every page works as before.
-- =====================================================================

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `pending_changes` (
  `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `public_id`   CHAR(36)        NOT NULL DEFAULT (UUID()),
  `user_id`     BIGINT UNSIGNED NOT NULL,                 -- who asked (artist or sub-admin)
  `target_type` ENUM('artwork','profile','article') NOT NULL,
  `target_id`   BIGINT UNSIGNED NOT NULL,                 -- artworks.id, users.id or articles.id
  `data`        MEDIUMTEXT      NOT NULL,                 -- the new values (JSON)
  `status`      ENUM('pending','approved','rejected','replaced') NOT NULL DEFAULT 'pending',
  `reason`      VARCHAR(500)    NULL DEFAULT NULL,        -- why it was rejected (the artist sees it)
  `decided_by`  BIGINT UNSIGNED NULL DEFAULT NULL,
  `created_at`  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `decided_at`  TIMESTAMP       NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_pending_changes_public_id` (`public_id`),
  KEY `idx_pending_status` (`status`, `target_type`),
  KEY `idx_pending_target` (`target_type`, `target_id`),
  KEY `idx_pending_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
