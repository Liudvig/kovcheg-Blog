<div class="page-head"><div><h1>Модули</h1><p>Независимые функции устанавливаются пакетами и не требуют изменения файлов KOVCHEG Blog.</p></div></div>
<section class="panel" style="margin-bottom:20px" data-upload-block>
 <h2>Установить модули формата 2</h2>
 <p>Перетащите один или несколько ZIP-пакетов. Каждый пакет должен содержать <code>manifest.json</code> и <code>bootstrap.php</code>; классы размещаются в <code>src</code>, маршруты — в <code>routes</code>, шаблоны — в <code>views</code>.</p>
 <form method="post" enctype="multipart/form-data" action="<?=e(app_url('/studio/modules/install'))?>">
  <?=csrf_field()?>
  <label class="upload-zone" data-upload-zone>
   <input type="file" name="packages[]" accept="application/zip,.zip" multiple required>
   <span class="upload-zone__icon">⬡</span><b>Перетащите ZIP-модули сюда</b><span>или нажмите, чтобы выбрать несколько пакетов</span><small>До 10 модулей за один раз, каждый не больше 100 МБ</small>
  </label>
  <div class="upload-selection" data-upload-selection></div><div class="upload-progress" data-upload-progress hidden><span></span></div>
  <label class="check-row" style="margin:14px 0"><input type="checkbox" name="enable" value="1" checked> Включить успешно установленные модули</label>
  <button class="button primary" type="submit">Проверить и установить</button>
 </form>
</section>
<?php if($modules):?><div class="module-grid"><?php foreach($modules as $module):?><article class="module-card"><div style="display:flex;justify-content:space-between;gap:12px"><span class="module-status <?=empty($module['files_ok'])?'broken':''?>"><?=e((string)$module['health'])?></span><small>v<?=e((string)$module['version'])?></small></div><h3><?=e((string)$module['name'])?></h3><p><?=e((string)($module['description']??''))?></p><small><code><?=e((string)$module['slug'])?></code> · формат <?=(int)($module['package_format']??1)?></small><div class="user-actions" style="margin-top:16px"><form method="post" action="<?=e(app_url('/studio/modules/'.rawurlencode((string)$module['slug']).'/toggle'))?>"><?=csrf_field()?><input type="hidden" name="enabled" value="<?=!empty($module['enabled'])?'0':'1'?>"><button class="button small"><?=!empty($module['enabled'])?'Отключить':'Включить'?></button></form><form method="post" data-confirm="Удалить модуль и его файлы? Данные таблиц модуля автоматически не удаляются." action="<?=e(app_url('/studio/modules/'.rawurlencode((string)$module['slug']).'/remove'))?>"><?=csrf_field()?><button class="button small danger">Удалить</button></form></div></article><?php endforeach;?></div><?php else:?><div class="empty-state"><h2>Модули пока не установлены</h2><p>KOVCHEG Blog готов принимать независимые расширения формата 2.</p></div><?php endif;?>