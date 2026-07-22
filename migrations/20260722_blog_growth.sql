CREATE TABLE IF NOT EXISTS content_redirects (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    source_path VARCHAR(255) NOT NULL,
    target_path VARCHAR(500) NOT NULL,
    status_code SMALLINT UNSIGNED NOT NULL DEFAULT 301,
    hits BIGINT UNSIGNED NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_by BIGINT UNSIGNED NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    UNIQUE KEY uniq_content_redirect_source(source_path),
    INDEX idx_content_redirect_active(is_active,id),
    CONSTRAINT fk_content_redirect_user FOREIGN KEY(created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS content_subscriptions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(190) NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'active',
    confirm_token_hash CHAR(64) NULL,
    unsubscribe_token_hash CHAR(64) NOT NULL,
    source VARCHAR(100) NULL,
    confirmed_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    UNIQUE KEY uniq_content_subscription_email(email),
    INDEX idx_content_subscription_status(status,id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS content_publication_log (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    entry_id BIGINT UNSIGNED NOT NULL,
    action VARCHAR(40) NOT NULL,
    details_json LONGTEXT NULL,
    created_at DATETIME NOT NULL,
    INDEX idx_content_publication_entry(entry_id,id),
    CONSTRAINT fk_content_publication_entry FOREIGN KEY(entry_id) REFERENCES content_entries(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO settings (`key`,`value`,updated_at) VALUES
('seo_site_title','',CURRENT_TIMESTAMP),
('seo_default_description','',CURRENT_TIMESTAMP),
('seo_robots_index','1',CURRENT_TIMESTAMP),
('seo_sitemap_enabled','1',CURRENT_TIMESTAMP),
('seo_rss_enabled','1',CURRENT_TIMESTAMP),
('subscriptions_enabled','1',CURRENT_TIMESTAMP),
('subscriptions_require_confirmation','0',CURRENT_TIMESTAMP)
ON DUPLICATE KEY UPDATE updated_at=updated_at;
