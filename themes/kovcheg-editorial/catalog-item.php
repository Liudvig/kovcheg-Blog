<?php
$item=$showcaseItem??[];
$config=$showcaseConfig??[];
$type=(string)($showcaseType??'product');
$meta=(array)($item['_meta']??[]);
$image=(string)($item['_image_url']??'');
$price=(string)($meta['price']??'');
$unit=(string)($meta['unit']??'');
$actionLabel=trim((string)($meta['action_label']??''));
$actionUrl=trim((string)($meta['action_url']??''));
if($actionUrl!==''&&!preg_match('~^https?://~i',$actionUrl))$actionUrl=app_url('/'.ltrim($actionUrl,'/'));
?>
<article class="farm-item-page">
 <nav class="farm-item-breadcrumb"><a href="<?=e(app_url('/'))?>">Главная</a><span>›</span><a href="<?=e((string)$config['public_base'])?>"><?=e((string)$config['plural'])?></a></nav>
 <div class="farm-item-hero <?=$image!==''?'has-image':''?>">
  <div class="farm-item-hero__media"><?php if($image!==''):?><img src="<?=e($image)?>" alt="<?=e((string)$item['title'])?>"><?php else:?><span><?=e(mb_substr((string)$item['title'],0,1))?></span><?php endif;?></div>
  <div class="farm-item-hero__content">
   <span class="farm-item-kicker"><?=e((string)$config['singular'])?></span>
   <h1><?=e((string)$item['title'])?></h1>
   <p><?=e((string)($item['excerpt']??''))?></p>
   <div class="farm-item-facts">
    <?php if($type==='product'):?>
     <?php if(!empty($meta['category'])):?><div><small>Категория</small><b><?=e((string)$meta['category'])?></b></div><?php endif;?>
     <?php if(!empty($meta['stock_status'])):?><div><small>Наличие</small><b><?=e((string)$meta['stock_status'])?></b></div><?php endif;?>
    <?php elseif($type==='livestock'):?>
     <?php foreach(['species'=>'Вид','breed'=>'Порода','sex'=>'Пол','age'=>'Возраст','stock_status'=>'Статус'] as $key=>$label):if(empty($meta[$key]))continue;?><div><small><?=e($label)?></small><b><?=e((string)$meta[$key])?></b></div><?php endforeach;?>
    <?php else:?>
     <?php if(!empty($meta['project_kind'])):?><div><small>Тип</small><b><?=e((string)$meta['project_kind'])?></b></div><?php endif;?>
     <?php if(!empty($meta['location'])):?><div><small>Место</small><b><?=e((string)$meta['location'])?></b></div><?php endif;?>
    <?php endif;?>
   </div>
   <?php if($price!==''):?><div class="farm-item-price"><?=e($price)?><?= $unit!==''?' <small>/ '.e($unit).'</small>':'' ?></div><?php endif;?>
   <?php if($actionUrl!==''&&$actionLabel!==''):?><a class="farm-item-cta" href="<?=e($actionUrl)?>"><?=e($actionLabel)?></a><?php endif;?>
  </div>
 </div>
 <?php if(trim((string)($item['content_html']??''))!==''):?><section class="farm-item-description prose"><?= (string)$item['content_html'] ?></section><?php endif;?>
</article>
