<?php

declare(strict_types=1);

$root=dirname(__DIR__);$errors=[];
$read=static function(string $path)use($root,&$errors):string{$value=@file_get_contents($root.'/'.$path);if(!is_string($value)){$errors[]='Не удалось прочитать '.$path;return '';}return $value;};
$expect=static function(string $text,string $token,string $message)use(&$errors):void{if(!str_contains($text,$token))$errors[]=$message;};

$bootstrap=$read('app/bootstrap.php');
$index=$read('index.php');
$wpRoutes=$read('routes/blog-wordpress-mode.php');
$compat=$read('routes/blog-wordpress-compat.php');
$entryRoutes=$read('routes/blog-entry-routing.php');
$studio32=$read('app/BlogStudio32.php');
$layout=$read('views/studio/layout.php');
$editor=$read('views/studio/wp-editor.php');
$entries=$read('views/studio/entries-index.php');
$categories=$read('views/studio/categories.php');
$menus=$read('views/studio/menus.php');
$home=$read('themes/kovcheg-portal/home.php');
$entry=$read('themes/kovcheg-editorial/entry.php');
$css=$read('assets/css/blog-studio-wordpress.css');
$migration=$read('migrations/20260806_wordpress_content_model.sql');

$expect($bootstrap,"const APP_VERSION = '3.6.0';",'APP_VERSION не равен 3.6.0.');
$expect($bootstrap,"const ASSET_REVISION = '3.6.0-wordpress-simple-core';",'Неверная ревизия assets.');
if(str_contains($bootstrap,"require_once BASE_PATH.'/app/BlogBuilder.php'"))$errors[]='Тяжёлый Builder загружается в bootstrap каждого запроса.';

$wpPos=strpos($index,'routes/blog-wordpress-mode.php');$compatPos=strpos($index,'routes/blog-wordpress-compat.php');$legacyPos=strpos($index,'routes/blog-builder.php');
if($wpPos===false||$compatPos===false||$legacyPos===false||$compatPos>$wpPos||$wpPos>$legacyPos)$errors[]='Compatibility и WordPress-маршруты должны регистрироваться раньше legacy Studio.';
if(str_contains($index,'routes/blog-demo.php'))$errors[]='Демо-маршрут загружается в runtime.';

foreach(['/studio/posts','/studio/pages','/studio/entry/save'] as $route)$expect($wpRoutes,$route,'Нет маршрута '.$route);
$expect($compat,'/studio/categories','Нет маршрута /studio/categories.');
$expect($compat,'/studio/content/save','Старая форма сохранения не нормализуется.');
$expect($compat,"$router->get('/portfolio'",'Старый URL /portfolio не обслуживается совместимым архивом записей.');
$expect($wpRoutes,"target_kind']??''",'Меню не различает страницу, рубрику и ссылку.');
$expect($wpRoutes,"type='page'",'Список источников меню не ограничен страницами.');
$expect($wpRoutes,"e.type='post'",'Архив рубрики не ограничен записями.');
$expect($wpRoutes,"/tag/{slug}', function () { redirect('/blog')",'Старые теги не отключены.');

foreach(["'posts'=>['Записи'","'categories'=>['Рубрики'","'pages'=>['Страницы'"] as $token)$expect($layout,$token,'В меню Studio отсутствует '.$token);
foreach(["'content'=>","'patterns'=>","'presets'=>","'widgets'=>","'modules'=>","'growth'=>"] as $token)if(str_contains($layout,$token))$errors[]='В меню Studio остался лишний раздел '.$token;
$expect($layout,'blog-studio-wordpress.css','WordPress stylesheet не подключён.');
if(str_contains($layout,'blog-builder.css')||str_contains($layout,'blog-zone-builder.css')||str_contains($layout,'blog-widgets.js'))$errors[]='Studio продолжает загружать конструктор или Widget Engine.';

$expect($editor,'wp-editor-layout','Нет компактного редактора.');
$expect($editor,'name="type"','Тип записи не зафиксирован.');
$expect($editor,'data-classic-visual','Классический редактор отсутствует.');
foreach(['value="portfolio"','name="tags"','portfolio_client','data-editor-tab="builder"'] as $token)if(str_contains($editor,$token))$errors[]='В редакторе осталось лишнее поле '.$token;
$expect($editor,'Рубрики','В редакторе записи отсутствуют рубрики.');
$expect($entries,'$entryType===\'page\'','Списки записей и страниц не разделены.');
if(str_contains($categories,'name="sort_order"'))$errors[]='В рубриках осталось ручное поле сортировки.';
foreach(['target_kind" value="page"','target_kind" value="category"','target_kind" value="custom"'] as $token)$expect($menus,$token,'В меню отсутствует источник '.$token);

$expect($studio32,'$type=(string)($input[\'type\']??\'\')===\'page\'?\'page\':\'post\';','Сохранение не ограничивает типы post/page.');
$expect($studio32,'$type===\'post\'?(array)($input[\'category_ids\']??[]):[]','Рубрики не ограничены записями.');
$expect($studio32,"self::syncTags(\$id,'');",'Теги не очищаются при сохранении.');
if(str_contains($studio32,'Builder::'))$errors[]='Сохранение всё ещё зависит от Builder.';
if(str_contains($entryRoutes,'/portfolio/{slug}'))$errors[]='Портфолио осталось каноническим типом.';
if(str_contains($home,'portfolio'))$errors[]='На главной осталось портфолио.';
if(str_contains($entry,'portfolio')||str_contains($entry,'/tag/'))$errors[]='Итоговый шаблон всё ещё выводит портфолио или теги.';

$expect($migration,"SET type = 'page'",'Миграция не переводит портфолио в страницы.');
$expect($migration,"WHERE type = 'portfolio'",'Миграция не ограничена портфолио.');
$expect($migration,"WHERE e.type = 'page'",'Миграция не удаляет рубрики со страниц.');
if(substr_count($css,'{')!==substr_count($css,'}'))$errors[]='В WordPress CSS нарушен баланс скобок.';

if($errors){fwrite(STDERR,"WordPress simple core audit failed:\n- ".implode("\n- ",$errors)."\n");exit(1);}echo "WordPress simple core audit OK\n";
