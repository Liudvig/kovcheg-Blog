-- KOVCHEG Blog 3.5.2 — Visual Zone Builder
-- Copyright © Ланцет Семён Борисович. All rights reserved.

UPDATE themes
SET version='1.2.0',
    description='Современная трёхколоночная тема портала с неподвижной шапкой, полностью прорисованными боковыми колонками, центральной прокруткой и визуальным конструктором зон.',
    updated_at=CURRENT_TIMESTAMP
WHERE slug='kovcheg-portal';

INSERT INTO settings (setting_key,setting_value,updated_at)
VALUES ('portal_visual_zone_builder','1',CURRENT_TIMESTAMP)
ON DUPLICATE KEY UPDATE setting_value='1',updated_at=CURRENT_TIMESTAMP;