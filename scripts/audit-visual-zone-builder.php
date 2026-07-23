<?php

declare(strict_types=1);

$root=dirname(__DIR__);$errors=[];
$read=static function(string $path)use($root,&$errors):string{$file=$root.'/'.$path;$content=is_file($file)?file_get_contents($file):false;if($content===false){$errors[]='Не удалось прочитать '.$path;return '';}return $content;};
$expect=static function(string $content,string $needle,string $message)use(&$errors):void{if(!str_contains($content,$needle))$errors[]=$message;};

$bootstrap=$read('app/bootstrap.php');
$studioLayout=$read('views/studio/layout.php');
$widgets=$read('views/studio/widgets.php');
$builderCss=$read('assets/css/blog-zone-builder.css');
$widgetsJs=$read('assets/js/blog-widgets.js');
$portalLayout=$read('themes/kovcheg-portal/layout.php');
$fixedShell=$read('themes/kovcheg-portal/assets/fixed-shell.css');
$home=$read('themes/kovcheg-portal/home.php');
$archive=$read('themes/kovcheg-portal/archive.php');
$theme=json_decode($read('themes/kovcheg-portal/theme.json'),true);
$migration=$read('migrations/20260722_blog_visual_zone_builder.sql');

$expect($bootstrap,"const APP_VERSION = '3.5.3';",'Версия приложения должна быть 3.5.3.');
$expect($bootstrap,"const ASSET_REVISION = '3.5.3-portal-login-routing';",'ASSET_REVISION должен соответствовать 3.5.3.');
$expect($studioLayout,'blog-zone-builder.css','Studio не подключает стили визуального конструктора зон.');
$expect($widgets,'widget-blueprint-header','В макете отсутствует визуальная шапка.');
$expect($widgets,'widget-blueprint-body','В макете отсутствует трёхколоночная область.');
$expect($widgets,'widget-blueprint-column--left','В макете отсутствует левая колонка.');
$expect($widgets,'widget-blueprint-center','В макете отсутствует центральная область.');
$expect($widgets,'widget-blueprint-column--right','В макете отсутствует правая колонка.');
$expect($widgets,'widget-blueprint-footer','В макете отсутствует визуальный подвал.');
$expect($widgets,"\$renderZone('header.top'",'Зона header.top не находится в визуальной шапке.');
$expect($widgets,"\$renderZone('layout.left'",'Зона layout.left не находится слева.');
$expect($widgets,"\$renderZone('layout.right'",'Зона layout.right не находится справа.');
$expect($widgets,"\$renderZone('footer.bottom'",'Зона footer.bottom не находится снизу.');
$expect($builderCss,'.widget-blueprint-body{display:grid','Визуальная схема страницы не использует сетку.');
$expect($builderCss,'grid-template-columns:minmax(180px,23%) minmax(360px,1fr) minmax(180px,23%)','Схема не отображает левую, центральную и правую колонки.');
$expect($widgetsJs,'refreshRegionStates','Drag & Drop не обновляет состояние зон и колонок.');
$expect($widgetsJs,"region.classList.toggle('has-widgets'",'Колонки не выделяются после установки виджета.');

if(str_contains($home,'portal-masthead'))$errors[]='Автоматический верхний hero-блок остался на главной.';
if(str_contains($archive,'portal-archive-head'))$errors[]='Автоматический верхний блок остался в архивах.';
$expect($portalLayout,'fixed-shell.css','Portal не подключает фиксированную оболочку.');
$expect($portalLayout,'portal-sidebar--populated','Заполненные боковые колонки не получают отдельный класс.');
$expect($fixedShell,'body.blog-theme-portal{height:100vh','Portal не закреплён на высоту окна.');
$expect($fixedShell,'grid-template-rows:auto minmax(0,1fr) auto','Шапка, содержимое и подвал не разделены на неподвижную оболочку.');
$expect($fixedShell,'.portal-content{height:100%','Центральная область не имеет отдельную прокрутку.');
$expect($fixedShell,'.portal-sidebar{position:relative','Боковые колонки не закреплены внутри оболочки.');
$expect($fixedShell,'.portal-sidebar--left','Левая колонка не прорисована полностью.');
$expect($fixedShell,'.portal-sidebar--right','Правая колонка не прорисована полностью.');
$expect($migration,"version='1.2.0'",'Миграция не обновляет Portal до 1.2.0.');
if(!is_array($theme)||($theme['version']??'')!=='1.2.0')$errors[]='Версия темы Portal должна быть 1.2.0.';
if(!is_array($theme)||($theme['min_core']??'')!=='3.5.2')$errors[]='Portal 1.2.0 должен требовать KOVCHEG Blog 3.5.2.';

if($errors){fwrite(STDERR,"Visual Zone Builder audit failed:\n- ".implode("\n- ",$errors)."\n");exit(1);}echo "Visual Zone Builder audit passed.\n";