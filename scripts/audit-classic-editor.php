<?php

declare(strict_types=1);

$root=dirname(__DIR__);$failures=[];
$require=static function(bool $condition,string $message)use(&$failures):void{if(!$condition)$failures[]=$message;};
$read=static function(string $path)use($root):string{$content=@file_get_contents($root.'/'.$path);return is_string($content)?$content:'';};

$bootstrap=$read('app/bootstrap.php');
$layout=$read('views/studio/layout.php');
$editor=$read('views/studio/wp-editor.php');
$studio32=$read('app/BlogStudio32.php');
$classic=$read('app/ClassicEditor.php');
$script=$read('assets/js/blog-classic-editor.js');
$style=$read('assets/css/blog-classic-editor.css');

$appVersion='';
if(preg_match("/const APP_VERSION = '([^']+)';/",$bootstrap,$match)){$appVersion=(string)$match[1];$require(version_compare($appVersion,'3.7.0','>='),'Версия должна быть 3.7.0 или новее.');}else{$require(false,'APP_VERSION не найден.');}
$require(str_contains($layout,'blog-classic-editor.css'),'CSS редактора не подключён.');
$require(str_contains($layout,'blog-classic-editor.js'),'JavaScript редактора не подключён.');
$require(str_contains($editor,'data-classic-visual'),'Нет визуальной области.');
$require(str_contains($editor,'data-classic-source'),'Нет режима HTML.');
$require(str_contains($editor,'name="type" value="page"'),'Редактор не фиксирует тип page.');
$require(!str_contains($editor,'Новая запись'),'В редакторе осталось понятие записи.');
$require(!str_contains($editor,'value="portfolio"'),'В редакторе осталось портфолио.');
$require(str_contains($editor,'data-action="media"'),'Нет кнопки изображения.');
$require(str_contains($editor,'data-action="preview"'),'Нет предпросмотра.');
$require(str_contains($editor,'name="category_ids[]"'),'Страница не может быть добавлена в рубрику.');
$require(str_contains($studio32,'$type=\'page\';'),'Studio32 не фиксирует тип page.');
$require(str_contains($studio32,'ClassicEditor::normalizePayload'),'Studio32 не обрабатывает классический payload.');
$require(!str_contains($studio32,'Builder::'),'Сохранение всё ещё зависит от Builder.');
$require(str_contains($classic,'DROP_CONTENT_TAGS'),'Не найден allowlist-санитайзер.');
$require(str_contains($script,'data-classic-media-item'),'Не реализована вставка изображения.');
$require(str_contains($script,'classicAutosave'),'Не реализовано автосохранение.');
$require(str_contains($style,'.classic-editor-toolbar'),'Нет оформления панели редактора.');

if(!function_exists('abort')){function abort(int $code,string $message=''):never{throw new RuntimeException($message,$code);}}
require_once $root.'/app/ClassicEditor.php';
$unsafe='<p onclick="evil()"><strong>Безопасный текст</strong><script>alert(1)</script><a href="javascript:alert(1)">опасная ссылка</a></p>';
$clean=\Kovcheg\Blog\ClassicEditor::sanitize($unsafe);
$require(str_contains($clean,'Безопасный текст'),'Санитайзер удалил разрешённый текст.');
$require(str_contains($clean,'<strong>'),'Санитайзер удалил форматирование.');
$require(!str_contains(strtolower($clean),'<script'),'Санитайзер пропустил script.');
$require(!str_contains(strtolower($clean),'onclick'),'Санитайзер пропустил обработчик события.');
$require(!str_contains(strtolower($clean),'javascript:'),'Санитайзер пропустил javascript URL.');
$payload=json_encode([['id'=>'classic-test','type'=>'classic','data'=>['html'=>'<h2>Заголовок</h2><p><em>Текст</em></p>']]],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
$normalized=\Kovcheg\Blog\ClassicEditor::normalizePayload($payload);$rendered=\Kovcheg\Blog\ClassicEditor::renderPayload($normalized);
$require(str_contains($rendered,'<h2>Заголовок</h2>'),'Не сохранён заголовок.');
$require(str_contains($rendered,'<em>Текст</em>'),'Не сохранён курсив.');
if($failures){foreach($failures as $failure)fwrite(STDERR,"FAIL: {$failure}\n");exit(1);}echo "Page classic editor audit OK\n";
