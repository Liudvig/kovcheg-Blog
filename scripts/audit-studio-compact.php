<?php

declare(strict_types=1);

$root=dirname(__DIR__);$failures=[];
$require=static function(bool $condition,string $message)use(&$failures):void{if(!$condition)$failures[]=$message;};
$read=static function(string $path)use($root):string{$data=@file_get_contents($root.'/'.$path);return is_string($data)?$data:'';};

$bootstrap=$read('app/bootstrap.php');
$layout=$read('views/studio/layout.php');
$editor=$read('views/studio/content-editor.php');
$contentList=$read('views/studio/content-list.php');
$categories=$read('views/studio/categories.php');
$menus=$read('views/studio/menus.php');
$script=$read('assets/js/blog-classic-editor.js');
$compact=$read('assets/css/blog-studio-compact.css');
$pageCss=$read('assets/css/blog-studio-pages.css');
$menuCss=$read('assets/css/blog-studio-menus.css');
$routes=$read('routes/blog-content-model.php');
$uxRoutes=$read('routes/blog-ux-fixes.php');

$appVersion='';
if(preg_match("/const APP_VERSION = '([^']+)';/",$bootstrap,$match)){$appVersion=(string)$match[1];$require(version_compare($appVersion,'3.8.0','>='),'APP_VERSION должен быть 3.8.0 или новее.');}else{$require(false,'APP_VERSION не найден.');}
$require(str_contains($bootstrap,"frame-src 'self'"),'CSP должен разрешать локальный preview iframe.');
foreach(['blog-studio-pages.css','blog-studio-menus.css','studio-body--pages'] as $token)$require(str_contains($layout,$token),'Studio layout не содержит '.$token);
$require(str_contains($layout,"'posts'=>['Записи'"),'В меню отсутствуют Записи.');
$require(str_contains($layout,"'categories'=>['Рубрики'"),'В меню отсутствуют Рубрики.');
$require(str_contains($layout,"'pages'=>['Страницы'"),'В меню отсутствуют Страницы.');
$require(str_contains($layout,'Добавить запись'),'Нет быстрой кнопки добавления записи.');
$require(str_contains($layout,'Страница'),'Нет быстрой кнопки добавления страницы.');
$require(str_contains($editor,'page-only-editor'),'Редактор не использует компактный layout документа.');
$require(str_contains($editor,'$isPost'),'Редактор не различает Записи и Страницы.');
$require(str_contains($contentList,'page-list-card'),'Список материалов не использует компактные карточки.');
$require(str_contains($contentList,'$isPost=$type===\'post\';'),'Список не различает Записи и Страницы.');
$require(str_contains($categories,'categories-layout--pages'),'Рубрики не используют компактный layout.');
$require(str_contains($menus,'cms-menu-layout'),'Меню не использует компактный layout.');
foreach(["shell.addEventListener('click'",'openMediaModal','openPreview','inlineUploadUrl'] as $token)$require(str_contains($script,$token),'В JavaScript отсутствует '.$token);
foreach(['.classic-editor-visual{min-height:225px','.studio-topbar{flex-basis:54px'] as $token)$require(str_contains($compact,$token),'В compact CSS отсутствует '.$token);
foreach(['.page-list-card{','.page-only-editor','.categories-layout--pages','@media(max-width:760px)'] as $token)$require(str_contains($pageCss,$token),'В Pages CSS отсутствует '.$token);
$require(str_contains($menuCss,'.cms-menu-layout'),'Нет компактного оформления меню.');
$require(str_contains($routes,"/studio/content/{id}/preview"),'Нет Studio Preview.');
$require(str_contains($uxRoutes,"/studio/media/upload-inline"),'Нет inline upload.');
foreach([$compact,$pageCss,$menuCss] as $css)$require(substr_count($css,'{')===substr_count($css,'}'),'В CSS нарушен баланс скобок.');
if($failures){foreach($failures as $failure)fwrite(STDERR,"FAIL: {$failure}\n");exit(1);}echo "Compact Posts and Pages Studio audit OK\n";