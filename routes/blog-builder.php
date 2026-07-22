<?php

declare(strict_types=1);

use Kovcheg\Auth;
use Kovcheg\Csrf;
use Kovcheg\DB;
use Kovcheg\Blog\Builder;
use Kovcheg\Blog\ModuleManager;
use Kovcheg\Blog\Studio;
use Kovcheg\Blog\Studio32;

require_once BASE_PATH.'/app/BlogStudio.php';
require_once BASE_PATH.'/app/BlogBuilder.php';
require_once BASE_PATH.'/app/BlogStudio32.php';
require_once BASE_PATH.'/app/BlogModules.php';

/* Registered before blog-studio.php: these handlers upgrade selected Studio routes. */
$router->get('/studio/content/new', function () {
    Studio::require('content');
    $type=in_array((string)($_GET['type']??''),['post','page','portfolio'],true)?(string)$_GET['type']:'post';
    $entry=['id'=>0,'type'=>$type,'status'=>'draft','title'=>'','slug'=>'','excerpt'=>'','content_json'=>'[]','featured_image_path'=>'','template'=>'','visibility'=>'public','comments_enabled'=>1,'reactions_enabled'=>1,'is_featured'=>0,'sort_order'=>0,'seo_title'=>'','seo_description'=>'','published_at'=>'','category_ids'=>[],'tags_text'=>'','meta'=>[]];
    Studio::render('editor',['studioSection'=>'content','studioTitle'=>'Новый материал','entry'=>$entry,'categories'=>DB::all('SELECT * FROM content_categories ORDER BY sort_order,name'),'media'=>DB::all("SELECT * FROM media_library WHERE mime_type LIKE 'image/%' ORDER BY id DESC LIMIT 120"),'mediaFolders'=>DB::all('SELECT * FROM media_folders ORDER BY sort_order,name'),'patterns'=>Builder::patterns(),'revisions'=>[]]);
});

$router->get('/studio/content/{id}/edit', function (array $params) {
    Studio::require('content');$entry=Studio::entry((int)$params['id']);if(!$entry||!empty($entry['deleted_at']))abort(404,'Материал не найден.');
    $autosave=DB::one('SELECT * FROM content_autosaves WHERE entry_id=? AND user_id=? ORDER BY saved_at DESC LIMIT 1',[(int)$entry['id'],Auth::id()]);
    Studio::render('editor',['studioSection'=>'content','studioTitle'=>'Редактирование','entry'=>$entry,'categories'=>DB::all('SELECT * FROM content_categories ORDER BY sort_order,name'),'media'=>DB::all("SELECT * FROM media_library WHERE mime_type LIKE 'image/%' ORDER BY id DESC LIMIT 120"),'mediaFolders'=>DB::all('SELECT * FROM media_folders ORDER BY sort_order,name'),'patterns'=>Builder::patterns(),'autosave'=>$autosave,'revisions'=>DB::all('SELECT r.id,r.title,r.created_at,u.display_name author_name FROM content_revisions r JOIN users u ON u.id=r.author_id WHERE r.entry_id=? ORDER BY r.id DESC LIMIT 30',[(int)$entry['id']])]);
});

$router->post('/studio/content/save', function () {
    Studio::require('content');Csrf::validate();$input=$_POST;
    if(!empty($_FILES['featured_image']['name'])){$media=Studio32::storeMedia($_FILES['featured_image'],Auth::id(),(int)($_POST['featured_folder_id']??0));$input['featured_image_path']=(string)($media['stored_path']??'');}
    $id=Studio32::saveEntry($input,Auth::id(),(int)($_POST['id']??0));$_SESSION['flash_success']='Материал сохранён в редакторе 3.2.';redirect('/studio/content/'.$id.'/edit');
});

$router->post('/studio/content/autosave', function () {
    Studio::require('content');Csrf::validate();Studio32::autosave((int)($_POST['entry_id']??0),Auth::id(),(string)($_POST['title']??''),(string)($_POST['excerpt']??''),(string)($_POST['content_json']??'[]'));json_response(['ok'=>true,'saved_at'=>date('H:i:s')]);
});

