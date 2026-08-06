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

/* Old public bookmark remains HTTP 200, but shows ordinary Posts only. */
$router->get('/portfolio', function () {
    Blog::render('archive',[
        'title'=>'Записи',
        'archiveTitle'=>'Записи',
        'archiveDescription'=>'Новости и статьи блога.',
        'entries'=>Blog::entries('post',100),
        'entryType'=>'post',
    ]);
});

/* Old forms and bookmarks keep working, but content is normalized to post/page. */
$router->post('/studio/content/save', function () {
    Studio::require('content');
    Csrf::validate();
    $input=$_POST;
    $input['type']=(string)($input['type']??'')==='page'?'page':'post';
    $input['tags']='';
    if($input['type']==='page'){
        $input['category_ids']=[];
        $input['excerpt']='';
    }
    if(!empty($_FILES['featured_image']['name'])){
        $media=Studio32::storeMedia($_FILES['featured_image'],Auth::id(),0);
        $input['featured_image_path']=(string)($media['stored_path']??'');
    }
    $id=Studio32::saveEntry($input,Auth::id(),(int)($_POST['id']??0));
    $_SESSION['flash_success']=$input['type']==='page'?'Страница сохранена.':'Запись сохранена.';
    redirect('/studio/'.($input['type']==='page'?'pages':'posts').'/'.$id.'/edit');
});

/* Categories belong only to Posts, as in WordPress. */
$router->get('/studio/categories', function () {
    Studio::require('content');
    $categories=DB::all("SELECT c.*,
        (SELECT COUNT(*) FROM content_entry_categories ec
         JOIN content_entries e ON e.id=ec.entry_id
         WHERE ec.category_id=c.id AND e.type='post' AND e.deleted_at IS NULL) entry_count
        FROM content_categories c ORDER BY c.name");
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
    audit('blog.category.save','content_category',$id);
    $_SESSION['flash_success']='Рубрика сохранена.';
    redirect('/studio/categories');
});

$router->post('/studio/categories/{id}/delete', function (array $params) {
    Studio::require('content');
    Csrf::validate();
    $id=(int)$params['id'];
    DB::run('DELETE FROM content_categories WHERE id=?',[$id]);
    audit('blog.category.delete','content_category',$id);
    $_SESSION['flash_success']='Рубрика удалена. Записи сохранены.';
    redirect('/studio/categories');
});
