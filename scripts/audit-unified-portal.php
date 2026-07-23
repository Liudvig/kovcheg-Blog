<?php

declare(strict_types=1);

$root=dirname(__DIR__);$errors=[];
$read=static function(string $path)use($root,&$errors):string{$file=$root.'/'.$path;$data=is_file($file)?file_get_contents($file):false;if($data===false){$errors[]='Не удалось прочитать '.$path;return '';}return $data;};
$expect=static function(string $content,string $needle,string $message)use(&$errors):void{if(!str_contains($content,$needle))$errors[]=$message;};
$bootstrap=$read('app/bootstrap.php');$studio=$read('views/studio/layout.php');$studioCss=$read('assets/css/blog-studio-unified.css');$account=$read('views/account-shell.php');$accountRoute=$read('routes/account.php');$widgetRoute=$read('routes/blog-layout.php');$repair=$read('app/BlogLayoutRepair.php');$media=$read('views/studio/media.php');$modules=$read('views/studio/modules.php');$builderRoute=$read('routes/blog-builder.php');$uploadJs=$read('assets/js/blog-upload.js');$editor=$read('views/studio/editor.php');$editorial=$read('themes/kovcheg-editorial/layout.php');$portal=$read('themes/kovcheg-portal/layout.php');$portalCss=$read('themes/kovcheg-portal/assets/theme.css');$portalManifest=$read('themes/kovcheg-portal/theme.json');$portalMigration=$read('migrations/20260722_blog_portal_theme.sql');
$expect($bootstrap,"const APP_VERSION = '3.5.2';",'Версия приложения должна быть 3.5.2.');
$expect($studio,'studio-footer','В Studio отсутствует реальный подвал.');
$expect($studio,'studio-sidebar-meta','В Studio отсутствует служебный блок левой колонки.');
if(preg_match('~<aside class="studio-sidebar".*?app_url\(\'/logout\'\).*?</aside>~s',$studio))$errors[]='Кнопка выхода осталась в левой колонке Studio.';
$expect($studioCss,'.studio-sidebar{position:fixed','Левая колонка Studio не зафиксирована.');
$expect($studioCss,'height:100vh','Studio не закреплена на высоту окна.');
$expect($studioCss,'scrollbar-width:none','Прокрутка без бегунков не настроена.');
$expect($accountRoute,"require BASE_PATH.'/views/account-shell.php';",'Кабинет всё ещё натянут на старую тему.');
$expect($account,'account-studio-shell','Личный кабинет не использует единый стиль Studio.');
$expect($account,'studio-footer','В личном кабинете отсутствует подвал.');
$expect($repair,'final class LayoutRepair','Отсутствует самовосстановление Widget Engine.');
$expect($widgetRoute,'LayoutRepair::ensure()','Маршрут виджетов не восстанавливает таблицы.');
$expect($widgetRoute,"Studio::render('widgets-error'",'Widget Engine всё ещё может отдавать белую 500 вместо диагностики.');
$expect($media,'data-upload-zone','В медиатеке отсутствует Drag & Drop.');
$expect($media,'name="media[]" multiple','В медиатеке отсутствует мультизагрузка.');
$expect($modules,'name="packages[]"','Модули не поддерживают пакетную загрузку.');
$expect($modules,'multiple','Модули не поддерживают мультивыбор.');
$expect($builderRoute,'foreach(array_slice(array_keys($files[\'name\']),0,10)','Маршрут не устанавливает несколько модулей.');
$expect($uploadJs,'new DataTransfer()','Общий Drag & Drop загрузчик не управляет набором файлов.');
$expect($editor,'data-upload-zone','Обложка материала не поддерживает перетаскивание.');
$expect($editorial,'site-footer__copyright','В Editorial/Portfolio отсутствует обязательный копирайт.');
$expect($editorial,'Ланцет Семён Борисович','В Editorial/Portfolio отсутствует имя правообладателя.');
$expect($portal,'portal-grid--three','Portal не поддерживает три колонки.');
$expect($portal,'portal-footer__copyright','В Portal отсутствует обязательный копирайт.');
$expect($portalCss,'.portal-grid--three','Отсутствует CSS трёхколоночной сетки Portal.');
$expect($portalCss,'.portal-footer__copyright','Отсутствует CSS копирайта Portal.');
$expect($portalManifest,'"min_core": "3.5.2"','Манифест Portal не привязан к 3.5.2.');
$expect($portalMigration,"'layout.left'",'Миграция Portal не наполняет левую колонку.');
$expect($portalMigration,"'layout.right'",'Миграция Portal не наполняет правую колонку.');
if($errors){fwrite(STDERR,"KOVCHEG Blog 3.5 audit failed:\n- ".implode("\n- ",$errors)."\n");exit(1);}echo "KOVCHEG Blog 3.5 unified Portal audit passed.\n";