<?php
$configuredName=trim((string)setting('site_name',''));
$legacyNames=['','KOVCHEG CMS','KOVCHEG Core','KOVCHEG Blog Core'];
$siteName=in_array($configuredName,$legacyNames,true)?'KOVCHEG Blog':$configuredName;
$tagline=trim((string)setting('site_tagline',''));
if($tagline==='')$tagline='Управление сайтом, публикациями и оформлением';
$loginError=(string)($_SESSION['flash_error']??'');
$loginSuccess=(string)($_SESSION['flash_success']??'');
unset($_SESSION['flash_error'],$_SESSION['flash_success']);
\Kovcheg\Hooks::on('layout.head',static function($html){
    return (string)$html.'<link rel="stylesheet" href="'.e(app_url('/assets/css/blog-login.css?v='.rawurlencode(ASSET_REVISION))).'">';
});
?>
<main class="blog-login-page">
 <section class="blog-login-shell" aria-labelledby="blog-login-title">
  <header class="blog-login-topbar">
   <a class="blog-login-brand" href="<?=e(app_url('/'))?>">
    <img src="<?=e(app_url('/brand/logo?v='.rawurlencode(APP_VERSION)))?>" alt="">
    <span><b><?=e($siteName)?></b><small>KOVCHEG Blog <?=e(APP_VERSION)?></small></span>
   </a>
   <a class="blog-login-site-link" href="<?=e(app_url('/'))?>"><b>←</b><span>Вернуться на сайт</span></a>
  </header>

  <div class="blog-login-grid">
   <aside class="blog-login-visual">
    <div class="blog-login-visual-content">
     <span class="blog-login-eyebrow">KOVCHEG Studio</span>
     <h1>Всё управление сайтом — в одном месте.</h1>
     <p>Материалы, страницы, портфолио, меню, виджеты и SEO работают в единой системе без перехода в старую социальную ленту.</p>
     <div class="blog-login-capabilities" aria-label="Возможности Studio">
      <div class="blog-login-capability"><i>✎</i><span>Материалы</span></div>
      <div class="blog-login-capability"><i>▦</i><span>Виджеты и зоны</span></div>
      <div class="blog-login-capability"><i>↗</i><span>SEO и рост</span></div>
     </div>
    </div>
    <footer class="blog-login-visual-footer"><b><?=e($siteName)?></b><span><?=e($tagline)?></span></footer>
   </aside>

   <section class="blog-login-form-panel">
    <div class="blog-login-form-wrap">
     <header class="blog-login-form-heading">
      <span>Защищённый вход</span>
      <h2 id="blog-login-title">Вход в Studio</h2>
      <p>После входа владелец и администратор сразу переходят в панель управления сайтом.</p>
     </header>

     <?php if($loginError!==''):?><div class="blog-login-alert blog-login-alert--error" role="alert"><?=e($loginError)?></div><?php endif;?>
     <?php if($loginSuccess!==''):?><div class="blog-login-alert blog-login-alert--success" role="status"><?=e($loginSuccess)?></div><?php endif;?>

     <form class="blog-login-form" method="post" action="<?=e(app_url('/login'))?>">
      <?=csrf_field()?>
      <label class="blog-login-field"><span>Email или ник</span><input name="login" autocomplete="username" placeholder="name@example.com или nik" required autofocus></label>
      <label class="blog-login-field"><span>Пароль</span><input type="password" name="password" autocomplete="current-password" placeholder="Введите пароль" required></label>
      <button class="blog-login-submit" type="submit">Войти в KOVCHEG Studio</button>
     </form>

     <?php if(setting('registration_mode','closed')==='email_approval'):?><p class="blog-login-register">Нет аккаунта? <a href="<?=e(app_url('/register'))?>">Подать заявку</a></p><?php endif;?>
     <p class="blog-login-form-copyright">© <?=date('Y')?> Ланцет Семён Борисович · Автор и правообладатель · Все права защищены</p>
    </div>
   </section>
  </div>
 </section>
</main>
