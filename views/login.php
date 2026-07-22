<?php
$configuredName=trim((string)setting('site_name',''));
$legacyNames=['','KOVCHEG CMS','KOVCHEG Core','KOVCHEG Blog Core'];
$siteName=in_array($configuredName,$legacyNames,true)?'KOVCHEG Blog':$configuredName;
?>
<main class="auth-split-page blog-auth-page">
 <section class="auth-split-form">
  <div class="auth-form-card">
   <div class="auth-brand-row">
    <div class="logo-large"><img src="<?=e(app_url('/brand/logo?v='.APP_VERSION))?>" alt="<?=e($siteName)?>"></div>
    <div><h1><?=e($siteName)?></h1><p>Управление сайтом и публикациями</p></div>
   </div>
   <h2>Вход в Studio</h2>
   <p class="auth-lead">Откройте статьи, страницы, портфолио, SEO, меню, виджеты и настройки оформления.</p>
   <form method="post" action="<?=e(app_url('/login'))?>"><?=csrf_field()?>
    <label>Email или ник<input name="login" autocomplete="username" placeholder="name@example.com или nik" required></label>
    <label>Пароль<input type="password" name="password" autocomplete="current-password" placeholder="Введите пароль" required></label>
    <button class="btn btn-primary">Войти</button>
   </form>
   <?php if(setting('registration_mode','closed')==='email_approval'):?><p class="auth-link">Нет аккаунта? <a href="<?=e(app_url('/register'))?>">Подать заявку на регистрацию</a><br><small>Доступ активирует администратор.</small></p><?php endif;?>
  </div>
 </section>
 <aside class="auth-split-promo">
  <div class="auth-promo-content">
   <small>KOVCHEG Blog Studio</small>
   <h2>Сайт, блог и портфолио управляются в одном месте.</h2>
   <p>Создавайте материалы, собирайте страницы из блоков, настраивайте SEO и размещайте виджеты без изменения программного кода.</p>
   <div class="auth-feature-grid">
    <div class="auth-feature"><b>Публикации</b><span>Статьи, страницы и проекты портфолио с отложенным выпуском.</span></div>
    <div class="auth-feature"><b>Визуальный редактор</b><span>Блоки, секции и структура страницы без ручной правки шаблонов.</span></div>
    <div class="auth-feature"><b>Меню и виджеты</b><span>Перемещение элементов между шапкой, колонками и подвалом.</span></div>
    <div class="auth-feature"><b>SEO и рост</b><span>Читаемые URL, sitemap, RSS, редиректы и подписчики.</span></div>
    <div class="auth-feature"><b>Модульная система</b><span>Новые возможности подключаются отдельными модулями и плагинами.</span></div>
    <div class="auth-feature"><b>Ваш сервер</b><span>Материалы, настройки и резервные копии остаются под вашим контролем.</span></div>
   </div>
  </div>
 </aside>
</main>