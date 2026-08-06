<?php

declare(strict_types=1);

$root=dirname(__DIR__);
$failures=[];
$require=static function(bool $condition,string $message)use(&$failures):void{if(!$condition)$failures[]=$message;};

$read=static function(string $path)use($root):string{
    $content=@file_get_contents($root.'/'.$path);
    return is_string($content)?$content:'';
};

$bootstrap=$read('app/bootstrap.php');
$layout=$read('views/studio/layout.php');
$editor=$read('views/studio/editor.php');
$studio32=$read('app/BlogStudio32.php');
$classic=$read('app/ClassicEditor.php');
$script=$read('assets/js/blog-classic-editor.js');
$style=$read('assets/css/blog-classic-editor.css');
$demo=$read('app/BlogDemoSite.php');
$routes=$read('routes/blog-demo.php');
$index=$read('index.php');

$require(str_contains($bootstrap,"const APP_VERSION = '3.5.6';"),'Версия приложения должна быть 3.5.6.');
$require(str_contains($bootstrap,"const ASSET_REVISION = '3.5.6-classic-editor-demo';"),'Неверная ревизия assets.');
$require(str_contains($layout,'blog-classic-editor.css'),'CSS классического редактора не подключён.');
$require(str_contains($layout,'blog-classic-editor.js'),'JavaScript классического редактора не подключён.');
$require(str_contains($editor,'data-classic-visual'),'В шаблоне отсутствует визуальная область.');
$require(str_contains($editor,'data-classic-source'),'В шаблоне отсутствует режим HTML.');
$require(str_contains($editor,'data-editor-tab="builder"'),'Конструктор секций должен оставаться дополнительным режимом.');
$require(str_contains($studio32,'ClassicEditor::normalizePayload'),'Studio32 не обрабатывает классический payload.');
$require(str_contains($classic,'DROP_CONTENT_TAGS'),'Не найден allowlist-санитайзер.');
$require(str_contains($script,"data-classic-media-item"),'Не реализована вставка изображения из медиатеки.');
$require(str_contains($script,'classicAutosave'),'Не реализовано автосохранение классического редактора.');
$require(str_contains($style,'.classic-editor-toolbar'),'Нет оформления панели редактора.');
$require(str_contains($demo,'final class DemoSite'),'Не найден установщик демонстрационного сайта.');
$require(str_contains($routes,"/studio/demo/install"),'Не зарегистрирован маршрут демо-сайта.');
$require(str_contains($index,"routes/blog-demo.php"),'Маршруты демо-сайта не подключены.');

if(!function_exists('abort')){function abort(int $code,string $message=''):never{throw new RuntimeException($message,$code);}}
require_once $root.'/app/ClassicEditor.php';

$unsafe='<p onclick="evil()" style="text-align:center"><strong>Безопасный текст</strong><script>alert(1)</script><a href="javascript:alert(1)">опасная ссылка</a></p>';
$clean=\Kovcheg\Blog\ClassicEditor::sanitize($unsafe);
$require(str_contains($clean,'Безопасный текст'),'Санитайзер удалил разрешённый текст.');
$require(str_contains($clean,'<strong>'),'Санитайзер удалил разрешённое форматирование.');
$require(!str_contains(strtolower($clean),'<script'),'Санитайзер пропустил script.');
$require(!str_contains(strtolower($clean),'onclick'),'Санитайзер пропустил обработчик события.');
$require(!str_contains(strtolower($clean),'javascript:'),'Санитайзер пропустил javascript URL.');

$payload=json_encode([['id'=>'classic-test','type'=>'classic','data'=>['html'=>'<h2>Заголовок</h2><p><em>Текст</em></p>']]],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
$normalized=\Kovcheg\Blog\ClassicEditor::normalizePayload($payload);
$rendered=\Kovcheg\Blog\ClassicEditor::renderPayload($normalized);
$require(\Kovcheg\Blog\ClassicEditor::isClassicPayload($normalized),'Нормализованный payload не распознан.');
$require(str_contains($rendered,'<h2>Заголовок</h2>'),'Не сохранён заголовок классического редактора.');
$require(str_contains($rendered,'<em>Текст</em>'),'Не сохранён курсив.');

if($failures){foreach($failures as $failure)fwrite(STDERR,"FAIL: {$failure}\n");exit(1);}
echo "Classic editor and demo audit OK\n";
