<?php
require_once BASE_PATH.'/app/profile-banner.php';
$profileUser=$user??\Kovcheg\Auth::user()??[];
$profileId=(int)($profileUser['id']??0);
$counts=$counts??profile_counts($profileId);
$profileTab=in_array((string)($_GET['tab']??($profileTab??'posts')),['posts','media'],true)?(string)($_GET['tab']??($profileTab??'posts')):'posts';
if($profileTab==='media')$wallPosts=array_values(array_filter((array)($wallPosts??[]),fn($post)=>count(array_filter((array)($post['attachments']??[]),fn($file)=>str_starts_with((string)($file['mime_type']??''),'image/')||str_starts_with((string)($file['mime_type']??''),'video/')))>0));
$bannerUrl=profile_banner_url($profileId);
$bannerPosition=profile_banner_position($profileId);
?>
<main class="x-layout">
 <?=\Kovcheg\View::partial('site-sidebar',['active'=>'profile'])?>
 <section class="x-main">
  <header class="x-title"><div><h1><?=e($profileUser['display_name']??'Профиль')?></h1><small><?=count($wallPosts??[])?> публикаций</small></div></header>
  <div class="x-profile-cover <?=$bannerUrl!==''?'has-image':''?>" style="--x-banner-position:<?=$bannerPosition?>%;<?php if($bannerUrl!==''):?>background-image:url('<?=e($bannerUrl)?>');<?php endif;?>">
   <a class="x-cover-action" href="<?=e(app_url('/settings/general'))?>"><?=$bannerUrl!==''?'Изменить баннер':'Добавить баннер'?></a>
  </div>
  <section class="x-profile-info">
   <div class="x-profile-avatar"><?=avatar_html($profileUser,'profile-avatar')?></div>
   <?php if($profileId===\Kovcheg\Auth::id()):?><a class="x-button x-edit" href="<?=e(app_url('/settings/general'))?>">Изменить профиль</a><?php endif;?>
   <h1><?=e($profileUser['display_name']??'Пользователь')?><?=verified_badge($profileUser)?></h1>
   <small>@<?=e($profileUser['username']??'')?></small>
   <?php if(!empty($profileUser['bio'])):?><p><?=nl2br(e($profileUser['bio']))?></p><?php endif;?>
   <div class="x-profile-stats"><a href="<?=e(app_url('/colleagues?tab=following'))?>"><b><?=e($counts['following']??0)?></b> <span>в читаемых</span></a><a href="<?=e(app_url('/colleagues?tab=followers'))?>"><b><?=e($counts['followers']??0)?></b> <span>читателей</span></a></div>
  </section>
  <nav class="x-tabs"><a class="<?=$profileTab==='posts'?'active':''?>" href="<?=e(app_url('/profile?tab=posts#profile-posts'))?>">Публикации</a><a class="<?=$profileTab==='media'?'active':''?>" href="<?=e(app_url('/profile?tab=media#profile-posts'))?>">Медиа</a><a href="<?=e(app_url('/colleagues'))?>">Сообщество</a></nav>
  <?php if($profileTab==='posts'):?><?=\Kovcheg\View::partial('wall-composer',['current'=>$profileUser,'action'=>app_url('/profile/'.$profileId.'/wall'),'placeholder'=>'Что происходит?','composerContext'=>'profile'])?><?php endif;?>
  <div data-wall-feed id="profile-posts"><?php foreach(($wallPosts??[]) as $post)echo \Kovcheg\View::partial('feed-post',['post'=>$post]);?><?php if(empty($wallPosts)):?><div class="x-post"><?=$profileTab==='media'?'Медиафайлов пока нет.':'Публикаций пока нет.'?></div><?php endif;?></div>
 </section>
 <aside class="x-right"><label class="x-search"><span>⌕</span><input type="search" data-top-global-search placeholder="Поиск"><div class="top-global-results" data-top-global-results hidden></div></label><?=\Kovcheg\View::partial('weather-widget',['weatherUserId'=>$profileId])?><section class="x-box"><h2>Вам может понравиться</h2><a href="<?=e(app_url('/colleagues'))?>">Найти людей</a></section></aside>
</main>