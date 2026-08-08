<?php
/** @var string $showcaseType */
/** @var array $config */
/** @var array|null $item */
$item=$item??[];
$meta=(array)($item['_meta']??[]);
$id=(int)($item['id']??0);
$isProduct=$showcaseType==='product';
$isLivestock=$showcaseType==='livestock';
$isProject=$showcaseType==='project';
?>
<div class="page-head cms-showcase-head"><div><h1><?=e($id>0?(string)$item['title']:'Новый материал')?></h1><p><?=e((string)$config['singular'])?> можно опубликовать в основном разделе и дополнительно вывести в рекламный слайдер на главной.</p></div><div class="editor-actions"><a class="button" href="<?=e(app_url('/studio/'.(string)$config['section']))?>">← К списку</a><?php if($id>0&&(string)($item['status']??'')==='published'):?><a class="button" href="<?=e((string)$item['_public_url'])?>" target="_blank" rel="noopener">Посмотреть</a><?php endif;?></div></div>

<form method="post" enctype="multipart/form-data" action="<?=e(app_url('/studio/showcase/save'))?>" class="cms-showcase-editor">
 <?=csrf_field()?>
 <input type="hidden" name="id" value="<?=$id?>">
 <input type="hidden" name="showcase_type" value="<?=e($showcaseType)?>">
 <section class="panel cms-showcase-editor__main">
  <div class="field"><label>Название</label><input name="title" maxlength="255" required value="<?=e((string)($item['title']??''))?>" placeholder="Название"></div>
  <div class="field"><label>Адрес</label><div class="cms-url-prefix"><span><?=e((string)$config['public_base'])?>/</span><input name="slug" maxlength="190" value="<?=e((string)($item['slug']??''))?>" placeholder="сформируется автоматически"></div></div>
  <div class="field"><label>Короткое описание</label><textarea name="excerpt" rows="3" maxlength="2000" placeholder="Короткий текст для карточки и слайдера"><?=e((string)($item['excerpt']??''))?></textarea></div>
  <div class="field"><label>Полное описание</label><textarea name="description" rows="10" placeholder="Подробное описание"><?=e(trim(strip_tags((string)($item['content_html']??''))))?></textarea></div>

  <div class="cms-showcase-fields">
   <?php if($isProduct):?>
    <div class="field"><label>Категория</label><input name="category" value="<?=e((string)($meta['category']??''))?>" placeholder="Молочная продукция"></div>
    <div class="field"><label>Цена</label><input name="price" value="<?=e((string)($meta['price']??''))?>" placeholder="250 ₽"></div>
    <div class="field"><label>Единица</label><input name="unit" value="<?=e((string)($meta['unit']??''))?>" placeholder="л, кг, шт."></div>
    <div class="field"><label>Наличие</label><input name="stock_status" value="<?=e((string)($meta['stock_status']??''))?>" placeholder="В наличии / Под заказ"></div>
   <?php elseif($isLivestock):?>
    <div class="field"><label>Вид животного</label><input name="species" value="<?=e((string)($meta['species']??''))?>" placeholder="Коза, свинья, осёл..."></div>
    <div class="field"><label>Порода</label><input name="breed" value="<?=e((string)($meta['breed']??''))?>"></div>
    <div class="field"><label>Пол</label><input name="sex" value="<?=e((string)($meta['sex']??''))?>" placeholder="Самец / Самка"></div>
    <div class="field"><label>Возраст</label><input name="age" value="<?=e((string)($meta['age']??''))?>" placeholder="5 месяцев"></div>
    <div class="field"><label>Цена</label><input name="price" value="<?=e((string)($meta['price']??''))?>" placeholder="25 000 ₽"></div>
    <div class="field"><label>Статус</label><input name="stock_status" value="<?=e((string)($meta['stock_status']??''))?>" placeholder="Продаётся / Бронь / Продано"></div>
   <?php elseif($isProject):?>
    <div class="field"><label>Тип</label><input name="project_kind" value="<?=e((string)($meta['project_kind']??''))?>" placeholder="Строительство / Вакансия / Проект"></div>
    <div class="field"><label>Место</label><input name="location" value="<?=e((string)($meta['location']??''))?>" placeholder="Никольское 3-е"></div>
    <div class="field"><label>Кнопка</label><input name="action_label" value="<?=e((string)($meta['action_label']??''))?>" placeholder="Откликнуться"></div>
    <div class="field"><label>Ссылка кнопки</label><input name="action_url" value="<?=e((string)($meta['action_url']??''))?>" placeholder="/page/kontakty"></div>
   <?php endif;?>
  </div>
 </section>

 <aside class="panel cms-showcase-editor__side">
  <div class="field"><label>Статус</label><select name="status"><option value="draft" <?=(string)($item['status']??'draft')==='draft'?'selected':''?>>Черновик</option><option value="published" <?=(string)($item['status']??'')==='published'?'selected':''?>>Опубликовано</option></select></div>
  <label class="check-row cms-promo-check"><input type="checkbox" name="is_featured" value="1" <?=!empty($item['is_featured'])?'checked':''?>> <span><b>Показывать на главной</b><small>Материал попадёт в рекламный слайдер своего раздела.</small></span></label>
  <div class="field"><label>Порядок в слайдере</label><input type="number" name="sort_order" min="-9999" max="9999" value="<?=(int)($item['sort_order']??0)?>"></div>
  <div class="field"><label>Главное фото</label><?php if(!empty($item['_image_url'])):?><img class="cms-showcase-editor__image" src="<?=e((string)$item['_image_url'])?>" alt=""><?php endif;?><input type="file" name="featured_image" accept="image/jpeg,image/png,image/webp"><small>JPEG, PNG или WebP.</small></div>
  <button class="button primary cms-showcase-save" type="submit">Сохранить</button>
 </aside>
</form>
