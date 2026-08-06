-- KOVCHEG Blog 3.6.0 — WordPress-style content model
-- Author and copyright: Ланцет Семён Борисович
-- License: proprietary / all rights reserved
--
-- Existing portfolio items are preserved and converted into regular Pages.
-- Categories remain attached only to Posts. No content rows are deleted.

UPDATE content_entries
SET type = 'page', updated_at = CURRENT_TIMESTAMP
WHERE type = 'portfolio';

DELETE ec
FROM content_entry_categories ec
INNER JOIN content_entries e ON e.id = ec.entry_id
WHERE e.type = 'page';
