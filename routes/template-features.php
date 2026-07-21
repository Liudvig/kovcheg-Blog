<?php

declare(strict_types=1);

use Kovcheg\Auth;
use Kovcheg\Csrf;
use Kovcheg\DB;
use Kovcheg\View;

require_once BASE_PATH.'/app/vk-media.php';

function kovcheg_post_wants_json(): bool
{
    return str_contains(strtolower((string)($_SERVER['HTTP_ACCEPT']??'')),'application/json')
        || strtolower((string)($_SERVER['HTTP_X_REQUESTED_WITH']??''))==='xmlhttprequest';
}

function kovcheg_post_client_token(): string
{
    $token=trim((string)($_POST['client_post_token']??''));
    return preg_match('/^[a-zA-Z0-9_-]{16,96}$/',$token)?$token:'';
}

function kovcheg_post_token_lookup(string $scope,int $userId,string $token): int
{
    if($token==='')return 0;
    $all=is_array($_SESSION['post_idempotency']??null)?$_SESSION['post_idempotency']:[];
    $now=time();
    foreach($all as $key=>$item)if(!is_array($item)||($now-(int)($item['time']??0))>7200)unset($all[$key]);
    $_SESSION['post_idempotency']=$all;
    $key=hash('sha256',$scope.'|'.$userId.'|'.$token);
    return (int)($all[$key]['id']??0);
}

function kovcheg_post_token_store(string $scope,int $userId,string $token,int $postId): void
{
    if($token===''||$postId<1)return;
    $all=is_array($_SESSION['post_idempotency']??null)?$_SESSION['post_idempotency']:[];
    $all[hash('sha256',$scope.'|'.$userId.'|'.$token)]=['id'=>$postId,'time'=>time()];
    if(count($all)>120)$all=array_slice($all,-120,null,true);
    $_SESSION['post_idempotency']=$all;
}

function kovcheg_post_json_or_redirect(int $id,string $message,string $state,string $redirect,bool $duplicate=false): void
{
    if(kovcheg_post_wants_json()){
        $post=$state==='published'?profile_post_for_render($id):null;
        json_response(['ok'=>true,'id'=>$id,'state'=>$state,'message'=>$message,'duplicate'=>$duplicate,'html'=>$post?View::partial('feed-post',['post'=>$post]):'']);
    }
    $_SESSION['flash_success']=$message;
    redirect($redirect);
}

function kovcheg_uploaded_files(string $field): array
{
    $source=$_FILES[$field]??null;
    if(!$source||!is_array($source['name']??null))return [];
    $files=[];
    foreach($source['name'] as $index=>$name){
        if((string)$name==='')continue;
        $files[]=[
            'name'=>$name,
            'type'=>$source['type'][$index]??'',
            'tmp_name'=>$source['tmp_name'][$index]??'',
            'error'=>$source['error'][$index]??UPLOAD_ERR_NO_FILE,
            'size'=>$source['size'][$index]??0,
        ];
    }
    return $files;
}

