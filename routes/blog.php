<?php

declare(strict_types=1);

use Kovcheg\Auth;
use Kovcheg\Csrf;
use Kovcheg\DB;
use Kovcheg\Blog\Blog;

$router->get('/', function () {
    Blog::render('home', [
        'title' => (string)setting('blog_home_title', setting('site_name', 'KOVCHEG Blog')),
        'posts' => Blog::entries('post', 8),
        'portfolio' => Blog::entries('portfolio', 6),
    ]);
});

$router->get('/blog', function () {
    Blog::render('archive', [
        'title' => 'Блог',
        'archiveTitle' => 'Блог',
        'archiveDescription' => (string)setting('blog_description', 'Разработки, идеи, опыт и новые проекты.'),
        'entries' => Blog::entries('post', 30),
        'entryType' => 'post',
    ]);
});

$router->get('/blog/{slug}', function (array $params) {
    $entry = Blog::entry((string)$params['slug'], 'post');
    if (!$entry) abort(404, 'Публикация не найдена.');

    Blog::render('entry', [
        'title' => (string)($entry['seo_title'] ?: $entry['title']),
        'description' => (string)($entry['seo_description'] ?: Blog::excerpt($entry, 300)),
        'entry' => $entry,
        'comments' => Blog::comments((int)$entry['id']),
        'reactions' => Blog::reactions((int)$entry['id']),
    ]);
});

$router->get('/page/{slug}', function (array $params) {
    $entry = Blog::entry((string)$params['slug'], 'page');
    if (!$entry) abort(404, 'Страница не найдена.');

    Blog::render('entry', [
        'title' => (string)($entry['seo_title'] ?: $entry['title']),
        'description' => (string)($entry['seo_description'] ?: Blog::excerpt($entry, 300)),
        'entry' => $entry,
        'comments' => Blog::comments((int)$entry['id']),
        'reactions' => Blog::reactions((int)$entry['id']),
    ]);
});

$router->get('/portfolio', function () {
    Blog::render('archive', [
        'title' => 'Портфолио',
        'archiveTitle' => 'Портфолио',
        'archiveDescription' => (string)setting('portfolio_description', 'Работы, проекты, релизы и результаты.'),
        'entries' => Blog::entries('portfolio', 60),
        'entryType' => 'portfolio',
    ]);
});

$router->get('/portfolio/{slug}', function (array $params) {
    $entry = Blog::entry((string)$params['slug'], 'portfolio');
    if (!$entry) abort(404, 'Работа портфолио не найдена.');

    Blog::render('entry', [
        'title' => (string)($entry['seo_title'] ?: $entry['title']),
        'description' => (string)($entry['seo_description'] ?: Blog::excerpt($entry, 300)),
        'entry' => $entry,
        'comments' => Blog::comments((int)$entry['id']),
        'reactions' => Blog::reactions((int)$entry['id']),
    ]);
});

$router->get('/author/{username}', function (array $params) {
    $author = Blog::author((string)$params['username']);
    if (!$author) abort(404, 'Автор не найден.');

    Blog::render('author', [
        'title' => (string)$author['display_name'],
        'author' => $author,
        'entries' => Blog::authorEntries((int)$author['id']),
    ]);
});

$router->post('/content/{id}/comment', function (array $params) {
    Auth::requireLogin();
    Csrf::validate();

    $entryId = (int)$params['id'];
    $entry = DB::one("SELECT id,type,slug,comments_enabled FROM content_entries WHERE id=? AND status='published' AND deleted_at IS NULL LIMIT 1", [$entryId]);
    if (!$entry) abort(404, 'Материал не найден.');
    if (empty($entry['comments_enabled'])) abort(403, 'Комментарии к этому материалу отключены.');

    $body = trim((string)($_POST['body'] ?? ''));
    if (mb_strlen($body) < 2 || mb_strlen($body) > 5000) {
        abort(422, 'Комментарий должен содержать от 2 до 5000 символов.');
    }

    $parentId = max(0, (int)($_POST['parent_id'] ?? 0));
    if ($parentId > 0 && !DB::one('SELECT id FROM content_comments WHERE id=? AND entry_id=? AND deleted_at IS NULL', [$parentId, $entryId])) {
        abort(422, 'Комментарий, на который вы отвечаете, не найден.');
    }

    $status = Blog::canModerate() || setting('comments_auto_approve', '0') === '1' ? 'approved' : 'pending';
    $ip = (string)($_SERVER['REMOTE_ADDR'] ?? '');
    $ipHash = $ip !== '' ? hash('sha256', $ip.'|'.(string)cfg('app.key', 'kovcheg')) : null;
    $agent = utf8_substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255);

    DB::insert(
        'INSERT INTO content_comments (entry_id,user_id,parent_id,body,status,ip_hash,user_agent,created_at,updated_at) VALUES (?,?,?,?,?,?,?,CURRENT_TIMESTAMP,CURRENT_TIMESTAMP)',
        [$entryId, Auth::id(), $parentId ?: null, $body, $status, $ipHash, $agent]
    );

    $_SESSION[$status === 'approved' ? 'flash_success' : 'flash_success'] = $status === 'approved'
        ? 'Комментарий опубликован.'
        : 'Комментарий отправлен на проверку.';

    redirect(Blog::entryUrl($entry).'#comments');
});

$router->post('/content/{id}/reaction', function (array $params) {
    Auth::requireLogin();
    Csrf::validate();

    $entryId = (int)$params['id'];
    $entry = DB::one("SELECT id,type,slug,reactions_enabled FROM content_entries WHERE id=? AND status='published' AND deleted_at IS NULL LIMIT 1", [$entryId]);
    if (!$entry) abort(404, 'Материал не найден.');
    if (empty($entry['reactions_enabled'])) abort(403, 'Реакции к этому материалу отключены.');

    $reaction = (string)($_POST['reaction'] ?? '');
    $allowed = ['👍', '❤️', '👏', '🔥', '💡'];
    if (!in_array($reaction, $allowed, true)) abort(422, 'Неизвестная реакция.');

    $existing = DB::one('SELECT reaction FROM content_reactions WHERE entry_id=? AND user_id=? LIMIT 1', [$entryId, Auth::id()]);
    DB::run('DELETE FROM content_reactions WHERE entry_id=? AND user_id=?', [$entryId, Auth::id()]);

    if (!$existing || !hash_equals((string)$existing['reaction'], $reaction)) {
        DB::run('INSERT INTO content_reactions (entry_id,user_id,reaction,created_at) VALUES (?,?,?,CURRENT_TIMESTAMP)', [$entryId, Auth::id(), $reaction]);
    }

    redirect(Blog::entryUrl($entry).'#reactions');
});
