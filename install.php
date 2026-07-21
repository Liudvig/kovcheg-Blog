<?php

declare(strict_types=1);

const INSTALL_ROOT = __DIR__;
const INSTALL_VERSION = '3.0';

if (is_file(INSTALL_ROOT.'/config/config.php')) {
    header('Location: index.php', true, 302);
    exit;
}

$forwardedProto=strtolower(trim(explode(',',(string)($_SERVER['HTTP_X_FORWARDED_PROTO']??''))[0]??''));
$secure=(!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off')||$forwardedProto==='https';
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: no-referrer');
header('Cache-Control: no-store, max-age=0');
header("Content-Security-Policy: default-src 'none'; style-src 'unsafe-inline'; form-action 'self'; base-uri 'none'; frame-ancestors 'none'");
@ini_set('session.use_strict_mode','1');
@ini_set('session.use_only_cookies','1');
@ini_set('session.cookie_httponly','1');
session_name('KOVCHEG_INSTALL');
$installDir=str_replace('\\','/',dirname((string)($_SERVER['SCRIPT_NAME']??'/install.php')));$installCookiePath=rtrim($installDir,'/').'/';if($installCookiePath==='//')$installCookiePath='/';
session_set_cookie_params(['lifetime'=>0,'path'=>$installCookiePath,'secure'=>$secure,'httponly'=>true,'samesite'=>'Strict']);
session_start();
$_SESSION['install_csrf'] ??= bin2hex(random_bytes(32));

function install_h(string $value): string { return htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); }
function install_value(string $key, string $default = ''): string { return (string)($_POST[$key] ?? $default); }

$errors=[];
$success=false;

