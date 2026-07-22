<?php

declare(strict_types=1);

namespace Kovcheg\Blog;

use Kovcheg\Auth;
use Kovcheg\DB;
use RuntimeException;
use Throwable;

final class Studio
{
    private const CONTENT_ROLES = ['owner', 'admin', 'editor'];
    private const MODERATION_ROLES = ['owner', 'admin', 'editor', 'moderator'];
    private const SITE_ROLES = ['owner', 'admin'];

    public static function role(): string
    {
        return (string)(Auth::user()['role'] ?? 'guest');
    }

    public static function can(string $capability): bool
    {
        if (!Auth::check()) return false;

        return match ($capability) {
            'content', 'media' => in_array(self::role(), self::CONTENT_ROLES, true),
            'comments' => in_array(self::role(), self::MODERATION_ROLES, true),
            'site', 'themes', 'menus', 'settings' => in_array(self::role(), self::SITE_ROLES, true),
            default => false,
        };
    }

    public static function require(string $capability = 'content'): void
    {
        Auth::requireLogin();
        if (!self::can($capability)) abort(403, 'Недостаточно прав для этого раздела KOVCHEG Studio.');
    }

    public static function render(string $view, array $data = []): void
    {
        $safe = preg_replace('/[^a-zA-Z0-9_-]/', '', $view);
        $file = BASE_PATH.'/views/studio/'.$safe.'.php';
        $layout = BASE_PATH.'/views/studio/layout.php';
        if (!is_file($file) || !is_file($layout)) throw new RuntimeException('Шаблон KOVCHEG Studio не найден.');

        extract($data, EXTR_SKIP);
        $studioSection = (string)($studioSection ?? $safe);
        $studioTitle = (string)($studioTitle ?? 'KOVCHEG Studio');
        $studioRole = self::role();
        $currentUser = Auth::user() ?? [];

        ob_start();
        require $file;
        $content = (string)ob_get_clean();
        require $layout;
    }

    public static function slugify(string $value): string
    {
        $value = mb_strtolower(trim($value));
        $map = [
            'а'=>'a','б'=>'b','в'=>'v','г'=>'g','д'=>'d','е'=>'e','ё'=>'e','ж'=>'zh','з'=>'z','и'=>'i','й'=>'y',
            'к'=>'k','л'=>'l','м'=>'m','н'=>'n','о'=>'o','п'=>'p','р'=>'r','с'=>'s','т'=>'t','у'=>'u','ф'=>'f',
            'х'=>'h','ц'=>'c','ч'=>'ch','ш'=>'sh','щ'=>'sch','ъ'=>'','ы'=>'y','ь'=>'','э'=>'e','ю'=>'yu','я'=>'ya',
        ];
        $value = strtr($value, $map);
        $value = preg_replace('/[^a-z0-9]+/u', '-', $value) ?? '';
        $value = trim($value, '-');
        return substr($value, 0, 190);
    }

    public static function uniqueSlug(string $value, int $ignoreId = 0): string
    {
        $base = self::slugify($value);
        if ($base === '') $base = 'material-'.date('Ymd-His');
        $slug = $base;
        $number = 2;
        while (DB::one('SELECT id FROM content_entries WHERE slug=? AND id<>? LIMIT 1', [$slug, $ignoreId])) {
            $suffix = '-'.$number++;
            $slug = substr($base, 0, 190 - strlen($suffix)).$suffix;
        }
        return $slug;
    }

    public static function entry(int $id): ?array
    {
        $entry = DB::one('SELECT * FROM content_entries WHERE id=? LIMIT 1', [$id]);
        if (!$entry) return null;
        $entry['category_ids'] = array_map('intval', array_column(DB::all('SELECT category_id FROM content_entry_categories WHERE entry_id=?', [$id]), 'category_id'));
        $entry['tags_text'] = implode(', ', array_column(DB::all('SELECT t.name FROM content_tags t JOIN content_entry_tags et ON et.tag_id=t.id WHERE et.entry_id=? ORDER BY t.name', [$id]), 'name'));
        $meta = DB::all('SELECT meta_key,meta_value FROM content_entry_meta WHERE entry_id=?', [$id]);
        $entry['meta'] = [];
        foreach ($meta as $item) $entry['meta'][(string)$item['meta_key']] = (string)($item['meta_value'] ?? '');
        return $entry;
    }

