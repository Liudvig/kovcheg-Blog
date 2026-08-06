<?php

declare(strict_types=1);

namespace Kovcheg\Blog;

use Kovcheg\Auth;
use Kovcheg\DB;
use RuntimeException;

final class Blog
{
    public static function theme(): string
    {
        $slug = strtolower((string)setting('blog_theme', 'kovcheg-editorial'));
        if (!preg_match('/^[a-z][a-z0-9-]{2,79}$/', $slug)) $slug = 'kovcheg-editorial';
        if (!is_dir(BASE_PATH.'/themes/'.$slug)) $slug = 'kovcheg-editorial';
        return $slug;
    }

    public static function themeAsset(string $path): string
    {
        return app_url('/themes/'.self::theme().'/assets/'.ltrim($path, '/'));
    }

    public static function render(string $view, array $data = []): void
    {
        $theme = self::theme();
        $safeView = preg_replace('/[^a-zA-Z0-9_-]/', '', $view);
        $viewFile = BASE_PATH.'/themes/'.$theme.'/'.$safeView.'.php';
        $layoutFile = BASE_PATH.'/themes/'.$theme.'/layout.php';
        if (!is_file($viewFile) || !is_file($layoutFile)) throw new RuntimeException('Файлы темы сайта отсутствуют.');

        extract($data, EXTR_SKIP);
        $siteName = (string)setting('site_name', cfg('app.name', 'KOVCHEG CMS'));
        $menuItems = self::menu('header');
        $currentUser = Auth::user() ?? [];
        $themeAsset = static fn(string $path): string => self::themeAsset($path);
        $layoutContext = [
            'page_type'=>$safeView !== '' ? $safeView : 'default',
            'view'=>$safeView,
            'entry_type'=>isset($entry) && is_array($entry) ? (string)($entry['type'] ?? '') : '',
            'entry_id'=>isset($entry) && is_array($entry) ? (int)($entry['id'] ?? 0) : 0,
        ];

        ob_start();
        require $viewFile;
        $content = (string)ob_get_clean();
        require $layoutFile;
    }

    public static function readableVisibilitySql(string $alias = 'e'): string
    {
        if (!preg_match('/^[a-z][a-z0-9_]*$/i', $alias)) $alias = 'e';
        $column = 'COALESCE('.$alias.'.visibility,\'public\')';
        $role = (string)(Auth::user()['role'] ?? 'guest');
        if (in_array($role, ['owner','admin','editor'], true)) return $column." IN ('public','users','private')";
        if (Auth::check()) return $column." IN ('public','users')";
        return $column."='public'";
    }

    public static function canRead(array $entry): bool
    {
        if (!empty($entry['deleted_at']) || (string)($entry['status'] ?? '') !== 'published') return false;
        $publishedAt = trim((string)($entry['published_at'] ?? ''));
        if ($publishedAt !== '') {
            $publishedTime = strtotime($publishedAt);
            if ($publishedTime !== false && $publishedTime > time()) return false;
        }
        return match ((string)($entry['visibility'] ?? 'public')) {
            'public'=>true,
            'users'=>Auth::check(),
            'private'=>in_array((string)(Auth::user()['role'] ?? ''), ['owner','admin','editor'], true),
            default=>false,
        };
    }

    public static function isPubliclyReadable(array $entry): bool
    {
        if (!empty($entry['deleted_at']) || (string)($entry['status'] ?? '') !== 'published') return false;
        if ((string)($entry['visibility'] ?? 'public') !== 'public') return false;
        $publishedAt = trim((string)($entry['published_at'] ?? ''));
        if ($publishedAt === '') return true;
        $publishedTime = strtotime($publishedAt);
        return $publishedTime === false || $publishedTime <= time();
    }

    public static function entries(string $type = 'post', int $limit = 12, int $offset = 0): array
    {
        if (!in_array($type, ['post','page'], true)) return [];
        $limit = max(1, min(100, $limit));
        $offset = max(0, $offset);
        $visibilitySql = self::readableVisibilitySql('e');
        return DB::all(
            "SELECT e.*,u.display_name author_name,u.username author_username,u.avatar_path,
                (SELECT COUNT(*) FROM content_comments c WHERE c.entry_id=e.id AND c.status='approved' AND c.deleted_at IS NULL) comment_count,
                (SELECT COUNT(*) FROM content_reactions r WHERE r.entry_id=e.id) reaction_count
             FROM content_entries e
             JOIN users u ON u.id=e.author_id
             WHERE e.type=? AND e.status='published' AND {$visibilitySql}
               AND e.deleted_at IS NULL AND (e.published_at IS NULL OR e.published_at<=CURRENT_TIMESTAMP)
             ORDER BY e.is_featured DESC,e.published_at DESC,e.id DESC
             LIMIT {$limit} OFFSET {$offset}",
            [$type]
        );
    }

