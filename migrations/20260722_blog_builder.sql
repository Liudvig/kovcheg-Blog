CREATE TABLE IF NOT EXISTS content_patterns (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    owner_id BIGINT UNSIGNED NULL,
    name VARCHAR(150) NOT NULL,
    slug VARCHAR(150) NOT NULL UNIQUE,
    description VARCHAR(500) NULL,
    blocks_json LONGTEXT NOT NULL,
    scope VARCHAR(30) NOT NULL DEFAULT 'site',
    is_system TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    INDEX idx_content_pattern_owner(owner_id,id),
    CONSTRAINT fk_content_pattern_owner FOREIGN KEY(owner_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS media_folders (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    parent_id BIGINT UNSIGNED NULL,
    name VARCHAR(150) NOT NULL,
    slug VARCHAR(150) NOT NULL UNIQUE,
    sort_order INT NOT NULL DEFAULT 0,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    INDEX idx_media_folder_parent(parent_id,sort_order),
    CONSTRAINT fk_media_folder_parent FOREIGN KEY(parent_id) REFERENCES media_folders(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE media_library
    ADD COLUMN IF NOT EXISTS folder_id BIGINT UNSIGNED NULL AFTER uploader_id,
    ADD COLUMN IF NOT EXISTS caption VARCHAR(500) NULL AFTER alt_text,
    ADD COLUMN IF NOT EXISTS usage_count INT UNSIGNED NOT NULL DEFAULT 0 AFTER height,
    ADD INDEX IF NOT EXISTS idx_media_folder(folder_id,id);

CREATE TABLE IF NOT EXISTS content_autosaves (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    entry_id BIGINT UNSIGNED NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(255) NULL,
    excerpt TEXT NULL,
    content_json LONGTEXT NOT NULL,
    saved_at DATETIME NOT NULL,
    UNIQUE KEY uniq_content_autosave(entry_id,user_id),
    INDEX idx_content_autosave_user(user_id,saved_at),
    CONSTRAINT fk_content_autosave_entry FOREIGN KEY(entry_id) REFERENCES content_entries(id) ON DELETE CASCADE,
    CONSTRAINT fk_content_autosave_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS site_preset_history (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NULL,
    preset_slug VARCHAR(100) NOT NULL,
    settings_json LONGTEXT NOT NULL,
    created_at DATETIME NOT NULL,
    INDEX idx_site_preset_history(preset_slug,id),
    CONSTRAINT fk_site_preset_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS module_migrations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    module_slug VARCHAR(80) NOT NULL,
    migration VARCHAR(190) NOT NULL,
    applied_at DATETIME NOT NULL,
    UNIQUE KEY uniq_module_migration(module_slug,migration)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS user_role_history (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    previous_role VARCHAR(50) NOT NULL,
    new_role VARCHAR(50) NOT NULL,
    changed_by BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL,
    INDEX idx_user_role_history(user_id,id),
    CONSTRAINT fk_user_role_history_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_user_role_history_actor FOREIGN KEY(changed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE modules
    ADD COLUMN IF NOT EXISTS manifest_json LONGTEXT NULL,
    ADD COLUMN IF NOT EXISTS package_format INT NOT NULL DEFAULT 1,
    ADD COLUMN IF NOT EXISTS health_status VARCHAR(30) NOT NULL DEFAULT 'unknown';

INSERT IGNORE INTO media_folders (name,slug,sort_order,created_at,updated_at) VALUES
('Общее','general',0,CURRENT_TIMESTAMP,CURRENT_TIMESTAMP),
('Обложки','covers',10,CURRENT_TIMESTAMP,CURRENT_TIMESTAMP),
('Портфолио','portfolio',20,CURRENT_TIMESTAMP,CURRENT_TIMESTAMP);
