<?php

declare(strict_types=1);

$root=dirname(__DIR__);
$errors=[];
$read=static function(string $path)use($root,&$errors):string{
    $value=@file_get_contents($root.'/'.$path);
    if(!is_string($value)){$errors[]='Не удалось прочитать '.$path;return '';}
    return $value;
};
$expect=static function(string $text,string $token,string $message)use(&$errors):void{
    if(!str_contains($text,$token))$errors[]=$message;
};
$reject=static function(string $text,string $token,string $message)use(&$errors):void{
    if(str_contains($text,$token))$errors[]=$message;
};

$bootstrap=$read('app/bootstrap.php');
$index=$read('index.php');
$usersRoutes=$read('routes/blog-users.php');
$usersView=$read('views/studio/users.php');
$studioLayout=$read('views/studio/layout.php');
$widgetsView=$read('views/studio/widgets.php');
$appearance=$read('views/studio/appearance.php');
$studioCss=$read('assets/css/blog-studio-ux-3.8.1.css');
$zoneCss=$read('assets/css/blog-zone-builder.css');
$pageView=$read('themes/kovcheg-portal/page.php');
$pageCss=$read('themes/kovcheg-portal/assets/document-compact-3.8.1.css');
$scrollCss=$read('themes/kovcheg-portal/assets/public-page-scroll.css');
$portalLayout=$read('themes/kovcheg-portal/layout.php');

$expect($bootstrap,"const APP_VERSION = '3.8.1';",'APP_VERSION не равен 3.8.1.');
$expect($bootstrap,"const ASSET_REVISION = '3.8.1-compact-fixed-shell';",'Неверная ревизия assets 3.8.1.');

$expect($index,'routes/blog-users.php','Users routes не подключены.');
$usersPos=strpos($index,'routes/blog-users.php');
$legacyPos=strpos($index,'routes/blog-studio.php');
if($usersPos===false||$legacyPos===false||$usersPos>$legacyPos)$errors[]='Users routes должны подключаться раньше legacy Studio routes.';
foreach(['/studio/users', '/studio/users/{id}/role', '/studio/users/{id}/status'] as $route)$expect($usersRoutes,$route,'Нет маршрута '.$route);
foreach(['Csrf::validate()',"Studio::require('site')",'kovcheg_active_owner_count','Нельзя заблокировать собственную учётную запись'] as $token)$expect($usersRoutes,$token,'Users routes не содержат защиту: '.$token);
foreach(['users-page-head','user-grid','/studio/users/','return_q'] as $token)$expect($usersView,$token,'Users view не содержит '.$token);

$expect($scrollCss,'html:has(body.blog-theme-document)','Нет отдельного режима документа.');
$expect($scrollCss,'overflow: hidden !important','Desktop shell не зафиксирован.');
$expect($scrollCss,'body.blog-theme-document .portal-matrix-content','Центральная колонка документа не определена.');
$expect($scrollCss,'overflow-y: auto !important','Центральная колонка не прокручивается.');
$expect($scrollCss,'body.blog-theme-document .portal-matrix-sidebar','Боковые колонки документа не зафиксированы.');
$expect($scrollCss,'@media (max-width: 900px)','Нет мобильного режима естественной прокрутки.');
$reject($scrollCss,"body.blog-theme-document {\n  height: auto",'Desktop document снова использует прокрутку body.');

$expect($pageView,'site-page-title-sr','У страницы отсутствует скрытый SEO-заголовок.');
$expect($pageView,'document-compact-3.8.1.css','Компактный stylesheet страницы не подключён.');
$expect($pageView,'site-page-utility','Нет компактной служебной строки страницы.');
$reject($pageView,'site-page-title-row','Старый огромный блок заголовка остался на странице.');
$reject($pageView,'<h1><?=e((string)$entry[\'title\'])?></h1>','Заголовок страницы остаётся видимым огромным H1.');
foreach(['.site-page-title-sr','.site-page-cover img','max-height: 380px','.site-page-content'] as $token)$expect($pageCss,$token,'В compact Page CSS отсутствует '.$token);
$expect($portalLayout,'public-page-scroll.css','Portal layout не подключает фиксированный document shell.');

foreach(['blog-zone-builder.css','blog-studio-ux-3.8.1.css'] as $asset)$expect($studioLayout,$asset,'Studio не подключает '.$asset);
foreach(['widget-builder-shell--matrix','widget-library-group','matrix-blueprint'] as $token)$expect($widgetsView,$token,'Widgets view не содержит '.$token);
foreach([
    'body[data-studio-section="widgets"] .widget-builder-shell--matrix',
    'body[data-studio-section="widgets"] .widget-library-group__body',
    'body[data-studio-section="widgets"] .matrix-blueprint',
    'body[data-studio-section="appearance"] .theme-grid',
    'body[data-studio-section="appearance"] .branding-grid',
    'body[data-studio-section="users"] .user-grid',
] as $token)$expect($studioCss,$token,'Studio UX CSS не содержит '.$token);
$expect($appearance,'theme-grid','Appearance view не содержит сетку тем.');
$expect($appearance,'branding-grid','Appearance view не содержит сетку брендинга.');
$expect($zoneCss,'.widget-builder-shell--matrix','Базовый Widget Builder CSS отсутствует.');

foreach([$studioCss,$zoneCss,$pageCss,$scrollCss] as $css){
    if(substr_count($css,'{')!==substr_count($css,'}'))$errors[]='В CSS нарушен баланс фигурных скобок.';
}

if($errors){
    fwrite(STDERR,"KOVCHEG CMS 3.8.1 UX audit failed:\n- ".implode("\n- ",$errors)."\n");
    exit(1);
}

echo "KOVCHEG CMS 3.8.1 UX audit OK\n";
