CREATE TABLE IF NOT EXISTS vk_media_albums (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 user_id BIGINT UNSIGNED NOT NULL,
 title VARCHAR(190) NOT NULL,
 description TEXT NULL,
 created_at DATETIME NULL,
 updated_at DATETIME NULL,
 INDEX idx_vk_media_albums_user(user_id,id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS vk_media_items (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 user_id BIGINT UNSIGNED NOT NULL,
 album_id BIGINT UNSIGNED NULL,
 media_type VARCHAR(20) NOT NULL,
 title VARCHAR(190) NULL,
 original_name VARCHAR(255) NOT NULL,
 stored_path VARCHAR(255) NOT NULL,
 mime_type VARCHAR(150) NOT NULL,
 file_size BIGINT UNSIGNED NOT NULL,
 created_at DATETIME NULL,
 updated_at DATETIME NULL,
 INDEX idx_vk_media_items_user_type(user_id,media_type,id),
 INDEX idx_vk_media_items_album(album_id,id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS vk_media_playlists (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 user_id BIGINT UNSIGNED NOT NULL,
 title VARCHAR(190) NOT NULL,
 description TEXT NULL,
 created_at DATETIME NULL,
 updated_at DATETIME NULL,
 INDEX idx_vk_media_playlists_user(user_id,id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS vk_media_playlist_items (
 playlist_id BIGINT UNSIGNED NOT NULL,
 item_id BIGINT UNSIGNED NOT NULL,
 sort_order INT NOT NULL DEFAULT 0,
 created_at DATETIME NULL,
 PRIMARY KEY(playlist_id,item_id),
 INDEX idx_vk_media_playlist_items_order(playlist_id,sort_order,item_id),
 INDEX idx_vk_media_playlist_items_item(item_id,playlist_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
