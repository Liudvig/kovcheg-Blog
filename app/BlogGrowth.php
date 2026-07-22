<?php

declare(strict_types=1);

namespace Kovcheg\Blog;

use Kovcheg\DB;
use Throwable;

final class Growth
{
    public static function publishScheduled(): int
    {
        $entries = DB::all("SELECT id,title,slug,type FROM content_entries WHERE status='scheduled' AND published_at IS NOT NULL AND published_at<=CURRENT_TIMESTAMP AND deleted_at IS NULL ORDER BY published_at,id LIMIT 200");
        $count = 0;
        foreach ($entries as $entry) {
            DB::run("UPDATE content_entries SET status='published',updated_at=CURRENT_TIMESTAMP WHERE id=? AND status='scheduled'", [(int)$entry['id']]);
            DB::run("INSERT INTO content_publication_log (entry_id,action,details_json,created_at) VALUES (?,'scheduled_publish',?,CURRENT_TIMESTAMP)", [(int)$entry['id'], json_encode(['slug'=>$entry['slug'],'type'=>$entry['type']], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)]);
            $count++;
        }
        return $count;
    }

    public static function redirectFor(string $path): ?array
    {
        try {
            $row = DB::one('SELECT id,target_path,status_code FROM content_redirects WHERE source_path=? AND is_active=1 LIMIT 1', [$path]);
            if (!$row) return null;
            DB::run('UPDATE content_redirects SET hits=hits+1,updated_at=CURRENT_TIMESTAMP WHERE id=?', [(int)$row['id']]);
            return ['target'=>(string)$row['target_path'],'code'=>in_array((int)$row['status_code'],[301,302,307,308],true)?(int)$row['status_code']:301];
        } catch (Throwable) {
            return null;
        }
    }

    public static function sitemapUrls(): array
    {
        $urls = [['loc'=>'/','updated_at'=>null],['loc'=>'/blog','updated_at'=>null],['loc'=>'/portfolio','updated_at'=>null]];
        foreach (DB::all("SELECT type,slug,updated_at,published_at FROM content_entries WHERE status='published' AND visibility='public' AND deleted_at IS NULL ORDER BY id DESC LIMIT 50000") as $row) {
            $prefix = $row['type']==='page'?'/page/':($row['type']==='portfolio'?'/portfolio/':'/blog/');
            $urls[] = ['loc'=>$prefix.$row['slug'],'updated_at'=>$row['updated_at']?:$row['published_at']];
        }
        return $urls;
    }

    public static function rssEntries(int $limit=30): array
    {
        return DB::all("SELECT e.id,e.title,e.slug,e.excerpt,e.content_html,e.published_at,e.updated_at,u.display_name author_name FROM content_entries e JOIN users u ON u.id=e.author_id WHERE e.type='post' AND e.status='published' AND e.visibility='public' AND e.deleted_at IS NULL ORDER BY COALESCE(e.published_at,e.created_at) DESC,e.id DESC LIMIT ".max(1,min(100,$limit)));
    }

    public static function subscribe(string $email, string $source='site'): void
    {
        $email = mb_strtolower(trim($email));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email)>190) abort(422,'Укажите корректный email.');
        $token = bin2hex(random_bytes(24));
        DB::run("INSERT INTO content_subscriptions (email,status,unsubscribe_token_hash,source,confirmed_at,created_at,updated_at) VALUES (?,'active',?,?,CURRENT_TIMESTAMP,CURRENT_TIMESTAMP,CURRENT_TIMESTAMP) ON DUPLICATE KEY UPDATE status='active',source=VALUES(source),updated_at=CURRENT_TIMESTAMP", [$email,hash('sha256',$token),mb_substr($source,0,100)]);
    }

    public static function saveRedirect(array $input, int $userId): int
    {
        $source = self::path((string)($input['source_path']??''));
        $target = trim((string)($input['target_path']??''));
        if ($source==='/' || $source==='') abort(422,'Исходный путь не может быть пустым или корнем сайта.');
        if (!(str_starts_with($target,'/') || filter_var($target,FILTER_VALIDATE_URL))) abort(422,'Укажите внутренний путь или полный URL назначения.');
        $code = in_array((int)($input['status_code']??301),[301,302,307,308],true)?(int)$input['status_code']:301;
        $id=(int)($input['id']??0);
        if($id>0){DB::run('UPDATE content_redirects SET source_path=?,target_path=?,status_code=?,is_active=?,updated_at=CURRENT_TIMESTAMP WHERE id=?',[$source,mb_substr($target,0,500),$code,!empty($input['is_active'])?1:0,$id]);return $id;}
        return DB::insert('INSERT INTO content_redirects (source_path,target_path,status_code,is_active,created_by,created_at,updated_at) VALUES (?,?,?,?,?,CURRENT_TIMESTAMP,CURRENT_TIMESTAMP)',[$source,mb_substr($target,0,500),$code,!empty($input['is_active'])?1:0,$userId]);
    }

    private static function path(string $value): string
    {
        $value = '/'.ltrim(trim($value),'/');
        $value = preg_replace('~/+~','/',$value) ?: '/';
        return mb_substr($value,0,255);
    }
}
