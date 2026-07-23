<?php
/** @var array $layouts */
/** @var array $currentLayout */
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
    try{$settings=json_decode((string)($instance['settings_json']??'{}'),true,512,JSON_THROW_ON_ERROR);}catch(Throwable){$settings=[];}
    return is_array($settings)?$settings:[];
};

$renderFields=static function(array $definition,array $settings)use($menus):void{
    foreach((array)($definition['fields']??[]) as $key=>$field){
        $type=(string)($field['type']??'text');$label=(string)($field['label']??$key);$value=$settings[$key]??($definition['defaults'][$key]??'');
        if($type==='checkbox'){?>
            <label class="check"><input type="checkbox" name="<?=e($key)?>" value="1" <?=$value?'checked':''?>> <span><?=e($label)?></span></label>
        <?php }elseif($type==='menu'){?>
            <label><span><?=e($label)?></span><select name="<?=e($key)?>"><option value="0">Меню по умолчанию</option><?php foreach($menus as $menu):?><option value="<?=(int)$menu['id']?>" <?=(int)$value===(int)$menu['id']?'selected':''?>><?=e($menu['name'])?></option><?php endforeach;?></select></label>
        <?php }elseif($type==='select'){?>
            <label><span><?=e($label)?></span><select name="<?=e($key)?>"><?php foreach((array)($field['options']??[]) as $optionValue=>$optionLabel):?><option value="<?=e($optionValue)?>" <?=(string)$value===(string)$optionValue?'selected':''?>><?=e($optionLabel)?></option><?php endforeach;?></select></label>
        <?php }elseif($type==='textarea'){?>
            <label><span><?=e($label)?></span><textarea name="<?=e($key)?>" rows="6" maxlength="<?=(int)($field['maxlength']??10000)?>"><?=e((string)$value)?></textarea></label>
        <?php }else{?>
            <label><span><?=e($label)?></span><input type="<?=$type==='number'?'number':($type==='url'?'url':'text')?>" name="<?=e($key)?>" value="<?=e((string)$value)?>" <?php if(isset($field['min'])):?>min="<?=(int)$field['min']?>"<?php endif;?> <?php if(isset($field['max'])):?>max="<?=(int)$field['max']?>"<?php endif;?> maxlength="<?=(int)($field['maxlength']??5000)?>"></label>
        <?php }
    }
};

$renderCard=static function(array $instance)use($widgetTypes,$settingsFor,$renderFields,$currentLayout):void{
    $type=(string)$instance['widget_type'];$definition=$widgetTypes[$type]??null;$available=$definition!==null;$settings=$settingsFor($instance);
?>
<article class="widget-card <?=$instance['is_enabled']?'':'is-disabled'?> <?=$available?'':'is-missing'?>" draggable="true" data-widget-id="<?=(int)$instance['id']?>">
  <header class="widget-card__head"><button type="button" class="widget-drag" aria-label="Перетащить" title="Перетащить">⋮⋮</button><div><b><?=e($instance['title'])?></b><small><?=e($available?(string)$definition['label']:'Тип недоступен: '.$type)?></small></div><span class="widget-state"><?=$instance['is_enabled']?'Включён':'Выключен'?></span></header>
  <div class="widget-card__move"><button type="button" data-widget-up aria-label="Выше">↑</button><button type="button" data-widget-down aria-label="Ниже">↓</button><button type="button" data-widget-pool>Убрать из зоны</button></div>
  <?php if($available):?><details class="widget-settings"><summary>Настройки</summary><form method="post" action="<?=e(app_url('/studio/widgets/'.(int)$instance['id'].'/update'))?>"><?=csrf_field()?><input type="hidden" name="layout_id" value="<?=(int)$currentLayout['id']?>"><label><span>Название экземпляра</span><input name="title" value="<?=e($instance['title'])?>" maxlength="180" required></label><?php $renderFields($definition,$settings);?><button class="button primary">Сохранить настройки</button></form></details><?php endif;?>
  <footer class="widget-card__actions">
    <form method="post" action="<?=e(app_url('/studio/widgets/'.(int)$instance['id'].'/toggle'))?>"><?=csrf_field()?><input type="hidden" name="layout_id" value="<?=(int)$currentLayout['id']?>"><button class="button small"><?=$instance['is_enabled']?'Отключить':'Включить'?></button></form>
    <form method="post" action="<?=e(app_url('/studio/widgets/'.(int)$instance['id'].'/duplicate'))?>"><?=csrf_field()?><input type="hidden" name="layout_id" value="<?=(int)$currentLayout['id']?>"><button class="button small">Копировать</button></form>
    <form method="post" action="<?=e(app_url('/studio/widgets/'.(int)$instance['id'].'/delete'))?>"><?=csrf_field()?><input type="hidden" name="layout_id" value="<?=(int)$currentLayout['id']?>"><button class="button danger small">Удалить</button></form>
  </footer>
</article>
<?php };

