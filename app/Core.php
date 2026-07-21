<?php

declare(strict_types=1);

namespace Kovcheg;

use PDO;
use PDOException;
use RuntimeException;
use Throwable;

final class DB
{
    private static ?PDO $pdo = null;

    public static function connect(array $cfg): PDO
    {
        if (self::$pdo) return self::$pdo;
        $driver = $cfg['driver'] ?? 'mysql';
        if ($driver === 'sqlite') {
            $dsn = 'sqlite:' . ($cfg['path'] ?? BASE_PATH . '/storage/database.sqlite');
            self::$pdo = new PDO($dsn);
        } else {
            $host = $cfg['host'] ?? 'localhost';
            $port = (int)($cfg['port'] ?? 3306);
            $name = $cfg['name'] ?? '';
            $charset = $cfg['charset'] ?? 'utf8mb4';
            $dsn = "mysql:host={$host};port={$port};dbname={$name};charset={$charset}";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            if (defined('PDO::MYSQL_ATTR_INIT_COMMAND')) {
                $options[PDO::MYSQL_ATTR_INIT_COMMAND] = 'SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci';
            }
            self::$pdo = new PDO($dsn, $cfg['user'] ?? '', $cfg['password'] ?? '', $options);
        }
        self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        self::$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        return self::$pdo;
    }

    public static function pdo(): PDO
    {
        if (!self::$pdo) throw new RuntimeException('Database is not connected.');
        return self::$pdo;
    }

    public static function one(string $sql, array $params = []): ?array
    {
        $st = self::pdo()->prepare($sql); $st->execute($params);
        $row = $st->fetch(); return $row === false ? null : $row;
    }

    public static function all(string $sql, array $params = []): array
    {
        $st = self::pdo()->prepare($sql); $st->execute($params); return $st->fetchAll();
    }

    public static function run(string $sql, array $params = []): int
    {
        $st = self::pdo()->prepare($sql); $st->execute($params); return $st->rowCount();
    }

    public static function insert(string $sql, array $params = []): int
    {
        self::run($sql, $params); return (int)self::pdo()->lastInsertId();
    }
}

final class Auth
{
    private static ?array $user = null;
    private const REMEMBER_COOKIE = 'KOVCHEGREMEMBER';
    private const REMEMBER_DAYS = 180;

    private static function cookieOptions(int $expires): array
    {
        $secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        $scriptDir=str_replace('\\','/',dirname((string)($_SERVER['SCRIPT_NAME']??'/')));$path=rtrim($scriptDir,'/').'/';if($path==='//')$path='/';
        return ['expires'=>$expires,'path'=>$path,'secure'=>$secure,'httponly'=>true,'samesite'=>'Lax'];
    }

    private static function parseRememberCookie(): ?array
    {
        $raw=(string)($_COOKIE[self::REMEMBER_COOKIE]??'');
        if(!preg_match('/^([a-f0-9]{36}):([a-f0-9]{64})$/',$raw,$m))return null;
        return ['selector'=>$m[1],'validator'=>$m[2]];
    }

    private static function expireRememberCookie(): void
    {
        unset($_COOKIE[self::REMEMBER_COOKIE]);
        if(!headers_sent())setcookie(self::REMEMBER_COOKIE,'',self::cookieOptions(time()-3600));
    }

    private static function restorePersistentLogin(): int
    {
        $cookie=self::parseRememberCookie();if(!$cookie)return 0;
        try{
            DB::run('DELETE FROM user_remember_tokens WHERE expires_at<=CURRENT_TIMESTAMP');
            $token=DB::one("SELECT t.*,u.is_active,u.approval_status FROM user_remember_tokens t JOIN users u ON u.id=t.user_id WHERE t.selector=? AND t.expires_at>CURRENT_TIMESTAMP LIMIT 1",[$cookie['selector']]);
            if(!$token||empty($token['is_active'])||($token['approval_status']??'')!=='approved'||!hash_equals((string)$token['validator_hash'],hash('sha256',$cookie['validator']))){
                if($token)DB::run('DELETE FROM user_remember_tokens WHERE selector=?',[$cookie['selector']]);
                self::expireRememberCookie();return 0;
            }
            $newValidator=bin2hex(random_bytes(32));$expires=time()+self::REMEMBER_DAYS*86400;
            DB::run('UPDATE user_remember_tokens SET validator_hash=?,expires_at=FROM_UNIXTIME(?),last_used_at=CURRENT_TIMESTAMP WHERE selector=?',[hash('sha256',$newValidator),$expires,$cookie['selector']]);
            $_COOKIE[self::REMEMBER_COOKIE]=$cookie['selector'].':'.$newValidator;
            if(!headers_sent())setcookie(self::REMEMBER_COOKIE,$_COOKIE[self::REMEMBER_COOKIE],self::cookieOptions($expires));
            $_SESSION['user_id']=(int)$token['user_id'];
            return (int)$token['user_id'];
        }catch(Throwable){self::expireRememberCookie();return 0;}
    }

