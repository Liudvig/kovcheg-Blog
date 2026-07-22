<?php
use Kovcheg\Auth;
use Kovcheg\Csrf;
$configuredSiteName=trim((string)setting('site_name',''));
$siteName=in_array($configuredSiteName,['','KOVCHEG CMS','KOVCHEG Core','KOVCHEG Blog Core'],true)?'KOVCHEG Blog':$configuredSiteName;
$theme=Auth::check()?user_setting('theme',setting('default_theme','dark')):setting('default_theme','dark');if(!in_array($theme,['light','dark','black'],true))$theme='dark';
$siteTemplate=isset($layoutTemplate)?(string)$layoutTemplate:(string)setting('site_template','default');if(!in_array($siteTemplate,['default','vk','x'],true))$siteTemplate='default';
$assetRevision=rawurlencode(ASSET_REVISION);
$canonical=current_absolute_url();
$cspNonce=(string)($GLOBALS['CSP_NONCE']??'');
$description=(string)setting('seo_description','Блог, портфолио и страницы на модульной платформе KOVCHEG Blog');
$keywords=(string)setting('seo_keywords','KOVCHEG Blog, блог, портфолио, CMS');
$indexing=setting('search_indexing','0')==='1';
$logoPath=(string)setting('logo_path','');$faviconPath=(string)setting('favicon_path','');
$notificationSettings=Auth::check()?[
    'enabled'=>user_setting('notifications_enabled','1')==='1',
    'sound'=>user_setting('notification_sound','1')==='1',
    'preview'=>(string)user_setting('notification_preview','full'),
    'avatar'=>user_setting('notification_avatar','1')==='1',
    'max'=>(int)user_setting('notification_max','3'),
    'desktop'=>user_setting('desktop_notifications','0')==='1',
]:[];
$flash=[];
if(!empty($_SESSION['flash_error'])){$flash[]=['type'=>'error','text'=>(string)$_SESSION['flash_error']];unset($_SESSION['flash_error']);}
if(!empty($_SESSION['flash_success'])){$flash[]=['type'=>'success','text'=>(string)$_SESSION['flash_success']];unset($_SESSION['flash_success']);}
$userUnread=Auth::check()?user_unread_count(Auth::id()):0;
$messageUnread=Auth::check()?chat_unread_count(Auth::id()):0;
$userNotes=Auth::check()?user_notifications(Auth::id(),20):[];
$currentUser=Auth::user()??[];
$liveMessageLast=0;if(Auth::check())try{$liveMessageLast=(int)(\Kovcheg\DB::one('SELECT COALESCE(MAX(m.id),0) c FROM messages m JOIN chat_members cm ON cm.chat_id=m.chat_id WHERE cm.user_id=?',[Auth::id()])['c']??0);}catch(Throwable){}
?><!doctype html>
<html lang="ru">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<meta name="csrf-token" content="<?=e(Csrf::token())?>">
<meta name="theme-color" content="<?=e($theme==='light'?'#f5f7fa':($theme==='black'?'#000000':'#17232f'))?>">
<meta name="description" content="<?=e($description)?>">
<meta name="keywords" content="<?=e($keywords)?>">
<meta name="robots" content="<?=(!$indexing||Auth::check())?'noindex,nofollow,noarchive':'index,follow,max-image-preview:large'?>">
<link rel="canonical" href="<?=e($canonical)?>">
<meta property="og:site_name" content="<?=e($siteName)?>">
<meta property="og:title" content="<?=e($title??$siteName)?>">
<meta property="og:description" content="<?=e($description)?>">
<meta property="og:url" content="<?=e($canonical)?>">
<meta property="og:type" content="website">
<meta name="twitter:card" content="summary_large_image">
<title><?=e($title??$siteName)?> — <?=e($siteName)?></title>
<link rel="icon" id="kovcheg-favicon" data-base-favicon="<?=e($faviconPath!==''?app_url('/brand/favicon?v='.rawurlencode(APP_VERSION)):app_url('/assets/icons/icon.svg?v='.rawurlencode(APP_VERSION)))?>" href="<?=e($faviconPath!==''?app_url('/brand/favicon?v='.rawurlencode(APP_VERSION)):app_url('/assets/icons/icon.svg?v='.rawurlencode(APP_VERSION)))?>">
<link rel="manifest" href="<?=e(app_url('/manifest.webmanifest'))?>">
<link rel="stylesheet" href="<?=e(app_url('/assets/css/kovcheg-core.css?v='.$assetRevision))?>">
<link rel="stylesheet" href="<?=e(app_url('/assets/css/blog-admin-shell.css?v='.$assetRevision))?>">
<link rel="stylesheet" href="<?=e(app_url('/assets/css/blog-account.css?v='.$assetRevision))?>">
<link rel="stylesheet" href="<?=e(app_url('/assets/css/templates/'.$siteTemplate.'.css?v='.$assetRevision))?>">
<?=\Kovcheg\Hooks::fire('layout.head','')?>
</head>
<body class="<?=Auth::check()?'auth-shell':'guest-shell'?>" data-theme="<?=e($theme)?>" data-template="<?=e($siteTemplate)?>">
<?php if(Auth::check()): ?>
<?php if(is_impersonating()):?><div class="impersonation-bar"><span>Режим просмотра: вы вошли как <?=e($currentUser['display_name']??'пользователь')?>. Онлайн-статус этого аккаунта не меняется.</span><form method="post" action="<?=e(app_url('/admin/impersonation/return'))?>"><?=csrf_field()?><button type="submit">Вернуться в админку</button></form></div><?php endif;?>
<header class="topbar topbar-100 kov-global-header" data-kov-global-header>
 <div class="kov-header-inner">
  <div class="topbar-left-170 kov-header-left">
   <a class="brand brand-static" href="<?=e(app_url('/feed'))?>" aria-label="<?=e($siteName)?>"><img class="brand-logo" src="<?=e(app_url('/brand/logo?v='.rawurlencode(APP_VERSION)))?>" alt="<?=e($siteName)?>"></a>
   <a class="brand-site-name-170" href="<?=e(app_url('/feed'))?>"><?=e($siteName)?></a>
   <div class="top-global-search topbar-global-search"><span>⌕</span><input type="search" data-top-global-search placeholder="Поиск людей, записей и сообщений" autocomplete="off"><div class="top-global-results" data-top-global-results hidden></div></div>
  </div>
  <nav class="topbar-actions kov-header-actions" aria-label="Быстрые действия">
   <details class="kov-header-menu kov-notifications-menu" data-kov-header-menu="notifications">
    <summary class="notification-bell" aria-label="Оповещения"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/></svg><em data-notification-bell-count <?=$userUnread?'':'hidden'?>><?=$userUnread?></em></summary>
    <section class="kov-header-panel kov-header-notifications">
     <header><div><b>Оповещения</b><small>Сообщения, заявки и события</small></div><button type="button" data-notifications-read-all>Прочитать всё</button></header>
     <div class="notification-permission" data-push-invite <?=user_setting('desktop_notifications','0')==='1'?'hidden':''?>><div><b>Включить прямые Push</b><small>Оповещения будут приходить даже при закрытой вкладке.</small></div><button type="button" data-request-notifications>Включить</button></div>
     <div class="kov-header-notification-list" data-notification-list>
      <?php foreach($userNotes as $note):?><a class="notification-note <?=empty($note['is_read'])?'unread':''?>" href="<?=e($note['url']?:app_url('/messages'))?>" data-notification-id="<?=(int)$note['id']?>"><span class="notification-note-icon"><?php if(!empty($note['icon'])):?><img src="<?=e($note['icon'])?>" alt=""><?php else:?><?=($note['type']??'')==='message'?'💬':(($note['type']??'')==='social'?'👥':'🔔')?><?php endif;?></span><div><b><?=e($note['title'])?></b><p><?=e($note['body']??'')?></p><small><?=e(human_time($note['created_at']))?></small></div></a><?php endforeach;?>
      <?php if(!$userNotes):?><p class="empty-notes">Оповещений пока нет.</p><?php endif;?>
     </div>
    </section>
   </details>
   <details class="kov-header-menu kov-account-menu" data-kov-header-menu="account">
    <summary class="top-profile kov-header-account-button" aria-label="Меню пользователя"><?=avatar_html($currentUser,'avatar-xs')?><span><?=e($currentUser['display_name']??'Профиль')?><?=verified_badge($currentUser)?></span><i aria-hidden="true">⌄</i></summary>
    <section class="kov-header-panel kov-header-account">
     <a class="kov-header-account-profile" href="<?=e(app_url('/account'))?>"><?=avatar_html($currentUser,'avatar-xs')?><span><b>Личный кабинет</b><small>Профиль, настройки и безопасность</small></span></a>
     <a href="<?=e(app_url('/profile'))?>"><span class="menu-symbol">👤</span><span><b>Мой профиль</b><small>@<?=e($currentUser['username']??'profile')?></small></span></a>
     <div class="profile-theme-row"><span>Оформление</span><div><button type="button" data-quick-theme="dark" class="<?=$theme==='dark'?'active':''?>">Тёмная</button><button type="button" data-quick-theme="black" class="<?=$theme==='black'?'active':''?>">Чёрная</button><button type="button" data-quick-theme="light" class="<?=$theme==='light'?'active':''?>">Светлая</button></div></div>
     <?php if(Auth::isAdmin()):?><a href="<?=e(app_url('/studio'))?>"><span class="menu-symbol">✦</span><span><b>KOVCHEG Studio</b><small>Управление сайтом и публикациями</small></span></a><?php endif;?>
     <?php if(Auth::isAdmin()):?><a href="<?=e(app_url('/admin'))?>"><span class="menu-symbol">⚙</span><span><b>Системная админка</b><small>Пользователи и настройки системы</small></span></a><?php endif;?>
     <form method="post" action="<?=e(app_url('/logout'))?>"><?=csrf_field()?><button type="submit" class="profile-logout"><span class="menu-symbol">↪</span><span><b>Выйти</b><small>Завершить сеанс вручную</small></span></button></form>
    </section>
   </details>
  </nav>
 </div>
