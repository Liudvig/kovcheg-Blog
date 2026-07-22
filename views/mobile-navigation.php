<?php
use Kovcheg\Auth;

$path=(string)(parse_url((string)($_SERVER['REQUEST_URI']??'/'),PHP_URL_PATH)?:'/');
$base=(string)(parse_url(app_url('/'),PHP_URL_PATH)?:'');
if($base!==''&&$base!=='/'&&str_starts_with($path,rtrim($base,'/'))) $path=substr($path,strlen(rtrim($base,'/')))?:'/';
$active=str_starts_with($path,'/messages')?'messages':(str_starts_with($path,'/account')?'account':(str_starts_with($path,'/profile')?'profile':(str_starts_with($path,'/colleagues')?'colleagues':(str_starts_with($path,'/admin')?'admin':'feed'))));
$items=[['feed','/feed','⌂','Лента'],['profile','/profile','👤','Профиль'],['account','/account','◉','Личный кабинет'],['messages','/messages','💬','Сообщения'],['colleagues','/colleagues','👥','Коллеги']];
$items=\Kovcheg\Hooks::fire('sidebar.items',$items);
$normalized=[];
foreach($items as $item){if(!is_array($item)||count($item)<4)continue;$normalized[(string)$item[0]]=array_slice($item,0,4);}
if(Auth::isAdmin())$normalized['admin']=['admin','/admin','🛡','Админка'];
$items=array_values($normalized);
$primary=['feed','messages','profile','colleagues'];
$counts=Auth::check()?profile_counts(Auth::id()):['requests'=>0];
?>
<nav class="mobile-bottom-nav" data-mobile-bottom-nav aria-label="Основное меню">
 <?php foreach($items as [$key,$url,$icon,$label]):?><?php if(in_array($key,$primary,true)):?>
  <a class="mobile-nav-item <?=$active===$key?'active':''?>" data-mobile-route="<?=e($key)?>" href="<?=e(app_url($url))?>"><span><?=e($icon)?></span><b><?=e($label)?></b><?php if($key==='colleagues'&&!empty($counts['requests'])):?><em><?=e($counts['requests'])?></em><?php endif;?></a>
 <?php endif;?><?php endforeach;?>
 <button type="button" class="mobile-nav-item mobile-more-button <?=($active&&!in_array($active,$primary,true))?'active':''?>" data-mobile-drawer-open><span>☰</span><b>Ещё</b></button>
</nav>
<div class="mobile-drawer-overlay" data-mobile-drawer-overlay hidden></div>
<aside class="mobile-side-drawer" data-mobile-side-drawer aria-hidden="true">
 <header><b>Разделы</b><button type="button" data-mobile-drawer-close aria-label="Закрыть">×</button></header>
 <nav>
  <?php foreach($items as [$key,$url,$icon,$label]):?><?php if(!in_array($key,$primary,true)):?>
   <a class="mobile-drawer-item <?=$active===$key?'active':''?>" data-mobile-route="<?=e($key)?>" href="<?=e(app_url($url))?>"><span><?=e($icon)?></span><b><?=e($label)?></b></a>
  <?php endif;?><?php endforeach;?>
 </nav>
</aside>
