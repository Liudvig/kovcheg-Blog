<?php
$active=$active??'';
$items=[
 ['profile','/profile','⌂','Моя Страница'],
 ['feed','/feed','▤','Новости'],
 ['messages','/messages','●','Сообщения'],
 ['colleagues','/colleagues','♟','Друзья'],
 ['photos','/photos','▣','Фотографии'],
 ['music','/music','♫','Аудиозаписи'],
 ['videos','/videos','▥','Видеозаписи'],
 ['settings','/settings','⚙','Настройки'],
];
?>
<nav class="vk-side vk-reference-side" aria-label="Основное меню">
 <div class="vk-side-links"><?php foreach($items as [$key,$url,$icon,$label]):?><a class="<?=$active===$key?'active':''?>" href="<?=e(app_url($url))?>"><span><?=e($icon)?></span><?=e($label)?></a><?php endforeach;?><?php if(\Kovcheg\Auth::isAdmin()):?><a href="<?=e(app_url('/admin'))?>"><span>⚙</span>Управление</a><?php endif;?></div>
 <footer class="vk-template-copyright"><a class="kovcheg-copyright-link" href="https://kovchegcms.ru" target="_blank" rel="noopener noreferrer">KOVCHEGCMS.RU</a><small>Автор: Ланцет Семён Борисович</small><small>Все права защищены · <?=date('Y')?></small></footer>
</nav>
