<?php

declare(strict_types=1);

use Kovcheg\Csrf;
use Kovcheg\DB;
use Kovcheg\Blog\Blog;
use Kovcheg\Blog\SiteManager;
use Kovcheg\Blog\Studio;

/* Registered before the Studio 3.1 moderation fallback. */
$router->post('/studio/comments/{id}/{action}', function (array $params) {
    Studio::require('comments');
    Csrf::validate();

    $id=(int)$params['id'];
    $action=(string)$params['action'];
    $comment=DB::one('SELECT * FROM content_comments WHERE id=? AND deleted_at IS NULL',[$id]);
    if(!$comment)abort(404,'Комментарий не найден.');

    if($action==='approve'){
        DB::run("UPDATE content_comments SET status='approved',updated_at=CURRENT_TIMESTAMP WHERE id=?",[$id]);
        if((string)$comment['status']!=='approved'){
            $entry=DB::one('SELECT id,type,slug FROM content_entries WHERE id=?',[(int)$comment['entry_id']]);
            if($entry)SiteManager::notifyDiscussion((int)$entry['id'],(int)$comment['user_id'],$id,(int)($comment['parent_id']??0),(string)$comment['body'],Blog::entryUrl($entry));
        }
    }elseif($action==='pending')DB::run("UPDATE content_comments SET status='pending',updated_at=CURRENT_TIMESTAMP WHERE id=?",[$id]);
    elseif($action==='spam')DB::run("UPDATE content_comments SET status='spam',updated_at=CURRENT_TIMESTAMP WHERE id=?",[$id]);
    elseif($action==='delete')DB::run('UPDATE content_comments SET deleted_at=CURRENT_TIMESTAMP,updated_at=CURRENT_TIMESTAMP WHERE id=?',[$id]);
    else abort(422,'Неизвестное действие.');

    audit('blog.comment.'.$action,'content_comment',$id);
    $_SESSION['flash_success']='Комментарий обновлён.';
    redirect('/studio/comments');
});
