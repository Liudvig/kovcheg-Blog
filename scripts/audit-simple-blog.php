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
$editor=$read('views/studio/editor.php');
$menus=$read('views/studio/menus.php');
$account=$read('views/account-shell.php');
$profile=$read('views/profile.php');
$profileCss=$read('assets/css/blog-profile-portal.css');
$simpleCss=$read('assets/css/blog-studio-simple.css');
$routes=$read('routes/blog-simple-mode.php');
$index=$read('index.php');

$expect($bootstrap,"const APP_VERSION = '3.5.8';",'Версия приложения должна быть 3.5.8.');
$expect($bootstrap,"const ASSET_REVISION = '3.5.8-simple-blog-ui';",'Неверная ревизия статических файлов.');
$expect($home,'portal-post-card','Главная не использует компактную ленту публикаций.');
if(str_contains($home,'portal-lead-story'))$errors[]='На главной остался растянутый ведущий материал.';
$expect($archive,'portal-post-card','Архив не использует компактную ленту.');
if(str_contains($archive,'portal-archive-card--lead'))$errors[]='В архиве осталась огромная первая карточка.';
$expect($compact,'grid-template-columns:210px minmax(0,1fr)','Не найдена компактная карточка с миниатюрой.');
$expect($compact,'max-height:360px','Изображение отдельного материала не ограничено по высоте.');
$expect($studio,"'content'=>['Материалы'",'В Studio отсутствует раздел материалов.');
foreach(["'patterns'=>","'presets'=>","'widgets'=>","'modules'=>"] as $hidden){if(str_contains($studio,$hidden))$errors[]='В обычном меню Studio остался лишний раздел '.$hidden;}
if(str_contains($editor,'data-editor-tab="builder"'))$errors[]='В редакторе осталась вкладка конструктора.';
$expect($editor,'data-classic-visual','Классический редактор отсутствует.');
$expect($editor,'<summary>Дополнительно</summary>','Редкие поля не свёрнуты в дополнительный раздел.');
if(str_contains($menus,'name="sort_order" value="0"'))$errors[]='В меню всё ещё показано ручное поле порядка.';
$expect($account,'portal-account-header','Личный кабинет не переведён на стиль Portal.');
$expect($profile,'portal-profile-shell','Профиль не переведён на стиль Portal.');
$expect($profileCss,'.portal-profile-grid','Нет оформления нового профиля.');
$expect($simpleCss,'.studio-body--simple .editor-layout','Нет компактного режима Studio.');
$expect($routes,"\$router->get('/studio/patterns'",'Не зарегистрирован редирект старого конструктора.');
$expect($routes,"redirect('/studio/content')",'Старый конструктор не перенаправляется к материалам.');
$expect($index,"routes/blog-simple-mode.php",'Простой режим не подключён к маршрутам.');

foreach([$compact,$profileCss,$simpleCss] as $css){if(substr_count($css,'{')!==substr_count($css,'}'))$errors[]='В CSS нарушен баланс фигурных скобок.';}
if($errors){fwrite(STDERR,"Simple blog audit failed:\n- ".implode("\n- ",$errors)."\n");exit(1);}echo "Simple blog UI audit OK\n";
