<?php
use Kovcheg\Auth;
use Kovcheg\Blog\SiteManager;
use Kovcheg\Blog\Studio;

$meta=SiteManager::meta(['title'=>$title??'','description'=>$description??'','entry'=>$entry??null]);
$pageTitle=(string)$meta['rawTitle'];
$siteName=(string)($siteName??setting('site_name','KOVCHEG Blog'));
$canonical=(string)$meta['canonical'];
if(str_starts_with($canonical,'/')){
 $parts=parse_url(current_absolute_url())?:[];$scheme=(string)($parts['scheme']??'https');$host=(string)($parts['host']??($_SERVER['HTTP_HOST']??'localhost'));$port=isset($parts['port'])?':'.(int)$parts['port']:'';$canonical=$scheme.'://'.$host.$port.$canonical;$meta['canonical']=$canonical;
}
if($meta['image']===''){$defaultImage=trim((string)setting('seo_default_image',''));if(filter_var($defaultImage,FILTER_VALIDATE_URL))$meta['image']=$defaultImage;}
$schemaData=json_decode((string)$meta['jsonLd'],true);if(!is_array($schemaData))$schemaData=[];$schemaData['url']=$canonical;if($meta['image']!=='')$schemaData['image']=$meta['image'];
$safeJsonLd=json_encode($schemaData,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT);
$logo=app_url('/brand/logo?v='.rawurlencode(APP_VERSION));
$favicon=app_url('/brand/favicon?v='.rawurlencode(APP_VERSION));
$unreadNotifications=Auth::check()?SiteManager::unreadCount(Auth::id()):0;
$flash=[];
if(!empty($_SESSION['flash_error'])){$flash[]=['type'=>'error','text'=>(string)$_SESSION['flash_error']];unset($_SESSION['flash_error']);}
if(!empty($_SESSION['flash_success'])){$flash[]=['type'=>'success','text'=>(string)$_SESSION['flash_success']];unset($_SESSION['flash_success']);}
?><!doctype html>
<html lang="ru">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<meta name="theme-color" content="#f4f3ef">
<meta name="description" content="<?=e($meta['description'])?>">
<meta name="robots" content="<?=e($meta['robots'])?>">
<link rel="canonical" href="<?=e($canonical)?>">
<?php if(setting('seo_feed_enabled','1')==='1'):?><link rel="alternate" type="application/rss+xml" title="<?=e($siteName)?> RSS" href="<?=e(app_url('/feed.xml'))?>"><?php endif;?>
<?php if(setting('seo_google_verification','')!==''):?><meta name="google-site-verification" content="<?=e(setting('seo_google_verification',''))?>"><?php endif;?>
<?php if(setting('seo_yandex_verification','')!==''):?><meta name="yandex-verification" content="<?=e(setting('seo_yandex_verification',''))?>"><?php endif;?>
<meta property="og:site_name" content="<?=e($siteName)?>">
<meta property="og:title" content="<?=e($meta['title'])?>">
<meta property="og:description" content="<?=e($meta['description'])?>">
<meta property="og:url" content="<?=e($canonical)?>">
<meta property="og:type" content="<?=e($meta['ogType'])?>">
<?php if($meta['image']!==''):?><meta property="og:image" content="<?=e($meta['image'])?>"><?php endif;?>
<meta name="twitter:card" content="<?=$meta['image']!==''?'summary_large_image':'summary'?>">
<meta name="twitter:title" content="<?=e($meta['title'])?>">
<meta name="twitter:description" content="<?=e($meta['description'])?>">
<?php if($meta['image']!==''):?><meta name="twitter:image" content="<?=e($meta['image'])?>"><?php endif;?>
<title><?=e($meta['title'])?></title>
<link rel="icon" href="<?=e($favicon)?>">
<link rel="stylesheet" href="<?=e($themeAsset('theme.css').'?v='.rawurlencode(ASSET_REVISION))?>">
<link rel="stylesheet" href="<?=e($themeAsset('content.css').'?v='.rawurlencode(ASSET_REVISION))?>">
<link rel="stylesheet" href="<?=e(app_url('/assets/css/blog-public-33.css?v='.rawurlencode(ASSET_REVISION)))?>">
<script type="application/ld+json" nonce="<?=e((string)($GLOBALS['CSP_NONCE']??''))?>"><?=$safeJsonLd?></script>
<?=\Kovcheg\Hooks::fire('blog.layout.head','')?>
</head>
<body class="blog-theme blog-theme-editorial">
<a class="skip-link" href="#main-content">Перейти к содержанию</a>
<header class="site-header">
 <div class="site-header__inner">
  <a class="site-brand" href="<?=e(app_url('/'))?>" aria-label="<?=e($siteName)?>"><img src="<?=e($logo)?>" alt="" class="site-brand__logo"><span class="site-brand__text"><b><?=e($siteName)?></b><small><?=e(setting('blog_tagline','Разработки · проекты · опыт'))?></small></span></a>
  <button class="site-menu-button" type="button" aria-expanded="false" aria-controls="site-navigation" data-site-menu-button>Меню</button>
  <nav class="site-navigation" id="site-navigation" aria-label="Главное меню" data-site-navigation>
   <?php foreach($menuItems as $item):$itemUrl=trim((string)($item['url']??'/'));if($itemUrl==='')$itemUrl='/';if(!preg_match('~^(?:https?:)?//~i',$itemUrl))$itemUrl=app_url('/'.ltrim($itemUrl,'/'));?><a href="<?=e($itemUrl)?>"><?=e((string)($item['label']??'Раздел'))?></a><?php endforeach;?>
  </nav>
  <div class="site-account">
   <?php if(Auth::check()):?>
    <a class="notification-link <?=$unreadNotifications?'has-unread':''?>" href="<?=e(app_url('/notifications'))?>" aria-label="Уведомления"><span>◉</span><?php if($unreadNotifications):?><b><?=$unreadNotifications>99?'99+':$unreadNotifications?></b><?php endif;?></a>
    <a class="site-account__profile" href="<?=e(app_url('/profile'))?>"><?=avatar_html($currentUser,'avatar-xs')?> <span><?=e((string)($currentUser['display_name']??'Профиль'))?></span></a>
    <?php if(Studio::can('comments')):?><a class="button button--quiet" href="<?=e(app_url('/studio'))?>">Studio</a><?php endif;?>
   <?php else:?><a href="<?=e(app_url('/login'))?>">Войти</a><a class="button button--dark" href="<?=e(app_url('/register'))?>">Регистрация</a><?php endif;?>
  </div>
 </div>
