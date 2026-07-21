<?php

declare(strict_types=1);

use Kovcheg\Auth;
use Kovcheg\Csrf;
use Kovcheg\DB;
use Kovcheg\Blog\Blog;
use Kovcheg\Blog\Studio;

require_once BASE_PATH.'/app/BlogStudio.php';

$router->get('/studio', function () {
    Studio::require('comments');
    $stats = DB::one("SELECT
        (SELECT COUNT(*) FROM content_entries WHERE deleted_at IS NULL) entries,
        (SELECT COUNT(*) FROM content_entries WHERE type='post' AND status='published' AND deleted_at IS NULL) posts,
        (SELECT COUNT(*) FROM content_entries WHERE type='page' AND deleted_at IS NULL) pages,
        (SELECT COUNT(*) FROM content_entries WHERE type='portfolio' AND deleted_at IS NULL) portfolio,
        (SELECT COUNT(*) FROM content_comments WHERE status='pending' AND deleted_at IS NULL) pending_comments,
        (SELECT COUNT(*) FROM users WHERE is_active=1 AND approval_status='approved') users,
        (SELECT COALESCE(SUM(views),0) FROM content_views_daily WHERE view_date>=DATE_SUB(CURRENT_DATE,INTERVAL 30 DAY)) views_30") ?? [];
    $recent = DB::all("SELECT e.id,e.type,e.status,e.title,e.slug,e.updated_at,u.display_name author_name FROM content_entries e JOIN users u ON u.id=e.author_id WHERE e.deleted_at IS NULL ORDER BY e.updated_at DESC,e.id DESC LIMIT 10");
    $comments = DB::all("SELECT c.id,c.body,c.status,c.created_at,e.title entry_title,u.display_name author_name FROM content_comments c JOIN content_entries e ON e.id=c.entry_id JOIN users u ON u.id=c.user_id WHERE c.deleted_at IS NULL ORDER BY c.id DESC LIMIT 8");
    Studio::render('dashboard', ['studioSection'=>'dashboard','studioTitle'=>'Обзор','stats'=>$stats,'recentEntries'=>$recent,'recentComments'=>$comments]);
});

$router->get('/studio/content', function () {
    Studio::require('content');
    $type = (string)($_GET['type'] ?? '');
    $status = (string)($_GET['status'] ?? '');
    $search = mb_substr(trim((string)($_GET['q'] ?? '')), 0, 150);
    Studio::render('content-index', [
        'studioSection'=>'content',
        'studioTitle'=>'Материалы',
        'entries'=>Studio::listEntries($type,$status,$search),
        'type'=>$type,'status'=>$status,'search'=>$search,
    ]);
});

$router->get('/studio/content/new', function () {
    Studio::require('content');
    $type = in_array((string)($_GET['type'] ?? ''), ['post','page','portfolio'], true) ? (string)$_GET['type'] : 'post';
    $entry = ['id'=>0,'type'=>$type,'status'=>'draft','title'=>'','slug'=>'','excerpt'=>'','content_json'=>'[]','featured_image_path'=>'','template'=>'','visibility'=>'public','comments_enabled'=>1,'reactions_enabled'=>1,'is_featured'=>0,'sort_order'=>0,'seo_title'=>'','seo_description'=>'','published_at'=>'','category_ids'=>[],'tags_text'=>'','meta'=>[]];
    Studio::render('editor', [
        'studioSection'=>'content','studioTitle'=>'Новый материал','entry'=>$entry,
        'categories'=>DB::all('SELECT * FROM content_categories ORDER BY sort_order,name'),
        'media'=>DB::all("SELECT * FROM media_library WHERE mime_type LIKE 'image/%' ORDER BY id DESC LIMIT 80"),
        'revisions'=>[],
    ]);
});

$router->get('/studio/content/{id}/edit', function (array $params) {
    Studio::require('content');
    $entry = Studio::entry((int)$params['id']);
    if (!$entry || !empty($entry['deleted_at'])) abort(404, 'Материал не найден.');
    Studio::render('editor', [
        'studioSection'=>'content','studioTitle'=>'Редактирование','entry'=>$entry,
        'categories'=>DB::all('SELECT * FROM content_categories ORDER BY sort_order,name'),
        'media'=>DB::all("SELECT * FROM media_library WHERE mime_type LIKE 'image/%' ORDER BY id DESC LIMIT 80"),
        'revisions'=>DB::all('SELECT r.id,r.title,r.created_at,u.display_name author_name FROM content_revisions r JOIN users u ON u.id=r.author_id WHERE r.entry_id=? ORDER BY r.id DESC LIMIT 20', [(int)$entry['id']]),
    ]);
});

$router->post('/studio/content/save', function () {
    Studio::require('content'); Csrf::validate();
    $input = $_POST;
    if (!empty($_FILES['featured_image']['name'])) {
        $media = Studio::storeMedia($_FILES['featured_image'], Auth::id());
        $input['featured_image_path'] = (string)($media['stored_path'] ?? '');
    }
    $id = Studio::saveEntry($input, Auth::id(), (int)($_POST['id'] ?? 0));
    $_SESSION['flash_success'] = 'Материал сохранён.';
    redirect('/studio/content/'.$id.'/edit');
});

$router->post('/studio/content/{id}/trash', function (array $params) {
    Studio::require('content'); Csrf::validate();
    $id = (int)$params['id'];
    if (!DB::one('SELECT id FROM content_entries WHERE id=? AND deleted_at IS NULL', [$id])) abort(404, 'Материал не найден.');
    DB::run('UPDATE content_entries SET deleted_at=CURRENT_TIMESTAMP,updated_at=CURRENT_TIMESTAMP WHERE id=?', [$id]);
    audit('blog.entry.trash','content_entry',$id); $_SESSION['flash_success']='Материал перемещён в корзину.'; redirect('/studio/content');
});

$router->post('/studio/content/{id}/duplicate', function (array $params) {
    Studio::require('content'); Csrf::validate();
    $source = Studio::entry((int)$params['id']);
    if (!$source) abort(404, 'Материал не найден.');
    $copy = $source;
    $copy['title'] = 'Копия — '.$source['title'];
    $copy['slug'] = '';
    $copy['status'] = 'draft';
    $copy['published_at'] = '';
    $copy['tags'] = (string)$source['tags_text'];
    $copy['portfolio_client'] = (string)($source['meta']['client'] ?? '');
    $copy['portfolio_year'] = (string)($source['meta']['year'] ?? '');
    $copy['portfolio_role'] = (string)($source['meta']['role'] ?? '');
    $copy['portfolio_url'] = (string)($source['meta']['project_url'] ?? '');
    $id = Studio::saveEntry($copy, Auth::id());
    $_SESSION['flash_success']='Создана копия материала.'; redirect('/studio/content/'.$id.'/edit');
});

$router->post('/studio/revisions/{id}/restore', function (array $params) {
    Studio::require('content'); Csrf::validate();
    $revision = DB::one('SELECT * FROM content_revisions WHERE id=?', [(int)$params['id']]);
    if (!$revision) abort(404, 'Ревизия не найдена.');
    $entry = Studio::entry((int)$revision['entry_id']);
    if (!$entry) abort(404, 'Материал не найден.');
    DB::pdo()->beginTransaction();
    try {
        DB::run('INSERT INTO content_revisions (entry_id,author_id,title,excerpt,content_json,content_html,created_at) VALUES (?,?,?,?,?,?,CURRENT_TIMESTAMP)', [$entry['id'],Auth::id(),$entry['title'],$entry['excerpt'],$entry['content_json'],$entry['content_html']]);
        DB::run('UPDATE content_entries SET title=?,excerpt=?,content_json=?,content_html=?,updated_at=CURRENT_TIMESTAMP WHERE id=?', [$revision['title'],$revision['excerpt'],$revision['content_json'],$revision['content_html'],$entry['id']]);
        DB::pdo()->commit();
    } catch (Throwable $e) { if (DB::pdo()->inTransaction()) DB::pdo()->rollBack(); throw $e; }
    audit('blog.revision.restore','content_entry',(int)$entry['id'],['revision_id'=>(int)$revision['id']]);
    $_SESSION['flash_success']='Ревизия восстановлена.'; redirect('/studio/content/'.(int)$entry['id'].'/edit');
});

$router->get('/studio/comments', function () {
    Studio::require('comments');
    $status = in_array((string)($_GET['status'] ?? ''), ['pending','approved','spam'], true) ? (string)$_GET['status'] : '';
    $where = 'c.deleted_at IS NULL'; $params=[];
    if ($status !== '') { $where .= ' AND c.status=?'; $params[]=$status; }
    $comments = DB::all("SELECT c.*,e.title entry_title,e.slug entry_slug,e.type entry_type,u.display_name author_name,u.username author_username FROM content_comments c JOIN content_entries e ON e.id=c.entry_id JOIN users u ON u.id=c.user_id WHERE {$where} ORDER BY c.id DESC LIMIT 300", $params);
    Studio::render('comments', ['studioSection'=>'comments','studioTitle'=>'Комментарии','comments'=>$comments,'status'=>$status]);
});

$router->post('/studio/comments/{id}/{action}', function (array $params) {
    Studio::require('comments'); Csrf::validate();
    $id=(int)$params['id']; $action=(string)$params['action'];
    if (!DB::one('SELECT id FROM content_comments WHERE id=? AND deleted_at IS NULL',[$id])) abort(404,'Комментарий не найден.');
    if ($action==='approve') DB::run("UPDATE content_comments SET status='approved',updated_at=CURRENT_TIMESTAMP WHERE id=?",[$id]);
    elseif ($action==='pending') DB::run("UPDATE content_comments SET status='pending',updated_at=CURRENT_TIMESTAMP WHERE id=?",[$id]);
    elseif ($action==='spam') DB::run("UPDATE content_comments SET status='spam',updated_at=CURRENT_TIMESTAMP WHERE id=?",[$id]);
    elseif ($action==='delete') DB::run('UPDATE content_comments SET deleted_at=CURRENT_TIMESTAMP,updated_at=CURRENT_TIMESTAMP WHERE id=?',[$id]);
    else abort(422,'Неизвестное действие.');
    audit('blog.comment.'.$action,'content_comment',$id); $_SESSION['flash_success']='Комментарий обновлён.'; redirect('/studio/comments');
});

$router->get('/studio/categories', function () {
    Studio::require('content');
    Studio::render('categories', ['studioSection'=>'categories','studioTitle'=>'Рубрики','categories'=>DB::all('SELECT c.*,(SELECT COUNT(*) FROM content_entry_categories ec WHERE ec.category_id=c.id) entry_count FROM content_categories c ORDER BY c.sort_order,c.name')]);
});

$router->post('/studio/categories/save', function () {
    Studio::require('content'); Csrf::validate();
    $id=max(0,(int)($_POST['id']??0)); $name=mb_substr(trim((string)($_POST['name']??'')),0,150);
    if ($name==='') abort(422,'Введите название рубрики.');
    $slug=Studio::slugify((string)($_POST['slug']??$name)); if($slug==='')abort(422,'Не удалось сформировать адрес рубрики.');
    $duplicate=DB::one('SELECT id FROM content_categories WHERE slug=? AND id<>?',[$slug,$id]); if($duplicate)abort(422,'Такой адрес рубрики уже используется.');
    $description=mb_substr(trim((string)($_POST['description']??'')),0,2000); $sort=max(-999,min(999,(int)($_POST['sort_order']??0)));
    if($id)DB::run('UPDATE content_categories SET name=?,slug=?,description=?,sort_order=?,updated_at=CURRENT_TIMESTAMP WHERE id=?',[$name,$slug,$description?:null,$sort,$id]);
    else $id=DB::insert('INSERT INTO content_categories (name,slug,description,sort_order,created_at,updated_at) VALUES (?,?,?,?,CURRENT_TIMESTAMP,CURRENT_TIMESTAMP)',[$name,$slug,$description?:null,$sort]);
    audit('blog.category.save','content_category',$id);$_SESSION['flash_success']='Рубрика сохранена.';redirect('/studio/categories');
});

$router->post('/studio/categories/{id}/delete', function (array $params) {
    Studio::require('content'); Csrf::validate(); $id=(int)$params['id'];
    DB::run('DELETE FROM content_categories WHERE id=?',[$id]);audit('blog.category.delete','content_category',$id);$_SESSION['flash_success']='Рубрика удалена.';redirect('/studio/categories');
});

$router->get('/studio/media', function () {
    Studio::require('media');
    Studio::render('media', ['studioSection'=>'media','studioTitle'=>'Медиатека','media'=>DB::all('SELECT m.*,u.display_name uploader_name FROM media_library m JOIN users u ON u.id=m.uploader_id ORDER BY m.id DESC LIMIT 300')]);
});

$router->post('/studio/media/upload', function () {
    Studio::require('media'); Csrf::validate();
    $files=$_FILES['media']??null;if(!$files||!is_array($files['name']??null))abort(422,'Выберите файлы.');
    $count=0;foreach(array_slice(array_keys($files['name']),0,20) as $i){if((string)$files['name'][$i]==='')continue;Studio::storeMedia(['name'=>$files['name'][$i],'tmp_name'=>$files['tmp_name'][$i],'error'=>$files['error'][$i],'size'=>$files['size'][$i]],Auth::id());$count++;}
    $_SESSION['flash_success']='Загружено файлов: '.$count;redirect('/studio/media');
});

$router->post('/studio/media/{id}/delete', function (array $params) {
    Studio::require('media'); Csrf::validate(); $id=(int)$params['id'];$item=DB::one('SELECT * FROM media_library WHERE id=?',[$id]);if(!$item)abort(404,'Файл не найден.');
    $used=DB::one('SELECT id FROM content_entries WHERE featured_image_path=? AND deleted_at IS NULL LIMIT 1',[$item['stored_path']]);if($used)abort(409,'Файл используется как обложка материала.');
    $path=BASE_PATH.'/storage/uploads/'.(string)$item['stored_path'];if(is_file($path))@unlink($path);DB::run('DELETE FROM media_library WHERE id=?',[$id]);audit('blog.media.delete','media',$id);$_SESSION['flash_success']='Файл удалён.';redirect('/studio/media');
});

$router->get('/studio/menus', function () {
    Studio::require('menus');
    $menus=DB::all('SELECT * FROM navigation_menus ORDER BY id');$menuId=max(0,(int)($_GET['menu']??($menus[0]['id']??0)));
    $items=$menuId?DB::all('SELECT * FROM navigation_items WHERE menu_id=? ORDER BY sort_order,id',[$menuId]):[];
    Studio::render('menus',['studioSection'=>'menus','studioTitle'=>'Меню','menus'=>$menus,'menuId'=>$menuId,'items'=>$items,'pages'=>DB::all("SELECT id,type,title,slug FROM content_entries WHERE status='published' AND deleted_at IS NULL ORDER BY type,title")]);
});

$router->post('/studio/menus/create', function () {
    Studio::require('menus');Csrf::validate();$name=mb_substr(trim((string)($_POST['name']??'')),0,150);if($name==='')abort(422,'Введите название меню.');$slug=Studio::slugify((string)($_POST['slug']??$name));
    $id=DB::insert('INSERT INTO navigation_menus (name,slug,location,is_active,created_at,updated_at) VALUES (?,?,?,?,CURRENT_TIMESTAMP,CURRENT_TIMESTAMP)',[$name,$slug,trim((string)($_POST['location']??''))?:null,1]);$_SESSION['flash_success']='Меню создано.';redirect('/studio/menus?menu='.$id);
});

$router->post('/studio/menus/item', function () {
    Studio::require('menus');Csrf::validate();$menuId=(int)($_POST['menu_id']??0);if(!DB::one('SELECT id FROM navigation_menus WHERE id=?',[$menuId]))abort(404,'Меню не найдено.');
    $label=mb_substr(trim((string)($_POST['label']??'')),0,150);if($label==='')abort(422,'Введите подпись пункта.');$url=mb_substr(trim((string)($_POST['url']??'')),0,500);$targetId=max(0,(int)($_POST['target_id']??0));
    if($targetId){$entry=DB::one('SELECT type,slug FROM content_entries WHERE id=?',[$targetId]);if($entry)$url=Blog::entryUrl($entry);}
    if($url==='')$url='/';$sort=max(-999,min(999,(int)($_POST['sort_order']??0)));
    DB::insert('INSERT INTO navigation_items (menu_id,label,url,target_type,target_id,sort_order,is_enabled,created_at,updated_at) VALUES (?,?,?,?,?,?,1,CURRENT_TIMESTAMP,CURRENT_TIMESTAMP)',[$menuId,$label,$url,$targetId?'content':'custom',$targetId?:null,$sort]);$_SESSION['flash_success']='Пункт меню добавлен.';redirect('/studio/menus?menu='.$menuId);
});

$router->post('/studio/menus/item/{id}/delete', function (array $params) {
    Studio::require('menus');Csrf::validate();$id=(int)$params['id'];$item=DB::one('SELECT menu_id FROM navigation_items WHERE id=?',[$id]);if(!$item)abort(404,'Пункт не найден.');DB::run('DELETE FROM navigation_items WHERE id=?',[$id]);$_SESSION['flash_success']='Пункт удалён.';redirect('/studio/menus?menu='.(int)$item['menu_id']);
});

$router->get('/studio/appearance', function () {
    Studio::require('themes');
    Studio::render('appearance',['studioSection'=>'appearance','studioTitle'=>'Внешний вид','themes'=>Studio::themes()]);
});

$router->post('/studio/appearance', function () {
    Studio::require('themes');Csrf::validate();
    foreach(['blog_tagline','blog_description','portfolio_description','blog_footer_text'] as $key)Studio::setSetting($key,mb_substr(trim((string)($_POST[$key]??'')),0,$key==='blog_description'||$key==='portfolio_description'?1000:300));
    $theme=(string)($_POST['blog_theme']??'');$available=array_column(Studio::themes(),'slug');if(!in_array($theme,$available,true))abort(422,'Тема не найдена.');Studio::setSetting('blog_theme',$theme);DB::run('UPDATE themes SET is_active=(slug=?)',[$theme]);
    audit('blog.appearance.update');$_SESSION['flash_success']='Оформление сохранено.';redirect('/studio/appearance');
});

$router->get('/studio/settings', function () {
    Studio::require('settings');
    Studio::render('settings',['studioSection'=>'settings','studioTitle'=>'Настройки сайта']);
});

$router->post('/studio/settings', function () {
    Studio::require('settings');Csrf::validate();
    $siteName=mb_substr(trim((string)($_POST['site_name']??'')),0,100);if($siteName==='')abort(422,'Введите название сайта.');
    Studio::setSetting('site_name',$siteName);Studio::setSetting('seo_description',mb_substr(trim((string)($_POST['seo_description']??'')),0,320));Studio::setSetting('seo_keywords',mb_substr(trim((string)($_POST['seo_keywords']??'')),0,500));Studio::setSetting('copyright',mb_substr(trim((string)($_POST['copyright']??'')),0,300));Studio::setSetting('search_indexing',!empty($_POST['search_indexing'])?'1':'0');Studio::setSetting('comments_auto_approve',!empty($_POST['comments_auto_approve'])?'1':'0');Studio::setSetting('registration_mode',in_array((string)($_POST['registration_mode']??''),['closed','manual','email_auto'],true)?(string)$_POST['registration_mode']:'manual');Studio::setSetting('blog_posts_per_page',(string)max(4,min(50,(int)($_POST['blog_posts_per_page']??12))));
    audit('blog.settings.update');$_SESSION['flash_success']='Настройки сохранены.';redirect('/studio/settings');
});
