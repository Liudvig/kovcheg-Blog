CREATE TABLE IF NOT EXISTS site_home_sections (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    section_key VARCHAR(100) NOT NULL UNIQUE,
    section_type VARCHAR(40) NOT NULL,
    title VARCHAR(190) NULL,
    settings_json LONGTEXT NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    is_enabled TINYINT(1) NOT NULL DEFAULT 1,
    created_by BIGINT UNSIGNED NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    INDEX idx_home_sections_enabled(is_enabled,sort_order,id),
    CONSTRAINT fk_home_section_creator FOREIGN KEY(created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS content_subscriptions (
    entry_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL,
    PRIMARY KEY(entry_id,user_id),
    INDEX idx_content_subscription_user(user_id,created_at),
    CONSTRAINT fk_content_subscription_entry FOREIGN KEY(entry_id) REFERENCES content_entries(id) ON DELETE CASCADE,
    CONSTRAINT fk_content_subscription_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS content_comment_reactions (
    comment_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    reaction VARCHAR(32) NOT NULL,
    created_at DATETIME NOT NULL,
    PRIMARY KEY(comment_id,user_id),
    INDEX idx_comment_reaction_user(user_id,created_at),
    CONSTRAINT fk_comment_reaction_comment FOREIGN KEY(comment_id) REFERENCES content_comments(id) ON DELETE CASCADE,
    CONSTRAINT fk_comment_reaction_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS blog_notifications (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    actor_id BIGINT UNSIGNED NULL,
    type VARCHAR(50) NOT NULL DEFAULT 'info',
    title VARCHAR(190) NOT NULL,
    body TEXT NULL,
    url VARCHAR(500) NULL,
    read_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    INDEX idx_blog_notification_user(user_id,read_at,id),
    INDEX idx_blog_notification_created(created_at),
    CONSTRAINT fk_blog_notification_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_blog_notification_actor FOREIGN KEY(actor_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS seo_redirects (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    source_path VARCHAR(500) NOT NULL UNIQUE,
    target_url VARCHAR(500) NOT NULL,
    status_code SMALLINT UNSIGNED NOT NULL DEFAULT 301,
    hits BIGINT UNSIGNED NOT NULL DEFAULT 0,
    is_enabled TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    INDEX idx_seo_redirect_enabled(is_enabled,id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @kovcheg_sql = IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='content_entries' AND COLUMN_NAME='canonical_url') = 0,
    'ALTER TABLE `content_entries` ADD COLUMN `canonical_url` VARCHAR(500) NULL AFTER `seo_description`',
    'SET @kovcheg_noop = 1'
);
PREPARE kovcheg_stmt FROM @kovcheg_sql;
EXECUTE kovcheg_stmt;
DEALLOCATE PREPARE kovcheg_stmt;

SET @kovcheg_sql = IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='content_entries' AND COLUMN_NAME='seo_image_path') = 0,
    'ALTER TABLE `content_entries` ADD COLUMN `seo_image_path` VARCHAR(255) NULL AFTER `canonical_url`',
    'SET @kovcheg_noop = 1'
);
PREPARE kovcheg_stmt FROM @kovcheg_sql;
EXECUTE kovcheg_stmt;
DEALLOCATE PREPARE kovcheg_stmt;

SET @kovcheg_sql = IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='content_entries' AND COLUMN_NAME='seo_noindex') = 0,
    'ALTER TABLE `content_entries` ADD COLUMN `seo_noindex` TINYINT(1) NOT NULL DEFAULT 0 AFTER `seo_image_path`',
    'SET @kovcheg_noop = 1'
);
PREPARE kovcheg_stmt FROM @kovcheg_sql;
EXECUTE kovcheg_stmt;
DEALLOCATE PREPARE kovcheg_stmt;

SET @kovcheg_sql = IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='content_entries' AND COLUMN_NAME='og_type') = 0,
    'ALTER TABLE `content_entries` ADD COLUMN `og_type` VARCHAR(30) NOT NULL DEFAULT ''article'' AFTER `seo_noindex`',
    'SET @kovcheg_noop = 1'
);
PREPARE kovcheg_stmt FROM @kovcheg_sql;
EXECUTE kovcheg_stmt;
DEALLOCATE PREPARE kovcheg_stmt;

SET @kovcheg_sql = IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='content_comments' AND COLUMN_NAME='edited_at') = 0,
    'ALTER TABLE `content_comments` ADD COLUMN `edited_at` DATETIME NULL AFTER `updated_at`',
    'SET @kovcheg_noop = 1'
);
PREPARE kovcheg_stmt FROM @kovcheg_sql;
EXECUTE kovcheg_stmt;
DEALLOCATE PREPARE kovcheg_stmt;

INSERT IGNORE INTO site_home_sections (section_key,section_type,title,settings_json,sort_order,is_enabled,created_at,updated_at) VALUES
('hero','hero','Первый экран','{"eyebrow":"KOVCHEG BLOG","title":"Создаём будущее своими руками","text":"Разработки, технологии, музыка, строительство и реальные проекты — без лишнего шума.","primary_text":"Читать блог","primary_url":"/blog","secondary_text":"Смотреть проекты","secondary_url":"/portfolio"}',10,1,CURRENT_TIMESTAMP,CURRENT_TIMESTAMP),
('latest-posts','latest_posts','Последние записи','{"limit":8,"eyebrow":"НОВЫЕ МАТЕРИАЛЫ","button_text":"Все записи","button_url":"/blog"}',20,1,CURRENT_TIMESTAMP,CURRENT_TIMESTAMP),
('portfolio','portfolio','Проекты и результаты','{"limit":6,"eyebrow":"ПОРТФОЛИО","button_text":"Всё портфолио","button_url":"/portfolio"}',30,1,CURRENT_TIMESTAMP,CURRENT_TIMESTAMP),
('statement','statement','О платформе','{"eyebrow":"О ПЛАТФОРМЕ","quote":"Сайт должен помогать человеку заявить о себе, а не заставлять его изучать программирование и десятки запутанных настроек.","text":"KOVCHEG Blog создаётся как быстрый и понятный инструмент для автора, музыканта, строителя, художника, мастера и небольшой команды."}',40,1,CURRENT_TIMESTAMP,CURRENT_TIMESTAMP);

INSERT INTO settings (`key`,`value`,updated_at) VALUES
('seo_title_suffix','KOVCHEG Blog',CURRENT_TIMESTAMP),
('seo_default_og_type','website',CURRENT_TIMESTAMP),
('seo_feed_enabled','1',CURRENT_TIMESTAMP),
('seo_sitemap_enabled','1',CURRENT_TIMESTAMP),
('seo_robots_extra','',CURRENT_TIMESTAMP),
('comments_edit_minutes','30',CURRENT_TIMESTAMP),
('comments_notifications','1',CURRENT_TIMESTAMP)
ON DUPLICATE KEY UPDATE updated_at=updated_at;
