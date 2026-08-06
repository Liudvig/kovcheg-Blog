<?php

declare(strict_types=1);

use Kovcheg\Auth;
use Kovcheg\DB;
use Kovcheg\Blog\Blog;

$pages=array_values($pages??[]);
$categories=array_values($categories??[]);

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
  <span>KOVCHEG CMS</span>
  <h1><?=e(setting('site_name','KOVCHEG'))?></h1>
  <p><?=e(setting('blog_description','Информация, документы и разделы сайта.'))?></p>
 </div>
 <?php if(Auth::isAdmin()):?><a href="<?=e(app_url('/studio/pages/new'))?>">+ Добавить страницу</a><?php endif;?>
</section>

<?php if($categories):?>
<section class="site-home-section">
 <header><div><span>Разделы</span><h2>Рубрики сайта</h2></div></header>
 <div class="site-home-rubrics">
  <?php foreach($categories as $category):?>
  <a href="<?=e(app_url('/category/'.rawurlencode((string)$category['slug'])))?>">
   <div><h3><?=e((string)$category['name'])?></h3><p><?=e((string)($category['description']?:'Открыть страницы этого раздела.'))?></p></div>
   <strong><?=(int)$category['page_count']?></strong>
  </a>
  <?php endforeach;?>
 </div>
</section>
<?php endif;?>

<section class="site-home-section">
 <header><div><span>Страницы</span><h2>Последние материалы</h2></div><?php if(Auth::isAdmin()):?><a href="<?=e(app_url('/studio/pages'))?>">Управление страницами</a><?php endif;?></header>
 <?php if($pages):?>
 <div class="site-home-pages">
  <?php foreach($pages as $entry):$cover=$coverUrl($entry);?>
  <article class="site-home-page-card <?=$cover!==''?'has-cover':'without-cover'?>">
   <?php if($cover!==''):?><a class="site-home-page-cover" href="<?=e(Blog::entryUrl($entry))?>"><img src="<?=e($cover)?>" alt="<?=e((string)$entry['title'])?>" loading="lazy"></a><?php endif;?>
   <div><h3><a href="<?=e(Blog::entryUrl($entry))?>"><?=e((string)$entry['title'])?></a></h3><p><?=e(Blog::excerpt($entry,220))?></p><footer><span><?=e(date('d.m.Y',strtotime((string)($entry['updated_at']?:$entry['published_at']?:$entry['created_at']))))?></span><a href="<?=e(Blog::entryUrl($entry))?>">Открыть →</a></footer></div>
  </article>
  <?php endforeach;?>
 </div>
 <?php else:?>
 <div class="site-home-empty"><h2>Страниц пока нет</h2><p>Создайте первую страницу. При необходимости добавьте её в рубрику и меню.</p><?php if(Auth::isAdmin()):?><a href="<?=e(app_url('/studio/pages/new'))?>">Создать страницу</a><?php endif;?></div>
 <?php endif;?>
</section>
