-- KOVCHEG CMS 3.7.0 — Pages and Categories core
-- Author and copyright: Ланцет Семён Борисович
-- License: proprietary / all rights reserved
--
-- Pages are the only content entity. Existing Posts and Portfolio items are
-- preserved and converted to Pages. Existing category links remain attached.

UPDATE content_entries
SET type = 'page', updated_at = CURRENT_TIMESTAMP
WHERE type IN ('post', 'portfolio');
