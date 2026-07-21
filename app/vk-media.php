<?php

declare(strict_types=1);

use Kovcheg\Auth;
use Kovcheg\DB;

function vk_media_ensure_schema(): void
{
    static $ready=false;
    if($ready)return;

    DB::run("CREATE TABLE IF NOT EXISTS vk_media_albums (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id BIGINT UNSIGNED NOT NULL,
        title VARCHAR(190) NOT NULL,
        description TEXT NULL,
        created_at DATETIME NULL,
        updated_at DATETIME NULL,
        INDEX idx_vk_media_albums_user(user_id,id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    DB::run("CREATE TABLE IF NOT EXISTS vk_media_items (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id BIGINT UNSIGNED NOT NULL,
        album_id BIGINT UNSIGNED NULL,
        media_type VARCHAR(20) NOT NULL,
        title VARCHAR(190) NULL,
        original_name VARCHAR(255) NOT NULL,
        stored_path VARCHAR(255) NOT NULL,
        mime_type VARCHAR(150) NOT NULL,
        file_size BIGINT UNSIGNED NOT NULL,
        created_at DATETIME NULL,
        updated_at DATETIME NULL,
        INDEX idx_vk_media_items_user_type(user_id,media_type,id),
        INDEX idx_vk_media_items_album(album_id,id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    DB::run("CREATE TABLE IF NOT EXISTS vk_media_playlists (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id BIGINT UNSIGNED NOT NULL,
        title VARCHAR(190) NOT NULL,
        description TEXT NULL,
        created_at DATETIME NULL,
        updated_at DATETIME NULL,
        INDEX idx_vk_media_playlists_user(user_id,id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    DB::run("CREATE TABLE IF NOT EXISTS vk_media_playlist_items (
        playlist_id BIGINT UNSIGNED NOT NULL,
        item_id BIGINT UNSIGNED NOT NULL,
        sort_order INT NOT NULL DEFAULT 0,
        created_at DATETIME NULL,
        PRIMARY KEY(playlist_id,item_id),
        INDEX idx_vk_media_playlist_items_order(playlist_id,sort_order,item_id),
        INDEX idx_vk_media_playlist_items_item(item_id,playlist_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $ready=true;
}

function vk_media_template_enabled(): bool
{
    return (string)setting('site_template','default')==='vk';
}

function vk_media_require_template(): void
{
    if(!vk_media_template_enabled())redirect('/profile');
}

function vk_media_allowed_owner(int $userId): array
{
    $owner=DB::one("SELECT id,username,display_name,avatar_path,is_verified,verification_label FROM users WHERE id=? AND is_active=1 AND approval_status='approved'",[$userId]);
    if(!$owner)abort(404,'Пользователь не найден.');
    if(!can_view_profile($owner,Auth::id()))abort(403,'Раздел недоступен.');
    return $owner;
}

function vk_media_page_owner(): array
{
    $requested=max(0,(int)($_GET['user']??0));
    return vk_media_allowed_owner($requested?:Auth::id());
}

function vk_media_item_url(int $id): string
{
    return app_url('/media-library/'.$id);
}

function vk_media_rows(int $userId,string $type,?int $albumId=null,?int $playlistId=null): array
{
    vk_media_ensure_schema();
    if($playlistId){
        $rows=DB::all("SELECT i.*,pi.sort_order FROM vk_media_playlist_items pi JOIN vk_media_items i ON i.id=pi.item_id WHERE pi.playlist_id=? AND i.user_id=? AND i.media_type='audio' ORDER BY pi.sort_order,pi.item_id DESC",[$playlistId,$userId]);
    }elseif($type==='photo'&&$albumId){
        $rows=DB::all("SELECT * FROM vk_media_items WHERE user_id=? AND media_type='photo' AND album_id=? ORDER BY id DESC",[$userId,$albumId]);
    }else{
        $rows=DB::all('SELECT * FROM vk_media_items WHERE user_id=? AND media_type=? ORDER BY id DESC',[$userId,$type]);
    }
    foreach($rows as &$row)$row['url']=vk_media_item_url((int)$row['id']);
    unset($row);
    return $rows;
}

function vk_media_albums(int $userId): array
{
    vk_media_ensure_schema();
    $rows=DB::all("SELECT a.*,(SELECT COUNT(*) FROM vk_media_items i WHERE i.album_id=a.id AND i.user_id=a.user_id AND i.media_type='photo') item_count,(SELECT i.id FROM vk_media_items i WHERE i.album_id=a.id AND i.user_id=a.user_id AND i.media_type='photo' ORDER BY i.id DESC LIMIT 1) cover_item_id FROM vk_media_albums a WHERE a.user_id=? ORDER BY a.updated_at DESC,a.id DESC",[$userId]);
    foreach($rows as &$row)$row['cover_url']=!empty($row['cover_item_id'])?vk_media_item_url((int)$row['cover_item_id']):'';
    unset($row);
    return $rows;
}

function vk_media_playlists(int $userId): array
{
    vk_media_ensure_schema();
    return DB::all("SELECT p.*,(SELECT COUNT(*) FROM vk_media_playlist_items pi JOIN vk_media_items i ON i.id=pi.item_id WHERE pi.playlist_id=p.id AND i.user_id=p.user_id AND i.media_type='audio') item_count FROM vk_media_playlists p WHERE p.user_id=? ORDER BY p.updated_at DESC,p.id DESC",[$userId]);
}

function vk_media_upload_error(array $file): string
{
    $code=(int)($file['error']??UPLOAD_ERR_NO_FILE);
    $errors=[
        UPLOAD_ERR_INI_SIZE=>'Файл больше лимита PHP upload_max_filesize.',
        UPLOAD_ERR_FORM_SIZE=>'Файл больше разрешённого размера формы.',
        UPLOAD_ERR_PARTIAL=>'Файл загрузился не полностью.',
        UPLOAD_ERR_NO_FILE=>'Выберите файл.',
        UPLOAD_ERR_NO_TMP_DIR=>'На сервере отсутствует временная папка.',
        UPLOAD_ERR_CANT_WRITE=>'Сервер не смог записать файл.',
        UPLOAD_ERR_EXTENSION=>'Загрузка остановлена расширением PHP.',
    ];
    return $errors[$code]??'Не удалось принять файл.';
}

function vk_media_store_upload(array $file,int $userId,string $type,?int $albumId=null): int
{
    vk_media_ensure_schema();
    if((int)($file['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK)abort(422,vk_media_upload_error($file));
    $tmp=(string)($file['tmp_name']??'');
    if($tmp===''||!is_file($tmp)||!is_uploaded_file($tmp))abort(422,'Временный файл не найден. Повторите загрузку.');
    $size=max((int)($file['size']??0),(int)filesize($tmp));
    if($size<1)abort(422,'Загруженный файл пустой.');
    $limit=max(5,min(250,(int)setting('max_upload_mb','25')))*1024*1024;
    if($size>$limit)abort(413,'Файл должен быть не больше '.round($limit/1024/1024).' МБ.');

    $original=basename((string)($file['name']??'file'));
    $extension=strtolower(pathinfo($original,PATHINFO_EXTENSION));
    $mime='';
    try{$mime=(new finfo(FILEINFO_MIME_TYPE))->file($tmp)?:'';}catch(Throwable){}
    $fallback=[
        'jpg'=>'image/jpeg','jpeg'=>'image/jpeg','png'=>'image/png','webp'=>'image/webp',
        'mp4'=>'video/mp4','webm'=>'video/webm',
        'mp3'=>'audio/mpeg','m4a'=>'audio/mp4','aac'=>'audio/aac','ogg'=>'audio/ogg','wav'=>'audio/wav','flac'=>'audio/flac',
    ];
    if(($mime===''||$mime==='application/octet-stream')&&isset($fallback[$extension]))$mime=$fallback[$extension];

    $allowed=[
        'photo'=>['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'],
        'video'=>['video/mp4'=>'mp4','video/webm'=>'webm'],
        'audio'=>['audio/mpeg'=>'mp3','audio/mp4'=>'m4a','audio/aac'=>'aac','audio/ogg'=>'ogg','application/ogg'=>'ogg','audio/wav'=>'wav','audio/x-wav'=>'wav','audio/flac'=>'flac'],
    ];
    if(!isset($allowed[$type][$mime]))abort(422,match($type){'photo'=>'Поддерживаются JPG, PNG и WebP.','video'=>'Поддерживаются MP4 и WebM.','audio'=>'Поддерживаются MP3, M4A, AAC, OGG, WAV и FLAC.',default=>'Недопустимый тип файла.'});

    if($type==='photo'&&$albumId){
        $album=DB::one('SELECT id FROM vk_media_albums WHERE id=? AND user_id=?',[$albumId,$userId]);
        if(!$album)abort(422,'Фотоальбом не найден.');
    }

    $base='vk-media/'.$userId.'/'.$type.'/'.date('Ymd-His').'-'.bin2hex(random_bytes(8));
    $relative='';
    $storedMime=$mime;
    $storedSize=$size;
    if($type==='photo'){
        $result=optimize_uploaded_image($tmp,$base,2560,2560,88);
        $relative=(string)$result['relative'];
        $storedMime=(string)$result['mime'];
        $storedSize=(int)$result['size'];
    }else{
        $relative=$base.'.'.$allowed[$type][$mime];
        $destination=BASE_PATH.'/storage/uploads/'.$relative;
        $directory=dirname($destination);
        if(!is_dir($directory)&&!mkdir($directory,0755,true)&&!is_dir($directory))abort(500,'Не удалось создать папку медиатеки.');
        if(!move_uploaded_file($tmp,$destination)&&!copy($tmp,$destination))abort(500,'Не удалось сохранить файл. Проверьте права storage/uploads.');
        $storedSize=(int)filesize($destination);
    }

    $title=utf8_substr(trim((string)($_POST['title']??pathinfo($original,PATHINFO_FILENAME))),0,190);
    try{
        $id=DB::insert('INSERT INTO vk_media_items (user_id,album_id,media_type,title,original_name,stored_path,mime_type,file_size,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,CURRENT_TIMESTAMP,CURRENT_TIMESTAMP)',[$userId,$albumId?:null,$type,$title?:null,$original,$relative,$storedMime,$storedSize]);
    }catch(Throwable $error){@unlink(BASE_PATH.'/storage/uploads/'.$relative);throw $error;}
    if($albumId)DB::run('UPDATE vk_media_albums SET updated_at=CURRENT_TIMESTAMP WHERE id=? AND user_id=?',[$albumId,$userId]);
    audit('vk.media.upload','vk_media_item',$id,['type'=>$type]);
    return $id;
}

function vk_media_delete_item(int $id,int $userId): void
{
    vk_media_ensure_schema();
    $item=DB::one('SELECT * FROM vk_media_items WHERE id=? AND user_id=?',[$id,$userId]);
    if(!$item)abort(404,'Файл не найден.');
    DB::pdo()->beginTransaction();
    try{
        DB::run('DELETE FROM vk_media_playlist_items WHERE item_id=?',[$id]);
        DB::run('DELETE FROM vk_media_items WHERE id=? AND user_id=?',[$id,$userId]);
        DB::pdo()->commit();
    }catch(Throwable $error){if(DB::pdo()->inTransaction())DB::pdo()->rollBack();throw $error;}
    @unlink(BASE_PATH.'/storage/uploads/'.$item['stored_path']);
    audit('vk.media.delete','vk_media_item',$id,['type'=>$item['media_type']]);
}

function vk_media_stream(int $id): void
{
    vk_media_ensure_schema();
    $item=DB::one('SELECT * FROM vk_media_items WHERE id=?',[$id]);
    if(!$item)abort(404,'Файл не найден.');
    vk_media_allowed_owner((int)$item['user_id']);
    $relative=(string)$item['stored_path'];if($relative===''||str_contains($relative,'..')||str_starts_with($relative,'/'))abort(404,'Файл не найден.');
    $path=BASE_PATH.'/storage/uploads/'.$relative;
    if(!is_file($path))abort(404,'Файл не найден на сервере.');

    $size=(int)filesize($path);
    $start=0;$end=max(0,$size-1);$status=200;
    $range=(string)($_SERVER['HTTP_RANGE']??'');
    if($range!==''&&preg_match('/bytes=(\d*)-(\d*)/',$range,$match)){
        if($match[1]!=='')$start=max(0,(int)$match[1]);
        if($match[2]!=='')$end=min($end,(int)$match[2]);
        if($start>$end||$start>=$size){header('Content-Range: bytes */'.$size);http_response_code(416);exit;}
        $status=206;
    }
    $length=$end-$start+1;
    http_response_code($status);
    header('Content-Type: '.(string)$item['mime_type']);
    header('Content-Length: '.$length);
    header('Accept-Ranges: bytes');
    header('Cache-Control: private, max-age=86400');
    header('X-Content-Type-Options: nosniff');
    header('Content-Disposition: inline; filename*=UTF-8\'\''.rawurlencode((string)$item['original_name']));
    if($status===206)header("Content-Range: bytes $start-$end/$size");
    $handle=fopen($path,'rb');
    if(!$handle)abort(500,'Не удалось открыть файл.');
    fseek($handle,$start);$remaining=$length;
    while($remaining>0&&!feof($handle)){$chunk=fread($handle,min(1024*1024,$remaining));if($chunk===false)break;echo $chunk;$remaining-=strlen($chunk);if(connection_aborted())break;}
    fclose($handle);exit;
}
