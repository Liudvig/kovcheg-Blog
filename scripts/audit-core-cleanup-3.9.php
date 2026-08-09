<?php

declare(strict_types=1);

$root=dirname(__DIR__);
$errors=[];

$read=static function(string $relative)use($root,&$errors):string{
    $path=$root.'/'.$relative;
    $data=@file_get_contents($path);
    if(!is_string($data)){$errors[]='Не удалось прочитать '.$relative;return '';}
    return $data;
};
$expect=static function(bool $condition,string $message)use(&$errors):void{if(!$condition)$errors[]=$message;};

$bootstrap=$read('app/bootstrap.php');
$installer=$read('install.php');
$schema=$read('database/schema.php');
$index=$read('index.php');
$layout=$read('views/layout.php');
$studio=$read('views/studio/layout.php');
$studio32=$read('app/BlogStudio32.php');
$account=$read('routes/account.php');
$accountView=$read('views/account-shell.php');
$readme=$read('README.md');
$security=$read('SECURITY.md');
$growth=$read('routes/blog-growth.php');
$gitignore=$read('.gitignore');
$vkMediaMigration=$read('migrations/20260719_vk_media_library.sql');
$foundationMigration=$read('migrations/20260721_blog_foundation.sql');
$builderMigration=$read('migrations/20260722_blog_builder.sql');
$legacyPageMigration=$read('migrations/20260806_z_page_category_core.sql');
$contentCleanupMigration=$read('migrations/20260809_content_model_cleanup.sql');

$expect(str_contains($bootstrap,"const APP_VERSION = '3.9.0';"),'APP_VERSION должен быть 3.9.0.');
$expect(str_contains($bootstrap,"const ASSET_REVISION = '3.9.0-core-cleanup';"),'Неверная ревизия assets 3.9.0.');
$expect(str_contains($bootstrap,'$sessionLifetime=2592000;'),'Основная сессия должна быть ограничена 30 днями.');
$expect(!str_contains($bootstrap,"app/modern-ui.php"),'Bootstrap не должен загружать legacy modern UI layer.');
$expect(!str_contains($bootstrap,'https://vk.com')&&!str_contains($bootstrap,'https://vkvideo.ru'),'CSP не должен разрешать legacy VK/VK Video embeds.');
$expect(str_contains($installer,"const INSTALL_VERSION = '3.9.0';"),'Installer должен устанавливать KOVCHEG CMS 3.9.0.');
$expect(str_contains($installer,'install_apply_migrations($pdo);'),'Installer должен применять migration-chain до завершения установки.');
$expect(!str_contains($installer,'INSERT IGNORE INTO user_permissions'),'Installer не должен создавать legacy social user_permissions.');

$expect(str_contains($schema,'CREATE TABLE IF NOT EXISTS user_remember_tokens'),'Fresh schema должна создавать таблицу постоянного входа.');
foreach(['chats','messages','profile_posts','user_follows','colleague_requests','stories','push_subscriptions','user_permissions'] as $legacyTable){
    $expect(!str_contains($schema,'CREATE TABLE IF NOT EXISTS '.$legacyTable.' '),'Fresh schema не должна создавать legacy social table: '.$legacyTable);
}
$expect(str_contains($vkMediaMigration,'kovcheg_legacy_vk_media_retired'),'Историческая VK media migration должна оставаться retired compatibility marker.');
$expect(!str_contains($vkMediaMigration,'CREATE TABLE'),'Историческая VK media migration не должна создавать таблицы на новых установках.');
$expect(!str_contains($foundationMigration,'CREATE TABLE IF NOT EXISTS content_tags'),'Foundation migration не должна создавать retired content_tags.');
$expect(!str_contains($foundationMigration,'CREATE TABLE IF NOT EXISTS content_entry_tags'),'Foundation migration не должна создавать retired content_entry_tags.');
$expect(!str_contains($studio32,'syncTags'),'Studio32 не должна синхронизировать retired tags.');
$expect(!str_contains($builderMigration,'CREATE TABLE IF NOT EXISTS content_patterns'),'Builder migration не должна создавать retired content_patterns.');
$expect(!str_contains($builderMigration,'CREATE TABLE IF NOT EXISTS site_preset_history'),'Builder migration не должна создавать retired site_preset_history.');
$expect(str_contains($builderMigration,'CREATE TABLE IF NOT EXISTS content_autosaves'),'Builder compatibility migration должна сохранять autosave schema.');
$expect(str_contains($builderMigration,'CREATE TABLE IF NOT EXISTS module_migrations'),'Builder compatibility migration должна сохранять module migration registry.');

$requiredRoutes=[
 'routes/blog-content-model.php',
 'routes/blog-interactions.php',
 'routes/blog-categories.php',
 'routes/blog-menus.php',
 'routes/blog-users.php',
 'routes/blog-admin.php',
 'routes/blog-growth.php',
 'routes/blog-layout.php',
 'routes/account.php',
 'routes/blog-auth.php',
];
foreach($requiredRoutes as $route)$expect(str_contains($index,$route),'Runtime не подключает '.$route);

