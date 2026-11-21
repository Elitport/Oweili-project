-- =====================================================================
--  courses_art_type.sql — courses by art type (owner, 21 Nov 2026, Stage 2).
--  Only adds columns; safe to run more than once.
--  courses.art_type: painting | sculpture | jewellery (existing courses become 'painting').
--  courses.art_style: optional style id from Art Styles (assets/js/art_styles.js), e.g. 'wood_carving'.
--  course_leads.art_type: "Which art?" on the "Notify me" form: any | painting | sculpture | jewellery
--  (older requests keep an empty value).
-- =====================================================================
ALTER TABLE `courses` ADD COLUMN IF NOT EXISTS `art_type` VARCHAR(20) NOT NULL DEFAULT 'painting';
ALTER TABLE `courses` ADD COLUMN IF NOT EXISTS `art_style` VARCHAR(50) NOT NULL DEFAULT '';
ALTER TABLE `course_leads` ADD COLUMN IF NOT EXISTS `art_type` VARCHAR(20) NOT NULL DEFAULT '';
