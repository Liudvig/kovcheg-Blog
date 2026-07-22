<?php

declare(strict_types=1);

namespace Kovcheg\Blog;

use Kovcheg\Auth;
use Kovcheg\DB;
use Throwable;

final class SiteManager
{
    private const SECTION_TYPES = [
        'hero' => 'Первый экран',
        'latest_posts' => 'Последние записи',
        'featured_post' => 'Избранный материал',
        'portfolio' => 'Портфолио',
        'text' => 'Текстовый блок',
        'stats' => 'Показатели',
        'cta' => 'Призыв к действию',
    ];

    public static function sectionTypes(): array
    {
        return self::SECTION_TYPES;
    }

    public static function homeSections(bool $onlyEnabled = true): array
    {
        try {
            $where = $onlyEnabled ? 'WHERE is_enabled=1' : '';
            $rows = DB::all("SELECT * FROM site_home_sections {$where} ORDER BY sort_order,id");
            foreach ($rows as &$row) {
                $row['settings'] = self::decodeSettings((string)($row['settings_json'] ?? '{}'));
                $row['payload'] = self::sectionPayload((string)$row['section_type'], $row['settings']);
            }
            unset($row);
            return $rows;
        } catch (Throwable) {
            return [];
        }
    }

    public static function saveSection(array $input, int $userId, int $id = 0): int
    {
        Studio::require('site');
        $type = (string)($input['section_type'] ?? 'text');
        if (!isset(self::SECTION_TYPES[$type])) abort(422, 'Неизвестный тип секции.');

        $title = mb_substr(trim((string)($input['title'] ?? self::SECTION_TYPES[$type])), 0, 190);
        $key = Studio::slugify((string)($input['section_key'] ?? $title));
        if ($key === '') $key = 'section-'.date('Ymd-His');
        $settings = self::normalizeSettings($type, $input);
        $json = json_encode($settings, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        $sort = max(-9999, min(9999, (int)($input['sort_order'] ?? 0)));
        $enabled = !empty($input['is_enabled']) ? 1 : 0;

        if ($id > 0) {
            $current = DB::one('SELECT id,section_key FROM site_home_sections WHERE id=?', [$id]);
            if (!$current) abort(404, 'Секция главной не найдена.');
            $key = self::uniqueSectionKey($key, $id);
            DB::run('UPDATE site_home_sections SET section_key=?,section_type=?,title=?,settings_json=?,sort_order=?,is_enabled=?,updated_at=CURRENT_TIMESTAMP WHERE id=?', [$key,$type,$title?:null,$json,$sort,$enabled,$id]);
            audit('blog.home_section.update', 'site_home_section', $id, ['type'=>$type]);
            return $id;
        }

        $key = self::uniqueSectionKey($key, 0);
        $id = DB::insert('INSERT INTO site_home_sections (section_key,section_type,title,settings_json,sort_order,is_enabled,created_by,created_at,updated_at) VALUES (?,?,?,?,?,?,?,CURRENT_TIMESTAMP,CURRENT_TIMESTAMP)', [$key,$type,$title?:null,$json,$sort,$enabled,$userId]);
        audit('blog.home_section.create', 'site_home_section', $id, ['type'=>$type]);
        return $id;
    }

    public static function deleteSection(int $id): void
    {
        Studio::require('site');
        if (!DB::one('SELECT id FROM site_home_sections WHERE id=?', [$id])) abort(404, 'Секция не найдена.');
        DB::run('DELETE FROM site_home_sections WHERE id=?', [$id]);
        audit('blog.home_section.delete', 'site_home_section', $id);
    }

    public static function moveSection(int $id, string $direction): void
    {
        Studio::require('site');
        $current = DB::one('SELECT id,sort_order FROM site_home_sections WHERE id=?', [$id]);
        if (!$current) abort(404, 'Секция не найдена.');
        $operator = $direction === 'up' ? '<' : '>';
        $order = $direction === 'up' ? 'DESC' : 'ASC';
        $other = DB::one("SELECT id,sort_order FROM site_home_sections WHERE sort_order {$operator} ? OR (sort_order=? AND id {$operator} ?) ORDER BY sort_order {$order},id {$order} LIMIT 1", [(int)$current['sort_order'],(int)$current['sort_order'],$id]);
        if (!$other) return;
        DB::pdo()->beginTransaction();
        try {
            DB::run('UPDATE site_home_sections SET sort_order=? WHERE id=?', [(int)$other['sort_order'],$id]);
            DB::run('UPDATE site_home_sections SET sort_order=? WHERE id=?', [(int)$current['sort_order'],(int)$other['id']]);
            DB::pdo()->commit();
        } catch (Throwable $error) {
            if (DB::pdo()->inTransaction()) DB::pdo()->rollBack();
            throw $error;
        }
    }

    public static function meta(array $data): array
    {
        $entry = is_array($data['entry'] ?? null) ? $data['entry'] : [];
        $siteName = (string)setting('site_name', 'KOVCHEG Blog');
        $title = trim((string)($data['title'] ?? ''));
        $description = trim((string)($data['description'] ?? setting('seo_description', 'Авторский блог, проекты и портфолио.')));
        $canonical = trim((string)($entry['canonical_url'] ?? '')) ?: current_absolute_url();
        $noindex = !$entry ? false : !empty($entry['seo_noindex']);
        $indexing = setting('search_indexing', '0') === '1' && !$noindex;
        $ogType = trim((string)($entry['og_type'] ?? '')) ?: (string)setting('seo_default_og_type', 'website');
        $imagePath = trim((string)($entry['seo_image_path'] ?? $entry['featured_image_path'] ?? setting('seo_default_image', '')));
        $image = self::mediaUrl($imagePath);
        $suffix = trim((string)setting('seo_title_suffix', $siteName));
        $fullTitle = $title !== '' ? $title.($suffix !== '' && $suffix !== $title ? ' — '.$suffix : '') : $siteName;

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => $entry ? (($entry['type'] ?? '') === 'portfolio' ? 'CreativeWork' : 'Article') : 'WebSite',
            'name' => $title !== '' ? $title : $siteName,
            'url' => $canonical,
            'description' => $description,
        ];
        if ($image !== '') $schema['image'] = $image;
        if ($entry) {
            $schema['datePublished'] = date(DATE_ATOM, strtotime((string)($entry['published_at'] ?: $entry['created_at'])));
            $schema['dateModified'] = date(DATE_ATOM, strtotime((string)($entry['updated_at'] ?: $entry['created_at'])));
            $schema['author'] = ['@type'=>'Person','name'=>(string)($entry['author_name'] ?? $siteName)];
        }

        return [
            'title'=>$fullTitle,'rawTitle'=>$title,'description'=>$description,'canonical'=>$canonical,
            'robots'=>$indexing?'index,follow,max-image-preview:large':'noindex,nofollow,noarchive',
            'ogType'=>$ogType,'image'=>$image,
            'jsonLd'=>json_encode($schema, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR),
        ];
    }

