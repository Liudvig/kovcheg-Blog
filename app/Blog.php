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
        if (!preg_match('/^[a-z][a-z0-9-]{2,79}$/', $slug)) {
            $slug = 'kovcheg-editorial';
        }

        if (!is_dir(BASE_PATH.'/themes/'.$slug)) {
            $slug = 'kovcheg-editorial';
        }

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

        if (!is_file($viewFile) || !is_file($layoutFile)) {
            throw new RuntimeException('Файлы темы блога отсутствуют.');
        }

        extract($data, EXTR_SKIP);
        $siteName = (string)setting('site_name', cfg('app.name', 'KOVCHEG Blog'));
        $menuItems = self::menu('header');
        $currentUser = Auth::user() ?? [];
        $themeAsset = static fn(string $path): string => self::themeAsset($path);

        ob_start();
        require $viewFile;
        $content = (string)ob_get_clean();

        require $layoutFile;
    }

    public static function entries(string $type, int $limit = 12, int $offset = 0): array
    {
        $allowed = ['post', 'page', 'portfolio'];
        if (!in_array($type, $allowed, true)) {
            return [];
        }

        $limit = max(1, min(100, $limit));
        $offset = max(0, $offset);

        return DB::all(
            "SELECT e.*,u.display_name author_name,u.username author_username,u.avatar_path,
                (SELECT COUNT(*) FROM content_comments c WHERE c.entry_id=e.id AND c.status='approved' AND c.deleted_at IS NULL) comment_count,
                (SELECT COUNT(*) FROM content_reactions r WHERE r.entry_id=e.id) reaction_count
             FROM content_entries e
             JOIN users u ON u.id=e.author_id
             WHERE e.type=? AND e.status='published' AND e.visibility='public'
               AND e.deleted_at IS NULL AND (e.published_at IS NULL OR e.published_at<=CURRENT_TIMESTAMP)
             ORDER BY e.is_featured DESC,e.published_at DESC,e.id DESC
             LIMIT {$limit} OFFSET {$offset}",
            [$type]
        );
    }

    public static function entry(string $slug, ?string $type = null): ?array
    {
        $slug = trim($slug);
        if ($slug === '') {
            return null;
        }

        $params = [$slug];
        $typeSql = '';
        if ($type !== null) {
            $typeSql = ' AND e.type=?';
            $params[] = $type;
        }

        return DB::one(
            "SELECT e.*,u.display_name author_name,u.username author_username,u.avatar_path,u.bio author_bio
             FROM content_entries e
             JOIN users u ON u.id=e.author_id
             WHERE e.slug=?{$typeSql} AND e.status='published' AND e.visibility='public'
               AND e.deleted_at IS NULL AND (e.published_at IS NULL OR e.published_at<=CURRENT_TIMESTAMP)
             LIMIT 1",
            $params
        );
    }

    public static function comments(int $entryId): array
    {
        return DB::all(
            "SELECT c.*,u.display_name author_name,u.username author_username,u.avatar_path
             FROM content_comments c
             JOIN users u ON u.id=c.user_id
             WHERE c.entry_id=? AND c.status='approved' AND c.deleted_at IS NULL
             ORDER BY c.id ASC",
            [$entryId]
        );
    }

    public static function reactions(int $entryId): array
    {
        return DB::all(
            'SELECT reaction,COUNT(*) total FROM content_reactions WHERE entry_id=? GROUP BY reaction ORDER BY total DESC,reaction ASC',
            [$entryId]
        );
    }

    public static function author(string $username): ?array
    {
        return DB::one(
            "SELECT id,username,display_name,avatar_path,bio,status_text,is_verified,verification_label
             FROM users WHERE username=? AND is_active=1 AND approval_status='approved' LIMIT 1",
            [$username]
        );
    }

    public static function authorEntries(int $authorId, int $limit = 30): array
    {
        $limit = max(1, min(100, $limit));
        return DB::all(
            "SELECT e.*,u.display_name author_name,u.username author_username,u.avatar_path,
                (SELECT COUNT(*) FROM content_comments c WHERE c.entry_id=e.id AND c.status='approved' AND c.deleted_at IS NULL) comment_count,
                (SELECT COUNT(*) FROM content_reactions r WHERE r.entry_id=e.id) reaction_count
             FROM content_entries e
             JOIN users u ON u.id=e.author_id
             WHERE e.author_id=? AND e.type IN ('post','portfolio') AND e.status='published'
               AND e.visibility='public' AND e.deleted_at IS NULL
               AND (e.published_at IS NULL OR e.published_at<=CURRENT_TIMESTAMP)
             ORDER BY e.published_at DESC,e.id DESC LIMIT {$limit}",
            [$authorId]
        );
    }

    public static function menu(string $location): array
    {
        try {
            $menu = DB::one('SELECT id FROM navigation_menus WHERE location=? AND is_active=1 ORDER BY id LIMIT 1', [$location]);
            if (!$menu) {
                return self::defaultMenu();
            }

            $items = DB::all(
                'SELECT * FROM navigation_items WHERE menu_id=? AND is_enabled=1 ORDER BY sort_order,id',
                [(int)$menu['id']]
            );

            return $items ?: self::defaultMenu();
        } catch (\Throwable) {
            return self::defaultMenu();
        }
    }

    public static function defaultMenu(): array
    {
        return [
            ['label' => 'Главная', 'url' => app_url('/'), 'parent_id' => null],
            ['label' => 'Блог', 'url' => app_url('/blog'), 'parent_id' => null],
            ['label' => 'Портфолио', 'url' => app_url('/portfolio'), 'parent_id' => null],
        ];
    }

    public static function entryUrl(array $entry): string
    {
        return match ((string)($entry['type'] ?? 'post')) {
            'page' => app_url('/page/'.rawurlencode((string)$entry['slug'])),
            'portfolio' => app_url('/portfolio/'.rawurlencode((string)$entry['slug'])),
            default => app_url('/blog/'.rawurlencode((string)$entry['slug'])),
        };
    }

    public static function canModerate(): bool
    {
        if (!Auth::check()) {
            return false;
        }

        return in_array((string)(Auth::user()['role'] ?? ''), ['owner', 'admin', 'editor', 'moderator'], true);
    }

    public static function excerpt(array $entry, int $length = 220): string
    {
        $excerpt = trim((string)($entry['excerpt'] ?? ''));
        if ($excerpt === '') {
            $excerpt = trim(strip_tags((string)($entry['content_html'] ?? '')));
        }

        return utf8_substr($excerpt, 0, max(40, $length));
    }
}
