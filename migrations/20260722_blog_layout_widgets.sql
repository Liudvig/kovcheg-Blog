CREATE TABLE IF NOT EXISTS site_layouts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(100) NOT NULL,
    name VARCHAR(150) NOT NULL,
    context_type VARCHAR(80) NOT NULL DEFAULT 'default',
    status VARCHAR(20) NOT NULL DEFAULT 'draft',
    settings_json LONGTEXT NULL,
    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,
    published_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    UNIQUE KEY uniq_site_layout_slug(slug),
    INDEX idx_site_layout_context_status(context_type,status,id),
    CONSTRAINT fk_site_layout_created_by FOREIGN KEY(created_by) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_site_layout_updated_by FOREIGN KEY(updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS site_widget_instances (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    system_key VARCHAR(100) NULL,
    widget_type VARCHAR(120) NOT NULL,
    module_slug VARCHAR(60) NULL,
    title VARCHAR(180) NOT NULL,
    settings_json LONGTEXT NULL,
    is_enabled TINYINT(1) NOT NULL DEFAULT 1,
    created_by BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    UNIQUE KEY uniq_site_widget_system_key(system_key),
    INDEX idx_site_widget_type_enabled(widget_type,is_enabled,id),
    CONSTRAINT fk_site_widget_created_by FOREIGN KEY(created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS site_widget_placements (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    layout_id BIGINT UNSIGNED NOT NULL,
    widget_id BIGINT UNSIGNED NOT NULL,
    zone VARCHAR(120) NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    visibility_json LONGTEXT NULL,
    style_json LONGTEXT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    UNIQUE KEY uniq_site_widget_placement(layout_id,widget_id,zone),
    INDEX idx_site_widget_zone(layout_id,zone,sort_order,id),
    CONSTRAINT fk_site_widget_placement_layout FOREIGN KEY(layout_id) REFERENCES site_layouts(id) ON DELETE CASCADE,
    CONSTRAINT fk_site_widget_placement_widget FOREIGN KEY(widget_id) REFERENCES site_widget_instances(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS site_layout_revisions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    layout_id BIGINT UNSIGNED NOT NULL,
    snapshot_json LONGTEXT NOT NULL,
    created_by BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL,
    INDEX idx_site_layout_revision(layout_id,id),
    CONSTRAINT fk_site_layout_revision_layout FOREIGN KEY(layout_id) REFERENCES site_layouts(id) ON DELETE CASCADE,
    CONSTRAINT fk_site_layout_revision_user FOREIGN KEY(created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO site_layouts (slug,name,context_type,status,settings_json,published_at,created_at,updated_at)
VALUES ('default','Основная схема','default','published','{}',CURRENT_TIMESTAMP,CURRENT_TIMESTAMP,CURRENT_TIMESTAMP)
ON DUPLICATE KEY UPDATE updated_at=updated_at;

INSERT INTO site_widget_instances (system_key,widget_type,title,settings_json,is_enabled,created_at,updated_at) VALUES
('default-logo','core.logo','Логотип и название','{}',1,CURRENT_TIMESTAMP,CURRENT_TIMESTAMP),
('default-menu','core.menu','Главное меню','{"location":"header"}',1,CURRENT_TIMESTAMP,CURRENT_TIMESTAMP),
('default-account','core.account','Профиль и вход','{}',1,CURRENT_TIMESTAMP,CURRENT_TIMESTAMP),
('default-subscription','core.subscription','Подписка','{"title":"Получать новые публикации"}',1,CURRENT_TIMESTAMP,CURRENT_TIMESTAMP)
ON DUPLICATE KEY UPDATE updated_at=updated_at;

INSERT INTO site_widget_placements (layout_id,widget_id,zone,sort_order,visibility_json,style_json,created_at,updated_at)
SELECT l.id,w.id,'header.main',10,'{}','{}',CURRENT_TIMESTAMP,CURRENT_TIMESTAMP
FROM site_layouts l JOIN site_widget_instances w ON w.system_key='default-logo'
WHERE l.slug='default' AND NOT EXISTS (
    SELECT 1 FROM site_widget_placements p WHERE p.layout_id=l.id AND p.widget_id=w.id AND p.zone='header.main'
);

INSERT INTO site_widget_placements (layout_id,widget_id,zone,sort_order,visibility_json,style_json,created_at,updated_at)
SELECT l.id,w.id,'header.main',20,'{}','{}',CURRENT_TIMESTAMP,CURRENT_TIMESTAMP
FROM site_layouts l JOIN site_widget_instances w ON w.system_key='default-menu'
WHERE l.slug='default' AND NOT EXISTS (
    SELECT 1 FROM site_widget_placements p WHERE p.layout_id=l.id AND p.widget_id=w.id AND p.zone='header.main'
);

INSERT INTO site_widget_placements (layout_id,widget_id,zone,sort_order,visibility_json,style_json,created_at,updated_at)
SELECT l.id,w.id,'header.main',30,'{}','{}',CURRENT_TIMESTAMP,CURRENT_TIMESTAMP
FROM site_layouts l JOIN site_widget_instances w ON w.system_key='default-account'
WHERE l.slug='default' AND NOT EXISTS (
    SELECT 1 FROM site_widget_placements p WHERE p.layout_id=l.id AND p.widget_id=w.id AND p.zone='header.main'
);

INSERT INTO site_widget_placements (layout_id,widget_id,zone,sort_order,visibility_json,style_json,created_at,updated_at)
SELECT l.id,w.id,'footer.columns',10,'{}','{}',CURRENT_TIMESTAMP,CURRENT_TIMESTAMP
FROM site_layouts l JOIN site_widget_instances w ON w.system_key='default-subscription'
WHERE l.slug='default' AND NOT EXISTS (
    SELECT 1 FROM site_widget_placements p WHERE p.layout_id=l.id AND p.widget_id=w.id AND p.zone='footer.columns'
);