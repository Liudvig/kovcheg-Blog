<?php

declare(strict_types=1);
use Kovcheg\Auth;
use Kovcheg\Csrf;
use Kovcheg\DB;
use Kovcheg\Blog\Blog;

function blog_record_view(int $entryId): void
{
    if ($entryId < 1) return;
    try {
        DB::run('INSERT INTO content_views_daily (entry_id,view_date,views) VALUES (?,CURRENT_DATE,1) ON DUPLICATE KEY UPDATE views=views+1', [$entryId]);
    } catch (Throwable) {}
}

function blog_taxonomy_archive(string $kind, string $slug): array
{
    if (!in_array($kind, ['category','tag'], true)) return ['term'=>null,'entries'=>[]];
    if ($kind === 'category') {
        $term = DB::one('SELECT id,name,slug,description FROM content_categories WHERE slug=? LIMIT 1', [$slug]);
        if (!$term) return ['term'=>null,'entries'=>[]];
        $entries = DB::all("SELECT e.*,u.display_name author_name,u.username author_username,u.avatar_path,
            (SELECT COUNT(*) FROM content_comments c WHERE c.entry_id=e.id AND c.status='approved' AND c.deleted_at IS NULL) comment_count,
            (SELECT COUNT(*) FROM content_reactions r WHERE r.entry_id=e.id) reaction_count
            FROM content_entries e JOIN users u ON u.id=e.author_id JOIN content_entry_categories ec ON ec.entry_id=e.id
            WHERE ec.category_id=? AND e.status='published' AND e.visibility='public' AND e.deleted_at IS NULL
            AND (e.published_at IS NULL OR e.published_at<=CURRENT_TIMESTAMP)
            ORDER BY e.published_at DESC,e.id DESC LIMIT 100", [(int)$term['id']]);
    } else {
        $term = DB::one('SELECT id,name,slug FROM content_tags WHERE slug=? LIMIT 1', [$slug]);
        if (!$term) return ['term'=>null,'entries'=>[]];
        $entries = DB::all("SELECT e.*,u.display_name author_name,u.username author_username,u.avatar_path,
            (SELECT COUNT(*) FROM content_comments c WHERE c.entry_id=e.id AND c.status='approved' AND c.deleted_at IS NULL) comment_count,
            (SELECT COUNT(*) FROM content_reactions r WHERE r.entry_id=e.id) reaction_count
            FROM content_entries e JOIN users u ON u.id=e.author_id JOIN content_entry_tags et ON et.entry_id=e.id
            WHERE et.tag_id=? AND e.status='published' AND e.visibility='public' AND e.deleted_at IS NULL
            AND (e.published_at IS NULL OR e.published_at<=CURRENT_TIMESTAMP)
            ORDER BY e.published_at DESC,e.id DESC LIMIT 100", [(int)$term['id']]);
    }
    return ['term'=>$term,'entries'=>$entries];
}

function blog_entry_context(array $entry): array
{
    $id=(int)$entry['id'];
    $categories=DB::all('SELECT c.id,c.name,c.slug FROM content_categories c JOIN content_entry_categories ec ON ec.category_id=c.id WHERE ec.entry_id=? ORDER BY c.sort_order,c.name',[$id]);
    $tags=DB::all('SELECT t.id,t.name,t.slug FROM content_tags t JOIN content_entry_tags et ON et.tag_id=t.id WHERE et.entry_id=? ORDER BY t.name',[$id]);
    $meta=[];foreach(DB::all('SELECT meta_key,meta_value FROM content_entry_meta WHERE entry_id=?',[$id]) as $item)$meta[(string)$item['meta_key']]=(string)($item['meta_value']??'');
    $views=(int)(DB::one('SELECT COALESCE(SUM(views),0) total FROM content_views_daily WHERE entry_id=?',[$id])['total']??0);
    $related=DB::all("SELECT e.id,e.type,e.title,e.slug,e.excerpt,e.featured_image_path,e.published_at,u.display_name author_name,u.username author_username
        FROM content_entries e JOIN users u ON u.id=e.author_id WHERE e.id<>? AND e.type=? AND e.status='published' AND e.visibility='public' AND e.deleted_at IS NULL
        ORDER BY e.published_at DESC,e.id DESC LIMIT 3",[$id,$entry['type']]);
    return ['categories'=>$categories,'tags'=>$tags,'portfolioMeta'=>$meta,'viewCount'=>$views,'relatedEntries'=>$related];
}

$router->get('/', function () {
    Blog::render('home', [
        'title' => (string)setting('blog_home_title', setting('site_name', 'KOVCHEG Blog')),
        'posts' => Blog::entries('post', 8),
        'portfolio' => Blog::entries('portfolio', 6),
    ]);
});

$router->get('/blog', function () {
    Blog::render('archive', [
        'title' => 'Блог','archiveTitle' => 'Блог',
        'archiveDescription' => (string)setting('blog_description', 'Разработки, идеи, опыт и новые проекты.'),
        'entries' => Blog::entries('post', max(4,min(50,(int)setting('blog_posts_per_page','12')))),
        'entryType' => 'post',
    ]);
});

$router->get('/search', function () {
    $q=mb_substr(trim((string)($_GET['q']??'')),0,120);$entries=[];
    if(mb_strlen($q)>=2){$like='%'.$q.'%';$entries=DB::all("SELECT e.*,u.display_name author_name,u.username author_username,u.avatar_path,
        (SELECT COUNT(*) FROM content_comments c WHERE c.entry_id=e.id AND c.status='approved' AND c.deleted_at IS NULL) comment_count,
        (SELECT COUNT(*) FROM content_reactions r WHERE r.entry_id=e.id) reaction_count
        FROM content_entries e JOIN users u ON u.id=e.author_id WHERE e.status='published' AND e.visibility='public' AND e.deleted_at IS NULL
        AND (e.title LIKE ? OR e.excerpt LIKE ? OR e.content_html LIKE ?) ORDER BY e.is_featured DESC,e.published_at DESC,e.id DESC LIMIT 100",[$like,$like,$like]);}
    Blog::render('archive',['title'=>'Поиск','archiveTitle'=>$q!==''?'Поиск: '.$q:'Поиск','archiveDescription'=>$q!==''?'Найдено материалов: '.count($entries):'Введите не меньше двух символов.','entries'=>$entries,'entryType'=>'search','searchQuery'=>$q]);
});

$router->get('/category/{slug}', function(array $params){$archive=blog_taxonomy_archive('category',(string)$params['slug']);if(!$archive['term'])abort(404,'Рубрика не найдена.');Blog::render('archive',['title'=>(string)$archive['term']['name'],'archiveTitle'=>(string)$archive['term']['name'],'archiveDescription'=>(string)($archive['term']['description']??'Материалы рубрики.'),'entries'=>$archive['entries'],'entryType'=>'category']);});
$router->get('/tag/{slug}', function(array $params){$archive=blog_taxonomy_archive('tag',(string)$params['slug']);if(!$archive['term'])abort(404,'Тег не найден.');Blog::render('archive',['title'=>'#'.(string)$archive['term']['name'],'archiveTitle'=>'#'.(string)$archive['term']['name'],'archiveDescription'=>'Материалы с этим тегом.','entries'=>$archive['entries'],'entryType'=>'tag']);});

$router->get('/blog/{slug}', function (array $params) {
    $entry = Blog::entry((string)$params['slug'], 'post'); if (!$entry) abort(404, 'Публикация не найдена.');blog_record_view((int)$entry['id']);
    Blog::render('entry', array_merge(['title'=>(string)($entry['seo_title']?:$entry['title']),'description'=>(string)($entry['seo_description']?:Blog::excerpt($entry,300)),'entry'=>$entry,'comments'=>Blog::comments((int)$entry['id']),'reactions'=>Blog::reactions((int)$entry['id'])],blog_entry_context($entry)));
});

$router->get('/page/{slug}', function (array $params) {
    $entry = Blog::entry((string)$params['slug'], 'page'); if (!$entry) abort(404, 'Страница не найдена.');blog_record_view((int)$entry['id']);
    Blog::render('entry', array_merge(['title'=>(string)($entry['seo_title']?:$entry['title']),'description'=>(string)($entry['seo_description']?:Blog::excerpt($entry,300)),'entry'=>$entry,'comments'=>Blog::comments((int)$entry['id']),'reactions'=>Blog::reactions((int)$entry['id'])],blog_entry_context($entry)));
});

$router->get('/portfolio', function () {
    Blog::render('archive', ['title'=>'Портфолио','archiveTitle'=>'Портфолио','archiveDescription'=>(string)setting('portfolio_description','Работы, проекты, релизы и результаты.'),'entries'=>Blog::entries('portfolio',60),'entryType'=>'portfolio']);
});

$router->get('/portfolio/{slug}', function (array $params) {
    $entry = Blog::entry((string)$params['slug'], 'portfolio'); if (!$entry) abort(404, 'Работа портфолио не найдена.');blog_record_view((int)$entry['id']);
    Blog::render('entry', array_merge(['title'=>(string)($entry['seo_title']?:$entry['title']),'description'=>(string)($entry['seo_description']?:Blog::excerpt($entry,300)),'entry'=>$entry,'comments'=>Blog::comments((int)$entry['id']),'reactions'=>Blog::reactions((int)$entry['id'])],blog_entry_context($entry)));
});

$router->get('/author/{username}', function (array $params) {
    $author = Blog::author((string)$params['username']); if (!$author) abort(404, 'Автор не найден.');
    Blog::render('author', ['title'=>(string)$author['display_name'],'author'=>$author,'entries'=>Blog::authorEntries((int)$author['id'])]);
});

$router->post('/content/{id}/comment', function (array $params) {
    Auth::requireLogin();Csrf::validate();$entryId=(int)$params['id'];$entry=DB::one("SELECT id,type,slug,comments_enabled FROM content_entries WHERE id=? AND status='published' AND deleted_at IS NULL LIMIT 1",[$entryId]);if(!$entry)abort(404,'Материал не найден.');if(empty($entry['comments_enabled']))abort(403,'Комментарии к этому материалу отключены.');
    $body=trim((string)($_POST['body']??''));if(mb_strlen($body)<2||mb_strlen($body)>5000)abort(422,'Комментарий должен содержать от 2 до 5000 символов.');$parentId=max(0,(int)($_POST['parent_id']??0));if($parentId>0&&!DB::one('SELECT id FROM content_comments WHERE id=? AND entry_id=? AND deleted_at IS NULL',[$parentId,$entryId]))abort(422,'Комментарий для ответа не найден.');
    $status=Blog::canModerate()||setting('comments_auto_approve','0')==='1'?'approved':'pending';$ip=(string)($_SERVER['REMOTE_ADDR']??'');$ipHash=$ip!==''?hash('sha256',$ip.'|'.(string)cfg('app.key','kovcheg')):null;$agent=utf8_substr((string)($_SERVER['HTTP_USER_AGENT']??''),0,255);
    DB::insert('INSERT INTO content_comments (entry_id,user_id,parent_id,body,status,ip_hash,user_agent,created_at,updated_at) VALUES (?,?,?,?,?,?,?,CURRENT_TIMESTAMP,CURRENT_TIMESTAMP)',[$entryId,Auth::id(),$parentId?:null,$body,$status,$ipHash,$agent]);$_SESSION['flash_success']=$status==='approved'?'Комментарий опубликован.':'Комментарий отправлен на проверку.';redirect(Blog::entryUrl($entry).'#comments');
});

$router->post('/content/comment/{id}/report', function(array $params){Auth::requireLogin();Csrf::validate();$id=(int)$params['id'];if(!DB::one("SELECT id FROM content_comments WHERE id=? AND status='approved' AND deleted_at IS NULL",[$id]))abort(404,'Комментарий не найден.');$reason=mb_substr(trim((string)($_POST['reason']??'Нарушение правил')),0,190);$details=mb_substr(trim((string)($_POST['details']??'')),0,2000);DB::run('INSERT INTO content_comment_reports (comment_id,reporter_id,reason,details,status,created_at) VALUES (?,?,?,?,\'open\',CURRENT_TIMESTAMP) ON DUPLICATE KEY UPDATE reason=VALUES(reason),details=VALUES(details),status=\'open\',created_at=CURRENT_TIMESTAMP',[$id,Auth::id(),$reason,$details?:null]);$_SESSION['flash_success']='Жалоба отправлена модератору.';redirect((string)($_SERVER['HTTP_REFERER']??'/blog').'#comments');});

$router->post('/content/{id}/reaction', function (array $params) {
    Auth::requireLogin();Csrf::validate();$entryId=(int)$params['id'];$entry=DB::one("SELECT id,type,slug,reactions_enabled FROM content_entries WHERE id=? AND status='published' AND deleted_at IS NULL LIMIT 1",[$entryId]);if(!$entry)abort(404,'Материал не найден.');if(empty($entry['reactions_enabled']))abort(403,'Реакции к этому материалу отключены.');$reaction=(string)($_POST['reaction']??'');$allowed=['👍','❤️','👏','🔥','💡'];if(!in_array($reaction,$allowed,true))abort(422,'Неизвестная реакция.');$existing=DB::one('SELECT reaction FROM content_reactions WHERE entry_id=? AND user_id=? LIMIT 1',[$entryId,Auth::id()]);DB::run('DELETE FROM content_reactions WHERE entry_id=? AND user_id=?',[$entryId,Auth::id()]);if(!$existing||!hash_equals((string)$existing['reaction'],$reaction))DB::run('INSERT INTO content_reactions (entry_id,user_id,reaction,created_at) VALUES (?,?,?,CURRENT_TIMESTAMP)',[$entryId,Auth::id(),$reaction]);redirect(Blog::entryUrl($entry).'#reactions');
});