</header>
<div class="global-audio-player kovcheg-headless-player" data-global-player hidden aria-hidden="true">
 <button type="button" data-player-prev>⏮</button><button type="button" data-player-play>▶</button><button type="button" data-player-next>⏭</button><button type="button" data-player-repeat>↻</button>
 <span data-player-title></span><span data-player-author></span><input type="range" min="0" max="1000" value="0" data-player-progress><time data-player-time>0:00 / 0:00</time><button type="button" data-player-mute>🔊</button><input type="range" min="0" max="100" value="100" data-player-volume><audio preload="metadata" data-player-audio></audio>
</div>
<?php endif;?>
<div id="kovcheg-page-content" data-kovcheg-page-content><?=$content??''?></div>
<?php if(Auth::check()) require __DIR__.'/mobile-navigation.php'; ?>
<div class="toast-stack" id="toast-stack" aria-live="polite"></div>
<?php if(!Auth::check()):?><footer class="footer"><b><?=e(setting('copyright','© KOVCHEG Blog'))?></b> · Автор проекта: Ланцет Семён Борисович · Все права защищены · <?=date('Y')?></footer><?php endif;?>
<script nonce="<?=e($cspNonce)?>">window.KOVCHEG={version:<?=json_encode(APP_VERSION)?>,baseUrl:<?=json_encode(rtrim(app_url('/'),'/'))?>,csrf:<?=json_encode(Csrf::token())?>,polling:<?=json_encode(max(3000,(int)setting('polling_ms','3000')))?>,userId:<?=json_encode(Auth::id())?>,isAdmin:<?=json_encode(Auth::isAdmin())?>,notificationLast:<?=json_encode((int)($userNotes[0]['id']??0))?>,liveMessageLast:<?=json_encode($liveMessageLast)?>,messageUnread:<?=json_encode($messageUnread)?>,pushPublicKey:<?=json_encode((string)setting('push_vapid_public_key',''))?>,notifications:<?=json_encode($notificationSettings,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)?>,flash:<?=json_encode($flash,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)?>};</script>
<script src="<?=e(app_url('/assets/js/post-submit-guard.js?v='.$assetRevision))?>" defer></script>
<script src="<?=e(app_url('/assets/js/kovcheg-core.js?v='.$assetRevision))?>" defer></script>
<script src="<?=e(app_url('/assets/js/blog-admin-shell.js?v='.$assetRevision))?>" defer></script>
<?=\Kovcheg\Hooks::fire('layout.scripts','')?>
</body></html>
