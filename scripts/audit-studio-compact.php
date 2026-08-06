<?php

declare(strict_types=1);

$root=dirname(__DIR__);$failures=[];
$require=static function(bool $condition,string $message)use(&$failures):void{if(!$condition)$failures[]=$message;};
$read=static function(string $path)use($root):string{$data=@file_get_contents($root.'/'.$path);return is_string($data)?$data:'';};

$bootstrap=$read('app/bootstrap.php');
$layout=$read('views/studio/layout.php');
$script=$read('assets/js/blog-classic-editor.js');
$compact=$read('assets/css/blog-studio-compact.css');
$simple=$read('assets/css/blog-studio-simple.css');
$routes=$read('routes/blog-ux-fixes.php');
$index=$read('index.php');

$appVersion='';
if(preg_match("/const APP_VERSION = '([^']+)';/",$bootstrap,$match)){$appVersion=(string)$match[1];$require(version_compare($appVersion,'3.5.7','>='),'APP_VERSION должен быть 3.5.7 или новее.');}else{$require(false,'APP_VERSION не найден.');}
if(preg_match("/const ASSET_REVISION = '([^']+)';/",$bootstrap,$match))$require($appVersion!==''&&str_starts_with((string)$match[1],$appVersion.'-'),'Неверная ревизия assets.');else $require(false,'ASSET_REVISION не найден.');
$require(str_contains($bootstrap,"frame-src 'self'"),'CSP должен разрешать локальный предпросмотр iframe.');

$classicPos=strpos($layout,'blog-classic-editor.css');$compactPos=strpos($layout,'blog-studio-compact.css');$simplePos=strpos($layout,'blog-studio-simple.css');
$require($compactPos!==false,'Компактный stylesheet не подключён.');
$require($simplePos!==false,'Простой stylesheet Studio не подключён.');
$require($classicPos!==false&&$compactPos>$classicPos&&$simplePos>$compactPos,'Стили Studio подключены в неверном порядке.');
foreach(["shell.addEventListener('click'",'openMediaModal','openPreview','inlineUploadUrl','data-inline-media-upload'] as $token)$require(str_contains($script,$token),'В JavaScript отсутствует исправление: '.$token);
foreach(['.classic-editor-visual{min-height:225px','.studio-topbar{flex-basis:54px','.classic-editor-inline-upload'] as $token)$require(str_contains($compact,$token),'В compact CSS отсутствует правило: '.$token);
foreach(['.studio-body--simple .editor-layout','.studio-body--simple .media-picker--compact','.studio-body--simple .menu-layout'] as $token)$require(str_contains($simple,$token),'В simple CSS отсутствует правило: '.$token);
foreach(["/studio/content/{id}/preview","/studio/media/upload-inline","/portfolio/{slug}"] as $token)$require(str_contains($routes,$token),'В UX-маршрутах отсутствует: '.$token);
$uxPos=strpos($index,'routes/blog-ux-fixes.php');$blogPos=strpos($index,'routes/blog.php');
$require($uxPos!==false&&$blogPos!==false&&$uxPos<$blogPos,'UX-маршруты должны быть перед публичными blog-маршрутами.');
$require(substr_count($compact,'{')===substr_count($compact,'}'),'В compact CSS нарушен баланс скобок.');
$require(substr_count($simple,'{')===substr_count($simple,'}'),'В simple CSS нарушен баланс скобок.');
if($failures){foreach($failures as $failure)fwrite(STDERR,"FAIL: {$failure}\n");exit(1);}echo "Studio compact UX audit OK\n";
