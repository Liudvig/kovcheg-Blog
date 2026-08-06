<?php

declare(strict_types=1);

$root=dirname(__DIR__);$errors=[];
$read=static function(string $path)use($root,&$errors):string{$data=@file_get_contents($root.'/'.$path);if(!is_string($data)){$errors[]='Не удалось прочитать '.$path;return '';}return $data;};
$expect=static function(string $content,string $needle,string $message)use(&$errors):void{if(!str_contains($content,$needle))$errors[]=$message;};
$reject=static function(string $content,string $needle,string $message)use(&$errors):void{if(str_contains($content,$needle))$errors[]=$message;};

$bootstrap=$read('app/bootstrap.php');
$blog=$read('app/Blog.php');
$index=$read('index.php');
$routes=$read('routes/blog-entry-routing.php');
$compat=$read('routes/blog-wordpress-compat.php');
$preview=$read('routes/blog-ux-fixes.php');
$pages=$read('views/studio/entries-index.php');

if(preg_match("/const APP_VERSION = '([^']+)';/",$bootstrap,$match)!==1||version_compare((string)$match[1],'3.7.0','<'))$errors[]='Версия должна быть 3.7.0 или новее.';
$expect($blog,'public static function storedEntry','Нет получения сохранённой страницы.');
$expect($blog,'public static function isPubliclyReadable','Нет проверки публичной доступности страницы.');
$expect($blog,"return app_url('/page/'",'entryUrl не формирует адрес страницы.');
$expect($routes,"$router->get('/page/{slug}'",'Не зарегистрирован маршрут /page/{slug}.');
$expect($routes,"Blog::render('page'",'Публичный маршрут не использует шаблон page.');
$reject($routes,"$router->get('/blog/{slug}'",'Блог остался каноническим маршрутом.');
$reject($routes,"$router->get('/portfolio/{slug}'",'Портфолио осталось каноническим маршрутом.');
$expect($compat,"$router->get('/blog/{slug}'",'Нет перенаправления старых адресов блога.');
$expect($compat,"$router->get('/portfolio/{slug}'",'Нет перенаправления старых адресов портфолио.');
$expect($compat,"app_url('/page/'",'Старые адреса не переводятся на страницы.');
$expect($preview,"Blog::render('page'",'Studio Preview не использует страницу.');
$expect($preview,"e.type='page'",'Studio Preview не ограничен страницами.');
$expect($pages,'Blog::entryUrl($entry)','Список Studio не использует канонический адрес.');

$compatPos=strpos($index,'routes/blog-wordpress-compat.php');$modePos=strpos($index,'routes/blog-wordpress-mode.php');$entryPos=strpos($index,'routes/blog-entry-routing.php');$legacyPos=strpos($index,'routes/blog.php');
if($compatPos===false||$modePos===false||$entryPos===false||$legacyPos===false||$compatPos>$modePos||$modePos>$entryPos||$entryPos>$legacyPos)$errors[]='Маршруты зарегистрированы в неверном порядке.';

if(!defined('BASE_PATH'))define('BASE_PATH',$root);
require_once $root.'/app/Core.php';
$router=new \Kovcheg\Router();$hit=[];
$router->get('/page/{slug}',static function(array $params)use(&$hit):void{$hit=['slug'=>$params['slug']??''];});
$router->dispatch('GET','/page/test-page');
if(($hit['slug']??'')!=='test-page')$errors[]='Router не разобрал адрес страницы.';

if($errors){fwrite(STDERR,"Page routing audit failed:\n- ".implode("\n- ",$errors)."\n");exit(1);}echo "Page routing audit OK\n";
