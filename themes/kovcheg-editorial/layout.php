<?php
use Kovcheg\Blog\Layout;
use Kovcheg\Blog\ThemeSupport;

$pageTitle=trim((string)($title??''));
$siteSeoTitle=trim((string)setting('seo_site_title',''))?:$siteName;
$metaDescription=trim((string)($description??setting('seo_default_description',setting('seo_description','Информация, записи и страницы сайта.'))));
$canonical=current_absolute_url();
$indexing=setting('seo_robots_index',setting('search_indexing','0'))==='1';
$logo=app_url('/brand/logo?v='.rawurlencode(APP_VERSION));
$favicon=setting('favicon_path','')!==''?app_url('/brand/favicon?v='.rawurlencode(APP_VERSION)):app_url('/assets/icons/icon.svg?v='.rawurlencode(APP_VERSION));
$layoutContext=is_array($layoutContext??null)?$layoutContext:['page_type'=>'default'];

$headerTop=Layout::renderZone('header.top',$layoutContext);
$headerMain=Layout::renderZone('header.main',$layoutContext);
$headerBottom=Layout::renderZone('header.bottom',$layoutContext);
$pageBefore=Layout::renderZone('page.before',$layoutContext);
$leftZone=Layout::renderZone('layout.left',$layoutContext);
$contentBefore=Layout::renderZone('content.before',$layoutContext);
$contentAfter=Layout::renderZone('content.after',$layoutContext);
$rightZone=Layout::renderZone('layout.right',$layoutContext);
$pageAfter=Layout::renderZone('page.after',$layoutContext);
$footerTop=Layout::renderZone('footer.top',$layoutContext);
$footerColumns=Layout::renderZone('footer.columns',$layoutContext);
$footerBottom=Layout::renderZone('footer.bottom',$layoutContext);

if($leftZone==='')$leftZone=ThemeSupport::menuHtml('left','vertical','Левое меню');
if($rightZone==='')$rightZone=ThemeSupport::menuHtml('right','vertical','Правое меню');
$headerMenu=ThemeSupport::menuHtml('header','horizontal','Главное меню');
$accountHtml=ThemeSupport::accountHtml();
$footerMenu=ThemeSupport::menuHtml('footer','horizontal','Меню в подвале');

$copyright='© '.date('Y').' Ланцет Семён Борисович';
$flash=[];
if(!empty($_SESSION['flash_error'])){$flash[]=['type'=>'error','text'=>(string)$_SESSION['flash_error']];unset($_SESSION['flash_error']);}
if(!empty($_SESSION['flash_success'])){$flash[]=['type'=>'success','text'=>(string)$_SESSION['flash_success']];unset($_SESSION['flash_success']);}
$pageType=(string)($layoutContext['page_type']??'default');
$documentClass=in_array($pageType,['entry','post','page'],true)?' blog-theme-document':'';
?><!doctype html>
<html lang="ru">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<meta name="theme-color" content="<?=e((string)setting('brand_accent','#2563eb'))?>">
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
<link rel="stylesheet" href="<?=e(app_url('/assets/css/blog-widgets.css?v='.rawurlencode(ASSET_REVISION)))?>">
<link rel="stylesheet" href="<?=e(app_url('/assets/css/blog-copyright.css?v='.rawurlencode(ASSET_REVISION)))?>">
<?=\Kovcheg\Hooks::fire('blog.layout.head','')?>
</head>
<body class="blog-theme blog-theme-editorial<?=e($documentClass)?>" style="--brand-accent:<?=e((string)setting('brand_accent','#2563eb'))?>">
<a class="skip-link" href="#main-content">Перейти к содержанию</a>
<header class="site-header">
 <?=$headerTop?>
 <?php if($headerMain!==''):?>
  <?=$headerMain?>
 <?php else:?>
  <div class="site-header__inner">
   <a class="site-brand" href="<?=e(app_url('/'))?>" aria-label="<?=e($siteName)?>"><img src="<?=e($logo)?>" alt="" class="site-brand__logo"><span class="site-brand__text"><b><?=e($siteName)?></b><small><?=e(setting('blog_tagline','Сайт на KOVCHEG CMS'))?></small></span></a>
   <?=$headerMenu?>
   <?=$accountHtml?>
  </div>
 <?php endif;?>
 <?=$headerBottom?>
</header>

<?php if($flash):?><div class="flash-stack" aria-live="polite"><?php foreach($flash as $message):?><div class="flash flash--<?=e($message['type'])?>"><?=e($message['text'])?></div><?php endforeach;?></div><?php endif;?>

<?=$pageBefore?>
<div class="site-layout-grid <?=$leftZone!==''?'has-left':''?> <?=$rightZone!==''?'has-right':''?>">
 <?php if($leftZone!==''):?><aside class="site-layout-grid__left" aria-label="Левая колонка"><?=$leftZone?></aside><?php endif;?>
 <main id="main-content" class="site-main site-layout-grid__main"><?=$contentBefore?><?=$content?><?=$contentAfter?></main>
 <?php if($rightZone!==''):?><aside class="site-layout-grid__right" aria-label="Правая колонка"><?=$rightZone?></aside><?php endif;?>
</div>
<?=$pageAfter?>

<footer class="site-footer">
 <?=$footerTop?>
 <?php if($footerColumns!==''):?>
  <?=$footerColumns?>
 <?php else:?>
  <div class="site-footer__inner"><div><b><?=e($siteName)?></b><p><?=e(setting('blog_footer_text','Сайт работает на KOVCHEG CMS.'))?></p><?=$footerMenu?></div></div>
 <?php endif;?>
 <?=$footerBottom?>
 <div class="site-footer__copyright"><span><?=e($copyright)?></span><span>Автор и правообладатель · KOVCHEG CMS <?=e(APP_VERSION)?> · Все права защищены</span><?php if(setting('seo_rss_enabled','1')==='1'):?><a href="<?=e(app_url('/feed.xml'))?>">RSS</a><?php endif;?></div>
</footer>
<script src="<?=e(app_url('/assets/js/blog-widgets.js?v='.rawurlencode(ASSET_REVISION)))?>" defer></script>
<?=\Kovcheg\Hooks::fire('blog.layout.scripts','')?>
</body>
</html>
