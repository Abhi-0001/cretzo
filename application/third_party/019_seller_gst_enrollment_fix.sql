-- Production hotfix for DB Error 1054: Unknown column 'sd.is_gst_registered'
-- Adds the columns created by migration 019_seller_gst_enrollment.php, which was
-- never applied to the production seller_data table.
--
-- NOTE: the `AFTER` clauses assume a `gst` column already exists in seller_data.
-- If your production table has no `gst` column, remove the two `AFTER ...` parts.

ALTER TABLE `seller_data`
  ADD COLUMN `is_gst_registered` TINYINT(1) NOT NULL DEFAULT 1 AFTER `gst`,
  ADD COLUMN `gst_enrollment_number` VARCHAR(64) NULL DEFAULT NULL AFTER `is_gst_registered`;
