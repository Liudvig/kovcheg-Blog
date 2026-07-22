<div class="page-head"><div><h1>Шаблоны секций</h1><p>Готовые части страниц для лендингов, портфолио, услуг и релизов.</p></div><a class="button primary" href="<?=e(app_url('/studio/content/new?type=page'))?>">Открыть конструктор</a></div>
<div class="pattern-grid">
<?php foreach($patterns as $pattern):?>
 <article class="pattern-card">
  <span class="role-badge"><?=!empty($pattern['system'])?'Системный':'Пользовательский'?></span>
  <h3><?=e((string)$pattern['name'])?></h3>
  <p><?=e((string)($pattern['description']??''))?></p>
  <small>Блоков: <?=count(json_decode((string)$pattern['blocks_json'],true)?:[])?></small>
  <?php if(empty($pattern['system'])):?><form method="post" data-confirm="Удалить этот шаблон секций?" action="<?=e(app_url('/studio/patterns/'.(int)$pattern['id'].'/delete'))?>" style="margin-top:14px"><?=csrf_field()?><button class="button small danger">Удалить</button></form><?php endif;?>
 </article>
<?php endforeach;?>
</div>
<section class="panel" style="margin-top:20px">
 <h2>Создать шаблон вручную</h2>
 <p>Обычно удобнее собрать секции в редакторе и нажать «Сохранить как шаблон». Эта форма подходит для переноса готового JSON.</p>
 <form method="post" action="<?=e(app_url('/studio/patterns'))?>"><?=csrf_field()?><div class="form-grid"><div class="field"><label>Название</label><input name="name" required maxlength="150"></div><div class="field"><label>Описание</label><input name="description" maxlength="500"></div></div><div class="field"><label>JSON блоков</label><textarea name="blocks_json" rows="8" required placeholder='[{"type":"heading","data":{"text":"Заголовок","level":2}}]'></textarea></div><button class="button primary">Сохранить шаблон</button></form>
</section>
