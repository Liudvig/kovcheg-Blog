<?php $current=\Kovcheg\Auth::user()??[];?>
<main class="x-layout">
 <?=\Kovcheg\View::partial('site-sidebar',['active'=>'feed'])?>
 <section class="x-main"><header class="x-title"><h1>Главная</h1></header><?=\Kovcheg\View::partial('wall-composer',['current'=>$current,'action'=>app_url('/feed/post'),'placeholder'=>'Что происходит?'])?><div data-wall-feed><?php foreach($posts as $post)echo \Kovcheg\View::partial('feed-post',['post'=>$post]);?><?php if(!$posts):?><div class="x-post">Публикаций пока нет.</div><?php endif;?></div></section>
 <aside class="x-right"><label class="x-search"><span>⌕</span><input type="search" data-top-global-search placeholder="Поиск"><div class="top-global-results" data-top-global-results hidden></div></label><?=\Kovcheg\View::partial('weather-widget')?><section class="x-box"><h2>Актуальные темы</h2><a href="<?=e(app_url('/feed'))?>"><small>События · Сейчас</small><b>KOVCHEG</b></a><a href="<?=e(app_url('/settings/appearance'))?>"><small>Настройки</small><b>Оформление и темы</b></a></section><section class="x-box"><h2>Кого читать</h2><a href="<?=e(app_url('/colleagues'))?>">Найти коллег</a></section></aside>
</main>
