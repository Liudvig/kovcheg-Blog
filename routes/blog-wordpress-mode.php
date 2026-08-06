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

if (!function_exists('kovcheg_page_editor')) {
    function kovcheg_page_editor(?array $entry = null): void
    {
        Studio::require('content');
        $isNew = !$entry;
        if ($isNew) {
            $entry = [
                'id'=>0,'type'=>'page','status'=>'draft','title'=>'','slug'=>'','excerpt'=>'',
                'content_json'=>'[]','content_html'=>'','featured_image_path'=>'','template'=>'',
                'visibility'=>'public','comments_enabled'=>0,'reactions_enabled'=>0,
                'is_featured'=>0,'sort_order'=>0,'seo_title'=>'','seo_description'=>'',
                'published_at'=>'','category_ids'=>[],'tags_text'=>'','meta'=>[],
            ];
        }
        if ((string)($entry['type'] ?? '') !== 'page') abort(404, 'Страница не найдена.');

        $id = (int)($entry['id'] ?? 0);
        $autosave = $id > 0 ? DB::one('SELECT * FROM content_autosaves WHERE entry_id=? AND user_id=? ORDER BY saved_at DESC LIMIT 1', [$id, Auth::id()]) : null;
        $revisions = $id > 0 ? DB::all('SELECT r.id,r.title,r.created_at,u.display_name author_name FROM content_revisions r JOIN users u ON u.id=r.author_id WHERE r.entry_id=? ORDER BY r.id DESC LIMIT 30', [$id]) : [];

        Studio::render('wp-editor', [
            'studioSection'=>'pages',
            'studioTitle'=>$isNew ? 'Новая страница' : 'Редактирование страницы',
            'entry'=>$entry,
            'categories'=>DB::all('SELECT * FROM content_categories ORDER BY sort_order,name'),
            'media'=>DB::all("SELECT * FROM media_library WHERE mime_type LIKE 'image/%' ORDER BY id DESC LIMIT 120"),
            'autosave'=>$autosave,
            'revisions'=>$revisions,
        ]);
    }
}

