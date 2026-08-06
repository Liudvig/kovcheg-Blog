<?php

declare(strict_types=1);

$root=dirname(__DIR__);$errors=[];
$read=static function(string $path)use($root,&$errors):string{$data=@file_get_contents($root.'/'.$path);if(!is_string($data)){$errors[]='Не удалось прочитать '.$path;return '';}return $data;};
$expect=static function(string $content,string $needle,string $message)use(&$errors):void{if(!str_contains($content,$needle))$errors[]=$message;};

$bootstrap=$read('app/bootstrap.php');
$blog=$read('app/Blog.php');
$index=$read('index.php');
$routes=$read('routes/blog-entry-routing.php');
$ux=$read('routes/blog-ux-fixes.php');
$contentIndex=$read('views/studio/content-index.php');

if(preg_match("/const APP_VERSION = '([^']+)';/",$bootstrap,$match)!==1||version_compare((string)$match[1],'3.5.8','<'))$errors[]='Версия приложения должна быть 3.5.8 или новее.';
$expect($blog,'public static function readableVisibilitySql','Нет единой проверки видимости материалов.');
$expect($blog,'public static function storedEntry','Нет получения сохранённого материала для безопасного preview.');
$expect($blog,'public static function canRead','Нет проверки фактической доступности материала.');
$expect($blog,"'users' => Auth::check()",'Материалы для пользователей не открываются авторизованным пользователям.');
$expect($blog,"['owner', 'admin', 'editor']",'Приватные материалы не учитывают редакторские роли.');

foreach(['/blog/{slug}','/page/{slug}','/portfolio/{slug}'] as $path)$expect($routes,$path,'Не зарегистрирован единый маршрут '.$path);
$expect($routes,'Blog::storedEntry','Маршрут не проверяет существующий черновик или закрытый материал.');
$expect($routes,"redirect('/studio/content/'",'Редактор не переводится на безопасный предпросмотр.');
$expect($routes,'Blog::entryUrl($other)','Нет канонического перенаправления при изменении типа материала.');
if(str_contains($routes,"header('Location: '.app_url('/portfolio')"))$errors[]='Маршрут портфолио не должен скрывать ошибку перенаправлением в архив.';
if(str_contains($ux,'$router->get(\'/portfolio/{slug}\''))$errors[]='В UX-файле остался отдельный конфликтующий маршрут портфолио.';

$entryPos=strpos($index,'routes/blog-entry-routing.php');
$blogPos=strpos($index,'routes/blog.php');
$uxPos=strpos($index,'routes/blog-ux-fixes.php');
if($entryPos===false||$blogPos===false||$uxPos===false||$entryPos>$uxPos||$entryPos>$blogPos)$errors[]='Единые маршруты материалов должны регистрироваться до старых обработчиков.';
$expect($contentIndex,'Blog::canRead($entry)','Список Studio не проверяет реальную доступность материала.');
$expect($contentIndex,"/preview'",'Для закрытых и неопубликованных материалов нет кнопки предпросмотра.');
$expect($contentIndex,'$direct?\'Просмотр\':\'Предпросмотр\'','Кнопка Studio не различает публичный просмотр и preview.');

if(!defined('BASE_PATH'))define('BASE_PATH',$root);
require_once $root.'/app/Core.php';
$router=new \Kovcheg\Router();$hit=[];
$router->get('/blog/{slug}',static function(array $params)use(&$hit):void{$hit=['type'=>'post','slug'=>$params['slug']??''];});
$router->get('/page/{slug}',static function(array $params)use(&$hit):void{$hit=['type'=>'page','slug'=>$params['slug']??''];});
$router->get('/portfolio/{slug}',static function(array $params)use(&$hit):void{$hit=['type'=>'portfolio','slug'=>$params['slug']??''];});
$router->dispatch('GET','/blog/test-material');
if(($hit['type']??'')!=='post'||($hit['slug']??'')!=='test-material')$errors[]='Ядро Router не разобрало адрес публикации.';
$router->dispatch('GET','/page/test-page');
if(($hit['type']??'')!=='page'||($hit['slug']??'')!=='test-page')$errors[]='Ядро Router не разобрало адрес страницы.';
$router->dispatch('GET','/portfolio/test-work');
if(($hit['type']??'')!=='portfolio'||($hit['slug']??'')!=='test-work')$errors[]='Ядро Router не разобрало адрес портфолио.';

if($errors){fwrite(STDERR,"Public entry routing audit failed:\n- ".implode("\n- ",$errors)."\n");exit(1);}echo "Public entry routing audit OK\n";
