<?php
use Kovcheg\Auth;
use Kovcheg\Csrf;

$siteName=setting('site_name',cfg('app.name','KOVCHEG CMS'));
$currentUser=Auth::user()??[];
$cspNonce=(string)($GLOBALS['CSP_NONCE']??'');
$userNotes=Auth::check()?user_notifications(Auth::id(),20):[];
$messageUnread=Auth::check()?chat_unread_count(Auth::id()):0;
$liveMessageLast=0;
if(Auth::check())try{$liveMessageLast=(int)(\Kovcheg\DB::one('SELECT COALESCE(MAX(m.id),0) c FROM messages m JOIN chat_members cm ON cm.chat_id=m.chat_id WHERE cm.user_id=?',[Auth::id()])['c']??0);}catch(Throwable){}
$savedTheme=Auth::check()?(string)user_setting('theme','black'):'black';
$xTheme=$savedTheme==='light'?'light':'black';
$flash=[];
if(!empty($_SESSION['flash_error'])){$flash[]=['type'=>'error','text'=>(string)$_SESSION['flash_error']];unset($_SESSION['flash_error']);}
if(!empty($_SESSION['flash_success'])){$flash[]=['type'=>'success','text'=>(string)$_SESSION['flash_success']];unset($_SESSION['flash_success']);}
?>
<!doctype html>
<html lang="ru">
<head>
 <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover"><meta name="csrf-token" content="<?=e(Csrf::token())?>"><meta name="theme-color" content="<?=$xTheme==='light'?'#ffffff':'#000000'?>"><meta name="robots" content="noindex,nofollow"><title><?=e($title??$siteName)?> — <?=e($siteName)?></title>
 <link rel="icon" href="<?=e(app_url('/assets/icons/icon.svg?v='.ASSET_REVISION))?>"><link rel="stylesheet" href="<?=e(app_url('/assets/css/kovcheg-core.css?v='.ASSET_REVISION))?>"><link rel="stylesheet" href="<?=e(app_url('/assets/css/templates/x.css?v='.ASSET_REVISION))?>"><link rel="stylesheet" href="<?=e(app_url('/assets/css/templates/x-fixes.css?v='.ASSET_REVISION))?>"><?=(string)\Kovcheg\Hooks::fire('layout.head','')?>
</head>
<body class="x-app <?=Auth::check()?'is-auth':'is-guest'?>" data-template="x" data-theme="<?=e($xTheme)?>">
<div id="kovcheg-page-content" data-kovcheg-page-content><?=$content??''?></div><div class="toast-stack" id="toast-stack" aria-live="polite"></div>
<script nonce="<?=e($cspNonce)?>">window.KOVCHEG={version:<?=json_encode(APP_VERSION)?>,baseUrl:<?=json_encode(rtrim(app_url('/'),'/'))?>,csrf:<?=json_encode(Csrf::token())?>,polling:<?=json_encode(max(3000,(int)setting('polling_ms','3000')))?>,userId:<?=json_encode(Auth::id())?>,isAdmin:<?=json_encode(Auth::isAdmin())?>,notificationLast:<?=json_encode((int)($userNotes[0]['id']??0))?>,liveMessageLast:<?=json_encode($liveMessageLast)?>,messageUnread:<?=json_encode($messageUnread)?>,notifications:{enabled:true,sound:true,preview:'full',avatar:true,max:3,desktop:false},flash:<?=json_encode($flash,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)?>};</script>
<script src="<?=e(app_url('/assets/js/post-submit-guard.js?v='.ASSET_REVISION))?>" defer></script><script src="<?=e(app_url('/assets/js/kovcheg-core.js?v='.ASSET_REVISION))?>" defer></script><script src="<?=e(app_url('/assets/js/social-templates.js?v='.ASSET_REVISION))?>" defer></script><script src="<?=e(app_url('/assets/js/social-template-fixes.js?v='.ASSET_REVISION))?>" defer></script><?=(string)\Kovcheg\Hooks::fire('layout.scripts','')?>
</body>
</html>
