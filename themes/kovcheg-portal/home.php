<?php

declare(strict_types=1);

use Kovcheg\Auth;
use Kovcheg\DB;
use Kovcheg\Blog\Blog;
use Kovcheg\Blog\FarmShowcase;

$posts=array_values($posts??[]);
$pages=array_values($pages??[]);

$coverUrl=static function(array $entry):string{
    $path=trim((string)($entry['featured_image_path']??''));
    if($path==='')return '';
    $id=(int)(DB::one('SELECT id FROM media_library WHERE stored_path=? LIMIT 1',[$path])['id']??0);
    return $id>0?app_url('/media/'.$id):'';
};
?>
<link rel="stylesheet" href="<?=e($themeAsset('site-home.css').'?v='.rawurlencode(ASSET_REVISION))?>">

<section class="site-home-hero">
 <div>
  <span><?=e(setting('blog_tagline','KOVCHEG CMS'))?></span>
  <h1><?=e(setting('site_name','KOVCHEG'))?></h1>
  <p><?=e(setting('blog_description','Информация и материалы сайта.'))?></p>
 </div>
 <div class="site-home-hero__actions"><a href="<?=e(app_url('/catalog'))?>">Смотреть продукцию</a><?php if(Auth::isAdmin()):?><a href="<?=e(app_url('/studio/catalog/new'))?>">+ Добавить товар</a><?php endif;?></div>
</section>

<?=FarmShowcase::homeShowcaseHtml()?>

<section class="site-home-section">
 <header><div><span>Материалы</span><h2>Последние записи</h2></div><?php if(Auth::isAdmin()):?><a href="<?=e(app_url('/studio/posts'))?>">Управление записями</a><?php endif;?></header>
 <?php if($posts):?>
 <div class="site-home-pages">
  <?php foreach($posts as $entry):$cover=$coverUrl($entry);?>
  <article class="site-home-page-card <?=$cover!==''?'has-cover':'without-cover'?>">
   <?php if($cover!==''):?><a class="site-home-page-cover" href="<?=e(Blog::entryUrl($entry))?>"><img src="<?=e($cover)?>" alt="<?=e((string)$entry['title'])?>" loading="lazy"></a><?php endif;?>
   <div><h3><a href="<?=e(Blog::entryUrl($entry))?>"><?=e((string)$entry['title'])?></a></h3><p><?=e(Blog::excerpt($entry,220))?></p><footer><span><?=e(date('d.m.Y',strtotime((string)($entry['published_at']?:$entry['created_at']))))?></span><a href="<?=e(Blog::entryUrl($entry))?>">Читать →</a></footer></div>
  </article>
  <?php endforeach;?>
 </div>
 <?php else:?>
 <div class="site-home-empty"><h2>Записей пока нет</h2><p>Создайте первую запись и выберите рубрику. Блок рубрик появится только там, куда вы добавите соответствующий виджет или меню.</p><?php if(Auth::isAdmin()):?><a href="<?=e(app_url('/studio/posts/new'))?>">Создать запись</a><?php endif;?></div>
 <?php endif;?>
</section>

<?php if($pages):?>
<section class="site-home-section site-home-section--pages">
 <header><div><span>Страницы</span><h2>Разделы сайта</h2></div><?php if(Auth::isAdmin()):?><a href="<?=e(app_url('/studio/pages'))?>">Управление страницами</a><?php endif;?></header>
 <div class="site-home-rubrics">
  <?php foreach(array_slice($pages,0,6) as $page):?><a href="<?=e(Blog::entryUrl($page))?>"><div><h3><?=e((string)$page['title'])?></h3><p><?=e(Blog::excerpt($page,130))?></p></div><strong>→</strong></a><?php endforeach;?>
 </div>
</section>
<?php endif;?>
