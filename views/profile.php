<?php
$profileUser = $user ?? \Kovcheg\Auth::user() ?? [];
$counts = $counts ?? profile_counts((int)$profileUser['id']);
$stories = $stories ?? active_stories_for_user((int)$profileUser['id'],\Kovcheg\Auth::id());
$avatarReactions = $avatarReactions ?? avatar_reaction_summary((int)$profileUser['id'],\Kovcheg\Auth::id());
?>
<link rel="stylesheet" href="<?=e(app_url('/assets/css/blog-profile-portal.css?v='.rawurlencode(ASSET_REVISION)))?>">
<script nonce="<?=e((string)($GLOBALS['CSP_NONCE']??''))?>">document.body.classList.add('portal-profile-context');</script>

<main class="portal-profile-shell">
 <nav class="portal-profile-breadcrumbs"><a href="<?=e(app_url('/'))?>">Сайт</a><span>→</span><a href="<?=e(app_url('/account'))?>">Личный кабинет</a><span>→</span><span>Профиль</span></nav>
 <div class="portal-profile-grid">
  <aside class="portal-profile-aside">
   <?=\Kovcheg\View::partial('profile-avatar-controls',compact('profileUser','stories','avatarReactions'))?>
   <a class="portal-profile-edit" href="<?=e(app_url('/settings/general'))?>">Редактировать профиль</a>
   <nav class="portal-profile-links">
    <a href="<?=e(app_url('/account'))?>">Личный кабинет</a>
    <a href="<?=e(app_url('/settings/general'))?>">Личные данные</a>
    <a href="<?=e(app_url('/settings/security'))?>">Безопасность</a>
    <a href="<?=e(user_public_url((string)$profileUser['username']))?>">Публичная ссылка</a>
   </nav>
  </aside>

  <section class="portal-profile-main">
   <article class="portal-profile-card">
    <header class="portal-profile-card__head">
     <div><h1><?=e((string)$profileUser['display_name'])?><?=verified_badge($profileUser,'verified-badge verified-large')?></h1><a class="portal-profile-handle" href="<?=e(user_public_url((string)$profileUser['username']))?>">@<?=e((string)$profileUser['username'])?></a></div>
     <span class="portal-profile-online" data-presence-user="<?=(int)$profileUser['id']?>"><?=online($profileUser['last_seen_at']??null)?'в сети':'был(а) '.e(human_time($profileUser['last_seen_at']??null))?></span>
    </header>
    <form class="portal-profile-status profile-status-editor" data-profile-status-form>
     <input name="status_text" maxlength="190" value="<?=e((string)($profileUser['status_text']??''))?>" placeholder="Статус">
     <button type="submit">Сохранить</button>
    </form>
    <div class="portal-profile-bio"><?=!empty($profileUser['bio'])?nl2br(e((string)$profileUser['bio'])):'Расскажите о себе в настройках профиля.'?></div>
    <dl class="portal-profile-fields">
     <div><dt>Email</dt><dd><?=e((string)($profileUser['email']??''))?></dd></div>
     <div><dt>Ник</dt><dd>@<?=e((string)($profileUser['username']??''))?></dd></div>
    </dl>
   </article>

   <article class="portal-profile-stats">
    <a href="<?=e(app_url('/colleagues'))?>"><b><?=e((string)$counts['colleagues'])?></b><span>контактов</span></a>
    <a href="<?=e(app_url('/colleagues?tab=followers'))?>"><b><?=e((string)$counts['followers'])?></b><span>подписчиков</span></a>
    <a href="<?=e(app_url('/colleagues?tab=following'))?>"><b><?=e((string)$counts['following'])?></b><span>подписок</span></a>
   </article>

   <div class="portal-profile-wall">
    <?=\Kovcheg\View::partial('profile-wall',['profileUser'=>$profileUser,'wallPosts'=>$wallPosts??[],'canPostWall'=>$canPostWall??true])?>
   </div>
  </section>
 </div>
</main>
<?=\Kovcheg\View::partial('profile-avatar-modals',['profileUser'=>$profileUser])?>
