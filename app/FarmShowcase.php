<?php

declare(strict_types=1);

namespace Kovcheg\Blog;

use Kovcheg\DB;

final class FarmShowcase
{
    private const TYPES = [
        'product'=>[
            'section'=>'catalog',
            'plural'=>'Товары',
            'singular'=>'Товар',
            'public_base'=>'/catalog',
            'slider_title'=>'Рекомендуем сегодня',
            'slider_kicker'=>'Продукты хозяйства',
        ],
        'livestock'=>[
            'section'=>'livestock',
            'plural'=>'Поголовье',
            'singular'=>'Животное',
            'public_base'=>'/livestock',
            'slider_title'=>'Поголовье на продажу',
            'slider_kicker'=>'Животные хозяйства',
        ],
        'project'=>[
            'section'=>'projects',
            'plural'=>'Проекты',
            'singular'=>'Проект',
            'public_base'=>'/projects',
            'slider_title'=>'Строительство и проекты',
            'slider_kicker'=>'Работа и развитие',
        ],
    ];

    private const META_KEYS = [
        'price','unit','stock_status','category','action_label','action_url',
        'species','breed','sex','age','quantity','project_kind','location',
    ];

    public static function types(): array
    {
        return self::TYPES;
    }

    public static function config(string $type): array
    {
        if (!isset(self::TYPES[$type])) abort(404, 'Раздел не найден.');
        return self::TYPES[$type];
    }

    public static function studioItems(string $type): array
    {
        self::config($type);
        $rows = DB::all(
            "SELECT e.*,u.display_name author_name
             FROM content_entries e
             JOIN users u ON u.id=e.author_id
             WHERE e.type=? AND e.deleted_at IS NULL
             ORDER BY e.is_featured DESC,e.updated_at DESC,e.id DESC",
            [$type]
        );
        return array_map([self::class,'hydrate'], $rows);
    }

    public static function publicItems(string $type, bool $featuredOnly = false, int $limit = 50): array
    {
        self::config($type);
        $limit = max(1, min(100, $limit));
        $featuredSql = $featuredOnly ? ' AND e.is_featured=1' : '';
        $rows = DB::all(
            "SELECT e.*,u.display_name author_name
             FROM content_entries e
             JOIN users u ON u.id=e.author_id
             WHERE e.type=? AND e.status='published' AND e.visibility='public'
               AND e.deleted_at IS NULL
               AND (e.published_at IS NULL OR e.published_at<=CURRENT_TIMESTAMP)
               {$featuredSql}
             ORDER BY e.is_featured DESC,e.sort_order ASC,e.published_at DESC,e.id DESC
             LIMIT {$limit}",
            [$type]
        );
        return array_map([self::class,'hydrate'], $rows);
    }

    public static function stored(int $id, string $type): ?array
    {
        self::config($type);
        $row = DB::one(
            "SELECT e.*,u.display_name author_name
             FROM content_entries e JOIN users u ON u.id=e.author_id
             WHERE e.id=? AND e.type=? AND e.deleted_at IS NULL LIMIT 1",
            [$id,$type]
        );
        return $row ? self::hydrate($row) : null;
    }

    public static function publicBySlug(string $type, string $slug): ?array
    {
        self::config($type);
        $row = DB::one(
            "SELECT e.*,u.display_name author_name
             FROM content_entries e JOIN users u ON u.id=e.author_id
             WHERE e.type=? AND e.slug=? AND e.status='published' AND e.visibility='public'
               AND e.deleted_at IS NULL
               AND (e.published_at IS NULL OR e.published_at<=CURRENT_TIMESTAMP)
             LIMIT 1",
            [$type,trim($slug)]
        );
        return $row ? self::hydrate($row) : null;
    }

