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
$matrixCss=$read('themes/kovcheg-portal/assets/layout-matrix.css');
$home=$read('themes/kovcheg-portal/home.php');
$archive=$read('themes/kovcheg-portal/archive.php');
$theme=json_decode($read('themes/kovcheg-portal/theme.json'),true);
$module=$read('modules/layout-matrix/bootstrap.php');
$migration=$read('migrations/20260723_blog_layout_matrix.sql');

$expect($bootstrap,"const APP_VERSION = '3.5.4';",'Версия приложения должна быть 3.5.4.');
$expect($bootstrap,"const ASSET_REVISION = '3.5.4-layout-matrix-builder';",'ASSET_REVISION должен соответствовать 3.5.4.');
$expect($studioLayout,'blog-zone-builder.css','Studio не подключает стили конструктора зон.');
$expect($widgets,'widget-builder-shell--matrix','Макет не использует двухколоночную схему: каталог и рабочая область.');
if(str_contains($widgets,'widget-revisions">'))$errors[]='На странице осталась отдельная правая колонка ревизий.';
$expect($widgets,'widget-catalog--accordion','Каталог виджетов не сворачивается.');
$expect($widgets,'matrix.preheader','Отсутствует сплошная область над шапкой.');
for($i=1;$i<=5;$i++)$expect($widgets,"matrix.header.$i",'Отсутствует секция шапки '.$i.'.');
$expect($widgets,'matrix.postheader','Отсутствует сплошная область под шапкой.');
$expect($widgets,'matrix.banner.top','Отсутствует верхняя баннерная полоса.');
for($i=1;$i<=4;$i++){
    $expect($widgets,"matrix.left.$i",'Отсутствует блок левой колонки '.$i.'.');
    $expect($widgets,"matrix.right.$i",'Отсутствует блок правой колонки '.$i.'.');
}
for($i=1;$i<=12;$i++)$expect($widgets,"matrix.center.$i",'Отсутствует центральный блок '.$i.'.');
$expect($widgets,'matrix.banner.bottom','Отсутствует нижняя баннерная полоса.');
for($i=1;$i<=8;$i++)$expect($widgets,"matrix.footer.$i",'Отсутствует блок подвала '.$i.'.');
$expect($widgets,'matrix-copyright-lock','Отсутствует длинный блок копирайта.');
$expect($builderCss,'grid-template-columns:300px minmax(0,1fr)','Конструктор не отдаёт всю оставшуюся ширину схеме.');
$expect($builderCss,'grid-template-columns:repeat(5','Шапка не делится на пять секций.');
$expect($builderCss,'grid-template-columns:repeat(4','Центр и подвал не делятся по четыре блока.');
$expect($builderCss,'grid-template-rows:repeat(4','Боковые колонки не делятся на четыре вертикальных блока.');
$expect($widgetsJs,'refreshRegionStates','Drag & Drop не обновляет состояния зон.');

if(str_contains($home,'portal-masthead'))$errors[]='Автоматический верхний hero-блок остался на главной.';
if(str_contains($archive,'portal-archive-head'))$errors[]='Автоматический верхний блок остался в архивах.';
$expect($portalLayout,'layout-matrix.css','Portal не подключает матричную оболочку.');
$expect($portalLayout,'portal-matrix-header-grid','Portal не выводит пять секций шапки.');
$expect($portalLayout,'portal-matrix-content-grid','Portal не выводит центральную сетку.');
$expect($portalLayout,'portal-matrix-footer-grid','Portal не выводит подвал 4×2.');
$expect($matrixCss,'body.blog-theme-portal-matrix{height:100vh','Portal не закреплён на высоту окна.');
$expect($matrixCss,'.portal-matrix-sidebar-grid','Боковые колонки не имеют отдельную сетку.');
$expect($matrixCss,'.portal-matrix-content{','Центральная область не имеет отдельную прокрутку.');
$expect($module,"matrix.center.12",'Модуль не регистрирует двенадцатый центральный блок.');
$expect($migration,"version='1.3.0'",'Миграция не обновляет Portal до 1.3.0.');
if(!is_array($theme)||($theme['version']??'')!=='1.3.0')$errors[]='Версия темы Portal должна быть 1.3.0.';
if(!is_array($theme)||($theme['min_core']??'')!=='3.5.4')$errors[]='Portal 1.3.0 должен требовать KOVCHEG Blog 3.5.4.';

if($errors){fwrite(STDERR,"Visual Zone Builder audit failed:\n- ".implode("\n- ",$errors)."\n");exit(1);}echo "Visual Zone Builder audit passed.\n";