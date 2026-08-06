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

$bootstrap=$read('app/bootstrap.php');
$index=$read('index.php');
$blog=$read('app/Blog.php');
$content=$read('routes/blog-content-model.php');
$categories=$read('routes/blog-categories.php');
$menus=$read('routes/blog-menus.php');
$users=$read('routes/blog-users.php');
$layoutRoutes=$read('routes/blog-layout.php');
$essential=$read('app/BlogEssentialWidgets.php');
$overrides=$read('app/BlogCmsWidgetOverrides.php');
$themeSupport=$read('app/BlogThemeSupport.php');
$layoutRepair=$read('app/BlogLayoutRepair.php');
$studioLayout=$read('views/studio/layout.php');
$dashboard=$read('views/studio/dashboard.php');
$editor=$read('views/studio/content-editor.php');
$menuView=$read('views/studio/menus.php');
$widgetsView=$read('views/studio/widgets.php');
$login=$read('views/login.php');
$register=$read('views/register.php');
$portalLayout=$read('themes/kovcheg-portal/layout.php');
$editorialLayout=$read('themes/kovcheg-editorial/layout.php');
$portalHome=$read('themes/kovcheg-portal/home.php');
$editorialHome=$read('themes/kovcheg-editorial/home.php');
$migration=$read('migrations/20260806_posts_menus_widgets.sql');
$menuCss=$read('assets/css/blog-studio-menus.css');
$registrationCss=$read('assets/css/blog-registration.css');

$appVersion='';
$assetRevision='';
if(preg_match("/const APP_VERSION = '([^']+)';/",$bootstrap,$match)===1)$appVersion=(string)$match[1];
if(preg_match("/const ASSET_REVISION = '([^']+)';/",$bootstrap,$match)===1)$assetRevision=(string)$match[1];
if($appVersion===''||version_compare($appVersion,'3.8.0','<'))$errors[]='APP_VERSION должен быть 3.8.0 или новее.';
if($assetRevision===''||$appVersion===''||!str_starts_with($assetRevision,$appVersion))$errors[]='ASSET_REVISION должен начинаться с APP_VERSION.';
$expect($bootstrap,"require_once BASE_PATH.'/app/BlogThemeSupport.php';",'ThemeSupport не загружается в bootstrap.');

foreach([
    'routes/blog-content-model.php',
    'routes/blog-categories.php',
    'routes/blog-menus.php',
    'routes/blog-users.php',
    'routes/blog-layout.php',
    'routes/blog-branding.php',
] as $route)$expect($index,$route,'Runtime не подключает '.$route);
foreach([
    'routes/blog-wordpress-mode.php',
    'routes/blog-wordpress-compat.php',
    'routes/blog-simple-mode.php',
    'routes/blog-entry-routing.php',
    'routes/blog-builder.php',
    'routes/blog-demo.php',
] as $legacy)if(str_contains($index,$legacy))$errors[]='Runtime всё ещё загружает устаревший файл '.$legacy;

foreach(['/studio/posts','/studio/pages','/studio/entry/save','/post/{slug}','/page/{slug}','/category/{slug}'] as $route)$expect($content,$route,'Нет маршрута '.$route);
$expect($content,"e.type='post'",'Публичная рубрика не ограничена записями.');
$expect($content,"\$input['type'] = (string)(\$_POST['type'] ?? '') === 'page' ? 'page' : 'post';",'Сохранение не различает Записи и Страницы.');
$expect($categories,"e.type='post'",'Счётчик рубрик включает не только записи.');
$expect($categories,'Записи сохранены без неё','Удаление рубрики должно сохранять записи.');
$expect($editor,'$isPost','Редактор не различает Запись и Страницу.');
$expect($editor,'name="category_ids[]"','В редакторе записи отсутствует выбор рубрик.');

foreach(['/studio/users','/studio/users/{id}/role','/studio/users/{id}/status'] as $route)$expect($users,$route,'Нет маршрута пользователей '.$route);
$expect($users,'kovcheg_active_owner_count','Не защищён последний владелец.');
$expect($users,"Studio::require('site')",'Управление пользователями не защищено правом site.');

foreach(['header','left','right','footer'] as $location)$expect($menus,"'{$location}'",'В меню отсутствует позиция '.$location);
foreach(['/studio/menus/create','/studio/menus/{id}/update','/studio/menus/{id}/delete','/studio/menus/item/{id}/update'] as $route)$expect($menus,$route,'Нет маршрута меню '.$route);
foreach(['target_kind" value="page"','target_kind" value="category"','target_kind" value="custom"'] as $token)$expect($menuView,$token,'В редакторе меню отсутствует источник '.$token);
$expect($menuView,'/studio/widgets','Меню не связано с зонами виджетов.');
$expect($blog,'public static function menuById','Нет выбора конкретного меню в виджете.');
if(str_contains($blog,"['Главная','/']")||str_contains($blog,"['Блог','/blog']"))$errors[]='В Blog остались принудительные пункты меню.';

