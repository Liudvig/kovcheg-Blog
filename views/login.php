<?php $siteName=setting('site_name','KOVCHEG CMS');$logoPath=(string)setting('logo_path',''); ?>
<main class="auth-split-page">
 <section class="auth-split-form">
  <div class="auth-form-card">
   <div class="auth-brand-row">
    <div class="logo-large"><img src="<?=e(app_url('/brand/logo?v='.APP_VERSION))?>" alt="<?=e($siteName)?>"></div>
    <div><h1><?=e($siteName)?></h1><p>Рабочая платформа для вашей команды</p></div>
   </div>
   <h2>Вход</h2><p class="auth-lead">Откройте сообщения, документы и рабочую ленту.</p>
   <form method="post" action="<?=e(app_url('/login'))?>"><?=csrf_field()?>
    <label>Email или ник<input name="login" autocomplete="username" placeholder="name@example.com или nik" required></label>
    <label>Пароль<input type="password" name="password" autocomplete="current-password" placeholder="Введите пароль" required></label>
    <button class="btn btn-primary">Войти</button>
   </form>
   <?php if(setting('registration_mode','closed')==='email_approval'):?><p class="auth-link">Нет аккаунта? <a href="<?=e(app_url('/register'))?>">Подать заявку на регистрацию</a><br><small>Доступ активирует администратор.</small></p><?php endif;?>
  </div>
 </section>
 <aside class="auth-split-promo"><div class="auth-promo-content"><small>Copyright KOVCHEG CMS</small><h2>Ваше закрытое пространство для общения и совместной работы.</h2><p>Личные переписки, голосовые сообщения, лента, документы и управление доступом работают на вашем сервере. Данные и правила остаются под вашим контролем.</p><div class="auth-feature-grid"><div class="auth-feature"><b>Живое общение</b><span>Личные сообщения, голосовые и рабочие обсуждения всегда собраны в одном месте.</span></div><div class="auth-feature"><b>Рабочие файлы</b><span>Передача документов и фотографий с защищённым доступом.</span></div><div class="auth-feature"><b>Закрытый доступ</b><span>Регистрации проверяются администратором, права задаются отдельно.</span></div><div class="auth-feature"><b>Модульное развитие</b><span>Новые функции устанавливаются через административную панель.</span></div><div class="auth-feature"><b>Приватность</b><span>Чёрный список, гибкая видимость профиля и контроль контактов.</span></div><div class="auth-feature"><b>На любом устройстве</b><span>Адаптивный интерфейс, PWA и прямые Push-уведомления.</span></div></div></div></aside>
</main>
