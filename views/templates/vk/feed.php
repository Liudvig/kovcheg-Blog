<?php $current=\Kovcheg\Auth::user()??[];?>
<main class="vk-page">
 <?=\Kovcheg\View::partial('site-sidebar',['active'=>'feed'])?>
 <section class="vk-feed">
  <header class="vk-card vk-card-head kov-vk-news-head"><div><h1>Новости</h1><p>Новые записи друзей и обновления сообщества</p></div></header>
  <?=\Kovcheg\View::partial('wall-composer',['current'=>$current,'action'=>app_url('/feed/post'),'placeholder'=>'Что у вас нового?'])?>
  <div data-wall-feed><?php foreach($posts as $post)echo \Kovcheg\View::partial('feed-post',['post'=>$post]);?><?php if(!$posts):?><div class="vk-card kov-vk-old-empty">В новостях пока пусто.</div><?php endif;?></div>
 </section>
 <aside class="vk-right">
  <?=\Kovcheg\View::partial('weather-widget')?>
  <section class="vk-card"><h3>Быстрые ссылки</h3><a class="vk-person-row" href="<?=e(app_url('/colleagues'))?>"><span>Найти друзей</span></a><a class="vk-person-row" href="<?=e(app_url('/profile'))?>"><span>Моя страница</span></a><a class="vk-person-row" href="<?=e(app_url('/settings/notifications'))?>"><span>Настроить уведомления</span></a></section>
  <section class="vk-card"><h3>Подсказка</h3><p>Используйте поиск в шапке, чтобы найти людей, записи и сообщения.</p></section>
 </aside>
</main>
