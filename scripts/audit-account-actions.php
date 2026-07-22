<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$errors = [];

$read = static function (string $path) use ($root, &$errors): string {
    $file = $root.'/'.$path;
    $content = is_file($file) ? file_get_contents($file) : false;
    if ($content === false) {
        $errors[] = 'Не удалось прочитать '.$path;
        return '';
    }
    return $content;
};

$bootstrap = $read('app/bootstrap.php');
$index = $read('index.php');
$route = $read('routes/account.php');
$view = $read('views/account.php');
$layout = $read('views/layout.php');
$studio = $read('views/studio/layout.php');
$sidebar = $read('views/site-sidebar.php');
$css = $read('assets/css/blog-account.css');

$expect = static function (string $content, string $needle, string $message) use (&$errors): void {
    if (!str_contains($content, $needle)) $errors[] = $message;
};

$expect($bootstrap, "const APP_VERSION = '3.4.2';", 'Версия должна быть 3.4.2.');
$expect($index, "require __DIR__.'/routes/account.php';", 'Маршрут личного кабинета не подключён.');
$expect($route, "$router->get('/account'", 'Отсутствует GET /account.');
$expect($route, 'Auth::requireLogin()', 'Личный кабинет должен требовать вход.');
$expect($view, 'Личный кабинет', 'Отсутствует страница личного кабинета.');
$expect($view, "app_url('/logout')", 'В кабинете отсутствует ручной выход.');
$expect($sidebar, "['account','/account'", 'Личный кабинет отсутствует в пользовательском меню.');
$expect($layout, "app_url('/account')", 'Личный кабинет отсутствует в меню пользователя.');
$expect($layout, '<b>Выйти</b>', 'В глобальном меню отсутствует кнопка выхода.');
$expect($studio, 'Перейти на сайт', 'В Studio отсутствует кнопка перехода на сайт.');
$expect($studio, 'Личный кабинет', 'В Studio отсутствует кнопка личного кабинета.');
$expect($studio, 'studio-logout-action', 'В Studio отсутствует явная кнопка выхода.');
$expect($studio, "app_url('/logout')", 'Кнопка выхода Studio не отправляет POST /logout.');
$expect($studio, 'csrf_field()', 'Форма выхода Studio должна содержать CSRF.');
$expect($css, '.studio-site-action', 'Отсутствуют стили кнопки перехода на сайт.');
$expect($css, '.studio-account-action', 'Отсутствуют стили кнопки личного кабинета.');
$expect($css, '.studio-logout-action', 'Отсутствуют стили кнопки выхода.');

if ($errors) {
    fwrite(STDERR, "Account / Studio actions audit failed:\n- ".implode("\n- ", $errors)."\n");
    exit(1);
}

echo "Account / Studio actions audit passed.\n";
