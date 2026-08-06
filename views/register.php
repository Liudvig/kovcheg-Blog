<?php
$configuredName=trim((string)setting('site_name',''));
$siteName=in_array($configuredName,['','KOVCHEG CMS','KOVCHEG Core','KOVCHEG Blog Core'],true)?'KOVCHEG CMS':$configuredName;
$tagline=trim((string)setting('site_tagline',setting('blog_tagline','')));
if($tagline==='')$tagline='Управление сайтом и публикациями';
$mode=$registrationMode??registration_mode();
$captcha=$captcha??[];
$accent=(string)setting('brand_accent','#2563eb');
if(!preg_match('/^#[0-9a-fA-F]{6}$/',$accent))$accent='#2563eb';
$hasBackground=trim((string)setting('login_background_path',''))!=='';
\Kovcheg\Hooks::on('layout.head',static function($html){
    return (string)$html.'<link rel="stylesheet" href="'.e(app_url('/assets/css/blog-login.css?v='.rawurlencode(ASSET_REVISION))).'">';
});
?>
<main class="blog-login-page blog-register-page" style="--brand-accent:<?=e($accent)?>">
 <section class="blog-login-shell" aria-labelledby="register-title">
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
     <h1><?=e($mode==='email_auto'?'Создайте учётную запись':'Подайте заявку на доступ')?></h1>
     <p><?=e($tagline)?></p>
     <div class="blog-login-capabilities" aria-label="Возможности аккаунта">
      <div class="blog-login-capability"><i>◎</i><span>Личный кабинет</span></div>
      <div class="blog-login-capability"><i>◌</i><span>Комментарии</span></div>
      <div class="blog-login-capability"><i>◇</i><span>Единый стиль сайта</span></div>
     </div>
    </div>
    <footer class="blog-login-visual-footer"><b><?=e($siteName)?></b><span><?=e($tagline)?></span></footer>
   </aside>

   <section class="blog-login-form-panel blog-register-form-panel">
    <div class="blog-login-form-wrap blog-register-form-wrap">
     <?php if($registered):?>
      <header class="blog-login-form-heading">
       <span>Заявка принята</span>
       <h2 id="register-title">Регистрация отправлена</h2>
       <p>Администратор проверит данные и активирует аккаунт.</p>
      </header>
      <a class="blog-login-submit blog-register-return" href="<?=e(app_url('/login'))?>">Вернуться ко входу</a>
     <?php else:?>
      <header class="blog-login-form-heading">
       <span><?=e($mode==='email_auto'?'Открытая регистрация':'Регистрация по заявке')?></span>
       <h2 id="register-title">Создать аккаунт</h2>
       <p><?=e($mode==='email_auto'?'После проверки защиты аккаунт станет доступен сразу.':'Доступ появится после одобрения администратором.')?></p>
      </header>

      <form class="blog-login-form blog-register-form" method="post" action="<?=e(app_url('/register'))?>">
       <?=csrf_field()?>
       <input class="captcha-honeypot" type="text" name="website" tabindex="-1" autocomplete="off" aria-hidden="true">
       <div class="blog-register-grid">
        <label class="blog-login-field"><span>Имя</span><input name="first_name" required maxlength="80" autocomplete="given-name"></label>
        <label class="blog-login-field"><span>Фамилия</span><input name="last_name" required maxlength="80" autocomplete="family-name"></label>
       </div>
       <label class="blog-login-field"><span>Email</span><input type="email" name="email" autocomplete="email" required></label>
       <label class="blog-login-field"><span>Уникальный ник</span><input name="username" pattern="[a-z0-9_]{3,40}" required maxlength="40" placeholder="my_nik"><small>Адрес профиля: @my_nik</small></label>
       <div class="blog-register-grid">
        <label class="blog-login-field"><span>Пароль от 10 символов</span><input type="password" name="password" minlength="10" autocomplete="new-password" required></label>
        <label class="blog-login-field"><span>Повторите пароль</span><input type="password" name="password_confirmation" minlength="10" autocomplete="new-password" required></label>
       </div>
       <?php if(($captcha['provider']??'builtin')==='turnstile'&&!empty($captcha['site_key'])):?>
        <div class="cf-turnstile" data-sitekey="<?=e($captcha['site_key'])?>"></div><script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
       <?php else:?>
        <label class="blog-login-field"><span>Проверка: <?=e($captcha['question']??'')?></span><input name="captcha_answer" inputmode="numeric" required autocomplete="off"></label>
       <?php endif;?>
       <button class="blog-login-submit" type="submit"><?=e($mode==='email_auto'?'Создать аккаунт':'Отправить заявку')?></button>
      </form>
      <p class="blog-login-register">Уже зарегистрированы? <a href="<?=e(app_url('/login'))?>">Войти</a></p>
     <?php endif;?>
     <p class="blog-login-form-copyright">© <?=date('Y')?> Ланцет Семён Борисович · Автор и правообладатель · Все права защищены</p>
    </div>
   </section>
  </div>
 </section>
</main>
