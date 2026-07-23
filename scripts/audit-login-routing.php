<?php

declare(strict_types=1);

$root=dirname(__DIR__);$errors=[];
$read=static function(string $path)use($root,&$errors):string{$file=$root.'/'.$path;$data=is_file($file)?file_get_contents($file):false;if($data===false){$errors[]='Не удалось прочитать '.$path;return '';}return $data;};
$expect=static function(string $content,string $needle,string $message)use(&$errors):void{if(!str_contains($content,$needle))$errors[]=$message;};

$bootstrap=$read('app/bootstrap.php');
$index=$read('index.php');
$route=$read('routes/blog-auth.php');
$login=$read('views/login.php');
$css=$read('assets/css/blog-login.css');

$expect($bootstrap,"const APP_VERSION = '3.5.3';",'Версия приложения должна быть 3.5.3.');
$expect($bootstrap,"const ASSET_REVISION = '3.5.3-portal-login-routing';",'ASSET_REVISION должен соответствовать 3.5.3.');
$expect($index,"require __DIR__.'/routes/blog-auth.php';",'Blog-first маршруты авторизации не подключены.');

$authPosition=strpos($index,"require __DIR__.'/routes/blog-auth.php';");
$legacyPosition=strpos($index,"require __DIR__.'/routes/web.php';");
if($authPosition===false||$legacyPosition===false||$authPosition>$legacyPosition)$errors[]='Маршруты Blog-авторизации должны подключаться раньше legacy routes/web.php.';

$expect($route,"return Auth::isAdmin() ? '/studio' : '/account';",'После входа не настроено разделение Studio/личный кабинет.');
$expect($route,"\$router->get('/login'",'Отсутствует Blog-first GET /login.');
$expect($route,"\$router->post('/login'",'Отсутствует Blog-first POST /login.');
$expect($route,"\$router->get('/feed'",'Старая ссылка /feed не перехватывается Blog-маршрутизацией.');
$expect($route,'Auth::attempt($login, $password)','Новая авторизация не использует защищённый Auth::attempt.');
$expect($route,'Csrf::validate()','POST /login не защищён CSRF.');
$expect($route,'redirect(blog_auth_destination())','Маршруты входа и старой ленты не используют единый Blog-переход.');
if(str_contains($route,"redirect('/feed')"))$errors[]='Blog-first авторизация снова перенаправляет в старую социальную ленту.';

$expect($login,'blog-login-page','Страница входа не использует новую оболочку.');
$expect($login,'blog-login.css','Страница входа не подключает собственный стиль.');
$expect($login,'Вернуться на сайт','На странице входа отсутствует возврат на публичный сайт.');
$expect($login,'Войти в KOVCHEG Studio','Основная кнопка входа не ведёт в Studio-сценарий.');
$expect($login,'Ланцет Семён Борисович','На странице входа отсутствует обязательный копирайт.');
if(str_contains($login,'auth-split-promo'))$errors[]='На странице входа остался старый огромный рекламный блок.';

$expect($css,'.blog-login-shell','Отсутствует единая карточка входа.');
$expect($css,'.blog-login-grid','Страница входа не имеет адаптивной сетки.');
$expect($css,'body.guest-shell:has(.blog-login-page)>.footer{display:none}','Старый гостевой подвал конфликтует с новой страницей входа.');
$expect($css,'@media(max-width:520px)','Отсутствует мобильная версия страницы входа.');

if($errors){fwrite(STDERR,"Login routing audit failed:\n- ".implode("\n- ",$errors)."\n");exit(1);}echo "Login routing audit passed.\n";
