<?php
use Kovcheg\Auth;
use Kovcheg\Blog\Studio;

$pageTitle = trim((string)($title ?? ''));
$metaDescription = trim((string)($description ?? setting('seo_description', 'Авторский блог, проекты и портфолио.')));
$canonical = current_absolute_url();
$indexing = setting('search_indexing', '0') === '1';
$logo = app_url('/brand/logo?v='.rawurlencode(APP_VERSION));
$favicon = app_url('/brand/favicon?v='.rawurlencode(APP_VERSION));
$flash = [];
if (!empty($_SESSION['flash_error'])) {
    $flash[] = ['type' => 'error', 'text' => (string)$_SESSION['flash_error']];
    unset($_SESSION['flash_error']);
}
if (!empty($_SESSION['flash_success'])) {
    $flash[] = ['type' => 'success', 'text' => (string)$_SESSION['flash_success']];
    unset($_SESSION['flash_success']);
}
?><!doctype html>
<html lang="ru">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<meta name="theme-color" content="#f4f3ef">
<meta name="description" content="<?=e($metaDescription)?>">
<meta name="robots" content="<?=$indexing?'index,follow,max-image-preview:large':'noindex,nofollow,noarchive'?>">
<link rel="canonical" href="<?=e($canonical)?>">
<meta property="og:site_name" content="<?=e($siteName)?>">
<meta property="og:title" content="<?=e($pageTitle !== '' ? $pageTitle : $siteName)?>">
<meta property="og:description" content="<?=e($metaDescription)?>">
<meta property="og:url" content="<?=e($canonical)?>">
<meta property="og:type" content="website">
<meta name="twitter:card" content="summary_large_image">
<title><?=e($pageTitle !== '' ? $pageTitle.' — '.$siteName : $siteName)?></title>
<link rel="icon" href="<?=e($favicon)?>">
<link rel="stylesheet" href="<?=e($themeAsset('theme.css').'?v='.rawurlencode(ASSET_REVISION))?>">
<?=\Kovcheg\Hooks::fire('blog.layout.head', '')?>
</head>
<body class="blog-theme blog-theme-editorial">
<a class="skip-link" href="#main-content">Перейти к содержанию</a>
<header class="site-header">
  <div class="site-header__inner">
    <a class="site-brand" href="<?=e(app_url('/'))?>" aria-label="<?=e($siteName)?>">
      <img src="<?=e($logo)?>" alt="" class="site-brand__logo">
      <span class="site-brand__text">
        <b><?=e($siteName)?></b>
        <small><?=e(setting('blog_tagline', 'Разработки · проекты · опыт'))?></small>
      </span>
    </a>

    <button class="site-menu-button" type="button" aria-expanded="false" aria-controls="site-navigation" data-site-menu-button>Меню</button>

    <nav class="site-navigation" id="site-navigation" aria-label="Главное меню" data-site-navigation>
      <?php foreach ($menuItems as $item):
        $itemUrl = trim((string)($item['url'] ?? '/'));
        if ($itemUrl === '') $itemUrl = '/';
        if (!preg_match('~^(?:https?:)?//~i', $itemUrl)) $itemUrl = app_url('/'.ltrim($itemUrl, '/'));
      ?>
        <a href="<?=e($itemUrl)?>"><?=e((string)($item['label'] ?? 'Раздел'))?></a>
      <?php endforeach; ?>
    </nav>

    <div class="site-account">
      <?php if (Auth::check()): ?>
        <a class="site-account__profile" href="<?=e(app_url('/profile'))?>"><?=avatar_html($currentUser, 'avatar-xs')?> <span><?=e((string)($currentUser['display_name'] ?? 'Профиль'))?></span></a>
        <?php if (Studio::can('comments')): ?><a class="button button--quiet" href="<?=e(app_url('/studio'))?>">Studio</a><?php endif; ?>
      <?php else: ?>
        <a href="<?=e(app_url('/login'))?>">Войти</a>
        <a class="button button--dark" href="<?=e(app_url('/register'))?>">Регистрация</a>
      <?php endif; ?>
    </div>
  </div>
</header>

<?php if ($flash): ?>
<div class="flash-stack" aria-live="polite">
  <?php foreach ($flash as $message): ?><div class="flash flash--<?=e($message['type'])?>"><?=e($message['text'])?></div><?php endforeach; ?>
</div>
<?php endif; ?>

<main id="main-content" class="site-main">
<?=$content?>
</main>

<footer class="site-footer">
  <div class="site-footer__inner">
    <div>
      <b><?=e($siteName)?></b>
      <p><?=e(setting('blog_footer_text', 'Авторский сайт, созданный на KOVCHEG Blog.'))?></p>
    </div>
    <div class="site-footer__meta">
      <span><?=e(setting('copyright', '© '.date('Y').' KOVCHEG CMS'))?></span>
      <span>Автор системы: Ланцет Семён Борисович</span>
    </div>
  </div>
</footer>

<script nonce="<?=e((string)($GLOBALS['CSP_NONCE'] ?? ''))?>">
(() => {
  const button = document.querySelector('[data-site-menu-button]');
  const nav = document.querySelector('[data-site-navigation]');
  if (!button || !nav) return;
  button.addEventListener('click', () => {
    const open = button.getAttribute('aria-expanded') === 'true';
    button.setAttribute('aria-expanded', open ? 'false' : 'true');
    nav.classList.toggle('is-open', !open);
  });
})();
</script>
<?=\Kovcheg\Hooks::fire('blog.layout.scripts', '')?>
</body>
</html>
