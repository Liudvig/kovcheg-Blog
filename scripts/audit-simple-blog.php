<?php

declare(strict_types=1);

$root=dirname(__DIR__);$errors=[];
$read=static function(string $path)use($root,&$errors):string{$data=@file_get_contents($root.'/'.$path);if(!is_string($data)){$errors[]='Не удалось прочитать '.$path;return '';}return $data;};
$expect=static function(string $content,string $needle,string $message)use(&$errors):void{if(!str_contains($content,$needle))$errors[]=$message;};

$bootstrap=$read('app/bootstrap.php');
$home=$read('themes/kovcheg-portal/home.php');
$archive=$read('themes/kovcheg-portal/archive.php');
$compact=$read('themes/kovcheg-portal/assets/blog-compact.css');
$studio=$read('views/studio/layout.php');
$editor=$read('views/studio/wp-editor.php');
$menus=$read('views/studio/menus.php');
$account=$read('views/account-shell.php');
$profile=$read('views/profile.php');
$profileCss=$read('assets/css/blog-profile-portal.css');
$simpleCss=$read('assets/css/blog-studio-simple.css');
$wpCss=$read('assets/css/blog-studio-wordpress.css');
$wpRoutes=$read('routes/blog-wordpress-mode.php');
$compat=$read('routes/blog-wordpress-compat.php');
$index=$read('index.php');

$appVersion='';
if(preg_match("/const APP_VERSION = '([^']+)';/",$bootstrap,$match)){$appVersion=(string)$match[1];if(version_compare($appVersion,'3.6.0','<'))$errors[]='Версия приложения должна быть 3.6.0 или новее.';}else{$errors[]='APP_VERSION не найден.';}
if(preg_match("/const ASSET_REVISION = '([^']+)';/",$bootstrap,$match)!==1||$appVersion===''||!str_starts_with((string)$match[1],$appVersion.'-'))$errors[]='ASSET_REVISION должен начинаться с текущей версии.';
$expect($home,'portal-post-card','Главная не использует компактную ленту записей.');
if(str_contains($home,'portfolio')||str_contains($home,'portal-lead-story'))$errors[]='На главной остался портфолио-блок или растянутый материал.';
$expect($archive,'portal-post-card','Архив не использует компактную ленту.');
$expect($compact,'grid-template-columns:210px minmax(0,1fr)','Не найдена компактная карточка с миниатюрой.');
foreach(["'posts'=>['Записи'","'categories'=>['Рубрики'","'pages'=>['Страницы'"] as $item)$expect($studio,$item,'В Studio отсутствует WordPress-раздел '.$item);
foreach(["'content'=>","'patterns'=>","'presets'=>","'widgets'=>","'modules'=>","'growth'=>"] as $hidden){if(str_contains($studio,$hidden))$errors[]='В основном меню Studio остался лишний раздел '.$hidden;}
$expect($editor,'data-classic-visual','Классический редактор отсутствует.');
$expect($editor,'name="type"','Тип записи не зафиксирован скрытым полем.');
if(str_contains($editor,'data-editor-tab="builder"')||str_contains($editor,'value="portfolio"')||str_contains($editor,'name="tags"'))$errors[]='В простом редакторе остались конструктор, портфолио или теги.';
$expect($menus,'target_kind" value="page"','Меню не умеет добавлять страницы.');
$expect($menus,'target_kind" value="category"','Меню не умеет добавлять рубрики.');
$expect($account,'portal-account-header','Личный кабинет не переведён на стиль Portal.');
$expect($profile,'portal-profile-shell','Профиль не переведён на стиль Portal.');
$expect($profileCss,'.portal-profile-grid','Нет оформления нового профиля.');
$expect($simpleCss,'.studio-body--simple .editor-layout','Нет компактного режима Studio.');
$expect($wpCss,'.studio-body--wordpress .wp-editor-layout','Нет лёгкого WordPress-режима Studio.');
foreach(['/studio/posts','/studio/pages'] as $path)$expect($wpRoutes,$path,'Не зарегистрирован маршрут '.$path);
$expect($compat,'/studio/categories','Не зарегистрирован маршрут /studio/categories.');
$expect($index,'routes/blog-wordpress-mode.php','WordPress-режим не подключён к маршрутам.');
if(str_contains($index,'routes/blog-demo.php'))$errors[]='Демо-маршруты всё ещё загружаются в runtime.';
foreach([$compact,$profileCss,$simpleCss,$wpCss] as $css){if(substr_count($css,'{')!==substr_count($css,'}'))$errors[]='В CSS нарушен баланс фигурных скобок.';}
if($errors){fwrite(STDERR,"Simple blog audit failed:\n- ".implode("\n- ",$errors)."\n");exit(1);}echo "Simple blog UI audit OK\n";
