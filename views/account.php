<?php
$accountStats = $accountStats ?? ['posts'=>0,'comments'=>0,'notifications'=>0,'colleagues'=>0];
$studioAllowed = (bool)($studioAllowed ?? false);
?>
<main class="site-page-shell account-page-shell">
 <?=\Kovcheg\View::partial('site-sidebar',['active'=>'account'])?>
 <section class="account-page">
  <header class="account-hero">
   <div class="account-identity">
    <?=avatar_html($user,'profile-avatar')?>
    <div>
     <span class="account-kicker">Личный кабинет</span>
     <h1><?=e((string)($user['display_name']??'Пользователь'))?><?=verified_badge($user)?></h1>
     <p>@<?=e((string)($user['username']??'user'))?> · <?=e((string)($user['email']??''))?></p>
     <?php if(!empty($user['status_text'])):?><blockquote><?=e((string)$user['status_text'])?></blockquote><?php endif;?>
    </div>
   </div>
   <div class="account-hero-actions">
    <a class="btn btn-primary" href="<?=e(app_url('/profile'))?>">Открыть мой профиль</a>
    <a class="btn" href="<?=e(app_url('/settings/general'))?>">Редактировать данные</a>
    <?php if($studioAllowed):?><a class="btn" href="<?=e(app_url('/studio'))?>">KOVCHEG Studio</a><?php endif;?>
   </div>
  </header>

  <section class="account-stats" aria-label="Статистика пользователя">
   <article><strong><?=e((string)$accountStats['posts'])?></strong><span>Моих публикаций</span></article>
   <article><strong><?=e((string)$accountStats['comments'])?></strong><span>Комментариев</span></article>
   <article><strong><?=e((string)$accountStats['colleagues'])?></strong><span>Коллег</span></article>
   <article><strong><?=e((string)$accountStats['notifications'])?></strong><span>Новых уведомлений</span></article>
  </section>

  <section class="account-grid">
   <article class="account-card">
    <header><span>👤</span><div><h2>Профиль</h2><p>Страница, которую видят другие пользователи.</p></div></header>
    <nav>
     <a href="<?=e(app_url('/profile'))?>"><b>Мой профиль</b><small>Стена, фотографии и публикации</small></a>
     <a href="<?=e(app_url('/settings/general'))?>"><b>Личные данные</b><small>Имя, email, статус и фотография</small></a>
     <a href="<?=e(app_url('/settings/privacy'))?>"><b>Приватность</b><small>Кто видит страницу и может писать</small></a>
    </nav>
   </article>

   <article class="account-card">
    <header><span>⚙</span><div><h2>Настройки</h2><p>Управление интерфейсом и безопасностью.</p></div></header>
    <nav>
     <a href="<?=e(app_url('/settings/appearance'))?>"><b>Оформление</b><small>Тема, правая колонка и погода</small></a>
     <a href="<?=e(app_url('/settings/notifications'))?>"><b>Уведомления</b><small>Push, звук и содержимое карточек</small></a>
     <a href="<?=e(app_url('/settings/security'))?>"><b>Безопасность</b><small>Пароль и текущий сеанс</small></a>
    </nav>
   </article>

   <article class="account-card">
    <header><span>💬</span><div><h2>Общение</h2><p>Ваши основные разделы внутри системы.</p></div></header>
    <nav>
     <a href="<?=e(app_url('/feed'))?>"><b>Лента</b><small>Публикации коллег и мои записи</small></a>
     <a href="<?=e(app_url('/messages'))?>"><b>Сообщения</b><small>Личная переписка</small></a>
     <a href="<?=e(app_url('/colleagues'))?>"><b>Коллеги</b><small>Контакты, подписчики и заявки</small></a>
    </nav>
   </article>

   <article class="account-card account-exit-card">
    <header><span>↪</span><div><h2>Сеанс</h2><p>Система не завершает вход автоматически.</p></div></header>
    <p>Выход выполняется только вручную. После выхода потребуется снова ввести логин и пароль.</p>
    <form method="post" action="<?=e(app_url('/logout'))?>">
     <?=csrf_field()?>
     <button class="btn account-logout-button" type="submit">Выйти из аккаунта</button>
    </form>
   </article>
  </section>
 </section>
</main>
