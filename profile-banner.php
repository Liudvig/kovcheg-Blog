<?php

declare(strict_types=1);

require __DIR__.'/app/bootstrap.php';
require_once __DIR__.'/app/profile-banner.php';

use Kovcheg\Auth;
use Kovcheg\DB;

$userId = (int)($_GET['user'] ?? 0);
$profile = $userId > 0 ? DB::one("SELECT id FROM users WHERE id=? AND is_active=1 AND approval_status='approved'", [$userId]) : null;
if (!$profile) abort(404, 'Профиль не найден.');
if (!can_view_profile($profile, Auth::check() ? Auth::id() : null)) abort(403, 'Баннер недоступен.');

$path = profile_banner_path($userId);
$file = $path !== '' ? BASE_PATH.'/storage/uploads/'.$path : '';
if ($file === '' || !is_file($file)) abort(404, 'Баннер не найден.');

$mime = (new finfo(FILEINFO_MIME_TYPE))->file($file) ?: 'image/jpeg';
if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) abort(415, 'Формат баннера недоступен.');

$etag = '"'.hash_file('sha256', $file).'"';
header('Content-Type: '.$mime);
header('Content-Length: '.filesize($file));
header('Cache-Control: private, max-age=86400');
header('ETag: '.$etag);
if (trim((string)($_SERVER['HTTP_IF_NONE_MATCH'] ?? '')) === $etag) {
    http_response_code(304);
    exit;
}
readfile($file);
exit;

