<?php

declare(strict_types=1);

$root=dirname(__DIR__);$errors=[];
$read=static function(string $path)use($root,&$errors):string{$file=$root.'/'.$path;$content=is_file($file)?file_get_contents($file):false;if($content===false){$errors[]='Не удалось прочитать '.$path;return '';}return $content;};
$bootstrap=$read('app/bootstrap.php');$index=$read('index.php');$route=$read('routes/account.php');$account=$read('views/account-shell.php');$studio=$read('views/studio/layout.php');$sidebar=$read('views/site-sidebar.php');$mobile=$read('views/mobile-navigation.php');$css=$read('assets/css/blog-studio-unified.css');
$expect=static function(string $content,string $needle,string $message)use(&$errors):void{if(!str_contains($content,$needle))$errors[]=$message;};
$expect($bootstrap,"const APP_VERSION = '3.5.2';",'Версия должна быть 3.5.2.');
$expect($index,"require __DIR__.'/routes/account.php';",'Маршрут личного кабинета не подключён.');
$expect($route,'$router->get(\'/account\'','Отсутствует GET /account.');
$expect($route,'Auth::requireLogin()','Личный кабинет должен требовать вход.');
$expect($route,"require BASE_PATH.'/views/account-shell.php';",'Личный кабинет должен использовать отдельную единую оболочку.');
$expect($account,'Личный кабинет','Отсутствует страница личного кабинета.');
$expect($account,"app_url('/logout')",'В кабинете отсутствует ручной выход.');
$expect($account,'studio-footer','В кабинете отсутствует подвал.');
$expect($account,'Ланцет Семён Борисович','В кабинете отсутствует копирайт.');
$expect($sidebar,"['account','/account'",'Личный кабинет отсутствует в пользовательском меню.');
$expect($mobile,"['account','/account'",'Личный кабинет отсутствует в мобильном меню.');
$expect($studio,'Перейти на сайт','В Studio отсутствует кнопка перехода на сайт.');
$expect($studio,'Личный кабинет','В Studio отсутствует кнопка личного кабинета.');
$expect($studio,'studio-logout-action','В Studio отсутствует явная кнопка выхода в верхней панели.');
$expect($studio,'studio-footer','В Studio отсутствует подвал.');
$expect($studio,'studio-sidebar-meta','В левой колонке отсутствует служебный блок.');
if(preg_match('~<aside class="studio-sidebar".*?app_url\(\'/logout\'\).*?</aside>~s',$studio))$errors[]='Кнопка выхода не должна находиться в левой колонке Studio.';
$expect($css,'.studio-sidebar{position:fixed','Левая колонка Studio должна быть неподвижной.');
$expect($css,'.studio-main{margin-left:280px','Рабочая область должна быть отделена от фиксированного меню.');
$expect($css,'.account-studio-shell','Отсутствует единая дизайн-система личного кабинета.');
if($errors){fwrite(STDERR,"Account / Studio actions audit failed:\n- ".implode("\n- ",$errors)."\n");exit(1);}echo "Account / Studio actions audit passed.\n";