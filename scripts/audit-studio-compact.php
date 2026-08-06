<?php

declare(strict_types=1);

$root=dirname(__DIR__);$failures=[];
$require=static function(bool $condition,string $message)use(&$failures):void{if(!$condition)$failures[]=$message;};
$read=static function(string $path)use($root):string{$data=@file_get_contents($root.'/'.$path);return is_string($data)?$data:'';};

$bootstrap=$read('app/bootstrap.php');
$layout=$read('views/studio/layout.php');
$editor=$read('views/studio/wp-editor.php');
$script=$read('assets/js/blog-classic-editor.js');
$compact=$read('assets/css/blog-studio-compact.css');
$simple=$read('assets/css/blog-studio-simple.css');
$wordpress=$read('assets/css/blog-studio-wordpress.css');
$routes=$read('routes/blog-ux-fixes.php');
$entryRoutes=$read('routes/blog-entry-routing.php');
$index=$read('index.php');

$appVersion='';
if(preg_match("/const APP_VERSION = '([^']+)';/",$bootstrap,$match)){$appVersion=(string)$match[1];$require(version_compare($appVersion,'3.6.0','>='),'APP_VERSION должен быть 3.6.0 или новее.');}else{$require(false,'APP_VERSION не найден.');}
if(preg_match("/const ASSET_REVISION = '([^']+)';/",$bootstrap,$match))$require($appVersion!==''&&str_starts_with((string)$match[1],$appVersion.'-'),'Неверная ревизия assets.');else $require(false,'ASSET_REVISION не найден.');
$require(str_contains($bootstrap,"frame-src 'self'"),'CSP должен разрешать локальный предпросмотр iframe.');

$classicPos=strpos($layout,'blog-classic-editor.css');$compactPos=strpos($layout,'blog-studio-compact.css');$simplePos=strpos($layout,'blog-studio-simple.css');$wpPos=strpos($layout,'blog-studio-wordpress.css');
$require($compactPos!==false&&$simplePos!==false&&$wpPos!==false,'Не все компактные stylesheet подключены.');
$require($classicPos!==false&&$compactPos>$classicPos&&$simplePos>$compactPos&&$wpPos>$simplePos,'Стили Studio подключены в неверном порядке.');
foreach(["shell.addEventListener('click'",'openMediaModal','openPreview','inlineUploadUrl','data-inline-media-upload'] as $token)$require(str_contains($script,$token),'В JavaScript отсутствует исправление: '.$token);
foreach(['.classic-editor-visual{min-height:225px','.studio-topbar{flex-basis:54px','.classic-editor-inline-upload'] as $token)$require(str_contains($compact,$token),'В compact CSS отсутствует правило: '.$token);
foreach(['.studio-body--simple .editor-layout','.studio-body--simple .media-picker--compact','.studio-body--simple .menu-layout'] as $token)$require(str_contains($simple,$token),'В simple CSS отсутствует правило: '.$token);
foreach(['.studio-body--wordpress .wp-editor-layout','.studio-body--wordpress .wp-entry-table','.studio-body--wordpress .wp-menu-sources'] as $token)$require(str_contains($wordpress,$token),'В WordPress CSS отсутствует правило: '.$token);
$require(str_contains($editor,'wp-editor-layout'),'Новый редактор не использует компактную сетку.');
foreach(["/studio/content/{id}/preview","/studio/media/upload-inline"] as $token)$require(str_contains($routes,$token),'В UX-маршрутах отсутствует: '.$token);
$require(str_contains($entryRoutes,"/blog/{slug}")&&str_contains($entryRoutes,"/page/{slug}"),'В единых маршрутах отсутствуют записи или страницы.');
$require(!str_contains($entryRoutes,"/portfolio/{slug}"),'Портфолио не должно оставаться в основном content model.');
$wpPosRoute=strpos($index,'routes/blog-wordpress-mode.php');$entryPos=strpos($index,'routes/blog-entry-routing.php');$uxPos=strpos($index,'routes/blog-ux-fixes.php');$blogPos=strpos($index,'routes/blog.php');
$require($wpPosRoute!==false&&$entryPos!==false&&$uxPos!==false&&$blogPos!==false&&$wpPosRoute<$entryPos&&$entryPos<$uxPos&&$uxPos<$blogPos,'Маршруты WordPress/entry/UX должны быть перед legacy blog-маршрутами.');
foreach([$compact,$simple,$wordpress] as $css)$require(substr_count($css,'{')===substr_count($css,'}'),'В CSS нарушен баланс скобок.');
if($failures){foreach($failures as $failure)fwrite(STDERR,"FAIL: {$failure}\n");exit(1);}echo "Studio compact UX audit OK\n";
