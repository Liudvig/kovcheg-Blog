<?php

declare(strict_types=1);

$root=dirname(__DIR__);$errors=[];
$read=static function(string $path)use($root,&$errors):string{$file=$root.'/'.$path;$data=is_file($file)?file_get_contents($file):false;if($data===false){$errors[]='Не удалось прочитать '.$path;return '';}return $data;};
$expect=static function(string $content,string $needle,string $message)use(&$errors):void{if(!str_contains($content,$needle))$errors[]=$message;};

$bootstrap=$read('app/bootstrap.php');
$index=$read('index.php');
$route=$read('routes/blog-auth.php');
$login=$read('views/login.php');
$register=$read('views/register.php');
$css=$read('assets/css/blog-login.css');
$brandCss=$read('assets/css/blog-auth-brand.css');

$appVersion='';$assetRevision='';
if(preg_match("/const APP_VERSION = '([0-9.]+)';/",$bootstrap,$versionMatch)===1)$appVersion=$versionMatch[1];
if(preg_match("/const ASSET_REVISION = '([^']+)';/",$bootstrap,$assetMatch)===1)$assetRevision=$assetMatch[1];
if($appVersion===''||version_compare($appVersion,'3.8.0','<'))$errors[]='Версия приложения должна быть 3.8.0 или новее.';
if($assetRevision===''||$appVersion===''||!str_starts_with($assetRevision,$appVersion))$errors[]='ASSET_REVISION должен начинаться с текущей версии приложения.';
$expect($index,"require __DIR__.'/routes/blog-auth.php';",'Маршруты авторизации не подключены.');
$authPosition=strpos($index,"require __DIR__.'/routes/blog-auth.php';");
$legacyPosition=strpos($index,"require __DIR__.'/routes/web.php';");
if($authPosition===false||$legacyPosition===false||$authPosition>$legacyPosition)$errors[]='Маршруты CMS-авторизации должны подключаться раньше routes/web.php.';

$expect($route,"return Auth::isAdmin() ? '/studio' : '/account';",'После входа не настроено разделение Studio/личный кабинет.');
$expect($route,"\$router->get('/login'",'Отсутствует GET /login.');
$expect($route,"\$router->post('/login'",'Отсутствует POST /login.');
$expect($route,"\$router->get('/feed'",'Старая ссылка /feed не перехватывается CMS-маршрутизацией.');
$expect($route,'Auth::attempt($login, $password)','Авторизация не использует Auth::attempt.');
$expect($route,'Csrf::validate()','POST /login не защищён CSRF.');
$expect($route,'redirect(blog_auth_destination())','Вход и старая лента не используют единый переход.');
if(str_contains($route,"redirect('/feed')"))$errors[]='Авторизация снова переводит в старую социальную ленту.';

foreach(['blog-login-page','blog-login.css','blog-auth-brand.css','/brand/logo','/brand/login-background','Вернуться на сайт','Ланцет Семён Борисович'] as $token)$expect($login,$token,'На странице входа отсутствует: '.$token);
$expect($login,'<button class="blog-login-submit" type="submit">Войти</button>','Основная кнопка входа отсутствует.');
$expect($login,"setting('registration_mode','closed')!=='closed'",'Ссылка регистрации не учитывает режим регистрации.');
foreach(['/brand/logo','/brand/login-background','blog-registration.css'] as $token)$expect($register,$token,'Регистрация не использует брендинг: '.$token);
if(str_contains($login,'auth-split-promo'))$errors[]='На странице входа остался старый рекламный блок.';

foreach(['.blog-login-shell','.blog-login-grid','body.guest-shell:has(.blog-login-page)>.footer{display:none}','@media(max-width:520px)'] as $token)$expect($css,$token,'В базовом CSS входа отсутствует '.$token);
$expect($brandCss,'var(--brand-accent','#Основной цвет бренда не применяется к авторизации.');

if($errors){fwrite(STDERR,"Login routing audit failed:\n- ".implode("\n- ",$errors)."\n");exit(1);}echo "Login routing audit passed.\n";