/* Registered before routes/web.php: Router resolves the first matching route. */
$router->post('/feed/post', function(){
    Auth::requireLogin();Csrf::validate();$uid=Auth::id();$token=kovcheg_post_client_token();
    $existing=kovcheg_post_token_lookup('feed',$uid,$token);
    if($existing){$row=DB::one('SELECT id,status FROM profile_posts WHERE id=? AND author_id=? AND deleted_at IS NULL',[$existing,$uid]);if($row){$state=(string)$row['status'];$message=$state==='draft'?'Пост уже сохранён в черновиках.':($state==='scheduled'?'Публикация уже запланирована.':'Запись уже опубликована.');kovcheg_post_json_or_redirect((int)$row['id'],$message,$state,'/feed',true);}}
    $body=trim((string)($_POST['body']??''));$photos=$_FILES['photos']??null;$videos=$_FILES['videos']??null;$documents=$_FILES['documents']??null;
    $hasPhotos=$photos&&is_array($photos['name']??null)&&count(array_filter($photos['name']))>0;$hasVideos=$videos&&is_array($videos['name']??null)&&count(array_filter($videos['name']))>0;$hasDocuments=$documents&&is_array($documents['name']??null)&&count(array_filter($documents['name']))>0;
    if($body===''&&!$hasPhotos&&!$hasVideos&&!$hasDocuments)abort(422,'Добавьте текст или вложение.');
    try{$state=profile_post_state_from_request($uid,$uid);}catch(RuntimeException $e){abort(422,$e->getMessage());}
    $id=DB::insert('INSERT INTO profile_posts (user_id,author_id,body,visibility,status,publish_at,created_at,updated_at) VALUES (?,?,?,?,?,?,CURRENT_TIMESTAMP,CURRENT_TIMESTAMP)',[$uid,$uid,utf8_substr($body,0,5000),$state['visibility'],$state['status'],$state['publishAt']]);$stored=[];
    try{if($hasPhotos)$stored=array_merge($stored,save_wall_post_photos($photos,$id,$uid));if($hasVideos)$stored=array_merge($stored,save_wall_post_videos($videos,$id,$uid));if($hasDocuments)$stored=array_merge($stored,save_wall_post_documents($documents,$id,$uid));}catch(Throwable $e){delete_uploaded_relatives($stored);DB::run('DELETE FROM profile_posts WHERE id=?',[$id]);throw $e;}
    kovcheg_post_token_store('feed',$uid,$token,$id);
    $message=$state['status']==='draft'?'Пост сохранён в черновиках.':($state['status']==='scheduled'?'Публикация запланирована.':'Запись опубликована.');
    audit('feed.post','profile_post',$id,['status'=>$state['status'],'visibility'=>$state['visibility']]);
    if($state['status']==='published'&&$state['visibility']!=='only_me')notify_social_audience($uid,'Новая запись',(Auth::user()['display_name']??'Пользователь').' опубликовал запись',wall_post_public_url((string)(Auth::user()['username']??''),$id),'social-post-'.$id);
    kovcheg_post_json_or_redirect($id,$message,(string)$state['status'],'/feed');
});

$router->post('/profile/{id}/wall', function($params){
    Auth::requireLogin();Csrf::validate();$profileId=(int)$params['id'];$authorId=Auth::id();$token=kovcheg_post_client_token();$scope='profile-'.$profileId;
    $profileUser=DB::one("SELECT id,username,display_name,avatar_path FROM users WHERE id=? AND is_active=1 AND approval_status='approved'",[$profileId]);if(!$profileUser)abort(404,'Профиль не найден.');if(!profile_wall_can_post($profileUser,$authorId))abort(403,'Публикации на этой стене ограничены.');
    $redirect=$profileId===$authorId?'/profile':'/@'.$profileUser['username'];
    $existing=kovcheg_post_token_lookup($scope,$authorId,$token);
    if($existing){$row=DB::one('SELECT id,status FROM profile_posts WHERE id=? AND author_id=? AND user_id=? AND deleted_at IS NULL',[$existing,$authorId,$profileId]);if($row){$state=(string)$row['status'];$message=$state==='draft'?'Пост уже сохранён в черновиках.':($state==='scheduled'?'Публикация уже запланирована.':'Запись уже опубликована.');kovcheg_post_json_or_redirect((int)$row['id'],$message,$state,$redirect,true);}}
    $body=trim((string)($_POST['body']??''));$photos=$_FILES['photos']??null;$videos=$_FILES['videos']??null;$documents=$_FILES['documents']??null;
    $hasPhotos=$photos&&is_array($photos['name']??null)&&count(array_filter($photos['name']))>0;$hasVideos=$videos&&is_array($videos['name']??null)&&count(array_filter($videos['name']))>0;$hasDocuments=$documents&&is_array($documents['name']??null)&&count(array_filter($documents['name']))>0;
    if($body===''&&!$hasPhotos&&!$hasVideos&&!$hasDocuments)abort(422,'Добавьте текст или вложение.');if(utf8_substr($body,0,5000)!==$body)abort(422,'Текст должен быть до 5000 символов.');
    try{$state=profile_post_state_from_request($authorId,$profileId);}catch(RuntimeException $e){abort(422,$e->getMessage());}
    $id=DB::insert('INSERT INTO profile_posts (user_id,author_id,body,visibility,status,publish_at,created_at,updated_at) VALUES (?,?,?,?,?,?,CURRENT_TIMESTAMP,CURRENT_TIMESTAMP)',[$profileId,$authorId,$body,$state['visibility'],$state['status'],$state['publishAt']]);$stored=[];
    try{if($hasPhotos)$stored=array_merge($stored,save_wall_post_photos($photos,$id,$authorId));if($hasVideos)$stored=array_merge($stored,save_wall_post_videos($videos,$id,$authorId));if($hasDocuments)$stored=array_merge($stored,save_wall_post_documents($documents,$id,$authorId));}catch(Throwable $e){delete_uploaded_relatives($stored);DB::run('DELETE FROM profile_posts WHERE id=?',[$id]);throw $e;}
    kovcheg_post_token_store($scope,$authorId,$token,$id);
    if($profileId!==$authorId&&$state['status']==='published')queue_user_push($profileId,'Новая запись на стене',(Auth::user()['display_name']??'Пользователь').' оставил запись в вашем профиле',user_public_url((string)$profileUser['username']),avatar_url($authorId,(string)(Auth::user()['avatar_path']??'')),'wall-'.$id);
    $message=$state['status']==='draft'?'Пост сохранён в черновиках.':($state['status']==='scheduled'?'Публикация запланирована.':'Запись опубликована.');
    audit('profile.wall.create','profile_post',$id,['profile_user_id'=>$profileId,'status'=>$state['status'],'visibility'=>$state['visibility']]);
    if($state['status']==='published'&&$state['visibility']!=='only_me')notify_social_audience($authorId,'Новая запись',(Auth::user()['display_name']??'Пользователь').' опубликовал запись',wall_post_public_url((string)$profileUser['username'],$id),'social-post-'.$id);
    kovcheg_post_json_or_redirect($id,$message,(string)$state['status'],$redirect);
});