    private static function ensurePersistentLogin(int $userId): void
    {
        if($userId<1||!empty($_SESSION['impersonator_user_id'])||headers_sent())return;
        $cookie=self::parseRememberCookie();
        try{
            if($cookie){
                $valid=DB::one('SELECT id FROM user_remember_tokens WHERE user_id=? AND selector=? AND expires_at>CURRENT_TIMESTAMP LIMIT 1',[$userId,$cookie['selector']]);
                if($valid)return;
                DB::run('DELETE FROM user_remember_tokens WHERE selector=?',[$cookie['selector']]);
            }
            $selector=bin2hex(random_bytes(18));$validator=bin2hex(random_bytes(32));$expires=time()+self::REMEMBER_DAYS*86400;
            DB::run('INSERT INTO user_remember_tokens (user_id,selector,validator_hash,expires_at,last_used_at,created_at) VALUES (?,?,?,FROM_UNIXTIME(?),CURRENT_TIMESTAMP,CURRENT_TIMESTAMP)',[$userId,$selector,hash('sha256',$validator),$expires]);
            $_COOKIE[self::REMEMBER_COOKIE]=$selector.':'.$validator;
            setcookie(self::REMEMBER_COOKIE,$_COOKIE[self::REMEMBER_COOKIE],self::cookieOptions($expires));
        }catch(Throwable){}
    }

    private static function forgetPersistentLogin(): void
    {
        $cookie=self::parseRememberCookie();
        if($cookie)try{DB::run('DELETE FROM user_remember_tokens WHERE selector=?',[$cookie['selector']]);}catch(Throwable){}
        self::expireRememberCookie();
    }

    public static function user(): ?array
    {
        if (self::$user !== null) return self::$user ?: null;
        $id = (int)($_SESSION['user_id'] ?? 0);
        if (!$id) $id=self::restorePersistentLogin();
        if (!$id) { self::$user = []; return null; }
        self::$user = DB::one("SELECT * FROM users WHERE id=? AND is_active=1 AND approval_status='approved'", [$id]) ?? [];
        if(!self::$user){unset($_SESSION['user_id']);self::forgetPersistentLogin();return null;}
        self::ensurePersistentLogin($id);
        return self::$user;
    }

    public static function id(): int { return (int)(self::user()['id'] ?? 0); }
    public static function check(): bool { return self::user() !== null; }
    public static function isAdmin(): bool { return in_array(self::user()['role'] ?? '', ['owner','admin'], true); }
    public static function isOwner(): bool { return (self::user()['role'] ?? '') === 'owner'; }
    public static function authenticateApiUser(array $user): void { self::$user=$user; }

    public static function attempt(string $login, string $password): bool
    {
        $user = DB::one("SELECT * FROM users WHERE (email=? OR username=?) AND is_active=1 AND approval_status='approved' LIMIT 1", [$login, $login]);
        if (!$user || !password_verify($password, $user['password_hash'])) return false;
        $algo=defined('PASSWORD_ARGON2ID')?PASSWORD_ARGON2ID:PASSWORD_DEFAULT;
        $options=$algo===PASSWORD_ARGON2ID?['memory_cost'=>65536,'time_cost'=>4,'threads'=>2]:[];
        if(password_needs_rehash((string)$user['password_hash'],$algo,$options)){
            try{$newHash=password_hash($password,$algo,$options);if($newHash)DB::run('UPDATE users SET password_hash=?,updated_at=CURRENT_TIMESTAMP WHERE id=?',[$newHash,$user['id']]);}catch(Throwable){}
        }
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int)$user['id'];
        self::$user = $user;
        self::ensurePersistentLogin((int)$user['id']);
        DB::run('UPDATE users SET last_seen_at=CURRENT_TIMESTAMP WHERE id=?', [$user['id']]);
        audit('auth.login', 'user', (int)$user['id']);
        return true;
    }

    public static function logout(): void
    {
        if (self::id()) audit('auth.logout', 'user', self::id());
        self::forgetPersistentLogin();
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time()-42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy(); self::$user = [];
    }

    public static function requireLogin(): void
    {
        if (!self::check()) redirect('/login');
    }

    public static function requireAdmin(): void
    {
        self::requireLogin();
        if (!self::isAdmin()) abort(403, 'Недостаточно прав.');
    }
}

