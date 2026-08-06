<?php
$selectedEntryId=max(0,(int)($_GET['entry']??0));
$selectedPage=null;
foreach($pages as $candidate){if((int)$candidate['id']===$selectedEntryId){$selectedPage=$candidate;break;}}
$locationLabels=$locations??[];
$selectedMenu=$selectedMenu??null;
?>
<div class="page-head">
 <div><h1>Меню сайта</h1><p>Создавайте несколько меню. Их можно назначить в шапку, левую или правую колонку, подвал либо разместить как виджет в любой зоне.</p></div>
 <a class="button" href="<?=e(app_url('/studio/widgets'))?>">Перейти к виджетам и зонам</a>
</div>

<div class="menu-layout wp-menu-layout cms-menu-layout">
 <aside class="panel cms-menu-sidebar">
  <h2>Созданные меню</h2>
  <div class="menu-list">
   <?php foreach($menus as $menu):$location=(string)($menu['location']??'');?>
    <a class="<?=(int)$menu['id']===$menuId?'active':''?>" href="<?=e(app_url('/studio/menus?menu='.(int)$menu['id'].($selectedEntryId>0?'&entry='.$selectedEntryId:'')))?>">
     <b><?=e($menu['name'])?></b>
     <small><?=e((string)($locationLabels[$location]??'Не назначено'))?> · <?=!empty($menu['is_active'])?'включено':'выключено'?></small>
    </a>
   <?php endforeach;?>
   <?php if(!$menus):?><div class="empty-state">Меню пока нет.</div><?php endif;?>
  </div>

  <hr>
  <form method="post" action="<?=e(app_url('/studio/menus/create'))?>" class="cms-menu-create">
   <?=csrf_field()?>
   <h3>Новое меню</h3>
   <div class="field"><label>Название</label><input name="name" required maxlength="150" placeholder="Например: Главное меню"></div>
   <div class="field"><label>Быстрое размещение</label><select name="location"><?php foreach($locationLabels as $value=>$label):?><option value="<?=e($value)?>"><?=e($label)?></option><?php endforeach;?></select><small>То же меню можно позже поставить в любую зону через виджет «Меню».</small></div>
   <button class="button primary">Создать меню</button>
  </form>
 </aside>

 <section class="panel cms-menu-editor">
  <?php if($selectedMenu):?>
   <div class="cms-menu-editor__head">
    <div><h2><?=e((string)$selectedMenu['name'])?></h2><p>Адрес меню в системе: <code><?=e((string)$selectedMenu['slug'])?></code></p></div>
    <form method="post" data-confirm="Удалить это меню вместе со всеми пунктами?" action="<?=e(app_url('/studio/menus/'.(int)$selectedMenu['id'].'/delete'))?>"><?=csrf_field()?><button class="button danger small">Удалить меню</button></form>
   </div>

   <form method="post" action="<?=e(app_url('/studio/menus/'.(int)$selectedMenu['id'].'/update'))?>" class="cms-menu-settings">
    <?=csrf_field()?>
    <div class="field"><label>Название меню</label><input name="name" maxlength="150" required value="<?=e((string)$selectedMenu['name'])?>"></div>
    <div class="field"><label>Показывать по умолчанию</label><select name="location"><?php foreach($locationLabels as $value=>$label):?><option value="<?=e($value)?>" <?=(string)($selectedMenu['location']??'')===(string)$value?'selected':''?>><?=e($label)?></option><?php endforeach;?></select></div>
    <label class="check-row"><input type="checkbox" name="is_active" value="1" <?=!empty($selectedMenu['is_active'])?'checked':''?>> Меню включено</label>
    <button class="button primary">Сохранить настройки</button>
   </form>

   <div class="cms-menu-tip"><b>Гибкое размещение</b><span>Создайте виджет «Меню», выберите это меню и перетащите его в шапку, колонку, центральную область или подвал.</span><a href="<?=e(app_url('/studio/widgets'))?>">Открыть виджеты</a></div>

   <h2>Пункты меню</h2>
   <div class="menu-items-simple cms-menu-items">
    <?php foreach($items as $item):?>
     <article class="menu-item-row cms-menu-item">
      <form method="post" action="<?=e(app_url('/studio/menus/item/'.(int)$item['id'].'/update'))?>">
       <?=csrf_field()?>
       <div class="field"><label>Название</label><input name="label" maxlength="150" required value="<?=e((string)$item['label'])?>"></div>
       <div class="field cms-menu-item__url"><label>Ссылка</label><input name="url" maxlength="500" required value="<?=e((string)($item['url']??'/'))?>"></div>
       <div class="field cms-menu-item__order"><label>Порядок</label><input type="number" name="sort_order" min="-9999" max="9999" value="<?=(int)$item['sort_order']?>"></div>
       <label class="check-row"><input type="checkbox" name="is_enabled" value="1" <?=!empty($item['is_enabled'])?'checked':''?>> Показывать</label>
       <button class="button small">Сохранить</button>
      </form>
      <form method="post" data-confirm="Удалить пункт меню?" action="<?=e(app_url('/studio/menus/item/'.(int)$item['id'].'/delete'))?>"><?=csrf_field()?><button class="button small danger">Удалить</button></form>
     </article>
    <?php endforeach;?>
    <?php if(!$items):?><div class="empty-state">В меню ещё нет пунктов.</div><?php endif;?>
   </div>

   <hr>
   <h2>Добавить пункт</h2>
   <div class="wp-menu-sources cms-menu-sources">
    <details class="editor-card" <?=$selectedPage?'open':''?>><summary>Страница</summary>
     <?php if($pages):?><form method="post" action="<?=e(app_url('/studio/menus/item'))?>"><?=csrf_field()?><input type="hidden" name="menu_id" value="<?=$menuId?>"><input type="hidden" name="target_kind" value="page"><div class="field"><label>Выберите страницу</label><select name="target_id" required><?php foreach($pages as $page):?><option value="<?=(int)$page['id']?>" <?=(int)$page['id']===$selectedEntryId?'selected':''?>><?=e($page['title'])?></option><?php endforeach;?></select></div><div class="field"><label>Подпись в меню</label><input name="label" value="<?=e((string)($selectedPage['title']??''))?>" placeholder="Можно оставить пустым"></div><button class="button primary">Добавить страницу</button></form><?php else:?><div class="empty-state">Сначала опубликуйте страницу.</div><?php endif;?>
    </details>

    <details class="editor-card"><summary>Рубрика записей</summary>
     <?php if($categories):?><form method="post" action="<?=e(app_url('/studio/menus/item'))?>"><?=csrf_field()?><input type="hidden" name="menu_id" value="<?=$menuId?>"><input type="hidden" name="target_kind" value="category"><div class="field"><label>Выберите рубрику</label><select name="target_id" required><?php foreach($categories as $category):?><option value="<?=(int)$category['id']?>"><?=e($category['name'])?> (<?=(int)($category['post_count']??0)?>)</option><?php endforeach;?></select></div><div class="field"><label>Подпись в меню</label><input name="label" placeholder="Например: Новости"></div><button class="button primary">Добавить рубрику</button></form><?php else:?><div class="empty-state">Сначала создайте рубрику.</div><?php endif;?>
    </details>

    <details class="editor-card"><summary>Произвольная ссылка</summary>
     <form method="post" action="<?=e(app_url('/studio/menus/item'))?>"><?=csrf_field()?><input type="hidden" name="menu_id" value="<?=$menuId?>"><input type="hidden" name="target_kind" value="custom"><input type="hidden" name="target_id" value="0"><div class="field"><label>Название</label><input name="label" required maxlength="150" placeholder="Например: Главная"></div><div class="field"><label>Ссылка</label><input name="url" required maxlength="500" placeholder="/ или https://..."></div><button class="button primary">Добавить ссылку</button></form>
    </details>
   </div>
  <?php else:?><div class="empty-state"><h2>Создайте первое меню</h2><p>После этого добавьте страницы, рубрики или произвольные ссылки.</p></div><?php endif;?>
 </section>
</div>
