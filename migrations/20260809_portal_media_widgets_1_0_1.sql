-- KOVCHEG Blog 3.9.0 — Portal Media Widgets 1.0.1 metadata update
-- Safe for existing installations: updates module registry only.

UPDATE modules
SET version='1.0.1',
    description='Фотокарусель, видеокарусель YouTube/Rutube/Vimeo и слайдер записей/страниц для KOVCHEG Blog.',
    updated_at=CURRENT_TIMESTAMP
WHERE slug='portal-media-widgets';
