-- KOVCHEG Blog 3.5.4 — Layout Matrix Builder
-- Copyright © Ланцет Семён Борисович. All rights reserved.

INSERT INTO modules (slug,name,version,description,enabled,installed_at,updated_at)
VALUES (
  'layout-matrix',
  'KOVCHEG Layout Matrix',
  '1.0.0',
  'Точная матрица зон для шапки, колонок, баннеров, центральной сетки и подвала.',
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

UPDATE site_widget_placements p
JOIN site_widget_instances w ON w.id=p.widget_id
SET p.zone='matrix.header.1',p.updated_at=CURRENT_TIMESTAMP
WHERE p.zone='header.main' AND w.system_key='default-logo';

UPDATE site_widget_placements p
JOIN site_widget_instances w ON w.id=p.widget_id
SET p.zone='matrix.header.3',p.updated_at=CURRENT_TIMESTAMP
WHERE p.zone='header.main' AND w.system_key='default-menu';

UPDATE site_widget_placements p
JOIN site_widget_instances w ON w.id=p.widget_id
SET p.zone='matrix.header.5',p.updated_at=CURRENT_TIMESTAMP
WHERE p.zone='header.main' AND w.system_key='default-account';

UPDATE site_widget_placements SET zone='matrix.preheader',updated_at=CURRENT_TIMESTAMP WHERE zone='header.top';
UPDATE site_widget_placements SET zone='matrix.header.2',updated_at=CURRENT_TIMESTAMP WHERE zone='header.main';
UPDATE site_widget_placements SET zone='matrix.postheader',updated_at=CURRENT_TIMESTAMP WHERE zone='header.bottom';
UPDATE site_widget_placements SET zone='matrix.banner.top',updated_at=CURRENT_TIMESTAMP WHERE zone='page.before';
UPDATE site_widget_placements SET zone='matrix.left.1',updated_at=CURRENT_TIMESTAMP WHERE zone='layout.left';
UPDATE site_widget_placements SET zone='matrix.center.1',updated_at=CURRENT_TIMESTAMP WHERE zone='content.before';
UPDATE site_widget_placements SET zone='matrix.center.5',updated_at=CURRENT_TIMESTAMP WHERE zone='content.after';
UPDATE site_widget_placements SET zone='matrix.right.1',updated_at=CURRENT_TIMESTAMP WHERE zone='layout.right';
UPDATE site_widget_placements SET zone='matrix.banner.bottom',updated_at=CURRENT_TIMESTAMP WHERE zone='page.after';
UPDATE site_widget_placements SET zone='matrix.footer.1',updated_at=CURRENT_TIMESTAMP WHERE zone='footer.top';
UPDATE site_widget_placements SET zone='matrix.footer.2',updated_at=CURRENT_TIMESTAMP WHERE zone='footer.columns';
UPDATE site_widget_placements SET zone='matrix.footer.8',updated_at=CURRENT_TIMESTAMP WHERE zone='footer.bottom';

UPDATE themes
SET version='1.3.0',
    description='Современная тема портала с точной матрицей зон: пять секций шапки, две баннерные полосы, три колонки, двенадцать центральных блоков и подвал 4×2.',
    updated_at=CURRENT_TIMESTAMP
WHERE slug='kovcheg-portal';

INSERT INTO settings (`key`,`value`,updated_at)
VALUES ('portal_layout_matrix','1',CURRENT_TIMESTAMP)
ON DUPLICATE KEY UPDATE `value`='1',updated_at=CURRENT_TIMESTAMP;