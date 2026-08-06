<?php
use Kovcheg\Blog\Builder;
use Kovcheg\Blog\ClassicEditor;

$typeLabels=['post'=>'Публикация','page'=>'Страница','portfolio'=>'Работа портфолио'];
$isNew=empty($entry['id']);
$publishedLocal='';
if(!empty($entry['published_at']))$publishedLocal=date('Y-m-d\TH:i',strtotime((string)$entry['published_at']));
$meta=is_array($entry['meta']??null)?$entry['meta']:[];
$patternLibrary=[];
foreach((array)($patterns??[]) as $pattern){
    $patternLibrary[(string)$pattern['slug']] = [
        'name'=>(string)$pattern['name'],
        'description'=>(string)($pattern['description']??''),
        'blocks'=>json_decode((string)$pattern['blocks_json'],true)?:[],
        'id'=>(int)($pattern['id']??0),
    ];
}
$autosaveData=[];
if(!empty($autosave['content_json'])){
    $autosaveData=[
        'title'=>(string)($autosave['title']??''),
        'excerpt'=>(string)($autosave['excerpt']??''),
        'content'=>json_decode((string)$autosave['content_json'],true)?:[],
        'saved_at'=>(string)$autosave['saved_at'],
    ];
}
$storedJson=(string)($entry['content_json']??'[]');
$isClassicStored=ClassicEditor::isClassicPayload($storedJson);
$builderJson=$isClassicStored?'[]':$storedJson;
$classicHtml=ClassicEditor::sanitize((string)($entry['content_html']??''));
$classicId='classic-'.((int)($entry['id']??0)?:'new');
?>
<form method="post" enctype="multipart/form-data" action="<?=e(app_url('/studio/content/save'))?>" data-entry-form data-autosave-url="<?=e(app_url('/studio/content/autosave'))?>">
<?=csrf_field()?>
<input type="hidden" name="id" value="<?=(int)($entry['id']??0)?>">
<input type="hidden" name="editor_mode" value="classic" data-editor-mode>
<input type="hidden" name="content_json" value="<?=e($builderJson)?>" data-block-json>
<textarea hidden data-pattern-library><?=e(json_encode($patternLibrary,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES))?></textarea>
<textarea hidden data-autosave-data><?=e(json_encode($autosaveData,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES))?></textarea>

<div class="page-head">
 <div><h1><?=$isNew?'Новый материал':'Редактирование материала'?></h1><p><?=e($typeLabels[$entry['type']]??'Материал')?> · <?=$isNew?'ещё не сохранён':'ID '.(int)$entry['id']?> <span class="autosave-state" data-autosave-state>Изменения сохраняются автоматически</span></p></div>
 <div class="editor-actions"><a class="button" href="<?=e(app_url('/studio/content'))?>">К списку</a><?php if(!$isNew&&$entry['status']==='published'):?><a class="button" target="_blank" href="<?=e(\Kovcheg\Blog\Blog::entryUrl($entry))?>">Открыть на сайте</a><?php endif;?><button class="button primary" type="submit">Сохранить</button></div>
</div>

<?php if($autosaveData):?>
<div class="autosave-restore" data-autosave-restore><div><b>Найдена автоматическая копия</b><span>Сохранена <?=e((string)$autosaveData['saved_at'])?></span></div><button type="button" class="button small primary" data-restore-autosave>Восстановить</button><button type="button" class="button small" data-dismiss-autosave>Не использовать</button></div>
<?php endif;?>

