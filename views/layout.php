<?php
use Kovcheg\Auth;
use Kovcheg\Csrf;

$configuredSiteName=trim((string)setting('site_name',''));
$siteName=in_array($configuredSiteName,['','KOVCHEG CMS','KOVCHEG Core','KOVCHEG Blog Core'],true)?'KOVCHEG Blog':$configuredSiteName;
$assetRevision=rawurlencode(ASSET_REVISION);
$canonical=current_absolute_url();
$description=(string)setting('seo_description','KOVCHEG Blog — записи, рубрики и страницы.');
$keywords=(string)setting('seo_keywords','KOVCHEG Blog, KOVCHEG CMS, блог, CMS');
$indexing=setting('search_indexing','0')==='1';
$favicon=(string)setting('favicon_path','')!==''
    ? app_url('/brand/favicon?v='.rawurlencode(APP_VERSION))
    : app_url('/assets/icons/icon.svg?v='.rawurlencode(APP_VERSION));
$flash=[];
if(!empty($_SESSION['flash_error'])){$flash[]=['type'=>'error','text'=>(string)$_SESSION['flash_error']];unset($_SESSION['flash_error']);}
if(!empty($_SESSION['flash_success'])){$flash[]=['type'=>'success','text'=>(string)$_SESSION['flash_success']];unset($_SESSION['flash_success']);}
$currentUser=Auth::user()??[];
?><!doctype html>
<html lang="ru">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<meta name="csrf-token" content="<?=e(Csrf::token())?>">
<meta name="theme-color" content="<?=e((string)setting('brand_accent','#2563eb'))?>">
<meta name="description" content="<?=e($description)?>">
<meta name="keywords" content="<?=e($keywords)?>">
<meta name="robots" content="<?=(!$indexing||Auth::check())?'noindex,nofollow,noarchive':'index,follow,max-image-preview:large'?>">
<link rel="canonical" href="<?=e($canonical)?>">
<meta property="og:site_name" content="<?=e($siteName)?>">
<meta property="og:title" content="<?=e($title??$siteName)?>">
<meta property="og:description" content="<?=e($description)?>">
<meta property="og:url" content="<?=e($canonical)?>">
<meta property="og:type" content="website">
<title><?=e($title??$siteName)?> — <?=e($siteName)?></title>
<link rel="icon" href="<?=e($favicon)?>">
<link rel="stylesheet" href="<?=e(app_url('/assets/css/kovcheg-shell.css?v='.$assetRevision))?>">
<?=\Kovcheg\Hooks::fire('layout.head','')?>
</head>
<body class="<?=Auth::check()?'auth-shell':'guest-shell'?>">
<header class="kov-shell-header">
 <div class="kov-shell-header__inner">
  <a class="kov-shell-brand" href="<?=e(app_url('/'))?>">
   <img src="<?=e(app_url('/brand/logo?v='.rawurlencode(APP_VERSION)))?>" alt="">
   <span><b><?=e($siteName)?></b><small>KOVCHEG CMS <?=e(APP_VERSION)?></small></span>
  </a>
  <nav class="kov-shell-nav" aria-label="Навигация">
   <a href="<?=e(app_url('/'))?>">Сайт</a>
   <?php if(Auth::check()):?>
    <a href="<?=e(app_url('/account'))?>">Кабинет</a>
    <?php if(Auth::isAdmin()):?><a class="primary" href="<?=e(app_url('/studio'))?>">Studio</a><?php endif;?>
    <form method="post" action="<?=e(app_url('/logout'))?>"><?=csrf_field()?><button type="submit">Выйти</button></form>
   <?php endif;?>
  </nav>
 </div>
</header>
<?php if($flash):?>
 <div aria-live="polite">
  <?php foreach($flash as $message):?><div class="kov-shell-flash kov-shell-flash--<?=e($message['type'])?>"><?=e($message['text'])?></div><?php endforeach;?>
 </div>
<?php endif;?>
<main class="kov-shell-main" id="kovcheg-page-content"><?=$content??''?></main>
<footer class="kov-shell-footer"><span>© <?=date('Y')?> Ланцет Семён Борисович</span><span>KOVCHEG Blog <?=e(APP_VERSION)?> · Все права защищены</span></footer>
<?=\Kovcheg\Hooks::fire('layout.scripts','')?>
</body>
</html>