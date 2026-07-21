<?php
$showProfileBanner=(bool)($showProfileBanner??false);
if($showProfileBanner)require_once BASE_PATH.'/app/profile-banner.php';
$section=$section??'general';
$sections=[
 'general'=>['Профиль','Основные данные и фотография'],
 'privacy'=>['Приватность','Кто видит страницу и может добавить в коллеги'],
 'blacklist'=>['Чёрный список','Заблокированные пользователи и доступ к сообщениям'],
 'notifications'=>['Уведомления','Звук, Push и содержимое карточек'],
 'appearance'=>['Оформление','Тема интерфейса'],
 'security'=>['Безопасность','Пароль и текущий сеанс'],
 'permissions'=>['Мои права','Разрешения, назначенные администратором'],
];
[$heading,$subtitle]=$sections[$section]??$sections['general'];
?>
<main class="site-page-shell">
 <?=\Kovcheg\View::partial('site-sidebar',['active'=>'settings'])?>
 <section class="settings-page-060">
  <header class="settings-title"><div><h1><?=e($heading)?></h1><p><?=e($subtitle)?></p></div><a class="btn" href="<?=e(app_url('/profile'))?>">Профиль</a></header>
  <div class="settings-layout-060">
   <nav class="settings-menu-060"><?php foreach($sections as $key=>$item):?><a class="<?=$section===$key?'active':''?>" href="<?=e(app_url('/settings/'.$key))?>"><b><?=e($item[0])?></b><small><?=e($item[1])?></small></a><?php endforeach;?></nav>
   <article class="settings-content-060">
   <?php if($section==='general'):?>
    <div class="settings-profile-head"><?=avatar_html($user,'profile-avatar')?><div><h2><?=e($user['display_name'])?><?=verified_badge($user)?></h2><a href="<?=e(user_public_url((string)$user['username']))?>">@<?=e($user['username'])?></a></div></div>
    <form class="avatar-dropzone" method="post" enctype="multipart/form-data" action="<?=e(app_url('/profile/avatar'))?>" data-avatar-form><?=csrf_field()?>
     <input type="file" name="avatar" accept="image/jpeg,image/png,image/webp" hidden data-avatar-input><div class="drop-icon">📷</div><b>Перетащите фотографию сюда</b><span>или нажмите, чтобы выбрать и обрезать</span><button type="button" class="btn" data-avatar-choose>Выбрать фото</button>
    </form>
    <?php if($showProfileBanner):$profileBannerUrl=profile_banner_url((int)$user['id']);?>
    <section class="profile-banner-settings">
     <div class="profile-banner-preview <?=$profileBannerUrl!==''?'has-image':''?>" data-profile-banner-preview <?php if($profileBannerUrl!==''):?>style="background-image:url('<?=e($profileBannerUrl)?>')"<?php endif;?>><span><?=$profileBannerUrl!==''?'Текущий баннер':'Баннер профиля'?></span></div>
     <div><b>Баннер профиля</b><p>Широкое изображение над профилем в шаблоне X. Рекомендуемый размер — 1500×500 пикселей.</p></div>
     <form method="post" enctype="multipart/form-data" action="<?=e(app_url('/profile-banner-action.php?action=upload'))?>" data-profile-banner-form><?=csrf_field()?><input type="file" name="banner" accept="image/jpeg,image/png,image/webp" required data-profile-banner-input><button class="btn btn-primary" type="submit">Загрузить баннер</button></form>
     <?php if($profileBannerUrl!==''):?><form method="post" action="<?=e(app_url('/profile-banner-action.php?action=delete'))?>"><?=csrf_field()?><button class="btn" type="submit">Удалить баннер</button></form><?php endif;?>
    </section>
    <?php endif;?>
    <form class="settings-form" method="post" action="<?=e(app_url('/profile'))?>"><?=csrf_field()?>
     <div class="form-grid"><label>Имя<input name="first_name" value="<?=e($user['first_name']??'')?>" required></label><label>Фамилия<input name="last_name" value="<?=e($user['last_name']??'')?>" required></label></div>
     <div class="form-grid"><label>Ник<input name="username" value="<?=e($user['username']??'')?>" pattern="[a-z0-9_]{3,40}" required></label><label>Email<input type="email" name="email" value="<?=e($user['email'])?>" required></label></div>
     <label>Статус<input name="status_text" maxlength="190" value="<?=e($user['status_text']??'')?>" placeholder="Короткая фраза под именем"></label>
     <label>Дата рождения<input type="date" name="birthday" value="<?=e($user['birthday']??'')?>"><small class="field-help">Используется для уведомления коллег о дне рождения. Год можно скрыть настройками приватности в будущих обновлениях.</small></label>
     <label>О себе<textarea name="bio" rows="5" maxlength="1000"><?=e($user['bio']??'')?></textarea></label><button class="btn btn-primary">Сохранить профиль</button>
    </form>
   <?php elseif($section==='privacy'):?>
    <form class="settings-form" method="post" action="<?=e(app_url('/settings/privacy'))?>"><?=csrf_field()?>
     <label>Кто может смотреть мою страницу<select name="profile_visibility"><option value="everyone" <?=user_setting('profile_visibility','users')==='everyone'?'selected':''?>>Все, включая гостей</option><option value="users" <?=user_setting('profile_visibility','users')==='users'?'selected':''?>>Только зарегистрированные пользователи</option><option value="colleagues" <?=user_setting('profile_visibility','users')==='colleagues'?'selected':''?>>Только коллеги</option><option value="nobody" <?=user_setting('profile_visibility','users')==='nobody'?'selected':''?>>Только я</option></select></label>
     <label>Кто может отправлять заявку в коллеги<select name="contact_request_policy"><option value="everyone" <?=user_setting('contact_request_policy','everyone')==='everyone'?'selected':''?>>Все пользователи</option><option value="followers" <?=user_setting('contact_request_policy')==='followers'?'selected':''?>>Только мои подписчики</option><option value="nobody" <?=user_setting('contact_request_policy')==='nobody'?'selected':''?>>Никто</option></select></label>
     <label>Кто видит время последней активности<select name="last_seen_visibility"><option value="everyone" <?=user_setting('last_seen_visibility','colleagues')==='everyone'?'selected':''?>>Все пользователи</option><option value="colleagues" <?=user_setting('last_seen_visibility','colleagues')==='colleagues'?'selected':''?>>Только коллеги</option><option value="nobody" <?=user_setting('last_seen_visibility')==='nobody'?'selected':''?>>Никто</option></select></label>
     <label>Кто может писать на моей стене<select name="wall_post_policy"><option value="everyone" <?=user_setting('wall_post_policy','colleagues')==='everyone'?'selected':''?>>Все зарегистрированные</option><option value="colleagues" <?=user_setting('wall_post_policy','colleagues')==='colleagues'?'selected':''?>>Только коллеги</option><option value="only_me" <?=user_setting('wall_post_policy')==='only_me'?'selected':''?>>Только я</option></select></label>
     <p class="settings-hint">При отклонении заявки человек остаётся подписчиком, пока сам не отпишется или вы его не заблокируете.</p><button class="btn btn-primary">Сохранить приватность</button>
    </form>
   <?php elseif($section==='blacklist'):?>
    <div class="blacklist-page">
     <div class="settings-note"><b>Чёрный список</b><p>Заблокированный пользователь не сможет открыть вашу страницу, написать вам, отправить заявку или найти вас через поиск. История переписки сохраняется только у вас.</p></div>
     <div class="blacklist-list" data-blacklist-list>
      <?php foreach(($blockedUsers??[]) as $blocked):?><article class="blacklist-row" data-blocked-user="<?=(int)$blocked['id']?>"><?=avatar_html($blocked,'avatar-md')?><div><a href="<?=e(user_public_url((string)$blocked['username']))?>"><b><?=e($blocked['display_name'])?><?=verified_badge($blocked)?></b></a><small>@<?=e($blocked['username'])?> · заблокирован <?=e(human_time($blocked['created_at']))?></small></div><button type="button" class="btn" data-unblock-user="<?=(int)$blocked['id']?>">Разблокировать</button></article><?php endforeach;?>
      <?php if(empty($blockedUsers)):?><p class="muted" data-blacklist-empty>Чёрный список пуст.</p><?php endif;?>
     </div>
    </div>
   <?php elseif($section==='notifications'):?>
    <form class="settings-form" method="post" action="<?=e(app_url('/profile/settings'))?>"><?=csrf_field()?>
     <input type="hidden" name="settings_section" value="notifications"><input type="hidden" name="theme" value="<?=e(user_setting('theme',setting('default_theme','dark')))?>"><label class="switch-row"><span><b>Всплывающие уведомления</b><small>Карточки новых сообщений в нижней части окна</small></span><input type="checkbox" name="notifications_enabled" <?=user_setting('notifications_enabled','1')==='1'?'checked':''?>></label>
     <label class="switch-row"><span><b>Звук</b><small>Короткий сигнал при новом сообщении</small></span><input type="checkbox" name="notification_sound" <?=user_setting('notification_sound','1')==='1'?'checked':''?>></label>
     <label>Содержимое уведомления<select name="notification_preview"><option value="full" <?=user_setting('notification_preview','full')==='full'?'selected':''?>>Аватар, имя и текст</option><option value="sender" <?=user_setting('notification_preview')==='sender'?'selected':''?>>Имя без текста</option><option value="count" <?=user_setting('notification_preview')==='count'?'selected':''?>>Только количество</option><option value="hidden" <?=user_setting('notification_preview')==='hidden'?'selected':''?>>Ничего не показывать</option></select></label>
     <label class="switch-row"><span><b>Показывать аватар</b></span><input type="checkbox" name="notification_avatar" <?=user_setting('notification_avatar','1')==='1'?'checked':''?>></label><label>Одновременно на экране<input type="number" name="notification_max" min="1" max="5" value="<?=e(user_setting('notification_max','3'))?>"></label>
     <label class="switch-row"><span><b>Системные Push-уведомления</b></span><input type="checkbox" name="desktop_notifications" <?=user_setting('desktop_notifications','0')==='1'?'checked':''?>></label><div class="button-row"><button type="button" class="btn" data-request-notifications>Разрешить в браузере</button><button class="btn btn-primary">Сохранить</button></div>
    </form>
   <?php elseif($section==='appearance'):?>
    <form class="settings-form" method="post" action="<?=e(app_url('/profile/settings'))?>"><?=csrf_field()?><input type="hidden" name="settings_section" value="appearance">
     <input type="hidden" name="notifications_enabled" value="<?=user_setting('notifications_enabled','1')?>"><input type="hidden" name="notification_sound" value="<?=user_setting('notification_sound','1')?>"><input type="hidden" name="notification_preview" value="<?=e(user_setting('notification_preview','full'))?>"><input type="hidden" name="notification_avatar" value="<?=user_setting('notification_avatar','1')?>"><input type="hidden" name="notification_max" value="<?=e(user_setting('notification_max','3'))?>"><input type="hidden" name="desktop_notifications" value="<?=user_setting('desktop_notifications','0')?>">
     <label>Тема<select name="theme" data-theme-select><option value="dark" <?=user_setting('theme',setting('default_theme','dark'))==='dark'?'selected':''?>>Тёмная</option><option value="black" <?=user_setting('theme')==='black'?'selected':''?>>Чёрная</option><option value="light" <?=user_setting('theme')==='light'?'selected':''?>>Светлая</option></select></label>
     <label class="switch-row"><span><b>Правая колонка профиля</b><small>Показывать блоки коллег, групп, логотипов и будущих модулей.</small></span><input type="checkbox" name="profile_right_column" value="1" <?=user_setting('profile_right_column','1')==='1'?'checked':''?>></label><label>Город для блока погоды<input name="weather_city" maxlength="120" value="<?=e(user_setting('weather_city',''))?>" placeholder="Например: Никольское 3-е, Воронежская область"><small class="field-help">Можно указать населённый пункт с областью или координаты. Блок появится в правой колонке, если администратор включил его.</small></label>
     <div class="theme-preview"><span></span><div><i></i><i></i></div></div><button class="btn btn-primary">Сохранить оформление</button>
    </form>
   <?php elseif($section==='security'):?>
    <div class="security-info"><div><b>Текущий вход</b><span><?=e($_SERVER['REMOTE_ADDR']??'неизвестно')?> · <?=e($_SERVER['HTTP_USER_AGENT']??'браузер')?></span></div><div><b>Последняя активность</b><span><?=e($user['last_seen_at']??'сейчас')?></span></div></div>
    <form class="settings-form" method="post" action="<?=e(app_url('/profile/security'))?>"><?=csrf_field()?>
     <label>Текущий пароль<input type="password" name="current_password" autocomplete="current-password" required></label><label>Новый пароль<input type="password" name="new_password" minlength="10" autocomplete="new-password" required></label><label>Повторите новый пароль<input type="password" name="new_password_confirmation" minlength="10" required></label><button class="btn btn-primary">Сменить пароль и обновить сеанс</button>
    </form>
   <?php else:?>
    <div class="permission-list"><?php foreach($permissionLabels as $key=>$label):?><div><span><?=$permissions[$key]?'✓':'—'?></span><b><?=e($label)?></b></div><?php endforeach;?></div><p class="muted">Права назначаются администратором и применяются сразу.</p>
   <?php endif;?>
   </article>
  </div>
 </section>
</main>
<?php if($section==='general'):?><div class="crop-modal" hidden data-crop-modal><div class="crop-card"><h2>Обрезать фотографию</h2><div class="crop-stage"><img alt="Предпросмотр" data-crop-image></div><label>Масштаб<input type="range" min="1" max="3" step="0.01" value="1" data-crop-scale></label><div class="button-row"><button type="button" class="btn" data-crop-cancel>Отмена</button><button type="button" class="btn btn-primary" data-crop-apply>Обрезать и загрузить</button></div></div></div><?php endif;?>
