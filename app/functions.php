<?php

declare(strict_types=1);

use Kovcheg\Auth;
use Kovcheg\Csrf;
use Kovcheg\DB;

function cfg(string $key, mixed $default=null): mixed {
    global $CONFIG;
    $parts=explode('.',$key); $value=$CONFIG;
    foreach($parts as $part){
        if(!is_array($value)||!array_key_exists($part,$value)) return $default;
        $value=$value[$part];
    }
    return $value;
}

function setting(string $key, mixed $default=null): mixed {
    static $cache=[];
    if(array_key_exists($key,$cache)) return $cache[$key];
    try{$row=DB::one('SELECT value FROM settings WHERE `key`=?',[$key]);}
    catch(Throwable){return $default;}
    return $cache[$key]=$row!==null?$row['value']:$default;
}

function set_setting(string $key, string $value): void {
    DB::run('INSERT INTO settings (`key`,`value`,`updated_at`) VALUES (?,?,CURRENT_TIMESTAMP) ON DUPLICATE KEY UPDATE value=VALUES(value),updated_at=CURRENT_TIMESTAMP',[$key,$value]);
}

function user_setting(string $key, mixed $default=null, int $userId=0): mixed {
    $userId=$userId?:Auth::id();
    if(!$userId) return $default;
    static $cache=[];
    $cacheKey=$userId.':'.$key;
    if(array_key_exists($cacheKey,$cache)) return $cache[$cacheKey];
    try{$row=DB::one('SELECT `value` FROM user_settings WHERE user_id=? AND `key`=?',[$userId,$key]);}
    catch(Throwable){return $default;}
    return $cache[$cacheKey]=$row!==null?$row['value']:$default;
}

function set_user_setting(string $key, string $value, int $userId=0): void {
    $userId=$userId?:Auth::id();
    if(!$userId) return;
    DB::run('INSERT INTO user_settings (user_id,`key`,`value`,updated_at) VALUES (?,?,?,CURRENT_TIMESTAMP) ON DUPLICATE KEY UPDATE `value`=VALUES(`value`),updated_at=CURRENT_TIMESTAMP',[$userId,$key,$value]);
}

