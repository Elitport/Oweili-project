-- =====================================================================
--  course_leads_city.sql — "City or Online" on the Art Institutes "Notify me" form (owner, 21 Nov 2026).
--  Only adds one column; safe to run more than once. Holds the English city name the visitor
--  chose, or 'online'. Older requests keep an empty value.
-- =====================================================================
ALTER TABLE `course_leads` ADD COLUMN IF NOT EXISTS `city` VARCHAR(120) NOT NULL DEFAULT '' AFTER `phone`;
