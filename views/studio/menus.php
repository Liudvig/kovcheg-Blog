<div class="page-head"><div><h1>Меню сайта</h1><p>Добавляйте страницы и ссылки в шапку или подвал.</p></div></div>
<div class="menu-layout">
 <aside class="panel">
  <h2>Меню</h2>
  <div class="menu-list"><?php foreach($menus as $menu):?><a class="<?=(int)$menu['id']===$menuId?'active':''?>" href="<?=e(app_url('/studio/menus?menu='.(int)$menu['id']))?>"><b><?=e($menu['name'])?></b><small><?=($menu['location']??'')==='header'?'Шапка':((($menu['location']??'')==='footer')?'Подвал':'Не назначено')?></small></a><?php endforeach;?></div>
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
   <h3>Добавить пункт</h3>
   <form method="post" action="<?=e(app_url('/studio/menus/item'))?>">
    <?=csrf_field()?>
    <input type="hidden" name="menu_id" value="<?=$menuId?>">
    <input type="hidden" name="sort_order" value="<?=count($items)*10?>">
    <div class="field"><label>Название</label><input name="label" required placeholder="Например: Новости"></div>
    <div class="field"><label>Выбрать готовую страницу</label><select name="target_id"><option value="0">Нет — укажу ссылку ниже</option><?php foreach($pages as $page):?><option value="<?=(int)$page['id']?>"><?=e($page['title'])?></option><?php endforeach;?></select></div>
    <div class="field"><label>Ссылка</label><input name="url" placeholder="/blog или https://..."></div>
    <button class="button primary">Добавить</button>
   </form>
  <?php else:?><div class="empty-state">Создайте или выберите меню слева.</div><?php endif;?>
 </section>
</div>