</header>
<?php if($flash):?><div class="flash-stack" aria-live="polite"><?php foreach($flash as $message):?><div class="flash flash--<?=e($message['type'])?>"><?=e($message['text'])?></div><?php endforeach;?></div><?php endif;?>
<main id="main-content" class="site-main"><?=$content?></main>
<footer class="site-footer"><div class="site-footer__inner"><div><b><?=e($siteName)?></b><p><?=e(setting('blog_footer_text','Авторский сайт, созданный на KOVCHEG Blog.'))?></p></div><div class="site-footer__meta"><span><?=e(setting('copyright','© '.date('Y').' KOVCHEG CMS'))?></span><span>Автор системы: Ланцет Семён Борисович</span><?php if(setting('seo_feed_enabled','1')==='1'):?><a href="<?=e(app_url('/feed.xml'))?>">RSS</a><?php endif;?></div></div></footer>
<script nonce="<?=e((string)($GLOBALS['CSP_NONCE']??''))?>">(()=>{const button=document.querySelector('[data-site-menu-button]');const nav=document.querySelector('[data-site-navigation]');if(!button||!nav)return;button.addEventListener('click',()=>{const open=button.getAttribute('aria-expanded')==='true';button.setAttribute('aria-expanded',open?'false':'true');nav.classList.toggle('is-open',!open);});})();</script>
<?=\Kovcheg\Hooks::fire('blog.layout.scripts','')?>
</body>
</html>
