<?php

declare(strict_types=1);

use Kovcheg\Auth;
use Kovcheg\Csrf;
use Kovcheg\DB;
use Kovcheg\Blog\Blog;
use Kovcheg\Blog\Studio;
use Kovcheg\Blog\Studio32;

require_once BASE_PATH.'/app/BlogStudio.php';
require_once BASE_PATH.'/app/BlogStudio32.php';

if (!function_exists('kovcheg_content_editor')) {
    function kovcheg_content_editor(string $type, ?array $entry = null): void
    {
        Studio::require('content');
        $type = $type === 'page' ? 'page' : 'post';
        $isNew = $entry === null;

        if ($isNew) {
            $entry = [
                'id'=>0,
                'type'=>$type,
                'status'=>'draft',
                'title'=>'',
                'slug'=>'',
                'excerpt'=>'',
                'content_json'=>'[]',
                'content_html'=>'',
                'featured_image_path'=>'',
                'visibility'=>'public',
                'comments_enabled'=>$type === 'post' ? 1 : 0,
                'reactions_enabled'=>0,
                'is_featured'=>0,
                'seo_title'=>'',
                'seo_description'=>'',
                'published_at'=>'',
                'category_ids'=>[],
            ];
        }

        if ((string)($entry['type'] ?? '') !== $type) {
            abort(404, $type === 'post' ? 'Запись не найдена.' : 'Страница не найдена.');
        }

        $id = (int)($entry['id'] ?? 0);
        $autosave = $id > 0
            ? DB::one('SELECT * FROM content_autosaves WHERE entry_id=? AND user_id=? ORDER BY saved_at DESC LIMIT 1', [$id, Auth::id()])
            : null;
        $revisions = $id > 0
            ? DB::all('SELECT r.id,r.title,r.created_at,u.display_name author_name FROM content_revisions r JOIN users u ON u.id=r.author_id WHERE r.entry_id=? ORDER BY r.id DESC LIMIT 30', [$id])
            : [];

        Studio::render('content-editor', [
            'studioSection'=>$type === 'post' ? 'posts' : 'pages',
            'studioTitle'=>$isNew
                ? ($type === 'post' ? 'Новая запись' : 'Новая страница')
                : ($type === 'post' ? 'Редактирование записи' : 'Редактирование страницы'),
            'entry'=>$entry,
            'categories'=>$type === 'post' ? DB::all('SELECT * FROM content_categories ORDER BY sort_order,name') : [],
            'media'=>DB::all("SELECT * FROM media_library WHERE mime_type LIKE 'image/%' ORDER BY id DESC LIMIT 120"),
            'autosave'=>$autosave,
            'revisions'=>$revisions,
        ]);
    }
}

if (!function_exists('kovcheg_public_content_context')) {
    function kovcheg_public_content_context(array $entry): array
    {
        $id = (int)($entry['id'] ?? 0);
        $type = (string)($entry['type'] ?? 'post') === 'page' ? 'page' : 'post';
        $categories = $type === 'post'
            ? DB::all('SELECT c.id,c.name,c.slug,c.description FROM content_categories c JOIN content_entry_categories ec ON ec.category_id=c.id WHERE ec.entry_id=? ORDER BY c.sort_order,c.name', [$id])
            : [];
        $views = (int)(DB::one('SELECT COALESCE(SUM(views),0) total FROM content_views_daily WHERE entry_id=?', [$id])['total'] ?? 0);
        $related = [];

        if ($type === 'post' && $categories) {
            $ids = array_map(static fn(array $row): int => (int)$row['id'], $categories);
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $related = DB::all(
                "SELECT DISTINCT e.id,e.type,e.title,e.slug,e.excerpt,e.featured_image_path,e.published_at,e.updated_at
                 FROM content_entries e
                 JOIN content_entry_categories ec ON ec.entry_id=e.id
                 WHERE e.id<>? AND e.type='post' AND ec.category_id IN ({$placeholders})
                   AND e.status='published' AND e.visibility='public' AND e.deleted_at IS NULL
                   AND (e.published_at IS NULL OR e.published_at<=CURRENT_TIMESTAMP)
                 ORDER BY e.is_featured DESC,e.published_at DESC,e.id DESC LIMIT 4",
                array_merge([$id], $ids)
            );
        }

        return [
            'categories'=>$categories,
            'viewCount'=>$views,
            'relatedEntries'=>$related,
        ];
    }
}

