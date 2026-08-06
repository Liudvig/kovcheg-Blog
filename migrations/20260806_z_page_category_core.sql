-- KOVCHEG CMS 3.7.0 — Pages and Rubrics core
-- Author and copyright: Ланцет Семён Борисович
-- License: proprietary / all rights reserved
--
-- This migration intentionally sorts after 20260806_wordpress_content_model.sql.
-- Legacy Posts retain their rubric links and become ordinary Pages.

UPDATE content_entries
SET type = 'page', updated_at = CURRENT_TIMESTAMP
WHERE type IN ('post', 'portfolio');
