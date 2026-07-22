INSERT INTO themes (slug,name,version,description,author,is_active,installed_at,updated_at)
VALUES ('kovcheg-portal','KOVCHEG Portal','1.0.0','Трёхколоночная тема новостного сайта, журнала и информационного портала.','Ланцет Семён Борисович',0,CURRENT_TIMESTAMP,CURRENT_TIMESTAMP)
ON DUPLICATE KEY UPDATE name=VALUES(name),version=VALUES(version),description=VALUES(description),author=VALUES(author),updated_at=CURRENT_TIMESTAMP;

INSERT INTO theme_settings (theme_slug,setting_key,setting_value,updated_at) VALUES
('kovcheg-portal','layout_width','1440',CURRENT_TIMESTAMP),
('kovcheg-portal','left_sidebar_width','250',CURRENT_TIMESTAMP),
('kovcheg-portal','right_sidebar_width','290',CURRENT_TIMESTAMP),
('kovcheg-portal','show_news_ticker','1',CURRENT_TIMESTAMP)
ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value),updated_at=CURRENT_TIMESTAMP;

INSERT INTO site_widget_instances (system_key,widget_type,title,settings_json,is_enabled,created_at,updated_at) VALUES
('portal-left-categories','core.categories','Рубрики','{"limit":20}',1,CURRENT_TIMESTAMP,CURRENT_TIMESTAMP),
('portal-left-menu','core.menu','Разделы сайта','{"menu_id":0,"orientation":"vertical"}',1,CURRENT_TIMESTAMP,CURRENT_TIMESTAMP),
('portal-right-search','core.search','Поиск','{"placeholder":"Поиск по сайту"}',1,CURRENT_TIMESTAMP,CURRENT_TIMESTAMP),
('portal-right-latest','core.latest-posts','Последние публикации','{"limit":7}',1,CURRENT_TIMESTAMP,CURRENT_TIMESTAMP)
ON DUPLICATE KEY UPDATE title=VALUES(title),settings_json=VALUES(settings_json),is_enabled=1,updated_at=CURRENT_TIMESTAMP;

INSERT INTO site_widget_placements (layout_id,widget_id,zone,sort_order,visibility_json,style_json,created_at,updated_at)
SELECT l.id,w.id,'layout.left',10,'{}','{}',CURRENT_TIMESTAMP,CURRENT_TIMESTAMP
FROM site_layouts l JOIN site_widget_instances w ON w.system_key='portal-left-menu'
WHERE l.slug='default' AND NOT EXISTS (SELECT 1 FROM site_widget_placements p WHERE p.layout_id=l.id AND p.widget_id=w.id AND p.zone='layout.left');

INSERT INTO site_widget_placements (layout_id,widget_id,zone,sort_order,visibility_json,style_json,created_at,updated_at)
SELECT l.id,w.id,'layout.left',20,'{}','{}',CURRENT_TIMESTAMP,CURRENT_TIMESTAMP
FROM site_layouts l JOIN site_widget_instances w ON w.system_key='portal-left-categories'
WHERE l.slug='default' AND NOT EXISTS (SELECT 1 FROM site_widget_placements p WHERE p.layout_id=l.id AND p.widget_id=w.id AND p.zone='layout.left');

INSERT INTO site_widget_placements (layout_id,widget_id,zone,sort_order,visibility_json,style_json,created_at,updated_at)
SELECT l.id,w.id,'layout.right',10,'{}','{}',CURRENT_TIMESTAMP,CURRENT_TIMESTAMP
FROM site_layouts l JOIN site_widget_instances w ON w.system_key='portal-right-search'
WHERE l.slug='default' AND NOT EXISTS (SELECT 1 FROM site_widget_placements p WHERE p.layout_id=l.id AND p.widget_id=w.id AND p.zone='layout.right');

INSERT INTO site_widget_placements (layout_id,widget_id,zone,sort_order,visibility_json,style_json,created_at,updated_at)
SELECT l.id,w.id,'layout.right',20,'{}','{}',CURRENT_TIMESTAMP,CURRENT_TIMESTAMP
FROM site_layouts l JOIN site_widget_instances w ON w.system_key='portal-right-latest'
WHERE l.slug='default' AND NOT EXISTS (SELECT 1 FROM site_widget_placements p WHERE p.layout_id=l.id AND p.widget_id=w.id AND p.zone='layout.right');
