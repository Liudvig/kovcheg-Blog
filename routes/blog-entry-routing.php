<?php

declare(strict_types=1);

use Kovcheg\DB;
use Kovcheg\Blog\Blog;
use Kovcheg\Blog\Studio;

if (!function_exists('kovcheg_public_entry_context')) {
    function kovcheg_public_entry_context(array $entry): array
    {
        $id = (int)($entry['id'] ?? 0);
        $categories = DB::all(
            'SELECT c.id,c.name,c.slug FROM content_categories c JOIN content_entry_categories ec ON ec.category_id=c.id WHERE ec.entry_id=? ORDER BY c.sort_order,c.name',
            [$id]
        );
        $tags = DB::all(
            'SELECT t.id,t.name,t.slug FROM content_tags t JOIN content_entry_tags et ON et.tag_id=t.id WHERE et.entry_id=? ORDER BY t.name',
            [$id]
        );
        $meta = [];
        foreach (DB::all('SELECT meta_key,meta_value FROM content_entry_meta WHERE entry_id=?', [$id]) as $item) {
            $meta[(string)$item['meta_key']] = (string)($item['meta_value'] ?? '');
        }
        $views = (int)(DB::one('SELECT COALESCE(SUM(views),0) total FROM content_views_daily WHERE entry_id=?', [$id])['total'] ?? 0);
        $visibilitySql = Blog::readableVisibilitySql('e');
        $related = DB::all(
            "SELECT e.id,e.type,e.title,e.slug,e.excerpt,e.featured_image_path,e.published_at,u.display_name author_name,u.username author_username
             FROM content_entries e
             JOIN users u ON u.id=e.author_id
             WHERE e.id<>? AND e.type=? AND e.status='published' AND {$visibilitySql}
               AND e.deleted_at IS NULL AND (e.published_at IS NULL OR e.published_at<=CURRENT_TIMESTAMP)
             ORDER BY e.published_at DESC,e.id DESC LIMIT 3",
            [$id, (string)($entry['type'] ?? 'post')]
        );

        return [
            'categories' => $categories,
            'tags' => $tags,
            'portfolioMeta' => $meta,
            'viewCount' => $views,
            'relatedEntries' => $related,
        ];
    }
}

if (!function_exists('kovcheg_record_public_entry_view')) {
    function kovcheg_record_public_entry_view(int $entryId): void
    {
        if ($entryId < 1) {
            return;
        }

        try {
            DB::run(
                'INSERT INTO content_views_daily (entry_id,view_date,views) VALUES (?,CURRENT_DATE,1) ON DUPLICATE KEY UPDATE views=views+1',
                [$entryId]
            );
        } catch (Throwable) {
        }
    }
}

if (!function_exists('kovcheg_render_public_entry')) {
    function kovcheg_render_public_entry(string $slug, string $type): void
    {
        $slug = trim($slug);
        $labels = [
            'post' => 'Публикация не найдена.',
            'page' => 'Страница не найдена.',
            'portfolio' => 'Работа портфолио не найдена.',
        ];

        if ($slug === '') {
            abort(404, $labels[$type] ?? 'Материал не найден.');
        }

        $entry = Blog::entry($slug, $type);
        if (!$entry) {
            $stored = Blog::storedEntry($slug, $type);
            if ($stored && Studio::can('content')) {
                redirect('/studio/content/'.(int)$stored['id'].'/preview');
            }

            $other = Blog::entry($slug);
            if ($other) {
                header('Location: '.Blog::entryUrl($other), true, 301);
                exit;
            }

            $storedOther = Blog::storedEntry($slug);
            if ($storedOther && Studio::can('content')) {
                redirect('/studio/content/'.(int)$storedOther['id'].'/preview');
            }

            abort(404, $labels[$type] ?? 'Материал не найден.');
        }

        if ((string)($entry['visibility'] ?? 'public') !== 'public') {
            header('X-Robots-Tag: noindex, nofollow, noarchive');
        }

        kovcheg_record_public_entry_view((int)$entry['id']);
        Blog::render('entry', array_merge([
            'title' => (string)($entry['seo_title'] ?: $entry['title']),
            'description' => (string)($entry['seo_description'] ?: Blog::excerpt($entry, 300)),
            'entry' => $entry,
            'comments' => Blog::comments((int)$entry['id']),
            'reactions' => Blog::reactions((int)$entry['id']),
        ], kovcheg_public_entry_context($entry)));
    }
}

$router->get('/blog/{slug}', static function (array $params): void {
    kovcheg_render_public_entry((string)($params['slug'] ?? ''), 'post');
});

$router->get('/page/{slug}', static function (array $params): void {
    kovcheg_render_public_entry((string)($params['slug'] ?? ''), 'page');
});

$router->get('/portfolio/{slug}', static function (array $params): void {
    kovcheg_render_public_entry((string)($params['slug'] ?? ''), 'portfolio');
});
