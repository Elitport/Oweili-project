-- =====================================================================
--  vat_settings.sql — VAT number and VAT start date (owner, 28 Nov 2026)
--  Sets the VAT number 314525767400003 and the start date 2026-12-01.
--  The VAT switch is NOT turned on: it keeps its current value (off when
--  there is no VAT row yet). The owner turns it on in Owner settings → VAT.
--  Safe to run more than once. Changes only the 'vat' setting row.
-- =====================================================================
INSERT INTO site_settings (setting_key, setting_value)
VALUES ('vat', '{"on":false,"rate":15,"number":"314525767400003","start":"2026-12-01"}')
ON DUPLICATE KEY UPDATE setting_value = JSON_SET(
    IF(JSON_VALID(setting_value), setting_value, '{"on":false,"rate":15}'),
    '$.number', '314525767400003',
    '$.start', '2026-12-01');
