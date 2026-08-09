<?php
use Kovcheg\Csrf;

$accountStats = array_merge(['materials'=>0,'comments'=>0], $accountStats ?? []);
$studioAllowed = (bool)($studioAllowed ?? false);
$siteName = trim((string)setting('site_name','KOVCHEG Blog')) ?: 'KOVCHEG Blog';
$logo = app_url('/brand/logo?v='.rawurlencode(APP_VERSION));
$copyright = '© '.date('Y').' Ланцет Семён Борисович';
$roleLabels = [
    'owner'=>'Владелец',
    'admin'=>'Администратор',
    'editor'=>'Редактор',
    'moderator'=>'Модератор',
    'user'=>'Пользователь',
];
$role = (string)($user['role'] ?? 'user');
$approval = (string)($user['approval_status'] ?? 'approved');
?><!doctype html>
<html lang="ru">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<meta name="robots" content="noindex,nofollow,noarchive">
<meta name="csrf-token" content="<?=e(Csrf::token())?>">
<title>Личный кабинет — <?=e($siteName)?></title>
<link rel="stylesheet" href="<?=e(app_url('/themes/kovcheg-portal/assets/theme.css?v='.rawurlencode(ASSET_REVISION)))?>">
<link rel="stylesheet" href="<?=e(app_url('/themes/kovcheg-portal/assets/fixed-shell.css?v='.rawurlencode(ASSET_REVISION)))?>">
<link rel="stylesheet" href="<?=e(app_url('/themes/kovcheg-portal/assets/blog-compact.css?v='.rawurlencode(ASSET_REVISION)))?>">
<link rel="stylesheet" href="<?=e(app_url('/assets/css/blog-profile-portal.css?v='.rawurlencode(ASSET_REVISION)))?>">
</head>
<body class="portal-account-body">
<header class="portal-account-header">
 <div class="portal-account-header__inner">
  <a class="portal-account-brand" href="<?=e(app_url('/'))?>">
   <img src="<?=e($logo)?>" alt="">
   <span><b><?=e($siteName)?></b><small><?=e(setting('blog_tagline','Записи · страницы · рубрики'))?></small></span>
  </a>
  <nav class="portal-account-nav" aria-label="Основная навигация">
   <a href="<?=e(app_url('/'))?>">Главная</a>
   <a class="active" href="<?=e(app_url('/account'))?>">Кабинет</a>
   <?php if($studioAllowed):?><a href="<?=e(app_url('/studio'))?>">Studio</a><?php endif;?>
  </nav>
  <div class="portal-account-actions">
   <form method="post" action="<?=e(app_url('/logout'))?>"><?=csrf_field()?><button type="submit">Выйти</button></form>
  </div>
 </div>
</header>

<main class="portal-account-main">
 <section class="portal-account-hero">
  <div class="portal-account-identity">
   <?=avatar_html($user,'profile-avatar')?>
   <div>
    <span class="portal-account-kicker">ЛИЧНЫЙ КАБИНЕТ</span>
    <h1><?=e((string)($user['display_name']??'Пользователь'))?><?=verified_badge($user)?></h1>
    <p>@<?=e((string)($user['username']??'user'))?> · <?=e((string)($user['email']??''))?></p>
   </div>
  </div>
  <div class="portal-account-hero__buttons">
   <a class="portal-account-button primary" href="<?=e(app_url('/'))?>">Открыть сайт</a>
   <?php if($studioAllowed):?><a class="portal-account-button" href="<?=e(app_url('/studio'))?>">KOVCHEG Studio</a><?php endif;?>
  </div>
 </section>

 <section class="portal-account-stats" aria-label="Статистика пользователя">
  <article><strong><?=e((string)$accountStats['materials'])?></strong><span>Материалов</span></article>
  <article><strong><?=e((string)$accountStats['comments'])?></strong><span>Комментариев</span></article>
  <article><strong><?=e((string)($roleLabels[$role] ?? $role))?></strong><span>Роль</span></article>
  <article><strong><?=e($approval==='approved'?'Активен':'Ожидает')?></strong><span>Статус аккаунта</span></article>
 </section>

 <section class="portal-account-grid">
  <article class="portal-account-card">
   <h2>Учётная запись</h2>
   <nav>
    <span><b>Имя</b><small><?=e((string)($user['display_name']??''))?></small></span>
    <span><b>Email</b><small><?=e((string)($user['email']??''))?></small></span>
    <span><b>Ник</b><small>@<?=e((string)($user['username']??''))?></small></span>
   </nav>
  </article>
  <article class="portal-account-card">
   <h2>KOVCHEG Blog</h2>
   <nav>
    <a href="<?=e(app_url('/'))?>"><span><b>Публичный сайт</b><small>Записи, рубрики и страницы</small></span><span>→</span></a>
    <?php if($studioAllowed):?><a href="<?=e(app_url('/studio'))?>"><span><b>KOVCHEG Studio</b><small>Управление сайтом и публикациями</small></span><span>→</span></a><?php endif;?>
   </nav>
  </article>
 </section>
</main>

<footer class="portal-account-footer"><span><?=e($copyright)?></span><span>KOVCHEG Blog <?=e(APP_VERSION)?> · Все права защищены</span></footer>
</body>
</html>