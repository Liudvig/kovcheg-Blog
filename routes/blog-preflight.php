<?php

declare(strict_types=1);

use Kovcheg\Auth;
use Kovcheg\Csrf;
use Kovcheg\DB;
use Kovcheg\Blog\Blog;

$router->get('/media/{id}', function(array $params){
    $id=(int)$params['id'];$item=DB::one('SELECT * FROM media_library WHERE id=? LIMIT 1',[$id]);if(!$item)abort(404,'Файл не найден.');
    $relative=str_replace('\\','/',trim((string)$item['stored_path']));if($relative===''||str_contains($relative,'..')||str_starts_with($relative,'/'))abort(404,'Файл недоступен.');
    $file=BASE_PATH.'/storage/uploads/'.$relative;if(!is_file($file))abort(404,'Файл отсутствует на сервере.');
    $mime=(string)($item['mime_type']??'application/octet-stream');$public=str_starts_with($mime,'image/')||str_starts_with($mime,'audio/')||str_starts_with($mime,'video/')||$mime==='application/pdf';
    if(!$public&&!Auth::check())abort(403,'Для скачивания файла требуется вход.');
    $etag='"'.hash_file('sha256',$file).'"';header('Content-Type: '.$mime);header('Content-Length: '.filesize($file));header('X-Content-Type-Options: nosniff');header('Cache-Control: public, max-age=86400');header('ETag: '.$etag);
    if(!str_starts_with($mime,'image/')&&!str_starts_with($mime,'audio/')&&!str_starts_with($mime,'video/')&&$mime!=='application/pdf')header('Content-Disposition: attachment; filename="'.rawurlencode(basename((string)$item['original_name'])).'"');
    if(trim((string)($_SERVER['HTTP_IF_NONE_MATCH']??''))===$etag){http_response_code(304);exit;}readfile($file);exit;
});

// Registered before the general blog routes so reports always return to the
// exact material instead of trusting an external Referer header.
$router->post('/content/comment/{id}/report', function(array $params){
    Auth::requireLogin();Csrf::validate();$id=(int)$params['id'];
    $comment=DB::one("SELECT c.id,c.entry_id,e.type,e.slug FROM content_comments c JOIN content_entries e ON e.id=c.entry_id WHERE c.id=? AND c.status='approved' AND c.deleted_at IS NULL LIMIT 1",[$id]);
    if(!$comment)abort(404,'Комментарий не найден.');$reason=mb_substr(trim((string)($_POST['reason']??'Нарушение правил')),0,190);$details=mb_substr(trim((string)($_POST['details']??'')),0,2000);
    DB::run("INSERT INTO content_comment_reports (comment_id,reporter_id,reason,details,status,created_at) VALUES (?,?,?,?, 'open',CURRENT_TIMESTAMP) ON DUPLICATE KEY UPDATE reason=VALUES(reason),details=VALUES(details),status='open',created_at=CURRENT_TIMESTAMP",[$id,Auth::id(),$reason,$details?:null]);
    audit('blog.comment.report','content_comment',$id,['reason'=>$reason]);$_SESSION['flash_success']='Жалоба отправлена модератору.';redirect(Blog::entryUrl($comment).'#comments');
});
