<?php

declare(strict_types=1);

const BASE_PATH = __DIR__.'/..';
const APP_VERSION = '3.1.0';
const ASSET_REVISION = '3.1.0-blog-studio';

if (!is_file(BASE_PATH.'/config/config.php')) {
    if (basename($_SERVER['SCRIPT_NAME'] ?? '') !== 'install.php') { header('Location: install.php'); exit; }
    return;
}

$CONFIG = require BASE_PATH.'/config/config.php';
if (!is_array($CONFIG) || !isset($CONFIG['app'], $CONFIG['database'])) {
    error_log('KOVCHEG CMS: invalid config/config.php structure.');
    http_response_code(500);
    exit('KOVCHEG CMS configuration error.');
}

$appKey=(string)($CONFIG['app']['key']??'');
$decodedKey=str_starts_with($appKey,'base64:')?base64_decode(substr($appKey,7),true):false;
if($decodedKey===false||strlen($decodedKey)<32){
    error_log('KOVCHEG CMS: application key is missing or too short.');
    http_response_code(500);
    exit('KOVCHEG CMS application key is not configured.');
}

date_default_timezone_set((string)($CONFIG['app']['timezone'] ?? 'UTC'));
$forwardedProto=strtolower(trim(explode(',',(string)($_SERVER['HTTP_X_FORWARDED_PROTO']??''))[0]??''));
$secure=(!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off')||$forwardedProto==='https';
$GLOBALS['CSP_NONCE']=base64_encode(random_bytes(18));

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Cross-Origin-Opener-Policy: same-origin');
header('Cross-Origin-Resource-Policy: same-site');
header('X-Permitted-Cross-Domain-Policies: none');
header("Permissions-Policy: geolocation=(), camera=(self), microphone=(self), payment=(), usb=()");
$csp="default-src 'self'; base-uri 'self'; form-action 'self'; frame-ancestors 'self'; object-src 'none'; script-src 'self' https://challenges.cloudflare.com 'nonce-".$GLOBALS['CSP_NONCE']."'; style-src 'self' 'unsafe-inline'; img-src 'self' data: blob: https:; media-src 'self' blob: https:; font-src 'self' data:; connect-src 'self' https:; frame-src https://challenges.cloudflare.com https://www.youtube-nocookie.com https://player.vimeo.com https://rutube.ru; worker-src 'self' blob:; manifest-src 'self'";
header('Content-Security-Policy: '.$csp);
if ($secure) header('Strict-Transport-Security: max-age=31536000; includeSubDomains');

session_name('KOVCHEGSESSID');
$sessionLifetime=15552000; // 180 days
@ini_set('session.use_strict_mode','1');
@ini_set('session.use_only_cookies','1');
@ini_set('session.cookie_httponly','1');
@ini_set('session.cookie_secure',$secure?'1':'0');
@ini_set('session.gc_maxlifetime',(string)$sessionLifetime);
@ini_set('session.cookie_lifetime',(string)$sessionLifetime);
$scriptDir=str_replace('\\','/',dirname((string)($_SERVER['SCRIPT_NAME']??'/')));
$cookiePath=rtrim($scriptDir,'/').'/';if($cookiePath==='//')$cookiePath='/';
session_set_cookie_params(['lifetime'=>$sessionLifetime,'path'=>$cookiePath,'secure'=>$secure,'httponly'=>true,'samesite'=>'Lax']);
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

require_once BASE_PATH.'/app/Core.php';
require_once BASE_PATH.'/app/functions.php';
require_once BASE_PATH.'/app/modern-ui.php';
require_once BASE_PATH.'/app/Blog.php';

set_exception_handler(static function (Throwable $error): void {
    log_error($error);
    if (cfg('app.debug', false)) render_system_error(500, 'Внутренняя ошибка', $error->getMessage());
    render_system_error(500, 'Внутренняя ошибка', 'Подробности записаны в журнал системы.');
});
register_shutdown_function(static function (): void {
    $last = error_get_last();
    if (!$last || !in_array((int)$last['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) return;
    $error = new ErrorException((string)$last['message'], 0, (int)$last['type'], (string)$last['file'], (int)$last['line']);
    log_error($error);
    if (!headers_sent()) render_system_error(500, 'Критическая ошибка', 'Подробности записаны в журнал системы.');
});

try { \Kovcheg\DB::connect($CONFIG['database']); }
catch (Throwable $e) { log_error($e); render_system_error(500, 'KOVCHEG CMS', 'Не удалось подключиться к базе данных.'); }

try { \Kovcheg\Auth::user(); } catch (Throwable $e) { log_error($e); }
$requestMethod = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));
$requestPath = (string)(parse_url((string)($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/');
$readOnlyRequest = (string)($_SERVER['HTTP_X_KOVCHEG_SOFT_NAVIGATION'] ?? '') === '1'
    || str_starts_with($requestPath, rtrim($cookiePath, '/').'/ajax/')
    || str_starts_with($requestPath, rtrim($cookiePath, '/').'/api/');
if ($requestMethod === 'GET' && $readOnlyRequest && session_status() === PHP_SESSION_ACTIVE) session_write_close();

\Kovcheg\Modules::boot();
