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
 'appearance'=>['Темы','/studio/appearance','◇','themes'],
 'presets'=>['Пресеты','/studio/presets','✦','site'],
 'users'=>['Пользователи','/studio/users','◎','site'],
 'modules'=>['Модули','/studio/modules','⬡','site'],
 'settings'=>['Настройки','/studio/settings','⚙','settings'],
];
?><!doctype html>
<html lang="ru">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<meta name="robots" content="noindex,nofollow,noarchive">
<meta name="csrf-token" content="<?=e(\Kovcheg\Csrf::token())?>">
<title><?=e($studioTitle)?> — KOVCHEG Studio</title>
<link rel="stylesheet" href="<?=e(app_url('/assets/css/blog-studio.css?v='.rawurlencode(ASSET_REVISION)))?>">
<link rel="stylesheet" href="<?=e(app_url('/assets/css/blog-builder.css?v='.rawurlencode(ASSET_REVISION)))?>">
</head>
<body class="studio-body" data-studio-section="<?=e($studioSection)?>">
<div class="studio-shell">
 <aside class="studio-sidebar" id="studio-sidebar">
  <header class="studio-brand"><a href="<?=e(app_url('/studio'))?>"><span>K</span><div><b>KOVCHEG Studio</b><small>Visual Builder 3.2</small></div></a><button type="button" data-studio-close aria-label="Закрыть">×</button></header>
  <nav class="studio-nav">
   <?php foreach($nav as $key=>$item):if(!\Kovcheg\Blog\Studio::can($item[3]))continue;?>
   <a class="<?=$studioSection===$key?'active':''?>" href="<?=e(app_url($item[1]))?>"><i><?=$item[2]?></i><span><?=$item[0]?></span></a>
   <?php endforeach;?>
  </nav>
  <footer class="studio-sidebar-footer"><a href="<?=e(app_url('/'))?>">↗ Открыть сайт</a><a href="<?=e(app_url('/profile'))?>">Профиль</a><small><?=e((string)($currentUser['display_name']??''))?> · <?=e($studioRole)?> · <?=e(APP_VERSION)?></small></footer>
 </aside>
 <button class="studio-overlay" type="button" data-studio-overlay hidden></button>
 <main class="studio-main">
  <header class="studio-topbar"><button type="button" class="studio-menu-button" data-studio-open>☰</button><div><small>KOVCHEG Blog</small><b><?=e($studioTitle)?></b></div><div class="studio-top-actions"><?php if(\Kovcheg\Blog\Studio::can('content')):?><a class="button primary" href="<?=e(app_url('/studio/content/new'))?>">+ Новый материал</a><?php endif;?></div></header>
  <?php if($flash):?><div class="studio-flashes"><?php foreach($flash as $message):?><div class="studio-flash <?=$message['type']==='error'?'error':'success'?>"><?=e($message['text'])?></div><?php endforeach;?></div><?php endif;?>
  <section class="studio-content"><?=$content?></section>
 </main>
</div>
<script src="<?=e(app_url('/assets/js/blog-studio.js?v='.rawurlencode(ASSET_REVISION)))?>" defer></script>
</body>
</html>
