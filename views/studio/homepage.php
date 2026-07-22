<?php
$sections=$sections??[];$sectionTypes=$sectionTypes??[];$publishedEntries=$publishedEntries??[];
$renderFields=static function(string $type,array $settings)use($publishedEntries):void{
    $v=static fn(string $key,string $default=''):string=>(string)($settings[$key]??$default);
    if($type==='hero'):?>
      <div class="studio-field-grid"><label>Надпись<input name="eyebrow" value="<?=e($v('eyebrow'))?>"></label><label>Заголовок<input name="hero_title" value="<?=e($v('title'))?>"></label></div>
      <label>Описание<textarea name="text" rows="3"><?=e($v('text'))?></textarea></label>
      <div class="studio-field-grid"><label>Основная кнопка<input name="primary_text" value="<?=e($v('primary_text'))?>"></label><label>Адрес<input name="primary_url" value="<?=e($v('primary_url'))?>" placeholder="/blog"></label><label>Вторая кнопка<input name="secondary_text" value="<?=e($v('secondary_text'))?>"></label><label>Адрес<input name="secondary_url" value="<?=e($v('secondary_url'))?>" placeholder="/portfolio"></label></div>
      <label>Фоновое изображение URL<input name="image_url" value="<?=e($v('image_url'))?>"></label>
    <?php elseif(in_array($type,['latest_posts','portfolio'],true)):?>
      <div class="studio-field-grid"><label>Надпись<input name="eyebrow" value="<?=e($v('eyebrow'))?>"></label><label>Количество<input type="number" min="1" max="24" name="limit" value="<?=e($v('limit','8'))?>"></label><label>Текст ссылки<input name="button_text" value="<?=e($v('button_text'))?>"></label><label>Адрес ссылки<input name="button_url" value="<?=e($v('button_url'))?>"></label></div>
    <?php elseif($type==='featured_post'):?>
      <div class="studio-field-grid"><label>Надпись<input name="eyebrow" value="<?=e($v('eyebrow','ИЗБРАННОЕ'))?>"></label><label>Материал<select name="entry_id"><option value="0">Выберите материал</option><?php foreach($publishedEntries as $entry):?><option value="<?=(int)$entry['id']?>" <?=((int)($settings['entry_id']??0)===(int)$entry['id'])?'selected':''?>><?=e($entry['title'])?> · <?=e($entry['type'])?></option><?php endforeach;?></select></label><label>Текст кнопки<input name="button_text" value="<?=e($v('button_text','Открыть материал'))?>"></label></div>
    <?php elseif($type==='text'):?>
      <div class="studio-field-grid"><label>Надпись<input name="eyebrow" value="<?=e($v('eyebrow'))?>"></label><label>Заголовок<input name="heading" value="<?=e($v('heading'))?>"></label><label>Выравнивание<select name="align"><option value="left" <?=$v('align','left')==='left'?'selected':''?>>Слева</option><option value="center" <?=$v('align')==='center'?'selected':''?>>По центру</option></select></label></div><label>Текст<textarea name="text" rows="6"><?=e($v('text'))?></textarea></label>
    <?php elseif($type==='stats'):$lines=[];foreach((array)($settings['items']??[]) as $item)$lines[]=(string)($item['value']??'').' | '.(string)($item['label']??'');?>
      <label>Надпись<input name="eyebrow" value="<?=e($v('eyebrow','В ЦИФРАХ'))?>"></label><label>Показатели — один на строку в формате «значение | подпись»<textarea name="items" rows="6"><?=e(implode("\n",$lines))?></textarea></label>
    <?php elseif($type==='cta'):?>
      <div class="studio-field-grid"><label>Надпись<input name="eyebrow" value="<?=e($v('eyebrow'))?>"></label><label>Заголовок<input name="heading" value="<?=e($v('heading'))?>"></label><label>Стиль<select name="tone"><option value="dark" <?=$v('tone','dark')==='dark'?'selected':''?>>Тёмный</option><option value="light" <?=$v('tone')==='light'?'selected':''?>>Светлый</option><option value="accent" <?=$v('tone')==='accent'?'selected':''?>>Акцентный</option></select></label></div><label>Текст<textarea name="text" rows="3"><?=e($v('text'))?></textarea></label><div class="studio-field-grid"><label>Кнопка<input name="button_text" value="<?=e($v('button_text'))?>"></label><label>Адрес<input name="button_url" value="<?=e($v('button_url'))?>"></label></div>
    <?php endif;
};
?>
<div class="studio-page-heading"><div><span class="studio-kicker">SITE MANAGER 3.3</span><h1>Конструктор главной</h1><p>Меняйте порядок, содержимое и видимость секций без правки PHP-шаблонов.</p></div><a class="button" href="<?=e(app_url('/'))?>" target="_blank" rel="noopener">Открыть главную ↗</a></div>