final class Csrf
{
    public static function token(): string
    {
        if (empty($_SESSION['_csrf'])) $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        return $_SESSION['_csrf'];
    }

    public static function validate(): void
    {
        $token = $_POST['_csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
        if (!is_string($token) || !hash_equals(self::token(), $token)) abort(419, 'Сессия формы истекла. Обновите страницу.');
    }
}

final class Router
{
    private array $routes = [];
    public function get(string $path, callable $handler): void { $this->add('GET', $path, $handler); }
    public function post(string $path, callable $handler): void { $this->add('POST', $path, $handler); }
    public function any(string $path, callable $handler): void { $this->add('ANY', $path, $handler); }

    private function add(string $method, string $path, callable $handler): void
    {
        $keys = [];
        $regex = preg_replace_callback('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', function($m) use (&$keys){ $keys[]=$m[1]; return '([^/]+)'; }, $path);
        $this->routes[] = [$method, '#^'.rtrim((string)$regex, '/').'/?$#', $keys, $handler];
    }

    public function dispatch(string $method, string $path): void
    {
        foreach ($this->routes as [$m,$regex,$keys,$handler]) {
            if ($m !== 'ANY' && $m !== $method) continue;
            if (preg_match($regex, $path, $matches)) {
                array_shift($matches); $params=[];
                foreach ($keys as $i=>$key) $params[$key] = urldecode($matches[$i] ?? '');
                $handler($params); return;
            }
        }
        abort(404, 'Страница не найдена.');
    }
}

final class View
{
    private static ?string $renderTemplate=null;

    private static function selectedTemplate(): string
    {
        $template=(string)setting('site_template','default');
        return in_array($template,['default','vk','x'],true)?$template:'default';
    }

    private static function templateFile(string $template,string $name): ?string
    {
        if($template==='default')return null;
        $safe=preg_replace('/[^a-zA-Z0-9_-]/','',$name);
        $file=BASE_PATH.'/views/templates/'.$template.'/'.$safe.'.php';
        return is_file($file)?$file:null;
    }

