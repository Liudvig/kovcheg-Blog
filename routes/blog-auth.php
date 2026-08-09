<?php

declare(strict_types=1);

use Kovcheg\Auth;
use Kovcheg\Csrf;
use Kovcheg\DB;
use Kovcheg\View;

function blog_auth_destination(): string
{
    return Auth::isAdmin() ? '/studio' : '/account';
}

function blog_registration_mode(): string
{
    $mode = (string)setting('registration_mode', 'closed');
    if ($mode === 'email_approval') $mode = 'manual';
    return in_array($mode, ['closed','manual','email_auto'], true) ? $mode : 'closed';
}

$router->get('/login', function (): void {
    if (Auth::check()) redirect(blog_auth_destination());
    View::render('login', ['title'=>'Вход в KOVCHEG CMS']);
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

$router->post('/logout', function (): void {
    Csrf::validate();
    Auth::logout();
    redirect('/login');
});

$router->get('/register', function (): void {
    if (Auth::check()) redirect(blog_auth_destination());

    $mode = blog_registration_mode();
    if ($mode === 'closed') abort(404, 'Регистрация сейчас закрыта.');

    View::render('register', [
        'title'=>'Регистрация',
        'registered'=>false,
        'registrationMode'=>$mode,
        'captcha'=>registration_captcha_prepare(),
    ]);
});

$router->post('/register', function (): void {
    $mode = blog_registration_mode();
    if ($mode === 'closed') abort(403, 'Регистрация сейчас закрыта.');
    Csrf::validate();

    $email = mb_lower(trim((string)($_POST['email'] ?? '')));
    registration_rate_check($email);
    if (!registration_captcha_validate($_POST)) {
        registration_rate_fail($email);
        abort(422, 'Проверка защиты не пройдена. Обновите страницу и повторите.');
    }

    $username = normalize_username((string)($_POST['username'] ?? ''));
    $first = trim((string)($_POST['first_name'] ?? ''));
    $last = trim((string)($_POST['last_name'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $confirm = (string)($_POST['password_confirmation'] ?? '');

    if (
        !filter_var($email, FILTER_VALIDATE_EMAIL)
        || $first === ''
        || $last === ''
        || strlen($password) < 10
        || !hash_equals($password, $confirm)
    ) {
        abort(422, 'Проверьте имя, фамилию, email и совпадение паролей от 10 символов.');
    }
    if (!valid_username($username)) {
        abort(422, 'Ник обязателен: 3–40 латинских букв, цифр или подчёркиваний.');
    }
    if (DB::one('SELECT id FROM users WHERE email=? OR username=? LIMIT 1', [$email,$username])) {
        abort(422, 'Такой email или ник уже зарегистрирован.');
    }

    $display = trim($first.' '.$last);
    $auto = $mode === 'email_auto';
    if ($auto) {
        $id = DB::insert(
            "INSERT INTO users
                (email,username,first_name,last_name,display_name,password_hash,role,is_active,approval_status,approved_at,created_at,updated_at)
             VALUES (?,?,?,?,?,?,'user',1,'approved',CURRENT_TIMESTAMP,CURRENT_TIMESTAMP,CURRENT_TIMESTAMP)",
            [$email,$username,$first,$last,$display,password_hash_secure($password)]
        );
    } else {
        $id = DB::insert(
            "INSERT INTO users
                (email,username,first_name,last_name,display_name,password_hash,role,is_active,approval_status,approved_at,created_at,updated_at)
             VALUES (?,?,?,?,?,?,'user',0,'pending',NULL,CURRENT_TIMESTAMP,CURRENT_TIMESTAMP)",
            [$email,$username,$first,$last,$display,password_hash_secure($password)]
        );
    }

    registration_rate_success($email);
    audit($auto ? 'registration.auto' : 'registration.request', 'user', $id);

    if ($auto) {
        $_SESSION['flash_success'] = 'Аккаунт создан. Теперь войдите.';
        redirect('/login');
    }

    View::render('register', [
        'title'=>'Регистрация отправлена',
        'registered'=>true,
        'registrationMode'=>$mode,
        'captcha'=>[],
    ]);
});

// Backward compatibility for saved tabs from early KOVCHEG builds.
$router->get('/feed', function (): void {
    Auth::requireLogin();
    redirect(blog_auth_destination());
});