<div class="editor-layout">
 <div class="editor-main">
  <section class="editor-card">
   <div class="field"><label>Заголовок</label><input class="title-input" data-entry-title name="title" maxlength="255" required value="<?=e((string)$entry['title'])?>" placeholder="Название публикации"></div>
   <div class="form-grid"><div class="field"><label>Адрес</label><input data-entry-slug name="slug" maxlength="190" value="<?=e((string)$entry['slug'])?>" placeholder="adres-materiala"></div><div class="field"><label>Тип</label><select name="type"><?php foreach($typeLabels as $key=>$label):?><option value="<?=e($key)?>" <?=$entry['type']===$key?'selected':''?>><?=e($label)?></option><?php endforeach;?></select></div></div>
   <div class="field"><label>Краткое описание</label><textarea name="excerpt" rows="4" maxlength="2000" placeholder="Анонс для карточки и поисковых систем"><?=e((string)($entry['excerpt']??''))?></textarea></div>
  </section>

  <section class="editor-card classic-editor-card" data-classic-editor-shell data-classic-id="<?=e($classicId)?>">
   <div class="editor-mode-tabs" role="tablist" aria-label="Режим редактора">
    <button type="button" class="active" role="tab" aria-selected="true" data-editor-tab="classic">Классический редактор</button>
    <button type="button" role="tab" aria-selected="false" data-editor-tab="builder">Конструктор секций</button>
   </div>

   <div class="classic-editor-panel" data-classic-panel>
    <div class="classic-editor-intro"><div><h2>Текст материала</h2><p>Обычный редактор в стиле классического WordPress: пишите и оформляйте текст как в документе.</p></div><button type="button" class="button small" data-action="preview">Предпросмотр</button></div>
    <div class="classic-editor-shell">
     <div class="classic-editor-head">
      <button type="button" class="button small classic-editor-media-button" data-action="media">▧ Добавить медиафайл</button>
      <div class="classic-editor-surface-tabs" aria-label="Режим отображения"><button type="button" class="active" data-classic-surface="visual">Визуально</button><button type="button" data-classic-surface="text">Текст</button></div>
     </div>
     <div class="classic-editor-toolbar" data-classic-toolbar role="toolbar" aria-label="Форматирование текста">
      <select data-classic-format aria-label="Формат абзаца"><option value="">Формат</option><option value="p">Абзац</option><option value="h2">Заголовок 2</option><option value="h3">Заголовок 3</option><option value="h4">Заголовок 4</option><option value="pre">Код</option></select>
      <span class="classic-editor-separator"></span>
      <button type="button" data-command="bold" title="Жирный (Ctrl+B)"><b>B</b></button>
      <button type="button" data-command="italic" title="Курсив (Ctrl+I)"><i>I</i></button>
      <button type="button" data-command="underline" title="Подчёркнутый (Ctrl+U)"><u>U</u></button>
      <button type="button" data-command="strikeThrough" title="Зачёркнутый"><s>S</s></button>
      <span class="classic-editor-separator"></span>
      <button type="button" data-command="insertUnorderedList" title="Маркированный список">•≡</button>
      <button type="button" data-command="insertOrderedList" title="Нумерованный список">1.</button>
      <button type="button" data-command="formatBlock" data-value="blockquote" title="Цитата">❝</button>
      <span class="classic-editor-separator"></span>
      <button type="button" data-command="justifyLeft" title="По левому краю">≡</button>
      <button type="button" data-command="justifyCenter" title="По центру">≡</button>
      <button type="button" data-command="justifyRight" title="По правому краю">≡</button>
      <span class="classic-editor-separator"></span>
      <button type="button" data-action="link" title="Вставить ссылку (Ctrl+K)">🔗</button>
      <button type="button" data-action="unlink" title="Удалить ссылку">⛓</button>
      <button type="button" data-command="insertHorizontalRule" title="Горизонтальная линия">―</button>
      <button type="button" data-command="removeFormat" title="Очистить форматирование">Tx</button>
      <span class="classic-editor-separator"></span>
      <button type="button" data-command="undo" title="Отменить">↶</button>
      <button type="button" data-command="redo" title="Повторить">↷</button>
      <button type="button" data-action="fullscreen" aria-pressed="false" title="На весь экран">⛶</button>
     </div>
     <div class="classic-editor-visual" contenteditable="true" spellcheck="true" data-classic-visual data-placeholder="Начните писать текст…"><?=$classicHtml?></div>
     <textarea class="classic-editor-source" data-classic-source hidden aria-label="HTML-код материала"><?=e($classicHtml)?></textarea>
     <div class="classic-editor-status"><span data-classic-count>0 слов · 0 знаков</span><span>Содержимое очищается от опасного кода при сохранении</span></div>
    </div>
    <p class="classic-editor-help"><kbd>Ctrl+B</kbd> жирный · <kbd>Ctrl+I</kbd> курсив · <kbd>Ctrl+K</kbd> ссылка · <kbd>Ctrl+S</kbd> сохранить</p>
   </div>

   <div class="classic-editor-panel" data-builder-panel hidden>
    <div class="builder-panel-note"><b>Конструктор секций сохранён как дополнительный режим.</b> При сохранении в этом режиме содержимое классического редактора будет заменено набором секций.</div>
    <div class="builder-heading"><div><h2>Визуальный конструктор</h2><p>Собирайте специальные страницы из секций и перемещайте их мышкой или стрелками.</p></div><div class="builder-tools"><button type="button" class="button small" data-preview-builder>Предпросмотр</button><button type="button" class="button small" data-save-pattern>Сохранить как шаблон</button></div></div>
    <details class="pattern-picker"><summary>Готовые шаблоны секций</summary><div><?php foreach($patternLibrary as $slug=>$pattern):?><button type="button" data-insert-pattern="<?=e($slug)?>"><b><?=e($pattern['name'])?></b><span><?=e($pattern['description'])?></span></button><?php endforeach;?></div></details>
    <div class="block-toolbar" data-block-toolbar><?php foreach(Builder::types() as $key=>$label):?><button type="button" data-add-block="<?=e($key)?>">+ <?=e($label)?></button><?php endforeach;?></div>
    <div class="block-editor" data-block-editor></div>
   </div>
  </section>

  <?php if($entry['type']==='portfolio'):?><section class="editor-card"><h2>Карточка проекта</h2><div class="form-grid"><div class="field"><label>Заказчик или проект</label><input name="portfolio_client" value="<?=e((string)($meta['client']??''))?>"></div><div class="field"><label>Год</label><input name="portfolio_year" maxlength="20" value="<?=e((string)($meta['year']??''))?>"></div><div class="field"><label>Роль / специализация</label><input name="portfolio_role" value="<?=e((string)($meta['role']??''))?>"></div><div class="field"><label>Ссылка на проект</label><input name="portfolio_url" value="<?=e((string)($meta['project_url']??''))?>" placeholder="https://"></div></div></section><?php endif;?>

  <section class="editor-card"><h2>Оформление материала</h2><div class="form-grid"><div class="field"><label>Ширина содержания</label><select name="layout_width"><option value="narrow" <?=($meta['layout_width']??'')==='narrow'?'selected':''?>>Узкая</option><option value="normal" <?=($meta['layout_width']??'normal')==='normal'?'selected':''?>>Обычная</option><option value="wide" <?=($meta['layout_width']??'')==='wide'?'selected':''?>>Широкая</option><option value="full" <?=($meta['layout_width']??'')==='full'?'selected':''?>>На всю ширину</option></select></div><div class="field"><label>Акцент материала</label><input type="color" name="accent" value="<?=e((string)($meta['accent']??'#ef6b49'))?>"></div></div></section>

  <section class="editor-card"><h2>SEO</h2><div class="field"><label>SEO-заголовок</label><input name="seo_title" maxlength="255" value="<?=e((string)($entry['seo_title']??''))?>"><small>Оставьте пустым, чтобы использовать основной заголовок.</small></div><div class="field"><label>Описание для поиска и соцсетей</label><textarea name="seo_description" rows="3" maxlength="320"><?=e((string)($entry['seo_description']??''))?></textarea></div></section>

  <?php if($revisions):?><section class="editor-card"><h2>История изменений</h2><div class="simple-list"><?php foreach($revisions as $revision):?><article><div><b><?=e($revision['title'])?></b><small><?=e($revision['author_name'])?> · <?=e($revision['created_at'])?></small></div><form method="post" data-confirm="Восстановить эту версию? Текущая версия сохранится в истории." action="<?=e(app_url('/studio/revisions/'.(int)$revision['id'].'/restore'))?>"><?=csrf_field()?><button class="button small">Восстановить</button></form></article><?php endforeach;?></div></section><?php endif;?>
 </div>

 <aside class="editor-side">
  <section class="editor-card"><h3>Публикация</h3><div class="field"><label>Статус</label><select name="status"><option value="draft" <?=$entry['status']==='draft'?'selected':''?>>Черновик</option><option value="published" <?=$entry['status']==='published'?'selected':''?>>Опубликовано</option><option value="scheduled" <?=$entry['status']==='scheduled'?'selected':''?>>Запланировано</option><option value="private" <?=$entry['status']==='private'?'selected':''?>>Приватно</option></select></div><div class="field"><label>Дата публикации</label><input type="datetime-local" name="published_at" value="<?=e($publishedLocal)?>"></div><div class="field"><label>Видимость</label><select name="visibility"><option value="public" <?=$entry['visibility']==='public'?'selected':''?>>Всем</option><option value="users" <?=$entry['visibility']==='users'?'selected':''?>>Только пользователям</option><option value="private" <?=$entry['visibility']==='private'?'selected':''?>>Только администрации</option></select></div><label class="check-row"><input type="checkbox" name="is_featured" value="1" <?=!empty($entry['is_featured'])?'checked':''?>> Закрепить / выделить</label><label class="check-row"><input type="checkbox" name="comments_enabled" value="1" <?=!empty($entry['comments_enabled'])?'checked':''?>> Разрешить комментарии</label><label class="check-row"><input type="checkbox" name="reactions_enabled" value="1" <?=!empty($entry['reactions_enabled'])?'checked':''?>> Разрешить реакции</label><div class="field"><label>Порядок</label><input type="number" name="sort_order" value="<?=(int)($entry['sort_order']??0)?>"></div><button class="button primary" type="submit">Сохранить материал</button></section>

  <section class="editor-card" data-upload-block><h3>Обложка</h3><input type="hidden" name="featured_image_path" data-feature-path value="<?=e((string)($entry['featured_image_path']??''))?>"><div class="field"><label>Папка загрузки</label><select name="featured_folder_id"><option value="0">Общее</option><?php foreach((array)($mediaFolders??[]) as $folder):?><option value="<?=(int)$folder['id']?>"><?=e($folder['name'])?></option><?php endforeach;?></select></div><label class="upload-zone" data-upload-zone><input type="file" name="featured_image" accept="image/jpeg,image/png,image/webp"><span class="upload-zone__icon">▧</span><b>Перетащите обложку сюда</b><span>или нажмите, чтобы выбрать изображение</span><small>JPEG, PNG или WebP</small></label><div class="upload-selection" data-upload-selection></div><?php if($media):?><div class="media-picker" style="margin-top:14px"><?php foreach($media as $item):?><button type="button" class="<?=$entry['featured_image_path']===$item['stored_path']?'active':''?>" data-media-path="<?=e($item['stored_path'])?>" data-media-url="<?=e(app_url('/media/'.(int)$item['id']))?>" title="<?=e($item['title']??$item['original_name'])?>"><img src="<?=e(app_url('/media/'.(int)$item['id']))?>" alt=""></button><?php endforeach;?></div><?php else:?><small>В медиатеке пока нет изображений.</small><?php endif;?></section>

  <section class="editor-card"><h3>Рубрики</h3><div class="check-list"><?php foreach($categories as $category):?><label class="check-row"><input type="checkbox" name="category_ids[]" value="<?=(int)$category['id']?>" <?=in_array((int)$category['id'],array_map('intval',(array)$entry['category_ids']),true)?'checked':''?>> <?=e($category['name'])?></label><?php endforeach;?></div><a class="button small" href="<?=e(app_url('/studio/categories'))?>">Управление рубриками</a></section>
  <section class="editor-card"><h3>Теги</h3><div class="field"><input name="tags" value="<?=e((string)($entry['tags_text']??''))?>" placeholder="разработка, новости, KOVCHEG"><small>Разделяйте запятыми.</small></div></section>
  <section class="editor-card"><h3>Шаблон страницы</h3><div class="field"><input name="template" maxlength="80" value="<?=e((string)($entry['template']??''))?>" placeholder="default"></div></section>
 </aside>
