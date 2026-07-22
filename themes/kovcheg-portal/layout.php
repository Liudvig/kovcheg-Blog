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

$zones = [];
foreach (['header.top','header.main','header.bottom','page.before','layout.left','content.before','content.after','layout.right','page.after','footer.top','footer.columns','footer.bottom'] as $zoneName) {
    $zones[$zoneName] = Layout::renderZone($zoneName, $layoutContext);
}

$hasLeft = $zones['layout.left'] !== '';
$hasRight = $zones['layout.right'] !== '';
$gridClass = $hasLeft && $hasRight ? 'portal-grid--three' : ($hasLeft ? 'portal-grid--left' : ($hasRight ? 'portal-grid--right' : 'portal-grid--single'));
$copyright='© '.date('Y').' Ланцет Семён Борисович';
$flash = [];
if (!empty($_SESSION['flash_error'])) {$flash[]=['type'=>'error','text'=>(string)$_SESSION['flash_error']];unset($_SESSION['flash_error']);}
if (!empty($_SESSION['flash_success'])) {$flash[]=['type'=>'success','text'=>(string)$_SESSION['flash_success']];unset($_SESSION['flash_success']);}
?><!doctype html>
<html lang="ru">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<meta name="theme-color" content="#f7f5ef">
<meta name="description" content="<?=e($metaDescription)?>">
<meta name="robots" content="<?=$indexing?'index,follow,max-image-preview:large':'noindex,nofollow,noarchive'?>">
<link rel="canonical" href="<?=e($canonical)?>">
<?php if(setting('seo_rss_enabled','1')==='1'):?><link rel="alternate" type="application/rss+xml" title="<?=e($siteName)?>" href="<?=e(app_url('/feed.xml'))?>"><?php endif;?>
<meta property="og:site_name" content="<?=e($siteName)?>">
<meta property="og:title" content="<?=e($pageTitle !== '' ? $pageTitle : $siteSeoTitle)?>">
<meta property="og:description" content="<?=e($metaDescription)?>">
<meta property="og:url" content="<?=e($canonical)?>">
<meta property="og:type" content="website">
<meta name="twitter:card" content="summary_large_image">
<title><?=e($pageTitle !== '' ? $pageTitle.' — '.$siteSeoTitle : $siteSeoTitle)?></title>
<link rel="icon" href="<?=e($favicon)?>">
<link rel="stylesheet" href="<?=e($themeAsset('theme.css').'?v='.rawurlencode(ASSET_REVISION))?>">
<link rel="stylesheet" href="<?=e($themeAsset('content.css').'?v='.rawurlencode(ASSET_REVISION))?>">
<link rel="stylesheet" href="<?=e(app_url('/assets/css/blog-widgets.css?v='.rawurlencode(ASSET_REVISION)))?>">
<?=\Kovcheg\Hooks::fire('blog.layout.head', '')?>
</head>
<body class="blog-theme blog-theme-portal">
<a class="skip-link" href="#main-content">Перейти к содержанию</a>
<header class="portal-header">
 <?php if($zones['header.top']!==''):?><div class="portal-header__top"><?=$zones['header.top']?></div><?php endif;?>
 <div class="portal-header__main">
  <?php if($zones['header.main']!==''):?><?=$zones['header.main']?><?php else:?><div class="portal-brand-fallback"><a href="<?=e(app_url('/'))?>" aria-label="<?=e($siteName)?>"><img src="<?=e($logo)?>" alt=""><span><b><?=e($siteName)?></b><small><?=e(setting('blog_tagline','Новости · мнения · проекты'))?></small></span></a></div><?php endif;?>
 </div>
 <?php if($zones['header.bottom']!==''):?><div class="portal-header__bottom"><?=$zones['header.bottom']?></div><?php endif;?>
</header>
<?php if($flash):?><div class="flash-stack" aria-live="polite"><?php foreach($flash as $message):?><div class="flash flash--<?=e($message['type'])?>"><?=e($message['text'])?></div><?php endforeach;?></div><?php endif;?>
<?php if($zones['page.before']!==''):?><div class="portal-page-zone portal-page-zone--before"><?=$zones['page.before']?></div><?php endif;?>
<div class="portal-grid <?=$gridClass?>">
 <?php if($hasLeft):?><aside class="portal-sidebar portal-sidebar--left" aria-label="Левая колонка"><?=$zones['layout.left']?></aside><?php endif;?>
 <main id="main-content" class="portal-content"><?=$zones['content.before']?><?=$content?><?=$zones['content.after']?></main>
 <?php if($hasRight):?><aside class="portal-sidebar portal-sidebar--right" aria-label="Правая колонка"><?=$zones['layout.right']?></aside><?php endif;?>
</div>
<?php if($zones['page.after']!==''):?><div class="portal-page-zone portal-page-zone--after"><?=$zones['page.after']?></div><?php endif;?>
<footer class="portal-footer">
 <?php if($zones['footer.top']!==''):?><div class="portal-footer__top"><?=$zones['footer.top']?></div><?php endif;?>
 <?php if($zones['footer.columns']!==''):?><div class="portal-footer__columns"><?=$zones['footer.columns']?></div><?php else:?><div class="portal-footer__fallback"><div><b><?=e($siteName)?></b><p><?=e(setting('blog_footer_text','Информационный сайт на KOVCHEG Blog.'))?></p></div><div><span>KOVCHEG Blog <?=e(APP_VERSION)?></span><span>Все права защищены</span></div></div><?php endif;?>
 <?php if($zones['footer.bottom']!==''):?><div class="portal-footer__bottom"><?=$zones['footer.bottom']?></div><?php endif;?>
 <div class="portal-footer__copyright"><span><?=e($copyright)?></span><span>Автор и правообладатель · KOVCHEG Blog · Все права защищены</span><?php if(setting('seo_rss_enabled','1')==='1'):?><a href="<?=e(app_url('/feed.xml'))?>">RSS</a><?php endif;?></div>
</footer>
<script src="<?=e(app_url('/assets/js/blog-widgets.js?v='.rawurlencode(ASSET_REVISION)))?>" defer></script>
<?=\Kovcheg\Hooks::fire('blog.layout.scripts','')?>
</body>
</html>