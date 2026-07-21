CREATE TABLE IF NOT EXISTS migrations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    migration VARCHAR(190) NOT NULL UNIQUE,
    batch INT NOT NULL DEFAULT 1,
    applied_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO roles (slug,name,description,is_system,sort_order,created_at,updated_at)
VALUES ('moderator','Модератор','Модерация комментариев и пользовательского контента',1,30,CURRENT_TIMESTAMP,CURRENT_TIMESTAMP);

CREATE TABLE IF NOT EXISTS content_entries (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    author_id BIGINT UNSIGNED NOT NULL,
    type VARCHAR(30) NOT NULL DEFAULT 'post',
    status VARCHAR(20) NOT NULL DEFAULT 'draft',
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(190) NOT NULL,
    excerpt TEXT NULL,
    content_json LONGTEXT NULL,
    content_html LONGTEXT NULL,
    featured_image_path VARCHAR(255) NULL,
    template VARCHAR(80) NULL,
    visibility VARCHAR(20) NOT NULL DEFAULT 'public',
    comments_enabled TINYINT(1) NOT NULL DEFAULT 1,
    reactions_enabled TINYINT(1) NOT NULL DEFAULT 1,
    is_featured TINYINT(1) NOT NULL DEFAULT 0,
    sort_order INT NOT NULL DEFAULT 0,
    seo_title VARCHAR(255) NULL,
    seo_description VARCHAR(320) NULL,
    published_at DATETIME NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    deleted_at DATETIME NULL,
    UNIQUE KEY uniq_content_slug(slug),
    INDEX idx_content_type_status(type,status,published_at),
    INDEX idx_content_author(author_id,status),
    INDEX idx_content_featured(type,is_featured,status),
    CONSTRAINT fk_content_author FOREIGN KEY(author_id) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS content_revisions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    entry_id BIGINT UNSIGNED NOT NULL,
    author_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    excerpt TEXT NULL,
    content_json LONGTEXT NULL,
    content_html LONGTEXT NULL,
    created_at DATETIME NULL,
    INDEX idx_content_revision_entry(entry_id,id),
    CONSTRAINT fk_content_revision_entry FOREIGN KEY(entry_id) REFERENCES content_entries(id) ON DELETE CASCADE,
    CONSTRAINT fk_content_revision_author FOREIGN KEY(author_id) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS content_categories (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    parent_id BIGINT UNSIGNED NULL,
    name VARCHAR(150) NOT NULL,
    slug VARCHAR(150) NOT NULL UNIQUE,
    description TEXT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    INDEX idx_content_category_parent(parent_id,sort_order),
    CONSTRAINT fk_content_category_parent FOREIGN KEY(parent_id) REFERENCES content_categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS content_entry_categories (
    entry_id BIGINT UNSIGNED NOT NULL,
    category_id BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY(entry_id,category_id),
    INDEX idx_content_entry_category(category_id,entry_id),
    CONSTRAINT fk_content_entry_category_entry FOREIGN KEY(entry_id) REFERENCES content_entries(id) ON DELETE CASCADE,
    CONSTRAINT fk_content_entry_category_category FOREIGN KEY(category_id) REFERENCES content_categories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS content_tags (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    slug VARCHAR(120) NOT NULL UNIQUE,
    created_at DATETIME NULL,
    updated_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS content_entry_tags (
    entry_id BIGINT UNSIGNED NOT NULL,
    tag_id BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY(entry_id,tag_id),
    INDEX idx_content_entry_tag(tag_id,entry_id),
    CONSTRAINT fk_content_entry_tag_entry FOREIGN KEY(entry_id) REFERENCES content_entries(id) ON DELETE CASCADE,
    CONSTRAINT fk_content_entry_tag_tag FOREIGN KEY(tag_id) REFERENCES content_tags(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS content_comments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    entry_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    parent_id BIGINT UNSIGNED NULL,
    body TEXT NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    ip_hash CHAR(64) NULL,
    user_agent VARCHAR(255) NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    deleted_at DATETIME NULL,
    INDEX idx_content_comment_entry(entry_id,status,id),
    INDEX idx_content_comment_user(user_id,id),
    INDEX idx_content_comment_parent(parent_id,id),
    CONSTRAINT fk_content_comment_entry FOREIGN KEY(entry_id) REFERENCES content_entries(id) ON DELETE CASCADE,
    CONSTRAINT fk_content_comment_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_content_comment_parent FOREIGN KEY(parent_id) REFERENCES content_comments(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS content_reactions (
    entry_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    reaction VARCHAR(32) NOT NULL,
    created_at DATETIME NULL,
    PRIMARY KEY(entry_id,user_id,reaction),
    INDEX idx_content_reaction_user(user_id,created_at),
    CONSTRAINT fk_content_reaction_entry FOREIGN KEY(entry_id) REFERENCES content_entries(id) ON DELETE CASCADE,
    CONSTRAINT fk_content_reaction_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS media_library (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uploader_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(190) NULL,
    alt_text VARCHAR(255) NULL,
    original_name VARCHAR(255) NOT NULL,
    stored_path VARCHAR(255) NOT NULL,
    mime_type VARCHAR(150) NOT NULL,
    file_size BIGINT UNSIGNED NOT NULL,
    width INT NULL,
    height INT NULL,
    metadata_json LONGTEXT NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    INDEX idx_media_uploader(uploader_id,id),
    INDEX idx_media_mime(mime_type,id),
    CONSTRAINT fk_media_uploader FOREIGN KEY(uploader_id) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS navigation_menus (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    location VARCHAR(80) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NULL,
    updated_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS navigation_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    menu_id BIGINT UNSIGNED NOT NULL,
    parent_id BIGINT UNSIGNED NULL,
    label VARCHAR(150) NOT NULL,
    url VARCHAR(500) NULL,
    target_type VARCHAR(30) NOT NULL DEFAULT 'custom',
    target_id BIGINT UNSIGNED NULL,
    icon VARCHAR(80) NULL,
    css_class VARCHAR(120) NULL,
    sort_order INT NOT NULL DEFAULT 0,
    is_enabled TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    INDEX idx_navigation_item_menu(menu_id,parent_id,sort_order),
    CONSTRAINT fk_navigation_item_menu FOREIGN KEY(menu_id) REFERENCES navigation_menus(id) ON DELETE CASCADE,
    CONSTRAINT fk_navigation_item_parent FOREIGN KEY(parent_id) REFERENCES navigation_items(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS themes (
    slug VARCHAR(80) PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    version VARCHAR(30) NOT NULL,
    description TEXT NULL,
    author VARCHAR(150) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 0,
    installed_at DATETIME NULL,
    updated_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO navigation_menus (name,slug,location,is_active,created_at,updated_at)
VALUES ('Главное меню','primary','header',1,CURRENT_TIMESTAMP,CURRENT_TIMESTAMP);
