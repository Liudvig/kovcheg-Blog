<?php

declare(strict_types=1);

const BASE_PATH = __DIR__.'/..';
const APP_VERSION = '3.3.0';
const ASSET_REVISION = '3.3.0-growth';

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