function app_url(string $path=''): string { return rtrim((string)cfg('app.url'),'/').'/'.ltrim($path,'/'); }
function e(mixed $value): string { return htmlspecialchars((string)$value,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8'); }
function csrf_field(): string { return '<input type="hidden" name="_csrf" value="'.e(Csrf::token()).'">'; }
function redirect(string $path): never { $url=preg_match('~^https?://~i',$path)?$path:app_url($path);header('Location: '.$url);exit; }
function json_response(array $data,int $status=200): never { http_response_code($status);header('Content-Type: application/json; charset=utf-8');echo json_encode($data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);exit; }
function render_system_error(int $status,string $title='Ошибка',string $message='Произошла ошибка.'): never {
    http_response_code($status);
    if(!headers_sent()){
        header('Content-Type: text/html; charset=utf-8');
        header('Cache-Control: no-store, max-age=0');
        header('X-Robots-Tag: noindex, nofollow, noarchive');
    }
    $home=htmlspecialchars(app_url('/'),ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
    $safeTitle=htmlspecialchars($title,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
    $safeMessage=htmlspecialchars($message,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
    echo '<!doctype html><html lang="ru"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>'.$status.' — '.$safeTitle.'</title><style>html{background:#101820;color:#eef3f7;font-family:system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif}body{min-height:100vh;margin:0;display:grid;place-items:center;padding:24px;box-sizing:border-box}.system-error{width:min(560px,100%);background:#182630;border:1px solid rgba(255,255,255,.12);border-radius:18px;padding:28px;box-sizing:border-box;box-shadow:0 20px 60px rgba(0,0,0,.28)}h1{font-size:54px;margin:0 0 8px}h2{margin:0 0 12px;font-size:24px}p{line-height:1.55;color:#c8d4dc}a{display:inline-flex;margin-top:12px;padding:11px 16px;border-radius:10px;background:#eef3f7;color:#101820;text-decoration:none;font-weight:700}</style></head><body><main class="system-error"><h1>'.$status.'</h1><h2>'.$safeTitle.'</h2><p>'.$safeMessage.'</p><a href="'.$home.'">На главную</a></main></body></html>';
    exit;
}
function abort(int $status,string $message='Ошибка'): never {
    if(str_contains(strtolower((string)($_SERVER['HTTP_ACCEPT']??'')),'application/json') || (string)($_SERVER['HTTP_X_REQUESTED_WITH']??'')==='XMLHttpRequest') json_response(['ok'=>false,'error'=>$message],$status);
    render_system_error($status,$status.' — '.$message,$message);
}

function request_path(): string {
    $path=parse_url($_SERVER['REQUEST_URI']??'/',PHP_URL_PATH)?:'/';
    $base=parse_url((string)cfg('app.url'),PHP_URL_PATH)?:''; $base=rtrim($base,'/');
    if($base!==''&&str_starts_with($path,$base))$path=substr($path,strlen($base))?:'/';
    return '/'.ltrim($path,'/');
}

function now_sql(): string { return date('Y-m-d H:i:s'); }
function human_time(?string $date): string { if(!$date)return ''; $t=strtotime($date);if(date('Y-m-d',$t)===date('Y-m-d'))return date('H:i',$t);return date('d.m.Y',$t); }
function online(?string $date): bool { return $date&&strtotime($date)>time()-90; }
function mb_lower(string $value): string { return function_exists('mb_strtolower')?mb_strtolower($value,'UTF-8'):strtolower($value); }
function utf8_substr(string $value,int $start,int $length): string { return function_exists('mb_substr')?mb_substr($value,$start,$length,'UTF-8'):substr($value,$start,$length); }
function first_char(string $value): string { return utf8_substr(trim($value),0,1); }
function format_bytes(int $bytes): string { $units=['Б','КБ','МБ','ГБ'];$i=0;$n=$bytes;while($n>=1024&&$i<count($units)-1){$n/=1024;$i++;}return round($n,$i?1:0).' '.$units[$i]; }

function audit(string $action,string $entityType='system',?int $entityId=null,array $meta=[]): void {
    try{DB::run('INSERT INTO audit_logs (user_id,action,entity_type,entity_id,ip,meta_json,created_at) VALUES (?,?,?,?,?,?,CURRENT_TIMESTAMP)',[Auth::id()?:null,$action,$entityType,$entityId,$_SERVER['REMOTE_ADDR']??'',json_encode($meta,JSON_UNESCAPED_UNICODE)]);}catch(Throwable){}
}
function log_error(Throwable $e): void {
    $dir=BASE_PATH.'/storage/logs';if(!is_dir($dir))@mkdir($dir,0755,true);$path=$dir.'/app.log';
    if(is_file($path)&&filesize($path)>5*1024*1024){@rename($path,$dir.'/app-'.date('Ymd-His').'.log');}
    $request=(string)($_SERVER['REQUEST_METHOD']??'CLI').' '.(string)($_SERVER['REQUEST_URI']??'');
    $line='['.date('c').'] '.$request.' | '.get_class($e).': '.$e->getMessage().' in '.$e->getFile().':'.$e->getLine()."\n".$e->getTraceAsString()."\n";
    @file_put_contents($path,$line,FILE_APPEND|LOCK_EX);
}
function rrmdir(string $dir): void { if(!is_dir($dir))return;foreach(scandir($dir)?:[] as $file){if($file==='.'||$file==='..')continue;$path=$dir.'/'.$file;is_dir($path)?rrmdir($path):@unlink($path);}@rmdir($dir); }


function is_impersonating(): bool { return !empty($_SESSION['impersonator_user_id'])&&!empty($_SESSION['impersonated_user_id']); }
function impersonator_user_id(): int { return (int)($_SESSION['impersonator_user_id']??0); }
function touch_presence(int $userId=0): void {
    if(is_impersonating())return;
    $userId=$userId?:Auth::id();if(!$userId)return;
    try{DB::run('UPDATE users SET last_seen_at=CURRENT_TIMESTAMP WHERE id=?',[$userId]);}catch(Throwable){}
}

function image_driver_available(): bool { return extension_loaded('gd')||extension_loaded('imagick'); }
function uploaded_image_info(string $tmp): array {
    $mime=(new finfo(FILEINFO_MIME_TYPE))->file($tmp)?:'';
    $size=@getimagesize($tmp);
    if(!$size||!str_starts_with($mime,'image/'))throw new RuntimeException('Файл не является корректным изображением.');
    $width=(int)$size[0];$height=(int)$size[1];
    if($width<1||$height<1||$width*$height>40000000)throw new RuntimeException('Изображение имеет недопустимые размеры.');
    return ['mime'=>$mime,'width'=>$width,'height'=>$height];
}
function normalize_jpeg_orientation($image,string $tmp){
    if(!function_exists('exif_read_data'))return $image;
    try{$exif=@exif_read_data($tmp);$orientation=(int)($exif['Orientation']??1);}catch(Throwable){$orientation=1;}
    return match($orientation){3=>imagerotate($image,180,0),6=>imagerotate($image,-90,0),8=>imagerotate($image,90,0),default=>$image};
}
function optimize_uploaded_image(string $tmp,string $relativeBase,int $maxWidth=1920,int $maxHeight=1920,int $quality=82): array {
    $info=uploaded_image_info($tmp);$mime=$info['mime'];$allowed=['image/jpeg','image/png','image/webp','image/gif'];
    if(!in_array($mime,$allowed,true))throw new RuntimeException('Поддерживаются JPG, PNG, WebP и GIF.');
    $relativeBase=preg_replace('/[^a-zA-Z0-9_\/-]/','-',$relativeBase)?:('images/'.bin2hex(random_bytes(8)));
    $targetDir=BASE_PATH.'/storage/uploads/'.dirname($relativeBase);if(!is_dir($targetDir)&&!mkdir($targetDir,0755,true)&&!is_dir($targetDir))throw new RuntimeException('Не удалось создать папку изображений.');
    $maxWidth=max(64,$maxWidth);$maxHeight=max(64,$maxHeight);$quality=max(55,min(92,$quality));
    if(extension_loaded('imagick')){
        try{
            $img=new Imagick($tmp);if(method_exists($img,'autoOrient'))$img->autoOrient();elseif(method_exists($img,'autoOrientImage'))$img->autoOrientImage();
            $img->stripImage();$img->thumbnailImage($maxWidth,$maxHeight,true,true);
            if($mime==='image/gif'&&$img->getNumberImages()>1){$ext='gif';$outMime='image/gif';$dest=BASE_PATH.'/storage/uploads/'.$relativeBase.'.gif';$img->setImageCompressionQuality($quality);$img->writeImages($dest,true);}
            else{$ext='webp';$outMime='image/webp';$dest=BASE_PATH.'/storage/uploads/'.$relativeBase.'.webp';$img->setImageFormat('webp');$img->setImageCompressionQuality($quality);$img->writeImage($dest);}
            $img->clear();$img->destroy();return ['relative'=>$relativeBase.'.'.$ext,'mime'=>$outMime,'size'=>(int)filesize($dest),'width'=>$info['width'],'height'=>$info['height']];
        }catch(Throwable $e){log_error($e);}
    }
    if(extension_loaded('gd')){
        $create=match($mime){'image/jpeg'=>'imagecreatefromjpeg','image/png'=>'imagecreatefrompng','image/webp'=>'imagecreatefromwebp','image/gif'=>'imagecreatefromgif',default=>null};
        if($create&&function_exists($create)){
            $src=@$create($tmp);if($src){
                if($mime==='image/jpeg')$src=normalize_jpeg_orientation($src,$tmp);
                $sw=imagesx($src);$sh=imagesy($src);$ratio=min(1,$maxWidth/$sw,$maxHeight/$sh);$tw=max(1,(int)round($sw*$ratio));$th=max(1,(int)round($sh*$ratio));
                $dst=imagecreatetruecolor($tw,$th);imagealphablending($dst,false);imagesavealpha($dst,true);$transparent=imagecolorallocatealpha($dst,0,0,0,127);imagefilledrectangle($dst,0,0,$tw,$th,$transparent);
                imagecopyresampled($dst,$src,0,0,0,0,$tw,$th,$sw,$sh);
                if(function_exists('imagewebp')){$ext='webp';$outMime='image/webp';$dest=BASE_PATH.'/storage/uploads/'.$relativeBase.'.webp';imagewebp($dst,$dest,$quality);}
                elseif($mime==='image/png'){$ext='png';$outMime='image/png';$dest=BASE_PATH.'/storage/uploads/'.$relativeBase.'.png';imagepng($dst,$dest,6);}
                else{$ext='jpg';$outMime='image/jpeg';$dest=BASE_PATH.'/storage/uploads/'.$relativeBase.'.jpg';imagejpeg($dst,$dest,$quality);}
                imagedestroy($src);imagedestroy($dst);if(!logo_image_has_content($dest)){@unlink($dest);throw new RuntimeException('Логотип после обработки оказался пустым.');}return ['relative'=>$relativeBase.'.'.$ext,'mime'=>$outMime,'size'=>(int)filesize($dest),'width'=>$tw,'height'=>$th];
            }
        }
    }
    $ext=match($mime){'image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp','image/gif'=>'gif',default=>'img'};
    $dest=BASE_PATH.'/storage/uploads/'.$relativeBase.'.'.$ext;
    if(!@move_uploaded_file($tmp,$dest)&&!@copy($tmp,$dest))throw new RuntimeException('Не удалось сохранить изображение.');
    return ['relative'=>$relativeBase.'.'.$ext,'mime'=>$mime,'size'=>(int)filesize($dest),'width'=>$info['width'],'height'=>$info['height']];
}
function logo_pixel_background(array $rgba,array $corner): bool {
    $alpha=(int)($rgba['alpha']??0);if($alpha>=118)return true;
    $r=(int)($rgba['red']??0);$g=(int)($rgba['green']??0);$b=(int)($rgba['blue']??0);
    $nearWhite=$r>=238&&$g>=238&&$b>=238&&(max($r,$g,$b)-min($r,$g,$b)<=20);if($nearWhite)return true;
    $cr=(int)($corner['red']??255);$cg=(int)($corner['green']??255);$cb=(int)($corner['blue']??255);$ca=(int)($corner['alpha']??0);
    return abs($r-$cr)<=34&&abs($g-$cg)<=34&&abs($b-$cb)<=34&&abs($alpha-$ca)<=14;
}
function logo_gd_bounds($src): array {
    $sw=imagesx($src);$sh=imagesy($src);$corner=imagecolorsforindex($src,imagecolorat($src,0,0));$left=$sw;$right=-1;$top=$sh;$bottom=-1;
    for($y=0;$y<$sh;$y++)for($x=0;$x<$sw;$x++){$rgba=imagecolorsforindex($src,imagecolorat($src,$x,$y));if(!logo_pixel_background($rgba,$corner)){$left=min($left,$x);$right=max($right,$x);$top=min($top,$y);$bottom=max($bottom,$y);}}
    if($right<0)return [0,0,$sw,$sh];
    $pad=max(0,(int)round(min($sw,$sh)*0.004));$left=max(0,$left-$pad);$top=max(0,$top-$pad);$right=min($sw-1,$right+$pad);$bottom=min($sh-1,$bottom+$pad);
    return [$left,$top,max(1,$right-$left+1),max(1,$bottom-$top+1)];
}
function logo_image_has_content(string $file): bool {
    if(!is_file($file)||(int)filesize($file)<64)return false;
    if(extension_loaded('imagick')){try{$img=new Imagick($file);$w=$img->getImageWidth();$h=$img->getImageHeight();if($w<8||$h<8){$img->clear();return false;}$img->setImageAlphaChannel(Imagick::ALPHACHANNEL_ACTIVATE);$stats=$img->getImageChannelStatistics();$alpha=$stats[Imagick::CHANNEL_ALPHA]??null;$img->clear();$img->destroy();if(!$alpha)return true;$quantum=(float)Imagick::getQuantum();return ((float)($alpha['mean']??$quantum)/max(1,$quantum))>0.01;}catch(Throwable){}}
    if(extension_loaded('gd')){try{$info=getimagesize($file);$mime=(string)($info['mime']??'');$create=match($mime){'image/png'=>'imagecreatefrompng','image/jpeg'=>'imagecreatefromjpeg','image/webp'=>'imagecreatefromwebp',default=>null};if($create&&function_exists($create)&&($img=@$create($file))){$w=imagesx($img);$h=imagesy($img);$visible=0;$samples=0;$step=max(1,(int)floor(max($w,$h)/120));for($y=0;$y<$h;$y+=$step)for($x=0;$x<$w;$x+=$step){$rgba=imagecolorsforindex($img,imagecolorat($img,$x,$y));$samples++;if((int)($rgba['alpha']??0)<118)$visible++;}imagedestroy($img);return $samples>0&&$visible/$samples>0.005;}}catch(Throwable){}}
    return true;
}
function brand_fallback_svg(): never {$file=BASE_PATH.'/assets/icons/icon.svg';if(is_file($file)){header('Content-Type: image/svg+xml; charset=utf-8');header('Content-Length: '.filesize($file));header('Cache-Control: public, max-age=86400');readfile($file);exit;}header('Content-Type: image/svg+xml; charset=utf-8');header('Cache-Control: no-cache');echo '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 96 96"><rect width="96" height="96" rx="22" fill="#447bba"/><text x="48" y="63" text-anchor="middle" font-family="Arial,sans-serif" font-size="52" font-weight="700" fill="white">K</text></svg>';exit;}

function logo_image_is_meaningful(string $file): bool {
    if(!logo_image_has_content($file))return false;
    if(!extension_loaded('gd'))return true;
    try{$info=getimagesize($file);$mime=(string)($info['mime']??'');$create=match($mime){'image/png'=>'imagecreatefrompng','image/jpeg'=>'imagecreatefromjpeg','image/webp'=>'imagecreatefromwebp',default=>null};if(!$create||!function_exists($create)||!($img=@$create($file)))return true;$w=imagesx($img);$h=imagesy($img);$step=max(1,(int)floor(max($w,$h)/90));$visible=0;$dark=0;$bright=0;$colors=[];for($y=0;$y<$h;$y+=$step)for($x=0;$x<$w;$x+=$step){$rgba=imagecolorsforindex($img,imagecolorat($img,$x,$y));if((int)($rgba['alpha']??0)>=118)continue;$visible++;$r=(int)$rgba['red'];$g=(int)$rgba['green'];$b=(int)$rgba['blue'];$lum=($r+$g+$b)/3;if($lum<70)$dark++;if($lum>180)$bright++;$colors[((int)($r/32)).'-'.((int)($g/32)).'-'.((int)($b/32))]=1;}imagedestroy($img);return $visible>=12&&count($colors)>=4&&($dark>0||$bright>0);}catch(Throwable){return true;}
}
function optimize_logo_image(string $tmp,string $relativeBase,int $maxWidth=1600,int $maxHeight=800,int $quality=90): array {
    $info=uploaded_image_info($tmp);$mime=$info['mime'];if(!in_array($mime,['image/jpeg','image/png','image/webp'],true))throw new RuntimeException('Для логотипа поддерживаются JPG, PNG и WebP.');
    $relativeBase=preg_replace('/[^a-zA-Z0-9_\/-]/','-',$relativeBase)?:('branding/logo-'.bin2hex(random_bytes(8)));
    $targetDir=BASE_PATH.'/storage/uploads/'.dirname($relativeBase);if(!is_dir($targetDir)&&!mkdir($targetDir,0755,true)&&!is_dir($targetDir))throw new RuntimeException('Не удалось создать папку логотипа.');
    $quality=max(70,min(94,$quality));
    if(extension_loaded('imagick')){
        try{
            $img=new Imagick($tmp);if(method_exists($img,'autoOrient'))$img->autoOrient();elseif(method_exists($img,'autoOrientImage'))$img->autoOrientImage();
            $quantum=(int)Imagick::getQuantum();$originalWidth=$img->getImageWidth();$originalHeight=$img->getImageHeight();
            $img->trimImage((int)($quantum*0.035));$img->setImagePage(0,0,0,0);$w=$img->getImageWidth();$h=$img->getImageHeight();
            if($w<16||$h<16||($w*$h)<max(256,(int)($originalWidth*$originalHeight*0.01)))throw new RuntimeException('Автоматическая обрезка логотипа дала пустое изображение.');
            $img->stripImage();$img->thumbnailImage($maxWidth,$maxHeight,true,true);$img->setImageFormat('webp');$img->setImageCompressionQuality($quality);$dest=BASE_PATH.'/storage/uploads/'.$relativeBase.'.webp';$img->writeImage($dest);$w=$img->getImageWidth();$h=$img->getImageHeight();$img->clear();$img->destroy();
            if(!logo_image_has_content($dest)){@unlink($dest);throw new RuntimeException('Логотип после обработки оказался прозрачным.');}
            return ['relative'=>$relativeBase.'.webp','mime'=>'image/webp','size'=>(int)filesize($dest),'width'=>$w,'height'=>$h];
        }catch(Throwable $e){log_error($e);}
    }
    if(extension_loaded('gd')){
        $create=match($mime){'image/jpeg'=>'imagecreatefromjpeg','image/png'=>'imagecreatefrompng','image/webp'=>'imagecreatefromwebp',default=>null};
        if($create&&function_exists($create)){$src=@$create($tmp);if($src){[$left,$top,$cw,$ch]=logo_gd_bounds($src);$ratio=min(1,$maxWidth/$cw,$maxHeight/$ch);$tw=max(1,(int)round($cw*$ratio));$th=max(1,(int)round($ch*$ratio));$dst=imagecreatetruecolor($tw,$th);imagealphablending($dst,false);imagesavealpha($dst,true);$transparent=imagecolorallocatealpha($dst,0,0,0,127);imagefilledrectangle($dst,0,0,$tw,$th,$transparent);imagecopyresampled($dst,$src,0,0,$left,$top,$tw,$th,$cw,$ch);if(function_exists('imagewebp')){$ext='webp';$outMime='image/webp';$dest=BASE_PATH.'/storage/uploads/'.$relativeBase.'.webp';imagewebp($dst,$dest,$quality);}else{$ext='png';$outMime='image/png';$dest=BASE_PATH.'/storage/uploads/'.$relativeBase.'.png';imagepng($dst,$dest,6);}imagedestroy($src);imagedestroy($dst);return ['relative'=>$relativeBase.'.'.$ext,'mime'=>$outMime,'size'=>(int)filesize($dest),'width'=>$tw,'height'=>$th];}}
    }
    return optimize_uploaded_image($tmp,$relativeBase,$maxWidth,$maxHeight,$quality);
}
function optimize_existing_image(string $source,string $relativeBase,int $maxWidth=1920,int $maxHeight=1920,int $quality=82): array {
    return optimize_uploaded_image($source,$relativeBase,$maxWidth,$maxHeight,$quality);
}


function save_wall_post_photos(array $files,int $postId,int $userId,int $startOrder=0): array {
    $stored=[];$count=min(10,count($files['name']??[]));
    for($i=0;$i<$count;$i++){
        if((int)($files['error'][$i]??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK)continue;
        if((int)($files['size'][$i]??0)>12*1024*1024)throw new RuntimeException('Фотография должна быть не больше 12 МБ.');
        $base='wall/'.$userId.'/'.date('Ymd').'-'.bin2hex(random_bytes(8));
        $result=optimize_uploaded_image((string)$files['tmp_name'][$i],$base,1920,1920,82);
        $stored[]=$result['relative'];
        $name=basename((string)($files['name'][$i]??'image'));
        DB::run('INSERT INTO profile_post_attachments (post_id,stored_path,mime_type,file_size,sort_order,original_name,created_at) VALUES (?,?,?,?,?,?,CURRENT_TIMESTAMP)',[$postId,$result['relative'],$result['mime'],$result['size'],$startOrder+$i,$name]);
    }
    return $stored;
}
function save_wall_post_videos(array $files,int $postId,int $userId,int $startOrder=50): array {
    $stored=[];$count=min(4,count($files['name']??[]));$max=min(100,(int)setting('max_upload_mb','25'))*1024*1024;
    for($i=0;$i<$count;$i++){
        if((int)($files['error'][$i]??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK)continue;
        if((int)($files['size'][$i]??0)>$max)throw new RuntimeException('Видео превышает допустимый размер.');
        $tmp=(string)($files['tmp_name'][$i]??'');$name=basename((string)($files['name'][$i]??'video.mp4'));$mime=(new finfo(FILEINFO_MIME_TYPE))->file($tmp)?:'application/octet-stream';
        if(!in_array($mime,['video/mp4','video/webm'],true))throw new RuntimeException('Для публикаций поддерживаются видео MP4 и WebM.');
        $ext=$mime==='video/webm'?'webm':'mp4';$relative='wall-video/'.$userId.'/'.date('Y/m').'/'.bin2hex(random_bytes(16)).'.'.$ext;$dest=BASE_PATH.'/storage/uploads/'.$relative;
        if(!is_dir(dirname($dest))&&!mkdir(dirname($dest),0755,true)&&!is_dir(dirname($dest)))throw new RuntimeException('Не удалось создать папку видео.');
        if(!move_uploaded_file($tmp,$dest)&&!copy($tmp,$dest))throw new RuntimeException('Не удалось сохранить видео.');
        $size=(int)filesize($dest);$stored[]=$relative;DB::run('INSERT INTO profile_post_attachments (post_id,stored_path,mime_type,file_size,sort_order,original_name,created_at) VALUES (?,?,?,?,?,?,CURRENT_TIMESTAMP)',[$postId,$relative,$mime,$size,$startOrder+$i,$name]);
    }
    return $stored;
}
function save_wall_post_documents(array $files,int $postId,int $userId,int $startOrder=100): array {
    $stored=[];$count=min(10,count($files['name']??[]));$max=(int)setting('max_upload_mb','25')*1024*1024;$allowed=array_filter(array_map('trim',explode(',',(string)setting('allowed_mimes','application/pdf,text/plain,application/zip,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'))));
    for($i=0;$i<$count;$i++){
        if((int)($files['error'][$i]??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK)continue;if((int)($files['size'][$i]??0)>$max)throw new RuntimeException('Документ превышает допустимый размер.');$tmp=(string)$files['tmp_name'][$i];$name=basename((string)($files['name'][$i]??'document'));$mime=(new finfo(FILEINFO_MIME_TYPE))->file($tmp)?:'application/octet-stream';if(str_starts_with($mime,'image/'))throw new RuntimeException('Изображения прикрепляйте через пункт «Изображение».');if(!in_array($mime,$allowed,true))throw new RuntimeException('Формат документа не разрешён администратором.');$ext=strtolower(pathinfo($name,PATHINFO_EXTENSION));if(preg_match('/^(php|phtml|phar|cgi|pl|sh|exe|js)$/',$ext))throw new RuntimeException('Этот тип файла запрещён.');$relative='wall-docs/'.$userId.'/'.date('Y/m').'/'.bin2hex(random_bytes(16)).($ext?'.'.$ext:'');$dest=BASE_PATH.'/storage/uploads/'.$relative;if(!is_dir(dirname($dest)))mkdir(dirname($dest),0755,true);if(!move_uploaded_file($tmp,$dest))throw new RuntimeException('Не удалось сохранить документ.');$size=(int)filesize($dest);$stored[]=$relative;DB::run('INSERT INTO profile_post_attachments (post_id,stored_path,mime_type,file_size,sort_order,original_name,created_at) VALUES (?,?,?,?,?,?,CURRENT_TIMESTAMP)',[$postId,$relative,$mime,$size,$startOrder+$i,$name]);
    }
    return $stored;
}
function delete_uploaded_relatives(array $paths): void { foreach($paths as $path)if(is_string($path)&&$path!==''&&!str_contains($path,'..'))@unlink(BASE_PATH.'/storage/uploads/'.$path); }



function avatar_url(int $userId,?string $avatarPath=null): string {
    if($avatarPath===null){try{$avatarPath=(string)(DB::one('SELECT avatar_path FROM users WHERE id=?',[$userId])['avatar_path']??'');}catch(Throwable){$avatarPath='';}}
    $url=app_url('/avatar/'.$userId);return $avatarPath!==''?$url.'?v='.substr(hash('sha256',$avatarPath),0,12):$url;
}
function media_url(int $attachmentId): string { return app_url('/media/'.$attachmentId); }
function user_public_url(string $username): string { return app_url('/@'.rawurlencode(ltrim($username,'@'))); }
function wall_post_public_url(string $username,int $postId): string { return app_url('/wall/@'.rawurlencode(ltrim($username,'@')).'/'.$postId); }
function message_public_url(int|array $message,?int $viewerId=null): string {
    $viewerId=$viewerId??(Auth::check()?Auth::id():0);
    $row=is_array($message)?$message:DB::one('SELECT m.id,m.chat_id,c.type,c.username FROM messages m JOIN chats c ON c.id=m.chat_id WHERE m.id=? LIMIT 1',[(int)$message]);
    if(!$row)return app_url('/messages');
    $messageId=(int)($row['id']??0);$chatId=(int)($row['chat_id']??0);$type=(string)($row['chat_type']??$row['type']??'direct');
    if($type==='channel'){
        $username=(string)($row['chat_username']??$row['username']??'');
        if($username==='')$username=(string)(DB::one('SELECT username FROM chats WHERE id=?',[$chatId])['username']??'');
        return $username!==''?app_url('/c/'.rawurlencode($username).'/m/'.$messageId):app_url('/messages/chat-'.$chatId.'/'.$messageId);
    }
    $other=DB::one('SELECT u.username FROM chat_members cm JOIN users u ON u.id=cm.user_id WHERE cm.chat_id=? AND cm.user_id<>? LIMIT 1',[$chatId,$viewerId]);
    $username=(string)($other['username']??'');
    return $username!==''?app_url('/messages/@'.rawurlencode($username).'/'.$messageId):app_url('/messages/chat-'.$chatId.'/'.$messageId);
}
function verified_badge(array $user,string $class='verified-badge'): string { return !empty($user['is_verified'])?'<span class="'.e($class).'" title="'.e((string)($user['verification_label']?:'Подтверждённый профиль')).'" aria-label="Подтверждённый профиль">✓</span>':''; }
function normalize_username(string $value): string { return strtolower(trim(ltrim($value,'@'))); }
function valid_username(string $value): bool { return preg_match('/^[a-z0-9_]{3,40}$/',$value)===1; }
function avatar_html(array $user,string $class='avatar',string $alt=''): string {
    $name=(string)($user['display_name']??trim(($user['first_name']??'').' '.($user['last_name']??'')));
    $alt=$alt?:($name?:'Аватар');
    $userId=(int)($user['user_id']??$user['sender_id']??$user['author_id']??$user['id']??0);
    $avatarPath=(string)($user['avatar_path']??'');
    $src=($userId>0&&$avatarPath!=='')?avatar_url($userId,$avatarPath):app_url('/assets/icons/default-avatar.svg?v='.APP_VERSION);
    return '<span class="'.e($class).' avatar-photo"><img src="'.e($src).'" alt="'.e($alt).'" loading="lazy" decoding="async"></span>';
}

function chat_member(int $chatId,int $userId=0): ?array { $userId=$userId?:Auth::id();return DB::one("SELECT cm.* FROM chat_members cm JOIN chats c ON c.id=cm.chat_id WHERE cm.chat_id=? AND cm.user_id=? AND c.type='direct' AND c.deleted_at IS NULL",[$chatId,$userId]); }
function require_chat_member(int $chatId): array { $member=chat_member($chatId);if(!$member||!empty($member['is_hidden']))abort(403,'Нет доступа к переписке.');return $member; }
function require_chat_admin(int $chatId): array { $member=chat_member($chatId);if(!$member||(!in_array($member['role']??'',['owner','admin'],true)&&empty($member['can_manage_settings'])&&!Auth::isAdmin()))abort(403,'Нужны права администратора канала.');return $member; }
function chat_title(array $chat,int $userId): string { if(($chat['type']??'')!=='direct')return $chat['title']?:'Канал';$other=DB::one('SELECT u.display_name FROM chat_members cm JOIN users u ON u.id=cm.user_id WHERE cm.chat_id=? AND cm.user_id<>? LIMIT 1',[$chat['id'],$userId]);return $other['display_name']??'Личная переписка'; }
function channel_public_url(array|string $channel): string { $username=is_array($channel)?(string)($channel['username']??''):(string)$channel;return $username!==''?app_url('/c/'.rawurlencode($username)):''; }
function channel_post_public_url(array $chat,int $messageId): string { $username=(string)($chat['username']??'');$chatId=(int)($chat['id']??$chat['chat_id']??0);return $username!==''?app_url('/c/'.rawurlencode($username).'/post/'.$messageId):app_url('/messages/chat-'.$chatId.'/post/'.$messageId); }
function channel_avatar_url(int $chatId): string { return app_url('/channel-avatar/'.$chatId); }
function chat_avatar_html(array $chat,?array $avatarUser=null,string $class='avatar'): string {
    if(($chat['type']??'')==='direct'&&$avatarUser)return avatar_html($avatarUser,$class);
    if(!empty($chat['avatar_path']))return '<span class="'.e($class).' avatar-photo"><img src="'.e(channel_avatar_url((int)$chat['id'])).'" alt="'.e((string)($chat['title']??'Канал')).'" loading="lazy"></span>';
    return '<span class="'.e($class).' channel-avatar">#</span>';
}
function channel_can_post(array $chat,array $membership): bool {
    if(($chat['type']??'')!=='channel')return true;
    return Auth::isAdmin()||($membership['role']??'')==='owner'||!empty($membership['can_post']);
}
function channel_can_manage(array $membership,string $permission='can_manage_settings'): bool {
    return Auth::isAdmin()||($membership['role']??'')==='owner'||(($membership['role']??'')==='admin'&&!empty($membership[$permission]));
}
function message_moderation_permissions(int $chatId): array { static $cache=[];if(isset($cache[$chatId]))return $cache[$chatId];if(Auth::isAdmin())return $cache[$chatId]=['edit'=>true,'delete'=>true];$chat=DB::one('SELECT type FROM chats WHERE id=?',[$chatId]);$member=chat_member($chatId);if(($chat['type']??'')!=='channel'||!$member)return $cache[$chatId]=['edit'=>false,'delete'=>false];return $cache[$chatId]=['edit'=>channel_can_manage($member,'can_edit_posts'),'delete'=>channel_can_manage($member,'can_delete_posts')];}

function chat_list_for_user(int $uid,bool $includeArchived=false): array {
    $archiveClause=$includeArchived?'':' AND cm.is_archived=0';
    $rows=DB::all("SELECT c.*,cm.last_read_message_id,cm.cleared_before_message_id,cm.is_muted,cm.is_pinned,cm.is_archived,cm.is_hidden,
        (SELECT MAX(id) FROM messages m WHERE m.chat_id=c.id AND m.deleted_at IS NULL AND m.reply_to_id IS NULL AND m.id>COALESCE(cm.cleared_before_message_id,0)) last_message_id,
        (SELECT body FROM messages m WHERE m.chat_id=c.id AND m.deleted_at IS NULL AND m.reply_to_id IS NULL AND m.id>COALESCE(cm.cleared_before_message_id,0) ORDER BY id DESC LIMIT 1) last_body,
        (SELECT type FROM messages m WHERE m.chat_id=c.id AND m.deleted_at IS NULL AND m.reply_to_id IS NULL AND m.id>COALESCE(cm.cleared_before_message_id,0) ORDER BY id DESC LIMIT 1) last_type,
        (SELECT created_at FROM messages m WHERE m.chat_id=c.id AND m.deleted_at IS NULL AND m.reply_to_id IS NULL AND m.id>COALESCE(cm.cleared_before_message_id,0) ORDER BY id DESC LIMIT 1) last_at,
        (SELECT COUNT(*) FROM messages m WHERE m.chat_id=c.id AND m.deleted_at IS NULL AND m.reply_to_id IS NULL AND m.id>GREATEST(COALESCE(cm.last_read_message_id,0),COALESCE(cm.cleared_before_message_id,0)) AND m.sender_id<>?) unread
        FROM chats c JOIN chat_members cm ON cm.chat_id=c.id
        WHERE cm.user_id=? AND cm.is_hidden=0 AND c.deleted_at IS NULL AND c.type='direct'".$archiveClause."
        ORDER BY cm.is_pinned DESC,last_at DESC,c.id DESC",[$uid,$uid]);
    foreach($rows as &$item){
        $item['display_title']=chat_title($item,$uid);$item['avatar_user']=null;
        if($item['type']==='direct')$item['avatar_user']=DB::one('SELECT u.id,u.display_name,u.username,u.avatar_path,u.is_verified,u.verification_label,u.last_seen_at FROM chat_members cm JOIN users u ON u.id=cm.user_id WHERE cm.chat_id=? AND cm.user_id<>? LIMIT 1',[$item['id'],$uid]);
        if(($item['last_type']??'')==='sticker')$item['last_body']='Стикер';
        if(($item['last_type']??'')==='file')$item['last_body']='Вложение';
    }
    unset($item);$rows=array_values(array_filter($rows,fn($item)=>($item['type']??'')!=='direct'||empty($item['avatar_user']['id'])||!users_blocked($uid,(int)$item['avatar_user']['id'])));return $rows;
}
function chat_unread_count(int $uid): int {
    try{
        return (int)(DB::one("SELECT COUNT(*) c FROM messages m JOIN chats c ON c.id=m.chat_id AND c.deleted_at IS NULL JOIN chat_members cm ON cm.chat_id=m.chat_id AND cm.user_id=? WHERE cm.is_hidden=0 AND m.deleted_at IS NULL AND m.reply_to_id IS NULL AND m.sender_id<>? AND m.id>GREATEST(COALESCE(cm.last_read_message_id,0),COALESCE(cm.cleared_before_message_id,0))",[$uid,$uid])['c']??0);
    }catch(Throwable $e){log_error($e);return 0;}
}
function load_chat_context(int $chatId,int $uid): array {
    $membership=require_chat_member($chatId);
    $chat=DB::one('SELECT * FROM chats WHERE id=? AND deleted_at IS NULL',[$chatId]);
    if(!$chat)abort(404,'Переписка не найдена.');
    if(($chat['type']??'')!=='direct')abort(404,'Каналы отключены. Доступны только личные сообщения.');
    $chat['display_title']=chat_title($chat,$uid);$chat['avatar_user']=null;$chat['other_online']=false;$chat['other_last_seen']=null;
    if($chat['type']==='direct'){
        $chat['avatar_user']=DB::one('SELECT u.id,u.display_name,u.username,u.avatar_path,u.is_verified,u.verification_label,u.last_seen_at FROM chat_members cm JOIN users u ON u.id=cm.user_id WHERE cm.chat_id=? AND cm.user_id<>? LIMIT 1',[$chatId,$uid]);
        $chat['other_online']=online($chat['avatar_user']['last_seen_at']??null);$chat['other_last_seen']=$chat['avatar_user']['last_seen_at']??null;
    }
    $clear=(int)($membership['cleared_before_message_id']??0);
    $rootClause=$chat['type']==='channel'?' AND reply_to_id IS NULL':'';
    $messages=DB::all("SELECT m.*,c.type chat_type,c.comments_enabled,c.reactions_enabled,c.sign_messages,u.display_name,u.first_name,u.last_name,u.username,u.avatar_path,u.is_verified,u.verification_label,fu.display_name forwarded_from_name,fu.username forwarded_from_username,fm.created_at forwarded_from_created_at,rm.body reply_body,ru.display_name reply_sender_name,a.id attachment_id,a.original_name,a.mime_type,a.file_size,(SELECT COUNT(*) FROM messages mc WHERE mc.chat_id=m.chat_id AND mc.thread_root_id=m.id AND mc.deleted_at IS NULL) comments_count FROM (SELECT * FROM messages WHERE chat_id=? AND deleted_at IS NULL AND id>?".$rootClause." ORDER BY id DESC LIMIT 80) m JOIN chats c ON c.id=m.chat_id JOIN users u ON u.id=m.sender_id LEFT JOIN messages fm ON fm.id=m.forwarded_from_id LEFT JOIN users fu ON fu.id=fm.sender_id LEFT JOIN messages rm ON rm.id=m.reply_to_id LEFT JOIN users ru ON ru.id=rm.sender_id LEFT JOIN attachments a ON a.message_id=m.id ORDER BY m.id ASC",[$chatId,$clear]);
    $members=DB::all('SELECT u.id,u.display_name,u.username,u.avatar_path,u.is_verified,u.verification_label,u.last_seen_at,cm.* FROM chat_members cm JOIN users u ON u.id=cm.user_id WHERE cm.chat_id=? ORDER BY FIELD(cm.role,\'owner\',\'admin\',\'member\'),u.display_name',[$chatId]);
    $last=(int)($messages?end($messages)['id']:0);if($last)DB::run('UPDATE chat_members SET last_read_message_id=GREATEST(COALESCE(last_read_message_id,0),?) WHERE chat_id=? AND user_id=?',[$last,$chatId,$uid]);
    $invites=$chat['type']==='channel'&&channel_can_manage($membership,'can_invite')?DB::all('SELECT * FROM channel_invite_links WHERE chat_id=? ORDER BY id DESC',[$chatId]):[];
    $joinRequests=$chat['type']==='channel'&&channel_can_manage($membership,'can_manage_members')?DB::all("SELECT r.*,u.display_name,u.username,u.avatar_path,u.is_verified FROM channel_join_requests r JOIN users u ON u.id=r.user_id WHERE r.chat_id=? AND r.status='pending' ORDER BY r.id DESC",[$chatId]):[];
    return compact('chat','membership','messages','members','invites','joinRequests');
}

function safe_message_html(string $text): string { $text=e($text);$text=preg_replace('~(https?://[^\s<]+)~u','<a href="$1" target="_blank" rel="noopener noreferrer">$1</a>',$text)??$text;return nl2br($text); }
function message_preview(array $message): string {
    if(($message['type']??'')==='sticker')return 'Стикер';
    if(($message['type']??'')==='file')return '📎 '.($message['original_name']??'Файл');
    if(($message['type']??'')==='track')return 'Аудиовложение недоступно';
    $body=trim(strip_tags((string)($message['body']??'')));
    return $body!==''?utf8_substr($body,0,140):'Новое сообщение';
}
function message_payload(array $message): array { return ['id'=>(int)$message['id'],'chat_id'=>(int)$message['chat_id'],'sender_id'=>(int)$message['sender_id'],'sender_name'=>$message['display_name']??'','sender_username'=>$message['username']??null,'sender_verified'=>!empty($message['is_verified']),'sender_verification_label'=>$message['verification_label']??null,'sender_avatar'=>avatar_url((int)$message['sender_id'],(string)($message['avatar_path']??'')),'type'=>$message['type'],'body'=>$message['body'],'sticker_code'=>$message['sticker_code'],'reply_to_id'=>$message['reply_to_id']?(int)$message['reply_to_id']:null,'reply_sender_name'=>$message['reply_sender_name']??null,'reply_body'=>$message['reply_body']??null,'forwarded_from_id'=>!empty($message['forwarded_from_id'])?(int)$message['forwarded_from_id']:null,'forwarded_from_name'=>$message['forwarded_from_name']??null,'created_at'=>$message['created_at'],'edited_at'=>$message['edited_at'],'deleted_at'=>$message['deleted_at'],'attachment_id'=>$message['attachment_id']?(int)$message['attachment_id']:null,'media_url'=>!empty($message['attachment_id'])?media_url((int)$message['attachment_id']):null,'file_name'=>$message['original_name']??null,'mime_type'=>$message['mime_type']??null,'file_size'=>$message['file_size']?(int)$message['file_size']:null]; }

function permission_defaults(string $role): array {
    $all=['can_send_messages'=>1,'can_send_files'=>1,'can_send_stickers'=>1,'can_send_voice'=>1,'can_edit_messages'=>1,'can_delete_messages'=>1,'can_publish_wall'=>1,'can_comment'=>1];
    if(in_array($role,['owner','admin'],true))return $all;
    if(in_array($role,['editor','moderator'],true))return $all;
    if($role==='guest')return ['can_send_messages'=>1,'can_send_files'=>0,'can_send_stickers'=>1,'can_send_voice'=>0,'can_edit_messages'=>1,'can_delete_messages'=>0,'can_publish_wall'=>0,'can_comment'=>1];
    return $all;
}
function permission_labels(): array { return ['can_send_messages'=>'Отправлять сообщения','can_send_files'=>'Отправлять документы и файлы','can_send_stickers'=>'Отправлять большие стикеры','can_send_voice'=>'Записывать голосовые сообщения','can_edit_messages'=>'Редактировать свои сообщения','can_delete_messages'=>'Удалять свои сообщения','can_publish_wall'=>'Публиковать записи на стене','can_comment'=>'Писать комментарии']; }
function role_catalog(): array {
    try{$rows=DB::all('SELECT * FROM roles ORDER BY sort_order,slug');if($rows)return $rows;}catch(Throwable){}
    return [['slug'=>'owner','name'=>'Владелец','is_system'=>1],['slug'=>'admin','name'=>'Администратор','is_system'=>1],['slug'=>'editor','name'=>'Редактор','is_system'=>1],['slug'=>'user','name'=>'Пользователь','is_system'=>1],['slug'=>'guest','name'=>'Гость','is_system'=>1]];
}
function role_permissions(string $role): array {
    $defaults=permission_defaults($role);
    if(in_array($role,['owner','admin'],true))return $defaults;
    try{$rows=DB::all('SELECT permission_key,is_allowed FROM role_permissions WHERE role_slug=?',[$role]);foreach($rows as $row)if(array_key_exists((string)$row['permission_key'],$defaults))$defaults[(string)$row['permission_key']]=(int)$row['is_allowed'];}catch(Throwable){}
    return $defaults;
}
function user_permissions(int $userId=0): array {
    $userId=$userId?:Auth::id();$user=$userId===Auth::id()?Auth::user():DB::one('SELECT role FROM users WHERE id=?',[$userId]);
    return role_permissions((string)($user['role']??'user'));
}
function can_do(string $permission,int $userId=0): bool { $permissions=user_permissions($userId);return !empty($permissions[$permission]); }
function save_role_permissions(string $role,array $input): void {
    if($role==='owner')return;foreach(permission_labels() as $key=>$label)DB::run('INSERT INTO role_permissions (role_slug,permission_key,is_allowed,updated_at) VALUES (?,?,?,CURRENT_TIMESTAMP) ON DUPLICATE KEY UPDATE is_allowed=VALUES(is_allowed),updated_at=CURRENT_TIMESTAMP',[$role,$key,!empty($input[$key])?1:0]);
}
function save_user_permissions(int $userId,array $input): void { /* 2.1: permissions are managed by role */ }


function emit_chat_event(int $chatId,string $type,?int $messageId=null,array $payload=[]): int {
    try{return DB::insert('INSERT INTO chat_events (chat_id,event_type,message_id,actor_id,payload_json,created_at) VALUES (?,?,?,?,?,CURRENT_TIMESTAMP)',[$chatId,$type,$messageId,Auth::id()?:null,json_encode($payload,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)]);}catch(Throwable $e){log_error($e);return 0;}
}

function message_row(int $messageId): ?array {
    $row=DB::one('SELECT m.*,c.type chat_type,c.comments_enabled,c.reactions_enabled,c.sign_messages,u.display_name,u.first_name,u.last_name,u.username,u.avatar_path,u.is_verified,u.verification_label,fu.display_name forwarded_from_name,fu.username forwarded_from_username,fm.created_at forwarded_from_created_at,rm.body reply_body,ru.display_name reply_sender_name,a.id attachment_id,a.original_name,a.stored_path,a.mime_type,a.file_size,(SELECT COUNT(*) FROM messages mc WHERE mc.chat_id=m.chat_id AND mc.thread_root_id=m.id AND mc.deleted_at IS NULL) comments_count FROM messages m JOIN chats c ON c.id=m.chat_id JOIN users u ON u.id=m.sender_id LEFT JOIN messages fm ON fm.id=m.forwarded_from_id LEFT JOIN users fu ON fu.id=fm.sender_id LEFT JOIN messages rm ON rm.id=m.reply_to_id LEFT JOIN users ru ON ru.id=rm.sender_id LEFT JOIN attachments a ON a.message_id=m.id WHERE m.id=?',[$messageId]);
    return $row;
}

function app_key_bytes(): string { $raw=(string)cfg('app.key','');$decoded=str_starts_with($raw,'base64:')?base64_decode(substr($raw,7),true):false;if($decoded===false||strlen($decoded)<32)throw new RuntimeException('Ключ приложения не настроен.');return hash('sha256',$decoded,true); }
function encrypt_secret(string $plain): string { $iv=random_bytes(12);$tag='';$cipher=openssl_encrypt($plain,'aes-256-gcm',app_key_bytes(),OPENSSL_RAW_DATA,$iv,$tag);if($cipher===false)throw new RuntimeException('Не удалось зашифровать секрет.');return base64_encode($iv.$tag.$cipher); }
function decrypt_secret(string $encoded): string { $raw=base64_decode($encoded,true);if($raw===false||strlen($raw)<29)throw new RuntimeException('Повреждён секрет Webhook.');$iv=substr($raw,0,12);$tag=substr($raw,12,16);$cipher=substr($raw,28);$plain=openssl_decrypt($cipher,'aes-256-gcm',app_key_bytes(),OPENSSL_RAW_DATA,$iv,$tag);if($plain===false)throw new RuntimeException('Не удалось расшифровать секрет Webhook.');return $plain; }
function set_secret_setting(string $key,string $plain): void { if($plain===''){set_setting($key,'');return;}set_setting($key,'enc:'.encrypt_secret($plain)); }
function secret_setting(string $key,string $default=''): string { $value=(string)setting($key,'');if($value==='')return $default;if(str_starts_with($value,'enc:')){try{return decrypt_secret(substr($value,4));}catch(Throwable){return $default;}}return $value; }
function webhook_target(string $url): ?array {
    if(!filter_var($url,FILTER_VALIDATE_URL))return null;
    $parts=parse_url($url);if(!is_array($parts))return null;
    $scheme=strtolower((string)($parts['scheme']??''));
    $host=strtolower((string)($parts['host']??''));
    if(!in_array($scheme,['http','https'],true)||$host===''||isset($parts['user'])||isset($parts['pass'])||isset($parts['fragment']))return null;
    if($host==='localhost'||str_ends_with($host,'.local'))return null;
    $port=(int)($parts['port']??($scheme==='https'?443:80));if(!in_array($port,[80,443],true))return null;
    $public=static fn(string $ip):bool=>filter_var($ip,FILTER_VALIDATE_IP,FILTER_FLAG_NO_PRIV_RANGE|FILTER_FLAG_NO_RES_RANGE)!==false;
    $ips=[];
    if(filter_var($host,FILTER_VALIDATE_IP)){$ips[]=$host;}
    else{
        $records=@dns_get_record($host,DNS_A|DNS_AAAA)?:[];
        foreach($records as $record){$ip=(string)($record['ip']??$record['ipv6']??'');if($ip!=='')$ips[]=$ip;}
    }
    $ips=array_values(array_unique($ips));if(!$ips)return null;
    foreach($ips as $ip)if(!$public($ip))return null;
    return ['url'=>$url,'scheme'=>$scheme,'host'=>$host,'port'=>$port,'ips'=>$ips];
}
function webhook_url_allowed(string $url): bool { return webhook_target($url)!==null; }
function queue_webhook(string $event,array $payload): void { try{$hooks=DB::all('SELECT id,events FROM webhooks WHERE is_active=1');foreach($hooks as $hook){$events=array_filter(array_map('trim',explode(',',(string)$hook['events'])));if($events&&!in_array('*',$events,true)&&!in_array($event,$events,true))continue;DB::run("INSERT INTO webhook_deliveries (webhook_id,event,payload_json,status,attempts,next_attempt_at,created_at) VALUES (?,?,?,'pending',0,CURRENT_TIMESTAMP,CURRENT_TIMESTAMP)",[$hook['id'],$event,json_encode(['event'=>$event,'created_at'=>date('c'),'data'=>$payload],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)]);}}catch(Throwable $e){log_error($e);} }
function deliver_webhook(array $delivery): array {
    $hook=DB::one('SELECT * FROM webhooks WHERE id=? AND is_active=1',[(int)$delivery['webhook_id']]);
    if(!$hook)return ['ok'=>false,'code'=>0,'error'=>'Webhook disabled or missing'];
    $target=webhook_target((string)$hook['url']);if(!$target)return ['ok'=>false,'code'=>0,'error'=>'Webhook target is not a public HTTP(S) address'];
    if(!function_exists('curl_init'))return ['ok'=>false,'code'=>0,'error'=>'PHP cURL is required for secure Webhook delivery'];
    $body=(string)$delivery['payload_json'];$secret=decrypt_secret((string)$hook['secret_encrypted']);$signature='sha256='.hash_hmac('sha256',$body,$secret);
    $headers=['Content-Type: application/json','User-Agent: KOVCHEG-CMS/'.APP_VERSION,'X-Kovcheg-Event: '.$delivery['event'],'X-Kovcheg-Delivery: '.$delivery['id'],'X-Kovcheg-Signature: '.$signature];
    $ip=(string)$target['ips'][0];$resolvedIp=str_contains($ip,':')?'['.$ip.']':$ip;
    $options=[CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>$body,CURLOPT_HTTPHEADER=>$headers,CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>12,CURLOPT_CONNECTTIMEOUT=>5,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_MAXREDIRS=>0,CURLOPT_RESOLVE=>[$target['host'].':'.$target['port'].':'.$resolvedIp]];
    if(defined('CURLOPT_PROTOCOLS'))$options[CURLOPT_PROTOCOLS]=CURLPROTO_HTTP|CURLPROTO_HTTPS;
    if(defined('CURLOPT_REDIR_PROTOCOLS'))$options[CURLOPT_REDIR_PROTOCOLS]=CURLPROTO_HTTP|CURLPROTO_HTTPS;
    $ch=curl_init((string)$target['url']);curl_setopt_array($ch,$options);$response=curl_exec($ch);$code=(int)curl_getinfo($ch,CURLINFO_RESPONSE_CODE);$error=curl_error($ch);curl_close($ch);
    return ['ok'=>$code>=200&&$code<300,'code'=>$code,'error'=>$error?:substr((string)$response,0,500)];
}

function b64url_encode(string $value): string { return rtrim(strtr(base64_encode($value),'+/','-_'),'='); }
function b64url_decode(string $value): string|false { $pad=strlen($value)%4;if($pad)$value.=str_repeat('=',4-$pad);return base64_decode(strtr($value,'-_','+/'),true); }
function generate_vapid_keypair(): array {
    if(!extension_loaded('openssl'))throw new RuntimeException('Для Web Push требуется OpenSSL.');
    $key=openssl_pkey_new(['private_key_type'=>OPENSSL_KEYTYPE_EC,'curve_name'=>'prime256v1']);
    if($key===false)throw new RuntimeException('Не удалось создать ключ Web Push.');
    $details=openssl_pkey_get_details($key);$pem='';
    if(!$details||empty($details['ec']['x'])||empty($details['ec']['y'])||!openssl_pkey_export($key,$pem))throw new RuntimeException('Не удалось экспортировать ключ Web Push.');
    return ['public'=>b64url_encode("\x04".$details['ec']['x'].$details['ec']['y']),'private'=>encrypt_secret($pem)];
}
function ensure_vapid_settings(): array {
    $public=(string)(DB::one('SELECT `value` FROM settings WHERE `key`=?',['push_vapid_public_key'])['value']??'');
    $private=(string)(DB::one('SELECT `value` FROM settings WHERE `key`=?',['push_vapid_private_key'])['value']??'');
    $subject=(string)(DB::one('SELECT `value` FROM settings WHERE `key`=?',['push_vapid_subject'])['value']??'');
    if($public===''||$private===''){$pair=generate_vapid_keypair();$public=$pair['public'];$private=$pair['private'];set_setting('push_vapid_public_key',$public);set_setting('push_vapid_private_key',$private);}
    if($subject===''){$subject=rtrim(app_url('/'),'/');set_setting('push_vapid_subject',$subject);}
    return compact('public','private','subject');
}
function der_read_length(string $der,int &$offset): int {
    if($offset>=strlen($der))throw new RuntimeException('Повреждена ECDSA-подпись.');$length=ord($der[$offset++]);
    if(($length&0x80)===0)return $length;$count=$length&0x7f;if($count<1||$count>4||$offset+$count>strlen($der))throw new RuntimeException('Повреждена длина ECDSA-подписи.');
    $length=0;for($i=0;$i<$count;$i++)$length=($length<<8)|ord($der[$offset++]);return $length;
}
function ecdsa_der_to_jose(string $der,int $partLength=32): string {
    $offset=0;if(ord($der[$offset++]??"\0")!==0x30)throw new RuntimeException('Некорректная ECDSA-подпись.');der_read_length($der,$offset);
    $parts=[];for($n=0;$n<2;$n++){if(ord($der[$offset++]??"\0")!==0x02)throw new RuntimeException('Некорректная ECDSA-подпись.');$len=der_read_length($der,$offset);$part=substr($der,$offset,$len);$offset+=$len;$part=ltrim($part,"\0");if(strlen($part)>$partLength)$part=substr($part,-$partLength);$parts[]=str_pad($part,$partLength,"\0",STR_PAD_LEFT);}return $parts[0].$parts[1];
}
function vapid_jwt(string $endpoint): array {
    $keys=ensure_vapid_settings();$parts=parse_url($endpoint);$scheme=strtolower((string)($parts['scheme']??''));$host=(string)($parts['host']??'');if(!in_array($scheme,['https'],true)||$host==='')throw new RuntimeException('Некорректная точка Web Push.');
    $port=isset($parts['port'])?':'.(int)$parts['port']:'';$aud=$scheme.'://'.$host.$port;$header=b64url_encode(json_encode(['typ'=>'JWT','alg'=>'ES256'],JSON_UNESCAPED_SLASHES));$claims=b64url_encode(json_encode(['aud'=>$aud,'exp'=>time()+43200,'sub'=>$keys['subject']],JSON_UNESCAPED_SLASHES));$data=$header.'.'.$claims;$private=openssl_pkey_get_private(decrypt_secret($keys['private']));if($private===false)throw new RuntimeException('Не удалось открыть ключ Web Push.');$signature='';if(!openssl_sign($data,$signature,$private,OPENSSL_ALGO_SHA256))throw new RuntimeException('Не удалось подписать Web Push.');return ['token'=>$data.'.'.b64url_encode(ecdsa_der_to_jose($signature)),'public'=>$keys['public']];
}

function channel_comment_tree(array $rows,int $rootId): array {
    $groups=[];
    foreach($rows as $row){$parent=(int)($row['reply_to_id']??$rootId);$groups[$parent][]=$row;}
    $build=function(int $parent,int $depth=0)use(&$build,&$groups):array{
        if($depth>30)return [];$out=[];
        foreach($groups[$parent]??[] as $row){$row['replies']=$build((int)$row['id'],$depth+1);$out[]=$row;}
        return $out;
    };
    return $build($rootId);
}


/* KOVCHEG CMS runtime services. */
function comment_reaction_context(string $context): string {
    return in_array($context,['wall','avatar','channel'],true)?$context:'';
}
function comment_reaction_summary(string $context,int $commentId,int $viewerId=0): array {
    $context=comment_reaction_context($context);$viewerId=$viewerId?:Auth::id();
    if($context===''||$commentId<1)return ['items'=>[],'mine'=>null,'total'=>0];
    try{
        $items=DB::all('SELECT emoji,COUNT(*) count,MAX(CASE WHEN user_id=? THEN 1 ELSE 0 END) mine FROM comment_reactions WHERE context_type=? AND comment_id=? GROUP BY emoji ORDER BY COUNT(*) DESC,MIN(created_at)',[$viewerId,$context,$commentId]);
        $mine=null;$total=0;foreach($items as $item){$total+=(int)$item['count'];if(!empty($item['mine']))$mine=(string)$item['emoji'];}
        return ['items'=>$items,'mine'=>$mine,'total'=>$total];
    }catch(Throwable){return ['items'=>[],'mine'=>null,'total'=>0];}
}
function comment_reaction_target(string $context,int $commentId): ?array {
    $context=comment_reaction_context($context);if($context===''||$commentId<1)return null;
    if($context==='wall'){
        $row=DB::one('SELECT c.id,c.user_id,c.post_id,p.user_id profile_user_id,p.author_id,p.visibility,p.status,p.publish_at,p.deleted_at FROM profile_post_comments c JOIN profile_posts p ON p.id=c.post_id WHERE c.id=? AND c.deleted_at IS NULL',[$commentId]);
        if(!$row||!profile_post_can_view($row,Auth::id()))return null;
        return $row+['context_type'=>'wall'];
    }
    if($context==='avatar'){
        $row=DB::one('SELECT c.id,c.user_id,c.profile_user_id FROM avatar_comments c WHERE c.id=? AND c.deleted_at IS NULL',[$commentId]);
        if(!$row||!can_view_profile(['id'=>(int)$row['profile_user_id']],Auth::id()))return null;
        return $row+['context_type'=>'avatar'];
    }
    $row=DB::one("SELECT m.id,m.sender_id user_id,m.chat_id,m.thread_root_id,c.visibility,c.deleted_at chat_deleted_at FROM messages m JOIN chats c ON c.id=m.chat_id WHERE m.id=? AND m.thread_root_id IS NOT NULL AND m.deleted_at IS NULL AND c.type='channel' LIMIT 1",[$commentId]);
    if(!$row||!empty($row['chat_deleted_at'])||!chat_member((int)$row['chat_id']))return null;
    return $row+['context_type'=>'channel'];
}
function password_hash_secure(string $password): string {
    $algo=defined('PASSWORD_ARGON2ID')?PASSWORD_ARGON2ID:PASSWORD_DEFAULT;
    $options=$algo===PASSWORD_ARGON2ID?['memory_cost'=>65536,'time_cost'=>4,'threads'=>2]:[];
    $hash=password_hash($password,$algo,$options);if($hash===false)throw new RuntimeException('Не удалось защитить пароль.');return $hash;
}
function auth_rate_key(string $login): string { return hash('sha256',mb_lower(trim($login)).'|'.(string)($_SERVER['REMOTE_ADDR']??'unknown').'|'.(string)cfg('app.key','')); }
function auth_rate_check(string $login): void {
    try{$row=DB::one('SELECT attempts,window_started_at,blocked_until FROM auth_rate_limits WHERE rate_key=?',[auth_rate_key($login)]);if(!$row)return;if(!empty($row['blocked_until'])&&strtotime((string)$row['blocked_until'])>time())abort(429,'Слишком много попыток входа. Повторите позже.');if(strtotime((string)$row['window_started_at'])<time()-900)DB::run('DELETE FROM auth_rate_limits WHERE rate_key=?',[auth_rate_key($login)]);}catch(Throwable){}
}
function auth_rate_fail(string $login): void {
    try{$key=auth_rate_key($login);$row=DB::one('SELECT attempts,window_started_at FROM auth_rate_limits WHERE rate_key=?',[$key]);$attempts=1;$start=date('Y-m-d H:i:s');if($row&&strtotime((string)$row['window_started_at'])>=time()-900){$attempts=(int)$row['attempts']+1;$start=(string)$row['window_started_at'];}$blocked=$attempts>=7?date('Y-m-d H:i:s',time()+900):null;DB::run('INSERT INTO auth_rate_limits (rate_key,attempts,window_started_at,blocked_until,updated_at) VALUES (?,?,?,?,CURRENT_TIMESTAMP) ON DUPLICATE KEY UPDATE attempts=VALUES(attempts),window_started_at=VALUES(window_started_at),blocked_until=VALUES(blocked_until),updated_at=CURRENT_TIMESTAMP',[$key,$attempts,$start,$blocked]);}catch(Throwable){}
}
function auth_rate_success(string $login): void { try{DB::run('DELETE FROM auth_rate_limits WHERE rate_key=?',[auth_rate_key($login)]);}catch(Throwable){} }
function current_absolute_url(): string {
    $scheme=(!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off')?'https':'http';
    $host=(string)($_SERVER['HTTP_HOST']??parse_url(app_url('/'),PHP_URL_HOST)??'localhost');
    $path=(string)(parse_url((string)($_SERVER['REQUEST_URI']??'/'),PHP_URL_PATH)?:'/');
    return $scheme.'://'.$host.$path;
}
function cleanup_runtime_garbage(int $days=14): array {
    $days=max(1,min(365,$days));$cut=time()-$days*86400;$removed=0;$bytes=0;
    $dirs=[BASE_PATH.'/storage/cache',BASE_PATH.'/storage/tmp'];
    foreach($dirs as $dir){if(!is_dir($dir))continue;$it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir,FilesystemIterator::SKIP_DOTS),RecursiveIteratorIterator::CHILD_FIRST);foreach($it as $item){$path=$item->getPathname();if($item->isFile()&&!$item->isLink()&&$item->getMTime()<$cut){$bytes+=$item->getSize();if(@unlink($path))$removed++;}elseif($item->isDir())@rmdir($path);}}
    return compact('removed','bytes');
}

function send_web_push_wakeup(array $subscription,int $timeout=6): array {
    $endpoint=(string)($subscription['endpoint']??'');if($endpoint==='')return ['ok'=>false,'code'=>0,'error'=>'Empty endpoint'];$vapid=vapid_jwt($endpoint);$headers=['TTL: 86400','Urgency: normal','Content-Length: 0','Authorization: vapid t='.$vapid['token'].', k='.$vapid['public'],'User-Agent: KOVCHEG-CMS/'.APP_VERSION];
    if(function_exists('curl_init')){$ch=curl_init($endpoint);curl_setopt_array($ch,[CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>'',CURLOPT_HTTPHEADER=>$headers,CURLOPT_RETURNTRANSFER=>true,CURLOPT_HEADER=>false,CURLOPT_TIMEOUT=>$timeout,CURLOPT_CONNECTTIMEOUT=>min(3,$timeout),CURLOPT_FOLLOWLOCATION=>false]);$response=curl_exec($ch);$code=(int)curl_getinfo($ch,CURLINFO_RESPONSE_CODE);$error=curl_error($ch);curl_close($ch);return ['ok'=>$code>=200&&$code<300,'code'=>$code,'error'=>$error?:substr((string)$response,0,500)];}
    $context=stream_context_create(['http'=>['method'=>'POST','header'=>implode("\r\n",$headers),'content'=>'','timeout'=>$timeout,'ignore_errors'=>true]]);$response=@file_get_contents($endpoint,false,$context);$line=$http_response_header[0]??'';preg_match('/\s(\d{3})\s/',$line,$m);$code=(int)($m[1]??0);return ['ok'=>$code>=200&&$code<300,'code'=>$code,'error'=>$response===false?'HTTP request failed':substr((string)$response,0,500)];
}
function raw_user_setting(int $userId,string $key,string $default=''): string { try{return (string)(DB::one('SELECT `value` FROM user_settings WHERE user_id=? AND `key`=?',[$userId,$key])['value']??$default);}catch(Throwable){return $default;} }
function notification_type_from_tag(string $tag): string { if(str_starts_with($tag,'chat-'))return 'message';if(str_starts_with($tag,'admin-')||str_starts_with($tag,'registration-'))return 'system';if(str_starts_with($tag,'colleague-')||str_starts_with($tag,'social-'))return 'social';if(str_starts_with($tag,'wall-'))return 'wall';if(str_starts_with($tag,'channel-'))return 'channel';return 'info'; }
function user_notification_create(int $userId,string $title,string $body,string $url='',string $icon='',string $tag='',string $type='info'): int {
    try{return DB::insert("INSERT INTO user_notifications (user_id,type,title,body,url,icon,tag,is_read,created_at) VALUES (?,?,?,?,?,?,?,0,CURRENT_TIMESTAMP)",[$userId,$type,utf8_substr($title,0,190),$body,$url?:null,$icon?:null,$tag?:null]);}catch(Throwable $e){log_error($e);return 0;}
}
function user_can_see_core_updates(int $userId): bool { try{$role=(string)(DB::one('SELECT role FROM users WHERE id=? LIMIT 1',[$userId])['role']??'user');return in_array($role,['owner','admin'],true);}catch(Throwable){return false;} }
function user_notifications(int $userId,int $limit=40): array { try{$filter=user_can_see_core_updates($userId)?'':" AND (tag IS NULL OR tag NOT LIKE 'core-update-%')";return DB::all('SELECT * FROM user_notifications WHERE user_id=?'.$filter.' ORDER BY id DESC LIMIT '.max(1,min(100,$limit)),[$userId]);}catch(Throwable){return [];} }
function user_unread_count(int $userId): int { try{$filter=user_can_see_core_updates($userId)?'':" AND (tag IS NULL OR tag NOT LIKE 'core-update-%')";return (int)(DB::one('SELECT COUNT(*) c FROM user_notifications WHERE user_id=? AND is_read=0'.$filter,[$userId])['c']??0);}catch(Throwable){return 0;} }
function mark_user_notifications_read(int $userId,array $ids=[]): void { if(!$ids){DB::run('UPDATE user_notifications SET is_read=1,read_at=COALESCE(read_at,CURRENT_TIMESTAMP) WHERE user_id=? AND is_read=0',[$userId]);return;}$ids=array_values(array_unique(array_filter(array_map('intval',$ids))));if(!$ids)return;$marks=implode(',',array_fill(0,count($ids),'?'));DB::run('UPDATE user_notifications SET is_read=1,read_at=COALESCE(read_at,CURRENT_TIMESTAMP) WHERE user_id=? AND id IN ('.$marks.')',array_merge([$userId],$ids)); }
function queue_user_push(int $userId,string $title,string $body,string $url='',string $icon='',string $tag='',bool $force=false,bool $createBell=true): int {
    try{
        if($createBell)user_notification_create($userId,$title,$body,$url,$icon,$tag,notification_type_from_tag($tag));
        if(!$force&&raw_user_setting($userId,'desktop_notifications','0')!=='1')return 0;
        $user=DB::one("SELECT id,last_seen_at FROM users WHERE id=? AND is_active=1 AND approval_status='approved'",[$userId]);if(!$user)return 0;
        if(!DB::one('SELECT id FROM push_subscriptions WHERE user_id=? AND is_active=1 LIMIT 1',[$userId]))return 0;
        return DB::insert("INSERT INTO push_deliveries (user_id,title,body,url,icon,tag,status,attempts,next_attempt_at,created_at) VALUES (?,?,?,?,?,?,'pending',0,CURRENT_TIMESTAMP,CURRENT_TIMESTAMP)",[$userId,utf8_substr($title,0,190),$body,$url?:null,$icon?:null,$tag?:null]);
    }catch(Throwable $e){log_error($e);return 0;}
}

function queue_message_push(int $messageId): int {
    try{$m=message_row($messageId);if(!$m||!empty($m['deleted_at']))return 0;$sender=(int)$m['sender_id'];$chatId=(int)$m['chat_id'];$chat=DB::one('SELECT * FROM chats WHERE id=?',[$chatId]);if(!$chat)return 0;$recipients=DB::all("SELECT cm.user_id FROM chat_members cm JOIN users u ON u.id=cm.user_id WHERE cm.chat_id=? AND cm.user_id<>? AND cm.is_hidden=0 AND cm.is_muted=0 AND u.is_active=1 AND u.approval_status='approved'",[$chatId,$sender]);$count=0;$delay=max(10,min(600,(int)setting('message_bell_delay_seconds','30')));foreach($recipients as $recipient){$uid=(int)$recipient['user_id'];$mode=raw_user_setting($uid,'notification_preview','full');$showAvatar=raw_user_setting($uid,'notification_avatar','1')==='1';$preview=message_preview($m);if($mode==='full'){$title=(string)$m['display_name'];$body=$preview;}elseif($mode==='sender'){$title=(string)$m['display_name'];$body='Новое сообщение';}elseif($mode==='count'){$title='Новое сообщение';$body='Откройте KOVCHEG CMS';}else{$title='KOVCHEG CMS';$body='Получено новое сообщение';}$url=message_public_url(['id'=>$messageId,'chat_id'=>$chatId,'chat_type'=>(string)($chat['type']??'direct'),'chat_username'=>(string)($chat['username']??'')],$uid);$icon=$showAvatar?avatar_url($sender,(string)($m['avatar_path']??'')):'';$count+=queue_user_push($uid,$title,$body,$url,$icon,'chat-'.$chatId,false,false)?1:0;try{DB::run("INSERT INTO message_notification_queue (message_id,user_id,due_at,created_at) VALUES (?,?,DATE_ADD(CURRENT_TIMESTAMP,INTERVAL $delay SECOND),CURRENT_TIMESTAMP) ON DUPLICATE KEY UPDATE due_at=VALUES(due_at),cancelled_at=NULL",[$messageId,$uid]);}catch(Throwable $e){log_error($e);}}return $count;}catch(Throwable $e){log_error($e);return 0;}
}
function process_delayed_message_notifications(int $limit=100): array {
    $created=0;$cancelled=0;try{$rows=DB::all("SELECT q.*,m.chat_id,m.sender_id,m.body,m.type,m.sticker_code,m.deleted_at,u.display_name,u.avatar_path FROM message_notification_queue q JOIN messages m ON m.id=q.message_id JOIN users u ON u.id=m.sender_id WHERE q.delivered_at IS NULL AND q.cancelled_at IS NULL AND q.due_at<=CURRENT_TIMESTAMP ORDER BY q.id LIMIT ".max(1,min(500,$limit)));}catch(Throwable $e){log_error($e);return compact('created','cancelled');}
    foreach($rows as $row){try{$member=DB::one('SELECT last_read_message_id,cleared_before_message_id,is_hidden FROM chat_members WHERE chat_id=? AND user_id=?',[$row['chat_id'],$row['user_id']]);$read=!$member||!empty($member['is_hidden'])||!empty($row['deleted_at'])||max((int)($member['last_read_message_id']??0),(int)($member['cleared_before_message_id']??0))>=(int)$row['message_id'];if($read){DB::run('UPDATE message_notification_queue SET cancelled_at=CURRENT_TIMESTAMP WHERE id=?',[$row['id']]);$cancelled++;continue;}$mode=raw_user_setting((int)$row['user_id'],'notification_preview','full');$title=$mode==='hidden'?'KOVCHEG CMS':($mode==='count'?'Новое сообщение':(string)$row['display_name']);$body=$mode==='full'?message_preview($row):($mode==='sender'?'Новое сообщение':($mode==='count'?'Откройте KOVCHEG CMS':'Получено новое сообщение'));$chatRow=DB::one('SELECT type,username FROM chats WHERE id=?',[(int)$row['chat_id']])??[];$url=message_public_url(['id'=>(int)$row['message_id'],'chat_id'=>(int)$row['chat_id'],'chat_type'=>(string)($chatRow['type']??'direct'),'chat_username'=>(string)($chatRow['username']??'')],(int)$row['user_id']);$icon=raw_user_setting((int)$row['user_id'],'notification_avatar','1')==='1'?avatar_url((int)$row['sender_id'],(string)($row['avatar_path']??'')):'';user_notification_create((int)$row['user_id'],$title,$body,$url,$icon,'chat-'.(int)$row['chat_id'],'message');DB::run('UPDATE message_notification_queue SET delivered_at=CURRENT_TIMESTAMP WHERE id=?',[$row['id']]);$created++;}catch(Throwable $e){log_error($e);}}
    return compact('created','cancelled');
}
function process_push_queue(int $limit=30,int $timeout=6): array {
    $sent=0;$failed=0;$invalid=0;try{$rows=DB::all("SELECT * FROM push_deliveries WHERE status IN ('pending','failed') AND attempts<6 AND (next_attempt_at IS NULL OR next_attempt_at<=CURRENT_TIMESTAMP) ORDER BY id ASC LIMIT ".max(1,min(100,$limit)));}catch(Throwable $e){log_error($e);return compact('sent','failed','invalid');}
    foreach($rows as $delivery){$id=(int)$delivery['id'];try{DB::run("UPDATE push_deliveries SET status='sending',attempts=attempts+1 WHERE id=?",[$id]);$subscriptions=DB::all('SELECT * FROM push_subscriptions WHERE user_id=? AND is_active=1',[(int)$delivery['user_id']]);if(!$subscriptions){DB::run("UPDATE push_deliveries SET status='failed',last_error='Нет активной подписки',next_attempt_at=NULL WHERE id=?",[$id]);$failed++;continue;}$ok=false;$errors=[];foreach($subscriptions as $subscription){$result=send_web_push_wakeup($subscription,$timeout);if($result['ok']){$ok=true;continue;}$code=(int)$result['code'];$errors[]='HTTP '.$code.' '.$result['error'];if(in_array($code,[404,410],true)){DB::run('UPDATE push_subscriptions SET is_active=0,updated_at=CURRENT_TIMESTAMP WHERE id=?',[$subscription['id']]);$invalid++;}}if($ok){DB::run("UPDATE push_deliveries SET status='delivered',last_error=NULL,delivered_at=CURRENT_TIMESTAMP,next_attempt_at=NULL WHERE id=?",[$id]);$sent++;}else{$attempt=(int)$delivery['attempts']+1;$delay=min(3600,60*(2**max(0,$attempt-1)));$next=$attempt>=6?null:date('Y-m-d H:i:s',time()+$delay);DB::run("UPDATE push_deliveries SET status='failed',last_error=?,next_attempt_at=? WHERE id=?",[utf8_substr(implode(' | ',$errors),0,2000),$next,$id]);$failed++;}}catch(Throwable $e){log_error($e);try{DB::run("UPDATE push_deliveries SET status='failed',last_error=?,next_attempt_at=DATE_ADD(CURRENT_TIMESTAMP,INTERVAL 5 MINUTE) WHERE id=?",[utf8_substr($e->getMessage(),0,1800),$id]);}catch(Throwable){}$failed++;}}
    return compact('sent','failed','invalid');
}

function admin_notify(string $type,string $title,string $body='',string $url='',?int $actorId=null): int {
    try{$id=DB::insert('INSERT INTO admin_notifications (type,title,body,url,actor_id,created_at) VALUES (?,?,?,?,?,CURRENT_TIMESTAMP)',[$type,$title,$body,$url?:null,$actorId]);foreach(DB::all("SELECT id FROM users WHERE role IN ('owner','admin') AND is_active=1 AND approval_status='approved'") as $admin)if((int)$admin['id']!==($actorId??0))queue_user_push((int)$admin['id'],$title,$body,$url,'','admin-'.$id);return $id;}
    catch(Throwable $e){log_error($e);return 0;}
}
function admin_notifications(int $userId,int $limit=30): array {
    try{return DB::all('SELECT n.*,u.display_name actor_name,CASE WHEN r.notification_id IS NULL THEN 0 ELSE 1 END is_read FROM admin_notifications n LEFT JOIN users u ON u.id=n.actor_id LEFT JOIN admin_notification_reads r ON r.notification_id=n.id AND r.user_id=? ORDER BY n.id DESC LIMIT '.max(1,min(100,$limit)),[$userId]);}
    catch(Throwable){return [];}
}
function admin_unread_count(int $userId): int {
    try{return (int)(DB::one('SELECT COUNT(*) c FROM admin_notifications n LEFT JOIN admin_notification_reads r ON r.notification_id=n.id AND r.user_id=? WHERE r.notification_id IS NULL',[$userId])['c']??0);}
    catch(Throwable){return 0;}
}
function mark_admin_notifications_read(int $userId,array $ids=[]): void {
    if(!$ids){$ids=array_map('intval',array_column(admin_notifications($userId,100),'id'));}
    foreach(array_unique(array_filter(array_map('intval',$ids))) as $id)DB::run('INSERT IGNORE INTO admin_notification_reads (notification_id,user_id,read_at) VALUES (?,?,CURRENT_TIMESTAMP)',[$id,$userId]);
}
function push_payload(string $title,string $body,string $url='',string $icon=''): array { return compact('title','body','url','icon')+['tag'=>'kovcheg-'.hash('sha256',$title.'|'.$url)]; }

function profile_wall_can_post(array $profileUser,int $viewerId): bool {
    if($viewerId===(int)$profileUser['id'])return true;$policy=raw_user_setting((int)$profileUser['id'],'wall_post_policy','colleagues');
    if($policy==='everyone')return Auth::check();if($policy==='colleagues')return are_colleagues($viewerId,(int)$profileUser['id']);return false;
}
function profile_post_media_url(int $attachmentId): string { return app_url('/wall-media/'.$attachmentId); }
function story_media_url(int $storyId): string { return app_url('/story/'.$storyId.'/media'); }
function profile_post_attachments(int $postId): array {
    try{$rows=DB::all("SELECT id,post_id,stored_path,mime_type,file_size,sort_order,COALESCE(original_name,'') original_name FROM profile_post_attachments WHERE post_id=? ORDER BY sort_order,id",[$postId]);foreach($rows as &$row)$row['url']=profile_post_media_url((int)$row['id']);unset($row);return $rows;}catch(Throwable){return [];}
}
function profile_post_comments(int $postId,int $limit=8): array {
    try{
        $rows=DB::all('SELECT c.*,u.display_name,u.username,u.avatar_path,u.is_verified,u.verification_label FROM profile_post_comments c JOIN users u ON u.id=c.user_id WHERE c.post_id=? AND c.deleted_at IS NULL ORDER BY c.id ASC LIMIT '.max(10,min(150,$limit*10)),[$postId]);
        $byId=[];$roots=[];
        foreach($rows as $row){$row['replies']=[];$byId[(int)$row['id']]=$row;}
        foreach($byId as $id=>&$row){$parent=(int)($row['parent_id']??0);if($parent&&isset($byId[$parent]))$byId[$parent]['replies'][]=&$row;else $roots[]=&$row;}unset($row);
        if(count($roots)>$limit)$roots=array_slice($roots,-$limit);
        return $roots;
    }catch(Throwable){return [];}
}
function profile_post_reaction_summary(int $postId,int $viewerId=0): array {
    try{$items=DB::all('SELECT emoji,COUNT(*) count,MAX(CASE WHEN user_id=? THEN 1 ELSE 0 END) mine FROM profile_post_reactions WHERE post_id=? GROUP BY emoji ORDER BY COUNT(*) DESC,MIN(created_at)',[$viewerId,$postId]);$mine=null;$total=0;foreach($items as $item){$total+=(int)$item['count'];if(!empty($item['mine']))$mine=$item['emoji'];}return ['items'=>$items,'mine'=>$mine,'total'=>$total];}catch(Throwable){return ['items'=>[],'mine'=>null,'total'=>0];}
}
function profile_post_share_data(int $postId): ?array {
    try{
        $row=DB::one('SELECT p.id,p.body,p.created_at,p.user_id,p.author_id,p.visibility,p.status,p.publish_at,a.display_name author_name,a.username author_username,a.avatar_path,a.is_verified,a.verification_label,t.username wall_username FROM profile_posts p JOIN users a ON a.id=p.author_id JOIN users t ON t.id=p.user_id WHERE p.id=? AND p.deleted_at IS NULL',[$postId]);
        if(!$row||!profile_post_can_view($row,Auth::check()?Auth::id():0))return null;$row['attachments']=profile_post_attachments($postId);return $row;
    }catch(Throwable){return null;}
}
function enrich_profile_posts(array $posts): array {
    foreach($posts as &$post){
        $post['attachments']=profile_post_attachments((int)$post['id']);
        $post['comments']=profile_post_comments((int)$post['id'],5);
        try{$post['comments_count']=(int)(DB::one('SELECT COUNT(*) c FROM profile_post_comments WHERE post_id=? AND deleted_at IS NULL',[$post['id']])['c']??0);}catch(Throwable){$post['comments_count']=0;}
        $summary=profile_post_reaction_summary((int)$post['id'],Auth::id());$post['reactions']=$summary['items'];$post['my_reaction']=$summary['mine'];$post['reaction_total']=$summary['total'];
        $post['repost']=!empty($post['repost_post_id'])?profile_post_share_data((int)$post['repost_post_id']):null;
    }unset($post);return $posts;
}
function profile_post_is_published(array $post): bool {
    $status=(string)($post['status']??'published');
    if($status==='draft')return false;
    if($status==='scheduled'){
        $publishAt=(string)($post['publish_at']??'');
        return $publishAt!==''&&strtotime($publishAt)!==false&&strtotime($publishAt)<=time();
    }
    return true;
}
function profile_post_can_view(array $post,?int $viewerId=null): bool {
    if(!profile_post_is_published($post))return false;
    $viewerId=$viewerId??(Auth::check()?Auth::id():0);$authorId=(int)($post['author_id']??0);$wallId=(int)($post['user_id']??0);
    if($viewerId>0&&($viewerId===$authorId||$viewerId===$wallId||Auth::isAdmin()))return true;
    $visibility=(string)($post['visibility']??'everyone');
    if($visibility==='everyone')return true;
    if($visibility==='users')return $viewerId>0;
    if($visibility==='colleagues')return $viewerId>0&&are_colleagues($viewerId,$authorId);
    return false;
}
function profile_post_state_from_request(int $authorId,int $wallUserId): array {
    $visibility=in_array((string)($_POST['visibility']??'everyone'),['everyone','users','colleagues','only_me'],true)?(string)$_POST['visibility']:'everyone';
    $mode=in_array((string)($_POST['publish_mode']??'now'),['now','scheduled','draft'],true)?(string)$_POST['publish_mode']:'now';
    if($authorId!==$wallUserId&&$mode!=='now')$mode='now';
    $status='published';$publishAt=null;
    if($mode==='draft')$status='draft';
    elseif($mode==='scheduled'){
        $raw=trim((string)($_POST['publish_at']??''));$dt=DateTimeImmutable::createFromFormat('Y-m-d\TH:i',$raw,new DateTimeZone(date_default_timezone_get()));
        if(!$dt||$dt->getTimestamp()<=time()+60)throw new RuntimeException('Выберите время публикации минимум на две минуты позже текущего.');
        $status='scheduled';$publishAt=$dt->format('Y-m-d H:i:00');
    }
    return compact('visibility','status','publishAt','mode');
}
function profile_post_drafts(int $authorId,int $limit=50): array {
    try{$rows=DB::all("SELECT p.id,p.body,p.status,p.visibility,p.publish_at,p.created_at,p.updated_at,(SELECT COUNT(*) FROM profile_post_attachments a WHERE a.post_id=p.id) attachment_count FROM profile_posts p WHERE p.author_id=? AND p.deleted_at IS NULL AND (p.status='draft' OR (p.status='scheduled' AND p.publish_at>CURRENT_TIMESTAMP)) ORDER BY COALESCE(p.publish_at,p.updated_at,p.created_at) DESC LIMIT ".max(1,min(100,$limit)),[$authorId]);return $rows;}catch(Throwable){return [];}
}
function profile_post_for_render(int $postId): ?array {
    try{$row=DB::one('SELECT p.*,a.display_name author_name,a.username author_username,a.avatar_path,a.is_verified,a.verification_label,t.display_name wall_name,t.username wall_username FROM profile_posts p JOIN users a ON a.id=p.author_id JOIN users t ON t.id=p.user_id WHERE p.id=? AND p.deleted_at IS NULL',[$postId]);if(!$row)return null;$rows=enrich_profile_posts([$row]);return $rows[0]??null;}catch(Throwable){return null;}
}
function profile_wall_posts(int $profileUserId,int $limit=40): array {
    try{$fetch=max(40,min(300,$limit*4));$rows=DB::all('SELECT p.*,u.display_name,u.username,u.avatar_path,u.is_verified,u.verification_label,(SELECT COUNT(*) FROM profile_post_likes l WHERE l.post_id=p.id) likes_count,(SELECT COUNT(*) FROM profile_post_likes l WHERE l.post_id=p.id AND l.user_id=?) liked_by_me FROM profile_posts p JOIN users u ON u.id=p.author_id WHERE p.user_id=? AND p.deleted_at IS NULL ORDER BY p.id DESC LIMIT '.$fetch,[Auth::id(),$profileUserId]);$rows=array_values(array_filter($rows,fn($row)=>profile_post_can_view($row,Auth::check()?Auth::id():0)));return enrich_profile_posts(array_slice($rows,0,max(1,min(100,$limit))));}catch(Throwable){return [];}
}
function current_avatar_history(int $userId): ?array { try{return DB::one('SELECT * FROM user_avatar_history WHERE user_id=? AND is_current=1 ORDER BY id DESC LIMIT 1',[$userId]);}catch(Throwable){return null;} }
function avatar_reaction_summary(int $userId,int $viewerId=0): array {
    $photo=current_avatar_history($userId);if(!$photo)return ['photo_id'=>0,'items'=>[],'mine'=>null,'total'=>0];
    try{$items=DB::all('SELECT emoji,COUNT(*) count,MAX(CASE WHEN user_id=? THEN 1 ELSE 0 END) mine FROM avatar_reactions WHERE avatar_history_id=? GROUP BY emoji ORDER BY COUNT(*) DESC,MIN(created_at)',[$viewerId,(int)$photo['id']]);$mine=null;$total=0;foreach($items as $item){$total+=(int)$item['count'];if(!empty($item['mine']))$mine=$item['emoji'];}return ['photo_id'=>(int)$photo['id'],'items'=>$items,'mine'=>$mine,'total'=>$total];}catch(Throwable){return ['photo_id'=>(int)$photo['id'],'items'=>[],'mine'=>null,'total'=>0];}
}
function active_stories_for_user(int $userId,int $viewerId=0): array {
    try{$rows=DB::all("SELECT s.*,CASE WHEN v.story_id IS NULL THEN 0 ELSE 1 END viewed,(SELECT COUNT(*) FROM story_views sv WHERE sv.story_id=s.id AND sv.user_id<>s.user_id) view_count FROM user_stories s LEFT JOIN story_views v ON v.story_id=s.id AND v.user_id=? WHERE s.user_id=? AND s.deleted_at IS NULL AND s.expires_at>CURRENT_TIMESTAMP ORDER BY s.id",[$viewerId,$userId]);foreach($rows as &$row)$row['media_url']=story_media_url((int)$row['id']);unset($row);return $rows;}catch(Throwable){return [];}
}
function has_active_story(int $userId): bool { try{return DB::one('SELECT id FROM user_stories WHERE user_id=? AND deleted_at IS NULL AND expires_at>CURRENT_TIMESTAMP LIMIT 1',[$userId])!==null;}catch(Throwable){return false;} }
function story_feed_users(int $viewerId,int $limit=30): array {
    try{$rows=DB::all("SELECT u.id,u.display_name,u.username,u.avatar_path,u.is_verified,u.verification_label,COUNT(s.id) story_count,SUM(CASE WHEN v.story_id IS NULL THEN 1 ELSE 0 END) unseen_count FROM users u JOIN user_stories s ON s.user_id=u.id AND s.deleted_at IS NULL AND s.expires_at>CURRENT_TIMESTAMP LEFT JOIN story_views v ON v.story_id=s.id AND v.user_id=? WHERE u.id=? OR EXISTS(SELECT 1 FROM colleague_requests r WHERE r.status='accepted' AND ((r.requester_id=? AND r.recipient_id=u.id) OR (r.recipient_id=? AND r.requester_id=u.id))) GROUP BY u.id ORDER BY (u.id=?) DESC,unseen_count DESC,MAX(s.id) DESC LIMIT ".max(1,min(100,$limit)),[$viewerId,$viewerId,$viewerId,$viewerId,$viewerId]);return $rows;}catch(Throwable){return [];}
}
function profile_people_blocks(int $profileUserId,int $limit=6): array {
    $limit=max(1,min(12,$limit));$base="u.id,u.display_name,u.username,u.avatar_path,u.is_verified,u.verification_label,u.last_seen_at";
    try{$colleagues=DB::all("SELECT $base FROM colleague_requests r JOIN users u ON u.id=IF(r.requester_id=?,r.recipient_id,r.requester_id) WHERE (r.requester_id=? OR r.recipient_id=?) AND r.status='accepted' AND u.is_active=1 ORDER BY u.last_seen_at DESC,u.display_name LIMIT $limit",[$profileUserId,$profileUserId,$profileUserId]);}catch(Throwable){$colleagues=[];}
    $online=array_values(array_filter($colleagues,fn($u)=>online($u['last_seen_at']??null)));
    try{$followers=DB::all("SELECT $base FROM user_follows f JOIN users u ON u.id=f.follower_id WHERE f.followed_id=? AND u.is_active=1 AND NOT EXISTS(SELECT 1 FROM colleague_requests r WHERE r.status='accepted' AND ((r.requester_id=f.follower_id AND r.recipient_id=f.followed_id) OR (r.recipient_id=f.follower_id AND r.requester_id=f.followed_id))) ORDER BY f.created_at DESC LIMIT $limit",[$profileUserId]);}catch(Throwable){$followers=[];}
    return ['online'=>array_slice($online,0,$limit),'colleagues'=>$colleagues,'followers'=>$followers];
}
function profile_right_blocks(int $profileUserId): array { return profile_people_blocks($profileUserId,6); }

function diagnostic_checks(): array {
    $checks=[];$add=function($name,$ok,$detail,$help)use(&$checks){$checks[]=compact('name','ok','detail','help');};
    $add('PHP 8.1+',version_compare(PHP_VERSION,'8.1.0','>='),PHP_VERSION,'requirements');
    foreach(['pdo','openssl','fileinfo','json','mbstring'] as $ext)$add('Расширение '.$ext,extension_loaded($ext),extension_loaded($ext)?'Установлено':'Не найдено','requirements');
    $add('PDO MySQL',extension_loaded('pdo_mysql'),'Нужно для MySQL/MariaDB','requirements');
    $add('ZipArchive',class_exists('ZipArchive'),'Нужно для модулей и резервных копий','module-management');
    $add('Оптимизация изображений',image_driver_available(),image_driver_available()?(extension_loaded('imagick')?'Imagick · сжатие и удаление метаданных':'GD · сжатие и уменьшение'):'Установите GD или Imagick','files');
    foreach(['storage','storage/uploads','storage/uploads/avatars','storage/backups','storage/builds','storage/logs','config','modules'] as $dir)$add('Запись: '.$dir,is_dir(BASE_PATH.'/'.$dir)&&is_writable(BASE_PATH.'/'.$dir),is_writable(BASE_PATH.'/'.$dir)?'Доступно':'Нет прав записи','requirements');
    try{DB::one('SELECT 1 ok');$add('База данных',true,'Соединение работает · utf8mb4','requirements');}catch(Throwable $e){$add('База данных',false,$e->getMessage(),'troubleshooting');}
    try{DB::one('SELECT slug FROM roles LIMIT 1');DB::one('SELECT role_slug FROM role_permissions LIMIT 1');$add('Роли и разрешения',true,'Централизованная RBAC готова','roles-and-permissions');}catch(Throwable){$add('Роли и разрешения',false,'Нужна миграция KOVCHEG CMS 2.1','troubleshooting');}
    try{$channels=(int)(DB::one("SELECT COUNT(*) c FROM chats WHERE type='channel' AND deleted_at IS NULL")['c']??0);$add('Режим личных сообщений',$channels===0,$channels===0?'Каналы отключены':'Найдено активных каналов: '.$channels,'messages-replies-reactions');}catch(Throwable){$add('Режим личных сообщений',false,'Не удалось проверить таблицу переписок','troubleshooting');}
    $secure=str_starts_with(app_url('/'),'https://')||str_contains(app_url('/'),'localhost');$pushReady=(string)setting('push_vapid_public_key','')!=='';
    $add('Web Push',$pushReady&&$secure,$pushReady?($secure?'Ключи и защищённое соединение готовы':'Ключи есть, но нужен HTTPS'):'Ключи не созданы','settings');
    $add('Лимит загрузки',true,ini_get('upload_max_filesize').' / POST '.ini_get('post_max_size'),'files');
    $add('Память PHP',true,(string)ini_get('memory_limit'),'requirements');
    $add('HTTPS и защищённые cookie',$secure,$secure?'Защищённое соединение включено':'Для рабочих данных обязателен HTTPS','security');
    $key=(string)cfg('app.key','');$add('Ключ приложения',strlen($key)>=32,strlen($key)>=32?'Ключ достаточной длины':'Создайте случайный ключ не короче 32 символов','security');
    $add('Защита паролей',defined('PASSWORD_ARGON2ID'),'Используется '.(defined('PASSWORD_ARGON2ID')?'Argon2id':'системный алгоритм PHP'),'security');
    $config=BASE_PATH.'/config/config.php';$perms=is_file($config)?(@fileperms($config)&0777):0;$add('Права конфигурации',$perms!==0&&($perms&0002)===0,$perms?sprintf('%04o',$perms):'Файл не найден','security');
    try{DB::one('SELECT rate_key FROM auth_rate_limits LIMIT 1');$add('Защита входа и регистрации',true,'Ограничение попыток и CAPTCHA доступны','registration');}catch(Throwable){$add('Защита входа и регистрации',false,'Нужна таблица auth_rate_limits','troubleshooting');}
    $captcha=registration_captcha_provider();$captchaReady=$captcha==='builtin'||(trim((string)setting('turnstile_site_key',''))!==''&&trim(secret_setting('turnstile_secret_key',''))!=='');$add('CAPTCHA регистрации',$captchaReady,$captcha==='turnstile'?($captchaReady?'Turnstile настроен':'Не заполнены ключи Turnstile'):'Встроенная проверка включена','registration');
    $add('CSP и защитные заголовки',true,'CSP, HSTS, COOP, CORP, Permissions-Policy и nosniff формируются ядром','security');
    $add('Developer Center исключён из сборки',is_dir(BASE_PATH.'/developer'),'История разработки хранится отдельно и фильтруется сборщиком','developer');
    return $checks;
}

function doc_articles(): array { $out=[];foreach(glob(BASE_PATH.'/docs/*.md')?:[] as $file){$raw=file_get_contents($file)?:'';$title=preg_match('/^#\s+(.+)$/m',$raw,$match)?trim($match[1]):basename($file,'.md');$out[]=['slug'=>basename($file,'.md'),'title'=>$title];}usort($out,fn($a,$b)=>strcmp($a['title'],$b['title']));return $out; }
function doc_read(string $slug): ?array { $slug=preg_replace('/[^a-z0-9_-]/i','',$slug);$file=BASE_PATH.'/docs/'.$slug.'.md';if(!is_file($file))return null;$raw=file_get_contents($file)?:'';$title=preg_match('/^#\s+(.+)$/m',$raw,$match)?trim($match[1]):$slug;$html=e($raw);$html=preg_replace('/^###\s+(.+)$/m','<h3>$1</h3>',$html);$html=preg_replace('/^##\s+(.+)$/m','<h2>$1</h2>',$html);$html=preg_replace('/^#\s+(.+)$/m','<h1>$1</h1>',$html);$html=preg_replace('/^-\s+(.+)$/m','<li>$1</li>',$html);$html=preg_replace('/`([^`]+)`/','<code>$1</code>',$html);$html=nl2br($html);return compact('slug','title','html','raw'); }


function auto_add_new_user_to_primary_admin(int $userId): void {
    if($userId<1||setting('auto_add_admin_colleague','1')!=='1')return;
    try{
        $admin=DB::one("SELECT id FROM users WHERE id<>? AND role IN ('owner','admin') AND is_active=1 AND approval_status='approved' ORDER BY FIELD(role,'owner','admin'),id ASC LIMIT 1",[$userId]);
        $adminId=(int)($admin['id']??0);if($adminId<1)return;
        $existing=DB::one('SELECT id FROM colleague_requests WHERE (requester_id=? AND recipient_id=?) OR (requester_id=? AND recipient_id=?) LIMIT 1',[$adminId,$userId,$userId,$adminId]);
        if($existing)DB::run("UPDATE colleague_requests SET requester_id=?,recipient_id=?,status='accepted',responded_at=CURRENT_TIMESTAMP,updated_at=CURRENT_TIMESTAMP WHERE id=?",[$adminId,$userId,$existing['id']]);
        else DB::run("INSERT INTO colleague_requests (requester_id,recipient_id,status,created_at,responded_at,updated_at) VALUES (?,?,'accepted',CURRENT_TIMESTAMP,CURRENT_TIMESTAMP,CURRENT_TIMESTAMP)",[$adminId,$userId]);
        DB::run('DELETE FROM user_follows WHERE (follower_id=? AND followed_id=?) OR (follower_id=? AND followed_id=?)',[$adminId,$userId,$userId,$adminId]);
        queue_user_push($userId,'Добавлен коллега','Администратор автоматически добавлен в ваши коллеги.',user_public_url((string)(DB::one('SELECT username FROM users WHERE id=?',[$adminId])['username']??'')),avatar_url($adminId),'auto-admin-colleague-'.$userId,true);
    }catch(Throwable $e){log_error($e);}
}

function are_colleagues(int $a,int $b): bool {
    if($a<1||$b<1||$a===$b)return $a===$b;
    try{return DB::one("SELECT id FROM colleague_requests WHERE status='accepted' AND ((requester_id=? AND recipient_id=?) OR (requester_id=? AND recipient_id=?)) LIMIT 1",[$a,$b,$b,$a])!==null;}catch(Throwable){return false;}
}
function follows_user(int $followerId,int $followedId): bool {
    try{return DB::one('SELECT 1 FROM user_follows WHERE follower_id=? AND followed_id=? LIMIT 1',[$followerId,$followedId])!==null;}catch(Throwable){return false;}
}
function colleague_request_between(int $a,int $b): ?array {
    try{return DB::one("SELECT * FROM colleague_requests WHERE ((requester_id=? AND recipient_id=?) OR (requester_id=? AND recipient_id=?)) ORDER BY id DESC LIMIT 1",[$a,$b,$b,$a]);}catch(Throwable){return null;}
}
function relationship_summary(int $viewerId,int $profileId): array {
    $request=colleague_request_between($viewerId,$profileId);$colleagues=$request&&($request['status']??'')==='accepted';
    return [
        'is_self'=>$viewerId===$profileId,
        'following'=>$viewerId>0?follows_user($viewerId,$profileId):false,
        'follows_me'=>$viewerId>0?follows_user($profileId,$viewerId):false,
        'colleagues'=>$colleagues,
        'request_id'=>(int)($request['id']??0),
        'request_status'=>$request['status']??null,
        'request_outgoing'=>$request&&((int)$request['requester_id']===$viewerId),
        'request_incoming'=>$request&&((int)$request['recipient_id']===$viewerId),
        'blocked_by_me'=>blocked_by_me($profileId,$viewerId),
        'blocked_between'=>users_blocked($viewerId,$profileId),
    ];
}
function profile_counts(int $userId): array {
    try{return [
        'colleagues'=>(int)(DB::one("SELECT COUNT(*) c FROM colleague_requests WHERE status='accepted' AND (requester_id=? OR recipient_id=?)",[$userId,$userId])['c']??0),
        'followers'=>(int)(DB::one("SELECT COUNT(*) c FROM user_follows f WHERE f.followed_id=? AND NOT EXISTS(SELECT 1 FROM colleague_requests r WHERE r.status='accepted' AND ((r.requester_id=f.follower_id AND r.recipient_id=f.followed_id) OR (r.recipient_id=f.follower_id AND r.requester_id=f.followed_id)))",[$userId])['c']??0),
        'following'=>(int)(DB::one("SELECT COUNT(*) c FROM user_follows f WHERE f.follower_id=? AND NOT EXISTS(SELECT 1 FROM colleague_requests r WHERE r.status='accepted' AND ((r.requester_id=f.follower_id AND r.recipient_id=f.followed_id) OR (r.recipient_id=f.follower_id AND r.requester_id=f.followed_id)))",[$userId])['c']??0),
        'requests'=>(int)(DB::one("SELECT COUNT(*) c FROM colleague_requests WHERE recipient_id=? AND status='pending'",[$userId])['c']??0),
    ];}catch(Throwable){return ['colleagues'=>0,'followers'=>0,'following'=>0,'requests'=>0];}
}
function can_view_profile(array $profile,?int $viewerId): bool {
    $profileId=(int)($profile['id']??0);if($profileId<1)return false;if($viewerId===$profileId||Auth::isAdmin())return true;if($viewerId&&users_blocked($viewerId,$profileId))return false;
    $visibility=raw_user_setting($profileId,'profile_visibility','users');
    if($visibility==='everyone')return true;
    if(!$viewerId)return false;
    if($visibility==='users')return true;
    if($visibility==='colleagues')return are_colleagues($viewerId,$profileId);
    return false;
}
function contact_request_allowed(int $targetId,int $viewerId): bool {
    if($targetId<1||$viewerId<1||$targetId===$viewerId||users_blocked($targetId,$viewerId))return false;
    $policy=raw_user_setting($targetId,'contact_request_policy','everyone');
    if($policy==='nobody')return false;
    if($policy==='followers')return follows_user($viewerId,$targetId);
    return true;
}
function relationship_contacts(int $userId,int $limit=200): array {
    try{$rows=DB::all("SELECT u.id,u.username,u.display_name,u.avatar_path,u.is_verified,u.verification_label,u.last_seen_at FROM colleague_requests cr JOIN users u ON u.id=CASE WHEN cr.requester_id=? THEN cr.recipient_id ELSE cr.requester_id END WHERE cr.status='accepted' AND (cr.requester_id=? OR cr.recipient_id=?) AND u.is_active=1 AND u.approval_status='approved' AND NOT EXISTS(SELECT 1 FROM user_blocks b WHERE (b.blocker_id=? AND b.blocked_id=u.id) OR (b.blocker_id=u.id AND b.blocked_id=?)) ORDER BY u.display_name LIMIT ".max(1,min(500,$limit)),[$userId,$userId,$userId,$userId,$userId]);return $rows;}catch(Throwable){return [];}
}
function stream_attachment(array $attachment,bool $download=false): never {
    $relative=(string)($attachment['stored_path']??'');if($relative===''||str_contains($relative,'..')||str_starts_with($relative,'/'))abort(404,'Файл отсутствует в хранилище.');
    $file=BASE_PATH.'/storage/uploads/'.$relative;if(!is_file($file))abort(404,'Файл отсутствует в хранилище.');
    $actual=(new finfo(FILEINFO_MIME_TYPE))->file($file)?:'application/octet-stream';$stored=(string)($attachment['mime_type']??'');$mime=$actual!=='application/octet-stream'?$actual:($stored?:$actual);
    $inline=!$download&&(str_starts_with($mime,'image/')||str_starts_with($mime,'audio/')||str_starts_with($mime,'video/'));
    if(session_status()===PHP_SESSION_ACTIVE)session_write_close();while(ob_get_level()>0)@ob_end_clean();
    header('Content-Type: '.$mime);header('Content-Length: '.filesize($file));header('Content-Disposition: '.($inline?'inline':'attachment').'; filename*=UTF-8\'\''.rawurlencode((string)$attachment['original_name']));header('Cache-Control: private, max-age=3600');header('X-Content-Type-Options: nosniff');header('X-Accel-Buffering: no');readfile($file);exit;
}
function is_image_attachment(array $message): bool {
    $mime=mb_lower((string)($message['mime_type']??''));if(str_starts_with($mime,'image/'))return true;
    $ext=mb_lower(pathinfo((string)($message['original_name']??''),PATHINFO_EXTENSION));return in_array($ext,['jpg','jpeg','png','webp','gif','bmp','avif','heic','heif'],true);
}

function create_backup(): string {
    if(!class_exists('ZipArchive'))throw new RuntimeException('Для резервных копий требуется PHP ZipArchive.');
    $name='kovcheg-backup-'.date('Ymd-His').'.zip';$path=BASE_PATH.'/storage/backups/'.$name;$zip=new ZipArchive();
    if($zip->open($path,ZipArchive::CREATE)!==true)throw new RuntimeException('Не удалось создать архив.');
    $tables=['settings','user_settings','users','user_permissions','chats','chat_members','messages','chat_events','attachments','message_reactions','sticker_packs','stickers','modules','api_tokens','webhooks','webhook_deliveries','audit_logs','admin_notifications','admin_notification_reads','channel_invite_links','channel_join_requests','push_subscriptions','push_deliveries','user_follows','colleague_requests','user_notifications','profile_posts','profile_post_likes','profile_post_reactions','user_blocks','avatar_comments'];
    foreach($tables as $table){try{$rows=DB::all('SELECT * FROM `'.$table.'`');}catch(Throwable){continue;}if($table==='users')foreach($rows as &$row)$row['password_hash']='[REDACTED]';if($table==='api_tokens')foreach($rows as &$row)$row['token_hash']='[REDACTED]';if($table==='webhooks')foreach($rows as &$row)$row['secret_encrypted']='[REDACTED]';if($table==='push_subscriptions')foreach($rows as &$row){$row['endpoint']='[REDACTED]';$row['p256dh']='[REDACTED]';$row['auth_token']='[REDACTED]';}if($table==='settings')foreach($rows as &$row){$key=mb_lower((string)($row['key']??''));if(preg_match('/(?:secret|private|password|token|api[_-]?key)/',$key)&&!str_contains($key,'public'))$row['value']='[REDACTED]';}$zip->addFromString('database/'.$table.'.json',json_encode($rows,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));}
    $zip->addFromString('README.txt','KOVCHEG CMS backup '.date('c')."\nСекреты и хеши паролей скрыты в JSON-экспорте.");$zip->close();audit('backup.create');return $path;
}



function users_blocked(int $a,int $b): bool {
    if($a<1||$b<1||$a===$b)return false;
    try{return DB::one('SELECT blocker_id FROM user_blocks WHERE (blocker_id=? AND blocked_id=?) OR (blocker_id=? AND blocked_id=?) LIMIT 1',[$a,$b,$b,$a])!==null;}catch(Throwable){return false;}
}
function blocked_by_me(int $targetId,int $viewerId=0): bool {
    $viewerId=$viewerId?:Auth::id();if($viewerId<1||$targetId<1)return false;
    try{return DB::one('SELECT blocker_id FROM user_blocks WHERE blocker_id=? AND blocked_id=? LIMIT 1',[$viewerId,$targetId])!==null;}catch(Throwable){return false;}
}
function block_list(int $userId): array {
    try{return DB::all("SELECT u.id,u.username,u.display_name,u.avatar_path,u.is_verified,u.verification_label,b.created_at FROM user_blocks b JOIN users u ON u.id=b.blocked_id WHERE b.blocker_id=? ORDER BY b.created_at DESC",[$userId]);}catch(Throwable){return [];}
}
function direct_chat_other_user(int $chatId,int $viewerId): ?array {
    return DB::one('SELECT u.id,u.username,u.display_name,u.avatar_path,u.is_verified,u.verification_label FROM chat_members cm JOIN users u ON u.id=cm.user_id WHERE cm.chat_id=? AND cm.user_id<>? LIMIT 1',[$chatId,$viewerId]);
}
function chat_public_url(int|array $chat,int $viewerId=0): string {
    $viewerId=$viewerId?:Auth::id();$row=is_array($chat)?$chat:DB::one('SELECT id,type,username FROM chats WHERE id=?',[(int)$chat]);
    if(!$row)return app_url('/messages');$id=(int)($row['id']??0);$type=(string)($row['type']??'direct');
    if($type==='channel'){$username=(string)($row['username']??'');return $username!==''?app_url('/messages/c/'.rawurlencode($username)):app_url('/messages/chat-'.$id);}
    $other=direct_chat_other_user($id,$viewerId);$username=(string)($other['username']??'');return $username!==''?app_url('/messages/@'.rawurlencode($username)):app_url('/messages/chat-'.$id);
}
function require_unblocked_chat(int $chatId,int $viewerId=0): void {
    $viewerId=$viewerId?:Auth::id();$chat=DB::one('SELECT type FROM chats WHERE id=?',[$chatId]);if(($chat['type']??'')!=='direct')return;$other=direct_chat_other_user($chatId,$viewerId);if($other&&users_blocked($viewerId,(int)$other['id']))abort(403,'Переписка недоступна: один из пользователей находится в чёрном списке.');
}
function video_embeds_from_text(string $text): array {
    preg_match_all('~https?://[^\\s<]+~iu',$text,$matches);$result=[];
    foreach(array_unique($matches[0]??[]) as $raw){$url=rtrim($raw,".,!?:;)\"]}");$parts=parse_url($url);$host=strtolower((string)($parts['host']??''));$path=(string)($parts['path']??'');$src='';$title='Видео';
        if(in_array($host,['youtube.com','www.youtube.com','m.youtube.com','music.youtube.com'],true)){$id='';parse_str((string)($parts['query']??''),$q);if(!empty($q['v']))$id=(string)$q['v'];elseif(preg_match('~/(?:shorts|embed)/([A-Za-z0-9_-]{6,})~',$path,$m))$id=$m[1];if($id!==''){$src='https://www.youtube-nocookie.com/embed/'.rawurlencode($id);$title='YouTube';}}
        elseif($host==='youtu.be'&&preg_match('~^/([A-Za-z0-9_-]{6,})~',$path,$m)){$src='https://www.youtube-nocookie.com/embed/'.rawurlencode($m[1]);$title='YouTube';}
        elseif(in_array($host,['vimeo.com','www.vimeo.com'],true)&&preg_match('~/(\\d+)~',$path,$m)){$src='https://player.vimeo.com/video/'.rawurlencode($m[1]);$title='Vimeo';}
        elseif(in_array($host,['rutube.ru','www.rutube.ru'],true)&&preg_match('~/(?:video|shorts)/([A-Za-z0-9_-]+)~',$path,$m)){$src='https://rutube.ru/play/embed/'.rawurlencode($m[1]);$title='Rutube';}
        if($src!==''&&!isset($result[$src]))$result[$src]=['src'=>$src,'title'=>$title,'url'=>$url];
    }
    return array_values($result);
}
function avatar_comments(int $profileUserId): array {
    try{$rows=DB::all('SELECT c.*,u.display_name,u.username,u.avatar_path,u.is_verified,u.verification_label FROM avatar_comments c JOIN users u ON u.id=c.user_id WHERE c.profile_user_id=? AND c.deleted_at IS NULL ORDER BY c.id ASC',[$profileUserId]);$by=[];$roots=[];foreach($rows as $row){$row['replies']=[];$by[(int)$row['id']]=$row;}foreach($by as $id=>$row){$parent=(int)($row['parent_id']??0);if($parent&&isset($by[$parent]))$by[$parent]['replies'][]=&$by[$id];else $roots[]=&$by[$id];}return $roots;}catch(Throwable){return [];}
}
function direct_chat_with(int $otherId,int $ownerId=0): int {
    $ownerId=$ownerId?:Auth::id();if(users_blocked($ownerId,$otherId))abort(403,'Пользователь находится в чёрном списке или заблокировал вас.');
    $existing=DB::one("SELECT c.id FROM chats c JOIN chat_members a ON a.chat_id=c.id AND a.user_id=? JOIN chat_members b ON b.chat_id=c.id AND b.user_id=? WHERE c.type='direct' AND c.deleted_at IS NULL LIMIT 1",[$ownerId,$otherId]);
    if($existing)return (int)$existing['id'];
    DB::pdo()->beginTransaction();
    try{$id=DB::insert("INSERT INTO chats (type,title,owner_id,created_at,updated_at) VALUES ('direct',NULL,?,CURRENT_TIMESTAMP,CURRENT_TIMESTAMP)",[$ownerId]);DB::run("INSERT INTO chat_members (chat_id,user_id,role,joined_at) VALUES (?,?,'owner',CURRENT_TIMESTAMP),(?,?,'member',CURRENT_TIMESTAMP)",[$id,$ownerId,$id,$otherId]);DB::pdo()->commit();return $id;}catch(Throwable $e){DB::pdo()->rollBack();throw $e;}
}

function distribution_exclusion_reason(string $relative,bool $includeModules=true): ?string {
    $relative=str_replace('\\','/',ltrim($relative,'/'));
    if($relative===''||str_contains($relative,"\0")||str_contains($relative,'../'))return 'unsafe_path';
    $basename=basename($relative);$lower=strtolower($relative);$baseLower=strtolower($basename);
    $exact=[
        'config/config.php','storage/installed.lock','.ftpquota','error_log','.ds_store','thumbs.db',
        'audit-report.md','implementation-status.md','technical-specification.md','build-info.txt','distribution-manifest.json','agents.md'
    ];
    if(in_array($lower,$exact,true))return 'private_or_runtime';
    $prefixes=['developer/','.git/','.github/','storage/uploads/','storage/cache/','storage/logs/','storage/backups/','storage/builds/'];
    foreach($prefixes as $prefix)if(str_starts_with($lower,$prefix))return 'private_or_runtime';
    if(str_starts_with($lower,'modules/kovcheg-core-update-')||str_starts_with($lower,'modules/kovcheg-messenger-update-'))return 'service_update';
    if(!$includeModules&&str_starts_with($lower,'modules/')&&!in_array($lower,['modules/.htaccess','modules/.keep'],true))return 'modules_disabled';
    if(($basename[0]??'')==='.'){if(!in_array($baseLower,['.htaccess','.keep'],true))return 'hidden_file';}
    if(in_array($baseLower,['error_log','debug.log','php_error.log','access.log'],true))return 'log_file';
    if(preg_match('/(?:^|\.)((?:bak|backup|old|orig|rej|tmp|temp|swp|swo|log|trace|dump))$/i',$basename))return 'temporary_or_backup';
    if(preg_match('/\.(?:sql\.gz|tar|tar\.gz|tgz|7z|rar)$/i',$basename))return 'archive_or_dump';
    if(str_ends_with($lower,'.zip')&&substr_count($relative,'/')===0)return 'root_archive';
    if(preg_match('/(?:^|\/)(?:\.env|credentials?|secrets?|private[-_.]?key)(?:\.|$)/i',$relative))return 'secret_file';
    return null;
}
function verify_distribution_archive(string $path): array {
    if(!class_exists('ZipArchive'))throw new RuntimeException('Для проверки сборки требуется PHP ZipArchive.');
    $zip=new ZipArchive();if($zip->open($path)!==true)throw new RuntimeException('Не удалось повторно открыть созданную сборку.');
    $seen=[];$total=0;
    try{
        for($i=0;$i<$zip->numFiles;$i++){
            $name=(string)$zip->getNameIndex($i);if($name===''||str_contains($name,'..')||str_starts_with($name,'/')||str_contains($name,"\\"))throw new RuntimeException('Сборка содержит небезопасный путь: '.$name);
            if(isset($seen[$name]))throw new RuntimeException('Сборка содержит дубликат: '.$name);$seen[$name]=true;
            $stat=$zip->statIndex($i);$total+=(int)($stat['size']??0);if($total>1024*1024*1024)throw new RuntimeException('Распакованный размер сборки превышает 1 ГБ.');
            if($name!==''&&!str_ends_with($name,'/')){
                $reason=distribution_exclusion_reason($name,true);
                if($reason!==null&&$name!=='BUILD-INFO.txt'&&$name!=='DISTRIBUTION-MANIFEST.json')throw new RuntimeException('В сборку попал запрещённый файл: '.$name.' ('.$reason.').');
            }
        }
        foreach(['install.php','database/schema.php','app/bootstrap.php','app/Core.php','app/functions.php','DISTRIBUTION-MANIFEST.json','BUILD-INFO.txt'] as $required)if(!isset($seen[$required]))throw new RuntimeException('В сборке отсутствует обязательный файл: '.$required);
        return ['entries'=>count($seen),'expanded_bytes'=>$total];
    }finally{$zip->close();}
}
function build_distribution(bool $includeModules=true): string {
    if(!class_exists('ZipArchive'))throw new RuntimeException('Для сборки требуется PHP ZipArchive.');
    $dir=BASE_PATH.'/storage/builds';if(!is_dir($dir)&&!mkdir($dir,0755,true))throw new RuntimeException('Не удалось создать папку сборок.');
    $name='KOVCHEG-CMS-'.APP_VERSION.'-INSTALL-'.date('Ymd-His').'.zip';$path=$dir.'/'.$name;$part=$path.'.part';@unlink($part);
    $zip=new ZipArchive();$manifest=['product'=>'KOVCHEG CMS','version'=>APP_VERSION,'built_at'=>date('c'),'modules_included'=>$includeModules,'files'=>[],'excluded'=>[]];
    try{
        if($zip->open($part,ZipArchive::CREATE|ZipArchive::OVERWRITE)!==true)throw new RuntimeException('Не удалось создать установочный архив.');
        $iterator=new RecursiveIteratorIterator(new RecursiveDirectoryIterator(BASE_PATH,FilesystemIterator::SKIP_DOTS),RecursiveIteratorIterator::LEAVES_ONLY);
        foreach($iterator as $file){
            if(!$file->isFile()||$file->isLink())continue;$full=$file->getPathname();$rel=str_replace('\\','/',substr($full,strlen(BASE_PATH)+1));
            $reason=distribution_exclusion_reason($rel,$includeModules);if($reason!==null){$manifest['excluded'][$reason]=($manifest['excluded'][$reason]??0)+1;continue;}
            if(!$zip->addFile($full,$rel))throw new RuntimeException('Не удалось добавить файл в сборку: '.$rel);
            $manifest['files'][]=['path'=>$rel,'sha256'=>hash_file('sha256',$full),'size'=>(int)$file->getSize()];
        }
        foreach(['config','storage/uploads','storage/cache','storage/logs','storage/backups','storage/builds','modules'] as $empty)$zip->addEmptyDir($empty);
        usort($manifest['files'],fn(array $a,array $b)=>strcmp($a['path'],$b['path']));ksort($manifest['excluded']);
        $buildInfo="KOVCHEG CMS ".APP_VERSION."\nAuthor: Ланцет Семён Борисович\nCopyright KOVCHEG CMS\nLicense: proprietary / all rights reserved\nСборка: ".date('c')."\nУстановка: загрузите содержимое архива на хостинг и откройте домен.\n";
        $zip->addFromString('BUILD-INFO.txt',$buildInfo);
        $zip->addFromString('DISTRIBUTION-MANIFEST.json',json_encode($manifest,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR));
        if(!$zip->close())throw new RuntimeException('Не удалось завершить установочный архив.');
        $verification=verify_distribution_archive($part);if(!rename($part,$path))throw new RuntimeException('Не удалось активировать созданную сборку.');
        audit('project.build','system',null,['file'=>$name,'modules'=>$includeModules,'entries'=>$verification['entries'],'expanded_bytes'=>$verification['expanded_bytes'],'excluded'=>$manifest['excluded']]);return $path;
    }catch(Throwable $e){try{$zip->close();}catch(Throwable){}@unlink($part);@unlink($path);throw $e;}
}

function api_auth(): void { $header=(string)($_SERVER['HTTP_AUTHORIZATION']??'');if(!preg_match('/^\s*Bearer\s+([A-Za-z0-9._~-]{20,512})\s*$/',$header,$match))json_response(['ok'=>false,'error'=>'Unauthorized'],401);$hash=hash('sha256',$match[1]);$user=DB::one("SELECT u.*,t.id api_token_id FROM api_tokens t JOIN users u ON u.id=t.user_id WHERE t.token_hash=? AND t.revoked_at IS NULL AND u.is_active=1 AND u.approval_status='approved' LIMIT 1",[$hash]);if(!$user)json_response(['ok'=>false,'error'=>'Invalid token'],401);Auth::authenticateApiUser($user);DB::run('UPDATE api_tokens SET last_used_at=CURRENT_TIMESTAMP WHERE id=?',[(int)$user['api_token_id']]); }
function api_user_public(?array $user): array { $username=$user['username']??null;return ['id'=>(int)($user['id']??0),'email'=>$user['email']??null,'username'=>$username,'first_name'=>$user['first_name']??null,'last_name'=>$user['last_name']??null,'display_name'=>$user['display_name']??null,'avatar_url'=>isset($user['id'])?avatar_url((int)$user['id'],(string)($user['avatar_path']??'')):null,'profile_url'=>$username?user_public_url((string)$username):null,'role'=>$user['role']??null,'is_verified'=>!empty($user['is_verified']),'verification_label'=>$user['verification_label']??null,'online'=>isset($user['last_seen_at'])?online($user['last_seen_at']):null,'last_seen_at'=>$user['last_seen_at']??null]; }


/* KOVCHEG CMS runtime services. */
function message_delete_window_minutes(): int {
    return max(0,min(525600,(int)setting('message_delete_window_minutes','120')));
}
function message_can_delete_now(array $message,bool $moderatorOverride=false): bool {
    if(Auth::isAdmin()||$moderatorOverride)return true;
    if((int)($message['sender_id']??0)!==Auth::id()||!can_do('can_delete_messages'))return false;
    $minutes=message_delete_window_minutes();
    if($minutes<=0)return false;
    $created=strtotime((string)($message['created_at']??''));
    return $created>0&&$created>=time()-($minutes*60);
}
function weather_cache_dir(): string {
    $dir=BASE_PATH.'/storage/cache/weather';
    if(!is_dir($dir))@mkdir($dir,0755,true);
    return $dir;
}
function weather_cache_file(string $kind,string $key): string {
    return weather_cache_dir().'/'.$kind.'-'.hash('sha256',mb_lower(trim($key))).'.json';
}
function weather_cache_read(string $file): ?array {
    if(!is_file($file))return null;
    $data=json_decode((string)@file_get_contents($file),true);
    return is_array($data)?$data:null;
}
function weather_cache_write(string $file,array $data): void {
    $tmp=$file.'.'.bin2hex(random_bytes(4)).'.tmp';
    if(file_put_contents($tmp,json_encode($data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),LOCK_EX)!==false)@rename($tmp,$file);
    else @unlink($tmp);
}
function weather_http_json(string $url,int $timeout=9,array $extraHeaders=[]): array {
    $body=false;$status=0;
    if(function_exists('curl_init')){
        $ch=curl_init($url);
        curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_FOLLOWLOCATION=>true,CURLOPT_CONNECTTIMEOUT=>min(5,$timeout),CURLOPT_TIMEOUT=>$timeout,CURLOPT_HTTPHEADER=>array_merge(['Accept: application/json','User-Agent: KOVCHEG-CMS/'.APP_VERSION.' (+'.app_url('/').')'],$extraHeaders)]);
        $body=curl_exec($ch);$status=(int)curl_getinfo($ch,CURLINFO_RESPONSE_CODE);$err=curl_error($ch);curl_close($ch);
        if($body===false)throw new RuntimeException($err?:'Внешний сервис не ответил.');
    }else{
        $headerLines=array_merge(['Accept: application/json','User-Agent: KOVCHEG-CMS/'.APP_VERSION],$extraHeaders);$ctx=stream_context_create(['http'=>['timeout'=>$timeout,'ignore_errors'=>true,'header'=>implode("\r\n",$headerLines)."\r\n"],'ssl'=>['verify_peer'=>true,'verify_peer_name'=>true]]);
        $body=@file_get_contents($url,false,$ctx);
        if($body===false)throw new RuntimeException('Сервер не может подключиться к погодному сервису.');
        foreach($http_response_header??[] as $line)if(preg_match('~^HTTP/\\S+\\s+(\\d+)~',$line,$m))$status=(int)$m[1];
    }
    if($status>=400)throw new RuntimeException('Погодный сервис вернул HTTP '.$status.'.');
    $data=json_decode((string)$body,true);
    if(!is_array($data))throw new RuntimeException('Погодный сервис вернул некорректные данные.');
    return $data;
}
function weather_coordinate_input(string $value): ?array {
    if(!preg_match('/^(-?\\d{1,2}(?:[.,]\\d+)?)\\s*[,; ]\\s*(-?\\d{1,3}(?:[.,]\\d+)?)$/u',trim($value),$m))return null;
    $lat=(float)str_replace(',','.',$m[1]);$lon=(float)str_replace(',','.',$m[2]);
    if(abs($lat)>90||abs($lon)>180)return null;
    return ['latitude'=>$lat,'longitude'=>$lon,'name'=>'Выбранная точка','admin1'=>'','country'=>''];
}
function weather_city_candidates(string $raw): array {
    $original=trim(preg_replace('/\\s+/u',' ',$raw));
    $withoutType=trim((string)preg_replace('/^(г\\.?|город|с\\.?|село|д\\.?|деревня|п\\.?|пос[её]лок|пгт)\\s+/iu','',$original));
    $withoutOrdinal=trim((string)preg_replace('/\\s+\\d+\\s*[-–—]?\\s*(?:е|й|я|ое|ая)?\\s*$/iu','',$withoutType));
    $first=trim(explode(',',$withoutType)[0]??$withoutType);
    return array_values(array_unique(array_filter([$original,$withoutType,$withoutOrdinal,$first,$original.', Россия',$withoutOrdinal.', Россия'],fn($v)=>mb_strlen($v)>=2)));
}
function weather_geocode(string $city,bool $force=false): array {
    $city=trim($city);if($city==='')throw new RuntimeException('Город не указан.');
    if($direct=weather_coordinate_input($city))return $direct;
    $file=weather_cache_file('place',$city);$cached=weather_cache_read($file);
    if(!$force&&$cached&&time()-(int)($cached['cached_at']??0)<30*86400)return $cached['data'];
    $stale=$cached['data']??null;
    try{
        foreach(weather_city_candidates($city) as $query){
            $geo=weather_http_json('https://geocoding-api.open-meteo.com/v1/search?name='.rawurlencode($query).'&count=10&language=ru&format=json',8);
            $rows=is_array($geo['results']??null)?$geo['results']:[];
            $place=null;foreach($rows as $row){if(strtoupper((string)($row['country_code']??''))==='RU'){$place=$row;break;}}$place=$place??($rows[0]??null);
            if($place){$found=['latitude'=>(float)$place['latitude'],'longitude'=>(float)$place['longitude'],'name'=>(string)($place['name']??$query),'admin1'=>(string)($place['admin1']??$place['admin2']??''),'country'=>(string)($place['country']??'')];weather_cache_write($file,['cached_at'=>time(),'data'=>$found]);return $found;}
        }
        foreach(weather_city_candidates($city) as $query){
            $rows=weather_http_json('https://nominatim.openstreetmap.org/search?format=jsonv2&addressdetails=1&limit=5&accept-language=ru&q='.rawurlencode($query),9);
            $row=null;foreach($rows as $candidate){if(strtolower((string)($candidate['address']['country_code']??''))==='ru'){$row=$candidate;break;}}$row=$row??($rows[0]??null);
            if($row){$a=$row['address']??[];$found=['latitude'=>(float)$row['lat'],'longitude'=>(float)$row['lon'],'name'=>(string)($a['city']??$a['town']??$a['village']??$a['hamlet']??$a['municipality']??$row['name']??$query),'admin1'=>(string)($a['state']??$a['region']??$a['county']??''),'country'=>(string)($a['country']??'')];weather_cache_write($file,['cached_at'=>time(),'data'=>$found]);return $found;}
        }
    }catch(Throwable $e){if(is_array($stale))return $stale;throw $e;}
    if(is_array($stale))return $stale;
    throw new RuntimeException('Населённый пункт не найден. Укажите его вместе с областью или сохраните координаты.');
}
function weather_short_forecast(string $city,bool $force=false): array {
    if(setting('weather_provider','openmeteo')==='yandex'&&trim(secret_setting('yandex_weather_api_key',''))!==''){try{return weather_yandex_short_forecast($city,$force);}catch(Throwable $e){log_error($e);}}
    $place=weather_geocode($city,$force);$key=$city.'|'.$place['latitude'].'|'.$place['longitude'];$file=weather_cache_file('forecast',$key);$cached=weather_cache_read($file);$fresh=$cached&&time()-(int)($cached['cached_at']??0)<1200;
    if(!$force&&$fresh)return $cached['data'];$stale=$cached['data']??null;
    try{
        $params=http_build_query(['latitude'=>$place['latitude'],'longitude'=>$place['longitude'],'current'=>'temperature_2m,relative_humidity_2m,apparent_temperature,is_day,precipitation,rain,showers,weather_code,wind_speed_10m,wind_direction_10m,wind_gusts_10m','hourly'=>'temperature_2m,apparent_temperature,precipitation_probability,precipitation,rain,showers,weather_code,wind_speed_10m','daily'=>'weather_code,temperature_2m_max,temperature_2m_min,apparent_temperature_max,apparent_temperature_min,precipitation_sum,rain_sum,showers_sum,precipitation_probability_max,sunrise,sunset,wind_speed_10m_max','timezone'=>'auto','forecast_days'=>10],'','&',PHP_QUERY_RFC3986);
        $api=weather_http_json('https://api.open-meteo.com/v1/forecast?'.$params,12);
        $data=['place'=>$place,'current'=>$api['current']??[],'hourly'=>$api['hourly']??[],'daily'=>$api['daily']??[],'timezone'=>$api['timezone']??'auto','fetched_at'=>date('c')];
        weather_cache_write($file,['cached_at'=>time(),'data'=>$data]);return $data;
    }catch(Throwable $e){if(is_array($stale)){$stale['stale']=true;return $stale;}throw $e;}
}
function weather_long_forecast(string $city,bool $force=false): array {
    $place=weather_geocode($city,$force);$key=$city.'|'.$place['latitude'].'|'.$place['longitude'];$file=weather_cache_file('long',$key);$cached=weather_cache_read($file);$fresh=$cached&&time()-(int)($cached['cached_at']??0)<21600;
    if(!$force&&$fresh)return $cached['data'];$stale=$cached['data']??null;
    try{
        $params=http_build_query(['latitude'=>$place['latitude'],'longitude'=>$place['longitude'],'daily'=>'temperature_2m_max,temperature_2m_min,precipitation_sum,rain_sum','timezone'=>'auto','forecast_days'=>30],'','&',PHP_QUERY_RFC3986);
        $api=weather_http_json('https://ensemble-api.open-meteo.com/v1/ensemble?'.$params,14);
        $data=['place'=>$place,'daily'=>$api['daily']??[],'timezone'=>$api['timezone']??'auto','fetched_at'=>date('c'),'probabilistic'=>true];weather_cache_write($file,['cached_at'=>time(),'data'=>$data]);return $data;
    }catch(Throwable $e){if(is_array($stale)){$stale['stale']=true;return $stale;}return ['place'=>$place,'daily'=>[],'error'=>$e->getMessage(),'probabilistic'=>true];}
}
function weather_radar_metadata(bool $force=false): array {
    $file=weather_cache_dir().'/rainviewer.json';$cached=weather_cache_read($file);if(!$force&&$cached&&time()-(int)($cached['cached_at']??0)<300)return $cached['data'];$stale=$cached['data']??null;
    try{$data=weather_http_json('https://api.rainviewer.com/public/weather-maps.json',8);weather_cache_write($file,['cached_at'=>time(),'data'=>$data]);return $data;}catch(Throwable $e){if(is_array($stale))return $stale;return ['error'=>$e->getMessage()];}
}
function weather_refresh_known_cities(int $limit=8): array {
    $result=['refreshed'=>0,'failed'=>0];
    try{$rows=DB::all("SELECT DISTINCT `value` city FROM user_settings WHERE `key`='weather_city' AND `value`<>'' ORDER BY updated_at DESC LIMIT ".max(1,min(30,$limit)));}catch(Throwable){return $result;}
    foreach($rows as $row){try{weather_short_forecast((string)$row['city'],true);$result['refreshed']++;}catch(Throwable $e){$result['failed']++;log_error($e);}}
    return $result;
}
function weather_code_text(int $code): string {
    return [0=>'Ясно',1=>'Преимущественно ясно',2=>'Переменная облачность',3=>'Пасмурно',45=>'Туман',48=>'Изморозь',51=>'Лёгкая морось',53=>'Морось',55=>'Сильная морось',61=>'Небольшой дождь',63=>'Дождь',65=>'Сильный дождь',71=>'Небольшой снег',73=>'Снег',75=>'Сильный снег',77=>'Снежные зёрна',80=>'Кратковременный дождь',81=>'Ливень',82=>'Сильный ливень',85=>'Снегопад',86=>'Сильный снегопад',95=>'Гроза',96=>'Гроза с градом',99=>'Сильная гроза с градом'][$code]??'Погода';
}
function weather_code_icon(int $code): string {
    if($code===0)return '☀';if($code<=2)return '🌤';if($code===3)return '☁';if(in_array($code,[45,48],true))return '🌫';if(($code>=51&&$code<=67)||($code>=80&&$code<=82))return '🌧';if(($code>=71&&$code<=77)||($code>=85&&$code<=86))return '❄';if($code>=95)return '⛈';return '◌';
}
function weather_long_icon(float $maxTemperature,float $precipitation): string {
    if($precipitation>=0.1&&$maxTemperature<=1)return '❄';
    if($precipitation>=2)return '🌧';
    if($precipitation>=0.1)return '🌦';
    return '◌';
}

/* KOVCHEG CMS runtime services. */
function message_is_important(int $messageId,int $userId=0): bool {
    $userId=$userId?:Auth::id();if($messageId<1||$userId<1)return false;
    try{return DB::one('SELECT 1 FROM message_bookmarks WHERE message_id=? AND user_id=? LIMIT 1',[$messageId,$userId])!==null;}catch(Throwable){return false;}
}
function social_audience(int $actorId,int $limit=500): array {
    if($actorId<1)return [];$limit=max(1,min(1000,$limit));
    try{$rows=DB::all("SELECT DISTINCT target_id FROM (SELECT CASE WHEN cr.requester_id=? THEN cr.recipient_id ELSE cr.requester_id END target_id FROM colleague_requests cr WHERE cr.status='accepted' AND (cr.requester_id=? OR cr.recipient_id=?) UNION SELECT follower_id target_id FROM user_follows WHERE followed_id=?) q JOIN users u ON u.id=q.target_id WHERE q.target_id<>? AND u.is_active=1 AND u.approval_status='approved' AND NOT EXISTS(SELECT 1 FROM user_blocks b WHERE (b.blocker_id=? AND b.blocked_id=q.target_id) OR (b.blocker_id=q.target_id AND b.blocked_id=?)) LIMIT $limit",[$actorId,$actorId,$actorId,$actorId,$actorId,$actorId,$actorId]);return array_values(array_unique(array_map('intval',array_column($rows,'target_id'))));}catch(Throwable){return [];}
}
function notify_social_audience(int $actorId,string $title,string $body,string $url,string $tag,string $icon=''): int {
    $actor=DB::one('SELECT id,display_name,username,avatar_path FROM users WHERE id=?',[$actorId])??[];$icon=$icon?:avatar_url($actorId,(string)($actor['avatar_path']??''));$count=0;
    foreach(social_audience($actorId) as $target){if(queue_user_push($target,$title,$body,$url,$icon,$tag.'-'.$target,true))$count++;}
    return $count;
}
function process_birthday_notifications(): int {
    $today=date('m-d');$year=date('Y');$count=0;
    try{$people=DB::all("SELECT id,display_name,username,avatar_path,birthday FROM users WHERE birthday IS NOT NULL AND DATE_FORMAT(birthday,'%m-%d')=? AND is_active=1 AND approval_status='approved'",[$today]);}catch(Throwable){return 0;}
    foreach($people as $person){$actorId=(int)$person['id'];foreach(social_audience($actorId) as $target){$tag='birthday-'.$actorId.'-'.$year;try{if(DB::one('SELECT id FROM user_notifications WHERE user_id=? AND tag=? LIMIT 1',[$target,$tag]))continue;}catch(Throwable){}queue_user_push($target,'Сегодня день рождения',(string)$person['display_name'].' сегодня отмечает день рождения',user_public_url((string)$person['username']),avatar_url($actorId,(string)($person['avatar_path']??'')),$tag,true);$count++;}}
    return $count;
}
function yandex_condition_code(string $condition): int {
    return match($condition){'clear'=>0,'partly-cloudy'=>2,'cloudy','overcast'=>3,'drizzle','light-rain'=>61,'rain','moderate-rain'=>63,'heavy-rain','continuous-heavy-rain','showers'=>65,'wet-snow','light-snow'=>71,'snow'=>73,'snow-showers','hail'=>75,'thunderstorm','thunderstorm-with-rain','thunderstorm-with-hail'=>95,default=>3};
}
function weather_yandex_short_forecast(string $city,bool $force=false): array {
    $key=trim(secret_setting('yandex_weather_api_key',''));if($key==='')throw new RuntimeException('Ключ Яндекс Погоды не настроен.');$place=weather_geocode($city,$force);$cache=weather_cache_file('yandex',$city.'|'.$place['latitude'].'|'.$place['longitude']);$cached=weather_cache_read($cache);if(!$force&&$cached&&time()-(int)($cached['cached_at']??0)<1200)return $cached['data'];$stale=$cached['data']??null;
    try{$url='https://api.weather.yandex.ru/v2/forecast?'.http_build_query(['lat'=>$place['latitude'],'lon'=>$place['longitude'],'lang'=>'ru_RU','limit'=>7,'hours'=>'true','extra'=>'true'],'','&',PHP_QUERY_RFC3986);$api=weather_http_json($url,12,['X-Yandex-Weather-Key: '.$key]);$fact=$api['fact']??[];$current=['temperature_2m'=>$fact['temp']??0,'apparent_temperature'=>$fact['feels_like']??0,'weather_code'=>yandex_condition_code((string)($fact['condition']??'')),'wind_speed_10m'=>isset($fact['wind_speed'])?(float)$fact['wind_speed']*3.6:0,'relative_humidity_2m'=>$fact['humidity']??0,'precipitation'=>0];$daily=['time'=>[],'weather_code'=>[],'temperature_2m_max'=>[],'temperature_2m_min'=>[],'precipitation_probability_max'=>[],'precipitation_sum'=>[]];$hourly=['time'=>[],'temperature_2m'=>[],'precipitation_probability'=>[],'precipitation'=>[],'weather_code'=>[]];foreach((array)($api['forecasts']??[]) as $forecast){$parts=$forecast['parts']??[];$day=$parts['day']??$parts['day_short']??[];$night=$parts['night']??$parts['night_short']??[];$daily['time'][]=(string)($forecast['date']??'');$daily['weather_code'][]=yandex_condition_code((string)($day['condition']??$night['condition']??''));$daily['temperature_2m_max'][]=$day['temp_max']??$day['temp_avg']??0;$daily['temperature_2m_min'][]=$night['temp_min']??$night['temp_avg']??0;$daily['precipitation_probability_max'][]=$day['prec_prob']??0;$daily['precipitation_sum'][]=$day['prec_mm']??0;foreach((array)($forecast['hours']??[]) as $h){$hourly['time'][]=(string)($h['hour_ts']??'');$hourly['temperature_2m'][]=$h['temp']??0;$hourly['precipitation_probability'][]=$h['prec_prob']??0;$hourly['precipitation'][]=$h['prec_mm']??0;$hourly['weather_code'][]=yandex_condition_code((string)($h['condition']??''));}}$data=['place'=>$place,'current'=>$current,'hourly'=>$hourly,'daily'=>$daily,'timezone'=>$api['info']['tzinfo']['name']??'auto','provider'=>'yandex','fetched_at'=>date('c')];weather_cache_write($cache,['cached_at'=>time(),'data'=>$data]);return $data;}catch(Throwable $e){if(is_array($stale)){$stale['stale']=true;return $stale;}throw $e;}
}


/* KOVCHEG CMS runtime services. */
function registration_mode(): string { $mode=(string)setting('registration_mode','closed');return in_array($mode,['closed','email_approval','email_auto'],true)?$mode:'closed'; }
function registration_captcha_provider(): string { $provider=(string)setting('captcha_provider','builtin');return in_array($provider,['builtin','turnstile'],true)?$provider:'builtin'; }
function registration_captcha_prepare(): array {
    $provider=registration_captcha_provider();
    $siteKey=trim((string)setting('turnstile_site_key',''));
    $secret=trim(secret_setting('turnstile_secret_key',''));
    if($provider==='turnstile'&&$siteKey!==''&&$secret!==''){
        $_SESSION['registration_captcha_provider']='turnstile';
        unset($_SESSION['registration_captcha']);
        return ['provider'=>'turnstile','site_key'=>$siteKey];
    }
    $a=random_int(2,12);$b=random_int(2,12);
    $_SESSION['registration_captcha_provider']='builtin';
    $_SESSION['registration_captcha']=['answer'=>(string)($a+$b),'expires'=>time()+600];
    return ['provider'=>'builtin','question'=>$a.' + '.$b.' = ?'];
}
function registration_captcha_validate(array $input): bool {
    if(trim((string)($input['website']??''))!=='')return false;
    $provider=(string)($_SESSION['registration_captcha_provider']??'builtin');
    unset($_SESSION['registration_captcha_provider']);
    if($provider==='turnstile'){
        $token=trim((string)($input['cf-turnstile-response']??''));
        $secret=trim(secret_setting('turnstile_secret_key',''));
        if($token===''||$secret==='')return false;
        $payload=http_build_query(['secret'=>$secret,'response'=>$token,'remoteip'=>(string)($_SERVER['REMOTE_ADDR']??'')]);$response=false;
        if(function_exists('curl_init')){
            $ch=curl_init('https://challenges.cloudflare.com/turnstile/v0/siteverify');
            curl_setopt_array($ch,[CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>$payload,CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>8,CURLOPT_CONNECTTIMEOUT=>4,CURLOPT_HTTPHEADER=>['Content-Type: application/x-www-form-urlencoded']]);
            $response=curl_exec($ch);curl_close($ch);
        }else{
            $response=@file_get_contents('https://challenges.cloudflare.com/turnstile/v0/siteverify',false,stream_context_create(['http'=>['method'=>'POST','header'=>'Content-Type: application/x-www-form-urlencoded','content'=>$payload,'timeout'=>8,'ignore_errors'=>true]]));
        }
        $json=json_decode((string)$response,true);
        return is_array($json)&&!empty($json['success']);
    }
    $session=$_SESSION['registration_captcha']??null;unset($_SESSION['registration_captcha']);
    return is_array($session)&&(int)($session['expires']??0)>=time()&&hash_equals((string)($session['answer']??''),trim((string)($input['captcha_answer']??'')));
}
function registration_rate_key(string $email): string { return hash('sha256','register|'.mb_lower(trim($email)).'|'.($_SERVER['REMOTE_ADDR']??'')); }
function registration_rate_check(string $email): void {
    $key=registration_rate_key($email);try{$row=DB::one('SELECT * FROM auth_rate_limits WHERE rate_key=?',[$key]);if($row&&!empty($row['blocked_until'])&&strtotime((string)$row['blocked_until'])>time())abort(429,'Слишком много попыток регистрации. Повторите позже.');}catch(Throwable){}
}
function registration_rate_fail(string $email): void {
    $key=registration_rate_key($email);try{DB::run("INSERT INTO auth_rate_limits (rate_key,attempts,window_started_at,blocked_until,updated_at) VALUES (?,1,CURRENT_TIMESTAMP,NULL,CURRENT_TIMESTAMP) ON DUPLICATE KEY UPDATE attempts=IF(window_started_at<DATE_SUB(CURRENT_TIMESTAMP,INTERVAL 30 MINUTE),1,attempts+1),window_started_at=IF(window_started_at<DATE_SUB(CURRENT_TIMESTAMP,INTERVAL 30 MINUTE),CURRENT_TIMESTAMP,window_started_at),blocked_until=IF(attempts+1>=8,DATE_ADD(CURRENT_TIMESTAMP,INTERVAL 30 MINUTE),blocked_until),updated_at=CURRENT_TIMESTAMP",[$key]);}catch(Throwable){}
}
function registration_rate_success(string $email): void { try{DB::run('DELETE FROM auth_rate_limits WHERE rate_key=?',[registration_rate_key($email)]);}catch(Throwable){} }
