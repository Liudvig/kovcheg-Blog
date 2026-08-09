-- KOVCHEG CMS 3.9.0 compatibility marker.
--
-- This historical migration filename is intentionally preserved because
-- bin/migrate.php tracks applied migrations by filename. The old VK media
-- subsystem is no longer part of KOVCHEG Blog / KOVCHEG CMS and fresh or
-- partially upgraded 3.9 installations must not create its tables.
-- Existing production tables, if any, are deliberately left untouched here;
-- their removal requires a separate backup-and-data-review procedure.

SET @kovcheg_legacy_vk_media_retired = 1;
