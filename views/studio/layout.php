<?php
$flash=[];
if(!empty($_SESSION['flash_error'])){$flash[]=['type'=>'error','text'=>(string)$_SESSION['flash_error']];unset($_SESSION['flash_error']);}
if(!empty($_SESSION['flash_success'])){$flash[]=['type'=>'success','text'=>(string)$_SESSION['flash_success']];unset($_SESSION['flash_success']);}

$nav=[
 'dashboard'=>['Обзор','/studio','⌂','comments'],
 'posts'=>['Записи','/studio/posts','✎','content'],
 'categories'=>['Рубрики','/studio/categories','≡','content'],
 'pages'=>['Страницы','/studio/pages','▤','content'],
 'catalog'=>['Товары','/studio/catalog','▣','content'],
 'livestock'=>['Поголовье','/studio/livestock','♞','content'],
 'projects'=>['Проекты','/studio/projects','⌂','content'],
 'comments'=>['Комментарии','/studio/comments','◌','comments'],
 'media'=>['Медиафайлы','/studio/media','▧','media'],
 'menus'=>['Меню','/studio/menus','☷','menus'],
 'widgets'=>['Виджеты и зоны','/studio/widgets','▦','site'],
 'appearance'=>['Брендинг и тема','/studio/appearance','◇','themes'],
 'users'=>['Пользователи','/studio/users','◎','site'],
 'settings'=>['Настройки','/studio/settings','⚙','settings'],
];