$renderZone=static function(string $zoneId,string $visualClass='')use($zones,$placedByZone,$renderCard):void{
    $zone=$zones[$zoneId]??['label'=>$zoneId,'width'=>'auto'];$items=$placedByZone[$zoneId]??[];
?>
<section class="widget-zone matrix-zone <?=e($visualClass)?> <?=$items?'has-widgets':''?>" data-zone-card data-zone-id="<?=e($zoneId)?>">
  <header class="matrix-zone__head"><b><?=e((string)$zone['label'])?></b><span><?=count($items)?></span></header>
  <div class="widget-zone__items" data-widget-zone="<?=e($zoneId)?>">
    <?php foreach($items as $instance)$renderCard($instance);?>
    <p class="widget-zone__empty">Перетащите виджет</p>
  </div>
</section>
<?php };
?>

<div class="studio-page-head widget-page-head"><div><span class="eyebrow">КОНСТРУКТОР САЙТА 3.5.4</span><h1>Виджеты и зоны</h1><p>Слева находится библиотека виджетов. Вся остальная область — точная схема страницы.</p></div><div class="studio-actions"><a class="button" href="<?=e(app_url('/'))?>" target="_blank" rel="noopener">Открыть сайт</a></div></div>

<section class="studio-card widget-layout-toolbar">
  <label><span>Схема сайта</span><select onchange="location.href='<?=e(app_url('/studio/widgets?layout='))?>'+this.value"><?php foreach($layouts as $item):?><option value="<?=(int)$item['id']?>" <?=(int)$item['id']===(int)$currentLayout['id']?'selected':''?>><?=e($item['name'])?> · <?=e($item['status'])?></option><?php endforeach;?></select></label>
  <form method="post" action="<?=e(app_url('/studio/widgets/layout/save'))?>" data-layout-save-form><?=csrf_field()?><input type="hidden" name="layout_id" value="<?=(int)$currentLayout['id']?>"><input type="hidden" name="placements_json" value="[]" data-placements-json><button class="button primary">Опубликовать расположение</button></form>
</section>

