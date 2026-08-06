<?php

declare(strict_types=1);

$root=dirname(__DIR__);
$failures=[];
$require=static function(bool $condition,string $message)use(&$failures):void{if(!$condition)$failures[]=$message;};
$read=static function(string $path)use($root):string{$data=@file_get_contents($root.'/'.$path);return is_string($data)?$data:'';};

$bootstrap=$read('app/bootstrap.php');
$layout=$read('views/studio/layout.php');
$script=$read('assets/js/blog-classic-editor.js');
$compact=$read('assets/css/blog-studio-compact.css');
$routes=$read('routes/blog-ux-fixes.php');
$index=$read('index.php');

$require(str_contains($bootstrap,"const APP_VERSION = '3.5.7';"),'APP_VERSION должен быть 3.5.7.');
$require(str_contains($bootstrap,"const ASSET_REVISION = '3.5.7-studio-compact-fixes';"),'Неверная ревизия assets.');
$require(str_contains($bootstrap,"frame-src 'self'"),'CSP должен разрешать безопасный локальный предпросмотр iframe.');

$classicPos=strpos($layout,'blog-classic-editor.css');
$compactPos=strpos($layout,'blog-studio-compact.css');
$require($compactPos!==false,'Компактный stylesheet не подключён.');
$require($classicPos!==false&&$compactPos>$classicPos,'Компактный stylesheet должен загружаться последним.');

foreach([
    "shell.addEventListener('click'",
    'openMediaModal',
    'openPreview',
    'inlineUploadUrl',
    'data-inline-media-upload',
    "document.querySelector('.classic-editor-intro p')?.remove()",
] as $token)$require(str_contains($script,$token),'В JavaScript отсутствует исправление: '.$token);

foreach([
    '.classic-editor-intro p{display:none',
    '.classic-editor-visual{min-height:225px',
    '.studio-sidebar{width:224px',
    '.studio-topbar{flex-basis:54px',
    '.editor-layout{grid-template-columns:minmax(0,1fr) 278px',
    '.classic-editor-inline-upload',
] as $token)$require(str_contains($compact,$token),'В compact CSS отсутствует правило: '.$token);

foreach([
    "/studio/content/{id}/preview",
    "/studio/media/upload-inline",
    "/portfolio/{slug}",
    "header('Location: '.app_url('/portfolio')",
] as $token)$require(str_contains($routes,$token),'В UX-маршрутах отсутствует: '.$token);

$uxPos=strpos($index,'routes/blog-ux-fixes.php');
$blogPos=strpos($index,'routes/blog.php');
$require($uxPos!==false,'UX-маршруты не подключены.');
$require($blogPos!==false&&$uxPos<$blogPos,'UX-маршруты должны регистрироваться до публичных blog-маршрутов.');

$require(substr_count($compact,'{')===substr_count($compact,'}'),'В compact CSS нарушен баланс фигурных скобок.');
$require(substr_count($script,'{')===substr_count($script,'}'),'В JavaScript нарушен баланс фигурных скобок.');

if($failures){foreach($failures as $failure)fwrite(STDERR,"FAIL: {$failure}\n");exit(1);}
echo "Studio compact UX audit OK\n";
