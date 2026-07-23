<?php

declare(strict_types=1);

use Kovcheg\Blog\Layout;

$pageTitle = trim((string)($title ?? ''));
$siteSeoTitle = trim((string)setting('seo_site_title', '')) ?: $siteName;
$metaDescription = trim((string)($description ?? setting('seo_default_description', setting('seo_description', 'Новости, статьи, проекты и аналитика.'))));
$canonical = current_absolute_url();
$indexing = setting('seo_robots_index', setting('search_indexing', '0')) === '1';
$logo = app_url('/brand/logo?v='.rawurlencode(APP_VERSION));
$favicon = app_url('/brand/favicon?v='.rawurlencode(APP_VERSION));
$layoutContext = is_array($layoutContext ?? null) ? $layoutContext : ['page_type'=>'default'];

$zone=static function(string $matrixZone,string $legacyZone='')use($layoutContext):string{
    $html=Layout::renderZone($matrixZone,$layoutContext);
    if($html===''&&$legacyZone!=='')$html=Layout::renderZone($legacyZone,$layoutContext);
    return $html;
};

$preheader=$zone('matrix.preheader','header.top');
$postheader=$zone('matrix.postheader','header.bottom');
$bannerTop=$zone('matrix.banner.top','page.before');
$bannerBottom=$zone('matrix.banner.bottom','page.after');

$headerSlots=[];
for($i=1;$i<=5;$i++)$headerSlots[$i]=$zone('matrix.header.'.$i);
if(implode('',$headerSlots)==='')$headerSlots[3]=Layout::renderZone('header.main',$layoutContext);

$leftSlots=[];$rightSlots=[];$centerSlots=[];$footerSlots=[];
for($i=1;$i<=4;$i++){
    $leftSlots[$i]=$zone('matrix.left.'.$i);
    $rightSlots[$i]=$zone('matrix.right.'.$i);
}
if(implode('',$leftSlots)==='')$leftSlots[1]=Layout::renderZone('layout.left',$layoutContext);
if(implode('',$rightSlots)==='')$rightSlots[1]=Layout::renderZone('layout.right',$layoutContext);
for($i=1;$i<=12;$i++)$centerSlots[$i]=$zone('matrix.center.'.$i);
if(implode('',$centerSlots)===''){
    $centerSlots[1]=Layout::renderZone('content.before',$layoutContext);
    $centerSlots[5]=Layout::renderZone('content.after',$layoutContext);
}
for($i=1;$i<=8;$i++)$footerSlots[$i]=$zone('matrix.footer.'.$i);
if(implode('',$footerSlots)===''){
    $footerSlots[1]=Layout::renderZone('footer.top',$layoutContext);
    $footerSlots[2]=Layout::renderZone('footer.columns',$layoutContext);
    $footerSlots[8]=Layout::renderZone('footer.bottom',$layoutContext);
}

$hasLeft=implode('',$leftSlots)!=='';
$hasRight=implode('',$rightSlots)!=='';
$columnsClass=$hasLeft&&$hasRight?'portal-matrix--three':($hasLeft?'portal-matrix--left':($hasRight?'portal-matrix--right':'portal-matrix--single'));
$copyright='© '.date('Y').' Ланцет Семён Борисович';
$flash=[];
if(!empty($_SESSION['flash_error'])){$flash[]=['type'=>'error','text'=>(string)$_SESSION['flash_error']];unset($_SESSION['flash_error']);}
if(!empty($_SESSION['flash_success'])){$flash[]=['type'=>'success','text'=>(string)$_SESSION['flash_success']];unset($_SESSION['flash_success']);}
?><!doctype html>
<html lang="ru">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<meta name="theme-color" content="#0f172a">
<meta name="description" content="<?=e($metaDescription)?>">
<meta name="robots" content="<?=$indexing?'index,follow,max-image-preview:large':'noindex,nofollow,noarchive'?>">
<link rel="canonical" href="<?=e($canonical)?>">
<?php if(setting('seo_rss_enabled','1')==='1'):?><link rel="alternate" type="application/rss+xml" title="<?=e($siteName)?>" href="<?=e(app_url('/feed.xml'))?>"><?php endif;?>
<meta property="og:site_name" content="<?=e($siteName)?>">
<meta property="og:title" content="<?=e($pageTitle!==''?$pageTitle:$siteSeoTitle)?>">
<meta property="og:description" content="<?=e($metaDescription)?>">
<meta property="og:url" content="<?=e($canonical)?>">
<meta property="og:type" content="website">
<meta name="twitter:card" content="summary_large_image">
<title><?=e($pageTitle!==''?$pageTitle.' — '.$siteSeoTitle:$siteSeoTitle)?></title>
<link rel="icon" href="<?=e($favicon)?>">
<link rel="stylesheet" href="<?=e($themeAsset('theme.css').'?v='.rawurlencode(ASSET_REVISION))?>">
<link rel="stylesheet" href="<?=e($themeAsset('content.css').'?v='.rawurlencode(ASSET_REVISION))?>">
<link rel="stylesheet" href="<?=e($themeAsset('fixed-shell.css').'?v='.rawurlencode(ASSET_REVISION))?>">
<link rel="stylesheet" href="<?=e($themeAsset('layout-matrix.css').'?v='.rawurlencode(ASSET_REVISION))?>">
<link rel="stylesheet" href="<?=e(app_url('/assets/css/blog-widgets.css?v='.rawurlencode(ASSET_REVISION)))?>">
<?=\Kovcheg\Hooks::fire('blog.layout.head','')?>
</head>
<body class="blog-theme blog-theme-portal blog-theme-portal-matrix <?=e($columnsClass)?>">
<a class="skip-link" href="#main-content">Перейти к содержанию</a>
<header class="portal-matrix-header">
 <?php if($preheader!==''):?><div class="portal-matrix-preheader"><?=$preheader?></div><?php endif;?>
 <div class="portal-matrix-header-grid">
  <?php for($i=1;$i<=5;$i++):?><div class="portal-matrix-header-cell portal-matrix-header-cell--<?=$i?>"><?php if($headerSlots[$i]!==''):?><?=$headerSlots[$i]?><?php elseif($i===1):?><a class="portal-brand-fallback" href="<?=e(app_url('/'))?>"><img src="<?=e($logo)?>" alt=""><span><b><?=e($siteName)?></b><small><?=e(setting('blog_tagline','Новости · мнения · проекты'))?></small></span></a><?php endif;?></div><?php endfor;?>
 </div>
 <?php if($postheader!==''):?><div class="portal-matrix-postheader"><?=$postheader?></div><?php endif;?>
