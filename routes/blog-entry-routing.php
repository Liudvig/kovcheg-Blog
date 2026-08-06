<?php

declare(strict_types=1);

use Kovcheg\DB;
use Kovcheg\Blog\Blog;
use Kovcheg\Blog\Studio;

if (!function_exists('kovcheg_public_entry_context')) {
    function kovcheg_public_entry_context(array $entry): array
    {
        $id = (int)($entry['id'] ?? 0);
        $type = (string)($entry['type'] ?? 'post');
        $categories = $type === 'post' ? DB::all(
            'SELECT c.id,c.name,c.slug FROM content_categories c JOIN content_entry_categories ec ON ec.category_id=c.id WHERE ec.entry_id=? ORDER BY c.sort_order,c.name',
            [$id]
        ) : [];
        $views = (int)(DB::one('SELECT COALESCE(SUM(views),0) total FROM content_views_daily WHERE entry_id=?', [$id])['total'] ?? 0);
        $visibilitySql = Blog::readableVisibilitySql('e');
        $related = DB::all(
            "SELECT e.id,e.type,e.title,e.slug,e.excerpt,e.featured_image_path,e.published_at,u.display_name author_name,u.username author_username
             FROM content_entries e
             JOIN users u ON u.id=e.author_id
             WHERE e.id<>? AND e.type=? AND e.status='published' AND {$visibilitySql}
               AND e.deleted_at IS NULL AND (e.published_at IS NULL OR e.published_at<=CURRENT_TIMESTAMP)
             ORDER BY e.published_at DESC,e.id DESC LIMIT 3",
            [$id, $type]
        );

        return [
            'categories' => $categories,
            'tags' => [],
            'portfolioMeta' => [],
            'viewCount' => $views,
            'relatedEntries' => $related,
        ];
    }
}

if (!function_exists('kovcheg_record_public_entry_view')) {
    function kovcheg_record_public_entry_view(int $entryId): void
    {
        if ($entryId < 1) return;
        try {
            DB::run(
                'INSERT INTO content_views_daily (entry_id,view_date,views) VALUES (?,CURRENT_DATE,1) ON DUPLICATE KEY UPDATE views=views+1',
                [$entryId]
            );
        } catch (Throwable) {
        }
    }
}

if (!function_exists('kovcheg_render_entry_record')) {
    function kovcheg_render_entry_record(array $entry, bool $editorFinalView = false): void
    {
        if ($editorFinalView || (string)($entry['visibility'] ?? 'public') !== 'public') {
            header('X-Robots-Tag: noindex, nofollow, noarchive');
        }
        if (!$editorFinalView && Blog::isPubliclyReadable($entry)) {
            kovcheg_record_public_entry_view((int)$entry['id']);
        }
        Blog::render('entry', array_merge([
            'title' => (string)($entry['seo_title'] ?: $entry['title']),
            'description' => (string)($entry['seo_description'] ?: Blog::excerpt($entry, 300)),
            'entry' => $entry,
            'comments' => $editorFinalView ? [] : Blog::comments((int)$entry['id']),
            'reactions' => $editorFinalView ? [] : Blog::reactions((int)$entry['id']),
            'editorFinalView' => $editorFinalView,
        ], kovcheg_public_entry_context($entry)));
    }
}

if (!function_exists('kovcheg_render_public_entry')) {
    function kovcheg_render_public_entry(string $slug, string $type): void
    {
        $slug = trim($slug);
        $type = $type === 'page' ? 'page' : 'post';
        $notFound = $type === 'page' ? 'Страница не найдена.' : 'Запись не найдена.';
        if ($slug === '') abort(404, $notFound);

        $entry = Blog::entry($slug, $type);
        if ($entry) {
            kovcheg_render_entry_record($entry);
            return;
        }

        $stored = Blog::storedEntry($slug, $type);
        if ($stored && Studio::can('content')) {
            kovcheg_render_entry_record($stored, true);
            return;
        }

        $other = Blog::entry($slug);
        if ($other && in_array((string)($other['type'] ?? ''), ['post','page'], true)) {
            header('Location: '.Blog::entryUrl($other), true, 301);
            exit;
        }

        $storedOther = Blog::storedEntry($slug);
        if ($storedOther && Studio::can('content') && in_array((string)($storedOther['type'] ?? ''), ['post','page'], true)) {
            header('Location: '.Blog::entryUrl($storedOther), true, 302);
            exit;
        }

        abort(404, $notFound);
    }
}

$router->get('/blog/{slug}', static function (array $params): void {
    kovcheg_render_public_entry((string)($params['slug'] ?? ''), 'post');
});

$router->get('/page/{slug}', static function (array $params): void {
    kovcheg_render_public_entry((string)($params['slug'] ?? ''), 'page');
});