    public static function listEntries(string $type = '', string $status = '', string $search = ''): array
    {
        $where = ['e.deleted_at IS NULL'];
        $params = [];
        if (in_array($type, ['post', 'page', 'portfolio'], true)) { $where[] = 'e.type=?'; $params[] = $type; }
        if (in_array($status, ['draft', 'published', 'scheduled', 'private'], true)) { $where[] = 'e.status=?'; $params[] = $status; }
        if ($search !== '') { $where[] = '(e.title LIKE ? OR e.slug LIKE ? OR e.excerpt LIKE ?)'; $q = '%'.$search.'%'; array_push($params, $q, $q, $q); }

        return DB::all(
            'SELECT e.*,u.display_name author_name,(SELECT COUNT(*) FROM content_comments c WHERE c.entry_id=e.id AND c.deleted_at IS NULL) comment_count FROM content_entries e JOIN users u ON u.id=e.author_id WHERE '.implode(' AND ', $where).' ORDER BY e.updated_at DESC,e.id DESC LIMIT 250',
            $params
        );
    }

    public static function saveEntry(array $input, int $authorId, ?int $entryId = null): int
    {
        self::require('content');
        $entryId = max(0, (int)$entryId);
        $current = $entryId ? self::entry($entryId) : null;
        if ($entryId && !$current) abort(404, 'Материал не найден.');

        $type = in_array((string)($input['type'] ?? ''), ['post', 'page', 'portfolio'], true) ? (string)$input['type'] : 'post';
        $status = in_array((string)($input['status'] ?? ''), ['draft', 'published', 'scheduled', 'private'], true) ? (string)$input['status'] : 'draft';
        $visibility = in_array((string)($input['visibility'] ?? ''), ['public', 'users', 'private'], true) ? (string)$input['visibility'] : 'public';
        $title = trim((string)($input['title'] ?? ''));
        if (mb_strlen($title) < 2 || mb_strlen($title) > 255) abort(422, 'Заголовок должен содержать от 2 до 255 символов.');

        $requestedSlug = trim((string)($input['slug'] ?? ''));
        $slug = self::uniqueSlug($requestedSlug !== '' ? $requestedSlug : $title, $entryId);
        $excerpt = mb_substr(trim((string)($input['excerpt'] ?? '')), 0, 2000);
        $contentJson = self::normalizeBlocks((string)($input['content_json'] ?? '[]'));
        $contentHtml = self::renderBlocks($contentJson);
        $featured = self::safeStoredPath((string)($input['featured_image_path'] ?? ''));
        $template = preg_match('/^[a-z0-9_-]{0,80}$/', (string)($input['template'] ?? '')) ? (string)($input['template'] ?? '') : '';
        $seoTitle = mb_substr(trim((string)($input['seo_title'] ?? '')), 0, 255);
        $seoDescription = mb_substr(trim((string)($input['seo_description'] ?? '')), 0, 320);
        $publishedAt = self::normalizeDateTime((string)($input['published_at'] ?? ''));
        if ($status === 'published' && $publishedAt === null) $publishedAt = date('Y-m-d H:i:s');
        if ($status === 'scheduled' && ($publishedAt === null || strtotime($publishedAt) <= time())) abort(422, 'Для запланированной публикации укажите будущую дату.');

        $flags = static fn(string $key): int => !empty($input[$key]) ? 1 : 0;
        $commentsEnabled = $flags('comments_enabled');
        $reactionsEnabled = $flags('reactions_enabled');
        $featuredFlag = $flags('is_featured');
        $sortOrder = max(-9999, min(9999, (int)($input['sort_order'] ?? 0)));

        DB::pdo()->beginTransaction();
        try {
            if ($current) {
                DB::run(
                    'INSERT INTO content_revisions (entry_id,author_id,title,excerpt,content_json,content_html,created_at) VALUES (?,?,?,?,?,?,CURRENT_TIMESTAMP)',
                    [$entryId, $authorId, (string)$current['title'], $current['excerpt'], $current['content_json'], $current['content_html']]
                );
                DB::run(
                    'UPDATE content_entries SET author_id=?,type=?,status=?,title=?,slug=?,excerpt=?,content_json=?,content_html=?,featured_image_path=?,template=?,visibility=?,comments_enabled=?,reactions_enabled=?,is_featured=?,sort_order=?,seo_title=?,seo_description=?,published_at=?,updated_at=CURRENT_TIMESTAMP WHERE id=?',
                    [$authorId,$type,$status,$title,$slug,$excerpt?:null,$contentJson,$contentHtml,$featured?:null,$template?:null,$visibility,$commentsEnabled,$reactionsEnabled,$featuredFlag,$sortOrder,$seoTitle?:null,$seoDescription?:null,$publishedAt,$entryId]
                );
                $id = $entryId;
            } else {
                $id = DB::insert(
                    'INSERT INTO content_entries (author_id,type,status,title,slug,excerpt,content_json,content_html,featured_image_path,template,visibility,comments_enabled,reactions_enabled,is_featured,sort_order,seo_title,seo_description,published_at,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,CURRENT_TIMESTAMP,CURRENT_TIMESTAMP)',
                    [$authorId,$type,$status,$title,$slug,$excerpt?:null,$contentJson,$contentHtml,$featured?:null,$template?:null,$visibility,$commentsEnabled,$reactionsEnabled,$featuredFlag,$sortOrder,$seoTitle?:null,$seoDescription?:null,$publishedAt]
                );
            }

            self::syncCategories($id, (array)($input['category_ids'] ?? []));
            self::syncTags($id, (string)($input['tags'] ?? ''));
            self::syncMeta($id, [
                'client' => trim((string)($input['portfolio_client'] ?? '')),
                'year' => trim((string)($input['portfolio_year'] ?? '')),
                'role' => trim((string)($input['portfolio_role'] ?? '')),
                'project_url' => self::safeUrl((string)($input['portfolio_url'] ?? '')),
            ]);

            DB::pdo()->commit();
        } catch (Throwable $e) {
            if (DB::pdo()->inTransaction()) DB::pdo()->rollBack();
            throw $e;
        }

        audit($current ? 'blog.entry.update' : 'blog.entry.create', 'content_entry', $id, ['type'=>$type,'status'=>$status]);
        return $id;
    }

