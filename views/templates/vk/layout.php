<?php
use Kovcheg\Auth;
use Kovcheg\Csrf;

$siteName=setting('site_name',cfg('app.name','KOVCHEG CMS'));
$currentUser=Auth::user()??[];
$cspNonce=(string)($GLOBALS['CSP_NONCE']??'');
$userNotes=Auth::check()?user_notifications(Auth::id(),20):[];
$userUnread=Auth::check()?user_unread_count(Auth::id()):0;
$messageUnread=Auth::check()?chat_unread_count(Auth::id()):0;
$liveMessageLast=0;
if(Auth::check())try{$liveMessageLast=(int)(\Kovcheg\DB::one('SELECT COALESCE(MAX(m.id),0) c FROM messages m JOIN chat_members cm ON cm.chat_id=m.chat_id WHERE cm.user_id=?',[Auth::id()])['c']??0);}catch(Throwable){}
$flash=[];
if(!empty($_SESSION['flash_error'])){$flash[]=['type'=>'error','text'=>(string)$_SESSION['flash_error']];unset($_SESSION['flash_error']);}
if(!empty($_SESSION['flash_success'])){$flash[]=['type'=>'success','text'=>(string)$_SESSION['flash_success']];unset($_SESSION['flash_success']);}
?>
<!doctype html>
<html lang="ru">
<head>
 <meta charset="utf-8">
 <meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
 <meta name="csrf-token" content="<?=e(Csrf::token())?>">
 <meta name="theme-color" content="#4f79a6">
 <meta name="robots" content="noindex,nofollow">
 <title><?=e($title??$siteName)?> — <?=e($siteName)?></title>
 <link rel="icon" href="<?=e(app_url('/assets/icons/icon.svg?v='.ASSET_REVISION))?>">
 <link rel="stylesheet" href="<?=e(app_url('/assets/css/kovcheg-core.css?v='.ASSET_REVISION))?>">
 <link rel="stylesheet" href="<?=e(app_url('/assets/css/templates/vk.css?v='.ASSET_REVISION))?>">
 <link rel="stylesheet" href="<?=e(app_url('/assets/css/templates/vk-fixes.css?v='.ASSET_REVISION))?>">
 <link rel="stylesheet" href="<?=e(app_url('/assets/css/vk-header-clean.css?v='.ASSET_REVISION))?>">
 <?=(string)\Kovcheg\Hooks::fire('layout.head','')?>
 <link rel="stylesheet" href="<?=e(app_url('/assets/css/vk-reference-layout.css?v='.ASSET_REVISION))?>">
 <link rel="stylesheet" href="<?=e(app_url('/assets/css/vk-reference-fixes.css?v='.ASSET_REVISION))?>">
</head>
<body class="vk-app <?=Auth::check()?'is-auth':'is-guest'?>" data-template="vk" data-theme="light">
<?php if(Auth::check()):?>
<header class="vk-top vk-reference-top"><div class="vk-top-inner"><a class="vk-wordmark vk-reference-wordmark" href="<?=e(app_url('/feed'))?>"><span>K</span><b><?=e($siteName)?></b></a><label class="vk-search vk-reference-search"><span>⌕</span><input type="search" data-top-global-search placeholder="Поиск" autocomplete="off"><div class="top-global-results" data-top-global-results hidden></div></label><div class="vk-reference-header-icons"><details class="vk-drop kov-header-menu" data-kov-header-menu="notifications"><summary class="vk-icon-button vk-notification-button" aria-label="Уведомления"><svg class="vk-bell-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M6.7 9.7a5.3 5.3 0 0 1 10.6 0v3.1c0 1.2.4 2.3 1.2 3.2l.5.6H5l.5-.6c.8-.9 1.2-2 1.2-3.2V9.7Z"/><path d="M9.7 19a2.6 2.6 0 0 0 4.6 0"/></svg><?php if($userUnread):?><em><?=$userUnread?></em><?php endif;?></summary><section class="vk-drop-panel kov-header-panel"><header><b>Уведомления</b><button type="button" data-notifications-read-all>Прочитать все</button></header><div data-notification-list><?php foreach($userNotes as $note):?><a class="notification-note <?=empty($note['is_read'])?'unread':''?>" href="<?=e($note['url']?:app_url('/messages'))?>" data-notification-id="<?=(int)$note['id']?>"><b><?=e($note['title'])?></b><small><?=e($note['body']??'')?></small></a><?php endforeach;?><?php if(!$userNotes):?><p>Новых уведомлений нет</p><?php endif;?></div></section></details><a class="vk-reference-music-link" href="<?=e(app_url('/music'))?>" aria-label="Музыка">♫</a></div><nav class="vk-top-actions vk-reference-account"><details class="vk-drop kov-header-menu" data-kov-header-menu="account"><summary class="vk-me"><span><?=e($currentUser['first_name']??$currentUser['display_name']??'Профиль')?></span><?=avatar_html($currentUser,'avatar-xs')?><i>⌄</i></summary><section class="vk-drop-panel vk-account-panel"><a href="<?=e(app_url('/profile'))?>">Моя страница</a><a href="<?=e(app_url('/settings'))?>">Настройки</a><?php if(Auth::isAdmin()):?><a href="<?=e(app_url('/admin'))?>">Управление</a><?php endif;?><form method="post" action="<?=e(app_url('/logout'))?>"><?=csrf_field()?><button type="submit">Выйти</button></form></section></details></nav></div></header>
<?php endif;?>
<div id="kovcheg-page-content" data-kovcheg-page-content><?=$content??''?></div><div class="toast-stack" id="toast-stack" aria-live="polite"></div>
<script nonce="<?=e($cspNonce)?>">window.KOVCHEG={version:<?=json_encode(APP_VERSION)?>,baseUrl:<?=json_encode(rtrim(app_url('/'),'/'))?>,csrf:<?=json_encode(Csrf::token())?>,polling:<?=json_encode(max(3000,(int)setting('polling_ms','3000')))?>,userId:<?=json_encode(Auth::id())?>,isAdmin:<?=json_encode(Auth::isAdmin())?>,notificationLast:<?=json_encode((int)($userNotes[0]['id']??0))?>,liveMessageLast:<?=json_encode($liveMessageLast)?>,messageUnread:<?=json_encode($messageUnread)?>,notifications:{enabled:true,sound:true,preview:'full',avatar:true,max:3,desktop:false},flash:<?=json_encode($flash,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)?>};</script>
<script src="<?=e(app_url('/assets/js/vk-profile-fixes.js?v='.ASSET_REVISION))?>" defer></script><script src="<?=e(app_url('/assets/js/post-submit-guard.js?v='.ASSET_REVISION))?>" defer></script><script src="<?=e(app_url('/assets/js/kovcheg-core.js?v='.ASSET_REVISION))?>" defer></script><script src="<?=e(app_url('/assets/js/social-templates.js?v='.ASSET_REVISION))?>" defer></script><script src="<?=e(app_url('/assets/js/social-template-fixes.js?v='.ASSET_REVISION))?>" defer></script><script src="<?=e(app_url('/assets/js/vk-media.js?v='.ASSET_REVISION))?>" defer></script><?=(string)\Kovcheg\Hooks::fire('layout.scripts','')?>
</body>
</html>
