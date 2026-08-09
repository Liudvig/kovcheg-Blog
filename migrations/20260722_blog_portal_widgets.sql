-- KOVCHEG Blog 3.5.1+ — Portal Media Widgets compatibility seed
-- Copyright © Ланцет Семён Борисович. All rights reserved.

INSERT INTO modules (slug,name,version,description,enabled,installed_at,updated_at)
VALUES (
  'portal-media-widgets',
  'KOVCHEG Portal Media Widgets',
  '1.0.1',
  'Фотокарусель, видеокарусель YouTube/Rutube/Vimeo и слайдер записей/страниц для KOVCHEG Blog.',
  1,
  CURRENT_TIMESTAMP,
  CURRENT_TIMESTAMP
)
ON DUPLICATE KEY UPDATE
  name=VALUES(name),
  version=VALUES(version),
  description=VALUES(description),
  enabled=1,
  updated_at=CURRENT_TIMESTAMP;

UPDATE themes
SET version='1.1.0',
    description='Современная трёхколоночная тема портала с компактной закреплённой шапкой, боковыми колонками, подвалом и модульными каруселями.',
    updated_at=CURRENT_TIMESTAMP
WHERE slug='kovcheg-portal';
