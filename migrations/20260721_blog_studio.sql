CREATE TABLE IF NOT EXISTS content_entry_meta (
    entry_id BIGINT UNSIGNED NOT NULL,
    meta_key VARCHAR(100) NOT NULL,
    meta_value LONGTEXT NULL,
    updated_at DATETIME NULL,
    PRIMARY KEY(entry_id,meta_key),
    CONSTRAINT fk_content_entry_meta_entry FOREIGN KEY(entry_id) REFERENCES content_entries(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS content_views_daily (
    entry_id BIGINT UNSIGNED NOT NULL,
    view_date DATE NOT NULL,
    views BIGINT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY(entry_id,view_date),
    INDEX idx_content_views_date(view_date,views),
    CONSTRAINT fk_content_views_entry FOREIGN KEY(entry_id) REFERENCES content_entries(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS content_comment_reports (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    comment_id BIGINT UNSIGNED NOT NULL,
    reporter_id BIGINT UNSIGNED NOT NULL,
    reason VARCHAR(190) NOT NULL,
    details TEXT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'open',
    reviewed_by BIGINT UNSIGNED NULL,
    reviewed_at DATETIME NULL,
    created_at DATETIME NULL,
    UNIQUE KEY uniq_comment_report(comment_id,reporter_id),
    INDEX idx_comment_report_status(status,id),
    CONSTRAINT fk_comment_report_comment FOREIGN KEY(comment_id) REFERENCES content_comments(id) ON DELETE CASCADE,
    CONSTRAINT fk_comment_report_reporter FOREIGN KEY(reporter_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_comment_report_reviewer FOREIGN KEY(reviewed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS theme_settings (
    theme_slug VARCHAR(80) NOT NULL,
    setting_key VARCHAR(100) NOT NULL,
    setting_value LONGTEXT NULL,
    updated_at DATETIME NULL,
    PRIMARY KEY(theme_slug,setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO content_categories (name,slug,description,sort_order,created_at,updated_at) VALUES
('Разработки','razrabotki','Ход разработки, технические решения и новые версии.',10,CURRENT_TIMESTAMP,CURRENT_TIMESTAMP),
('Проекты','proekty','Отдельные проекты, продукты и эксперименты.',20,CURRENT_TIMESTAMP,CURRENT_TIMESTAMP),
('Новости','novosti','Новости автора и проекта.',30,CURRENT_TIMESTAMP,CURRENT_TIMESTAMP);

INSERT INTO themes (slug,name,version,description,author,is_active,installed_at,updated_at)
VALUES ('kovcheg-editorial','KOVCHEG Editorial','1.0.0','Редакционная тема для блога и портфолио.','Ланцет Семён Борисович',1,CURRENT_TIMESTAMP,CURRENT_TIMESTAMP)
ON DUPLICATE KEY UPDATE name=VALUES(name),version=VALUES(version),description=VALUES(description),author=VALUES(author),updated_at=CURRENT_TIMESTAMP;

INSERT IGNORE INTO navigation_items (menu_id,parent_id,label,url,target_type,target_id,sort_order,is_enabled,created_at,updated_at)
SELECT m.id,NULL,'Главная','/','custom',NULL,10,1,CURRENT_TIMESTAMP,CURRENT_TIMESTAMP FROM navigation_menus m WHERE m.slug='primary';
INSERT IGNORE INTO navigation_items (menu_id,parent_id,label,url,target_type,target_id,sort_order,is_enabled,created_at,updated_at)
SELECT m.id,NULL,'Блог','/blog','custom',NULL,20,1,CURRENT_TIMESTAMP,CURRENT_TIMESTAMP FROM navigation_menus m WHERE m.slug='primary';
INSERT IGNORE INTO navigation_items (menu_id,parent_id,label,url,target_type,target_id,sort_order,is_enabled,created_at,updated_at)
SELECT m.id,NULL,'Портфолио','/portfolio','custom',NULL,30,1,CURRENT_TIMESTAMP,CURRENT_TIMESTAMP FROM navigation_menus m WHERE m.slug='primary';

INSERT INTO settings (`key`,`value`,updated_at) VALUES
('blog_tagline','Разработки · проекты · опыт',CURRENT_TIMESTAMP),
('blog_description','Разработки, идеи, опыт и новые проекты.',CURRENT_TIMESTAMP),
('portfolio_description','Работы, проекты, релизы и результаты.',CURRENT_TIMESTAMP),
('comments_auto_approve','0',CURRENT_TIMESTAMP),
('blog_posts_per_page','12',CURRENT_TIMESTAMP),
('blog_theme','kovcheg-editorial',CURRENT_TIMESTAMP)
ON DUPLICATE KEY UPDATE updated_at=updated_at;