$router->get('/studio', function () {
    Studio::require('comments');
    $stats = DB::one("SELECT
        (SELECT COUNT(*) FROM content_entries WHERE type='page' AND deleted_at IS NULL) pages,
        (SELECT COUNT(*) FROM content_categories) categories,
        (SELECT COUNT(*) FROM content_comments WHERE status='pending' AND deleted_at IS NULL) pending_comments,
        (SELECT COALESCE(SUM(views),0) FROM content_views_daily WHERE view_date>=DATE_SUB(CURRENT_DATE,INTERVAL 30 DAY)) views_30") ?? [];
    $recent = DB::all("SELECT e.id,e.status,e.title,e.slug,e.updated_at,u.display_name author_name
        FROM content_entries e JOIN users u ON u.id=e.author_id
        WHERE e.type='page' AND e.deleted_at IS NULL
        ORDER BY e.updated_at DESC,e.id DESC LIMIT 10");
    $comments = DB::all("SELECT c.id,c.body,c.status,c.created_at,e.title entry_title,u.display_name author_name
        FROM content_comments c JOIN content_entries e ON e.id=c.entry_id JOIN users u ON u.id=c.user_id
        WHERE c.deleted_at IS NULL AND e.type='page' ORDER BY c.id DESC LIMIT 8");
    Studio::render('dashboard', ['studioSection'=>'dashboard','studioTitle'=>'Обзор','stats'=>$stats,'recentEntries'=>$recent,'recentComments'=>$comments]);
});

$router->get('/studio/pages', function () {
    Studio::require('content');
    $status = (string)($_GET['status'] ?? '');
    $search = mb_substr(trim((string)($_GET['q'] ?? '')), 0, 150);
    Studio::render('entries-index', [
        'studioSection'=>'pages',
        'studioTitle'=>'Страницы',
        'entryType'=>'page',
        'entries'=>Studio::listEntries('page',$status,$search),
        'status'=>$status,
        'search'=>$search,
    ]);
});

$router->get('/studio/pages/new', function () {
    kovcheg_page_editor();
});

$router->get('/studio/pages/{id}/edit', function (array $params) {
    $entry = Studio::entry((int)$params['id']);
    if (!$entry || !empty($entry['deleted_at']) || (string)($entry['type'] ?? '') !== 'page') abort(404, 'Страница не найдена.');
    kovcheg_page_editor($entry);
});

$router->post('/studio/entry/save', function () {
    Studio::require('content');
    Csrf::validate();
    $input = $_POST;
    $input['type'] = 'page';
    $input['tags'] = '';
    if (!empty($_FILES['featured_image']['name'])) {
        $media = Studio32::storeMedia($_FILES['featured_image'], Auth::id(), 0);
        $input['featured_image_path'] = (string)($media['stored_path'] ?? '');
    }
    $id = Studio32::saveEntry($input, Auth::id(), (int)($_POST['id'] ?? 0));
    $_SESSION['flash_success'] = 'Страница сохранена.';
    redirect('/studio/pages/'.$id.'/edit');
});

$router->post('/studio/entries/{id}/trash', function (array $params) {
    Studio::require('content'); Csrf::validate();
    $entry = Studio::entry((int)$params['id']);
    if (!$entry || (string)($entry['type'] ?? '') !== 'page') abort(404, 'Страница не найдена.');
    DB::run('UPDATE content_entries SET deleted_at=CURRENT_TIMESTAMP,updated_at=CURRENT_TIMESTAMP WHERE id=?', [(int)$entry['id']]);
    audit('cms.page.trash','content_entry',(int)$entry['id']);
    $_SESSION['flash_success']='Страница перемещена в корзину.';
    redirect('/studio/pages');
});

$router->post('/studio/entries/{id}/duplicate', function (array $params) {
    Studio::require('content'); Csrf::validate();
    $source = Studio::entry((int)$params['id']);
    if (!$source || (string)($source['type'] ?? '') !== 'page') abort(404, 'Страница не найдена.');
    $copy = $source;
    $copy['title'] = 'Копия — '.$source['title'];
    $copy['slug'] = '';
    $copy['status'] = 'draft';
    $copy['published_at'] = '';
    $copy['tags'] = '';
    $id = Studio32::saveEntry($copy, Auth::id());
    $_SESSION['flash_success'] = 'Создана копия страницы.';
    redirect('/studio/pages/'.$id.'/edit');
});

$router->get('/studio/menus', function () {
    Studio::require('menus');
    $menus=DB::all('SELECT * FROM navigation_menus ORDER BY id');
    $menuId=max(0,(int)($_GET['menu']??($menus[0]['id']??0)));
    $items=$menuId?DB::all('SELECT * FROM navigation_items WHERE menu_id=? ORDER BY sort_order,id',[$menuId]):[];
    Studio::render('menus',[
        'studioSection'=>'menus','studioTitle'=>'Меню','menus'=>$menus,'menuId'=>$menuId,'items'=>$items,
        'pages'=>DB::all("SELECT id,type,title,slug FROM content_entries WHERE type='page' AND status='published' AND visibility='public' AND deleted_at IS NULL ORDER BY title"),
        'categories'=>DB::all('SELECT id,name,slug FROM content_categories ORDER BY sort_order,name'),
    ]);
});

$router->post('/studio/menus/item', function () {
    Studio::require('menus'); Csrf::validate();
    $menuId=(int)($_POST['menu_id']??0);
    if(!DB::one('SELECT id FROM navigation_menus WHERE id=?',[$menuId]))abort(404,'Меню не найдено.');
    $kind=in_array((string)($_POST['target_kind']??''),['page','category','custom'],true)?(string)$_POST['target_kind']:'custom';
    $targetId=max(0,(int)($_POST['target_id']??0));
    $label=mb_substr(trim((string)($_POST['label']??'')),0,150);
    $url=mb_substr(trim((string)($_POST['url']??'')),0,500);
    $targetType='custom';$storedTarget=null;
    if($kind==='page'&&$targetId>0){
        $page=DB::one("SELECT id,type,title,slug FROM content_entries WHERE id=? AND type='page' AND status='published' AND deleted_at IS NULL",[$targetId]);
        if(!$page)abort(404,'Страница не найдена.');
        if($label==='')$label=(string)$page['title'];$url=Blog::entryUrl($page);$targetType='content';$storedTarget=$targetId;
    }elseif($kind==='category'&&$targetId>0){
        $category=DB::one('SELECT id,name,slug FROM content_categories WHERE id=?',[$targetId]);
        if(!$category)abort(404,'Рубрика не найдена.');
        if($label==='')$label=(string)$category['name'];$url=app_url('/category/'.rawurlencode((string)$category['slug']));$targetType='category';$storedTarget=$targetId;
    }
    if($label==='')abort(422,'Введите подпись пункта.');
    if($url==='')$url='/';
    $sort=max(-999,min(999,(int)($_POST['sort_order']??0)));
    DB::insert('INSERT INTO navigation_items (menu_id,label,url,target_type,target_id,sort_order,is_enabled,created_at,updated_at) VALUES (?,?,?,?,?,?,1,CURRENT_TIMESTAMP,CURRENT_TIMESTAMP)',[$menuId,$label,$url,$targetType,$storedTarget,$sort]);
    $_SESSION['flash_success']='Пункт меню добавлен.';redirect('/studio/menus?menu='.$menuId);
});

$router->get('/', function () {
    $categories=DB::all("SELECT c.id,c.name,c.slug,c.description,
        (SELECT COUNT(*) FROM content_entry_categories ec JOIN content_entries e ON e.id=ec.entry_id
         WHERE ec.category_id=c.id AND e.type='page' AND e.status='published' AND e.visibility='public'
           AND e.deleted_at IS NULL AND (e.published_at IS NULL OR e.published_at<=CURRENT_TIMESTAMP)) page_count
        FROM content_categories c ORDER BY c.sort_order,c.name");
    Blog::render('home', [
        'title'=>(string)setting('blog_home_title',setting('site_name','KOVCHEG CMS')),
        'pages'=>Blog::entries('page',max(6,min(36,(int)setting('blog_posts_per_page','18')))),
        'categories'=>$categories,
    ]);
});

$router->get('/search', function () {
    $q=mb_substr(trim((string)($_GET['q']??'')),0,120);$entries=[];
    if(mb_strlen($q)>=2){$like='%'.$q.'%';$entries=DB::all("SELECT e.*,u.display_name author_name,u.username author_username,u.avatar_path,
        (SELECT COUNT(*) FROM content_comments c WHERE c.entry_id=e.id AND c.status='approved' AND c.deleted_at IS NULL) comment_count,
        (SELECT COUNT(*) FROM content_reactions r WHERE r.entry_id=e.id) reaction_count
        FROM content_entries e JOIN users u ON u.id=e.author_id
        WHERE e.type='page' AND e.status='published' AND e.visibility='public' AND e.deleted_at IS NULL
        AND (e.title LIKE ? OR e.excerpt LIKE ? OR e.content_html LIKE ?)
        ORDER BY e.is_featured DESC,e.published_at DESC,e.id DESC LIMIT 100",[$like,$like,$like]);}
    Blog::render('archive',['title'=>'Поиск','archiveTitle'=>$q!==''?'Поиск: '.$q:'Поиск','archiveDescription'=>$q!==''?'Найдено: '.count($entries):'Введите не меньше двух символов.','entries'=>$entries,'entryType'=>'search','searchQuery'=>$q]);
});

$router->get('/category/{slug}', function(array $params){
    $term=DB::one('SELECT id,name,slug,description FROM content_categories WHERE slug=? LIMIT 1',[(string)$params['slug']]);
    if(!$term)abort(404,'Рубрика не найдена.');
    $entries=DB::all("SELECT e.*,u.display_name author_name,u.username author_username,u.avatar_path,
        (SELECT COUNT(*) FROM content_comments c WHERE c.entry_id=e.id AND c.status='approved' AND c.deleted_at IS NULL) comment_count,
        (SELECT COUNT(*) FROM content_reactions r WHERE r.entry_id=e.id) reaction_count
        FROM content_entries e JOIN users u ON u.id=e.author_id JOIN content_entry_categories ec ON ec.entry_id=e.id
        WHERE ec.category_id=? AND e.type='page' AND e.status='published' AND e.visibility='public' AND e.deleted_at IS NULL
        AND (e.published_at IS NULL OR e.published_at<=CURRENT_TIMESTAMP)
        ORDER BY e.is_featured DESC,e.published_at DESC,e.id DESC LIMIT 100",[(int)$term['id']]);
    Blog::render('archive',['title'=>(string)$term['name'],'archiveTitle'=>(string)$term['name'],'archiveDescription'=>(string)($term['description']??'Страницы рубрики.'),'entries'=>$entries,'entryType'=>'category','category'=>$term]);
});