<div class="widget-builder-shell widget-builder-shell--matrix">
  <aside class="studio-card widget-catalog widget-catalog--accordion">
    <details class="widget-library-group" open>
      <summary><span><b>Добавить виджеты</b><small><?=count($widgetTypes)?> типов</small></span><i>⌄</i></summary>
      <div class="widget-library-group__body">
        <?php foreach($widgetTypes as $type=>$definition):?>
        <details class="widget-library-item">
          <summary><span><b><?=e($definition['label'])?></b><small><?=e($definition['module']??'core')?></small></span><i>+</i></summary>
          <div><p><?=e($definition['description'])?></p><form method="post" action="<?=e(app_url('/studio/widgets/create'))?>"><?=csrf_field()?><input type="hidden" name="layout_id" value="<?=(int)$currentLayout['id']?>"><input type="hidden" name="widget_type" value="<?=e($type)?>"><input type="hidden" name="title" value="<?=e($definition['label'])?>"><button class="button primary small" type="submit">Создать виджет</button></form></div>
        </details>
        <?php endforeach;?>
      </div>
    </details>

    <details class="widget-library-group" open>
      <summary><span><b>Не размещены</b><small><?=count($pool)?> видж.</small></span><i>⌄</i></summary>
      <div class="widget-library-group__body widget-zone__items widget-pool" data-widget-zone="__pool">
        <?php foreach($pool as $instance)$renderCard($instance);?>
        <p class="widget-zone__empty">Перетащите сюда, чтобы убрать блок с сайта.</p>
      </div>
    </details>

    <details class="widget-library-group">
      <summary><span><b>История изменений</b><small><?=count($revisions)?> ревиз.</small></span><i>⌄</i></summary>
      <div class="widget-library-group__body widget-revision-list">
        <?php if(!$revisions):?><p class="empty">Ревизий пока нет.</p><?php else:?><?php foreach($revisions as $revision):?><div><span><b><?=e($revision['created_at'])?></b><small><?=e((string)($revision['author_name']??'Система'))?></small></span><form method="post" action="<?=e(app_url('/studio/widgets/revisions/'.(int)$revision['id'].'/restore'))?>"><?=csrf_field()?><button class="button small">Восстановить</button></form></div><?php endforeach;?><?php endif;?>
      </div>
    </details>
  </aside>

  <main class="widget-page-blueprint matrix-blueprint" aria-label="Точная схема зон сайта">
    <section class="matrix-row matrix-row--full matrix-preheader"><div class="matrix-row__title">ОБЛАСТЬ НАД ШАПКОЙ</div><?php $renderZone('matrix.preheader','matrix-zone--full');?></section>

    <section class="matrix-row matrix-header"><div class="matrix-row__title">ШАПКА · 5 СЕКЦИЙ</div><div class="matrix-header__grid"><?php for($i=1;$i<=5;$i++)$renderZone('matrix.header.'.$i,'matrix-zone--header');?></div></section>

    <section class="matrix-row matrix-row--full matrix-postheader"><div class="matrix-row__title">ОБЛАСТЬ ПОД ШАПКОЙ</div><?php $renderZone('matrix.postheader','matrix-zone--full');?></section>
    <section class="matrix-row matrix-row--full matrix-banner"><div class="matrix-row__title">ВЕРХНЯЯ БАННЕРНАЯ ПОЛОСА / БЕГУЩАЯ СТРОКА</div><?php $renderZone('matrix.banner.top','matrix-zone--banner');?></section>

    <section class="matrix-columns">
      <aside class="matrix-sidebar matrix-sidebar--left"><div class="matrix-row__title">ЛЕВАЯ КОЛОНКА · 4 БЛОКА</div><div class="matrix-sidebar__grid"><?php for($i=1;$i<=4;$i++)$renderZone('matrix.left.'.$i,'matrix-zone--sidebar');?></div></aside>

      <section class="matrix-center"><div class="matrix-row__title">ЦЕНТРАЛЬНАЯ КОЛОНКА · 12 БЛОКОВ ПО 4 В РЯДУ</div><div class="matrix-center__grid"><?php for($i=1;$i<=4;$i++)$renderZone('matrix.center.'.$i,'matrix-zone--center');?><div class="matrix-content-lock"><span>ОСНОВНОЕ СОДЕРЖИМОЕ</span><b>Страница, запись блога или портфолио</b><small>Системный блок, не перетаскивается</small></div><?php for($i=5;$i<=12;$i++)$renderZone('matrix.center.'.$i,'matrix-zone--center');?></div></section>

      <aside class="matrix-sidebar matrix-sidebar--right"><div class="matrix-row__title">ПРАВАЯ КОЛОНКА · 4 БЛОКА</div><div class="matrix-sidebar__grid"><?php for($i=1;$i<=4;$i++)$renderZone('matrix.right.'.$i,'matrix-zone--sidebar');?></div></aside>
    </section>

    <section class="matrix-row matrix-row--full matrix-banner"><div class="matrix-row__title">НИЖНЯЯ БАННЕРНАЯ ПОЛОСА / БЕГУЩАЯ СТРОКА</div><?php $renderZone('matrix.banner.bottom','matrix-zone--banner');?></section>

    <section class="matrix-row matrix-footer"><div class="matrix-row__title">ПОДВАЛ · 4 БЛОКА × 2 РЯДА</div><div class="matrix-footer__grid"><?php for($i=1;$i<=8;$i++)$renderZone('matrix.footer.'.$i,'matrix-zone--footer');?></div><div class="matrix-copyright-lock"><b>© <?=date('Y')?> Ланцет Семён Борисович</b><span>KOVCHEG Blog · Все права защищены</span></div></section>
  </main>
</div>