    public static function save(string $type, array $input, int $authorId, int $id = 0): int
    {
        self::config($type);
        Studio::require('content');
        $id = max(0, $id);
        $current = $id > 0 ? self::stored($id, $type) : null;
        if ($id > 0 && !$current) abort(404, 'Материал не найден.');

        $title = mb_substr(trim((string)($input['title'] ?? '')), 0, 255);
        if (mb_strlen($title) < 2) abort(422, 'Введите название.');
        $slug = Studio::uniqueSlug(trim((string)($input['slug'] ?? '')) ?: $title, $id);
        $status = (string)($input['status'] ?? '') === 'published' ? 'published' : 'draft';
        $excerpt = mb_substr(trim((string)($input['excerpt'] ?? '')), 0, 2000);
        $description = trim((string)($input['description'] ?? ''));
        $contentHtml = self::plainTextHtml($description);
        $featured = self::safeStoredPath((string)($input['featured_image_path'] ?? ''));
        $promote = !empty($input['is_featured']) ? 1 : 0;
        $publishedAt = $status === 'published' ? date('Y-m-d H:i:s') : null;

        if ($current) {
            DB::run(
                "UPDATE content_entries
                 SET author_id=?,status=?,title=?,slug=?,excerpt=?,content_json='[]',content_html=?,featured_image_path=?,
                     template=NULL,visibility='public',comments_enabled=0,reactions_enabled=0,is_featured=?,sort_order=?,
                     seo_title=NULL,seo_description=?,published_at=?,updated_at=CURRENT_TIMESTAMP
                 WHERE id=?",
                [$authorId,$status,$title,$slug,$excerpt ?: null,$contentHtml,$featured ?: null,$promote,
                    (int)($input['sort_order'] ?? 0),$excerpt ?: null,$publishedAt,$id]
            );
        } else {
            $id = DB::insert(
                "INSERT INTO content_entries
                 (author_id,type,status,title,slug,excerpt,content_json,content_html,featured_image_path,template,visibility,
                  comments_enabled,reactions_enabled,is_featured,sort_order,seo_title,seo_description,published_at,created_at,updated_at)
                 VALUES (?,?,?,?,?,?,'[]',?,?,NULL,'public',0,0,?,?,NULL,?,?,CURRENT_TIMESTAMP,CURRENT_TIMESTAMP)",
                [$authorId,$type,$status,$title,$slug,$excerpt ?: null,$contentHtml,$featured ?: null,$promote,
                    (int)($input['sort_order'] ?? 0),$excerpt ?: null,$publishedAt]
            );
        }

        self::syncMeta($id, $input);
        audit($current ? 'cms.showcase.update' : 'cms.showcase.create', 'content_entry', $id, ['type'=>$type]);
        return $id;
    }

    public static function trash(int $id, string $type): void
    {
        self::config($type);
        if (!self::stored($id, $type)) abort(404, 'Материал не найден.');
        DB::run('UPDATE content_entries SET deleted_at=CURRENT_TIMESTAMP,updated_at=CURRENT_TIMESTAMP WHERE id=?', [$id]);
        audit('cms.showcase.trash', 'content_entry', $id, ['type'=>$type]);
    }

    public static function publicUrl(array $entry): string
    {
        $type = (string)($entry['type'] ?? 'product');
        $config = self::config($type);
        return app_url($config['public_base'].'/'.rawurlencode((string)($entry['slug'] ?? '')));
    }

    public static function archiveUrl(string $type): string
    {
        return app_url(self::config($type)['public_base']);
    }

    public static function imageUrl(array $entry): string
    {
        $path = trim((string)($entry['featured_image_path'] ?? ''));
        if ($path === '') return '';
        $media = DB::one('SELECT id FROM media_library WHERE stored_path=? LIMIT 1', [$path]);
        return $media ? app_url('/media/'.(int)$media['id']) : '';
    }

    public static function homeShowcaseHtml(): string
    {
        $sections = [];
        foreach (array_keys(self::TYPES) as $type) {
            $items = self::publicItems($type, true, 10);
            if (!$items) continue;
            $sections[] = self::sliderHtml($type, $items);
        }
        return implode("\n", $sections);
    }

    public static function label(string $type, string $key): string
    {
        return (string)(self::config($type)[$key] ?? '');
    }

    private static function hydrate(array $entry): array
    {
        $entry['_meta'] = self::meta((int)$entry['id']);
        $entry['_image_url'] = self::imageUrl($entry);
        $entry['_public_url'] = self::publicUrl($entry);
        return $entry;
    }

