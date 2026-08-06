<?php

declare(strict_types=1);

use Kovcheg\Csrf;
use Kovcheg\Blog\Studio;

require_once BASE_PATH.'/app/BlogStudio.php';

if (!function_exists('kovcheg_brand_remove')) {
    function kovcheg_brand_remove(string $relative): void
    {
        $relative=trim(str_replace('\\','/',$relative));
        if($relative===''||!str_starts_with($relative,'branding/')||str_contains($relative,'..'))return;
        $file=BASE_PATH.'/storage/uploads/'.$relative;
        if(is_file($file))@unlink($file);
    }
}

if (!function_exists('kovcheg_brand_store')) {
    function kovcheg_brand_store(array $file,string $kind): ?string
    {
        $error=(int)($file['error']??UPLOAD_ERR_NO_FILE);
        if($error===UPLOAD_ERR_NO_FILE||trim((string)($file['name']??''))==='')return null;
        if($error!==UPLOAD_ERR_OK)abort(422,'Не удалось загрузить файл бренда.');
        $tmp=(string)($file['tmp_name']??'');
        $size=(int)($file['size']??0);
        if($tmp===''||!is_file($tmp)||$size<1||$size>12*1024*1024)abort(422,'Файл бренда отсутствует или превышает 12 МБ.');

        $token=date('YmdHis').'-'.bin2hex(random_bytes(5));
        if($kind==='logo'){
            $stored=optimize_logo_image($tmp,'branding/logo-'.$token,1600,800,90);
        }elseif($kind==='favicon'){
            $stored=optimize_uploaded_image($tmp,'branding/favicon-'.$token,512,512,90);
        }elseif($kind==='login_background'){
            $stored=optimize_uploaded_image($tmp,'branding/login-background-'.$token,2560,1800,88);
        }else{
            abort(422,'Неизвестный тип файла бренда.');
        }
        return (string)($stored['relative']??'');
    }
}

$router->get('/brand/{kind}', function(array $params): void {
    $kind=(string)($params['kind']??'');
    $map=[
        'logo'=>'logo_path',
        'favicon'=>'favicon_path',
        'login-background'=>'login_background_path',
    ];
    if(!isset($map[$kind]))abort(404,'Файл бренда не найден.');
    $path=trim((string)setting($map[$kind],''));
    if($path===''||!str_starts_with($path,'branding/')||str_contains($path,'..')){
        if($kind==='logo')brand_fallback_svg();
        abort(404,'Файл бренда не найден.');
    }
    $file=BASE_PATH.'/storage/uploads/'.$path;
    if(!is_file($file)){
        if($kind==='logo')brand_fallback_svg();
        abort(404,'Файл бренда не найден.');
    }
    if($kind==='logo'&&!logo_image_is_meaningful($file))brand_fallback_svg();
    $mime=(new finfo(FILEINFO_MIME_TYPE))->file($file)?:'application/octet-stream';
    $etag='"'.hash_file('sha256',$file).'"';
    header('Content-Type: '.$mime);
    header('Content-Length: '.filesize($file));
    header('Cache-Control: public, max-age=86400, stale-while-revalidate=604800');
    header('ETag: '.$etag);
    header('Last-Modified: '.gmdate('D, d M Y H:i:s',filemtime($file)).' GMT');
    if(trim((string)($_SERVER['HTTP_IF_NONE_MATCH']??''))===$etag){http_response_code(304);exit;}
    readfile($file);
    exit;
});

$router->get('/studio/appearance', function (): void {
    Studio::require('themes');
    Studio::render('appearance',[
        'studioSection'=>'appearance',
        'studioTitle'=>'Внешний вид и брендинг',
        'themes'=>Studio::themes(),
        'brand'=>[
            'logo_path'=>(string)setting('logo_path',''),
            'favicon_path'=>(string)setting('favicon_path',''),
            'login_background_path'=>(string)setting('login_background_path',''),
            'accent'=>(string)setting('brand_accent','#2563eb'),
        ],
    ]);
});

$router->post('/studio/appearance', function (): void {
    Studio::require('themes');
    Csrf::validate();

    $theme=(string)($_POST['blog_theme']??'');
    $available=array_column(Studio::themes(),'slug');
    if(!in_array($theme,$available,true))abort(422,'Тема не найдена.');

    $siteName=mb_substr(trim((string)($_POST['site_name']??'')),0,150);
    if($siteName==='')abort(422,'Введите название сайта.');
    set_setting('site_name',$siteName);
    set_setting('blog_tagline',mb_substr(trim((string)($_POST['blog_tagline']??'')),0,300));
    set_setting('site_tagline',mb_substr(trim((string)($_POST['blog_tagline']??'')),0,300));
    set_setting('blog_description',mb_substr(trim((string)($_POST['blog_description']??'')),0,1000));
    set_setting('blog_footer_text',mb_substr(trim((string)($_POST['blog_footer_text']??'')),0,1000));
    set_setting('login_heading',mb_substr(trim((string)($_POST['login_heading']??'')),0,220));
    $accent=strtolower(trim((string)($_POST['brand_accent']??'#2563eb')));
    if(!preg_match('/^#[0-9a-f]{6}$/',$accent))abort(422,'Цвет бренда должен быть указан в формате #2563eb.');
    set_setting('brand_accent',$accent);
    set_setting('blog_theme',$theme);
    \Kovcheg\DB::run('UPDATE themes SET is_active=(slug=?)',[$theme]);

    foreach([
        'logo'=>['file'=>'brand_logo','setting'=>'logo_path'],
        'favicon'=>['file'=>'brand_favicon','setting'=>'favicon_path'],
        'login_background'=>['file'=>'login_background','setting'=>'login_background_path'],
    ] as $kind=>$definition){
        $settingKey=(string)$definition['setting'];
        $old=(string)setting($settingKey,'');
        if(!empty($_POST['remove_'.$kind])){
            kovcheg_brand_remove($old);
            set_setting($settingKey,'');
            continue;
        }
        $uploaded=kovcheg_brand_store((array)($_FILES[(string)$definition['file']]??[]),$kind);
        if($uploaded!==null&&$uploaded!==''){
            kovcheg_brand_remove($old);
            set_setting($settingKey,$uploaded);
        }
    }

    audit('cms.branding.update','site_branding',null,['theme'=>$theme]);
    $_SESSION['flash_success']='Брендинг и оформление сохранены.';
    redirect('/studio/appearance');
});
