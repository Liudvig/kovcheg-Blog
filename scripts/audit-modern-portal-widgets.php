<?php

declare(strict_types=1);

$root=dirname(__DIR__);$errors=[];
$read=static function(string $path)use($root,&$errors):string{$file=$root.'/'.$path;$content=is_file($file)?file_get_contents($file):false;if($content===false){$errors[]='Не удалось прочитать '.$path;return '';}return $content;};
$expect=static function(string $content,string $needle,string $message)use(&$errors):void{if(!str_contains($content,$needle))$errors[]=$message;};

$bootstrap=$read('app/bootstrap.php');
$route=$read('routes/blog-layout.php');
$view=$read('views/studio/widgets.php');
$studioCss=$read('assets/css/blog-studio-unified.css');
$portalLayout=$read('themes/kovcheg-portal/layout.php');
$portalCss=$read('themes/kovcheg-portal/assets/theme.css');
$theme=json_decode($read('themes/kovcheg-portal/theme.json'),true);
$module=$read('modules/portal-media-widgets/bootstrap.php');
$moduleCss=$read('modules/portal-media-widgets/assets/widgets.css');
$moduleJs=$read('modules/portal-media-widgets/assets/widgets.js');
$migration=$read('migrations/20260722_blog_portal_widgets.sql');

$expect($bootstrap,"const APP_VERSION = '3.5.1';",'Версия приложения должна быть 3.5.1.');
$expect($bootstrap,'https://vk.com https://vkvideo.ru','CSP не разрешает доверенное встраивание VK Видео.');
$expect($route,'$state[\'currentLayout\'] = $state[\'layout\'];','Маршрут не устраняет конфликт переменной layout.');
$expect($route,'unset($state[\'layout\']);','Старое имя layout остаётся в данных Studio.');
$expect($view,'$currentLayout','Страница виджетов не использует currentLayout.');
if(str_contains($view,'$layout[\'id\']'))$errors[]='Страница виджетов всё ещё обращается к конфликтующей переменной layout.';
$expect($studioCss,'.studio-footer{position:sticky;bottom:0','Подвал Studio не закреплён снизу.');
$expect($studioCss,'.studio-sidebar{position:fixed','Левая колонка Studio не зафиксирована.');
$expect($studioCss,'.widget-layout-toolbar{position:sticky','Панель сохранения схемы не закреплена.');
$expect($portalLayout,'portal-sidebar__scroll','Portal не использует отдельную прокрутку закреплённых колонок.');
$expect($portalCss,'.portal-header{position:sticky','Шапка Portal не закреплена.');
$expect($portalCss,'.portal-sidebar{min-width:0;position:sticky','Боковые колонки Portal не закреплены.');
$expect($portalCss,'.portal-footer{position:fixed','Подвал Portal не закреплён.');
$expect($portalCss,'font-family:Inter','Portal не переведён на современную типографику.');
$expect($module,"portal.photo-carousel",'Не зарегистрирована фотокарусель.');
$expect($module,"portal.video-carousel",'Не зарегистрирована видеокарусель.');
$expect($module,"portal.content-slider",'Не зарегистрирован слайдер контента.');
$expect($module,'youtube-nocookie.com','Видеокарусель не поддерживает YouTube.');
$expect($module,'vkvideo','Видеокарусель не поддерживает VK Видео.');
$expect($moduleCss,'.pmw-carousel','Отсутствуют стили каруселей.');
$expect($moduleJs,'data-pmw-carousel','Отсутствует JavaScript каруселей.');
$expect($migration,"'portal-media-widgets'",'Миграция не включает модуль каруселей.');
$expect($migration,"version='1.1.0'",'Миграция не обновляет версию Portal.');
if(!is_array($theme)||($theme['version']??'')!=='1.1.0')$errors[]='Версия темы Portal должна быть 1.1.0.';
if(!is_array($theme)||($theme['min_core']??'')!=='3.5.1')$errors[]='Portal 1.1.0 должен требовать KOVCHEG Blog 3.5.1.';

if($errors){fwrite(STDERR,"Modern Portal / widgets audit failed:\n- ".implode("\n- ",$errors)."\n");exit(1);}echo "Modern Portal and carousel widgets audit passed.\n";
