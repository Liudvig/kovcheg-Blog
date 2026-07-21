<?php
$profileUser=$profileUser??[];
$profileId=(int)($profileId??0);
$wallPosts=$wallPosts??[];
$avatarSrc=$avatarSrc??(!empty($profileUser['avatar_path'])?avatar_url($profileId,(string)$profileUser['avatar_path']):app_url('/assets/icons/default-avatar.svg?v='.ASSET_REVISION));
$firstName=(string)($profileUser['first_name']??$profileUser['display_name']??'пользователя');
?>
<section id="profile-wall" class="kov-vk-profile-media vk-reference-wall" data-kov-vk-media style="position:relative;--vk-composer-avatar:url('<?=e($avatarSrc)?>')">
 <div class="kov-vk-media-panels">
  <section class="kov-vk-media-panel kov-vk-media-panel-wall">
   <?=\Kovcheg\View::partial('wall-composer',['current'=>$profileUser,'action'=>app_url('/profile/'.$profileId.'/wall'),'placeholder'=>'Написать сообщение…','composerContext'=>'profile','classicVk'=>true])?>
   <nav class="vk-reference-wall-tabs" aria-label="Фильтр записей"><button type="button" class="active">Все записи</button><button type="button">Записи <?=e($firstName)?></button><button type="button" class="vk-reference-wall-search" data-wall-post-search-toggle aria-label="Поиск по записям">⌕</button></nav>
   <div data-wall-feed id="profile-posts" class="vk-reference-post-feed"><?php foreach($wallPosts as $post)echo \Kovcheg\View::partial('feed-post',['post'=>$post]);?><?php if(!$wallPosts):?><div class="vk-card kov-vk-old-empty">На стене пока нет записей.</div><?php endif;?></div>
  </section>
 </div>
</section>