$router->post('/profile/settings', function(){
    Auth::requireLogin();Csrf::validate();$template=(string)setting('site_template','default');$allowed=$template==='x'?['black','light']:['light','dark','black'];$fallback=$template==='x'?'black':(string)setting('default_theme','dark');$requested=(string)($_POST['theme']??$fallback);$theme=in_array($requested,$allowed,true)?$requested:$fallback;
    $preview=in_array($_POST['notification_preview']??'full',['full','sender','count','hidden'],true)?$_POST['notification_preview']:'full';$max=max(1,min(5,(int)($_POST['notification_max']??3)));$flag=static fn(string $key): string=>array_key_exists($key,$_POST)&&in_array((string)$_POST[$key],['1','on','true'],true)?'1':'0';
    $settings=['theme'=>$theme,'notifications_enabled'=>$flag('notifications_enabled'),'notification_sound'=>$flag('notification_sound'),'notification_preview'=>$preview,'notification_avatar'=>$flag('notification_avatar'),'notification_max'=>(string)$max,'desktop_notifications'=>$flag('desktop_notifications')];foreach($settings as $key=>$value)set_user_setting($key,$value);
    if(array_key_exists('profile_right_column',$_POST))set_user_setting('profile_right_column',$flag('profile_right_column'));if(array_key_exists('weather_city',$_POST)){$weatherCity=utf8_substr(trim((string)$_POST['weather_city']),0,120);set_user_setting('weather_city',$weatherCity);if($weatherCity!=='')try{weather_short_forecast($weatherCity,true);}catch(Throwable $e){log_error($e);}}
    audit('profile.settings','user',Auth::id());$_SESSION['flash_success']='Настройки сохранены.';redirect('/settings/'.((string)($_POST['settings_section']??'notifications')));
});
$router->post('/profile/theme', function(){Auth::requireLogin();Csrf::validate();$template=(string)setting('site_template','default');$allowed=$template==='x'?['black','light']:['dark','light','black'];$fallback=$template==='x'?'black':'dark';$theme=in_array((string)($_POST['theme']??$fallback),$allowed,true)?(string)$_POST['theme']:$fallback;set_user_setting('theme',$theme);json_response(['ok'=>true,'theme'=>$theme]);});

