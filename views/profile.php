<?php
$profileUser=$user??\Kovcheg\Auth::user()??[];
$counts=$counts??profile_counts((int)$profileUser['id']);
$peopleBlocks=$peopleBlocks??profile_people_blocks((int)$profileUser['id']);
$stories=$stories??active_stories_for_user((int)$profileUser['id'],\Kovcheg\Auth::id());
$avatarReactions=$avatarReactions??avatar_reaction_summary((int)$profileUser['id'],\Kovcheg\Auth::id());
?>
<main class="site-page-shell vk-site-shell"><?=\Kovcheg\View::partial('site-sidebar',['active'=>'profile'])?>
 <section class="vk-profile-page profile-page-090">
  <div class="vk-profile-grid vk-profile-grid-090 has-right">
   <aside class="vk-profile-left">
    <?=\Kovcheg\View::partial('profile-avatar-controls',compact('profileUser','stories','avatarReactions'))?>
    <a class="btn btn-primary vk-wide-button" href="<?=e(app_url('/settings/general'))?>">Редактировать профиль</a>
    <?=\Kovcheg\View::partial('profile-people-blocks',['profileUser'=>$profileUser,'peopleBlocks'=>$peopleBlocks])?>
   </aside>
   <div class="vk-profile-main">
    <article class="vk-profile-info-card profile-info-090">
     <header><div><h1><?=e($profileUser['display_name'])?><?=verified_badge($profileUser,'verified-badge verified-large')?></h1><a href="<?=e(user_public_url((string)$profileUser['username']))?>">@<?=e($profileUser['username'])?></a></div><span class="profile-online" data-presence-user="<?=(int)$profileUser['id']?>"><?=online($profileUser['last_seen_at']??null)?'в сети':'был(а) '.e(human_time($profileUser['last_seen_at']??null))?></span></header>
     <form class="profile-status-editor" data-profile-status-form><input name="status_text" maxlength="190" value="<?=e($profileUser['status_text']??'')?>" placeholder="Установить статус"><button type="submit">Сохранить</button></form>
     <?php if(!empty($profileUser['bio'])):?><div class="vk-bio"><?=nl2br(e($profileUser['bio']))?></div><?php else:?><div class="vk-bio muted">Расскажите коллегам о себе.</div><?php endif;?>
     <dl class="vk-profile-fields"><div><dt>Email</dt><dd><?=e($profileUser['email']??'')?></dd></div><div><dt>Ник</dt><dd>@<?=e($profileUser['username']??'')?></dd></div></dl>
    </article>
    <article class="vk-profile-stats"><a href="<?=e(app_url('/colleagues'))?>"><b><?=e($counts['colleagues'])?></b><span>коллег</span></a><a href="<?=e(app_url('/colleagues?tab=followers'))?>"><b><?=e($counts['followers'])?></b><span>подписчиков</span></a><a href="<?=e(app_url('/colleagues?tab=following'))?>"><b><?=e($counts['following'])?></b><span>подписок</span></a></article>
    <?=\Kovcheg\View::partial('profile-wall',['profileUser'=>$profileUser,'wallPosts'=>$wallPosts??[],'canPostWall'=>$canPostWall??true])?>
   </div>
   <aside class="vk-profile-right"><?=\Kovcheg\View::partial('weather-widget',['weatherUserId'=>(int)$profileUser['id']])?><article class="vk-right-card"><header><b>Дополнительные блоки</b></header><p>Колонка зарезервирована для групп, логотипов, описаний и блоков устанавливаемых модулей.</p></article></aside>
  </div>
 </section>
</main>
<?=\Kovcheg\View::partial('profile-avatar-modals',['profileUser'=>$profileUser])?>
