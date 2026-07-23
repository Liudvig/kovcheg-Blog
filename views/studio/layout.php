<?php
$flash=[];
if(!empty($_SESSION['flash_error'])){$flash[]=['type'=>'error','text'=>(string)$_SESSION['flash_error']];unset($_SESSION['flash_error']);}
if(!empty($_SESSION['flash_success'])){$flash[]=['type'=>'success','text'=>(string)$_SESSION['flash_success']];unset($_SESSION['flash_success']);}
$nav=[
 'dashboard'=>['Обзор','/studio','⌂','comments'],
 'content'=>['Материалы','/studio/content','✎','content'],
 'patterns'=>['Конструктор','/studio/patterns','⊞','content'],
 'categories'=>['Рубрики','/studio/categories','≡','content'],
 'comments'=>['Комментарии','/studio/comments','◌','comments'],
 'media'=>['Медиатека','/studio/media','▧','media'],
 'menus'=>['Меню','/studio/menus','☷','menus'],
 'widgets'=>['Виджеты и зоны','/studio/widgets','▦','site'],
 'appearance'=>['Темы','/studio/appearance','◇','themes'],
 'presets'=>['Пресеты','/studio/presets','✦','site'],
 'growth'=>['SEO и рост','/studio/growth','↗','site'],
 'users'=>['Пользователи','/studio/users','◎','site'],
 'modules'=>['Модули','/studio/modules','⬡','site'],
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
<link rel="stylesheet" href="<?=e(app_url('/assets/css/blog-studio.css?v='.rawurlencode(ASSET_REVISION)))?>">
<link rel="stylesheet" href="<?=e(app_url('/assets/css/blog-builder.css?v='.rawurlencode(ASSET_REVISION)))?>">
<link rel="stylesheet" href="<?=e(app_url('/assets/css/blog-widgets.css?v='.rawurlencode(ASSET_REVISION)))?>">
<link rel="stylesheet" href="<?=e(app_url('/assets/css/blog-studio-unified.css?v='.rawurlencode(ASSET_REVISION)))?>">
<link rel="stylesheet" href="<?=e(app_url('/assets/css/blog-zone-builder.css?v='.rawurlencode(ASSET_REVISION)))?>">
<link rel="stylesheet" href="<?=e(app_url('/assets/css/blog-upload.css?v='.rawurlencode(ASSET_REVISION)))?>">
</head>
<body class="studio-body" data-studio-section="<?=e($studioSection)?>">
<div class="studio-shell">
 <aside class="studio-sidebar" id="studio-sidebar" aria-label="Навигация KOVCHEG Studio">
  <header class="studio-brand"><a href="<?=e(app_url('/studio'))?>"><span>K</span><div><b>KOVCHEG Studio</b><small>Blog <?=e(APP_VERSION)?></small></div></a><button type="button" data-studio-close aria-label="Закрыть меню">×</button></header>
  <nav class="studio-nav">
   <?php foreach($nav as $key=>$item):if(!\Kovcheg\Blog\Studio::can($item[3]))continue;?>
   <a class="<?=$studioSection===$key?'active':''?>" href="<?=e(app_url($item[1]))?>"><i><?=$item[2]?></i><span><?=$item[0]?></span></a>
   <?php endforeach;?>
  </nav>
  <div class="studio-sidebar-meta">
   <b>KOVCHEG Blog <?=e(APP_VERSION)?></b>
   <small><?=e((string)($currentUser['display_name']??''))?> · <?=e($studioRole)?></small>
   <small><?=e($copyright)?></small>
  </div>
 </aside>
 <button class="studio-overlay" type="button" data-studio-overlay hidden aria-label="Закрыть меню"></button>
 <main class="studio-main" id="studio-main">
  <header class="studio-topbar">
   <button type="button" class="studio-menu-button" data-studio-open aria-label="Открыть меню">☰</button>
   <div class="studio-topbar-title"><small>KOVCHEG Blog</small><b><?=e($studioTitle)?></b></div>
   <div class="studio-top-actions">
    <a class="button studio-site-action" href="<?=e(app_url('/'))?>" target="_blank" rel="noopener"><span class="studio-action-icon">↗</span><span class="studio-action-label">Перейти на сайт</span></a>
    <a class="button studio-account-action" href="<?=e(app_url('/account'))?>"><span class="studio-action-icon">◉</span><span class="studio-action-label">Личный кабинет</span></a>
    <?php if(\Kovcheg\Blog\Studio::can('content')):?><a class="button primary" href="<?=e(app_url('/studio/content/new'))?>"><span class="studio-action-icon">＋</span><span class="studio-action-label">Новый материал</span></a><?php endif;?>
    <form method="post" action="<?=e(app_url('/logout'))?>"><?=csrf_field()?><button class="button studio-logout-action" type="submit"><span class="studio-action-icon">↪</span><span class="studio-action-label">Выйти</span></button></form>
   </div>
  </header>
  <?php if($flash):?><div class="studio-flashes"><?php foreach($flash as $message):?><div class="studio-flash <?=$message['type']==='error'?'error':'success'?>"><?=e($message['text'])?></div><?php endforeach;?></div><?php endif;?>
  <section class="studio-content"><?=$content?></section>
  <footer class="studio-footer">
   <div><b>KOVCHEG Blog</b><span>Система управления блогом, портфолио и информационным порталом.</span></div>
   <div><span><?=e($copyright)?></span><span>Автор и правообладатель · Все права защищены</span></div>
  </footer>
 </main>
</div>
<script src="<?=e(app_url('/assets/js/blog-studio.js?v='.rawurlencode(ASSET_REVISION)))?>" defer></script>
<script src="<?=e(app_url('/assets/js/blog-widgets.js?v='.rawurlencode(ASSET_REVISION)))?>" defer></script>
<script src="<?=e(app_url('/assets/js/blog-upload.js?v='.rawurlencode(ASSET_REVISION)))?>" defer></script>
</body>
</html>