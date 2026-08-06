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

if (!function_exists('kovcheg_redirect_home_permanent')) {
    function kovcheg_redirect_home_permanent(): never
    {
        header('Location: '.app_url('/'), true, 301);
        exit;
    }
}

if (!function_exists('kovcheg_render_legacy_home_alias')) {
    function kovcheg_render_legacy_home_alias(): void
    {
        $categories=DB::all("SELECT c.id,c.name,c.slug,c.description,
            (SELECT COUNT(*) FROM content_entry_categories ec JOIN content_entries e ON e.id=ec.entry_id
             WHERE ec.category_id=c.id AND e.type='page' AND e.status='published' AND e.visibility='public'
               AND e.deleted_at IS NULL AND (e.published_at IS NULL OR e.published_at<=CURRENT_TIMESTAMP)) page_count
            FROM content_categories c ORDER BY c.sort_order,c.name");
        Blog::render('home',[
            'title'=>(string)setting('blog_home_title',setting('site_name','KOVCHEG CMS')),
            'pages'=>Blog::entries('page',max(6,min(36,(int)setting('blog_posts_per_page','18')))),
            'categories'=>$categories,
        ]);
    }
}

if (!function_exists('kovcheg_redirect_legacy_content')) {
    function kovcheg_redirect_legacy_content(string $slug): never
    {
        $slug=trim($slug);
        if($slug!==''){
            $entry=DB::one('SELECT slug FROM content_entries WHERE slug=? AND deleted_at IS NULL LIMIT 1',[$slug]);
            if($entry){
                header('Location: '.app_url('/page/'.rawurlencode((string)$entry['slug'])),true,301);
                exit;
            }
        }
        kovcheg_redirect_home_permanent();
    }
}

/* Old top-level URLs display the same Pages home; they are not separate sections. */
$router->get('/blog', function () { kovcheg_render_legacy_home_alias(); });
$router->get('/portfolio', function () { kovcheg_render_legacy_home_alias(); });
$router->get('/tag/{slug}', function () { kovcheg_redirect_home_permanent(); });
$router->get('/blog/{slug}', function (array $params) { kovcheg_redirect_legacy_content((string)($params['slug']??'')); });
$router->get('/portfolio/{slug}', function (array $params) { kovcheg_redirect_legacy_content((string)($params['slug']??'')); });

/* Old Studio bookmarks all open the single Pages section. */
$router->get('/studio/posts', function () { redirect('/studio/pages'); });
$router->get('/studio/posts/new', function () { redirect('/studio/pages/new'); });
$router->get('/studio/posts/{id}/edit', function (array $params) { redirect('/studio/pages/'.(int)$params['id'].'/edit'); });
$router->get('/studio/content', function () { redirect('/studio/pages'); });
$router->get('/studio/content/new', function () { redirect('/studio/pages/new'); });
$router->get('/studio/content/{id}/edit', function (array $params) { redirect('/studio/pages/'.(int)$params['id'].'/edit'); });
$router->get('/studio/portfolio', function () { redirect('/studio/pages'); });

/* Old save forms remain accepted, but always create or update a Page. */
$router->post('/studio/content/save', function () {
    Studio::require('content');
    Csrf::validate();
    $input=$_POST;
    $input['type']='page';
    $input['tags']='';
    if(!empty($_FILES['featured_image']['name'])){
        $media=Studio32::storeMedia($_FILES['featured_image'],Auth::id(),0);
        $input['featured_image_path']=(string)($media['stored_path']??'');
    }
    $id=Studio32::saveEntry($input,Auth::id(),(int)($_POST['id']??0));
    $_SESSION['flash_success']='Страница сохранена.';
    redirect('/studio/pages/'.$id.'/edit');
});

/* Rubrics are optional sections for Pages: Новости, Блог, Документы, etc. */
$router->get('/studio/categories', function () {
    Studio::require('content');
    $categories=DB::all("SELECT c.*,
        (SELECT COUNT(*) FROM content_entry_categories ec
         JOIN content_entries e ON e.id=ec.entry_id
         WHERE ec.category_id=c.id AND e.type='page' AND e.deleted_at IS NULL) entry_count
        FROM content_categories c ORDER BY c.sort_order,c.name");
    Studio::render('categories',['studioSection'=>'categories','studioTitle'=>'Рубрики','categories'=>$categories]);
});

$router->post('/studio/categories/save', function () {
    Studio::require('content');
    Csrf::validate();
    $id=max(0,(int)($_POST['id']??0));
    $name=mb_substr(trim((string)($_POST['name']??'')),0,150);
    if($name==='')abort(422,'Введите название рубрики.');
    $slug=Studio::slugify((string)($_POST['slug']??$name));
    if($slug==='')abort(422,'Не удалось сформировать адрес рубрики.');
    if(DB::one('SELECT id FROM content_categories WHERE slug=? AND id<>?',[$slug,$id]))abort(422,'Такой адрес рубрики уже используется.');
    $description=mb_substr(trim((string)($_POST['description']??'')),0,2000);
    if($id){
        DB::run('UPDATE content_categories SET name=?,slug=?,description=?,sort_order=0,updated_at=CURRENT_TIMESTAMP WHERE id=?',[$name,$slug,$description?:null,$id]);
    }else{
        $id=DB::insert('INSERT INTO content_categories (name,slug,description,sort_order,created_at,updated_at) VALUES (?,?,?,0,CURRENT_TIMESTAMP,CURRENT_TIMESTAMP)',[$name,$slug,$description?:null]);
    }
    audit('cms.category.save','content_category',$id);
    $_SESSION['flash_success']='Рубрика сохранена.';
    redirect('/studio/categories');
});

$router->post('/studio/categories/{id}/delete', function (array $params) {
    Studio::require('content');
    Csrf::validate();
    $id=(int)$params['id'];
    DB::run('DELETE FROM content_categories WHERE id=?',[$id]);
    audit('cms.category.delete','content_category',$id);
    $_SESSION['flash_success']='Рубрика удалена. Страницы сохранены.';
    redirect('/studio/categories');
});
