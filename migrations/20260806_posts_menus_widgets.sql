-- KOVCHEG CMS 3.8.0 — Posts, Categories, Pages, Menus and Widgets
-- Author and copyright: Ланцет Семён Борисович
-- License: proprietary / all rights reserved
--
-- The migration is non-destructive for content and media.

-- Old portfolio works become normal Pages.
UPDATE content_entries
SET type='page', updated_at=CURRENT_TIMESTAMP
WHERE type='portfolio';

-- Categories belong to Posts only.
DELETE ec
FROM content_entry_categories ec
INNER JOIN content_entries e ON e.id=ec.entry_id
WHERE e.type='page';

-- Old demo navigation is not mandatory in the CMS model.
DELETE FROM navigation_items
WHERE target_type='custom'
  AND url IN ('/blog','/portfolio')
  AND label IN ('Блог','Портфолио');

-- Rubrics, search, subscription and footer text are optional widgets.
-- Keep the widget records so the owner may enable them again, but do not force them on the site.
DELETE p
FROM site_widget_placements p
INNER JOIN site_widget_instances w ON w.id=p.widget_id
WHERE w.system_key IN (
    'portal-section-menu',
    'portal-default-search',
    'default-subscription',
    'portal-footer-note'
);

UPDATE site_widget_instances
SET is_enabled=0, updated_at=CURRENT_TIMESTAMP
WHERE system_key IN (
    'portal-section-menu',
    'portal-default-search',
    'default-subscription',
    'portal-footer-note'
);

-- Make the first created menu the header menu only when no menu location was configured.
UPDATE navigation_menus
SET location='header', updated_at=CURRENT_TIMESTAMP
WHERE id=(
    SELECT selected_id FROM (
        SELECT MIN(id) selected_id FROM navigation_menus WHERE is_active=1
    ) chosen
)
AND NOT EXISTS (
    SELECT 1 FROM (
        SELECT id FROM navigation_menus WHERE location='header' AND is_active=1 LIMIT 1
    ) existing_header
);
