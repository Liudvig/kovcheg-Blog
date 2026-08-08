<?php
/** @var string $showcaseType */
/** @var array $config */
/** @var array $items */
$newUrl=app_url('/studio/'.(string)$config['section'].'/new');
$archiveUrl=app_url((string)$config['public_base']);
?>
<div class="page-head cms-showcase-head">
 <div><h1><?=e((string)$config['plural'])?></h1><p>Реальные материалы сайта. Отмеченные как рекламные автоматически попадают в соответствующий слайдер на главной.</p></div>
 <div class="editor-actions"><a class="button" href="<?=e($archiveUrl)?>" target="_blank" rel="noopener">Открыть на сайте</a><a class="button primary" href="<?=e($newUrl)?>">+ Добавить</a></div>
</div>

<div class="cms-showcase-grid">
 <?php foreach($items as $item):$meta=(array)($item['_meta']??[]);$image=(string)($item['_image_url']??'');?>
 <article class="cms-showcase-card">
  <a class="cms-showcase-card__media" href="<?=e(app_url('/studio/'.(string)$config['section'].'/'.(int)$item['id'].'/edit'))?>">
   <?php if($image!==''):?><img src="<?=e($image)?>" alt="" loading="lazy"><?php else:?><span><?=e(mb_substr((string)$item['title'],0,1))?></span><?php endif;?>
  </a>
  <div class="cms-showcase-card__body">
   <div class="cms-showcase-card__flags"><span class="status <?=e((string)$item['status'])?>"><?=e((string)$item['status'])?></span><?php if(!empty($item['is_featured'])):?><span class="cms-promo-badge">В слайдере</span><?php endif;?></div>
   <h3><?=e((string)$item['title'])?></h3>
   <p><?=e((string)($item['excerpt']??''))?></p>
   <div class="cms-showcase-card__meta">
    <?php if(!empty($meta['price'])):?><b><?=e((string)$meta['price'])?><?=!empty($meta['unit'])?' / '.e((string)$meta['unit']):''?></b><?php endif;?>
    <?php if(!empty($meta['stock_status'])):?><span><?=e((string)$meta['stock_status'])?></span><?php endif;?>
   </div>
   <div class="cms-showcase-card__actions"><a class="button small" href="<?=e(app_url('/studio/'.(string)$config['section'].'/'.(int)$item['id'].'/edit'))?>">Изменить</a><?php if((string)$item['status']==='published'):?><a class="button small" href="<?=e((string)$item['_public_url'])?>" target="_blank" rel="noopener">Посмотреть</a><?php endif;?><form method="post" data-confirm="Переместить материал в корзину?" action="<?=e(app_url('/studio/showcase/'.(int)$item['id'].'/trash'))?>"><?=csrf_field()?><input type="hidden" name="showcase_type" value="<?=e($showcaseType)?>"><button class="button small danger">В корзину</button></form></div>
  </div>
 </article>
 <?php endforeach;?>
 <?php if(!$items):?><div class="empty-state cms-showcase-empty"><h2>Пока пусто</h2><p>Добавьте первый материал. После публикации он появится в публичном разделе, а рекламный материал — ещё и на главной.</p><a class="button primary" href="<?=e($newUrl)?>">Добавить</a></div><?php endif;?>
</div>
