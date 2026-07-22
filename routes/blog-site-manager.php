<?php

declare(strict_types=1);

use Kovcheg\Auth;
use Kovcheg\Csrf;
use Kovcheg\DB;
use Kovcheg\Blog\Blog;
use Kovcheg\Blog\SiteManager;
use Kovcheg\Blog\Studio;

require_once BASE_PATH.'/app/BlogSiteManager.php';

/* KOVCHEG Blog 3.3 routes are registered before 3.2 and 3.1 fallbacks. */
$router->get('/', function () {
    Blog::render('home', [
        'title'=>(string)setting('blog_home_title', setting('site_name','KOVCHEG Blog')),
        'description'=>(string)setting('blog_home_intro', setting('blog_description','Авторский блог и проекты.')),
        'homeSections'=>SiteManager::homeSections(true),
        'posts'=>Blog::entries('post',8),
        'portfolio'=>Blog::entries('portfolio',6),
    ]);
});

$router->get('/feed.xml', function () {
    if (setting('seo_feed_enabled','1') !== '1') abort(404,'RSS отключён.');
    header('Content-Type: application/rss+xml; charset=utf-8');
    header('Cache-Control: public, max-age=900');
    echo SiteManager::feedXml();
    exit;
});

$router->get('/sitemap.xml', function () {
    if (setting('seo_sitemap_enabled','1') !== '1') abort(404,'Карта сайта отключена.');
    header('Content-Type: application/xml; charset=utf-8');
    header('Cache-Control: public, max-age=900');
    echo SiteManager::sitemapXml();
    exit;
});

$router->get('/robots.txt', function () {
    header('Content-Type: text/plain; charset=utf-8');
    header('Cache-Control: public, max-age=900');
    echo SiteManager::robotsText();
    exit;
});

$router->get('/notifications', function () {
    Auth::requireLogin();
    Blog::render('notifications', [
        'title'=>'Уведомления',
        'description'=>'Новые ответы и события обсуждений.',
        'notifications'=>SiteManager::notifications(Auth::id()),
    ]);
});

$router->post('/notifications/read', function () {
    Auth::requireLogin();Csrf::validate();SiteManager::markNotificationsRead(Auth::id());redirect('/notifications');
});

$router->get('/studio/homepage', function () {
    Studio::require('site');
    Studio::render('homepage', [
        'studioSection'=>'homepage','studioTitle'=>'Главная страница',
        'sections'=>SiteManager::homeSections(false),
        'sectionTypes'=>SiteManager::sectionTypes(),
        'publishedEntries'=>DB::all("SELECT id,type,title FROM content_entries WHERE status='published' AND deleted_at IS NULL ORDER BY published_at DESC,id DESC LIMIT 300"),
    ]);
});

$router->post('/studio/homepage/save', function () {
    Studio::require('site');Csrf::validate();
    SiteManager::saveSection($_POST,Auth::id(),(int)($_POST['id']??0));
    $_SESSION['flash_success']='Секция главной сохранена.';redirect('/studio/homepage');
});

$router->post('/studio/homepage/{id}/delete', function (array $params) {
    Studio::require('site');Csrf::validate();SiteManager::deleteSection((int)$params['id']);
    $_SESSION['flash_success']='Секция удалена.';redirect('/studio/homepage');
});

$router->post('/studio/homepage/{id}/move', function (array $params) {
    Studio::require('site');Csrf::validate();SiteManager::moveSection((int)$params['id'],(string)($_POST['direction']??'down'));redirect('/studio/homepage');
});

$router->get('/studio/seo', function () {
    Studio::require('site');
    Studio::render('seo', [
        'studioSection'=>'seo','studioTitle'=>'SEO и индексация',
        'redirects'=>DB::all('SELECT * FROM seo_redirects ORDER BY id DESC LIMIT 300'),
    ]);
});

$router->post('/studio/seo/settings', function () {
    Studio::require('site');Csrf::validate();
    $keys=['seo_title_suffix','seo_description','seo_default_image','seo_robots_extra','seo_google_verification','seo_yandex_verification'];
    foreach($keys as $key)Studio::setSetting($key,mb_substr(trim((string)($_POST[$key]??'')),0,$key==='seo_robots_extra'?5000:1000));
    foreach(['search_indexing','seo_feed_enabled','seo_sitemap_enabled'] as $key)Studio::setSetting($key,!empty($_POST[$key])?'1':'0');
    audit('blog.seo.settings','settings');$_SESSION['flash_success']='SEO-настройки сохранены.';redirect('/studio/seo');
});

$router->post('/studio/seo/redirects', function () {
    Studio::require('site');Csrf::validate();
    $source=trim((string)($_POST['source_path']??''));$target=trim((string)($_POST['target_url']??''));$status=(int)($_POST['status_code']??301);
    if(!str_starts_with($source,'/')||str_starts_with($source,'//')||mb_strlen($source)>500)abort(422,'Исходный адрес должен начинаться с одного символа /.');
    if($target===''||mb_strlen($target)>500||(!str_starts_with($target,'/')&&!filter_var($target,FILTER_VALIDATE_URL)))abort(422,'Укажите корректный адрес назначения.');
    if(!in_array($status,[301,302],true))$status=301;
    DB::run('INSERT INTO seo_redirects (source_path,target_url,status_code,is_enabled,created_at,updated_at) VALUES (?,?,?,1,CURRENT_TIMESTAMP,CURRENT_TIMESTAMP) ON DUPLICATE KEY UPDATE target_url=VALUES(target_url),status_code=VALUES(status_code),is_enabled=1,updated_at=CURRENT_TIMESTAMP',[$source,$target,$status]);
    $_SESSION['flash_success']='Редирект сохранён.';redirect('/studio/seo');
});