$copyright='© '.date('Y').' Ланцет Семён Борисович';
?><!doctype html>
<html lang="ru" class="studio-document">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<meta name="robots" content="noindex,nofollow,noarchive">
<meta name="csrf-token" content="<?=e(\Kovcheg\Csrf::token())?>">
<title><?=e($studioTitle)?> — KOVCHEG Studio</title>
<link rel="icon" href="<?=e(setting('favicon_path','')!==''?app_url('/brand/favicon?v='.rawurlencode(ASSET_REVISION)):app_url('/assets/icons/icon.svg?v='.rawurlencode(ASSET_REVISION)))?>">
<link rel="stylesheet" href="<?=e(app_url('/assets/css/blog-studio.css?v='.rawurlencode(ASSET_REVISION)))?>">
<link rel="stylesheet" href="<?=e(app_url('/assets/css/blog-studio-unified.css?v='.rawurlencode(ASSET_REVISION)))?>">
<link rel="stylesheet" href="<?=e(app_url('/assets/css/blog-upload.css?v='.rawurlencode(ASSET_REVISION)))?>">
<link rel="stylesheet" href="<?=e(app_url('/assets/css/blog-classic-editor.css?v='.rawurlencode(ASSET_REVISION)))?>">
<link rel="stylesheet" href="<?=e(app_url('/assets/css/blog-studio-compact.css?v='.rawurlencode(ASSET_REVISION)))?>">
<link rel="stylesheet" href="<?=e(app_url('/assets/css/blog-studio-simple.css?v='.rawurlencode(ASSET_REVISION)))?>">
<link rel="stylesheet" href="<?=e(app_url('/assets/css/blog-studio-wordpress.css?v='.rawurlencode(ASSET_REVISION)))?>">
<link rel="stylesheet" href="<?=e(app_url('/assets/css/blog-studio-pages.css?v='.rawurlencode(ASSET_REVISION)))?>">
<link rel="stylesheet" href="<?=e(app_url('/assets/css/blog-studio-branding.css?v='.rawurlencode(ASSET_REVISION)))?>">
<link rel="stylesheet" href="<?=e(app_url('/assets/css/blog-studio-menus.css?v='.rawurlencode(ASSET_REVISION)))?>">
<link rel="stylesheet" href="<?=e(app_url('/assets/css/blog-zone-builder.css?v='.rawurlencode(ASSET_REVISION)))?>">
<link rel="stylesheet" href="<?=e(app_url('/assets/css/blog-studio-ux-3.8.1.css?v='.rawurlencode(ASSET_REVISION)))?>">
<link rel="stylesheet" href="<?=e(app_url('/assets/css/blog-studio-showcase.css?v='.rawurlencode(ASSET_REVISION)))?>">
</head>
<body class="studio-body studio-body--simple studio-body--wordpress studio-body--pages" data-studio-section="<?=e($studioSection)?>">
<div class="studio-shell">
 <aside class="studio-sidebar" id="studio-sidebar" aria-label="Навигация KOVCHEG Studio">
  <header class="studio-brand"><a href="<?=e(app_url('/studio'))?>"><img src="<?=e(app_url('/brand/logo?v='.rawurlencode(ASSET_REVISION)))?>" alt=""><div><b>KOVCHEG Studio</b><small><?=e(setting('site_name','CMS'))?> · <?=e(APP_VERSION)?></small></div></a><button type="button" data-studio-close aria-label="Закрыть меню">×</button></header>
  <nav class="studio-nav">
   <?php foreach($nav as $key=>$item):if(!\Kovcheg\Blog\Studio::can($item[3]))continue;?>
   <a class="<?=$studioSection===$key?'active':''?>" href="<?=e(app_url($item[1]))?>"><i><?=$item[2]?></i><span><?=$item[0]?></span></a>
   <?php endforeach;?>
  </nav>
  <div class="studio-sidebar-meta"><b>KOVCHEG CMS <?=e(APP_VERSION)?></b><small><?=e((string)($currentUser['display_name']??''))?> · <?=e($studioRole)?></small><small><?=e($copyright)?></small></div>
 </aside>
 <button class="studio-overlay" type="button" data-studio-overlay hidden aria-label="Закрыть меню"></button>
 <main class="studio-main" id="studio-main">
  <header class="studio-topbar">
   <button type="button" class="studio-menu-button" data-studio-open aria-label="Открыть меню">☰</button>
   <div class="studio-topbar-title"><small><?=e(setting('site_name','KOVCHEG CMS'))?></small><b><?=e($studioTitle)?></b></div>
   <div class="studio-top-actions">
    <a class="button studio-site-action" href="<?=e(app_url('/'))?>" target="_blank" rel="noopener"><span class="studio-action-icon">↗</span><span class="studio-action-label">Сайт</span></a>
    <a class="button studio-account-action" href="<?=e(app_url('/account'))?>"><span class="studio-action-icon">◉</span><span class="studio-action-label">Профиль</span></a>
    <?php if(\Kovcheg\Blog\Studio::can('content')):?><a class="button primary" href="<?=e(app_url('/studio/posts/new'))?>"><span class="studio-action-icon">＋</span><span class="studio-action-label">Запись</span></a><a class="button" href="<?=e(app_url('/studio/catalog/new'))?>"><span class="studio-action-icon">＋</span><span class="studio-action-label">Товар</span></a><?php endif;?>
    <form method="post" action="<?=e(app_url('/logout'))?>"><?=csrf_field()?><button class="button studio-logout-action" type="submit"><span class="studio-action-icon">↪</span><span class="studio-action-label">Выйти</span></button></form>
   </div>
  </header>
  <?php if($flash):?><div class="studio-flashes"><?php foreach($flash as $message):?><div class="studio-flash <?=$message['type']==='error'?'error':'success'?>"><?=e($message['text'])?></div><?php endforeach;?></div><?php endif;?>
  <section class="studio-content"><?=$content?></section>
  <footer class="studio-footer"><div><b>KOVCHEG CMS</b><span>Контент, товары, поголовье, проекты, меню и управляемые виджеты.</span></div><div><span><?=e($copyright)?></span><span>Все права защищены</span></div></footer>
 </main>
</div>
<script src="<?=e(app_url('/assets/js/blog-studio.js?v='.rawurlencode(ASSET_REVISION)))?>" defer></script>
<script src="<?=e(app_url('/assets/js/blog-classic-editor.js?v='.rawurlencode(ASSET_REVISION)))?>" defer></script>
<script src="<?=e(app_url('/assets/js/blog-upload.js?v='.rawurlencode(ASSET_REVISION)))?>" defer></script>
</body>
</html>
