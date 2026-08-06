<?php

declare(strict_types=1);

$root=dirname(__DIR__);$errors=[];
$read=static function(string $path)use($root,&$errors):string{$data=@file_get_contents($root.'/'.$path);if(!is_string($data)){$errors[]='Не удалось прочитать '.$path;return '';}return $data;};
$expect=static function(string $content,string $needle,string $message)use(&$errors):void{if(!str_contains($content,$needle))$errors[]=$message;};

$bootstrap=$read('app/bootstrap.php');
$blog=$read('app/Blog.php');
$index=$read('index.php');
$routes=$read('routes/blog-entry-routing.php');
$compat=$read('routes/blog-wordpress-compat.php');
$wpRoutes=$read('routes/blog-wordpress-mode.php');
$ux=$read('routes/blog-ux-fixes.php');
$contentIndex=$read('views/studio/entries-index.php');

if(preg_match("/const APP_VERSION = '([^']+)';/",$bootstrap,$match)!==1||version_compare((string)$match[1],'3.6.0','<'))$errors[]='Версия приложения должна быть 3.6.0 или новее.';
$expect($blog,'public static function readableVisibilitySql','Нет единой проверки видимости материалов.');
$expect($blog,'public static function storedEntry','Нет получения сохранённого материала для редакторского просмотра.');
$expect($blog,'public static function canRead','Нет проверки фактической доступности материала.');
$expect($blog,'public static function isPubliclyReadable','Нет отдельной проверки публичной доступности материала.');
foreach(['/blog/{slug}','/page/{slug}'] as $path)$expect($routes,$path,'Не зарегистрирован маршрут '.$path);
if(str_contains($routes,'/portfolio/{slug}'))$errors[]='Портфолио осталось отдельным типом публичного материала.';
$expect($routes,'Blog::storedEntry','Маршрут не проверяет черновик или закрытый материал.');
$expect($routes,'kovcheg_render_entry_record($stored, true);','Редактор не видит сохранённый материал по итоговому адресу.');
$expect($routes,'Blog::entryUrl($other)','Нет канонического перенаправления при изменении типа.');
$expect($ux,'if(Blog::canRead($entry))','Studio Preview не распознаёт опубликованный материал.');
$expect($ux,"e.type IN ('post','page')",'Studio Preview не ограничен записями и страницами.');
$expect($compat,"\$router->get('/portfolio'",'Старый адрес портфолио не обслуживается совместимым архивом.');
$expect($compat,"'entries'=>Blog::entries('post',100)",'Совместимый архив портфолио должен показывать только записи.');

$compatPos=strpos($index,'routes/blog-wordpress-compat.php');
$wpPos=strpos($index,'routes/blog-wordpress-mode.php');
$entryPos=strpos($index,'routes/blog-entry-routing.php');
$uxPos=strpos($index,'routes/blog-ux-fixes.php');
$blogPos=strpos($index,'routes/blog.php');
if($compatPos===false||$wpPos===false||$entryPos===false||$uxPos===false||$blogPos===false||$compatPos>$wpPos||$wpPos>$entryPos||$entryPos>$uxPos||$entryPos>$blogPos)$errors[]='Compatibility, WordPress и публичные маршруты зарегистрированы в неверном порядке.';
$expect($contentIndex,'Blog::isPubliclyReadable($entry)','Список Studio не различает просмотр и предпросмотр.');
$expect($contentIndex,'Blog::entryUrl($entry)','Список Studio не использует канонический адрес.');
$expect($contentIndex,'Посмотреть','В списке Studio отсутствует публичный просмотр.');
$expect($contentIndex,"/preview'",'Для черновиков отсутствует предпросмотр.');

if(!defined('BASE_PATH'))define('BASE_PATH',$root);
require_once $root.'/app/Core.php';
$router=new \Kovcheg\Router();$hit=[];
$router->get('/blog/{slug}',static function(array $params)use(&$hit):void{$hit=['type'=>'post','slug'=>$params['slug']??''];});
$router->get('/page/{slug}',static function(array $params)use(&$hit):void{$hit=['type'=>'page','slug'=>$params['slug']??''];});
$router->dispatch('GET','/blog/test-record');
if(($hit['type']??'')!=='post'||($hit['slug']??'')!=='test-record')$errors[]='Ядро Router не разобрало адрес записи.';
$router->dispatch('GET','/page/test-page');
if(($hit['type']??'')!=='page'||($hit['slug']??'')!=='test-page')$errors[]='Ядро Router не разобрало адрес страницы.';

if($errors){fwrite(STDERR,"Public entry routing audit failed:\n- ".implode("\n- ",$errors)."\n");exit(1);}echo "Public entry routing audit OK\n";
