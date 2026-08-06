<?php

declare(strict_types=1);

$root=dirname(__DIR__);$errors=[];
$read=static function(string $path)use($root,&$errors):string{$data=@file_get_contents($root.'/'.$path);if(!is_string($data)){$errors[]='Не удалось прочитать '.$path;return '';}return $data;};
$expect=static function(string $content,string $needle,string $message)use(&$errors):void{if(!str_contains($content,$needle))$errors[]=$message;};
$reject=static function(string $content,string $needle,string $message)use(&$errors):void{if(str_contains($content,$needle))$errors[]=$message;};

$bootstrap=$read('app/bootstrap.php');
$home=$read('themes/kovcheg-portal/home.php');
$archive=$read('themes/kovcheg-portal/archive.php');
$page=$read('themes/kovcheg-portal/page.php');
$layout=$read('views/studio/layout.php');
$editor=$read('views/studio/wp-editor.php');
$studioCss=$read('assets/css/blog-studio-pages.css');
$homeCss=$read('themes/kovcheg-portal/assets/site-home.css');
$pageCss=$read('themes/kovcheg-portal/assets/page.css');
$categoryCss=$read('themes/kovcheg-portal/assets/category.css');

if(preg_match("/const APP_VERSION = '([^']+)';/",$bootstrap,$match)!==1||version_compare((string)$match[1],'3.7.0','<'))$errors[]='Версия должна быть 3.7.0 или новее.';
$expect($home,'site-home-rubrics','Главная не выводит рубрики.');
$expect($home,'site-home-pages','Главная не выводит страницы.');
$expect($archive,'category-page-grid','Рубрика не выводит страницы.');
$expect($page,'site-page-content','Публичная страница не использует новый документ.');
$expect($page,'site-page-related','Нет связанных страниц.');
$expect($layout,"'pages'=>['Страницы'",'В Studio отсутствуют Страницы.');
$expect($layout,"'categories'=>['Рубрики'",'В Studio отсутствуют Рубрики.');
$reject($layout,"'posts'=>['Записи'",'В Studio остались Записи.');
$reject($layout,'Добавить запись','В Studio осталась кнопка записи.');
$expect($editor,'Рубрика — это раздел сайта','Редактор не объясняет рубрики.');
$reject($editor,'value="post"','Редактор позволяет создать запись.');
foreach([$studioCss,$homeCss,$pageCss,$categoryCss] as $css)if(substr_count($css,'{')!==substr_count($css,'}'))$errors[]='Нарушен баланс CSS-скобок.';
if($errors){fwrite(STDERR,"Simple Pages UI audit failed:\n- ".implode("\n- ",$errors)."\n");exit(1);}echo "Simple Pages UI audit OK\n";
