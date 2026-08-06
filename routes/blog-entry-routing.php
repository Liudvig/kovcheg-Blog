<?php

declare(strict_types=1);

use Kovcheg\DB;
use Kovcheg\Blog\Blog;
use Kovcheg\Blog\Studio;

if (!function_exists('kovcheg_public_page_context')) {
    function kovcheg_public_page_context(array $entry): array
    {
        $id=(int)($entry['id']??0);
        $categories=DB::all(
            'SELECT c.id,c.name,c.slug,c.description FROM content_categories c JOIN content_entry_categories ec ON ec.category_id=c.id WHERE ec.entry_id=? ORDER BY c.sort_order,c.name',
            [$id]
        );
        $views=(int)(DB::one('SELECT COALESCE(SUM(views),0) total FROM content_views_daily WHERE entry_id=?',[$id])['total']??0);
        $visibilitySql=Blog::readableVisibilitySql('e');

        if($categories){
            $ids=array_map(static fn(array $category):int=>(int)$category['id'],$categories);
            $placeholders=implode(',',array_fill(0,count($ids),'?'));
            $related=DB::all(
                "SELECT DISTINCT e.id,e.type,e.title,e.slug,e.excerpt,e.featured_image_path,e.published_at,e.updated_at
                 FROM content_entries e
                 JOIN content_entry_categories ec ON ec.entry_id=e.id
                 WHERE e.id<>? AND e.type='page' AND ec.category_id IN ({$placeholders})
                   AND e.status='published' AND {$visibilitySql} AND e.deleted_at IS NULL
                   AND (e.published_at IS NULL OR e.published_at<=CURRENT_TIMESTAMP)
                 ORDER BY e.is_featured DESC,e.published_at DESC,e.id DESC LIMIT 4",
                array_merge([$id],$ids)
            );
        }else{
            $related=DB::all(
                "SELECT e.id,e.type,e.title,e.slug,e.excerpt,e.featured_image_path,e.published_at,e.updated_at
                 FROM content_entries e
                 WHERE e.id<>? AND e.type='page' AND e.status='published' AND {$visibilitySql}
                   AND e.deleted_at IS NULL AND (e.published_at IS NULL OR e.published_at<=CURRENT_TIMESTAMP)
                 ORDER BY e.is_featured DESC,e.published_at DESC,e.id DESC LIMIT 4",
                [$id]
            );
        }

        return [
            'categories'=>$categories,
            'viewCount'=>$views,
            'relatedEntries'=>$related,
        ];
    }
}

if (!function_exists('kovcheg_record_public_page_view')) {
    function kovcheg_record_public_page_view(int $entryId): void
    {
        if($entryId<1)return;
        try{
            DB::run('INSERT INTO content_views_daily (entry_id,view_date,views) VALUES (?,CURRENT_DATE,1) ON DUPLICATE KEY UPDATE views=views+1',[$entryId]);
        }catch(Throwable){
        }
    }
}

if (!function_exists('kovcheg_render_page_record')) {
    function kovcheg_render_page_record(array $entry,bool $editorFinalView=false):void
    {
        if($editorFinalView||(string)($entry['visibility']??'public')!=='public'){
            header('X-Robots-Tag: noindex, nofollow, noarchive');
        }
        if(!$editorFinalView&&Blog::isPubliclyReadable($entry)){
            kovcheg_record_public_page_view((int)$entry['id']);
        }
        Blog::render('page',array_merge([
            'title'=>(string)($entry['seo_title']?:$entry['title']),
            'description'=>(string)($entry['seo_description']?:Blog::excerpt($entry,300)),
            'entry'=>$entry,
            'comments'=>$editorFinalView?[]:Blog::comments((int)$entry['id']),
            'editorFinalView'=>$editorFinalView,
        ],kovcheg_public_page_context($entry)));
    }
}

if (!function_exists('kovcheg_render_public_page')) {
    function kovcheg_render_public_page(string $slug):void
    {
        $slug=trim($slug);
        if($slug==='')abort(404,'Страница не найдена.');

        $entry=Blog::entry($slug,'page');
        if($entry){
            kovcheg_render_page_record($entry);
            return;
        }

        $stored=Blog::storedEntry($slug,'page');
        if($stored&&Studio::can('content')){
            kovcheg_render_page_record($stored,true);
            return;
        }

        abort(404,'Страница не найдена.');
    }
}

$router->get('/page/{slug}',static function(array $params):void{
    kovcheg_render_public_page((string)($params['slug']??''));
});