if (!function_exists('kovcheg_render_public_content')) {
    function kovcheg_render_public_content(string $slug, string $type, bool $preview = false): void
    {
        $type = $type === 'page' ? 'page' : 'post';
        $entry = $preview ? Blog::storedEntry($slug, $type) : Blog::entry($slug, $type);
        if (!$entry) abort(404, $type === 'post' ? 'Запись не найдена.' : 'Страница не найдена.');
        if ($preview && !Studio::can('content')) abort(403, 'Недостаточно прав.');

        if ($preview || (string)($entry['visibility'] ?? 'public') !== 'public') {
            header('X-Robots-Tag: noindex, nofollow, noarchive');
        }

        if (!$preview && Blog::isPubliclyReadable($entry)) {
            try {
                DB::run(
                    'INSERT INTO content_views_daily (entry_id,view_date,views) VALUES (?,CURRENT_DATE,1) ON DUPLICATE KEY UPDATE views=views+1',
                    [(int)$entry['id']]
                );
            } catch (Throwable) {
            }
        }

        Blog::render($type, array_merge([
            'title'=>(string)($entry['seo_title'] ?: $entry['title']),
            'description'=>(string)($entry['seo_description'] ?: Blog::excerpt($entry, 300)),
            'entry'=>$entry,
            'comments'=>$preview ? [] : Blog::comments((int)$entry['id']),
            'studioPreview'=>$preview,
            'publicUrl'=>Blog::entryUrl($entry),
        ], kovcheg_public_content_context($entry)));
    }
}