    public static function sitemapXml(): string
    {
        $urls = [['loc'=>app_url('/'),'updated'=>date('c'),'priority'=>'1.0']];
        foreach (DB::all("SELECT type,slug,updated_at,published_at FROM content_entries WHERE status='published' AND visibility='public' AND deleted_at IS NULL AND seo_noindex=0 AND (published_at IS NULL OR published_at<=CURRENT_TIMESTAMP) ORDER BY id DESC") as $entry) {
            $urls[] = ['loc'=>self::absolute(Blog::entryUrl($entry)),'updated'=>date('c',strtotime((string)($entry['updated_at'] ?: $entry['published_at']))),'priority'=>$entry['type']==='page'?'0.8':'0.7'];
        }
        foreach (['/blog','/portfolio'] as $path) $urls[]=['loc'=>self::absolute(app_url($path)),'updated'=>date('c'),'priority'=>'0.8'];
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        foreach ($urls as $url) $xml .= '<url><loc>'.self::xml((string)$url['loc']).'</loc><lastmod>'.self::xml((string)$url['updated']).'</lastmod><priority>'.self::xml((string)$url['priority']).'</priority></url>';
        return $xml.'</urlset>';
    }

    public static function feedXml(): string
    {
        $site = (string)setting('site_name','KOVCHEG Blog');
        $items = Blog::entries('post', 30);
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<rss version="2.0"><channel><title>'.self::xml($site).'</title><link>'.self::xml(self::absolute(app_url('/'))).'</link><description>'.self::xml((string)setting('blog_description','Авторский блог и проекты.')).'</description><language>ru</language>';
        foreach ($items as $item) {
            $url = self::absolute(Blog::entryUrl($item));
            $xml .= '<item><title>'.self::xml((string)$item['title']).'</title><link>'.self::xml($url).'</link><guid isPermaLink="true">'.self::xml($url).'</guid><pubDate>'.date(DATE_RSS,strtotime((string)($item['published_at'] ?: $item['created_at']))).'</pubDate><description>'.self::xml(Blog::excerpt($item,500)).'</description></item>';
        }
        return $xml.'</channel></rss>';
    }