    public static function render(string $name, array $data=[]): void
    {
        extract($data, EXTR_SKIP);
        // Keep the control panel on its own stable service layout.
        $template=$name==='admin'?'default':self::selectedTemplate();
        self::$renderTemplate=$template;
        $view=self::templateFile($template,$name)??BASE_PATH.'/views/'.$name.'.php';
        ob_start(); require $view; $content = ob_get_clean();
        if ((string)($_SERVER['HTTP_X_KOVCHEG_SOFT_NAVIGATION'] ?? '') === '1') {
            header('Content-Type: application/json; charset=utf-8');
            header('Cache-Control: private, no-store, max-age=0');
            echo json_encode([
                'ok' => true,
                'html' => $content,
                'title' => (string)($title ?? cfg('app.name', 'KOVCHEG CMS')),
                'url' => current_absolute_url(),
                'version' => APP_VERSION,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            return;
        }
        $layoutTemplate=$template;
        $layout=self::templateFile($template,'layout')??BASE_PATH.'/views/layout.php';
        require $layout;
    }

    public static function partial(string $name, array $data=[]): string
    {
        extract($data, EXTR_SKIP);$template=self::$renderTemplate??self::selectedTemplate();
        $view=self::templateFile($template,$name)??BASE_PATH.'/views/'.$name.'.php';
        ob_start();require $view;return (string)ob_get_clean();
    }
}

final class Hooks
{
    private static array $listeners=[];
    public static function on(string $event, callable $callback): void { self::$listeners[$event][]=$callback; }
    public static function fire(string $event, mixed $payload=null): mixed
    {
        foreach (self::$listeners[$event] ?? [] as $listener) $payload = $listener($payload) ?? $payload;
        return $payload;
    }
}

final class Modules
{
    public static function boot(): void
    {
        try { $rows = DB::all('SELECT slug FROM modules WHERE enabled=1'); } catch (Throwable) { return; }
        foreach ($rows as $row) {
            $file = BASE_PATH.'/modules/'.basename((string)$row['slug']).'/bootstrap.php';
            if (is_file($file)) { try { require_once $file; } catch (Throwable $e) { log_error($e); } }
        }
    }

    public static function installZip(string $tmpPath, bool $autoEnable=true): array
    {
        if (!class_exists('ZipArchive')) throw new RuntimeException('На сервере не установлено расширение PHP ZipArchive.');
        $zip = new \ZipArchive();
        if ($zip->open($tmpPath) !== true) throw new RuntimeException('Не удалось открыть ZIP-пакет.');
        try {
            if($zip->numFiles<1||$zip->numFiles>5000)throw new RuntimeException('Пакет содержит недопустимое количество файлов.');
            $compressed=@filesize($tmpPath)?:0;if($compressed>200*1024*1024)throw new RuntimeException('ZIP-пакет превышает 200 МБ.');
            $expanded=0;
            $manifestRaw = $zip->getFromName('manifest.json');
            if ($manifestRaw === false) throw new RuntimeException('В пакете отсутствует обязательный manifest.json.');
            $manifest = json_decode($manifestRaw, true, 512, JSON_THROW_ON_ERROR);
            foreach (['slug','name','version','min_core','author','copyright','license'] as $required) {
                if (trim((string)($manifest[$required] ?? '')) === '') throw new RuntimeException('В manifest.json отсутствует обязательное поле: '.$required);
            }
            $slug = strtolower((string)$manifest['slug']);
            if (!preg_match('/^[a-z][a-z0-9_-]{2,50}$/', $slug)) throw new RuntimeException('Некорректный slug модуля.');
            if (version_compare(APP_VERSION, (string)$manifest['min_core'], '<')) throw new RuntimeException('Модулю требуется ядро '.$manifest['min_core'].' или новее.');
            $packageType=(string)($manifest['type']??'module');
            if($packageType==='core_update'&&version_compare((string)$manifest['version'],'2.1.1','>=')){
                $updateRaw=$zip->getFromName('update.json');if($updateRaw===false)throw new RuntimeException('В обновлении ядра отсутствует update.json.');
                $update=json_decode($updateRaw,true,512,JSON_THROW_ON_ERROR);
                foreach(['schema','target_version','min_core','min_php','changelog','rollback','migrations'] as $required)if(!array_key_exists($required,$update))throw new RuntimeException('В update.json отсутствует обязательное поле: '.$required);
                if((string)$update['target_version']!==(string)$manifest['version'])throw new RuntimeException('Версии manifest.json и update.json не совпадают.');
                if(version_compare(APP_VERSION,(string)$update['min_core'],'<'))throw new RuntimeException('Обновлению требуется ядро '.(string)$update['min_core'].' или новее.');
                if(version_compare(PHP_VERSION,(string)$update['min_php'],'<'))throw new RuntimeException('Обновлению требуется PHP '.(string)$update['min_php'].' или новее.');
                foreach((array)($update['required_extensions']??[]) as $extension)if(!extension_loaded((string)$extension))throw new RuntimeException('Для обновления требуется PHP-расширение: '.$extension);
                foreach([(string)$update['changelog'],(string)$update['rollback']] as $requiredFile)if($requiredFile===''||$zip->locateName($requiredFile)===false)throw new RuntimeException('В обновлении отсутствует обязательный файл: '.$requiredFile);
                foreach((array)$update['migrations'] as $migration)if(!is_string($migration)||!str_starts_with($migration,'migrations/')||!str_ends_with($migration,'.sql')||$zip->locateName($migration)===false)throw new RuntimeException('Некорректная SQL-миграция в update.json.');
            }
            for ($i=0; $i<$zip->numFiles; $i++) {
                $stat=$zip->statIndex($i);$expanded+=(int)($stat['size']??0);if($expanded>1024*1024*1024)throw new RuntimeException('Распакованный пакет превышает безопасный предел 1 ГБ.');
                $name = (string)$zip->getNameIndex($i);
                if ($name==='' || str_contains($name,'..') || str_starts_with($name,'/') || str_contains($name,"\\")) throw new RuntimeException('Пакет содержит небезопасный путь: '.$name);
                if (preg_match('/\.(php[34578]?|phtml|phar|cgi|pl|sh|exe)$/i',$name) && $name!=='bootstrap.php') throw new RuntimeException('Пакет содержит запрещённый исполняемый файл: '.$name);
            }
            $checksRaw=$zip->getFromName('payload-manifest.json');
            if($checksRaw!==false){
                $checks=json_decode($checksRaw,true,512,JSON_THROW_ON_ERROR);
                foreach(array_merge((array)($checks['files']??[]),(array)($checks['package_files']??[])) as $file){
                    $source=(string)($file['source']??'');$expected=(string)($file['sha256']??'');
                    if($source===''||$expected===''||str_contains($source,'..')||str_starts_with($source,'/')||str_contains($source,"\\"))throw new RuntimeException('Повреждён payload-manifest.json.');
                    $data=$zip->getFromName($source);
                    if($data===false||!hash_equals($expected,hash('sha256',$data)))throw new RuntimeException('Контрольная сумма не совпала: '.$source);
                    if(isset($file['size'])&&strlen($data)!==(int)$file['size'])throw new RuntimeException('Размер файла не совпал: '.$source);
                }
            }
            $temp=BASE_PATH.'/storage/cache/module-'.bin2hex(random_bytes(8));
            if(!mkdir($temp,0755,true)&&!is_dir($temp))throw new RuntimeException('Не удалось создать временную папку модуля.');
            if(!$zip->extractTo($temp)){rrmdir($temp);throw new RuntimeException('Не удалось распаковать модуль.');}
        } finally { $zip->close(); }
        $it=new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($temp,\FilesystemIterator::SKIP_DOTS));
        foreach($it as $item)if($item->isLink()){rrmdir($temp);throw new RuntimeException('Символические ссылки в модулях запрещены.');}
        $dest=BASE_PATH.'/modules/'.$slug;$old=null;
        if(is_dir($dest)){
            $backupPath=BASE_PATH.'/storage/backups/module-'.$slug.'-before-'.date('Ymd-His').'.zip';$backup=new \ZipArchive();
            if($backup->open($backupPath,\ZipArchive::CREATE|\ZipArchive::OVERWRITE)!==true){rrmdir($temp);throw new RuntimeException('Не удалось создать резервную копию текущего модуля.');}
            $iterator=new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dest,\FilesystemIterator::SKIP_DOTS),\RecursiveIteratorIterator::LEAVES_ONLY);
            foreach($iterator as $file)if($file->isFile())$backup->addFile($file->getPathname(),substr($file->getPathname(),strlen($dest)+1));$backup->close();
            $old=$dest.'.old-'.bin2hex(random_bytes(4));if(!rename($dest,$old)){rrmdir($temp);throw new RuntimeException('Не удалось подготовить текущий модуль к обновлению.');}
        }
        if(!rename($temp,$dest)){if($old&&is_dir($old))rename($old,$dest);rrmdir($temp);throw new RuntimeException('Не удалось активировать файлы нового модуля.');}
        try {
            DB::run('INSERT INTO modules (slug,name,version,description,enabled,installed_at,updated_at) VALUES (?,?,?,?,?,CURRENT_TIMESTAMP,CURRENT_TIMESTAMP) ON DUPLICATE KEY UPDATE name=VALUES(name),version=VALUES(version),description=VALUES(description),enabled=VALUES(enabled),updated_at=CURRENT_TIMESTAMP',[$slug,(string)$manifest['name'],(string)$manifest['version'],(string)($manifest['description']??''),$autoEnable?1:0]);
        } catch (Throwable $databaseError) {
            rrmdir($dest);
            if($old&&is_dir($old))rename($old,$dest);
            throw new RuntimeException('Файлы модуля проверены, но запись в базе не выполнена: '.$databaseError->getMessage(),0,$databaseError);
        }
        if($old)rrmdir($old);
        audit('module.install','module',null,['slug'=>$slug,'version'=>$manifest['version'],'enabled'=>$autoEnable]);
        return $manifest+['enabled'=>$autoEnable];
    }

    public static function remove(string $slug): void
    {
        $slug=basename($slug);if($slug===''||str_starts_with($slug,'.'))throw new RuntimeException('Некорректный модуль.');
        DB::run('DELETE FROM modules WHERE slug=?',[$slug]);rrmdir(BASE_PATH.'/modules/'.$slug);audit('module.remove','module',null,['slug'=>$slug]);
    }
}
