<?php

declare(strict_types=1);

use Kovcheg\Auth;
use Kovcheg\Csrf;
use Kovcheg\DB;
use Kovcheg\Blog\Studio;

require_once BASE_PATH.'/app/BlogStudio.php';

$router->post('/studio/revisions/{id}/restore', function (array $params): void {
    Studio::require('content');
    Csrf::validate();

    $revision = DB::one('SELECT * FROM content_revisions WHERE id=?', [(int)$params['id']]);
    if (!$revision) abort(404, 'Ревизия не найдена.');
    $entry = Studio::entry((int)$revision['entry_id']);
    if (!$entry || !in_array((string)$entry['type'], ['post','page'], true)) abort(404, 'Материал не найден.');

    DB::pdo()->beginTransaction();
    try {
        DB::run(
            'INSERT INTO content_revisions (entry_id,author_id,title,excerpt,content_json,content_html,created_at)
             VALUES (?,?,?,?,?,?,CURRENT_TIMESTAMP)',
            [$entry['id'],Auth::id(),$entry['title'],$entry['excerpt'],$entry['content_json'],$entry['content_html']]
        );
        DB::run(
            'UPDATE content_entries SET title=?,excerpt=?,content_json=?,content_html=?,updated_at=CURRENT_TIMESTAMP WHERE id=?',
            [$revision['title'],$revision['excerpt'],$revision['content_json'],$revision['content_html'],$entry['id']]
        );
        DB::pdo()->commit();
    } catch (Throwable $error) {
        if (DB::pdo()->inTransaction()) DB::pdo()->rollBack();
        throw $error;
    }

    audit('cms.revision.restore', 'content_entry', (int)$entry['id'], ['revision_id'=>(int)$revision['id']]);
    $_SESSION['flash_success'] = 'Ревизия восстановлена.';
    $section = (string)$entry['type'] === 'post' ? 'posts' : 'pages';
    redirect('/studio/'.$section.'/'.(int)$entry['id'].'/edit');
});

$router->get('/studio/comments', function (): void {
    Studio::require('comments');
    $status = in_array((string)($_GET['status'] ?? ''), ['pending','approved','spam'], true)
        ? (string)$_GET['status']
        : '';
    $where = "c.deleted_at IS NULL AND e.type IN ('post','page')";
    $params = [];
    if ($status !== '') {
        $where .= ' AND c.status=?';
        $params[] = $status;
    }

    $comments = DB::all(
        "SELECT c.*,e.title entry_title,e.slug entry_slug,e.type entry_type,
                u.display_name author_name,u.username author_username
         FROM content_comments c
         JOIN content_entries e ON e.id=c.entry_id
         JOIN users u ON u.id=c.user_id
         WHERE {$where}
         ORDER BY c.id DESC LIMIT 300",
        $params
    );

    Studio::render('comments', [
        'studioSection'=>'comments',
        'studioTitle'=>'Комментарии',
        'comments'=>$comments,
        'status'=>$status,
    ]);
});

$router->post('/studio/comments/{id}/{action}', function (array $params): void {
    Studio::require('comments');
    Csrf::validate();

    $id = (int)$params['id'];
    $action = (string)$params['action'];
    if (!DB::one('SELECT id FROM content_comments WHERE id=? AND deleted_at IS NULL', [$id])) {
        abort(404, 'Комментарий не найден.');
    }

    if ($action === 'approve') {
        DB::run("UPDATE content_comments SET status='approved',updated_at=CURRENT_TIMESTAMP WHERE id=?", [$id]);
    } elseif ($action === 'pending') {
        DB::run("UPDATE content_comments SET status='pending',updated_at=CURRENT_TIMESTAMP WHERE id=?", [$id]);
    } elseif ($action === 'spam') {
        DB::run("UPDATE content_comments SET status='spam',updated_at=CURRENT_TIMESTAMP WHERE id=?", [$id]);
    } elseif ($action === 'delete') {
        DB::run('UPDATE content_comments SET deleted_at=CURRENT_TIMESTAMP,updated_at=CURRENT_TIMESTAMP WHERE id=?', [$id]);
    } else {
        abort(422, 'Неизвестное действие.');
    }

    audit('cms.comment.'.$action, 'content_comment', $id);
    $_SESSION['flash_success'] = 'Комментарий обновлён.';
    redirect('/studio/comments');
});

