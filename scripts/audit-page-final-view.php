<?php

declare(strict_types=1);

$root=dirname(__DIR__);$errors=[];
$read=static function(string $path)use($root,&$errors):string{$data=@file_get_contents($root.'/'.$path);if(!is_string($data)){$errors[]='Не удалось прочитать '.$path;return '';}return $data;};
$expect=static function(string $content,string $needle,string $message)use(&$errors):void{if(!str_contains($content,$needle))$errors[]=$message;};

$bootstrap=$read('app/bootstrap.php');
$routes=$read('routes/blog-entry-routing.php');
$preview=$read('routes/blog-ux-fixes.php');
$page=$read('themes/kovcheg-portal/page.php');
$layout=$read('themes/kovcheg-portal/layout.php');
$pageCss=$read('themes/kovcheg-portal/assets/page.css');
$scrollCss=$read('themes/kovcheg-portal/assets/public-page-scroll.css');
$editor=$read('views/studio/wp-editor.php');
$editorJs=$read('assets/js/blog-classic-editor.js');

$expect($bootstrap,"const APP_VERSION = '3.7.0';",'Версия 3.7.0 не установлена.');
$expect($bootstrap,"const ASSET_REVISION = '3.7.0-page-category-core';",'Неверная ревизия assets.');
$expect($routes,"Blog::render('page'",'Итоговая страница не использует Page view.');
$expect($preview,"Blog::render('page'",'Предпросмотр не использует Page view.');
foreach(['site-page-breadcrumbs','site-page-title-row','site-page-content','site-page-rubrics','site-page-related','site-page-comments'] as $token)$expect($page,$token,'В Page view отсутствует '.$token);
$expect($layout,'in_array($pageType,[\'entry\',\'page\'],true)','Page view не получает document mode.');
$expect($layout,'$pageType===\'page\'?\' blog-theme-page\'','Page view не получает отдельный класс.');
$expect($scrollCss,'html:has(body.blog-theme-document)','Для документа не включена естественная прокрутка.');
foreach(['.site-page{','.site-page-content{','.site-page-cover{','.site-page-related,','@media(max-width:560px)'] as $token)$expect($pageCss,$token,'В CSS страницы отсутствует '.$token);
foreach(['data-entry-public-url','data-copy-public-url','Добавить в меню'] as $token)$expect($editor,$token,'В редакторе отсутствует '.$token);
$expect($editorJs,"frame.setAttribute('scrolling', 'yes')",'Iframe предпросмотра не прокручивается.');
if(substr_count($pageCss,'{')!==substr_count($pageCss,'}'))$errors[]='В CSS страницы нарушен баланс скобок.';
if($errors){foreach($errors as $error)echo '::error::'.$error.PHP_EOL;fwrite(STDERR,"Public Page view audit failed:\n- ".implode("\n- ",$errors)."\n");exit(1);}echo "Public Page view audit OK\n";
