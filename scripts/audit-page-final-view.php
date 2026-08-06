<?php

declare(strict_types=1);

$root=dirname(__DIR__);$errors=[];
$read=static function(string $path)use($root,&$errors):string{$data=@file_get_contents($root.'/'.$path);if(!is_string($data)){$errors[]='Не удалось прочитать '.$path;return '';}return $data;};
$expect=static function(string $content,string $needle,string $message)use(&$errors):void{if(!str_contains($content,$needle))$errors[]=$message;};

$bootstrap=$read('app/bootstrap.php');
$studio32=$read('app/BlogStudio32.php');
$entryRoutes=$read('routes/blog-entry-routing.php');
$previewRoutes=$read('routes/blog-ux-fixes.php');
$editor=$read('views/studio/editor.php');
$contentIndex=$read('views/studio/content-index.php');
$menus=$read('views/studio/menus.php');
$layout=$read('themes/kovcheg-portal/layout.php');
$matrixCss=$read('themes/kovcheg-portal/assets/layout-matrix.css');
$scrollCss=$read('themes/kovcheg-portal/assets/public-page-scroll.css');
$editorCss=$read('assets/css/blog-classic-editor.css');
$editorJs=$read('assets/js/blog-classic-editor.js');

if(preg_match("/const APP_VERSION = '([^']+)';/",$bootstrap,$match)!==1||version_compare((string)$match[1],'3.5.10','<'))$errors[]='Версия приложения должна быть 3.5.10 или новее.';
$expect($bootstrap,"const ASSET_REVISION = '3.5.10-public-page-scroll';",'Не обновлена ревизия статических файлов 3.5.10.');
$expect($studio32,"\$status==='published'&&(\$publishedAt===null||strtotime(\$publishedAt)>time())",'Опубликованный материал с будущей датой не переводится на текущую дату.');
$expect($studio32,"\$status==='scheduled'&&(\$publishedAt===null||strtotime(\$publishedAt)<=time())",'Запланированная публикация не проверяет будущую дату.');

$expect($entryRoutes,'function kovcheg_render_entry_record','Нет единого рендера итоговой страницы.');
$expect($entryRoutes,'kovcheg_render_entry_record($stored, true);','Сохранённый материал не открывается на итоговом каноническом адресе для редактора.');
if(str_contains($entryRoutes,"redirect('/studio/content/'.(int)\$stored['id'].'/preview')"))$errors[]='Канонический адрес всё ещё переводит редактора на Studio Preview.';
$expect($entryRoutes,"header('Location: '.Blog::entryUrl(\$storedOther), true, 302);",'Смена типа материала не переводит на правильный итоговый адрес.');

$expect($previewRoutes,'if(Blog::canRead($entry))','Preview не определяет опубликованный материал.');
$expect($previewRoutes,"header('Location: '.Blog::entryUrl(\$entry),true,302);",'Опубликованный материал не выходит из preview на итоговую страницу.');
$expect($previewRoutes,"'studioPreview'=>true",'Черновик не помечается как полноэкранный preview.');

foreach(['data-entry-public-url','data-copy-public-url','data-entry-public-link','/studio/menus?entry='] as $token)$expect($editor,$token,'В редакторе отсутствует элемент постоянной ссылки: '.$token);
$expect($editor,'Открыть итоговую страницу','В редакторе нет отдельной кнопки итоговой страницы.');
$expect($contentIndex,'Открыть страницу','В списке материалов нет итоговой ссылки.');
$expect($contentIndex,'Blog::entryUrl($entry)','Список материалов не использует канонический адрес.');
$expect($menus,"\$_GET['entry']",'Страница не передаётся в редактор меню.');
$expect($menus,'selectedEntryId','Редактор меню не выбирает переданный материал.');

$expect($layout,'blog-theme-preview','Тема не получает класс полноэкранного preview.');
$expect($matrixCss,'html:has(body.blog-theme-preview)','Для preview не восстановлена прокрутка документа.');
$expect($matrixCss,'body.blog-theme-preview .portal-matrix-content','Центральная область preview не переведена на обычную прокрутку.');
$expect($editorCss,'.classic-editor-preview{overflow:auto','Модальное окно preview не прокручивается.');
$expect($editorCss,'.editor-permalink-line','Нет стилей постоянной ссылки.');
$expect($editorJs,'const updatePermalink','JavaScript не обновляет постоянную ссылку.');
$expect($editorJs,"frame.setAttribute('scrolling', 'yes')",'Iframe preview не включает прокрутку.');
$expect($editorJs,'overflow-y:auto','Документ внутри iframe preview не прокручивается.');

$expect($layout,"$pageType=(string)($layoutContext['page_type']??'default');",'Тема не определяет тип публичного представления.');
$expect($layout,"$documentClass=$pageType==='entry'?' blog-theme-document':'';",'Итоговая страница не получает класс документа.');
$expect($layout,"public-page-scroll.css",'CSS исправления публичной прокрутки не подключён.');
$expect($scrollCss,'html:has(body.blog-theme-document)','Корневой HTML не переведён на естественную прокрутку документа.');
$expect($scrollCss,'body.blog-theme-document','Итоговый материал не переведён на естественную прокрутку.');
$expect($scrollCss,'overflow-y: auto !important','Вертикальная прокрутка итоговой страницы не включена принудительно.');
$expect($scrollCss,'touch-action: pan-y','Сенсорная вертикальная прокрутка итоговой страницы не разрешена.');
$expect($scrollCss,'body.blog-theme-document .portal-matrix-content','Центральная область итоговой страницы не освобождена от overflow lock.');

if(substr_count($matrixCss,'{')!==substr_count($matrixCss,'}'))$errors[]='В layout-matrix.css нарушен баланс скобок.';
if(substr_count($scrollCss,'{')!==substr_count($scrollCss,'}'))$errors[]='В public-page-scroll.css нарушен баланс скобок.';
if(substr_count($editorCss,'{')!==substr_count($editorCss,'}'))$errors[]='В blog-classic-editor.css нарушен баланс скобок.';

if($errors){fwrite(STDERR,"Page final view audit failed:\n- ".implode("\n- ",$errors)."\n");exit(1);}echo "Page final view audit OK\n";
