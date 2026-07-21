<?php

declare(strict_types=1);

require __DIR__.'/app/bootstrap.php';
require_once __DIR__.'/app/profile-banner.php';

use Kovcheg\Auth;
use Kovcheg\Csrf;

if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST') abort(405, 'Метод не поддерживается.');

Auth::requireLogin();
Csrf::validate();
$userId = Auth::id();
$action = (string)($_GET['action'] ?? 'upload');

if ($action === 'delete') {
    $old = profile_banner_path($userId);
    set_user_setting('profile_banner_path', '', $userId);
    set_user_setting('profile_banner_position', '50', $userId);
    if ($old !== '') @unlink(BASE_PATH.'/storage/uploads/'.$old);
    audit('profile.banner.delete', 'user', $userId);
    $_SESSION['flash_success'] = 'Баннер удалён.';
    redirect('/settings/general');
}

if ($action !== 'upload') abort(404, 'Действие не найдено.');

$file = $_FILES['banner'] ?? null;
$error = (int)($file['error'] ?? UPLOAD_ERR_NO_FILE);
$errors = [
    UPLOAD_ERR_INI_SIZE => 'Баннер больше лимита PHP upload_max_filesize.',
    UPLOAD_ERR_FORM_SIZE => 'Баннер больше разрешённого размера формы.',
    UPLOAD_ERR_PARTIAL => 'Баннер загрузился не полностью. Повторите отправку.',
    UPLOAD_ERR_NO_FILE => 'Выберите изображение для баннера.',
    UPLOAD_ERR_NO_TMP_DIR => 'На сервере отсутствует временная папка загрузки.',
    UPLOAD_ERR_CANT_WRITE => 'Сервер не смог записать загруженный файл.',
    UPLOAD_ERR_EXTENSION => 'Загрузка остановлена расширением PHP.',
];
if ($error !== UPLOAD_ERR_OK) abort($error === UPLOAD_ERR_INI_SIZE ? 413 : 422, $errors[$error] ?? 'Не удалось принять баннер.');

$temporary = (string)($file['tmp_name'] ?? '');
$size = (int)($file['size'] ?? 0);
if ($temporary === '' || !is_file($temporary) || !is_uploaded_file($temporary)) abort(422, 'Временный файл баннера не найден. Повторите загрузку.');
if ($size <= 0) abort(422, 'Загруженный баннер пуст.');
if ($size > 12 * 1024 * 1024) abort(413, 'Баннер должен быть не больше 12 МБ.');

$position=max(0,min(100,(int)($_POST['banner_position']??50)));
$old = profile_banner_path($userId);
$result = optimize_uploaded_image($temporary, 'banners/'.$userId.'/'.bin2hex(random_bytes(12)), 1800, 700, 86);
$stored = (string)$result['relative'];
$destination = BASE_PATH.'/storage/uploads/'.$stored;
if (!is_file($destination) || (int)filesize($destination) < 64) {
    @unlink($destination);
    abort(500, 'Сервер не смог сохранить баннер.');
}

set_user_setting('profile_banner_path', $stored, $userId);
set_user_setting('profile_banner_position', (string)$position, $userId);
if ($old !== '' && $old !== $stored) @unlink(BASE_PATH.'/storage/uploads/'.$old);
audit('profile.banner', 'user', $userId, ['position'=>$position]);
$_SESSION['flash_success'] = 'Баннер профиля обновлён.';
redirect('/settings/general');