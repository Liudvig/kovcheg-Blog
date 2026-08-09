<?php

declare(strict_types=1);

use Kovcheg\Auth;
use Kovcheg\Csrf;
use Kovcheg\DB;
use Kovcheg\Blog\Blog;

$router->get('/author/{username}', function (array $params): void {
    $author = Blog::author((string)($params['username'] ?? ''));
    if (!$author) abort(404, 'Автор не найден.');

    Blog::render('author', [
        'title'=>(string)$author['display_name'],
        'author'=>$author,
        'entries'=>Blog::authorEntries((int)$author['id']),
    ]);
});

$router->post('/content/{id}/comment', function (array $params): void {
    Auth::requireLogin();
    Csrf::validate();

    $entryId = (int)$params['id'];
    $entry = DB::one(
        "SELECT id,type,slug,comments_enabled
         FROM content_entries
         WHERE id=? AND type IN ('post','page') AND status='published' AND deleted_at IS NULL
         LIMIT 1",
        [$entryId]
    );
    if (!$entry) abort(404, 'Материал не найден.');
    if (empty($entry['comments_enabled'])) abort(403, 'Комментарии к этому материалу отключены.');

    $body = trim((string)($_POST['body'] ?? ''));
    if (mb_strlen($body) < 2 || mb_strlen($body) > 5000) {
        abort(422, 'Комментарий должен содержать от 2 до 5000 символов.');
    }

    $parentId = max(0, (int)($_POST['parent_id'] ?? 0));
    if ($parentId > 0 && !DB::one(
        'SELECT id FROM content_comments WHERE id=? AND entry_id=? AND deleted_at IS NULL',
        [$parentId,$entryId]
    )) {
        abort(422, 'Комментарий для ответа не найден.');
    }

    $status = Blog::canModerate() || setting('comments_auto_approve','0') === '1' ? 'approved' : 'pending';
    $ip = (string)($_SERVER['REMOTE_ADDR'] ?? '');
    $ipHash = $ip !== '' ? hash('sha256', $ip.'|'.(string)cfg('app.key','kovcheg')) : null;
    $agent = utf8_substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255);

    DB::insert(
        'INSERT INTO content_comments (entry_id,user_id,parent_id,body,status,ip_hash,user_agent,created_at,updated_at)
         VALUES (?,?,?,?,?,?,?,CURRENT_TIMESTAMP,CURRENT_TIMESTAMP)',
        [$entryId,Auth::id(),$parentId ?: null,$body,$status,$ipHash,$agent]
    );

    audit('cms.comment.create', 'content_entry', $entryId, ['status'=>$status]);
    $_SESSION['flash_success'] = $status === 'approved'
        ? 'Комментарий опубликован.'
        : 'Комментарий отправлен на проверку.';
    redirect(Blog::entryUrl($entry).'#comments');
});

$router->post('/content/comment/{id}/report', function (array $params): void {
    Auth::requireLogin();
    Csrf::validate();

    $id = (int)$params['id'];
    if (!DB::one(
        "SELECT id FROM content_comments WHERE id=? AND status='approved' AND deleted_at IS NULL",
        [$id]
    )) {
        abort(404, 'Комментарий не найден.');
    }

    $reason = mb_substr(trim((string)($_POST['reason'] ?? 'Нарушение правил')), 0, 190);
    $details = mb_substr(trim((string)($_POST['details'] ?? '')), 0, 2000);

    DB::run(
        "INSERT INTO content_comment_reports (comment_id,reporter_id,reason,details,status,created_at)
         VALUES (?,?,?,?, 'open', CURRENT_TIMESTAMP)
         ON DUPLICATE KEY UPDATE reason=VALUES(reason),details=VALUES(details),status='open',created_at=CURRENT_TIMESTAMP",
        [$id,Auth::id(),$reason,$details ?: null]
    );

    audit('cms.comment.report', 'content_comment', $id);
    $_SESSION['flash_success'] = 'Жалоба отправлена модератору.';
    redirect((string)($_SERVER['HTTP_REFERER'] ?? '/').'#comments');
});

$router->post('/content/{id}/reaction', function (array $params): void {
    Auth::requireLogin();
    Csrf::validate();

    $entryId = (int)$params['id'];
    $entry = DB::one(
        "SELECT id,type,slug,reactions_enabled
         FROM content_entries
         WHERE id=? AND type IN ('post','page') AND status='published' AND deleted_at IS NULL
         LIMIT 1",
        [$entryId]
    );
    if (!$entry) abort(404, 'Материал не найден.');
    if (empty($entry['reactions_enabled'])) abort(403, 'Реакции к этому материалу отключены.');

    $reaction = (string)($_POST['reaction'] ?? '');
    $allowed = ['👍','❤️','👏','🔥','💡'];
    if (!in_array($reaction, $allowed, true)) abort(422, 'Неизвестная реакция.');

    $existing = DB::one(
        'SELECT reaction FROM content_reactions WHERE entry_id=? AND user_id=? LIMIT 1',
        [$entryId,Auth::id()]
    );
    DB::run('DELETE FROM content_reactions WHERE entry_id=? AND user_id=?', [$entryId,Auth::id()]);
    if (!$existing || !hash_equals((string)$existing['reaction'], $reaction)) {
        DB::run(
            'INSERT INTO content_reactions (entry_id,user_id,reaction,created_at) VALUES (?,?,?,CURRENT_TIMESTAMP)',
            [$entryId,Auth::id(),$reaction]
        );
    }

    redirect(Blog::entryUrl($entry).'#reactions');
});
