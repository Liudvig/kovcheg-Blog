<?php
$profileUser=$user??\Kovcheg\Auth::user()??[];
$profileId=(int)($profileUser['id']??0);
$isSelf=\Kovcheg\Auth::check()&&\Kovcheg\Auth::id()===$profileId;
$counts=$counts??profile_counts($profileId);
$peopleBlocks=$peopleBlocks??profile_people_blocks($profileId);
$description=trim((string)($profileUser['bio']??''));
$username=(string)($profileUser['username']??'');
$avatarSrc=!empty($profileUser['avatar_path'])?avatar_url($profileId,(string)$profileUser['avatar_path']):app_url('/assets/icons/default-avatar.svg?v='.ASSET_REVISION);
$avatarCss='url('.json_encode($avatarSrc,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE).')';
$profilePhotos=[];
try{if(function_exists('vk_media_rows'))$profilePhotos=vk_media_rows($profileId,'photo');}catch(Throwable $error){log_error($error);}
$profilePhotoCount=count($profilePhotos);
$profilePhotoPreview=array_slice($profilePhotos,0,4);
$profilePhotosUrl=app_url('/photos'.($isSelf?'':'?user='.$profileId));
$onlineFriends=(array)($peopleBlocks['online']??[]);
$friends=(array)($peopleBlocks['colleagues']??[]);
$followers=(array)($peopleBlocks['followers']??[]);
$wallCount=count($wallPosts??[]);
$birthday=!empty($profileUser['birthday'])?date('d.m.Y',strtotime((string)$profileUser['birthday'])):'Не указана';
$city=trim((string)($profileUser['city']??$profileUser['location']??''));
$education=trim((string)($profileUser['education']??''));
$messageUrl=app_url('/messages'.($username!==''?'/@'.$username:''));
?>
<main class="vk-page kov-vk-old-profile-page vk-reference-profile">
 <?=\Kovcheg\View::partial('site-sidebar',['active'=>'profile'])?>

 <aside class="kov-vk-old-profile-left vk-reference-profile-left">
  <section class="vk-card kov-vk-old-avatar-card vk-reference-avatar-card">
   <div class="kov-vk-old-avatar kov-vk-avatar-shell" data-profile-avatar>
    <button type="button" class="kov-vk-avatar-open" data-avatar-view aria-label="Открыть фотографию профиля, реакции и комментарии" style="--vk-avatar-image:<?=e($avatarCss)?>">
     <img class="kov-vk-avatar-image" src="<?=e($avatarSrc)?>" alt="Фотография профиля <?=e($profileUser['display_name']??'пользователя')?>">
     <span>Открыть фотографию</span>
    </button>
    <div data-avatar-menu hidden></div>
   </div>
   <div class="vk-reference-profile-actions <?=$isSelf?'single':''?>">
    <?php if($isSelf):?>
     <a class="vk-reference-primary-action" href="<?=e(app_url('/settings/general'))?>">Редактировать профиль</a>
    <?php else:?>
     <a class="vk-reference-primary-action" href="<?=e($messageUrl)?>">Отправить сообщение</a>
     <a class="vk-reference-secondary-action" href="<?=e(app_url('/colleagues'))?>">Добавить в друзья</a>
    <?php endif;?>
   </div>
  </section>

  <section class="vk-card kov-vk-old-side-card vk-reference-friends-card">
   <header><a href="<?=e(app_url('/colleagues'))?>">Друзья онлайн <span><?=count($onlineFriends)?></span></a></header>
   <div class="kov-vk-old-friends"><?php foreach(array_slice($onlineFriends,0,3) as $person):?><a href="<?=e(user_public_url((string)$person['username']))?>"><?=avatar_html($person,'avatar-xs')?><small><?=e($person['first_name']??explode(' ',(string)$person['display_name'])[0])?></small></a><?php endforeach;?><?php if(!$onlineFriends):?><p>Сейчас никого нет.</p><?php endif;?></div>
  </section>

  <section class="vk-card kov-vk-old-side-card vk-reference-friends-card">
   <header><a href="<?=e(app_url('/colleagues'))?>">Друзья <span><?=count($friends)?></span></a><a class="vk-reference-side-news" href="<?=e(app_url('/feed'))?>">новости</a></header>
   <div class="kov-vk-old-friends"><?php foreach(array_slice($friends,0,6) as $person):?><a href="<?=e(user_public_url((string)$person['username']))?>"><?=avatar_html($person,'avatar-xs')?><small><?=e($person['first_name']??explode(' ',(string)$person['display_name'])[0])?></small></a><?php endforeach;?><?php if(!$friends):?><p>Друзей пока нет.</p><?php endif;?></div>
  </section>

  <?php if($followers):?><section class="vk-card kov-vk-old-side-card vk-reference-friends-card"><header><a href="<?=e(app_url('/colleagues?tab=followers'))?>">Подписчики <span><?=count($followers)?></span></a></header><div class="kov-vk-old-friends"><?php foreach(array_slice($followers,0,3) as $person):?><a href="<?=e(user_public_url((string)$person['username']))?>"><?=avatar_html($person,'avatar-xs')?><small><?=e($person['first_name']??explode(' ',(string)$person['display_name'])[0])?></small></a><?php endforeach;?></div></section><?php endif;?>
 </aside>

 <section class="kov-vk-old-profile-main vk-reference-profile-main">
  <article class="vk-card kov-vk-old-info vk-reference-info-card" id="profile-details">
   <header class="vk-reference-info-head"><div><h1><?=e($profileUser['display_name']??'Пользователь')?><?=verified_badge($profileUser)?></h1><p><?=e($profileUser['status_text']?:'Статус пока не установлен')?></p></div><span><?=online($profileUser['last_seen_at']??null)?'Online':'был(а) '.e(human_time($profileUser['last_seen_at']??null))?></span></header>
   <div class="vk-reference-info-body">
    <dl>
     <div><dt>День рождения:</dt><dd><?=e($birthday)?></dd></div>
     <?php if($city!==''):?><div><dt>Город:</dt><dd><?=e($city)?></dd></div><?php endif;?>
     <?php if($education!==''):?><div><dt>Образование:</dt><dd><?=e($education)?></dd></div><?php endif;?>
     <div><dt>Короткое имя:</dt><dd><a href="<?=e(user_public_url($username))?>">@<?=e($username)?></a></dd></div>
    </dl>
    <details class="vk-reference-details"><summary>Показать подробную информацию</summary><div><?php if($description!==''):?><?=nl2br(e($description))?><?php elseif($isSelf):?>Расскажите о себе в настройках профиля.<?php else:?>Подробная информация пока не заполнена.<?php endif;?></div></details>
   </div>
   <div class="kov-vk-old-stats vk-reference-stats">
    <a href="<?=e(app_url('/colleagues'))?>"><b><?=count($onlineFriends)?></b><span>онлайн</span></a>
    <a href="<?=e(app_url('/colleagues'))?>"><b><?=e($counts['colleagues']??0)?></b><span>друзей</span></a>
    <a href="<?=e(app_url('/colleagues?tab=followers'))?>"><b><?=e($counts['followers']??0)?></b><span>подписчиков</span></a>
    <a href="<?=e($profilePhotosUrl)?>"><b><?=$profilePhotoCount?></b><span>фотографий</span></a>
    <a href="#profile-posts"><b><?=$wallCount?></b><span>записей</span></a>
   </div>
  </article>

  <section class="vk-card kov-vk-profile-photos vk-reference-photo-strip" aria-label="Четыре последние фотографии пользователя">
   <header class="kov-vk-profile-photos-head"><a href="<?=e($profilePhotosUrl)?>"><b>Фотографии <?=e($profileUser['first_name']??$profileUser['display_name']??'пользователя')?> <span><?=$profilePhotoCount?></span></b></a><a href="<?=e($profilePhotosUrl)?>">показать все</a></header>
   <div class="kov-vk-profile-photo-row vk-reference-photo-grid">
    <?php foreach($profilePhotoPreview as $photo):$photoTitle=(string)($photo['title']?:$photo['original_name']?:'Фотография');?><button type="button" class="kov-vk-profile-photo" data-vk-photo-open="<?=e($photo['url'])?>" data-vk-title="<?=e($photoTitle)?>"><img src="<?=e($photo['url'])?>" alt="<?=e($photoTitle)?>" loading="lazy"></button><?php endforeach;?>
    <?php for($slot=count($profilePhotoPreview);$slot<4;$slot++):?><a class="kov-vk-profile-photo vk-reference-photo-placeholder" href="<?=e($profilePhotosUrl)?>"><span>▣</span><small><?=$isSelf?'Добавить фото':'Нет фотографии'?></small></a><?php endfor;?>
   </div>
  </section>

  <?=\Kovcheg\View::partial('profile-media-tabs',['profileUser'=>$profileUser,'profileId'=>$profileId,'wallPosts'=>$wallPosts??[],'avatarSrc'=>$avatarSrc])?>
 </section>
</main>
<?=\Kovcheg\View::partial('profile-avatar-modals',['profileUser'=>$profileUser])?>
