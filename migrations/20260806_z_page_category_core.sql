-- KOVCHEG CMS 3.9.0 — legacy page/category compatibility
-- Author and copyright: Ланцет Семён Борисович
-- License: proprietary / all rights reserved
--
-- Historical filename is retained for migration compatibility.
-- Posts remain Posts. Legacy portfolio rows become normal Pages.
-- Categories are allowed only for Posts.

UPDATE content_entries
SET type = 'page', updated_at = CURRENT_TIMESTAMP
WHERE type = 'portfolio';

DELETE ec
FROM content_entry_categories ec
INNER JOIN content_entries e ON e.id = ec.entry_id
WHERE e.type = 'page';
