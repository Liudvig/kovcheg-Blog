<?php
$active=$active??'';
$counts=\Kovcheg\Auth::check()?profile_counts(\Kovcheg\Auth::id()):['requests'=>0];
$items=[['feed','/feed','⌂','Лента'],['profile','/profile','👤','Профиль'],['messages','/messages','💬','Сообщения'],['colleagues','/colleagues','👥','Коллеги']];
$items=\Kovcheg\Hooks::fire('sidebar.items',$items);
if(\Kovcheg\Auth::isAdmin())$items[]=['admin','/admin','🛡','Админка'];
$primary=['feed','messages','music','profile'];
?>
<nav class="site-sidebar" aria-label="Меню сайта">
 <div class="site-sidebar-menu desktop-site-menu">
  <?php foreach($items as [$key,$url,$icon,$label]):?>
   <a class="site-nav-item <?=$active===$key?'active':''?>" href="<?=e(app_url($url))?>" title="<?=e($label)?>"><span><?=e($icon)?></span><b><?=e($label)?></b><?php if($key==='colleagues'&&!empty($counts['requests'])):?><em><?=e($counts['requests'])?></em><?php endif;?></a>
  <?php endforeach;?>
 </div>
 <footer class="sidebar-copyright"><b><a class="kovcheg-copyright-link" href="https://kovchegcms.ru" target="_blank" rel="noopener noreferrer">KOVCHEGCMS.RU</a></b><small>Автор проекта: Ланцет Семён Борисович</small><small>Все права защищены · <?=date('Y')?></small></footer>
</nav>
