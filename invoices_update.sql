-- =====================================================================
--  Oweili — invoices for every purchase (owner, 27 Nov 2026)
--  Only adds. Safe to run more than once.
--
--  invoices            one row per purchase (artwork / gift / commission
--                      order, course or workshop booking, free
--                      registration, museum ticket). The details are
--                      frozen when it is made, so the PDF never changes
--  invoice_counters    the last number used each year (OW-2026-00001)
-- =====================================================================

CREATE TABLE IF NOT EXISTS `invoices` (
  `id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `invoice_no`      VARCHAR(20)     NOT NULL,
  `year`            SMALLINT UNSIGNED NOT NULL,
  `seq`             INT UNSIGNED    NOT NULL,
  `source_kind`     VARCHAR(12)     NOT NULL,           -- artwork / gift / commission / course / workshop / ticket
  `source_id`       BIGINT UNSIGNED NOT NULL,
  `ref_no`          VARCHAR(40)     NOT NULL DEFAULT '', -- the order or booking number
  `user_id`         BIGINT UNSIGNED NULL DEFAULT NULL,  -- the customer's account, when there is one
  `customer_name`   VARCHAR(150)    NOT NULL DEFAULT '',
  `customer_email`  VARCHAR(190)    NOT NULL DEFAULT '',
  `item_en`         VARCHAR(255)    NOT NULL DEFAULT '',
  `item_ar`         VARCHAR(255)    NOT NULL DEFAULT '',
  `amount`          DECIMAL(10,2)   NOT NULL DEFAULT 0.00, -- what the customer paid (VAT included when it is a tax invoice)
  `is_tax`          TINYINT(1)      NOT NULL DEFAULT 0,    -- 0 = purchase receipt, 1 = tax invoice
  `vat_rate`        DECIMAL(5,2)    NOT NULL DEFAULT 0.00,
  `vat_amount`      DECIMAL(10,2)   NOT NULL DEFAULT 0.00,
  `vat_number`      VARCHAR(20)     NOT NULL DEFAULT '',
  `seller_address`  VARCHAR(400)    NOT NULL DEFAULT '',   -- Owner settings "Invoice address", frozen; printed only in the PDF
  `currency`        CHAR(3)         NOT NULL DEFAULT 'SAR',
  `lang`            CHAR(2)         NOT NULL DEFAULT 'en',
  `issued_at`       DATETIME        NOT NULL,
  `emailed_at`      DATETIME        NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_inv_no` (`invoice_no`),
  UNIQUE KEY `uq_inv_year_seq` (`year`, `seq`),
  UNIQUE KEY `uq_inv_source` (`source_kind`, `source_id`),
  KEY `idx_inv_user` (`user_id`),
  KEY `idx_inv_email` (`customer_email`),
  KEY `idx_inv_issued` (`issued_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `invoice_counters` (
  `year`  SMALLINT UNSIGNED NOT NULL,
  `last`  INT UNSIGNED      NOT NULL DEFAULT 0,
  PRIMARY KEY (`year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 28 Nov 2026 (owner): the invoice address, for a table made before this column existed
ALTER TABLE `invoices` ADD COLUMN IF NOT EXISTS `seller_address` VARCHAR(400) NOT NULL DEFAULT '' AFTER `vat_number`;