<div class="studio-home-layout">
 <section class="studio-panel studio-home-sections">
  <header><div><h2>Секции главной</h2><p>Порядок сверху вниз соответствует сайту.</p></div></header>
  <?php if(!$sections):?><div class="studio-empty">Секции не созданы. Добавьте первую справа.</div><?php endif;?>
  <?php foreach($sections as $section):$settings=(array)($section['settings']??[]);?>
   <details class="studio-home-card" open>
    <summary><span class="home-order"><?=(int)$section['sort_order']?></span><div><b><?=e($section['title']?:($sectionTypes[$section['section_type']]??$section['section_type']))?></b><small><?=e($sectionTypes[$section['section_type']]??$section['section_type'])?> · <?=$section['is_enabled']?'показывается':'скрыта'?></small></div><span>Настроить</span></summary>
    <form method="post" action="<?=e(app_url('/studio/homepage/save'))?>" class="studio-form home-section-form">
     <?=csrf_field()?><input type="hidden" name="id" value="<?=(int)$section['id']?>"><input type="hidden" name="section_type" value="<?=e($section['section_type'])?>">
     <div class="studio-field-grid"><label>Служебный ключ<input name="section_key" value="<?=e($section['section_key'])?>"></label><label>Название в Studio<input name="title" value="<?=e((string)$section['title'])?>"></label><label>Позиция<input type="number" name="sort_order" value="<?=(int)$section['sort_order']?>"></label><label class="studio-check"><input type="checkbox" name="is_enabled" value="1" <?=$section['is_enabled']?'checked':''?>> Показывать секцию</label></div>
     <?php $renderFields((string)$section['section_type'],$settings);?>
     <div class="studio-form-actions"><button class="button primary" type="submit">Сохранить секцию</button></div>
    </form>
    <div class="home-card-actions"><form method="post" action="<?=e(app_url('/studio/homepage/'.(int)$section['id'].'/move'))?>"><?=csrf_field()?><input type="hidden" name="direction" value="up"><button type="submit">↑ Выше</button></form><form method="post" action="<?=e(app_url('/studio/homepage/'.(int)$section['id'].'/move'))?>"><?=csrf_field()?><input type="hidden" name="direction" value="down"><button type="submit">↓ Ниже</button></form><form method="post" action="<?=e(app_url('/studio/homepage/'.(int)$section['id'].'/delete'))?>" onsubmit="return confirm('Удалить секцию с главной?')"><?=csrf_field()?><button class="danger" type="submit">Удалить</button></form></div>
   </details>
  <?php endforeach;?>
 </section>

 <aside class="studio-panel studio-home-add">
  <header><div><h2>Добавить секцию</h2><p>После создания её можно заполнить и переставить.</p></div></header>
  <form method="post" action="<?=e(app_url('/studio/homepage/save'))?>" class="studio-form">
   <?=csrf_field()?>
   <label>Тип секции<select name="section_type"><?php foreach($sectionTypes as $key=>$label):?><option value="<?=e($key)?>"><?=e($label)?></option><?php endforeach;?></select></label>
   <label>Название<input name="title" placeholder="Например: Наши преимущества" required></label>
   <label>Позиция<input type="number" name="sort_order" value="50"></label>
   <label class="studio-check"><input type="checkbox" name="is_enabled" value="1" checked> Сразу показывать</label>
   <button class="button primary" type="submit">Добавить</button>
  </form>
 </aside>
</div>
