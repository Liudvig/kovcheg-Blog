-- KOVCHEG CMS 3.9.0 — content model cleanup
-- Author and copyright: Ланцет Семён Борисович
-- License: proprietary / all rights reserved
--
-- Idempotent compatibility cleanup for installations upgraded from early builds.
-- Existing content is preserved. Old portfolio rows become normal Pages.
-- Categories remain attached only to Posts.

UPDATE content_entries
SET type = 'page', updated_at = CURRENT_TIMESTAMP
WHERE type = 'portfolio';

DELETE ec
FROM content_entry_categories ec
INNER JOIN content_entries e ON e.id = ec.entry_id
WHERE e.type = 'page';