$obsoleteRuntime=[
 'routes/blog.php',
 'routes/blog-studio.php',
 'routes/web.php',
 'routes/template-features.php',
 'routes/blog-builder.php',
 'routes/blog-demo.php',
 'routes/blog-entry-routing.php',
 'routes/blog-simple-mode.php',
 'app/BlogBuilder.php',
 'app/BlogDemoSite.php',
 'app/modern-ui.php',
 'views/studio/content-index.php',
 'views/studio/editor.php',
 'views/studio/entries-index.php',
 'views/studio/patterns.php',
 'views/studio/presets.php',
 'views/templates/vk',
 'views/templates/x',
 'views/avatar-comment.php',
 'views/channel-comment.php',
 'views/channel-post.php',
 'views/comment-reactions.php',
 'views/conversation-shell.php',
 'views/feed-post.php',
 'views/feed.php',
 'views/message.php',
 'views/messenger.php',
 'views/profile-avatar-controls.php',
 'views/profile-avatar-modals.php',
 'views/profile-avatar-reactions.php',
 'views/profile-people-blocks.php',
 'views/profile-wall-comment.php',
 'views/profile-wall.php',
 'views/profile.php',
 'views/public-channel.php',
 'views/public-profile.php',
 'views/story-viewer.php',
 'views/wall-attachment-control.php',
 'views/wall-composer.php',
 'views/people.php',
 'views/settings.php',
 'views/mobile-navigation.php',
 'views/search.php',
 'views/site-sidebar.php',
 'views/weather-widget.php',
 'views/weather.php',
 'assets/css/blog-builder.css',
 'assets/css/modern-upload.css',
 'assets/js/modern-upload.js',
 'assets/css/template-polish.css',
 'assets/css/layout-repair.css',
 'assets/js/layout-repair.js',
];
foreach($obsoleteRuntime as $relative)$expect(!file_exists($root.'/'.$relative),'В дереве остался устаревший путь: '.$relative);

$expect(!str_contains($layout,'chat_unread_count'),'Общая оболочка всё ещё зависит от чата.');
$expect(!str_contains($layout,'user_notifications'),'Общая оболочка всё ещё зависит от социальной системы уведомлений.');
$expect(!str_contains($layout,'site_template'),'Общая оболочка всё ещё переключает старые социальные шаблоны.');
$expect(!str_contains($account,'profile_posts'),'Личный кабинет всё ещё читает социальные публикации.');
$expect(!str_contains($account,'colleague'),'Личный кабинет всё ещё зависит от коллег.');
$expect(!str_contains($accountView,"/profile"),'Личный кабинет всё ещё ведёт на старый профиль.');
$expect(!str_contains($studio,'studio-body--'.'word'.'press'),'Studio содержит старый внешний класс.');
$expect(!str_contains($studio,'blog-studio-'.'word'.'press.css'),'Studio загружает старый внешний CSS.');
$expect(str_contains($growth,"'/post/'"),'RSS должен использовать канонический /post/{slug}.');
$expect(str_contains($gitignore,'/config/config.php'),'.gitignore не защищает production config.');
$expect(str_contains($gitignore,'/storage/uploads/*'),'.gitignore не защищает uploads.');
$expect(str_contains($readme,'KOVCHEG Blog / KOVCHEG CMS 3.9'),'README не обновлён до 3.9.');
$expect(str_contains($security,'KOVCHEG Blog / KOVCHEG CMS 3.9'),'SECURITY не обновлён до 3.9.');

$expect(str_contains($legacyPageMigration,"WHERE type = 'portfolio'"),'Legacy migration должна преобразовывать только portfolio в page.');
$expect(!str_contains($legacyPageMigration,"type IN ('post'"),'Legacy migration не должна преобразовывать записи post в page.');
$expect(str_contains($contentCleanupMigration,"WHERE type = 'portfolio'"),'Cleanup migration должна сохранять записи post.');
$expect(str_contains($contentCleanupMigration,"WHERE e.type = 'page'"),'Cleanup migration должна удалить рубрики только у страниц.');

$forbidden=[
    'Word'.'Press',
    'word'.'press',
    'Instant'.'CMS',
    'instant'.'cms',
    'Joo'.'mla',
    'Dru'.'pal',
];

// Historical records are intentionally preserved verbatim. They document how the
// product evolved and are not loaded by the application runtime. Active source,
// current documentation and migrations remain subject to the terminology scan.
$historicalAllowlist=[
    'docs/DEVELOPMENT_LOG.md',
    'docs/development/KOVCHEG_CMS_3.8.0.md',
    'docs/releases/KOVCHEG_BLOG_3.5.6.md',
    'docs/releases/KOVCHEG_CMS_3.8.0.md',
    'docs/releases/KOVCHEG_BLOG_3.6.0.md',
    'scripts/audit-cms-core-3.8.php',
    'scripts/audit-entry-routing.php',
    'scripts/audit-page-category-core.php',
];

$extensions=['php','md','css','js','json','yml','yaml','sql','txt'];
$iterator=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root,FilesystemIterator::SKIP_DOTS));
foreach($iterator as $file){
    if(!$file->isFile())continue;
    $path=$file->getPathname();
    $relative=str_replace('\\','/',substr($path,strlen($root)+1));
    if(str_starts_with($relative,'.git/'))continue;
    if($relative==='scripts/audit-core-cleanup-3.9.php')continue;
    if(in_array($relative,$historicalAllowlist,true))continue;
    $ext=strtolower(pathinfo($relative,PATHINFO_EXTENSION));
    if(!in_array($ext,$extensions,true))continue;
    $data=@file_get_contents($path);
    if(!is_string($data))continue;
    foreach($forbidden as $term){
        if(str_contains($data,$term)){$errors[]='Чужая CMS-терминология в '.$relative;break;}
    }
}

if(file_exists($root.'/config/config.php'))$errors[]='config/config.php не должен быть отслеживаемым файлом CI checkout.';
if(file_exists($root.'/.env'))$errors[]='.env не должен быть отслеживаемым файлом.';

if($errors){fwrite(STDERR,"KOVCHEG CMS 3.9 cleanup audit failed:\n- ".implode("\n- ",array_values(array_unique($errors)))."\n");exit(1);}
echo "KOVCHEG CMS 3.9 core cleanup audit OK\n";
