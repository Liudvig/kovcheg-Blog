<?php
$isSelf=(int)$profileUser['id']===\Kovcheg\Auth::id();
$stories=$stories??active_stories_for_user((int)$profileUser['id'],\Kovcheg\Auth::id());
$hasStory=!empty($stories);
$hasUnseen=$hasStory&&count(array_filter($stories,fn($story)=>empty($story['viewed'])))>0;
$avatarReactions=$avatarReactions??avatar_reaction_summary((int)$profileUser['id'],\Kovcheg\Auth::id());
?>
<div class="profile-avatar-interactive <?=$hasStory?'has-story':''?> <?=$hasUnseen?'has-unseen-story':''?>" data-profile-avatar data-profile-user-id="<?=(int)$profileUser['id']?>">
 <button type="button" class="profile-avatar-button" data-avatar-menu-button aria-haspopup="menu" aria-expanded="false">
  <span class="profile-story-ring"><?=avatar_html($profileUser,'vk-profile-avatar')?></span>
  <span class="avatar-hover-label"><?=$hasStory?'История и фото':($isSelf?'Добавить историю или изменить фото':'Открыть фото')?></span>
 </button>
 <div class="profile-avatar-menu" data-avatar-menu hidden>
  <?php if($hasStory):?><button type="button" data-story-open="<?=(int)$profileUser['id']?>">▶ Посмотреть историю</button><?php endif;?>
  <?php if($isSelf):?><button type="button" data-story-create>＋ Добавить историю</button><?php endif;?>
  <button type="button" data-avatar-view>Посмотреть фото</button>
  <?php if($isSelf):?><button type="button" data-avatar-replace>Заменить фото</button><?php if(!empty($profileUser['avatar_path'])):?><button type="button" class="danger" data-avatar-delete>Удалить фото</button><?php endif;?><?php endif;?>
 </div>
 <?php if($isSelf):?>
 <form data-profile-avatar-form method="post" action="<?=e(app_url('/profile/avatar'))?>" enctype="multipart/form-data" hidden><?=csrf_field()?><input type="file" name="avatar" accept="image/jpeg,image/png,image/webp" data-profile-avatar-input></form>
 <form data-story-upload-form action="<?=e(app_url('/profile/story'))?>" enctype="multipart/form-data" hidden><?=csrf_field()?><input type="file" name="story" accept="image/jpeg,image/png,image/webp,video/mp4,video/webm" data-story-file-input></form>
 <?php endif;?>
</div>