    public static function entry(string $slug, ?string $type = null): ?array
    {
        $slug = trim($slug);
        if ($slug === '') return null;
        $params = [$slug];
        $typeSql = '';
        if ($type !== null) {
            if (!in_array($type, ['post','page'], true)) return null;
            $typeSql = ' AND e.type=?';
            $params[] = $type;
        }
        $visibilitySql = self::readableVisibilitySql('e');
        return DB::one(
            "SELECT e.*,u.display_name author_name,u.username author_username,u.avatar_path,u.bio author_bio
             FROM content_entries e
             JOIN users u ON u.id=e.author_id
             WHERE e.slug=?{$typeSql} AND e.status='published' AND {$visibilitySql}
               AND e.deleted_at IS NULL AND (e.published_at IS NULL OR e.published_at<=CURRENT_TIMESTAMP)
             LIMIT 1",
            $params
        );
    }

    public static function storedEntry(string $slug, ?string $type = null): ?array
    {
        $slug = trim($slug);
        if ($slug === '') return null;
        $params = [$slug];
        $typeSql = '';
        if ($type !== null) {
            if (!in_array($type, ['post','page'], true)) return null;
            $typeSql = ' AND e.type=?';
            $params[] = $type;
        }
        return DB::one(
            "SELECT e.*,u.display_name author_name,u.username author_username,u.avatar_path,u.bio author_bio
             FROM content_entries e
             JOIN users u ON u.id=e.author_id
             WHERE e.slug=?{$typeSql} AND e.deleted_at IS NULL
             LIMIT 1",
            $params
        );
    }

    public static function comments(int $entryId): array
    {
        return DB::all(
            "SELECT c.*,u.display_name author_name,u.username author_username,u.avatar_path
             FROM content_comments c JOIN users u ON u.id=c.user_id
             WHERE c.entry_id=? AND c.status='approved' AND c.deleted_at IS NULL ORDER BY c.id ASC",
            [$entryId]
        );
    }

    public static function reactions(int $entryId): array
    {
        return DB::all('SELECT reaction,COUNT(*) total FROM content_reactions WHERE entry_id=? GROUP BY reaction ORDER BY total DESC,reaction ASC', [$entryId]);
    }

    public static function author(string $username): ?array
    {
        return DB::one("SELECT id,username,display_name,avatar_path,bio,status_text,is_verified,verification_label FROM users WHERE username=? AND is_active=1 AND approval_status='approved' LIMIT 1", [$username]);
    }

    public static function authorEntries(int $authorId, int $limit = 30): array
    {
        $limit = max(1, min(100, $limit));
        $visibilitySql = self::readableVisibilitySql('e');
        return DB::all(
            "SELECT e.*,u.display_name author_name,u.username author_username,u.avatar_path,
                (SELECT COUNT(*) FROM content_comments c WHERE c.entry_id=e.id AND c.status='approved' AND c.deleted_at IS NULL) comment_count,
                (SELECT COUNT(*) FROM content_reactions r WHERE r.entry_id=e.id) reaction_count
             FROM content_entries e JOIN users u ON u.id=e.author_id
             WHERE e.author_id=? AND e.type='post' AND e.status='published'
               AND {$visibilitySql} AND e.deleted_at IS NULL
               AND (e.published_at IS NULL OR e.published_at<=CURRENT_TIMESTAMP)
             ORDER BY e.published_at DESC,e.id DESC LIMIT {$limit}",
            [$authorId]
        );
    }

    public static function menu(string $location): array
    {
        try {
            $menu = DB::one('SELECT id FROM navigation_menus WHERE location=? AND is_active=1 ORDER BY id LIMIT 1', [$location]);
            return $menu ? self::menuById((int)$menu['id']) : [];
        } catch (\Throwable) {
            return [];
        }
    }

    public static function menuById(int $menuId): array
    {
        if ($menuId < 1) return [];
        try {
            $menu = DB::one('SELECT id FROM navigation_menus WHERE id=? AND is_active=1 LIMIT 1', [$menuId]);
            if (!$menu) return [];
            return DB::all('SELECT * FROM navigation_items WHERE menu_id=? AND is_enabled=1 ORDER BY sort_order,id', [$menuId]);
        } catch (\Throwable) {
            return [];
        }
    }

    public static function defaultMenu(): array
    {
        return [];
    }

    public static function entryUrl(array $entry): string
    {
        $slug = rawurlencode((string)($entry['slug'] ?? ''));
        return (string)($entry['type'] ?? 'post') === 'post'
            ? app_url('/post/'.$slug)
            : app_url('/page/'.$slug);
    }

    public static function canModerate(): bool
    {
        return Auth::check() && in_array((string)(Auth::user()['role'] ?? ''), ['owner','admin','editor','moderator'], true);
    }

    public static function excerpt(array $entry, int $length = 220): string
    {
        $excerpt = trim((string)($entry['excerpt'] ?? ''));
        if ($excerpt === '') $excerpt = trim(strip_tags((string)($entry['content_html'] ?? '')));
        return utf8_substr($excerpt, 0, max(40, $length));
    }
}
