-- visitor_stats.sql — visitor city statistics (owner, 9 Nov 2026). Only adds one table; safe to run more than once.
-- One row per day, country and city with a count. No internet address and nothing personal is stored.
CREATE TABLE IF NOT EXISTS `visitor_stats` (
  `day`     DATE         NOT NULL,
  `country` CHAR(2)      NOT NULL DEFAULT 'ZZ',
  `city`    VARCHAR(100) NOT NULL DEFAULT '',
  `visits`  INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`day`, `country`, `city`),
  KEY `idx_visitor_stats_day` (`day`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