</header>
<?php if($flash):?><div class="flash-stack" aria-live="polite"><?php foreach($flash as $message):?><div class="flash flash--<?=e($message['type'])?>"><?=e($message['text'])?></div><?php endforeach;?></div><?php endif;?>
<div class="portal-matrix-viewport">
 <?php if($bannerTop!==''):?><div class="portal-matrix-banner portal-matrix-banner--top"><?=$bannerTop?></div><?php endif;?>
 <div class="portal-matrix-columns <?=e($columnsClass)?>">
  <?php if($hasLeft):?><aside class="portal-matrix-sidebar portal-matrix-sidebar--left" aria-label="Левая колонка"><div class="portal-matrix-sidebar-grid"><?php for($i=1;$i<=4;$i++):?><div class="portal-matrix-slot portal-matrix-slot--sidebar"><?=$leftSlots[$i]?></div><?php endfor;?></div></aside><?php endif;?>
  <main id="main-content" class="portal-matrix-content"><div class="portal-matrix-content-grid"><?php for($i=1;$i<=4;$i++):?><div class="portal-matrix-slot portal-matrix-slot--content"><?=$centerSlots[$i]?></div><?php endfor;?><article class="portal-matrix-page-content"><?=$content?></article><?php for($i=5;$i<=12;$i++):?><div class="portal-matrix-slot portal-matrix-slot--content"><?=$centerSlots[$i]?></div><?php endfor;?></div></main>
  <?php if($hasRight):?><aside class="portal-matrix-sidebar portal-matrix-sidebar--right" aria-label="Правая колонка"><div class="portal-matrix-sidebar-grid"><?php for($i=1;$i<=4;$i++):?><div class="portal-matrix-slot portal-matrix-slot--sidebar"><?=$rightSlots[$i]?></div><?php endfor;?></div></aside><?php endif;?>
 </div>
 <?php if($bannerBottom!==''):?><div class="portal-matrix-banner portal-matrix-banner--bottom"><?=$bannerBottom?></div><?php endif;?>
</div>
<footer class="portal-matrix-footer">
 <div class="portal-matrix-footer-grid"><?php for($i=1;$i<=8;$i++):?><div class="portal-matrix-slot portal-matrix-slot--footer"><?=$footerSlots[$i]?></div><?php endfor;?></div>
 <div class="portal-matrix-copyright"><span><?=e($copyright)?></span><span>KOVCHEG Blog <?=e(APP_VERSION)?> · Все права защищены</span><?php if(setting('seo_rss_enabled','1')==='1'):?><a href="<?=e(app_url('/feed.xml'))?>">RSS</a><?php endif;?></div>
</footer>
<script src="<?=e(app_url('/assets/js/blog-widgets.js?v='.rawurlencode(ASSET_REVISION)))?>" defer></script>
<?=\Kovcheg\Hooks::fire('blog.layout.scripts','')?>
</body>
</html>