$router->post('/studio/seo/redirects/{id}/toggle', function (array $params) {
    Studio::require('site');Csrf::validate();DB::run('UPDATE seo_redirects SET is_enabled=?,updated_at=CURRENT_TIMESTAMP WHERE id=?',[!empty($_POST['enabled'])?1:0,(int)$params['id']]);redirect('/studio/seo');
});

$router->post('/studio/seo/redirects/{id}/delete', function (array $params) {
    Studio::require('site');Csrf::validate();DB::run('DELETE FROM seo_redirects WHERE id=?',[(int)$params['id']]);redirect('/studio/seo');
});

$router->post('/content/{id}/subscribe', function (array $params) {
    Auth::requireLogin();Csrf::validate();$entryId=(int)$params['id'];$enabled=SiteManager::toggleSubscription($entryId,Auth::id());
    $_SESSION['flash_success']=$enabled?'Вы подписались на новые комментарии.':'Подписка на обсуждение отключена.';
    $entry=DB::one('SELECT type,slug FROM content_entries WHERE id=?',[$entryId]);redirect($entry?Blog::entryUrl($entry).'#comments':'/blog');
});

$router->post('/content/{id}/comment', function (array $params) {
    Auth::requireLogin();Csrf::validate();$entryId=(int)$params['id'];
    $entry=DB::one("SELECT id,type,slug,comments_enabled FROM content_entries WHERE id=? AND status='published' AND deleted_at IS NULL LIMIT 1",[$entryId]);
    if(!$entry)abort(404,'Материал не найден.');if(empty($entry['comments_enabled']))abort(403,'Комментарии к этому материалу отключены.');
    $body=trim((string)($_POST['body']??''));if(mb_strlen($body)<2||mb_strlen($body)>5000)abort(422,'Комментарий должен содержать от 2 до 5000 символов.');
    $parentId=max(0,(int)($_POST['parent_id']??0));if($parentId>0&&!DB::one('SELECT id FROM content_comments WHERE id=? AND entry_id=? AND deleted_at IS NULL',[$parentId,$entryId]))abort(422,'Комментарий для ответа не найден.');
    $status=Blog::canModerate()||setting('comments_auto_approve','0')==='1'?'approved':'pending';$ip=(string)($_SERVER['REMOTE_ADDR']??'');$ipHash=$ip!==''?hash('sha256',$ip.'|'.(string)cfg('app.key','kovcheg')):null;$agent=utf8_substr((string)($_SERVER['HTTP_USER_AGENT']??''),0,255);
    $commentId=DB::insert('INSERT INTO content_comments (entry_id,user_id,parent_id,body,status,ip_hash,user_agent,created_at,updated_at) VALUES (?,?,?,?,?,?,?,CURRENT_TIMESTAMP,CURRENT_TIMESTAMP)',[$entryId,Auth::id(),$parentId?:null,$body,$status,$ipHash,$agent]);
    if(!empty($_POST['subscribe']))DB::run('INSERT IGNORE INTO content_subscriptions (entry_id,user_id,created_at) VALUES (?,?,CURRENT_TIMESTAMP)',[$entryId,Auth::id()]);
    if($status==='approved')SiteManager::notifyDiscussion($entryId,Auth::id(),$commentId,$parentId,$body,Blog::entryUrl($entry));
    $_SESSION['flash_success']=$status==='approved'?'Комментарий опубликован.':'Комментарий отправлен на проверку.';redirect(Blog::entryUrl($entry).'#comment-'.$commentId);
});

$router->post('/content/comment/{id}/edit', function (array $params) {
    Auth::requireLogin();Csrf::validate();$comment=SiteManager::editComment((int)$params['id'],Auth::id(),(string)($_POST['body']??''));
    $entry=DB::one('SELECT type,slug FROM content_entries WHERE id=?',[(int)$comment['entry_id']]);$_SESSION['flash_success']='Комментарий изменён.';redirect($entry?Blog::entryUrl($entry).'#comment-'.(int)$params['id']:'/blog');
});

$router->post('/content/comment/{id}/delete', function (array $params) {
    Auth::requireLogin();Csrf::validate();$comment=SiteManager::deleteComment((int)$params['id'],Auth::id());
    $entry=DB::one('SELECT type,slug FROM content_entries WHERE id=?',[(int)$comment['entry_id']]);$_SESSION['flash_success']='Комментарий удалён.';redirect($entry?Blog::entryUrl($entry).'#comments':'/blog');
});

$router->post('/content/comment/{id}/reaction', function (array $params) {
    Auth::requireLogin();Csrf::validate();$id=(int)$params['id'];SiteManager::reactComment($id,Auth::id(),(string)($_POST['reaction']??''));
    $comment=DB::one('SELECT entry_id FROM content_comments WHERE id=?',[$id]);$entry=$comment?DB::one('SELECT type,slug FROM content_entries WHERE id=?',[(int)$comment['entry_id']]):null;redirect($entry?Blog::entryUrl($entry).'#comment-'.$id:'/blog');
});