    private static function meta(int $entryId): array
    {
        $rows = DB::all('SELECT meta_key,meta_value FROM content_entry_meta WHERE entry_id=?', [$entryId]);
        $result = [];
        foreach ($rows as $row) $result[(string)$row['meta_key']] = (string)($row['meta_value'] ?? '');
        return $result;
    }

    private static function syncMeta(int $entryId, array $input): void
    {
        foreach (self::META_KEYS as $key) {
            DB::run('DELETE FROM content_entry_meta WHERE entry_id=? AND meta_key=?', [$entryId,$key]);
            $value = mb_substr(trim((string)($input[$key] ?? '')), 0, 2000);
            if ($value === '') continue;
            DB::run(
                'INSERT INTO content_entry_meta (entry_id,meta_key,meta_value,updated_at) VALUES (?,?,?,CURRENT_TIMESTAMP)',
                [$entryId,$key,$value]
            );
        }
    }

    private static function sliderHtml(string $type, array $items): string
    {
        $config = self::config($type);
        $cards = [];
        foreach ($items as $item) {
            $meta = (array)($item['_meta'] ?? []);
            $image = (string)($item['_image_url'] ?? '');
            $price = trim((string)($meta['price'] ?? ''));
            $unit = trim((string)($meta['unit'] ?? ''));
            $badge = match ($type) {
                'product'=>(string)($meta['category'] ?? 'Продукт'),
                'livestock'=>(string)($meta['species'] ?? 'Поголовье'),
                'project'=>(string)($meta['project_kind'] ?? 'Проект'),
                default=>'',
            };
            $priceHtml = $price !== ''
                ? '<strong class="farm-slide-card__price">'.e($price).($unit !== '' ? ' <small>/ '.e($unit).'</small>' : '').'</strong>'
                : '';
            $cards[] = '<article class="farm-slide-card">'
                .'<a href="'.e((string)$item['_public_url']).'" class="farm-slide-card__media">'
                .($image !== '' ? '<img src="'.e($image).'" alt="'.e((string)$item['title']).'" loading="lazy">' : '<span class="farm-slide-card__placeholder">'.e(mb_substr((string)$item['title'],0,1)).'</span>')
                .'</a><div class="farm-slide-card__body"><span class="farm-slide-card__badge">'.e($badge).'</span>'
                .'<h3><a href="'.e((string)$item['_public_url']).'">'.e((string)$item['title']).'</a></h3>'
                .'<p>'.e(mb_substr((string)($item['excerpt'] ?? ''),0,145)).'</p>'.$priceHtml
                .'<a class="farm-slide-card__action" href="'.e((string)$item['_public_url']).'">Подробнее →</a></div></article>';
        }

        $id = 'farm-slider-'.$type;
        return '<section class="farm-showcase farm-showcase--'.e($type).'">'
            .'<header class="farm-showcase__head"><div><span>'.e((string)$config['slider_kicker']).'</span><h2>'.e((string)$config['slider_title']).'</h2></div>'
            .'<div class="farm-showcase__actions"><a href="'.e(self::archiveUrl($type)).'">Смотреть всё</a>'
            .'<button type="button" data-farm-slider-prev="'.e($id).'" aria-label="Назад">←</button>'
            .'<button type="button" data-farm-slider-next="'.e($id).'" aria-label="Вперёд">→</button></div></header>'
            .'<div class="farm-slider" id="'.e($id).'" data-farm-slider>'.implode('', $cards).'</div></section>';
    }

    private static function plainTextHtml(string $text): string
    {
        if ($text === '') return '';
        $parts = preg_split('/\R{2,}/u', $text) ?: [];
        $html = [];
        foreach ($parts as $part) {
            $part = trim($part);
            if ($part === '') continue;
            $html[] = '<p>'.nl2br(htmlspecialchars($part, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')).'</p>';
        }
        return implode("\n", $html);
    }

    private static function safeStoredPath(string $path): string
    {
        $path = trim(str_replace('\\','/',$path));
        if ($path === '' || str_contains($path,'..') || str_starts_with($path,'/')) return '';
        return preg_match('~^[a-zA-Z0-9_./-]{1,255}$~',$path) ? $path : '';
    }
}