/* Main Studio dashboard: Posts, Categories and Pages. */
$router->get('/studio', function (): void {
    Studio::require('comments');
    $stats = DB::one("SELECT
        (SELECT COUNT(*) FROM content_entries WHERE type='post' AND deleted_at IS NULL) posts,
        (SELECT COUNT(*) FROM content_entries WHERE type='page' AND deleted_at IS NULL) pages,
        (SELECT COUNT(*) FROM content_categories) categories,
        (SELECT COUNT(*) FROM content_comments WHERE status='pending' AND deleted_at IS NULL) pending_comments,
        (SELECT COALESCE(SUM(views),0) FROM content_views_daily WHERE view_date>=DATE_SUB(CURRENT_DATE,INTERVAL 30 DAY)) views_30") ?? [];
    $recent = DB::all("SELECT e.id,e.type,e.status,e.title,e.slug,e.updated_at,u.display_name author_name
        FROM content_entries e JOIN users u ON u.id=e.author_id
        WHERE e.type IN ('post','page') AND e.deleted_at IS NULL
        ORDER BY e.updated_at DESC,e.id DESC LIMIT 12");
    $comments = DB::all("SELECT c.id,c.body,c.status,c.created_at,e.title entry_title,u.display_name author_name
        FROM content_comments c JOIN content_entries e ON e.id=c.entry_id JOIN users u ON u.id=c.user_id
        WHERE c.deleted_at IS NULL AND e.type IN ('post','page') ORDER BY c.id DESC LIMIT 8");

    Studio::render('dashboard', [
        'studioSection'=>'dashboard',
        'studioTitle'=>'Обзор',
        'stats'=>$stats,
        'recentEntries'=>$recent,
        'recentComments'=>$comments,
    ]);
});

foreach (['posts'=>'post', 'pages'=>'page'] as $section=>$type) {
    $router->get('/studio/'.$section, function () use ($section, $type): void {
        Studio::require('content');
        $status = (string)($_GET['status'] ?? '');
        $search = mb_substr(trim((string)($_GET['q'] ?? '')), 0, 150);
        Studio::render('content-list', [
            'studioSection'=>$section,
            'studioTitle'=>$type === 'post' ? 'Записи' : 'Страницы',
            'entryType'=>$type,
            'entries'=>Studio::listEntries($type, $status, $search),
            'status'=>$status,
            'search'=>$search,
        ]);
    });

    $router->get('/studio/'.$section.'/new', function () use ($type): void {
        kovcheg_content_editor($type);
    });

    $router->get('/studio/'.$section.'/{id}/edit', function (array $params) use ($type): void {
        $entry = Studio::entry((int)$params['id']);
        if (!$entry || !empty($entry['deleted_at']) || (string)$entry['type'] !== $type) {
            abort(404, $type === 'post' ? 'Запись не найдена.' : 'Страница не найдена.');
        }
        kovcheg_content_editor($type, $entry);
    });
}

$router->post('/studio/entry/save', function (): void {
    Studio::require('content');
    Csrf::validate();
    $input = $_POST;
    $input['type'] = (string)($_POST['type'] ?? '') === 'page' ? 'page' : 'post';
    $input['tags'] = '';
    if ($input['type'] === 'page') $input['category_ids'] = [];

    if (!empty($_FILES['featured_image']['name'])) {
        $media = Studio32::storeMedia($_FILES['featured_image'], Auth::id(), 0);
        $input['featured_image_path'] = (string)($media['stored_path'] ?? '');
    }

    $id = Studio32::saveEntry($input, Auth::id(), (int)($_POST['id'] ?? 0));
    $section = $input['type'] === 'post' ? 'posts' : 'pages';
    $_SESSION['flash_success'] = $input['type'] === 'post' ? 'Запись сохранена.' : 'Страница сохранена.';
    redirect('/studio/'.$section.'/'.$id.'/edit');
});

$router->post('/studio/content/autosave', function (): void {
    Studio::require('content');
    Csrf::validate();
    Studio32::autosave(
        (int)($_POST['entry_id'] ?? 0),
        Auth::id(),
        (string)($_POST['title'] ?? ''),
        (string)($_POST['excerpt'] ?? ''),
        (string)($_POST['content_json'] ?? '[]')
    );
    json_response(['ok'=>true, 'saved_at'=>date('H:i:s')]);
});

$router->post('/studio/entries/{id}/trash', function (array $params): void {
    Studio::require('content');
    Csrf::validate();
    $entry = Studio::entry((int)$params['id']);
    if (!$entry || !in_array((string)$entry['type'], ['post','page'], true)) abort(404, 'Материал не найден.');
    DB::run('UPDATE content_entries SET deleted_at=CURRENT_TIMESTAMP,updated_at=CURRENT_TIMESTAMP WHERE id=?', [(int)$entry['id']]);
    audit('cms.content.trash', 'content_entry', (int)$entry['id']);
    $_SESSION['flash_success'] = 'Материал перемещён в корзину.';
    redirect((string)$entry['type'] === 'post' ? '/studio/posts' : '/studio/pages');
});

$router->post('/studio/entries/{id}/duplicate', function (array $params): void {
    Studio::require('content');
    Csrf::validate();
    $source = Studio::entry((int)$params['id']);
    if (!$source || !in_array((string)$source['type'], ['post','page'], true)) abort(404, 'Материал не найден.');
    $copy = $source;
    $copy['title'] = 'Копия — '.$source['title'];
    $copy['slug'] = '';
    $copy['status'] = 'draft';
    $copy['published_at'] = '';
    $copy['tags'] = '';
    if ((string)$source['type'] === 'page') $copy['category_ids'] = [];
    $id = Studio32::saveEntry($copy, Auth::id());
    $section = (string)$source['type'] === 'post' ? 'posts' : 'pages';
    $_SESSION['flash_success'] = 'Создана копия материала.';
    redirect('/studio/'.$section.'/'.$id.'/edit');
});

$router->get('/studio/content/{id}/preview', function (array $params): void {
    Studio::require('content');
    $entry = Studio::entry((int)$params['id']);
    if (!$entry || !in_array((string)$entry['type'], ['post','page'], true)) abort(404, 'Материал не найден.');
    if (Blog::canRead($entry)) {
        header('Location: '.Blog::entryUrl($entry), true, 302);
        exit;
    }
    kovcheg_render_public_content((string)$entry['slug'], (string)$entry['type'], true);
});

/* Canonical public routes. */
$router->get('/post/{slug}', function (array $params): void {
    kovcheg_render_public_content((string)($params['slug'] ?? ''), 'post');
});
$router->get('/page/{slug}', function (array $params): void {
    kovcheg_render_public_content((string)($params['slug'] ?? ''), 'page');
});

/* Compatibility for old KOVCHEG Blog links. */
$router->get('/blog/{slug}', function (array $params): void {
    $entry = Blog::storedEntry((string)($params['slug'] ?? ''));
    if (!$entry) abort(404, 'Материал не найден.');
    header('Location: '.Blog::entryUrl($entry), true, 301);
    exit;
});
$router->get('/portfolio/{slug}', function (array $params): void {
    $entry = Blog::storedEntry((string)($params['slug'] ?? ''));
    if (!$entry) {
        header('Location: '.app_url('/'), true, 302);
        exit;
    }
    header('Location: '.Blog::entryUrl($entry), true, 301);
    exit;
});
$router->get('/blog', function (): void {
    header('Location: '.app_url('/'), true, 301);
    exit;
});
$router->get('/portfolio', function (): void {
    header('Location: '.app_url('/'), true, 301);
    exit;
});

/* Home is a feed of Posts. A section becomes “Новости”, “Блог” or anything else through Categories and Menus. */
$router->get('/', function (): void {
    Blog::render('home', [
        'title'=>(string)setting('blog_home_title', setting('site_name', 'KOVCHEG CMS')),
        'posts'=>Blog::entries('post', max(6, min(36, (int)setting('blog_posts_per_page', '18')))),
    ]);
});

$router->get('/category/{slug}', function (array $params): void {
    $term = DB::one('SELECT id,name,slug,description FROM content_categories WHERE slug=? LIMIT 1', [(string)$params['slug']]);
    if (!$term) abort(404, 'Рубрика не найдена.');
    $entries = DB::all("SELECT e.*,u.display_name author_name,u.username author_username,u.avatar_path,
        (SELECT COUNT(*) FROM content_comments c WHERE c.entry_id=e.id AND c.status='approved' AND c.deleted_at IS NULL) comment_count,
        (SELECT COUNT(*) FROM content_reactions r WHERE r.entry_id=e.id) reaction_count
        FROM content_entries e JOIN users u ON u.id=e.author_id JOIN content_entry_categories ec ON ec.entry_id=e.id
        WHERE ec.category_id=? AND e.type='post' AND e.status='published' AND e.visibility='public' AND e.deleted_at IS NULL
          AND (e.published_at IS NULL OR e.published_at<=CURRENT_TIMESTAMP)
        ORDER BY e.is_featured DESC,e.published_at DESC,e.id DESC LIMIT 100", [(int)$term['id']]);
    Blog::render('archive', [
        'title'=>(string)$term['name'],
        'archiveTitle'=>(string)$term['name'],
        'archiveDescription'=>(string)($term['description'] ?? 'Записи рубрики.'),
        'entries'=>$entries,
        'entryType'=>'category',
        'category'=>$term,
    ]);
});

$router->get('/search', function (): void {
    $q = mb_substr(trim((string)($_GET['q'] ?? '')), 0, 120);
    $date = trim((string)($_GET['date'] ?? ''));
    $entries = [];
    $where = [];
    $params = [];

    if (mb_strlen($q) >= 2) {
        $like = '%'.$q.'%';
        $where[] = '(e.title LIKE ? OR e.excerpt LIKE ? OR e.content_html LIKE ?)';
        array_push($params, $like, $like, $like);
    }
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        $where[] = 'DATE(COALESCE(e.published_at,e.created_at))=?';
        $params[] = $date;
    }

    if ($where) {
        $entries = DB::all("SELECT e.*,u.display_name author_name,u.username author_username,u.avatar_path,
            (SELECT COUNT(*) FROM content_comments c WHERE c.entry_id=e.id AND c.status='approved' AND c.deleted_at IS NULL) comment_count,
            (SELECT COUNT(*) FROM content_reactions r WHERE r.entry_id=e.id) reaction_count
            FROM content_entries e JOIN users u ON u.id=e.author_id
            WHERE e.type IN ('post','page') AND e.status='published' AND e.visibility='public' AND e.deleted_at IS NULL
              AND ".implode(' AND ', $where)." ORDER BY e.is_featured DESC,e.published_at DESC,e.id DESC LIMIT 100", $params);
    }

    $heading = $date !== '' ? 'Материалы за '.$date : ($q !== '' ? 'Поиск: '.$q : 'Поиск');
    Blog::render('archive', [
        'title'=>'Поиск',
        'archiveTitle'=>$heading,
        'archiveDescription'=>'Найдено: '.count($entries),
        'entries'=>$entries,
        'entryType'=>'search',
        'searchQuery'=>$q,
    ]);
});
