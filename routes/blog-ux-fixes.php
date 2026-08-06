<?php

declare(strict_types=1);

use Kovcheg\Auth;
use Kovcheg\Csrf;
use Kovcheg\DB;
use Kovcheg\Blog\Blog;
use Kovcheg\Blog\Studio;
use Kovcheg\Blog\Studio32;

require_once BASE_PATH.'/app/BlogStudio.php';
require_once BASE_PATH.'/app/BlogStudio32.php';

if (!function_exists('kovcheg_entry_preview_context')) {
    function kovcheg_entry_preview_context(array $entry): array
    {
        $id=(int)($entry['id']??0);
        $categories=DB::all('SELECT c.id,c.name,c.slug FROM content_categories c JOIN content_entry_categories ec ON ec.category_id=c.id WHERE ec.entry_id=? ORDER BY c.sort_order,c.name',[$id]);
        $tags=DB::all('SELECT t.id,t.name,t.slug FROM content_tags t JOIN content_entry_tags et ON et.tag_id=t.id WHERE et.entry_id=? ORDER BY t.name',[$id]);
        $meta=[];
        foreach(DB::all('SELECT meta_key,meta_value FROM content_entry_meta WHERE entry_id=?',[$id]) as $item){
            $meta[(string)$item['meta_key']]=(string)($item['meta_value']??'');
        }
        $views=(int)(DB::one('SELECT COALESCE(SUM(views),0) total FROM content_views_daily WHERE entry_id=?',[$id])['total']??0);
        return [
            'categories'=>$categories,
            'tags'=>$tags,
            'portfolioMeta'=>$meta,
            'viewCount'=>$views,
            'relatedEntries'=>[],
        ];
    }
}

/*
 * Studio preview for drafts, private entries and future publications.
 * It prevents the editor's preview link from opening a public 404 page.
 */
$router->get('/studio/content/{id}/preview', function (array $params) {
    Studio::require('content');
    $entry=DB::one("SELECT e.*,u.display_name author_name,u.username author_username,u.avatar_path,u.bio author_bio
        FROM content_entries e JOIN users u ON u.id=e.author_id
        WHERE e.id=? AND e.deleted_at IS NULL LIMIT 1",[(int)$params['id']]);
    if(!$entry)abort(404,'Материал не найден.');

    header('X-Robots-Tag: noindex, nofollow, noarchive');
    Blog::render('entry',array_merge([
        'title'=>'Предпросмотр — '.(string)$entry['title'],
        'description'=>(string)($entry['seo_description']?:Blog::excerpt($entry,300)),
        'entry'=>$entry,
        'comments'=>[],
        'reactions'=>[],
        'studioPreview'=>true,
    ],kovcheg_entry_preview_context($entry)));
});

/* Upload an image without leaving the classic editor. */
$router->post('/studio/media/upload-inline', function () {
    Studio::require('media');
    Csrf::validate();

    $file=$_FILES['media']??null;
    if(!is_array($file)||empty($file['name']))abort(422,'Выберите изображение.');
    $tmp=(string)($file['tmp_name']??'');
    $mime=$tmp!==''&&is_file($tmp)?((new finfo(FILEINFO_MIME_TYPE))->file($tmp)?:''):'';
    if(!in_array($mime,['image/jpeg','image/png','image/webp'],true))abort(422,'В редактор можно загрузить JPEG, PNG или WebP.');

    $item=Studio32::storeMedia($file,Auth::id(),max(0,(int)($_POST['folder_id']??0)));
    if(!$item)abort(500,'Не удалось сохранить изображение.');

    json_response([
        'ok'=>true,
        'item'=>[
            'id'=>(int)$item['id'],
            'url'=>app_url('/media/'.(int)$item['id']),
            'title'=>(string)($item['title']??$item['original_name']??'Изображение'),
            'alt'=>(string)($item['alt_text']??''),
            'caption'=>(string)($item['caption']??''),
        ],
    ]);
});

/*
 * Registered before routes/blog.php. Existing portfolio items open normally.
 * A renamed or mistyped old portfolio URL no longer leaves the visitor on a
 * dead 404 page: a matching material is redirected to its canonical URL, an
 * unpublished item opens through Studio for an authorised editor, otherwise
 * the visitor returns to the portfolio archive.
 */
$router->get('/portfolio/{slug}', function (array $params) {
    $slug=trim((string)($params['slug']??''));
    if($slug==='')redirect('/portfolio');

    $entry=Blog::entry($slug,'portfolio');
    if(!$entry){
        $other=Blog::entry($slug);
        if($other){
            header('Location: '.Blog::entryUrl($other),true,302);
            exit;
        }

        $stored=DB::one("SELECT id FROM content_entries WHERE slug=? AND type='portfolio' AND deleted_at IS NULL LIMIT 1",[$slug]);
        if($stored&&Studio::can('content'))redirect('/studio/content/'.(int)$stored['id'].'/preview');

        header('Location: '.app_url('/portfolio'),true,302);
        exit;
    }

    try{
        DB::run('INSERT INTO content_views_daily (entry_id,view_date,views) VALUES (?,CURRENT_DATE,1) ON DUPLICATE KEY UPDATE views=views+1',[(int)$entry['id']]);
    }catch(Throwable){}

    Blog::render('entry',array_merge([
        'title'=>(string)($entry['seo_title']?:$entry['title']),
        'description'=>(string)($entry['seo_description']?:Blog::excerpt($entry,300)),
        'entry'=>$entry,
        'comments'=>Blog::comments((int)$entry['id']),
        'reactions'=>Blog::reactions((int)$entry['id']),
    ],kovcheg_entry_preview_context($entry)));
});
