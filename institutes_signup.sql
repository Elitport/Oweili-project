-- =====================================================================
--  institutes_signup.sql — art institutes register themselves with their
--  documents; more course details for the Art Institutes filters
--  (owner, 25 Nov 2026). Only ADDS columns. Nothing is changed or deleted.
--  Safe to run more than once.
--
--  institutes  legal name, contact person, registration (Commercial Register
--              or Freelance document: type, number, private file), training
--              licence (Ministry of Culture "Ebdaa" or TVTC: number, private
--              file; optional at sign-up), a link for the owner's review only,
--              when the "documents are valid and mine" tick was given, and the
--              reason when the owner did not approve
--  courses     audience (children / adults / all), attendance (men / women /
--              mixed), length (workshop 1-2 days / short course / long course)
-- =====================================================================

ALTER TABLE `institutes`
  ADD COLUMN IF NOT EXISTS `legal_name`        VARCHAR(200) NOT NULL DEFAULT '',
  ADD COLUMN IF NOT EXISTS `contact_person`    VARCHAR(150) NOT NULL DEFAULT '',
  ADD COLUMN IF NOT EXISTS `reg_type`          VARCHAR(12)  NOT NULL DEFAULT '',
  ADD COLUMN IF NOT EXISTS `reg_number`        VARCHAR(40)  NOT NULL DEFAULT '',
  ADD COLUMN IF NOT EXISTS `reg_file`          VARCHAR(120) NOT NULL DEFAULT '',
  ADD COLUMN IF NOT EXISTS `licence_from`      VARCHAR(12)  NOT NULL DEFAULT '',
  ADD COLUMN IF NOT EXISTS `licence_number`    VARCHAR(40)  NOT NULL DEFAULT '',
  ADD COLUMN IF NOT EXISTS `licence_file`      VARCHAR(120) NOT NULL DEFAULT '',
  ADD COLUMN IF NOT EXISTS `review_link`       VARCHAR(300) NOT NULL DEFAULT '',
  ADD COLUMN IF NOT EXISTS `docs_confirmed_at` DATETIME     NULL DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `reject_reason`     VARCHAR(500) NOT NULL DEFAULT '';

ALTER TABLE `courses`
  ADD COLUMN IF NOT EXISTS `audience`    VARCHAR(10) NOT NULL DEFAULT 'all',
  ADD COLUMN IF NOT EXISTS `attendance`  VARCHAR(10) NOT NULL DEFAULT 'mixed',
  ADD COLUMN IF NOT EXISTS `length_kind` VARCHAR(10) NOT NULL DEFAULT '';