$router->get('/photos', function(){Auth::requireLogin();vk_media_require_template();vk_media_ensure_schema();$owner=vk_media_page_owner();$albumId=max(0,(int)($_GET['album']??0));$album=null;if($albumId){$album=DB::one('SELECT * FROM vk_media_albums WHERE id=? AND user_id=?',[$albumId,$owner['id']]);if(!$album)abort(404,'Фотоальбом не найден.');}View::render('media-library',['title'=>'Фотографии','mediaType'=>'photo','owner'=>$owner,'isSelf'=>(int)$owner['id']===Auth::id(),'items'=>vk_media_rows((int)$owner['id'],'photo',$albumId?:null),'albums'=>vk_media_albums((int)$owner['id']),'activeAlbum'=>$album,'playlists'=>[],'activePlaylist'=>null]);});
$router->post('/photos/album', function(){Auth::requireLogin();vk_media_require_template();Csrf::validate();vk_media_ensure_schema();$title=utf8_substr(trim((string)($_POST['title']??'')),0,190);if($title==='')abort(422,'Введите название альбома.');$description=utf8_substr(trim((string)($_POST['description']??'')),0,1000);$id=DB::insert('INSERT INTO vk_media_albums (user_id,title,description,created_at,updated_at) VALUES (?,?,?,CURRENT_TIMESTAMP,CURRENT_TIMESTAMP)',[Auth::id(),$title,$description?:null]);audit('vk.album.create','vk_media_album',$id);$_SESSION['flash_success']='Фотоальбом создан.';redirect('/photos?album='.$id);});
$router->post('/photos/upload', function(){Auth::requireLogin();vk_media_require_template();Csrf::validate();$albumId=max(0,(int)($_POST['album_id']??0));$files=kovcheg_uploaded_files('media');if(!$files)abort(422,'Выберите фотографии.');foreach(array_slice($files,0,30) as $file)vk_media_store_upload($file,Auth::id(),'photo',$albumId?:null);$_SESSION['flash_success']='Фотографии загружены.';redirect('/photos'.($albumId?'?album='.$albumId:''));});
$router->post('/photos/album/{id}/delete', function($params){Auth::requireLogin();vk_media_require_template();Csrf::validate();vk_media_ensure_schema();$id=(int)$params['id'];if(!DB::one('SELECT id FROM vk_media_albums WHERE id=? AND user_id=?',[$id,Auth::id()]))abort(404,'Альбом не найден.');DB::run('UPDATE vk_media_items SET album_id=NULL,updated_at=CURRENT_TIMESTAMP WHERE album_id=? AND user_id=?',[$id,Auth::id()]);DB::run('DELETE FROM vk_media_albums WHERE id=? AND user_id=?',[$id,Auth::id()]);audit('vk.album.delete','vk_media_album',$id);$_SESSION['flash_success']='Альбом удалён, фотографии сохранены в общем разделе.';redirect('/photos');});

$router->get('/videos', function(){Auth::requireLogin();vk_media_require_template();$owner=vk_media_page_owner();View::render('media-library',['title'=>'Видео','mediaType'=>'video','owner'=>$owner,'isSelf'=>(int)$owner['id']===Auth::id(),'items'=>vk_media_rows((int)$owner['id'],'video'),'albums'=>[],'activeAlbum'=>null,'playlists'=>[],'activePlaylist'=>null]);});
$router->post('/videos/upload', function(){Auth::requireLogin();vk_media_require_template();Csrf::validate();$files=kovcheg_uploaded_files('media');if(!$files)abort(422,'Выберите видео.');foreach(array_slice($files,0,10) as $file)vk_media_store_upload($file,Auth::id(),'video');$_SESSION['flash_success']='Видео загружено.';redirect('/videos');});

