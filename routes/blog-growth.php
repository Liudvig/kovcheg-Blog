<?php

declare(strict_types=1);

use Kovcheg\Auth;
use Kovcheg\Csrf;
use Kovcheg\DB;
use Kovcheg\Blog\Growth;
use Kovcheg\Blog\Studio;

require_once BASE_PATH.'/app/BlogGrowth.php';

$baseUrl = static function (): string {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = preg_replace('/[^a-zA-Z0-9.:-]/','',(string)($_SERVER['HTTP_HOST']??'localhost')) ?: 'localhost';
    return $scheme.'://'.$host;
};

$router->get('/robots.txt', function () use ($baseUrl) {
    header('Content-Type: text/plain; charset=UTF-8');
    $index = setting('seo_robots_index','1')==='1';
    echo "User-agent: *\n";
    echo $index ? "Allow: /\n" : "Disallow: /\n";
    echo "Disallow: /admin\nDisallow: /studio\nDisallow: /api\n";
    if (setting('seo_sitemap_enabled','1')==='1') echo 'Sitemap: '.$baseUrl()."/sitemap.xml\n";
    exit;
});

$router->get('/sitemap.xml', function () use ($baseUrl) {
    if (setting('seo_sitemap_enabled','1')!=='1') abort(404,'Карта сайта отключена.');
    header('Content-Type: application/xml; charset=UTF-8');
    $xml=['<?xml version="1.0" encoding="UTF-8"?>','<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'];
    foreach(Growth::sitemapUrls() as $item){$xml[]='<url><loc>'.htmlspecialchars($baseUrl().$item['loc'],ENT_XML1|ENT_QUOTES,'UTF-8').'</loc>'.(!empty($item['updated_at'])?'<lastmod>'.date('c',strtotime((string)$item['updated_at'])).'</lastmod>':'').'</url>';}
    $xml[]='</urlset>';echo implode("\n",$xml);exit;
});

$router->get('/feed.xml', function () use ($baseUrl) {
    if (setting('seo_rss_enabled','1')!=='1') abort(404,'RSS отключён.');
    header('Content-Type: application/rss+xml; charset=UTF-8');
    $site=setting('site_name','KOVCHEG Blog');$description=setting('blog_description','Новые публикации');
    echo '<?xml version="1.0" encoding="UTF-8"?><rss version="2.0"><channel>';
    echo '<title>'.htmlspecialchars($site,ENT_XML1|ENT_QUOTES,'UTF-8').'</title><link>'.htmlspecialchars($baseUrl().'/',ENT_XML1|ENT_QUOTES,'UTF-8').'</link><description>'.htmlspecialchars($description,ENT_XML1|ENT_QUOTES,'UTF-8').'</description>';
    foreach(Growth::rssEntries() as $entry){$url=$baseUrl().'/blog/'.$entry['slug'];echo '<item><title>'.htmlspecialchars((string)$entry['title'],ENT_XML1|ENT_QUOTES,'UTF-8').'</title><link>'.htmlspecialchars($url,ENT_XML1|ENT_QUOTES,'UTF-8').'</link><guid isPermaLink="true">'.htmlspecialchars($url,ENT_XML1|ENT_QUOTES,'UTF-8').'</guid><description>'.htmlspecialchars((string)($entry['excerpt']?:strip_tags((string)$entry['content_html'])),ENT_XML1|ENT_QUOTES,'UTF-8').'</description><pubDate>'.date(DATE_RSS,strtotime((string)($entry['published_at']?:$entry['updated_at']))).'</pubDate><author>'.htmlspecialchars((string)$entry['author_name'],ENT_XML1|ENT_QUOTES,'UTF-8').'</author></item>';}
    echo '</channel></rss>';exit;
});

$router->post('/subscribe', function () {
    Csrf::validate();
    if(setting('subscriptions_enabled','1')!=='1')abort(404,'Подписка отключена.');
    Growth::subscribe((string)($_POST['email']??''),(string)($_POST['source']??'site'));
    $_SESSION['flash_success']='Подписка оформлена.';redirect((string)($_SERVER['HTTP_REFERER']??'/'));
});

$router->get('/studio/growth', function () {
    Studio::require('site');
    Studio::render('growth',['studioSection'=>'growth','studioTitle'=>'SEO и рост','redirects'=>DB::all('SELECT r.*,u.display_name creator_name FROM content_redirects r LEFT JOIN users u ON u.id=r.created_by ORDER BY r.id DESC LIMIT 500'),'subscriptions'=>DB::all('SELECT * FROM content_subscriptions ORDER BY id DESC LIMIT 500'),'scheduled'=>DB::all("SELECT id,title,type,published_at FROM content_entries WHERE status='scheduled' AND deleted_at IS NULL ORDER BY published_at,id LIMIT 200"),'publicationLog'=>DB::all('SELECT l.*,e.title FROM content_publication_log l JOIN content_entries e ON e.id=l.entry_id ORDER BY l.id DESC LIMIT 100')]);
});

$router->post('/studio/growth/settings', function () {
    Studio::require('site');Csrf::validate();
    $settings=['seo_site_title','seo_default_description','seo_robots_index','seo_sitemap_enabled','seo_rss_enabled','subscriptions_enabled'];
    foreach($settings as $key){$value=in_array($key,['seo_robots_index','seo_sitemap_enabled','seo_rss_enabled','subscriptions_enabled'],true)?(!empty($_POST[$key])?'1':'0'):mb_substr(trim((string)($_POST[$key]??'')),0,500);Studio::setSetting($key,$value);}
    $_SESSION['flash_success']='SEO-настройки сохранены.';redirect('/studio/growth');
});

$router->post('/studio/growth/redirects', function () {
    Studio::require('site');Csrf::validate();Growth::saveRedirect($_POST,Auth::id());$_SESSION['flash_success']='Редирект сохранён.';redirect('/studio/growth');
});

$router->post('/studio/growth/redirects/{id}/delete', function(array $params){Studio::require('site');Csrf::validate();DB::run('DELETE FROM content_redirects WHERE id=?',[(int)$params['id']]);$_SESSION['flash_success']='Редирект удалён.';redirect('/studio/growth');});

$router->post('/studio/growth/publish-scheduled', function(){Studio::require('content');Csrf::validate();$count=Growth::publishScheduled();$_SESSION['flash_success']='Опубликовано материалов: '.$count;redirect('/studio/growth');});
