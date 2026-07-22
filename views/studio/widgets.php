<?php
/** @var array $layouts */
/** @var array $layout */
/** @var array $zones */
/** @var array $widgetTypes */
/** @var array $instances */
/** @var array $placements */
/** @var array $revisions */
/** @var array $menus */

$instancesById=[];
foreach($instances as $instance)$instancesById[(int)$instance['id']]=$instance;
$placedByZone=[];$placedIds=[];
foreach($placements as $placement){
    $id=(int)$placement['widget_id'];
    if(!isset($instancesById[$id]))continue;
    $placedByZone[(string)$placement['zone']][]=$instancesById[$id];
    $placedIds[$id]=true;
}
$pool=[];
foreach($instances as $instance)if(!isset($placedIds[(int)$instance['id']]))$pool[]=$instance;

$settingsFor=static function(array $instance):array{
    try{$settings=json_decode((string)($instance['settings_json']??'{}'),true,512,JSON_THROW_ON_ERROR);}
    catch(Throwable){$settings=[];}
    return is_array($settings)?$settings:[];
};

$renderFields=static function(array $definition,array $settings)use($menus):void{
    foreach((array)($definition['fields']??[]) as $key=>$field){
        $type=(string)($field['type']??'text');$label=(string)($field['label']??$key);$value=$settings[$key]??($definition['defaults'][$key]??'');
        if($type==='checkbox'){?>
            <label class="check"><input type="checkbox" name="<?=e($key)?>" value="1" <?=$value?'checked':''?>> <?=e($label)?></label>
        <?php }elseif($type==='menu'){?>
            <label><?=e($label)?><select name="<?=e($key)?>"><option value="0">Меню по расположению header</option><?php foreach($menus as $menu):?><option value="<?=(int)$menu['id']?>" <?=(int)$value===(int)$menu['id']?'selected':''?>><?=e($menu['name'])?></option><?php endforeach;?></select></label>
        <?php }elseif($type==='select'){?>
            <label><?=e($label)?><select name="<?=e($key)?>"><?php foreach((array)($field['options']??[]) as $optionValue=>$optionLabel):?><option value="<?=e($optionValue)?>" <?=(string)$value===(string)$optionValue?'selected':''?>><?=e($optionLabel)?></option><?php endforeach;?></select></label>
        <?php }elseif($type==='textarea'){?>
            <label><?=e($label)?><textarea name="<?=e($key)?>" rows="5" maxlength="<?=(int)($field['maxlength']??5000)?>"><?=e((string)$value)?></textarea></label>
        <?php }else{?>
            <label><?=e($label)?><input type="<?=$type==='number'?'number':($type==='url'?'url':'text')?>" name="<?=e($key)?>" value="<?=e((string)$value)?>" <?php if(isset($field['min'])):?>min="<?=(int)$field['min']?>"<?php endif;?> <?php if(isset($field['max'])):?>max="<?=(int)$field['max']?>"<?php endif;?> maxlength="<?=(int)($field['maxlength']??5000)?>"></label>
        <?php }
    }
};

$renderCard=static function(array $instance)use($widgetTypes,$settingsFor,$renderFields,$layout):void{
    $type=(string)$instance['widget_type'];$definition=$widgetTypes[$type]??null;$available=$definition!==null;$settings=$settingsFor($instance);
?>
<article class="widget-card <?=$instance['is_enabled']?'':'is-disabled'?> <?=$available?'':'is-missing'?>" draggable="true" data-widget-id="<?=(int)$instance['id']?>">
  <header class="widget-card__head"><button type="button" class="widget-drag" aria-label="Перетащить" title="Перетащить">⋮⋮</button><div><b><?=e($instance['title'])?></b><small><?=e($available?(string)$definition['label']:'Тип недоступен: '.$type)?></small></div><span class="widget-state"><?=$instance['is_enabled']?'Включён':'Выключен'?></span></header>
  <div class="widget-card__move"><button type="button" data-widget-up aria-label="Выше">↑</button><button type="button" data-widget-down aria-label="Ниже">↓</button><button type="button" data-widget-pool>Убрать из зоны</button></div>
  <?php if($available):?><details class="widget-settings"><summary>Настройки</summary><form method="post" action="<?=e(app_url('/studio/widgets/'.(int)$instance['id'].'/update'))?>"><?=csrf_field()?><input type="hidden" name="layout_id" value="<?=(int)$layout['id']?>"><label>Название экземпляра<input name="title" value="<?=e($instance['title'])?>" maxlength="180" required></label><?php $renderFields($definition,$settings);?><button class="button primary">Сохранить настройки</button></form></details><?php endif;?>
  <footer class="widget-card__actions">
    <form method="post" action="<?=e(app_url('/studio/widgets/'.(int)$instance['id'].'/toggle'))?>"><?=csrf_field()?><input type="hidden" name="layout_id" value="<?=(int)$layout['id']?>"><button class="button small"><?=$instance['is_enabled']?'Отключить':'Включить'?></button></form>
    <form method="post" action="<?=e(app_url('/studio/widgets/'.(int)$instance['id'].'/duplicate'))?>"><?=csrf_field()?><input type="hidden" name="layout_id" value="<?=(int)$layout['id']?>"><button class="button small">Копировать</button></form>
    <form method="post" action="<?=e(app_url('/studio/widgets/'.(int)$instance['id'].'/delete'))?>" onsubmit="return confirm('Удалить виджет и все его размещения?')"><?=csrf_field()?><input type="hidden" name="layout_id" value="<?=(int)$layout['id']?>"><button class="button danger small">Удалить</button></form>
  </footer>
</article>
<?php };
?>

