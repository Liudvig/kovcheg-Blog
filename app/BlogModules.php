<?php

declare(strict_types=1);

namespace Kovcheg\Blog;

use Kovcheg\DB;
use RuntimeException;
use Throwable;

final class ModuleManager
{
    private const MAX_COMPRESSED = 100_000_000;
    private const MAX_EXPANDED = 500_000_000;
    private const MAX_FILES = 3000;

    public static function installed(): array
    {
        $rows = DB::all('SELECT * FROM modules ORDER BY name,slug');
        foreach ($rows as &$row) {
            $manifestFile = BASE_PATH.'/modules/'.basename((string)$row['slug']).'/manifest.json';
            $manifest = [];
            if (is_file($manifestFile)) {
                try { $manifest=json_decode((string)file_get_contents($manifestFile),true,512,JSON_THROW_ON_ERROR); }
                catch(Throwable) { $manifest=[]; }
            }
            $row['manifest']=$manifest;
            $row['files_ok']=is_file(BASE_PATH.'/modules/'.basename((string)$row['slug']).'/bootstrap.php');
            $row['health']=$row['files_ok']?'ready':'broken';
        }
        unset($row);
        return $rows;
    }

    public static function install(array $upload, bool $enable=true): array
    {
        Studio::require('site');
        if (!class_exists('ZipArchive')) throw new RuntimeException('Для установки модулей требуется PHP ZipArchive.');
        $error=(int)($upload['error']??UPLOAD_ERR_NO_FILE);
        if($error!==UPLOAD_ERR_OK)throw new RuntimeException(self::uploadError($error));
        $tmp=(string)($upload['tmp_name']??'');
        if($tmp===''||!is_file($tmp))throw new RuntimeException('Временный ZIP-файл не найден.');
        $compressed=(int)filesize($tmp);
        if($compressed<1||$compressed>self::MAX_COMPRESSED)throw new RuntimeException('Пакет модуля должен быть не больше 100 МБ.');

        $zip=new \ZipArchive();
        if($zip->open($tmp)!==true)throw new RuntimeException('Не удалось открыть ZIP-пакет.');
        $temp=BASE_PATH.'/storage/cache/module35-'.bin2hex(random_bytes(8));
        try{
            if($zip->numFiles<2||$zip->numFiles>self::MAX_FILES)throw new RuntimeException('Недопустимое количество файлов в пакете.');
            $manifestRaw=$zip->getFromName('manifest.json');
            if($manifestRaw===false)throw new RuntimeException('В корне пакета отсутствует manifest.json.');
            $manifest=json_decode($manifestRaw,true,512,JSON_THROW_ON_ERROR);
            self::validateManifest($manifest);
            $expanded=0;
            for($i=0;$i<$zip->numFiles;$i++){
                $stat=$zip->statIndex($i);$expanded+=(int)($stat['size']??0);
                if($expanded>self::MAX_EXPANDED)throw new RuntimeException('Распакованный модуль превышает 500 МБ.');
                self::validatePath((string)$zip->getNameIndex($i));
            }
            if(!mkdir($temp,0755,true)&&!is_dir($temp))throw new RuntimeException('Не удалось создать временный каталог модуля.');
            if(!$zip->extractTo($temp))throw new RuntimeException('Не удалось распаковать модуль.');
        }finally{$zip->close();}

        try{
            $iterator=new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($temp,\FilesystemIterator::SKIP_DOTS));
            foreach($iterator as $item)if($item->isLink())throw new RuntimeException('Символические ссылки в модуле запрещены.');
            if(!is_file($temp.'/bootstrap.php'))throw new RuntimeException('В модуле отсутствует bootstrap.php.');
            $manifest=json_decode((string)file_get_contents($temp.'/manifest.json'),true,512,JSON_THROW_ON_ERROR);
            self::validateManifest($manifest);
            self::validatePhpFiles($temp);
            self::runPackageMigrations($temp,(string)$manifest['slug']);
            $slug=(string)$manifest['slug'];$dest=BASE_PATH.'/modules/'.$slug;$old=null;
            if(is_dir($dest)){$old=$dest.'.old-'.bin2hex(random_bytes(5));if(!rename($dest,$old))throw new RuntimeException('Не удалось подготовить старую версию модуля.');}
            if(!rename($temp,$dest)){if($old&&is_dir($old))rename($old,$dest);throw new RuntimeException('Не удалось активировать модуль.');}
            try{
                DB::run('INSERT INTO modules (slug,name,version,description,enabled,manifest_json,package_format,health_status,installed_at,updated_at) VALUES (?,?,?,?,?,?,2,\'ready\',CURRENT_TIMESTAMP,CURRENT_TIMESTAMP) ON DUPLICATE KEY UPDATE name=VALUES(name),version=VALUES(version),description=VALUES(description),enabled=VALUES(enabled),manifest_json=VALUES(manifest_json),package_format=2,health_status=\'ready\',updated_at=CURRENT_TIMESTAMP',[$slug,(string)$manifest['name'],(string)$manifest['version'],(string)($manifest['description']??''),$enable?1:0,json_encode($manifest,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)]);
            }catch(Throwable $e){rrmdir($dest);if($old&&is_dir($old))rename($old,$dest);throw $e;}
            if($old&&is_dir($old))rrmdir($old);
            audit('blog.module.install','module',null,['slug'=>$slug,'version'=>$manifest['version'],'format'=>2]);
            return $manifest+['enabled'=>$enable];
        }finally{if(is_dir($temp))rrmdir($temp);}
    }

    public static function setEnabled(string $slug,bool $enabled):void
    {
        Studio::require('site');$slug=self::slug($slug);
        if(!DB::one('SELECT slug FROM modules WHERE slug=?',[$slug]))abort(404,'Модуль не найден.');
        if($enabled&&!is_file(BASE_PATH.'/modules/'.$slug.'/bootstrap.php'))abort(409,'Файлы модуля повреждены.');
        DB::run('UPDATE modules SET enabled=?,health_status=?,updated_at=CURRENT_TIMESTAMP WHERE slug=?',[$enabled?1:0,$enabled?'ready':'disabled',$slug]);
        audit('blog.module.'.($enabled?'enable':'disable'),'module',null,['slug'=>$slug]);
    }

    public static function remove(string $slug):void
    {
        Studio::require('site');$slug=self::slug($slug);
        DB::run('DELETE FROM modules WHERE slug=?',[$slug]);
        DB::run('DELETE FROM module_migrations WHERE module_slug=?',[$slug]);
        rrmdir(BASE_PATH.'/modules/'.$slug);
        audit('blog.module.remove','module',null,['slug'=>$slug]);
    }

    private static function validateManifest(array $manifest):void
    {
        foreach(['slug','name','version','min_core','author','license'] as $field)if(trim((string)($manifest[$field]??''))==='')throw new RuntimeException('manifest.json: отсутствует поле '.$field.'.');
        $slug=strtolower((string)$manifest['slug']);
        if(!preg_match('/^[a-z][a-z0-9_-]{2,50}$/',$slug))throw new RuntimeException('manifest.json: некорректный slug.');
        if(version_compare(APP_VERSION,(string)$manifest['min_core'],'<'))throw new RuntimeException('Модулю требуется KOVCHEG Blog '.(string)$manifest['min_core'].' или новее.');
        if(isset($manifest['min_php'])&&version_compare(PHP_VERSION,(string)$manifest['min_php'],'<'))throw new RuntimeException('Модулю требуется PHP '.(string)$manifest['min_php'].' или новее.');
        foreach((array)($manifest['extensions']??[]) as $extension)if(!extension_loaded((string)$extension))throw new RuntimeException('Модулю требуется PHP-расширение '.(string)$extension.'.');
        if(isset($manifest['migrations'])&&!is_array($manifest['migrations']))throw new RuntimeException('manifest.json: migrations должен быть массивом.');
    }

    private static function validatePath(string $name):void
    {
        if($name===''||str_starts_with($name,'/')||str_contains($name,'..')||str_contains($name,"\\")||str_contains($name,"\0"))throw new RuntimeException('Небезопасный путь в ZIP: '.$name);
        if(str_starts_with($name,'.git/')||str_starts_with($name,'.github/')||str_contains($name,'/.'))throw new RuntimeException('Скрытые служебные файлы в модуле запрещены.');
        $extension=strtolower(pathinfo($name,PATHINFO_EXTENSION));
        $allowed=['','php','json','sql','css','js','svg','png','jpg','jpeg','webp','gif','woff','woff2','txt','md','html','xml','csv'];
        if(!in_array($extension,$allowed,true))throw new RuntimeException('Недопустимый тип файла в модуле: '.$name);
        if(in_array($extension,['phar','phtml','cgi','pl','sh','exe','dll','so'],true))throw new RuntimeException('Исполняемый файл запрещён: '.$name);
    }

    private static function validatePhpFiles(string $root):void
    {
        $iterator=new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root,\FilesystemIterator::SKIP_DOTS));
        foreach($iterator as $file){
            if(!$file->isFile()||strtolower($file->getExtension())!=='php')continue;
            $relative=str_replace('\\','/',substr($file->getPathname(),strlen($root)+1));
            if($relative!=='bootstrap.php'&&!preg_match('~^(src|routes|views)/[a-zA-Z0-9_./-]+\.php$~',$relative))throw new RuntimeException('PHP-файл находится вне разрешённого каталога: '.$relative);
            $source=(string)file_get_contents($file->getPathname());
            if(preg_match('/\b(eval|shell_exec|passthru|proc_open|popen)\s*\(/i',$source))throw new RuntimeException('В модуле обнаружена запрещённая функция: '.$relative);
        }
    }

    private static function runPackageMigrations(string $root,string $slug):void
    {
        $manifest=json_decode((string)file_get_contents($root.'/manifest.json'),true,512,JSON_THROW_ON_ERROR);
        foreach((array)($manifest['migrations']??[]) as $migration){
            $migration=(string)$migration;
            if(!preg_match('~^migrations/[a-zA-Z0-9_.-]+\.sql$~',$migration)||!is_file($root.'/'.$migration))throw new RuntimeException('Некорректная миграция модуля: '.$migration);
            if(DB::one('SELECT id FROM module_migrations WHERE module_slug=? AND migration=?',[$slug,$migration]))continue;
            $sql=(string)file_get_contents($root.'/'.$migration);
            foreach(preg_split('/;\s*(?:\r?\n|$)/',$sql)?:[] as $statement){$statement=trim($statement);if($statement!=='')DB::pdo()->exec($statement);}
            DB::run('INSERT INTO module_migrations (module_slug,migration,applied_at) VALUES (?,?,CURRENT_TIMESTAMP)',[$slug,$migration]);
        }
    }

    private static function uploadError(int $error):string
    {
        return match($error){
            UPLOAD_ERR_INI_SIZE,UPLOAD_ERR_FORM_SIZE=>'ZIP-пакет превышает разрешённый сервером размер.',
            UPLOAD_ERR_PARTIAL=>'ZIP-пакет загрузился не полностью.',
            UPLOAD_ERR_NO_FILE=>'ZIP-пакет не выбран.',
            UPLOAD_ERR_NO_TMP_DIR=>'На сервере отсутствует временный каталог загрузки.',
            UPLOAD_ERR_CANT_WRITE=>'Сервер не смог записать временный ZIP-файл.',
            UPLOAD_ERR_EXTENSION=>'Загрузка остановлена PHP-расширением.',
            default=>'Неизвестная ошибка загрузки ZIP-пакета.',
        };
    }

    private static function slug(string $slug):string
    {
        $slug=strtolower(basename(trim($slug)));if(!preg_match('/^[a-z][a-z0-9_-]{2,50}$/',$slug))abort(422,'Некорректный модуль.');return $slug;
    }
}