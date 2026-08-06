<?php

declare(strict_types=1);

use Kovcheg\DB;
use Kovcheg\Blog\Blog;

$entries=array_values($entries??[]);
$entryType=(string)($entryType??'category');
$archiveTitle=trim((string)($archiveTitle??($entryType==='search'?'Результаты поиска':'Раздел')));
$archiveDescription=trim((string)($archiveDescription??($entryType==='search'?'Найденные страницы.':'Страницы этого раздела.')));

$coverUrl=static function(array $entry):string{
    $path=trim((string)($entry['featured_image_path']??''));
    if($path==='')return '';
    $id=(int)(DB::one('SELECT id FROM media_library WHERE stored_path=? LIMIT 1',[$path])['id']??0);
    return $id>0?app_url('/media/'.$id):'';
};
?>
<link rel="stylesheet" href="<?=e($themeAsset('category.css').'?v='.rawurlencode(ASSET_REVISION))?>">

<section class="category-page">
 <nav><a href="<?=e(app_url('/'))?>">← На главную</a></nav>
 <header><span><?=$entryType==='search'?'Поиск':'Рубрика'?></span><h1><?=e($archiveTitle)?></h1><p><?=e($archiveDescription)?></p></header>

 <?php if($entries):?>
 <div class="category-page-grid">
  <?php foreach($entries as $entry):$cover=$coverUrl($entry);?>
  <article class="category-page-card <?=$cover!==''?'has-cover':'without-cover'?>">
   <?php if($cover!==''):?><a class="category-page-card__cover" href="<?=e(Blog::entryUrl($entry))?>"><img src="<?=e($cover)?>" alt="<?=e((string)$entry['title'])?>" loading="lazy"></a><?php endif;?>
   <div><h2><a href="<?=e(Blog::entryUrl($entry))?>"><?=e((string)$entry['title'])?></a></h2><p><?=e(Blog::excerpt($entry,240))?></p><footer><span>Обновлено <?=e(date('d.m.Y',strtotime((string)($entry['updated_at']?:$entry['published_at']?:$entry['created_at']))))?></span><a href="<?=e(Blog::entryUrl($entry))?>">Открыть →</a></footer></div>
  </article>
  <?php endforeach;?>
 </div>
 <?php else:?>
 <div class="category-page-empty"><h2><?=$entryType==='search'?'Ничего не найдено':'В рубрике пока нет страниц'?></h2><p><?=$entryType==='search'?'Попробуйте изменить запрос.':'Добавьте нужные страницы в эту рубрику через KOVCHEG Studio.'?></p></div>
 <?php endif;?>
</section>
