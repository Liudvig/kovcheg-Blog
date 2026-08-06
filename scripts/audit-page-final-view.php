<?php

declare(strict_types=1);

$root=dirname(__DIR__);$errors=[];
$read=static function(string $path)use($root,&$errors):string{$data=@file_get_contents($root.'/'.$path);if(!is_string($data)){$errors[]='Не удалось прочитать '.$path;return '';}return $data;};
$expect=static function(string $content,string $needle,string $message)use(&$errors):void{if(!str_contains($content,$needle))$errors[]=$message;};

$bootstrap=$read('app/bootstrap.php');
$studio32=$read('app/BlogStudio32.php');
$entryRoutes=$read('routes/blog-entry-routing.php');
$previewRoutes=$read('routes/blog-ux-fixes.php');
$editor=$read('views/studio/wp-editor.php');
$contentIndex=$read('views/studio/entries-index.php');
$menus=$read('views/studio/menus.php');
$layout=$read('themes/kovcheg-portal/layout.php');
$matrixCss=$read('themes/kovcheg-portal/assets/layout-matrix.css');
$scrollCss=$read('themes/kovcheg-portal/assets/public-page-scroll.css');
$editorCss=$read('assets/css/blog-classic-editor.css');
$editorJs=$read('assets/js/blog-classic-editor.js');

if(preg_match("/const APP_VERSION = '([^']+)';/",$bootstrap,$match)!==1||version_compare((string)$match[1],'3.6.0','<'))$errors[]='Версия приложения должна быть 3.6.0 или новее.';
$expect($bootstrap,"const ASSET_REVISION = '3.6.0-wordpress-simple-core';",'Не обновлена ревизия статических файлов 3.6.0.');
$expect($studio32,"\$status==='published'&&(\$publishedAt===null||strtotime(\$publishedAt)>time())",'Опубликованный материал с будущей датой не переводится на текущую дату.');
$expect($studio32,"\$status==='scheduled'&&(\$publishedAt===null||strtotime(\$publishedAt)<=time())",'Запланированная публикация не проверяет будущую дату.');

$expect($entryRoutes,'function kovcheg_render_entry_record','Нет единого рендера итоговой страницы.');
$expect($entryRoutes,'kovcheg_render_entry_record($stored, true);','Сохранённый материал не открывается по каноническому адресу редактору.');
$expect($entryRoutes,"header('Location: '.Blog::entryUrl(\$storedOther), true, 302);",'Смена типа не переводит на правильный итоговый адрес.');
$expect($previewRoutes,'if(Blog::canRead($entry))','Preview не определяет опубликованный материал.');
$expect($previewRoutes,"header('Location: '.Blog::entryUrl(\$entry),true,302);",'Опубликованный материал не выходит из preview.');
$expect($previewRoutes,"'studioPreview'=>true",'Черновик не помечается как полноэкранный preview.');

foreach(['data-entry-public-url','data-copy-public-url','data-entry-public-link','/studio/menus?entry='] as $token)$expect($editor,$token,'В редакторе отсутствует постоянная ссылка: '.$token);
$expect($editor,'Добавить в меню','В редакторе страницы нет добавления в меню.');
$expect($contentIndex,'Посмотреть','В списке нет итогового просмотра.');
$expect($contentIndex,'Blog::entryUrl($entry)','Список не использует канонический адрес.');
$expect($menus,"\$_GET['entry']",'Страница не передаётся в меню.');
$expect($menus,'selectedEntryId','Меню не выбирает переданную страницу.');

$expect($layout,'blog-theme-preview','Тема не получает класс preview.');
$expect($matrixCss,'html:has(body.blog-theme-preview)','Для preview не восстановлена прокрутка.');
$expect($editorCss,'.classic-editor-preview{overflow:auto','Модальное окно preview не прокручивается.');
$expect($editorJs,"frame.setAttribute('scrolling', 'yes')",'Iframe preview не включает прокрутку.');
$expect($layout,"\$documentClass=\$pageType==='entry'?' blog-theme-document':'';",'Итоговая страница не получает класс документа.');
$expect($layout,'public-page-scroll.css','CSS публичной прокрутки не подключён.');
$expect($scrollCss,'html:has(body.blog-theme-document)','HTML не переведён на естественную прокрутку.');
$expect($scrollCss,'overflow-y: auto !important','Вертикальная прокрутка итоговой страницы не включена.');
$expect($scrollCss,'touch-action: pan-y','Сенсорная прокрутка не разрешена.');

foreach([$matrixCss,$scrollCss,$editorCss] as $css)if(substr_count($css,'{')!==substr_count($css,'}'))$errors[]='В CSS нарушен баланс скобок.';
if($errors){fwrite(STDERR,"Page final view audit failed:\n- ".implode("\n- ",$errors)."\n");exit(1);}echo "Page final view audit OK\n";
