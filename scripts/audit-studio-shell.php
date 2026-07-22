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
$core = $read('app/Core.php');
$layout = $read('views/layout.php');
$login = $read('views/login.php');
$css = $read('assets/css/blog-admin-shell.css');
$js = $read('assets/js/blog-admin-shell.js');

$required = [
    [str_contains($bootstrap, "const APP_VERSION = '3.4.1';"), 'Версия приложения должна быть 3.4.1.'],
    [str_contains($bootstrap, '$sessionLifetime=315360000;'), 'Сессионная cookie должна продлеваться на 10 лет.'],
    [str_contains($bootstrap, 'setcookie(session_name(),session_id(),$sessionCookieOptions)'), 'Сессионная cookie должна обновляться при активном использовании.'],
    [str_contains($core, 'private const REMEMBER_DAYS = 3650;'), 'Постоянный вход должен продлеваться на 10 лет.'],
    [!str_contains($core, '$newValidator'), 'Validator нельзя вращать при восстановлении параллельных запросов.'],
    [str_contains($core, 'Validator remains stable'), 'Не найдено исправление гонки persistent-login.'],
    [str_contains($core, "HTTP_X_FORWARDED_PROTO"), 'Remember-cookie должна учитывать HTTPS за reverse proxy.'],
    [str_contains($layout, 'blog-admin-shell.css'), 'Layout не подключает отдельные стили Studio.'],
    [str_contains($layout, 'blog-admin-shell.js'), 'Layout не подключает логику Studio.'],
    [str_contains($login, 'Вход в Studio'), 'Страница входа всё ещё не относится к KOVCHEG Blog Studio.'],
    [!str_contains($login, 'Ваше закрытое пространство для общения'), 'На странице входа остался старый текст универсальной CMS.'],
    [str_contains($css, 'grid-template-columns:280px minmax(0,1fr)'), 'Не найдена фиксированная двухколоночная сетка админки.'],
    [str_contains($css, 'overflow-y:auto'), 'Рабочая область должна прокручиваться отдельно.'],
    [str_contains($css, 'scrollbar-width:none'), 'Firefox scrollbar должен быть скрыт.'],
    [str_contains($css, '::-webkit-scrollbar'), 'Chromium/WebKit scrollbar должен быть скрыт.'],
    [str_contains($css, '.admin-content>.admin-footer'), 'Не найдены стили полноценного подвала админки.'],
    [str_contains($js, "['KOVCHEG CMS', 'KOVCHEG Blog']"), 'Не найдена очистка старого названия в админке.'],
];

foreach ($required as [$ok, $message]) {
    if (!$ok) $errors[] = $message;
}

if ($errors) {
    fwrite(STDERR, "Studio shell audit failed:\n- ".implode("\n- ", $errors)."\n");
    exit(1);
}

echo "Studio shell audit passed.\n";
