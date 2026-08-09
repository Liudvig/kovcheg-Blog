<?php
use Kovcheg\Blog\Blog;
use Kovcheg\Blog\ClassicEditor;

$type=(string)($entry['type']??'post')==='page'?'page':'post';
$isPost=$type==='post';
$isNew=empty($entry['id']);
$entryId=(int)($entry['id']??0);
$status=(string)($entry['status']??'draft');
$visibility=(string)($entry['visibility']??'public');
$publishedLocal=!empty($entry['published_at'])?date('Y-m-d\TH:i',strtotime((string)$entry['published_at'])):'';
$autosaveData=[];
if(!empty($autosave['content_json']))$autosaveData=['title'=>(string)($autosave['title']??''),'excerpt'=>(string)($autosave['excerpt']??''),'content'=>json_decode((string)$autosave['content_json'],true)?:[],'saved_at'=>(string)$autosave['saved_at']];
$classicHtml=ClassicEditor::sanitize((string)($entry['content_html']??''));
$publicUrl=!$isNew&&trim((string)($entry['slug']??''))!==''?Blog::entryUrl($entry):'';
$isPublishedNow=!$isNew&&Blog::isPubliclyReadable($entry);
$selectedCategories=array_map('intval',(array)($entry['category_ids']??[]));
$section=$isPost?'posts':'pages';
$entityName=$isPost?'запись':'страница';
?>
<form method="post" enctype="multipart/form-data" action="<?=e(app_url('/studio/entry/save'))?>" data-entry-form data-autosave-url="<?=e(app_url('/studio/content/autosave'))?>" data-app-url="<?=e(rtrim(app_url('/'),'/'))?>">
<?=csrf_field()?>
<input type="hidden" name="id" value="<?=$entryId?>">
<input type="hidden" name="type" value="<?=e($type)?>" data-entry-type>
<input type="hidden" name="editor_mode" value="classic" data-editor-mode>
<input type="hidden" name="content_json" value="<?=e((string)($entry['content_json']??'[]'))?>" data-block-json>
<input type="hidden" name="sort_order" value="0">
<input type="hidden" name="template" value="">
<input type="hidden" name="reactions_enabled" value="0">
<textarea hidden data-autosave-data><?=e(json_encode($autosaveData,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES))?></textarea>

<div class="page-head page-head--editor">
 <div><h1><?=$isNew?'Новая '.$entityName:'Редактирование: '.e((string)$entry['title'])?></h1><p><?=$isPost?'Запись публикуется в выбранных рубриках.':'Страница существует отдельно и добавляется в меню.'?> <span class="autosave-state" data-autosave-state>Автосохранение включено</span></p></div>
 <div class="editor-actions"><a class="button" href="<?=e(app_url('/studio/'.$section))?>">Назад</a><?php if(!$isNew):?><?php if($isPublishedNow):?><a class="button" target="_blank" rel="noopener" data-entry-public-link href="<?=e($publicUrl)?>">Открыть</a><?php else:?><a class="button" target="_blank" rel="noopener" href="<?=e(app_url('/studio/content/'.$entryId.'/preview'))?>">Предпросмотр</a><?php endif;?><?php endif;?><button class="button primary" type="submit">Сохранить</button></div>
</div>

<?php if($autosaveData):?><div class="autosave-restore" data-autosave-restore><div><b>Найдена автокопия</b><span><?=e((string)$autosaveData['saved_at'])?></span></div><button type="button" class="button small primary" data-restore-autosave>Восстановить</button><button type="button" class="button small" data-dismiss-autosave>Закрыть</button></div><?php endif;?>

