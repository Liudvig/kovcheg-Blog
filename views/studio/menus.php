<?php
$selectedEntryId=max(0,(int)($_GET['entry']??0));
$selectedPage=null;
foreach($pages as $candidate){if((int)$candidate['id']===$selectedEntryId){$selectedPage=$candidate;break;}}
$menuQuerySuffix=$selectedEntryId>0?'&entry='.$selectedEntryId:'';
$nextSort=count($items)*10;
?>
<div class="page-head"><div><h1>Меню сайта</h1><p>Добавляйте в меню страницы, рубрики или обычные ссылки.</p></div></div>
<div class="menu-layout wp-menu-layout">
 <aside class="panel">
  <h2>Меню</h2>
  <div class="menu-list"><?php foreach($menus as $menu):?><a class="<?=(int)$menu['id']===$menuId?'active':''?>" href="<?=e(app_url('/studio/menus?menu='.(int)$menu['id'].$menuQuerySuffix))?>"><b><?=e($menu['name'])?></b><small><?=($menu['location']??'')==='header'?'Шапка':((($menu['location']??'')==='footer')?'Подвал':'Не назначено')?></small></a><?php endforeach;?></div>
  <hr>
  <form method="post" action="<?=e(app_url('/studio/menus/create'))?>">
   <?=csrf_field()?>
   <div class="field"><label>Новое меню</label><input name="name" required placeholder="Например: Главное"></div>
   <div class="field"><label>Где показывать</label><select name="location"><option value="header">В шапке</option><option value="footer">В подвале</option><option value="">Пока не показывать</option></select></div>
   <button class="button primary small">Создать</button>
  </form>
 </aside>

 <section class="panel">
  <h2>Пункты меню</h2>
  <?php if($menuId):?>
   <div class="menu-items-simple">
    <?php foreach($items as $item):?><article class="menu-item-row"><div><b><?=e($item['label'])?></b><small><?=e($item['url']??'')?></small></div><form method="post" data-confirm="Удалить пункт меню?" action="<?=e(app_url('/studio/menus/item/'.(int)$item['id'].'/delete'))?>"><?=csrf_field()?><button class="button small danger">Удалить</button></form></article><?php endforeach;?>
    <?php if(!$items):?><div class="empty-state">В меню ещё нет пунктов.</div><?php endif;?>
   </div>
   <hr>
   <div class="wp-menu-sources">
    <details class="editor-card" <?=$selectedPage?'open':''?>><summary>Страница</summary>
     <?php if($pages):?><form method="post" action="<?=e(app_url('/studio/menus/item'))?>"><?=csrf_field()?><input type="hidden" name="menu_id" value="<?=$menuId?>"><input type="hidden" name="target_kind" value="page"><input type="hidden" name="sort_order" value="<?=$nextSort?>"><div class="field"><label>Выберите страницу</label><select name="target_id" required><?php foreach($pages as $page):?><option value="<?=(int)$page['id']?>" <?=(int)$page['id']===$selectedEntryId?'selected':''?>><?=e($page['title'])?></option><?php endforeach;?></select></div><div class="field"><label>Подпись в меню</label><input name="label" value="<?=e((string)($selectedPage['title']??''))?>" placeholder="Можно оставить пустым"></div><button class="button primary">Добавить страницу</button></form><?php else:?><div class="empty-state">Сначала опубликуйте страницу.</div><?php endif;?>
    </details>

    <details class="editor-card"><summary>Рубрика</summary>
     <?php if($categories):?><form method="post" action="<?=e(app_url('/studio/menus/item'))?>"><?=csrf_field()?><input type="hidden" name="menu_id" value="<?=$menuId?>"><input type="hidden" name="target_kind" value="category"><input type="hidden" name="sort_order" value="<?=$nextSort?>"><div class="field"><label>Выберите рубрику</label><select name="target_id" required><?php foreach($categories as $category):?><option value="<?=(int)$category['id']?>"><?=e($category['name'])?></option><?php endforeach;?></select></div><div class="field"><label>Подпись в меню</label><input name="label" placeholder="Можно оставить пустым"></div><button class="button primary">Добавить рубрику</button></form><?php else:?><div class="empty-state">Сначала создайте рубрику.</div><?php endif;?>
    </details>

    <details class="editor-card"><summary>Произвольная ссылка</summary>
     <form method="post" action="<?=e(app_url('/studio/menus/item'))?>"><?=csrf_field()?><input type="hidden" name="menu_id" value="<?=$menuId?>"><input type="hidden" name="target_kind" value="custom"><input type="hidden" name="target_id" value="0"><input type="hidden" name="sort_order" value="<?=$nextSort?>"><div class="field"><label>Название</label><input name="label" required placeholder="Например: Главная"></div><div class="field"><label>Ссылка</label><input name="url" required placeholder="/blog или https://..."></div><button class="button primary">Добавить ссылку</button></form>
    </details>
   </div>
  <?php else:?><div class="empty-state">Создайте или выберите меню слева.</div><?php endif;?>
 </section>
</div>