$router->get('/studio/media', function (): void {
    Studio::require('media');
    Studio::render('media', [
        'studioSection'=>'media',
        'studioTitle'=>'Медиатека',
        'media'=>DB::all(
            'SELECT m.*,u.display_name uploader_name
             FROM media_library m JOIN users u ON u.id=m.uploader_id
             ORDER BY m.id DESC LIMIT 300'
        ),
    ]);
});

$router->post('/studio/media/upload', function (): void {
    Studio::require('media');
    Csrf::validate();

    $files = $_FILES['media'] ?? null;
    if (!$files || !is_array($files['name'] ?? null)) abort(422, 'Выберите файлы.');

    $count = 0;
    foreach (array_slice(array_keys($files['name']), 0, 20) as $index) {
        if ((string)$files['name'][$index] === '') continue;
        Studio::storeMedia([
            'name'=>$files['name'][$index],
            'tmp_name'=>$files['tmp_name'][$index],
            'error'=>$files['error'][$index],
            'size'=>$files['size'][$index],
        ], Auth::id());
        $count++;
    }

    $_SESSION['flash_success'] = 'Загружено файлов: '.$count;
    redirect('/studio/media');
});

$router->post('/studio/media/{id}/delete', function (array $params): void {
    Studio::require('media');
    Csrf::validate();

    $id = (int)$params['id'];
    $item = DB::one('SELECT * FROM media_library WHERE id=?', [$id]);
    if (!$item) abort(404, 'Файл не найден.');

    $used = DB::one(
        'SELECT id FROM content_entries WHERE featured_image_path=? AND deleted_at IS NULL LIMIT 1',
        [$item['stored_path']]
    );
    if ($used) abort(409, 'Файл используется как обложка материала.');

    $path = BASE_PATH.'/storage/uploads/'.(string)$item['stored_path'];
    if (is_file($path)) @unlink($path);
    DB::run('DELETE FROM media_library WHERE id=?', [$id]);
    audit('cms.media.delete', 'media', $id);
    $_SESSION['flash_success'] = 'Файл удалён.';
    redirect('/studio/media');
});

$router->get('/studio/settings', function (): void {
    Studio::require('settings');
    Studio::render('settings', [
        'studioSection'=>'settings',
        'studioTitle'=>'Настройки сайта',
    ]);
});

$router->post('/studio/settings', function (): void {
    Studio::require('settings');
    Csrf::validate();

    $siteName = mb_substr(trim((string)($_POST['site_name'] ?? '')), 0, 100);
    if ($siteName === '') abort(422, 'Введите название сайта.');

    Studio::setSetting('site_name', $siteName);
    Studio::setSetting('seo_description', mb_substr(trim((string)($_POST['seo_description'] ?? '')), 0, 320));
    Studio::setSetting('seo_keywords', mb_substr(trim((string)($_POST['seo_keywords'] ?? '')), 0, 500));
    Studio::setSetting('copyright', mb_substr(trim((string)($_POST['copyright'] ?? '')), 0, 300));
    Studio::setSetting('search_indexing', !empty($_POST['search_indexing']) ? '1' : '0');
    Studio::setSetting('comments_auto_approve', !empty($_POST['comments_auto_approve']) ? '1' : '0');
    Studio::setSetting(
        'registration_mode',
        in_array((string)($_POST['registration_mode'] ?? ''), ['closed','manual','email_auto'], true)
            ? (string)$_POST['registration_mode']
            : 'manual'
    );
    Studio::setSetting('blog_posts_per_page', (string)max(4, min(50, (int)($_POST['blog_posts_per_page'] ?? 12))));

    audit('cms.settings.update');
    $_SESSION['flash_success'] = 'Настройки сохранены.';
    redirect('/studio/settings');
});
