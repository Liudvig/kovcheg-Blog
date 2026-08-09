-- KOVCHEG CMS 3.9.0 compatibility marker.
--
-- The historical Visual Zone Builder setting is no longer read by the current
-- Layout & Widget Engine. The filename is preserved because migrations are
-- tracked by name. Existing production settings are intentionally left intact;
-- fresh 3.9 installations must not create this retired flag.

SET @kovcheg_legacy_visual_zone_builder_retired = 1;
