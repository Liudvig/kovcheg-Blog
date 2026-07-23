<?php

declare(strict_types=1);

use Kovcheg\Auth;
use Kovcheg\Csrf;
use Kovcheg\View;

/**
 * Return the proper landing page for the KOVCHEG Blog product.
 * The legacy social feed is not part of the Blog navigation flow.
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

// Old installations and saved browser tabs may still open /feed.
// Keep the URL safe, but route it into the Blog product instead of the legacy UI.
$router->get('/feed', function (): void {
    Auth::requireLogin();
    redirect(blog_auth_destination());
});