    public static function robotsText(): string
    {
        if (setting('search_indexing','0') !== '1') return "User-agent: *\nDisallow: /\n";
        $extra = trim((string)setting('seo_robots_extra',''));
        $text = "User-agent: *\nAllow: /\nDisallow: /studio\nDisallow: /admin\nDisallow: /api\nSitemap: ".self::absolute(app_url('/sitemap.xml'))."\n";
        return $text.($extra !== '' ? "\n".$extra."\n" : '');
    }

    public static function redirectFor(string $path): ?array
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') return null;
        try {
            $row = DB::one('SELECT id,target_url,status_code FROM seo_redirects WHERE source_path=? AND is_enabled=1 LIMIT 1', [$path]);
            if (!$row) return null;
            DB::run('UPDATE seo_redirects SET hits=hits+1 WHERE id=?', [(int)$row['id']]);
            return ['url'=>(string)$row['target_url'],'status'=>in_array((int)$row['status_code'],[301,302],true)?(int)$row['status_code']:301];
        } catch (Throwable) { return null; }
    }

    public static function toggleSubscription(int $entryId, int $userId): bool
    {
        if (!DB::one("SELECT id FROM content_entries WHERE id=? AND status='published' AND deleted_at IS NULL", [$entryId])) abort(404,'Материал не найден.');
        $exists = DB::one('SELECT entry_id FROM content_subscriptions WHERE entry_id=? AND user_id=?', [$entryId,$userId]);
        if ($exists) { DB::run('DELETE FROM content_subscriptions WHERE entry_id=? AND user_id=?', [$entryId,$userId]); return false; }
        DB::run('INSERT INTO content_subscriptions (entry_id,user_id,created_at) VALUES (?,?,CURRENT_TIMESTAMP)', [$entryId,$userId]);
        return true;
    }

    public static function isSubscribed(int $entryId, int $userId): bool
    {
        return (bool)DB::one('SELECT entry_id FROM content_subscriptions WHERE entry_id=? AND user_id=?', [$entryId,$userId]);
    }

    public static function notifyDiscussion(int $entryId, int $actorId, int $commentId, int $parentId, string $body, string $url): void
    {
        if (setting('comments_notifications','1') !== '1') return;
        $recipients = [];
        foreach (DB::all('SELECT user_id FROM content_subscriptions WHERE entry_id=? AND user_id<>?', [$entryId,$actorId]) as $row) $recipients[(int)$row['user_id']] = true;
        if ($parentId > 0) {
            $parent = DB::one('SELECT user_id FROM content_comments WHERE id=?', [$parentId]);
            if ($parent && (int)$parent['user_id'] !== $actorId) $recipients[(int)$parent['user_id']] = true;
        }
        foreach (array_keys($recipients) as $userId) {
            DB::insert('INSERT INTO blog_notifications (user_id,actor_id,type,title,body,url,created_at) VALUES (?,?,?,?,?,?,CURRENT_TIMESTAMP)', [$userId,$actorId,'comment','Новый комментарий в обсуждении',mb_substr(trim($body),0,500),$url.'#comment-'.$commentId]);
        }
    }

    public static function notifications(int $userId, int $limit = 100): array
    {
        $limit=max(1,min(200,$limit));
        return DB::all("SELECT n.*,u.display_name actor_name,u.username actor_username,u.avatar_path FROM blog_notifications n LEFT JOIN users u ON u.id=n.actor_id WHERE n.user_id=? ORDER BY n.id DESC LIMIT {$limit}", [$userId]);
    }

    public static function unreadCount(int $userId): int
    {
        if ($userId < 1) return 0;
        try { return (int)(DB::one('SELECT COUNT(*) total FROM blog_notifications WHERE user_id=? AND read_at IS NULL',[$userId])['total']??0); }
        catch (Throwable) { return 0; }
    }

    public static function markNotificationsRead(int $userId): void
    {
        DB::run('UPDATE blog_notifications SET read_at=CURRENT_TIMESTAMP WHERE user_id=? AND read_at IS NULL', [$userId]);
    }

    public static function commentReactions(int $commentId): array
    {
        $map=[];
        foreach(DB::all('SELECT reaction,COUNT(*) total FROM content_comment_reactions WHERE comment_id=? GROUP BY reaction ORDER BY total DESC',[$commentId]) as $row)$map[(string)$row['reaction']]=(int)$row['total'];
        return $map;
    }

    public static function reactComment(int $commentId, int $userId, string $reaction): void
    {
        $allowed=['👍','❤️','👏','🔥','💡'];
        if(!in_array($reaction,$allowed,true))abort(422,'Неизвестная реакция.');
        if(!DB::one("SELECT id FROM content_comments WHERE id=? AND status='approved' AND deleted_at IS NULL",[$commentId]))abort(404,'Комментарий не найден.');
        $existing=DB::one('SELECT reaction FROM content_comment_reactions WHERE comment_id=? AND user_id=?',[$commentId,$userId]);
        DB::run('DELETE FROM content_comment_reactions WHERE comment_id=? AND user_id=?',[$commentId,$userId]);
        if(!$existing||!hash_equals((string)$existing['reaction'],$reaction))DB::run('INSERT INTO content_comment_reactions (comment_id,user_id,reaction,created_at) VALUES (?,?,?,CURRENT_TIMESTAMP)',[$commentId,$userId,$reaction]);
    }

    public static function canEditComment(array $comment, int $userId): bool
    {
        if (Blog::canModerate()) return true;
        if ((int)($comment['user_id']??0) !== $userId) return false;
        $minutes=max(1,min(1440,(int)setting('comments_edit_minutes','30')));
        return strtotime((string)$comment['created_at']) >= time()-($minutes*60);
    }

    public static function editComment(int $commentId, int $userId, string $body): array
    {
        $comment=DB::one('SELECT * FROM content_comments WHERE id=? AND deleted_at IS NULL',[$commentId]);
        if(!$comment)abort(404,'Комментарий не найден.');
        if(!self::canEditComment($comment,$userId))abort(403,'Время редактирования комментария истекло.');
        $body=trim($body);if(mb_strlen($body)<2||mb_strlen($body)>5000)abort(422,'Комментарий должен содержать от 2 до 5000 символов.');
        DB::run('UPDATE content_comments SET body=?,updated_at=CURRENT_TIMESTAMP,edited_at=CURRENT_TIMESTAMP WHERE id=?',[$body,$commentId]);
        audit('blog.comment.edit','content_comment',$commentId);
        return $comment;
    }

    public static function deleteComment(int $commentId, int $userId): array
    {
        $comment=DB::one('SELECT * FROM content_comments WHERE id=? AND deleted_at IS NULL',[$commentId]);
        if(!$comment)abort(404,'Комментарий не найден.');
        if(!self::canEditComment($comment,$userId))abort(403,'Недостаточно прав для удаления комментария.');
        DB::run('UPDATE content_comments SET deleted_at=CURRENT_TIMESTAMP,body=? WHERE id=?',['Комментарий удалён.',$commentId]);
        audit('blog.comment.delete','content_comment',$commentId);
        return $comment;
    }

    public static function mediaUrl(string $storedPath): string
    {
        if ($storedPath === '') return '';
        try {
            $row=DB::one('SELECT id FROM media_library WHERE stored_path=? LIMIT 1',[$storedPath]);
            return $row?self::absolute(app_url('/media/'.(int)$row['id'])):'';
        } catch(Throwable){return '';}
    }

    private static function sectionPayload(string $type, array $settings): array
    {
        return match($type){
            'latest_posts'=>['entries'=>Blog::entries('post',max(1,min(24,(int)($settings['limit']??8))))],
            'portfolio'=>['entries'=>Blog::entries('portfolio',max(1,min(24,(int)($settings['limit']??6))))],
            'featured_post'=>['entry'=>!empty($settings['entry_id'])?DB::one("SELECT e.*,u.display_name author_name,u.username author_username,u.avatar_path FROM content_entries e JOIN users u ON u.id=e.author_id WHERE e.id=? AND e.status='published' AND e.deleted_at IS NULL",[(int)$settings['entry_id']]):null],
            default=>[],
        };
    }

    private static function normalizeSettings(string $type, array $input): array
    {
        $text=static fn(string $key,int $max=5000):string=>mb_substr(trim((string)($input[$key]??'')),0,$max);
        $url=static fn(string $key):string=>self::safeUrl((string)($input[$key]??''));
        return match($type){
            'hero'=>['eyebrow'=>$text('eyebrow',150),'title'=>$text('hero_title',500),'text'=>$text('text',3000),'primary_text'=>$text('primary_text',150),'primary_url'=>$url('primary_url'),'secondary_text'=>$text('secondary_text',150),'secondary_url'=>$url('secondary_url'),'image_url'=>$url('image_url')],
            'latest_posts','portfolio'=>['limit'=>max(1,min(24,(int)($input['limit']??8))),'eyebrow'=>$text('eyebrow',150),'button_text'=>$text('button_text',150),'button_url'=>$url('button_url')],
            'featured_post'=>['entry_id'=>max(0,(int)($input['entry_id']??0)),'eyebrow'=>$text('eyebrow',150),'button_text'=>$text('button_text',150)],
            'text'=>['eyebrow'=>$text('eyebrow',150),'heading'=>$text('heading',500),'text'=>$text('text',10000),'align'=>in_array((string)($input['align']??''),['left','center'],true)?(string)$input['align']:'left'],
            'stats'=>['eyebrow'=>$text('eyebrow',150),'items'=>self::parseLines($text('items',5000),12,true)],
            'cta'=>['eyebrow'=>$text('eyebrow',150),'heading'=>$text('heading',500),'text'=>$text('text',3000),'button_text'=>$text('button_text',150),'button_url'=>$url('button_url'),'tone'=>in_array((string)($input['tone']??''),['light','dark','accent'],true)?(string)$input['tone']:'dark'],
            default=>[],
        };
    }

    private static function parseLines(string $value,int $limit,bool $pairs=false):array
    {
        $result=[];foreach(array_slice(preg_split('/\R/u',$value)?:[],0,$limit) as $line){$line=trim($line);if($line==='')continue;if($pairs){[$a,$b]=array_pad(array_map('trim',explode('|',$line,2)),2,'');$result[]=['value'=>mb_substr($a,0,80),'label'=>mb_substr($b,0,200)];}else$result[]=mb_substr($line,0,500);}return $result;
    }

    private static function decodeSettings(string $json):array
    {try{$data=json_decode($json,true,512,JSON_THROW_ON_ERROR);return is_array($data)?$data:[];}catch(Throwable){return [];}}

    private static function uniqueSectionKey(string $key,int $ignoreId):string
    {$base=mb_substr($key,0,90);$candidate=$base;$n=2;while(DB::one('SELECT id FROM site_home_sections WHERE section_key=? AND id<>?',[$candidate,$ignoreId]))$candidate=mb_substr($base,0,84).'-'.$n++;return $candidate;}

    private static function safeUrl(string $url):string
    {$url=trim($url);if($url==='')return '';if(str_starts_with($url,'/'))return $url;return filter_var($url,FILTER_VALIDATE_URL)&&preg_match('~^https?://~i',$url)?$url:'';}

    private static function absolute(string $url):string
    {if(preg_match('~^https?://~i',$url))return $url;$scheme=(!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off')?'https':'http';$host=(string)($_SERVER['HTTP_HOST']??'localhost');return $scheme.'://'.$host.'/'.ltrim($url,'/');}

    private static function xml(string $value):string
    {return htmlspecialchars($value,ENT_XML1|ENT_QUOTES,'UTF-8');}
}
