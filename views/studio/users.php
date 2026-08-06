<div class="page-head users-page-head">
 <div><h1>Пользователи</h1><p>Роли, доступ и состояние учётных записей.</p></div>
 <form method="get" action="<?=e(app_url('/studio/users'))?>" class="users-search"><input name="q" value="<?=e($search)?>" placeholder="Имя, логин или email"><button class="button">Найти</button></form>
</div>

<div class="user-grid">
<?php foreach($users as $user):?>
 <article class="user-card">
  <header class="user-card__head">
   <?=avatar_html($user,'avatar-xs')?>
   <div><h3><?=e((string)$user['display_name'])?></h3><small>@<?=e((string)($user['username']??''))?></small></div>
   <span class="role-badge"><?=e((string)$user['role'])?></span>
  </header>
  <dl>
   <dt>Email</dt><dd><?=e((string)$user['email'])?></dd>
   <dt>Статус</dt><dd><?=!empty($user['is_active'])?'Активен':'Заблокирован'?> · <?=e((string)$user['approval_status'])?></dd>
   <dt>Создан</dt><dd><?=e((string)$user['created_at'])?></dd>
   <dt>Вход</dt><dd><?=e((string)($user['last_seen_at']??'—'))?></dd>
  </dl>
  <div class="user-actions">
   <form method="post" action="<?=e(app_url('/studio/users/'.(int)$user['id'].'/role'))?>">
    <?=csrf_field()?>
    <input type="hidden" name="return_q" value="<?=e($search)?>">
    <select name="role" aria-label="Роль пользователя">
     <?php foreach(['owner'=>'Владелец','admin'=>'Администратор','editor'=>'Редактор','moderator'=>'Модератор','user'=>'Пользователь'] as $role=>$label):?><option value="<?=e($role)?>" <?=$user['role']===$role?'selected':''?>><?=e($label)?></option><?php endforeach;?>
    </select>
    <button class="button small">Сохранить роль</button>
   </form>
   <?php if((int)$user['id']!==\Kovcheg\Auth::id()):?>
    <form method="post" action="<?=e(app_url('/studio/users/'.(int)$user['id'].'/status'))?>">
     <?=csrf_field()?>
     <input type="hidden" name="return_q" value="<?=e($search)?>">
     <input type="hidden" name="active" value="<?=!empty($user['is_active'])?'0':'1'?>">
     <button class="button small <?=!empty($user['is_active'])?'danger':''?>"><?=!empty($user['is_active'])?'Заблокировать':'Разблокировать'?></button>
    </form>
   <?php endif;?>
  </div>
 </article>
<?php endforeach;?>
</div>

<?php if(!$users):?><div class="empty-state"><h2>Пользователи не найдены</h2><p>Измените поисковый запрос.</p></div><?php endif;?>

<?php if($roleHistory):?>
<section class="panel history-table"><h2>Последние изменения ролей</h2><div class="table-wrap"><table><thead><tr><th>Пользователь</th><th>Было</th><th>Стало</th><th>Кто изменил</th><th>Дата</th></tr></thead><tbody><?php foreach($roleHistory as $row):?><tr><td><?=e((string)$row['user_name'])?></td><td><?=e((string)$row['previous_role'])?></td><td><?=e((string)$row['new_role'])?></td><td><?=e((string)($row['actor_name']??'Система'))?></td><td><?=e((string)$row['created_at'])?></td></tr><?php endforeach;?></tbody></table></div></section>
<?php endif;?>
