<?php
$current=$current??(\Kovcheg\Auth::user()??[]);
$action=$action??app_url('/feed/post');
$placeholder=$placeholder??'Напишите что-нибудь…';
$composerContext=$composerContext??'feed';
$canCreate=$canCreate??($composerContext!=='readonly');
$allowDeferred=$allowDeferred??true;
$classicVk=(bool)($classicVk??false);
$clientPostToken=bin2hex(random_bytes(20));
?>
<section class="wall-create-shell wall-create-160 <?=$classicVk?'vk-classic-composer-shell':''?>" data-wall-create-shell>
 <div class="wall-create-bar <?=$canCreate?'':'search-only'?>">
  <?php if($canCreate):?><button type="button" class="wall-create-primary" data-wall-composer-open><span><?=$classicVk?'':'＋'?></span><b><?=$classicVk?'Написать сообщение…':'Создать запись'?></b></button><?php endif;?>
  <div class="wall-create-tools">
   <?php if($classicVk&&$canCreate):?>
    <button type="button" data-wall-composer-open title="Добавить фотографию" aria-label="Добавить фотографию">▣</button>
    <button type="button" data-wall-composer-open title="Добавить видео" aria-label="Добавить видео">▥</button>
    <button type="button" data-wall-composer-open title="Добавить музыку" aria-label="Добавить музыку">♫</button>
   <?php else:?>
    <?php if($canCreate):?><button type="button" class="wall-create-drafts" data-wall-drafts-open title="Черновики" aria-label="Черновики"><span>☷</span><b>Черновики</b><em data-wall-draft-count hidden></em></button><?php endif;?>
    <button type="button" class="wall-create-search" data-wall-post-search-toggle title="Поиск по записям" aria-label="Поиск по записям">⌕</button>
   <?php endif;?>
  </div>
 </div>
 <div class="wall-post-search" data-wall-post-search hidden><span>⌕</span><input type="search" placeholder="Поиск по записям" autocomplete="off" data-wall-post-search-input><button type="button" data-wall-post-search-close aria-label="Закрыть">×</button></div>

 <?php if($canCreate):?>
 <div class="wall-publisher-modal" data-wall-publisher-modal hidden>
  <form class="wall-publisher wall-composer-modern" method="post" enctype="multipart/form-data" action="<?=e($action)?>" data-wall-composer data-wall-publisher data-modern-wall-composer data-composer-context="<?=e($composerContext)?>">
   <?=csrf_field()?>
   <input type="hidden" name="client_post_token" value="<?=e($clientPostToken)?>" data-client-post-token>
   <header class="wall-publisher-head"><b data-wall-publisher-title>Новая запись</b><button type="button" class="wall-publisher-close" data-wall-publisher-close aria-label="Закрыть">×</button></header>
   <section class="wall-publisher-step active" data-wall-publisher-step="content">
    <div class="wall-publisher-text modern-wall-text"><?=avatar_html($current,'avatar-sm')?><textarea name="body" rows="4" maxlength="5000" placeholder="<?=e($placeholder)?>"></textarea><button type="button" class="wall-publisher-emoji" aria-label="Эмодзи">☺</button></div>
    <div class="wall-unified-dropzone" data-wall-unified-dropzone tabindex="0" role="button" aria-label="Добавить фото, видео или документы">
     <input type="file" multiple accept="image/jpeg,image/png,image/webp,video/mp4,video/webm,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.zip,.rar,.7z,audio/mpeg,audio/mp4,audio/ogg,audio/wav,audio/flac" data-wall-unified-picker hidden>
     <input type="file" name="photos[]" multiple accept="image/jpeg,image/png,image/webp" data-wall-photos hidden>
     <input type="file" name="videos[]" multiple accept="video/mp4,video/webm" data-wall-videos hidden>
     <input type="file" name="documents[]" multiple data-wall-documents hidden>
     <span class="wall-upload-symbol">＋</span><div><strong>Добавить несколько файлов</strong><small>Нажмите сюда или перетащите фото, видео и документы</small></div>
    </div>
    <div class="wall-unified-preview" data-wall-unified-preview hidden></div>
    <details class="wall-publish-options"><summary><span>⚙</span><b>Настройки публикации</b><small>Видимость и время</small></summary><div class="wall-publish-options-grid"><label>Кто увидит<select name="visibility"><option value="everyone">Все</option><option value="users">Пользователи сайта</option><option value="colleagues">Только коллеги</option><option value="only_me">Только я</option></select></label><label>Когда опубликовать<select name="publish_mode" data-wall-publish-mode><option value="now">Сейчас</option><?php if($allowDeferred):?><option value="scheduled">По времени</option><option value="draft">Сохранить в черновиках</option><?php endif;?></select></label><?php if($allowDeferred):?><label class="wall-schedule-field" data-wall-schedule-field hidden>Дата и время<input type="datetime-local" name="publish_at" min="<?=e(date('Y-m-d\TH:i',time()+300))?>"></label><?php endif;?></div></details>
   </section>
   <footer class="wall-publisher-footer modern-wall-footer"><small data-wall-file-summary>Файлы не выбраны</small><button type="submit" class="btn btn-primary modern-wall-submit">Опубликовать</button></footer>
  </form>
 </div>
 <div class="wall-drafts-modal" data-wall-drafts-modal hidden><div class="wall-drafts-card"><header><div><b>Черновики и отложенные записи</b><small>Доступны только вам</small></div><button type="button" data-wall-drafts-close>×</button></header><div class="wall-drafts-list" data-wall-drafts-list><p class="muted">Загрузка…</p></div></div></div>
 <?php endif;?>
</section>
