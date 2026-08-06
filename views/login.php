<?php
$configuredName=trim((string)setting('site_name',''));
$legacyNames=['','KOVCHEG CMS','KOVCHEG Core','KOVCHEG Blog Core'];
$siteName=in_array($configuredName,$legacyNames,true)?'KOVCHEG CMS':$configuredName;
$tagline=trim((string)setting('site_tagline',setting('blog_tagline','')));
if($tagline==='')$tagline='Управление сайтом и публикациями';
$loginHeading=trim((string)setting('login_heading','Всё управление сайтом — в одном месте.'));
$accent=(string)setting('brand_accent','#2563eb');
if(!preg_match('/^#[0-9a-fA-F]{6}$/',$accent))$accent='#2563eb';
$hasBackground=trim((string)setting('login_background_path',''))!=='';
$loginError=(string)($_SESSION['flash_error']??'');
$loginSuccess=(string)($_SESSION['flash_success']??'');
unset($_SESSION['flash_error'],$_SESSION['flash_success']);
\Kovcheg\Hooks::on('layout.head',static function($html){
    return (string)$html.'<link rel="stylesheet" href="'.e(app_url('/assets/css/blog-login.css?v='.rawurlencode(ASSET_REVISION))).'">';
});
?>
<main class="blog-login-page" style="--brand-accent:<?=e($accent)?>">
 <section class="blog-login-shell" aria-labelledby="blog-login-title">
  <header class="blog-login-topbar">
   <a class="blog-login-brand" href="<?=e(app_url('/'))?>">
    <img src="<?=e(app_url('/brand/logo?v='.rawurlencode(ASSET_REVISION)))?>" alt="">
    <span><b><?=e($siteName)?></b><small>KOVCHEG CMS <?=e(APP_VERSION)?></small></span>
   </a>
   <a class="blog-login-site-link" href="<?=e(app_url('/'))?>"><b>←</b><span>Вернуться на сайт</span></a>
  </header>

  <div class="blog-login-grid">
   <aside class="blog-login-visual <?=$hasBackground?'has-brand-background':''?>" <?=$hasBackground?'style="background-image:linear-gradient(145deg,rgba(9,18,31,.86),rgba(20,45,68,.72)),url(\''.e(app_url('/brand/login-background?v='.rawurlencode(ASSET_REVISION))).'\')"':''?>>
    <div class="blog-login-visual-content">
     <span class="blog-login-eyebrow"><?=e($siteName)?></span>
     <h1><?=e($loginHeading)?></h1>
     <p><?=e($tagline)?></p>
     <div class="blog-login-capabilities" aria-label="Возможности сайта">
      <div class="blog-login-capability"><i>✎</i><span>Записи и страницы</span></div>
      <div class="blog-login-capability"><i>☷</i><span>Меню и виджеты</span></div>
      <div class="blog-login-capability"><i>◇</i><span>Собственный бренд</span></div>
     </div>
    </div>
    <footer class="blog-login-visual-footer"><b><?=e($siteName)?></b><span><?=e($tagline)?></span></footer>
   </aside>

   <section class="blog-login-form-panel">
    <div class="blog-login-form-wrap">
     <header class="blog-login-form-heading">
      <span>Защищённый вход</span>
      <h2 id="blog-login-title">Вход</h2>
      <p>Введите данные своей учётной записи.</p>
     </header>

     <?php if($loginError!==''):?><div class="blog-login-alert blog-login-alert--error" role="alert"><?=e($loginError)?></div><?php endif;?>
     <?php if($loginSuccess!==''):?><div class="blog-login-alert blog-login-alert--success" role="status"><?=e($loginSuccess)?></div><?php endif;?>

     <form class="blog-login-form" method="post" action="<?=e(app_url('/login'))?>">
      <?=csrf_field()?>
      <label class="blog-login-field"><span>Email или ник</span><input name="login" autocomplete="username" placeholder="name@example.com или nik" required autofocus></label>
      <label class="blog-login-field"><span>Пароль</span><input type="password" name="password" autocomplete="current-password" placeholder="Введите пароль" required></label>
      <button class="blog-login-submit" type="submit">Войти</button>
     </form>

     <?php if(setting('registration_mode','closed')!=='closed'):?><p class="blog-login-register">Нет аккаунта? <a href="<?=e(app_url('/register'))?>"><?=setting('registration_mode','closed')==='email_auto'?'Зарегистрироваться':'Подать заявку'?></a></p><?php endif;?>
     <p class="blog-login-form-copyright">© <?=date('Y')?> Ланцет Семён Борисович · Автор и правообладатель · Все права защищены</p>
    </div>
   </section>
  </div>
 </section>
</main>