foreach(['core.auth-form','core.content-slider','core.media','core.media-slider','core.category-calendar'] as $type)$expect($essential,$type,'Не зарегистрирован виджет '.$type);
foreach(['core.menu','core.account','core.categories'] as $type)$expect($overrides,$type,'Не переопределён виджет '.$type);
$expect($overrides,"e.type='post'",'Виджет рубрик считает не только записи.');
$expect($overrides,'registration_mode','Виджет профиля не учитывает закрытую регистрацию.');
$expect($layoutRoutes,'CmsWidgetOverrides::boot();','Widget overrides не активируются.');
$expect($widgetsView,'Главная, запись или страница','Конструктор зон не знает о Записях и Страницах.');
$expect($widgetsView,'Ненужные виджеты можно отключить','Интерфейс не объясняет отключение виджетов.');
if(str_contains($layoutRepair,'portal-section-menu')||str_contains($layoutRepair,'portal-default-search'))$errors[]='LayoutRepair всё ещё принудительно создаёт боковые виджеты.';

foreach([$portalLayout,$editorialLayout] as $themeLayout){
    $expect($themeLayout,"ThemeSupport::menuHtml('header'",'Тема не выводит назначенное меню шапки.');
    $expect($themeLayout,"ThemeSupport::menuHtml('left'",'Тема не выводит назначенное левое меню.');
    $expect($themeLayout,"ThemeSupport::menuHtml('right'",'Тема не выводит назначенное правое меню.');
    $expect($themeLayout,"ThemeSupport::menuHtml('footer'",'Тема не выводит назначенное меню подвала.');
}
$expect($themeSupport,'registration_mode','ThemeSupport не скрывает закрытую регистрацию.');
$expect($portalHome,'$posts','Portal home не выводит Записи.');
$expect($editorialHome,'$posts','Editorial home не выводит Записи.');
if(str_contains($portalHome,'$categories')||str_contains($editorialHome,'$categories'))$errors[]='Главная принудительно выводит рубрики.';
if(!is_file($root.'/themes/kovcheg-portal/post.php')||!is_file($root.'/themes/kovcheg-editorial/post.php'))$errors[]='В темах отсутствует шаблон публичной Записи.';

foreach(['blog-auth-brand.css','/brand/logo','/brand/login-background'] as $token)$expect($login,$token,'Вход не использует брендинг: '.$token);
foreach(['blog-registration.css','/brand/logo','/brand/login-background'] as $token)$expect($register,$token,'Регистрация не использует брендинг: '.$token);
$expect($studioLayout,'/brand/logo','Studio не использует логотип сайта.');
$expect($studioLayout,'blog-studio-menus.css','Studio не подключает оформление меню.');
$expect($dashboard,"\$stats['posts']",'Dashboard не показывает Записи.');

foreach(["SET type='page'","WHERE type='portfolio'","WHERE e.type='page'",'portal-section-menu','default-subscription'] as $token)$expect($migration,$token,'Миграция 3.8.0 не содержит '.$token);
$expect($migration,'DELETE p','Миграция не удаляет принудительные размещения виджетов.');

foreach([$menuCss,$registrationCss] as $css)if(substr_count($css,'{')!==substr_count($css,'}'))$errors[]='В CSS нарушен баланс фигурных скобок.';

if(!defined('BASE_PATH'))define('BASE_PATH',$root);
require_once $root.'/app/Core.php';
$router=new \Kovcheg\Router();$hit=[];
$router->get('/post/{slug}',static function(array $params)use(&$hit):void{$hit=['type'=>'post','slug'=>$params['slug']??''];});
$router->get('/page/{slug}',static function(array $params)use(&$hit):void{$hit=['type'=>'page','slug'=>$params['slug']??''];});
$router->dispatch('GET','/post/test-news');
if(($hit['type']??'')!=='post'||($hit['slug']??'')!=='test-news')$errors[]='Router не разобрал публичную Запись.';
$router->dispatch('GET','/page/test-page');
if(($hit['type']??'')!=='page'||($hit['slug']??'')!=='test-page')$errors[]='Router не разобрал публичную Страницу.';

if($errors){fwrite(STDERR,"CMS 3.8+ audit failed:\n- ".implode("\n- ",$errors)."\n");exit(1);}
echo "KOVCHEG CMS 3.8+ core audit OK\n";