$router->get('/studio/media', function () {
    Studio::require('media');$folder=max(0,(int)($_GET['folder']??0));$search=mb_substr(trim((string)($_GET['q']??'')),0,120);$where=['1=1'];$params=[];
    if($folder>0){$where[]='m.folder_id=?';$params[]=$folder;}if($search!==''){$where[]='(m.title LIKE ? OR m.original_name LIKE ? OR m.alt_text LIKE ?)';$q='%'.$search.'%';array_push($params,$q,$q,$q);}
    $media=DB::all('SELECT m.*,u.display_name uploader_name,f.name folder_name FROM media_library m JOIN users u ON u.id=m.uploader_id LEFT JOIN media_folders f ON f.id=m.folder_id WHERE '.implode(' AND ',$where).' ORDER BY m.id DESC LIMIT 500',$params);
    Studio::render('media',['studioSection'=>'media','studioTitle'=>'Медиатека','media'=>$media,'folders'=>DB::all('SELECT f.*,(SELECT COUNT(*) FROM media_library m WHERE m.folder_id=f.id) media_count FROM media_folders f ORDER BY f.sort_order,f.name'),'folder'=>$folder,'search'=>$search]);
});

$router->post('/studio/media/upload', function () {
    Studio::require('media');Csrf::validate();$files=$_FILES['media']??null;if(!$files||!is_array($files['name']??null))abort(422,'Выберите файлы.');$folder=max(0,(int)($_POST['folder_id']??0));$count=0;
    foreach(array_slice(array_keys($files['name']),0,30) as $i){if((string)$files['name'][$i]==='')continue;Studio32::storeMedia(['name'=>$files['name'][$i],'tmp_name'=>$files['tmp_name'][$i],'error'=>$files['error'][$i],'size'=>$files['size'][$i]],Auth::id(),$folder);$count++;}
    $_SESSION['flash_success']='Загружено файлов: '.$count;redirect('/studio/media'.($folder?'?folder='.$folder:''));
});

$router->post('/studio/media/folders', function () {
    Studio::require('media');Csrf::validate();$name=mb_substr(trim((string)($_POST['name']??'')),0,150);if($name==='')abort(422,'Введите название папки.');$slug=Studio::slugify($name);$base=$slug?:'folder';$n=2;while(DB::one('SELECT id FROM media_folders WHERE slug=?',[$slug]))$slug=$base.'-'.$n++;
    $id=DB::insert('INSERT INTO media_folders (name,slug,sort_order,created_at,updated_at) VALUES (?,?,?,CURRENT_TIMESTAMP,CURRENT_TIMESTAMP)',[$name,$slug,max(-999,min(999,(int)($_POST['sort_order']??0)))]);$_SESSION['flash_success']='Папка создана.';redirect('/studio/media?folder='.$id);
});

$router->post('/studio/media/{id}/update', function (array $params) {
    Studio::require('media');Csrf::validate();$id=(int)$params['id'];if(!DB::one('SELECT id FROM media_library WHERE id=?',[$id]))abort(404,'Файл не найден.');$folder=max(0,(int)($_POST['folder_id']??0));if($folder&&!DB::one('SELECT id FROM media_folders WHERE id=?',[$folder]))abort(404,'Папка не найдена.');
    DB::run('UPDATE media_library SET folder_id=?,title=?,alt_text=?,caption=?,updated_at=CURRENT_TIMESTAMP WHERE id=?',[$folder?:null,mb_substr(trim((string)($_POST['title']??'')),0,190)?:null,mb_substr(trim((string)($_POST['alt_text']??'')),0,255)?:null,mb_substr(trim((string)($_POST['caption']??'')),0,500)?:null,$id]);$_SESSION['flash_success']='Данные файла сохранены.';redirect('/studio/media'.($folder?'?folder='.$folder:''));
});

