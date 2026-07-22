<?php
use Kovcheg\Csrf;
$accountStats=$accountStats??['posts'=>0,'comments'=>0,'notifications'=>0,'colleagues'=>0];
$studioAllowed=(bool)($studioAllowed??false);
$copyright='© '.date('Y').' Ланцет Семён Борисович';
?><!doctype html>
<html lang="ru" class="studio-document">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<meta name="robots" content="noindex,nofollow,noarchive">
<meta name="csrf-token" content="<?=e(Csrf::token())?>">
<title>Личный кабинет — KOVCHEG Blog</title>
<link rel="stylesheet" href="<?=e(app_url('/assets/css/blog-studio.css?v='.rawurlencode(ASSET_REVISION)))?>">
<link rel="stylesheet" href="<?=e(app_url('/assets/css/blog-studio-unified.css?v='.rawurlencode(ASSET_REVISION)))?>">
</head>
<body class="account-studio-body">
<div class="account-studio-shell">
 <aside class="account-studio-sidebar">
  <header class="studio-brand"><a href="<?=e(app_url('/account'))?>"><span>K</span><div><b>KOVCHEG Blog</b><small>Личный кабинет</small></div></a></header>
  <nav aria-label="Разделы личного кабинета">
   <a class="active" href="<?=e(app_url('/account'))?>">◉ Обзор кабинета</a>
   <a href="<?=e(app_url('/profile'))?>">👤 Мой профиль</a>
   <a href="<?=e(app_url('/settings/general'))?>">✎ Личные данные</a>
   <a href="<?=e(app_url('/settings/privacy'))?>">◈ Приватность</a>
   <a href="<?=e(app_url('/settings/notifications'))?>">♢ Уведомления</a>
   <a href="<?=e(app_url('/settings/appearance'))?>">◇ Оформление</a>
   <a href="<?=e(app_url('/settings/security'))?>">⚿ Безопасность</a>
   <a href="<?=e(app_url('/messages'))?>">💬 Сообщения</a>
   <a href="<?=e(app_url('/colleagues'))?>">👥 Коллеги</a>
   <?php if($studioAllowed):?><a href="<?=e(app_url('/studio'))?>">▦ KOVCHEG Studio</a><?php endif;?>
  </nav>
  <div class="studio-sidebar-meta"><b>KOVCHEG Blog <?=e(APP_VERSION)?></b><small><?=e($copyright)?></small><small>Все права защищены</small></div>
 </aside>
 <main class="account-studio-main">
  <header class="studio-topbar">
   <div class="studio-topbar-title"><small>KOVCHEG BLOG</small><b>Личный кабинет</b></div>
   <div class="studio-top-actions">
    <a class="button studio-site-action" href="<?=e(app_url('/'))?>"><span class="studio-action-icon">↗</span><span class="studio-action-label">Перейти на сайт</span></a>
    <a class="button studio-account-action" href="<?=e(app_url('/profile'))?>"><span class="studio-action-icon">👤</span><span class="studio-action-label">Мой профиль</span></a>
    <?php if($studioAllowed):?><a class="button primary" href="<?=e(app_url('/studio'))?>"><span class="studio-action-icon">▦</span><span class="studio-action-label">Studio</span></a><?php endif;?>
    <form method="post" action="<?=e(app_url('/logout'))?>"><?=csrf_field()?><button class="button studio-logout-action" type="submit"><span class="studio-action-icon">↪</span><span class="studio-action-label">Выйти</span></button></form>
   </div>
  </header>
  <section class="account-studio-content">
   <header class="account-dashboard-hero">
    <div class="account-dashboard-identity"><?=avatar_html($user,'profile-avatar')?><div><small class="account-kicker">ЛИЧНЫЙ КАБИНЕТ</small><h1><?=e((string)($user['display_name']??'Пользователь'))?><?=verified_badge($user)?></h1><p>@<?=e((string)($user['username']??'user'))?> · <?=e((string)($user['email']??''))?></p></div></div>
    <div class="account-dashboard-actions"><a class="button primary" href="<?=e(app_url('/profile'))?>">Открыть профиль</a><a class="button" href="<?=e(app_url('/settings/general'))?>">Редактировать данные</a></div>
   </header>
   <section class="account-dashboard-stats" aria-label="Статистика пользователя">
    <article><strong><?=e((string)$accountStats['posts'])?></strong><span>Публикаций</span></article>
    <article><strong><?=e((string)$accountStats['comments'])?></strong><span>Комментариев</span></article>
    <article><strong><?=e((string)$accountStats['colleagues'])?></strong><span>Коллег</span></article>
    <article><strong><?=e((string)$accountStats['notifications'])?></strong><span>Новых уведомлений</span></article>
   </section>
   <section class="account-dashboard-grid">
    <article class="account-dashboard-card"><h2>Профиль и данные</h2><nav><a href="<?=e(app_url('/profile'))?>"><b>Моя страница</b><small>Публикации, фотографии и информация</small></a><a href="<?=e(app_url('/settings/general'))?>"><b>Личные данные</b><small>Имя, email, статус и аватар</small></a><a href="<?=e(app_url('/settings/privacy'))?>"><b>Приватность</b><small>Кто видит профиль и может писать</small></a></nav></article>
    <article class="account-dashboard-card"><h2>Настройки</h2><nav><a href="<?=e(app_url('/settings/appearance'))?>"><b>Оформление</b><small>Тема и отображение разделов</small></a><a href="<?=e(app_url('/settings/notifications'))?>"><b>Уведомления</b><small>Push, звук и содержимое сообщений</small></a><a href="<?=e(app_url('/settings/security'))?>"><b>Безопасность</b><small>Пароль и управление сеансом</small></a></nav></article>
    <article class="account-dashboard-card"><h2>Общение</h2><nav><a href="<?=e(app_url('/feed'))?>"><b>Лента</b><small>Публикации пользователей</small></a><a href="<?=e(app_url('/messages'))?>"><b>Сообщения</b><small>Личная переписка</small></a><a href="<?=e(app_url('/colleagues'))?>"><b>Коллеги</b><small>Контакты, подписчики и заявки</small></a></nav></article>
    <article class="account-dashboard-card"><h2>Управление сайтом</h2><nav><?php if($studioAllowed):?><a href="<?=e(app_url('/studio'))?>"><b>KOVCHEG Studio</b><small>Материалы, темы, модули и виджеты</small></a><?php endif;?><a href="<?=e(app_url('/'))?>"><b>Открыть сайт</b><small>Посмотреть публичную часть</small></a><a href="<?=e(app_url('/blog'))?>"><b>Публикации</b><small>Открыть блог и новости</small></a></nav></article>
   </section>
  </section>
  <footer class="studio-footer"><div><b>KOVCHEG Blog</b><span>Личный кабинет пользователя</span></div><div><span><?=e($copyright)?></span><span>Автор и правообладатель · Все права защищены</span></div></footer>
 </main>
</div>
</body>
</html>