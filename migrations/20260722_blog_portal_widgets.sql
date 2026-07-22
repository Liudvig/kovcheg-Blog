-- KOVCHEG Blog 3.5.1 — Portal Media Widgets
-- Copyright © Ланцет Семён Борисович. All rights reserved.

INSERT INTO modules (slug,name,version,description,enabled,installed_at,updated_at)
VALUES (
  'portal-media-widgets',
  'KOVCHEG Portal Media Widgets',
  '1.0.0',
  'Фотокарусель, видеокарусель и слайдер контента для KOVCHEG Blog.',
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