<div class="editor-layout cms-editor-layout page-only-editor">
 <div class="editor-main">
  <section class="editor-card editor-card--basic">
   <div class="field"><label>Заголовок</label><input class="title-input" data-entry-title name="title" maxlength="255" required value="<?=e((string)($entry['title']??''))?>" placeholder="Введите заголовок"></div>
   <div class="field"><label>Краткое описание</label><textarea name="excerpt" rows="2" maxlength="2000" placeholder="Показывается в рубриках, списках и слайдерах."><?=e((string)($entry['excerpt']??''))?></textarea></div>
  </section>

  <section class="editor-card classic-editor-card" data-classic-editor-shell data-classic-id="classic-<?=($entryId?:'new')?>">
   <div class="classic-editor-panel" data-classic-panel>
    <div class="classic-editor-intro"><h2>Содержимое</h2><button type="button" class="button small" data-action="preview">Предпросмотр</button></div>
    <div class="classic-editor-shell">
     <div class="classic-editor-head"><button type="button" class="button small classic-editor-media-button" data-action="media">▧ Добавить медиафайл</button><div class="classic-editor-surface-tabs"><button type="button" class="active" data-classic-surface="visual">Визуально</button><button type="button" data-classic-surface="text">HTML</button></div></div>
     <div class="classic-editor-toolbar" data-classic-toolbar role="toolbar" aria-label="Форматирование">
      <select data-classic-format><option value="">Абзац</option><option value="p">Обычный текст</option><option value="h2">Заголовок 2</option><option value="h3">Заголовок 3</option><option value="h4">Заголовок 4</option><option value="pre">Код</option></select><span class="classic-editor-separator"></span>
      <button type="button" data-command="bold"><b>B</b></button><button type="button" data-command="italic"><i>I</i></button><button type="button" data-command="underline"><u>U</u></button><span class="classic-editor-separator"></span>
      <button type="button" data-command="insertUnorderedList">•≡</button><button type="button" data-command="insertOrderedList">1.</button><button type="button" data-command="formatBlock" data-value="blockquote">❝</button><span class="classic-editor-separator"></span>
      <button type="button" data-action="link">🔗</button><button type="button" data-action="unlink">⛓</button><button type="button" data-command="removeFormat">Tx</button><span class="classic-editor-separator"></span>
      <button type="button" data-command="undo">↶</button><button type="button" data-command="redo">↷</button><button type="button" data-action="fullscreen">⛶</button>
     </div>
     <div class="classic-editor-visual" contenteditable="true" spellcheck="true" data-classic-visual data-placeholder="Начните писать…"><?=$classicHtml?></div>
     <textarea class="classic-editor-source" data-classic-source hidden><?=e($classicHtml)?></textarea>
     <div class="classic-editor-status"><span data-classic-count>0 слов · 0 знаков</span></div>
    </div>
   </div>
  </section>

  <?php if($revisions):?><details class="editor-card editor-details"><summary>История изменений (<?=count($revisions)?>)</summary><div class="simple-list"><?php foreach($revisions as $revision):?><article><div><b><?=e($revision['title'])?></b><small><?=e($revision['created_at'])?></small></div><form method="post" data-confirm="Восстановить эту версию?" action="<?=e(app_url('/studio/revisions/'.(int)$revision['id'].'/restore'))?>"><?=csrf_field()?><button class="button small">Восстановить</button></form></article><?php endforeach;?></div></details><?php endif;?>
 </div>

 <aside class="editor-side">
  <section class="editor-card"><h3>Публикация</h3><div class="field"><label>Статус</label><select name="status"><option value="draft" <?=$status==='draft'?'selected':''?>>Черновик</option><option value="published" <?=$status==='published'?'selected':''?>>Опубликовано</option><option value="scheduled" <?=$status==='scheduled'?'selected':''?>>Запланировано</option><option value="private" <?=$status==='private'?'selected':''?>>Личное</option></select></div><div class="field"><label>Дата</label><input type="datetime-local" name="published_at" value="<?=e($publishedLocal)?>"></div><button class="button primary" type="submit">Сохранить</button></section>

  <section class="editor-card editor-permalink-card"><h3>Адрес</h3><div class="field"><div class="editor-permalink-line"><input type="text" readonly data-entry-public-url value="<?=e($publicUrl)?>" placeholder="Появится после сохранения"><button type="button" class="button small" data-copy-public-url <?=$publicUrl===''?'disabled':''?>>Копировать</button></div></div><div class="field"><label>Ярлык</label><input data-entry-slug name="slug" maxlength="190" value="<?=e((string)($entry['slug']??''))?>" placeholder="adres-materiala"></div><?php if(!$isNew&&$isPublishedNow):?><div class="editor-link-actions"><a class="button small" target="_blank" rel="noopener" data-entry-public-link href="<?=e($publicUrl)?>">Открыть</a><?php if(!$isPost):?><a class="button small" href="<?=e(app_url('/studio/menus?entry='.$entryId))?>">Добавить в меню</a><?php endif;?></div><?php endif;?></section>

  <?php if($isPost):?><section class="editor-card"><div class="editor-card-title"><h3>Рубрики</h3><a href="<?=e(app_url('/studio/categories'))?>">Управление</a></div><p class="field-help">Рубрика создаёт самостоятельный раздел сайта с выбранными записями.</p><div class="check-list"><?php foreach($categories as $category):?><label class="check-row"><input type="checkbox" name="category_ids[]" value="<?=(int)$category['id']?>" <?=in_array((int)$category['id'],$selectedCategories,true)?'checked':''?>> <?=e($category['name'])?></label><?php endforeach;?><?php if(!$categories):?><small>Сначала создайте рубрику.</small><?php endif;?></div></section><?php endif;?>

  <section class="editor-card" data-upload-block><h3>Обложка</h3><input type="hidden" name="featured_image_path" data-feature-path value="<?=e((string)($entry['featured_image_path']??''))?>"><input type="hidden" name="featured_folder_id" value="0"><label class="upload-zone upload-zone--compact" data-upload-zone><input type="file" name="featured_image" accept="image/jpeg,image/png,image/webp"><span class="upload-zone__icon">▧</span><b>Выбрать файл</b><small>JPEG, PNG или WebP</small></label><div class="upload-selection" data-upload-selection></div><?php if($media):?><div class="media-picker media-picker--compact"><?php foreach(array_slice($media,0,12) as $item):?><button type="button" class="<?=($entry['featured_image_path']??'')===$item['stored_path']?'active':''?>" data-media-path="<?=e($item['stored_path'])?>" data-media-url="<?=e(app_url('/media/'.(int)$item['id']))?>"><img src="<?=e(app_url('/media/'.(int)$item['id']))?>" alt=""></button><?php endforeach;?></div><?php endif;?></section>

  <details class="editor-card editor-details"><summary>Дополнительно</summary><div class="field"><label>Видимость</label><select name="visibility"><option value="public" <?=$visibility==='public'?'selected':''?>>Всем</option><option value="users" <?=$visibility==='users'?'selected':''?>>Только пользователям</option><option value="private" <?=$visibility==='private'?'selected':''?>>Только администрации</option></select></div><label class="check-row"><input type="checkbox" name="is_featured" value="1" <?=!empty($entry['is_featured'])?'checked':''?>> Показывать первой</label><label class="check-row"><input type="checkbox" name="comments_enabled" value="1" <?=!empty($entry['comments_enabled'])?'checked':''?>> Разрешить комментарии</label><div class="field"><label>SEO-заголовок</label><input name="seo_title" maxlength="255" value="<?=e((string)($entry['seo_title']??''))?>"></div><div class="field"><label>SEO-описание</label><textarea name="seo_description" rows="2" maxlength="320"><?=e((string)($entry['seo_description']??''))?></textarea></div></details>
 </aside>
