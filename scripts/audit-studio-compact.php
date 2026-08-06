<?php

declare(strict_types=1);

$root=dirname(__DIR__);$failures=[];
$require=static function(bool $condition,string $message)use(&$failures):void{if(!$condition)$failures[]=$message;};
$read=static function(string $path)use($root):string{$data=@file_get_contents($root.'/'.$path);return is_string($data)?$data:'';};

$bootstrap=$read('app/bootstrap.php');
$layout=$read('views/studio/layout.php');
$editor=$read('views/studio/wp-editor.php');
$pages=$read('views/studio/entries-index.php');
$categories=$read('views/studio/categories.php');
$script=$read('assets/js/blog-classic-editor.js');
$compact=$read('assets/css/blog-studio-compact.css');
$pageCss=$read('assets/css/blog-studio-pages.css');
$routes=$read('routes/blog-ux-fixes.php');

$appVersion='';
if(preg_match("/const APP_VERSION = '([^']+)';/",$bootstrap,$match)){$appVersion=(string)$match[1];$require(version_compare($appVersion,'3.7.0','>='),'APP_VERSION должен быть 3.7.0 или новее.');}else{$require(false,'APP_VERSION не найден.');}
$require(str_contains($bootstrap,"frame-src 'self'"),'CSP должен разрешать локальный preview iframe.');
$require(str_contains($layout,'blog-studio-pages.css'),'Стили Pages Studio не подключены.');
$require(str_contains($layout,'studio-body--pages'),'Не включён режим Pages Studio.');
$require(!str_contains($layout,"'posts'=>['Записи'"),'В меню остались Записи.');
$require(str_contains($layout,'Добавить страницу'),'Нет быстрой кнопки добавления страницы.');
$require(str_contains($editor,'page-only-editor'),'Редактор не использует компактный Page layout.');
$require(str_contains($pages,'page-list-card'),'Список страниц не использует компактные карточки.');
$require(str_contains($categories,'categories-layout--pages'),'Рубрики не используют новый layout.');
foreach(["shell.addEventListener('click'",'openMediaModal','openPreview','inlineUploadUrl'] as $token)$require(str_contains($script,$token),'В JavaScript отсутствует '.$token);
foreach(['.classic-editor-visual{min-height:225px','.studio-topbar{flex-basis:54px'] as $token)$require(str_contains($compact,$token),'В compact CSS отсутствует '.$token);
foreach(['.page-list-card{','.page-only-editor','.categories-layout--pages','@media(max-width:760px)'] as $token)$require(str_contains($pageCss,$token),'В Pages CSS отсутствует '.$token);
$require(str_contains($routes,"/studio/content/{id}/preview"),'Нет Studio Preview.');
$require(str_contains($routes,"/studio/media/upload-inline"),'Нет inline upload.');
foreach([$compact,$pageCss] as $css)$require(substr_count($css,'{')===substr_count($css,'}'),'В CSS нарушен баланс скобок.');
if($failures){foreach($failures as $failure)fwrite(STDERR,"FAIL: {$failure}\n");exit(1);}echo "Compact Pages Studio audit OK\n";