    public static function normalizeBlocks(string $json): string
    {
        if (strlen($json) > 2 * 1024 * 1024) abort(413, 'Материал слишком большой.');
        try { $blocks = json_decode($json, true, 512, JSON_THROW_ON_ERROR); }
        catch (Throwable) { abort(422, 'Редактор передал повреждённые данные.'); }
        if (!is_array($blocks)) $blocks = [];
        $blocks = array_slice(array_values(array_filter($blocks, 'is_array')), 0, 250);
        return json_encode($blocks, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }

    public static function renderBlocks(string $json): string
    {
        try { $blocks = json_decode($json, true, 512, JSON_THROW_ON_ERROR); }
        catch (Throwable) { return ''; }
        if (!is_array($blocks)) return '';
        $html = [];
        foreach (array_slice($blocks, 0, 250) as $block) {
            if (!is_array($block)) continue;
            $type = (string)($block['type'] ?? 'paragraph');
            $data = is_array($block['data'] ?? null) ? $block['data'] : [];
            $text = trim((string)($data['text'] ?? ''));
            if ($type === 'paragraph' && $text !== '') $html[] = '<p>'.nl2br(self::h($text)).'</p>';
            elseif ($type === 'heading' && $text !== '') { $level = max(2, min(4, (int)($data['level'] ?? 2))); $html[] = '<h'.$level.'>'.self::h($text).'</h'.$level.'>'; }
            elseif ($type === 'quote' && $text !== '') $html[] = '<blockquote><p>'.nl2br(self::h($text)).'</p>'.(!empty($data['caption'])?'<cite>'.self::h((string)$data['caption']).'</cite>':'').'</blockquote>';
            elseif ($type === 'code' && $text !== '') $html[] = '<pre><code>'.self::h($text).'</code></pre>';
            elseif ($type === 'separator') $html[] = '<hr>';
            elseif ($type === 'image') {
                $url = self::safeUrl((string)($data['url'] ?? ''));
                if ($url !== '') $html[] = '<figure><img src="'.self::h($url).'" alt="'.self::h((string)($data['alt'] ?? '')).'" loading="lazy">'.(!empty($data['caption'])?'<figcaption>'.self::h((string)$data['caption']).'</figcaption>':'').'</figure>';
            } elseif ($type === 'button') {
                $url = self::safeUrl((string)($data['url'] ?? ''));
                if ($url !== '' && $text !== '') $html[] = '<p class="content-button"><a href="'.self::h($url).'">'.self::h($text).'</a></p>';
            } elseif ($type === 'list') {
                $items = array_slice(array_filter(array_map('trim', (array)($data['items'] ?? []))), 0, 100);
                if ($items) { $tag = !empty($data['ordered']) ? 'ol' : 'ul'; $html[] = '<'.$tag.'><li>'.implode('</li><li>', array_map([self::class, 'h'], $items)).'</li></'.$tag.'>'; }
            } elseif ($type === 'columns') {
                $columns = array_slice((array)($data['columns'] ?? []), 0, 3);
                if ($columns) { $parts=[]; foreach($columns as $column)$parts[]='<div>'.nl2br(self::h((string)$column)).'</div>'; $html[]='<div class="content-columns">'.implode('', $parts).'</div>'; }
            } elseif ($type === 'video') {
                $url = self::safeUrl((string)($data['url'] ?? ''));
                if ($url !== '') $html[] = '<div class="content-video"><a href="'.self::h($url).'" rel="noopener noreferrer">Открыть видео</a></div>';
            }
        }
        return implode("\n", $html);
    }

    public static function storeMedia(array $file, int $uploaderId): array
    {
        self::require('media');
        $error = (int)($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($error !== UPLOAD_ERR_OK) abort(422, 'Не удалось принять файл медиатеки.');
        $tmp = (string)($file['tmp_name'] ?? '');
        if ($tmp === '' || !is_file($tmp)) abort(422, 'Временный файл не найден.');
        $size = (int)filesize($tmp);
        if ($size < 1 || $size > 40 * 1024 * 1024) abort(413, 'Файл должен быть не больше 40 МБ.');

        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($tmp) ?: 'application/octet-stream';
        $name = mb_substr(basename((string)($file['name'] ?? 'file')), 0, 255);
        $base = 'media/'.$uploaderId.'/'.date('Y/m').'/'.bin2hex(random_bytes(12));
        $width = null; $height = null;

        if (in_array($mime, ['image/jpeg','image/png','image/webp'], true)) {
            $result = \optimize_uploaded_image($tmp, $base, 2560, 2560, 88);
            $stored = (string)$result['relative'];
            $mime = (string)$result['mime'];
            $size = (int)$result['size'];
            $info = @getimagesize(BASE_PATH.'/storage/uploads/'.$stored);
            if (is_array($info)) { $width = (int)$info[0]; $height = (int)$info[1]; }
        } else {
            $extensions = ['application/pdf'=>'pdf','audio/mpeg'=>'mp3','audio/ogg'=>'ogg','video/mp4'=>'mp4','video/webm'=>'webm','application/zip'=>'zip'];
            if (!isset($extensions[$mime])) abort(422, 'Разрешены изображения, PDF, MP3, OGG, MP4, WebM и ZIP.');
            $stored = $base.'.'.$extensions[$mime];
            $destination = BASE_PATH.'/storage/uploads/'.$stored;
            $dir = dirname($destination);
            if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) abort(500, 'Не удалось создать каталог медиатеки.');
            if (!move_uploaded_file($tmp, $destination) && !copy($tmp, $destination)) abort(500, 'Не удалось сохранить файл.');
        }

        $id = DB::insert('INSERT INTO media_library (uploader_id,title,original_name,stored_path,mime_type,file_size,width,height,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,CURRENT_TIMESTAMP,CURRENT_TIMESTAMP)', [$uploaderId,pathinfo($name, PATHINFO_FILENAME),$name,$stored,$mime,$size,$width,$height]);
        audit('blog.media.upload', 'media', $id, ['mime'=>$mime,'size'=>$size]);
        return DB::one('SELECT * FROM media_library WHERE id=?', [$id]) ?? [];
    }

    public static function themes(): array
    {
        $active = Blog::theme();
        $result = [];
        foreach (glob(BASE_PATH.'/themes/*/theme.json') ?: [] as $manifestFile) {
            try { $manifest = json_decode((string)file_get_contents($manifestFile), true, 512, JSON_THROW_ON_ERROR); }
            catch (Throwable) { continue; }
            $slug = basename(dirname($manifestFile));
            if (!preg_match('/^[a-z][a-z0-9-]{2,79}$/', $slug)) continue;
            $result[] = [
                'slug'=>$slug,
                'name'=>(string)($manifest['name'] ?? $slug),
                'version'=>(string)($manifest['version'] ?? '1.0.0'),
                'description'=>(string)($manifest['description'] ?? ''),
                'author'=>(string)($manifest['author'] ?? ''),
                'preview'=>(string)($manifest['preview'] ?? ''),
                'active'=>$slug === $active,
            ];
        }
        return $result;
    }

    public static function setSetting(string $key, string $value): void
    {
        if (!preg_match('/^[a-z0-9_.-]{2,100}$/', $key)) throw new RuntimeException('Некорректный ключ настройки.');
        DB::run('INSERT INTO settings (`key`,`value`,updated_at) VALUES (?,?,CURRENT_TIMESTAMP) ON DUPLICATE KEY UPDATE `value`=VALUES(`value`),updated_at=CURRENT_TIMESTAMP', [$key,$value]);
    }

    private static function syncCategories(int $entryId, array $categoryIds): void
    {
        DB::run('DELETE FROM content_entry_categories WHERE entry_id=?', [$entryId]);
        foreach (array_unique(array_map('intval', $categoryIds)) as $categoryId) {
            if ($categoryId > 0 && DB::one('SELECT id FROM content_categories WHERE id=?', [$categoryId])) DB::run('INSERT IGNORE INTO content_entry_categories (entry_id,category_id) VALUES (?,?)', [$entryId,$categoryId]);
        }
    }

    private static function syncTags(int $entryId, string $tags): void
    {
        DB::run('DELETE FROM content_entry_tags WHERE entry_id=?', [$entryId]);
        $names = array_slice(array_unique(array_filter(array_map('trim', preg_split('/[,;]+/u', $tags) ?: []))), 0, 30);
        foreach ($names as $name) {
            $name = mb_substr($name, 0, 120);
            $slug = self::slugify($name);
            if ($slug === '') continue;
            DB::run('INSERT INTO content_tags (name,slug,created_at,updated_at) VALUES (?,?,CURRENT_TIMESTAMP,CURRENT_TIMESTAMP) ON DUPLICATE KEY UPDATE name=VALUES(name),updated_at=CURRENT_TIMESTAMP', [$name,$slug]);
            $tag = DB::one('SELECT id FROM content_tags WHERE slug=?', [$slug]);
            if ($tag) DB::run('INSERT IGNORE INTO content_entry_tags (entry_id,tag_id) VALUES (?,?)', [$entryId,(int)$tag['id']]);
        }
    }

    private static function syncMeta(int $entryId, array $meta): void
    {
        foreach ($meta as $key=>$value) {
            DB::run('DELETE FROM content_entry_meta WHERE entry_id=? AND meta_key=?', [$entryId,$key]);
            if ($value !== '') DB::run('INSERT INTO content_entry_meta (entry_id,meta_key,meta_value,updated_at) VALUES (?,?,?,CURRENT_TIMESTAMP)', [$entryId,$key,mb_substr($value,0,2000)]);
        }
    }

    private static function normalizeDateTime(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') return null;
        $timestamp = strtotime($value);
        if ($timestamp === false) abort(422, 'Дата публикации указана неверно.');
        return date('Y-m-d H:i:s', $timestamp);
    }

    private static function safeStoredPath(string $path): string
    {
        $path = trim(str_replace('\\', '/', $path));
        if ($path === '' || str_contains($path, '..') || str_starts_with($path, '/')) return '';
        return preg_match('~^[a-zA-Z0-9_./-]{1,255}$~', $path) ? $path : '';
    }

    private static function safeUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '') return '';
        if (str_starts_with($url, '/')) return $url;
        return filter_var($url, FILTER_VALIDATE_URL) && preg_match('~^https?://~i', $url) ? $url : '';
    }

    private static function h(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
