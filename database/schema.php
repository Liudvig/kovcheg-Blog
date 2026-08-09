<?php

declare(strict_types=1);

return <<<'SQL'
CREATE TABLE IF NOT EXISTS settings (
    `key` VARCHAR(100) PRIMARY KEY,
    `value` LONGTEXT NULL,
    updated_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(190) NOT NULL UNIQUE,
    username VARCHAR(80) NOT NULL UNIQUE,
    first_name VARCHAR(80) NULL,
    last_name VARCHAR(80) NULL,
    display_name VARCHAR(150) NOT NULL,
    avatar_path VARCHAR(255) NULL,
    bio TEXT NULL,
    status_text VARCHAR(190) NULL,
    birthday DATE NULL,
    password_hash VARCHAR(255) NOT NULL,
    role VARCHAR(30) NOT NULL DEFAULT 'user',
    is_verified TINYINT(1) NOT NULL DEFAULT 0,
    verification_label VARCHAR(80) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    approval_status VARCHAR(20) NOT NULL DEFAULT 'approved',
    approved_by BIGINT UNSIGNED NULL,
    approved_at DATETIME NULL,
    last_seen_at DATETIME NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    INDEX idx_users_last_seen(last_seen_at),
    INDEX idx_users_approval(approval_status,is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS user_settings (
    user_id BIGINT UNSIGNED NOT NULL,
    `key` VARCHAR(100) NOT NULL,
    `value` LONGTEXT NULL,
    updated_at DATETIME NULL,
    PRIMARY KEY(user_id,`key`),
    CONSTRAINT fk_user_settings_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS user_remember_tokens (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    selector CHAR(36) NOT NULL UNIQUE,
    validator_hash CHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    last_used_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    INDEX idx_remember_user(user_id,expires_at),
    INDEX idx_remember_expiry(expires_at),
    CONSTRAINT fk_remember_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS roles (
    slug VARCHAR(40) PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description VARCHAR(255) NULL,
    is_system TINYINT(1) NOT NULL DEFAULT 0,
    sort_order INT NOT NULL DEFAULT 100,
    created_at DATETIME NULL,
    updated_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS role_permissions (
    role_slug VARCHAR(40) NOT NULL,
    permission_key VARCHAR(80) NOT NULL,
    is_allowed TINYINT(1) NOT NULL DEFAULT 0,
    updated_at DATETIME NULL,
    PRIMARY KEY(role_slug,permission_key),
    CONSTRAINT fk_role_permission_role FOREIGN KEY(role_slug) REFERENCES roles(slug) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO roles (slug,name,description,is_system,sort_order,created_at,updated_at) VALUES
('owner','Владелец','Полный доступ',1,10,CURRENT_TIMESTAMP,CURRENT_TIMESTAMP),
('admin','Администратор','Управление системой',1,20,CURRENT_TIMESTAMP,CURRENT_TIMESTAMP),
('editor','Редактор','Управление содержимым',1,30,CURRENT_TIMESTAMP,CURRENT_TIMESTAMP),
('user','Пользователь','Стандартный доступ',1,40,CURRENT_TIMESTAMP,CURRENT_TIMESTAMP),
('guest','Гость','Ограниченный доступ',1,50,CURRENT_TIMESTAMP,CURRENT_TIMESTAMP);

CREATE TABLE IF NOT EXISTS modules (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(80) NOT NULL UNIQUE,
    name VARCHAR(150) NOT NULL,
    version VARCHAR(30) NOT NULL,
    description TEXT NULL,
    enabled TINYINT(1) NOT NULL DEFAULT 0,
    installed_at DATETIME NULL,
    updated_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS api_tokens (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(120) NOT NULL,
    token_hash CHAR(64) NOT NULL UNIQUE,
    abilities TEXT NULL,
    last_used_at DATETIME NULL,
    revoked_at DATETIME NULL,
    created_at DATETIME NULL,
    CONSTRAINT fk_token_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS webhooks (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    url VARCHAR(500) NOT NULL,
    secret_encrypted TEXT NOT NULL,
    events TEXT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NULL,
    updated_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS webhook_deliveries (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    webhook_id BIGINT UNSIGNED NOT NULL,
    event VARCHAR(120) NOT NULL,
    payload_json LONGTEXT NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    attempts INT NOT NULL DEFAULT 0,
    next_attempt_at DATETIME NULL,
    last_error TEXT NULL,
    created_at DATETIME NULL,
    delivered_at DATETIME NULL,
    INDEX idx_webhook_queue(status,next_attempt_at),
    CONSTRAINT fk_delivery_webhook FOREIGN KEY(webhook_id) REFERENCES webhooks(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS admin_notifications (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    type VARCHAR(50) NOT NULL DEFAULT 'info',
    title VARCHAR(190) NOT NULL,
    body TEXT NULL,
    url VARCHAR(500) NULL,
    actor_id BIGINT UNSIGNED NULL,
    created_at DATETIME NULL,
    INDEX idx_admin_notifications_created(created_at),
    CONSTRAINT fk_admin_notification_actor FOREIGN KEY(actor_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS admin_notification_reads (
    notification_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    read_at DATETIME NULL,
    PRIMARY KEY(notification_id,user_id),
    CONSTRAINT fk_admin_read_notification FOREIGN KEY(notification_id) REFERENCES admin_notifications(id) ON DELETE CASCADE,
    CONSTRAINT fk_admin_read_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS audit_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NULL,
    action VARCHAR(120) NOT NULL,
    entity_type VARCHAR(80) NULL,
    entity_id BIGINT UNSIGNED NULL,
    ip VARCHAR(64) NULL,
    meta_json LONGTEXT NULL,
    created_at DATETIME NULL,
    INDEX idx_audit_created(created_at),
    INDEX idx_audit_action(action)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS auth_rate_limits (
    rate_key CHAR(64) PRIMARY KEY,
    attempts INT NOT NULL DEFAULT 0,
    window_started_at DATETIME NOT NULL,
    blocked_until DATETIME NULL,
    updated_at DATETIME NULL,
    INDEX idx_auth_rate_blocked(blocked_until)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;