<div class="studio-page-head"><div><span class="eyebrow">ВНЕШНИЙ ВИД 3.4</span><h1>Виджеты и зоны</h1><p>Перетаскивайте меню, поиск, публикации и другие блоки между шапкой, колонками, содержимым и подвалом. Любой необязательный элемент можно убрать.</p></div><div class="studio-actions"><a class="button" href="<?=e(app_url('/'))?>" target="_blank">Открыть сайт</a></div></div>

<section class="studio-card widget-layout-toolbar">
  <div><b>Схема</b><select onchange="location.href='<?=e(app_url('/studio/widgets?layout='))?>'+this.value"><?php foreach($layouts as $item):?><option value="<?=(int)$item['id']?>" <?=(int)$item['id']===(int)$layout['id']?'selected':''?>><?=e($item['name'])?> · <?=e($item['status'])?></option><?php endforeach;?></select></div>
  <form method="post" action="<?=e(app_url('/studio/widgets/layout/save'))?>" data-layout-save-form><?=csrf_field()?><input type="hidden" name="layout_id" value="<?=(int)$layout['id']?>"><input type="hidden" name="placements_json" value="[]" data-placements-json><button class="button primary">Сохранить расположение</button></form>
</section>

<div class="widget-studio-grid">
  <aside class="studio-card widget-catalog">
    <h2>Добавить виджет</h2><p>Создайте экземпляр, затем перетащите его в нужную зону.</p>
    <div class="widget-catalog__list">
      <?php foreach($widgetTypes as $type=>$definition):?>
      <form method="post" action="<?=e(app_url('/studio/widgets/create'))?>" class="widget-catalog__item"><?=csrf_field()?><input type="hidden" name="layout_id" value="<?=(int)$layout['id']?>"><input type="hidden" name="widget_type" value="<?=e($type)?>"><input type="hidden" name="title" value="<?=e($definition['label'])?>"><div><b><?=e($definition['label'])?></b><small><?=e($definition['description'])?></small></div><button type="submit" aria-label="Добавить <?=e($definition['label'])?>">+</button></form>
      <?php endforeach;?>
    </div>
    <h2>Не размещены</h2>
    <div class="widget-zone__items widget-pool" data-widget-zone="__pool">
      <?php foreach($pool as $instance)$renderCard($instance);?>
      <p class="widget-zone__empty">Перетащите сюда, чтобы убрать блок с сайта.</p>
    </div>
  </aside>

  <main class="widget-layout-canvas">
    <?php foreach($zones as $zoneId=>$zone):if(!empty($zone['reserved']))continue;?>
    <section class="widget-zone studio-card" data-zone-card>
      <header><div><b><?=e($zone['label'])?></b><code><?=e($zoneId)?></code></div><span><?=e((string)$zone['width'])?></span></header>
      <div class="widget-zone__items" data-widget-zone="<?=e($zoneId)?>">
        <?php foreach($placedByZone[$zoneId]??[] as $instance)$renderCard($instance);?>
        <p class="widget-zone__empty">Перетащите виджет сюда</p>
      </div>
    </section>
    <?php endforeach;?>
  </main>

  <aside class="studio-card widget-revisions">
    <h2>Ревизии</h2><p>Перед каждой публикацией расположения создаётся снимок.</p>
    <?php if(!$revisions):?><p class="empty">Ревизий пока нет.</p><?php else:?><div class="activity-list"><?php foreach($revisions as $revision):?><div><span><b><?=e($revision['created_at'])?></b><small><?=e((string)($revision['author_name']??'Система'))?></small></span><form method="post" action="<?=e(app_url('/studio/widgets/revisions/'.(int)$revision['id'].'/restore'))?>" onsubmit="return confirm('Восстановить эту схему?')"><?=csrf_field()?><button class="button small">Восстановить</button></form></div><?php endforeach;?></div><?php endif;?>
    <div class="widget-help"><b>Важно</b><p>Выключенный виджет сохраняет настройки и место, но не выводится. Виджет в разделе «Не размещены» не показывается на сайте.</p></div>
  </aside>
</div>