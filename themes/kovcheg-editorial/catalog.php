<?php
use Kovcheg\Blog\FarmShowcase;
$items=array_values($showcaseItems??[]);
$config=$showcaseConfig??[];
$type=(string)($showcaseType??'product');
?>
<section class="farm-catalog-page">
 <header class="farm-catalog-hero"><div><span><?=e((string)($config['singular']??'Каталог'))?></span><h1><?=e((string)($config['plural']??'Каталог'))?></h1><p><?=e((string)($description??'Актуальные предложения.'))?></p></div></header>
 <?php if($items):?>
 <div class="farm-catalog-grid">
  <?php foreach($items as $item):$meta=(array)($item['_meta']??[]);$image=(string)($item['_image_url']??'');$price=(string)($meta['price']??'');$unit=(string)($meta['unit']??'');?>
  <article class="farm-catalog-card">
   <a class="farm-catalog-card__media" href="<?=e((string)$item['_public_url'])?>"><?php if($image!==''):?><img src="<?=e($image)?>" alt="<?=e((string)$item['title'])?>" loading="lazy"><?php else:?><span><?=e(mb_substr((string)$item['title'],0,1))?></span><?php endif;?></a>
   <div class="farm-catalog-card__body"><div class="farm-catalog-card__top"><?php if($type==='product'&&!empty($meta['category'])):?><span><?=e((string)$meta['category'])?></span><?php elseif($type==='livestock'&&!empty($meta['species'])):?><span><?=e((string)$meta['species'])?></span><?php elseif($type==='project'&&!empty($meta['project_kind'])):?><span><?=e((string)$meta['project_kind'])?></span><?php endif;?><?php if(!empty($meta['stock_status'])):?><em><?=e((string)$meta['stock_status'])?></em><?php endif;?></div><h2><a href="<?=e((string)$item['_public_url'])?>"><?=e((string)$item['title'])?></a></h2><p><?=e((string)($item['excerpt']??''))?></p><?php if($price!==''):?><strong><?=e($price)?><?= $unit!==''?' / '.e($unit):'' ?></strong><?php endif;?><a class="farm-catalog-card__action" href="<?=e((string)$item['_public_url'])?>">Подробнее →</a></div>
  </article>
  <?php endforeach;?>
 </div>
 <?php else:?><div class="farm-catalog-empty"><h2>Пока пусто</h2><p>Опубликованные материалы появятся здесь автоматически.</p></div><?php endif;?>
</section>
