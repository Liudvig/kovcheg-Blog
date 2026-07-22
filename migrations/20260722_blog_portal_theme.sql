INSERT INTO themes (slug,name,version,description,author,is_active,installed_at,updated_at)
VALUES ('kovcheg-portal','KOVCHEG Portal','1.0.0','Трёхколоночная тема новостного сайта, журнала и информационного портала.','Ланцет Семён Борисович',0,CURRENT_TIMESTAMP,CURRENT_TIMESTAMP)
ON DUPLICATE KEY UPDATE name=VALUES(name),version=VALUES(version),description=VALUES(description),author=VALUES(author),updated_at=CURRENT_TIMESTAMP;

INSERT INTO theme_settings (theme_slug,setting_key,setting_value,updated_at) VALUES
('kovcheg-portal','layout_width','1440',CURRENT_TIMESTAMP),
('kovcheg-portal','left_sidebar_width','250',CURRENT_TIMESTAMP),
('kovcheg-portal','right_sidebar_width','290',CURRENT_TIMESTAMP),
('kovcheg-portal','show_news_ticker','1',CURRENT_TIMESTAMP)
ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value),updated_at=CURRENT_TIMESTAMP;