</div>
</form>

<div class="classic-editor-modal" data-classic-media-modal hidden>
 <div class="classic-editor-modal__dialog" role="dialog" aria-modal="true" aria-label="Выбор медиафайла">
  <header class="classic-editor-modal__head"><div><h2>Добавить медиафайл</h2><small>Нажмите на изображение, чтобы вставить его в текст.</small></div><button type="button" data-close-classic-media aria-label="Закрыть">×</button></header>
  <?php if($media):?><div class="classic-editor-media-grid"><?php foreach($media as $item):?><button type="button" data-classic-media-item data-media-url="<?=e(app_url('/media/'.(int)$item['id']))?>" data-media-title="<?=e((string)($item['title']??$item['original_name']??''))?>" data-media-alt="<?=e((string)($item['alt_text']??''))?>" data-media-caption="<?=e((string)($item['caption']??''))?>"><img src="<?=e(app_url('/media/'.(int)$item['id']))?>" alt=""><span><?=e((string)($item['title']??$item['original_name']??'Изображение'))?></span></button><?php endforeach;?></div><?php else:?><div class="empty-state">В медиатеке пока нет изображений. Сначала загрузите файл в разделе «Медиатека».</div><?php endif;?>
 </div>
</div>

<div class="classic-editor-preview" data-classic-preview hidden><div class="classic-editor-preview__dialog"><header class="classic-editor-preview__head"><h2>Предпросмотр материала</h2><button type="button" data-close-classic-preview aria-label="Закрыть">×</button></header><iframe title="Предпросмотр классического редактора"></iframe></div></div>
<div class="builder-preview" data-builder-preview hidden><button type="button" data-close-preview>×</button><iframe title="Предпросмотр конструктора секций"></iframe></div>