$router->get('/music', function(){Auth::requireLogin();vk_media_require_template();vk_media_ensure_schema();$owner=vk_media_page_owner();$playlistId=max(0,(int)($_GET['playlist']??0));$playlist=null;if($playlistId){$playlist=DB::one('SELECT * FROM vk_media_playlists WHERE id=? AND user_id=?',[$playlistId,$owner['id']]);if(!$playlist)abort(404,'Плейлист не найден.');}View::render('media-library',['title'=>'Музыка','mediaType'=>'audio','owner'=>$owner,'isSelf'=>(int)$owner['id']===Auth::id(),'items'=>vk_media_rows((int)$owner['id'],'audio',null,$playlistId?:null),'albums'=>[],'activeAlbum'=>null,'playlists'=>vk_media_playlists((int)$owner['id']),'activePlaylist'=>$playlist]);});
$router->post('/music/upload', function(){Auth::requireLogin();vk_media_require_template();Csrf::validate();$playlistId=max(0,(int)($_POST['playlist_id']??0));if($playlistId&&!DB::one('SELECT id FROM vk_media_playlists WHERE id=? AND user_id=?',[$playlistId,Auth::id()]))abort(422,'Плейлист не найден.');$files=kovcheg_uploaded_files('media');if(!$files)abort(422,'Выберите аудиофайлы.');foreach(array_slice($files,0,30) as $file){$id=vk_media_store_upload($file,Auth::id(),'audio');if($playlistId){$sort=(int)(DB::one('SELECT COALESCE(MAX(sort_order),0)+10 next_sort FROM vk_media_playlist_items WHERE playlist_id=?',[$playlistId])['next_sort']??10);DB::run('INSERT IGNORE INTO vk_media_playlist_items (playlist_id,item_id,sort_order,created_at) VALUES (?,?,?,CURRENT_TIMESTAMP)',[$playlistId,$id,$sort]);}}if($playlistId)DB::run('UPDATE vk_media_playlists SET updated_at=CURRENT_TIMESTAMP WHERE id=? AND user_id=?',[$playlistId,Auth::id()]);$_SESSION['flash_success']='Музыка загружена.';redirect('/music'.($playlistId?'?playlist='.$playlistId:''));});
$router->post('/music/playlist', function(){Auth::requireLogin();vk_media_require_template();Csrf::validate();vk_media_ensure_schema();$title=utf8_substr(trim((string)($_POST['title']??'')),0,190);if($title==='')abort(422,'Введите название плейлиста.');$description=utf8_substr(trim((string)($_POST['description']??'')),0,1000);$id=DB::insert('INSERT INTO vk_media_playlists (user_id,title,description,created_at,updated_at) VALUES (?,?,?,CURRENT_TIMESTAMP,CURRENT_TIMESTAMP)',[Auth::id(),$title,$description?:null]);audit('vk.playlist.create','vk_media_playlist',$id);$_SESSION['flash_success']='Плейлист создан.';redirect('/music?playlist='.$id);});
$router->post('/music/playlist/{id}/add', function($params){Auth::requireLogin();vk_media_require_template();Csrf::validate();vk_media_ensure_schema();$playlistId=(int)$params['id'];$itemId=(int)($_POST['item_id']??0);if(!DB::one('SELECT id FROM vk_media_playlists WHERE id=? AND user_id=?',[$playlistId,Auth::id()]))abort(404,'Плейлист не найден.');if(!DB::one("SELECT id FROM vk_media_items WHERE id=? AND user_id=? AND media_type='audio'",[$itemId,Auth::id()]))abort(404,'Трек не найден.');$sort=(int)(DB::one('SELECT COALESCE(MAX(sort_order),0)+10 next_sort FROM vk_media_playlist_items WHERE playlist_id=?',[$playlistId])['next_sort']??10);DB::run('INSERT IGNORE INTO vk_media_playlist_items (playlist_id,item_id,sort_order,created_at) VALUES (?,?,?,CURRENT_TIMESTAMP)',[$playlistId,$itemId,$sort]);DB::run('UPDATE vk_media_playlists SET updated_at=CURRENT_TIMESTAMP WHERE id=?',[$playlistId]);$_SESSION['flash_success']='Трек добавлен в плейлист.';redirect('/music?playlist='.$playlistId);});
$router->post('/music/playlist/{id}/remove', function($params){Auth::requireLogin();vk_media_require_template();Csrf::validate();$playlistId=(int)$params['id'];$itemId=(int)($_POST['item_id']??0);if(!DB::one('SELECT id FROM vk_media_playlists WHERE id=? AND user_id=?',[$playlistId,Auth::id()]))abort(404,'Плейлист не найден.');DB::run('DELETE FROM vk_media_playlist_items WHERE playlist_id=? AND item_id=?',[$playlistId,$itemId]);$_SESSION['flash_success']='Трек убран из плейлиста.';redirect('/music?playlist='.$playlistId);});
$router->post('/music/playlist/{id}/delete', function($params){Auth::requireLogin();vk_media_require_template();Csrf::validate();$id=(int)$params['id'];if(!DB::one('SELECT id FROM vk_media_playlists WHERE id=? AND user_id=?',[$id,Auth::id()]))abort(404,'Плейлист не найден.');DB::run('DELETE FROM vk_media_playlist_items WHERE playlist_id=?',[$id]);DB::run('DELETE FROM vk_media_playlists WHERE id=? AND user_id=?',[$id,Auth::id()]);audit('vk.playlist.delete','vk_media_playlist',$id);$_SESSION['flash_success']='Плейлист удалён, треки сохранены.';redirect('/music');});
$router->post('/media-library/{id}/delete', function($params){Auth::requireLogin();vk_media_require_template();Csrf::validate();vk_media_ensure_schema();$id=(int)$params['id'];$row=DB::one('SELECT media_type FROM vk_media_items WHERE id=? AND user_id=?',[$id,Auth::id()]);if(!$row)abort(404,'Файл не найден.');vk_media_delete_item($id,Auth::id());$_SESSION['flash_success']='Файл удалён.';redirect(match((string)$row['media_type']){'photo'=>'/photos','video'=>'/videos',default=>'/music'});});
$router->get('/media-library/{id}', function($params){Auth::requireLogin();vk_media_stream((int)$params['id']);});