</div>
</form>

<div class="classic-editor-modal" data-classic-media-modal hidden><div class="classic-editor-modal__dialog" role="dialog" aria-modal="true"><header class="classic-editor-modal__head"><div><h2>Медиафайлы</h2><small>Загрузите изображение или выберите существующее.</small></div><button type="button" data-close-classic-media>×</button></header><?php if($media):?><div class="classic-editor-media-grid"><?php foreach($media as $item):?><button type="button" data-classic-media-item data-media-url="<?=e(app_url('/media/'.(int)$item['id']))?>" data-media-title="<?=e((string)($item['title']??$item['original_name']??''))?>" data-media-alt="<?=e((string)($item['alt_text']??''))?>" data-media-caption="<?=e((string)($item['caption']??''))?>"><img src="<?=e(app_url('/media/'.(int)$item['id']))?>" alt=""><span><?=e((string)($item['title']??$item['original_name']??'Изображение'))?></span></button><?php endforeach;?></div><?php else:?><div class="empty-state">Изображений пока нет.</div><?php endif;?></div></div>
<div class="classic-editor-preview" data-classic-preview hidden><div class="classic-editor-preview__dialog"><header class="classic-editor-preview__head"><h2>Предпросмотр</h2><button type="button" data-close-classic-preview>×</button></header><iframe title="Предпросмотр" scrolling="yes"></iframe></div></div>