if (strtoupper((string)($_SERVER['REQUEST_METHOD']??'GET')) === 'POST') {
    if (!hash_equals((string)$_SESSION['install_csrf'], (string)($_POST['csrf'] ?? ''))) {
        $errors[]='Сессия установки устарела. Обновите страницу и попробуйте снова.';
    }

    $siteName=trim(install_value('site_name','KOVCHEG CMS'));
    $dbHost=trim(install_value('db_host','localhost'));
    $dbPort=(int)install_value('db_port','3306');
    $dbName=trim(install_value('db_name'));
    $dbUser=trim(install_value('db_user'));
    $dbPassword=install_value('db_password');
    $email=mb_strtolower(trim(install_value('admin_email')));
    $username=trim(install_value('admin_username'));
    $displayName=trim(install_value('admin_name','Владелец'));
    $password=install_value('admin_password');
    $passwordConfirm=install_value('admin_password_confirm');

    if ($siteName===''||mb_strlen($siteName)>100) $errors[]='Укажите название сайта до 100 символов.';
    if ($dbHost===''||strlen($dbHost)>255||preg_match('/[\x00-\x20]/',$dbHost)) $errors[]='Проверьте хост базы данных.';
    if ($dbName===''||strlen($dbName)>128||$dbUser===''||strlen($dbUser)>128||$dbPort<1||$dbPort>65535) $errors[]='Заполните параметры базы данных.';
    if (!filter_var($email,FILTER_VALIDATE_EMAIL)) $errors[]='Укажите корректный email владельца.';
    if (!preg_match('/^[A-Za-z0-9_]{3,80}$/',$username)) $errors[]='Ник: 3–80 символов, только латинские буквы, цифры и подчёркивание.';
    if (mb_strlen($displayName)<2||mb_strlen($displayName)>150) $errors[]='Укажите имя владельца.';
    if (strlen($password)<10) $errors[]='Пароль владельца должен быть не короче 10 символов.';
    if (!hash_equals($password,$passwordConfirm)) $errors[]='Пароли владельца не совпадают.';

    if (!$errors) {
        try {
            $pdo=new PDO(
                'mysql:host='.$dbHost.';port='.$dbPort.';dbname='.$dbName.';charset=utf8mb4',
                $dbUser,
                $dbPassword,
                [
                    PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES=>false,
                ]
            );
            $schema=require INSTALL_ROOT.'/database/schema.php';
            if (!is_string($schema)||trim($schema)==='') throw new RuntimeException('Схема установки отсутствует.');
            foreach (preg_split('/;\s*(?:\r?\n|$)/',$schema) as $statement) {
                $statement=trim($statement);
                if ($statement!=='') $pdo->exec($statement);
            }
            $existing=$pdo->query('SELECT id FROM users LIMIT 1')->fetch();
            if (!$existing) {
                $algorithm=defined('PASSWORD_ARGON2ID')?PASSWORD_ARGON2ID:PASSWORD_DEFAULT;
                $options=$algorithm===PASSWORD_ARGON2ID?['memory_cost'=>65536,'time_cost'=>4,'threads'=>2]:[];
                $hash=password_hash($password,$algorithm,$options);
                if ($hash===false) throw new RuntimeException('Не удалось защитить пароль владельца.');
                $insert=$pdo->prepare("INSERT INTO users (email,username,display_name,password_hash,role,is_verified,is_active,approval_status,approved_at,created_at,updated_at) VALUES (?,?,?,?, 'owner',1,1,'approved',CURRENT_TIMESTAMP,CURRENT_TIMESTAMP,CURRENT_TIMESTAMP)");
                $insert->execute([$email,$username,$displayName,$hash]);
                $ownerId=(int)$pdo->lastInsertId();
                $pdo->prepare('INSERT IGNORE INTO user_permissions (user_id,updated_at) VALUES (?,CURRENT_TIMESTAMP)')->execute([$ownerId]);
            }

            $scheme=$secure?'https':'http';
            $host=preg_replace('/[^A-Za-z0-9.:-]/','',(string)($_SERVER['HTTP_HOST']??'localhost'))?:'localhost';
            $config=[
                'app'=>[
                    'name'=>$siteName,
                    'url'=>$scheme.'://'.$host,
                    'key'=>'base64:'.base64_encode(random_bytes(32)),
                    'debug'=>false,
                    'timezone'=>'Europe/Moscow',
                    'version'=>INSTALL_VERSION,
                ],
                'database'=>[
                    'driver'=>'mysql','host'=>$dbHost,'port'=>$dbPort,'name'=>$dbName,
                    'user'=>$dbUser,'password'=>$dbPassword,'charset'=>'utf8mb4',
                ],
            ];
            $configPath=INSTALL_ROOT.'/config/config.php';
            $written=file_put_contents($configPath,"<?php\n\nreturn ".var_export($config,true).";\n",LOCK_EX);
            if ($written===false) throw new RuntimeException('Не удалось записать конфигурацию.');
            @chmod($configPath,0640);
            session_regenerate_id(true);
            unset($_SESSION['install_csrf']);
            $_SESSION['install_done']=true;
            $success=true;
        } catch (Throwable $error) {
            error_log('[KOVCHEG installer] '.$error::class.': '.$error->getMessage());
            $errors[]='Установка не завершена. Проверьте параметры базы, права каталога config и журнал PHP.';
        }
    }
}
?><!doctype html>
<html lang="ru"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Установка KOVCHEG CMS</title>
<style>body{margin:0;background:#101827;color:#e8edf6;font:16px system-ui,sans-serif}.box{max-width:680px;margin:44px auto;padding:28px;background:#172235;border:1px solid #2d405e;border-radius:16px}h1{margin-top:0}label{display:block;margin:14px 0 5px}input{box-sizing:border-box;width:100%;padding:10px;border:1px solid #3b5174;border-radius:8px;background:#0d1522;color:#fff}button{margin-top:22px;padding:11px 16px;border:0;border-radius:8px;background:#4b8cff;color:#fff;font-weight:700;cursor:pointer}.error{background:#48242a;padding:12px;border-radius:8px}.ok{background:#16402c;padding:16px;border-radius:8px}small{color:#aebbd0}a{color:#9dc4ff}</style>
<body><main class="box"><h1>KOVCHEG CMS <?=install_h(INSTALL_VERSION)?></h1>
<?php if ($success): ?><div class="ok">Установка завершена. <a href="index.php">Открыть KOVCHEG CMS</a></div>
<?php else: ?><?php if ($errors): ?><div class="error"><?php foreach ($errors as $error): ?><div><?=install_h($error)?></div><?php endforeach; ?></div><?php endif; ?>
<p><small>Создайте пустую базу и отдельного пользователя базы. Введённые реквизиты сохраняются только в закрытом файле <code>config/config.php</code>.</small></p>
<form method="post" autocomplete="off"><input type="hidden" name="csrf" value="<?=install_h((string)$_SESSION['install_csrf'])?>">
<label>Название сайта</label><input name="site_name" required maxlength="100" value="<?=install_h(install_value('site_name','KOVCHEG CMS'))?>">
<label>Хост базы</label><input name="db_host" required maxlength="255" value="<?=install_h(install_value('db_host','localhost'))?>">
<label>Порт базы</label><input name="db_port" required inputmode="numeric" value="<?=install_h(install_value('db_port','3306'))?>">
<label>Имя базы</label><input name="db_name" required maxlength="128" value="<?=install_h(install_value('db_name'))?>">
<label>Пользователь базы</label><input name="db_user" required maxlength="128" value="<?=install_h(install_value('db_user'))?>">
<label>Пароль базы</label><input type="password" name="db_password" autocomplete="new-password">
<label>Email владельца</label><input type="email" name="admin_email" required autocomplete="email" value="<?=install_h(install_value('admin_email'))?>">
<label>Ник владельца</label><input name="admin_username" required pattern="[A-Za-z0-9_]{3,80}" value="<?=install_h(install_value('admin_username'))?>">
<label>Отображаемое имя</label><input name="admin_name" required maxlength="150" value="<?=install_h(install_value('admin_name','Владелец'))?>">
<label>Пароль владельца</label><input type="password" name="admin_password" required minlength="10" autocomplete="new-password">
<label>Повторите пароль</label><input type="password" name="admin_password_confirm" required minlength="10" autocomplete="new-password">
<button type="submit">Установить KOVCHEG CMS</button></form><?php endif; ?></main></body></html>
