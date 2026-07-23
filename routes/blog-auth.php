<?php

declare(strict_types=1);

use Kovcheg\Auth;
use Kovcheg\Csrf;
use Kovcheg\View;

/**
 * Return the proper landing page for the KOVCHEG Blog product.
 * Social feed remains an optional legacy feature and is never the default.
 */
function blog_auth_destination(): string
{
    return Auth::isAdmin() ? '/studio' : '/account';
}

$router->get('/login', function (): void {
    if (Auth::check()) redirect(blog_auth_destination());
    View::render('login', ['title' => 'Вход в KOVCHEG Blog']);
});

$router->post('/login', function (): void {
    Csrf::validate();

    $login = trim((string)($_POST['login'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    auth_rate_check($login);

    if (!Auth::attempt($login, $password)) {
        auth_rate_fail($login);
        usleep(random_int(250000, 520000));
        $_SESSION['flash_error'] = 'Неверный логин, пароль или учётная запись пока недоступна.';
        redirect('/login');
    }

    auth_rate_success($login);
    redirect(blog_auth_destination());
});