$router->get('/studio/patterns', function () {Studio::require('content');Studio::render('patterns',['studioSection'=>'patterns','studioTitle'=>'Шаблоны секций','patterns'=>Builder::patterns()]);});
$router->post('/studio/patterns', function () {Studio::require('content');Csrf::validate();$id=Builder::savePattern((string)($_POST['name']??''),(string)($_POST['description']??''),(string)($_POST['blocks_json']??'[]'),Auth::id());audit('blog.pattern.create','content_pattern',$id);$_SESSION['flash_success']='Шаблон секций сохранён.';redirect('/studio/patterns');});
$router->post('/studio/patterns/{id}/delete', function (array $params) {Studio::require('content');Csrf::validate();Builder::deletePattern((int)$params['id'],Auth::id(),Auth::isAdmin());$_SESSION['flash_success']='Шаблон удалён.';redirect('/studio/patterns');});

$router->get('/studio/users', function () {
    Studio::require('site');$q=mb_substr(trim((string)($_GET['q']??'')),0,120);$where='1=1';$params=[];if($q!==''){$where='(display_name LIKE ? OR username LIKE ? OR email LIKE ?)';$like='%'.$q.'%';$params=[$like,$like,$like];}
    Studio::render('users',['studioSection'=>'users','studioTitle'=>'Пользователи и роли','users'=>DB::all("SELECT id,username,display_name,email,role,is_active,approval_status,created_at,last_seen_at FROM users WHERE {$where} ORDER BY FIELD(role,'owner','admin','editor','moderator','user'),id DESC LIMIT 500",$params),'search'=>$q,'roleHistory'=>DB::all('SELECT h.*,u.display_name user_name,a.display_name actor_name FROM user_role_history h JOIN users u ON u.id=h.user_id LEFT JOIN users a ON a.id=h.changed_by ORDER BY h.id DESC LIMIT 40')]);
});
$router->post('/studio/users/{id}/role', function (array $params) {Studio::require('site');Csrf::validate();Studio32::changeUserRole((int)$params['id'],(string)($_POST['role']??''),Auth::id());$_SESSION['flash_success']='Роль пользователя обновлена.';redirect('/studio/users');});
$router->post('/studio/users/{id}/status', function (array $params) {Studio::require('site');Csrf::validate();$id=(int)$params['id'];if($id===Auth::id())abort(422,'Нельзя заблокировать собственную учётную запись.');$active=!empty($_POST['active'])?1:0;DB::run('UPDATE users SET is_active=?,updated_at=CURRENT_TIMESTAMP WHERE id=?',[$active,$id]);audit('blog.user.status','user',$id,['active'=>$active]);$_SESSION['flash_success']='Статус пользователя изменён.';redirect('/studio/users');});

$router->get('/studio/presets', function () {Studio::require('site');Studio::render('presets',['studioSection'=>'presets','studioTitle'=>'Профессиональные пресеты','presets'=>Studio32::presets()]);});
$router->post('/studio/presets/{slug}/apply', function (array $params) {Studio::require('site');Csrf::validate();Studio32::applyPreset((string)$params['slug'],Auth::id());$_SESSION['flash_success']='Пресет применён. Проверьте главную и оформление.';redirect('/studio/presets');});

$router->get('/studio/modules', function () {Studio::require('site');Studio::render('modules',['studioSection'=>'modules','studioTitle'=>'Модули','modules'=>ModuleManager::installed()]);});
$router->post('/studio/modules/install', function () {Studio::require('site');Csrf::validate();$manifest=ModuleManager::install($_FILES['package']??[],!empty($_POST['enable']));$_SESSION['flash_success']='Модуль '.(string)$manifest['name'].' установлен.';redirect('/studio/modules');});
$router->post('/studio/modules/{slug}/toggle', function (array $params) {Studio::require('site');Csrf::validate();ModuleManager::setEnabled((string)$params['slug'],!empty($_POST['enabled']));$_SESSION['flash_success']='Состояние модуля изменено.';redirect('/studio/modules');});
$router->post('/studio/modules/{slug}/remove', function (array $params) {Studio::require('site');Csrf::validate();ModuleManager::remove((string)$params['slug']);$_SESSION['flash_success']='Модуль удалён.';redirect('/studio/modules');});
