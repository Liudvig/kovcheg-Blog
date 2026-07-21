<?php
$isChannelPublisher=!isset($chat)||!$chat||($chat['type']??'')!=='channel'||channel_can_post($chat,$currentMembership??[]);
$canPublish=$isChannelPublisher&&!empty($permissions['can_send_messages']);
$canManage=$chat&&$chat['type']==='channel'&&channel_can_manage($currentMembership??[]);
$channelSettingsId=$chat?'channel-settings-'.(int)$chat['id']:'';
?>
<section class="conversation <?=($chat&&($chat['type']??'')==='channel')?'channel-conversation':''?>" data-conversation data-chat-type="<?=e((string)($chat['type']??''))?>">
<?php if($chat):?>
 <header class="conversation-head">
  <button type="button" class="mobile-back" data-mobile-chat-back aria-label="Вернуться к списку диалогов" title="К списку диалогов">‹</button>
  <?=chat_avatar_html($chat,$chat['avatar_user']??null,'avatar')?>
  <div class="conversation-title">
   <h1><?=e($chat['display_title'])?><?=!empty($chat['avatar_user'])?verified_badge($chat['avatar_user']):''?></h1>
   <small><?php if($chat['type']==='channel'):?><?=e(count($members).' подписчик(а)')?><?php if(!empty($chat['username'])):?> · @<?=e($chat['username'])?><?php endif;?><?php else:?><?php if(!empty($chat['avatar_user']['username'])):?><a href="<?=e(user_public_url($chat['avatar_user']['username']))?>">@<?=e($chat['avatar_user']['username'])?></a> · <?php endif;?><span data-presence-user="<?=(int)($chat['avatar_user']['id']??0)?>"><?=e($chat['other_online']?'в сети':'был(а) '.human_time($chat['other_last_seen']??null))?></span><?php endif;?></small>
  </div>
  <div class="conversation-head-actions">
   <?php if($canManage):?><button class="conversation-settings-btn" type="button" data-modal="<?=e($channelSettingsId)?>" title="Настройки канала" aria-label="Настройки канала">⚙</button><?php endif;?>
  </div>
 </header>
 <button type="button" data-select-messages hidden aria-hidden="true"></button>
 <div class="messages-wrap"><div class="messages" id="messages" data-file-drop><?php foreach($messages as $message)echo \Kovcheg\View::partial('message',['m'=>$message]);?><div class="drop-overlay"><b>Отпустите файлы для отправки</b><span>Можно выбрать несколько файлов</span></div></div><button type="button" class="scroll-bottom-button" data-scroll-bottom hidden aria-label="К новым сообщениям">⌄<em data-scroll-unread hidden></em></button></div>
 <div class="message-selection-bar" data-message-selection-bar hidden><button type="button" class="btn" data-selection-cancel>Отмена</button><b><span data-selection-count>0</span> выбрано</b><div class="selection-actions"><button type="button" class="btn" data-selection-comment disabled>Прокомментировать</button><button type="button" class="btn btn-primary" data-selection-forward disabled>Переслать</button></div></div>
 <?php if($canPublish):?><form id="composer" class="composer composer-modern" enctype="multipart/form-data"><input type="hidden" name="chat_id" value="<?=(int)$chat['id']?>"><input type="hidden" name="reply_to_id" value=""><input type="hidden" name="edit_message_id" value=""><input type="hidden" name="comment_message_ids" value=""><div class="composer-context" data-composer-context hidden><span class="context-line"></span><div><b data-context-title></b><small data-context-text></small></div><button type="button" data-context-cancel>×</button></div><div class="composer-attachment-strip" data-composer-attachment-strip><div class="file-preview"></div></div><div class="composer-row"><div class="composer-tools" aria-label="Инструменты сообщения"><button type="button" class="icon-btn composer-tool" data-emoji title="Эмодзи">☺</button><?php if(!empty($permissions['can_send_stickers'])):?><button type="button" class="icon-btn composer-tool" data-stickers title="Стикеры">▣</button><?php endif;?><?php if(!empty($permissions['can_send_voice'])):?><button type="button" class="icon-btn composer-tool" data-voice title="Голосовое сообщение">🎙</button><?php endif;?><div class="composer-attach-wrap" data-composer-attach-wrap><button type="button" class="icon-btn composer-tool composer-attach-toggle" data-composer-attach-toggle aria-expanded="false" title="Прикрепить">📎</button><div class="composer-attach-menu" data-composer-attach-menu hidden><?php if(!empty($permissions['can_send_files'])):?><label class="composer-attach-action file-pick"><span class="composer-attach-icon">▤</span><span><b>Документ</b><small>PDF, Office, текст или архив</small></span><input type="file" name="files[]" data-composer-file-kind="document" accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.rtf,.odt,.ods,.csv,.zip,.rar,.7z,application/pdf,text/plain,application/zip" multiple hidden></label><label class="composer-attach-action file-pick"><span class="composer-attach-icon">▧</span><span><b>Фото</b><small>JPG, PNG, WebP и GIF</small></span><input type="file" name="files[]" data-composer-file-kind="photo" accept="image/*" multiple hidden></label><?php endif;?></div></div></div><div class="composer-input-box" data-composer-input-box><textarea name="body" rows="1" placeholder="<?=$chat['type']==='channel'?'Публикация':'Сообщение'?>" maxlength="10000"></textarea><div class="voice-recorder-panel voice-recorder-inline" data-voice-panel hidden><button type="button" class="voice-cancel" data-voice-cancel title="Отменить запись">×</button><span class="voice-record-dot"></span><b data-voice-timer>0:00</b><div class="voice-wave-live" aria-hidden="true"><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i></div><small>Нажмите отправить</small></div></div><button class="send-btn" type="submit" title="Отправить">➤</button></div><div class="picker emoji-picker" hidden aria-hidden="true"></div><?php if(!empty($permissions['can_send_stickers'])):?><div class="picker sticker-picker" hidden aria-hidden="true"><?php foreach($stickers as $sticker):?><button type="button" data-sticker-code="sticker:<?=(int)$sticker['id']?>" title="<?=e($sticker['pack_name'].' — '.$sticker['name'])?>"><img src="<?=e(app_url('/sticker/'.$sticker['id']))?>" alt="<?=e($sticker['name'])?>"></button><?php endforeach;?></div><?php endif;?></form>
 <?php else:?><div class="read-only-composer"><?=($chat['type']??'')==='channel'?'Публиковать могут только назначенные администраторы.':'Отправка сообщений отключена.'?></div><?php endif;?>

 <?php if($canManage):?>
 <div class="modal modern-sheet channel-settings-modal" id="<?=e($channelSettingsId)?>" hidden>
  <div class="modal-card channel-settings-card">
   <button class="modal-close" type="button" aria-label="Закрыть">×</button>
   <div class="channel-settings-heading"><div><?=chat_avatar_html($chat,null,'avatar')?></div><span><h2>Настройки канала</h2><small><?=e($chat['display_title'])?></small></span></div>
   <div class="channel-settings-tabs" role="tablist">
    <button type="button" class="active" data-channel-tab="general">Основные</button>
    <button type="button" data-channel-tab="avatar">Аватар</button>
    <button type="button" data-channel-tab="invites">Приглашения</button>
   </div>
   <section class="channel-settings-panel active" data-channel-panel="general">
    <form class="settings-form flat" method="post" action="<?=e(app_url('/ajax/channel/'.(int)$chat['id'].'/settings'))?>" data-ajax-form>
     <?=csrf_field()?>
     <label>Название канала<input name="title" value="<?=e($chat['title']??'')?>" maxlength="120" required></label>
     <label>Описание<textarea name="description" rows="4" maxlength="2000" placeholder="О чём этот канал"><?=e($chat['description']??'')?></textarea></label>
     <label>Публичный адрес<input name="username" value="<?=e($chat['username']??'')?>" pattern="[a-z0-9_]{3,40}" placeholder="channel_name"></label>
     <div class="form-grid">
      <label>Тип канала<select name="visibility"><option value="private" <?=($chat['visibility']??'private')==='private'?'selected':''?>>Закрытый</option><option value="public" <?=($chat['visibility']??'private')==='public'?'selected':''?>>Публичный</option></select></label>
      <label>Вступление<select name="join_policy"><option value="invite" <?=($chat['join_policy']??'invite')==='invite'?'selected':''?>>Только по приглашению</option><option value="request" <?=($chat['join_policy']??'invite')==='request'?'selected':''?>>По заявке</option><option value="open" <?=($chat['join_policy']??'invite')==='open'?'selected':''?>>Свободное</option></select></label>
     </div>
     <label>Медленный режим, секунд<input type="number" name="slow_mode_seconds" min="0" max="3600" value="<?=(int)($chat['slow_mode_seconds']??0)?>"></label>
     <label class="switch-row"><span><b>Комментарии к публикациям</b><small>Подписчики смогут открывать публикацию и обсуждать её отдельно.</small></span><input type="checkbox" name="comments_enabled" value="1" <?=!empty($chat['comments_enabled'])?'checked':''?>></label>
     <label class="switch-row"><span><b>Реакции</b><small>Разрешить реакции под публикациями канала.</small></span><input type="checkbox" name="reactions_enabled" value="1" <?=!empty($chat['reactions_enabled'])?'checked':''?>></label>
     <label class="switch-row"><span><b>Подписи авторов</b><small>Показывать, кто из администраторов опубликовал запись.</small></span><input type="checkbox" name="sign_messages" value="1" <?=!empty($chat['sign_messages'])?'checked':''?>></label>
     <label class="switch-row"><span><b>Защита содержимого</b><small>Отключить пересылку публикаций из канала.</small></span><input type="checkbox" name="content_protection" value="1" <?=!empty($chat['content_protection'])?'checked':''?>></label>
     <button class="btn btn-primary" type="submit">Сохранить настройки</button>
    </form>
   </section>
   <section class="channel-settings-panel" data-channel-panel="avatar">
    <form class="channel-avatar-form" method="post" enctype="multipart/form-data" action="<?=e(app_url('/ajax/channel/'.(int)$chat['id'].'/avatar'))?>" data-channel-avatar-form>
     <?=csrf_field()?>
     <div class="channel-avatar-preview"><?=chat_avatar_html($chat,null,'public-avatar')?></div>
     <div class="upload-dropzone compact-upload"><input type="file" name="avatar" accept="image/jpeg,image/png,image/webp" hidden required><div class="drop-icon">⬆</div><b>Перетащите изображение</b><small>Или нажмите, чтобы выбрать файл. До 5 МБ.</small></div>
     <p class="muted">Изображение будет автоматически уменьшено и сжато.</p>
    </form>
   </section>
   <section class="channel-settings-panel" data-channel-panel="invites">
    <?php $memberIds=array_map('intval',array_column($members??[],'id'));$inviteCandidates=array_values(array_filter(relationship_contacts(\Kovcheg\Auth::id(),200),fn($person)=>!in_array((int)$person['id'],$memberIds,true)));?>
    <div class="channel-invite-panel" data-channel-invite-panel="<?=(int)$chat['id']?>">
     <div class="channel-invite-search"><span>⌕</span><input type="search" data-channel-invite-search placeholder="Найти коллегу"></div>
     <div class="channel-invite-list">
      <?php foreach($inviteCandidates as $person):?><article data-channel-invite-person data-search="<?=e(mb_lower(($person['display_name']??'').' '.($person['username']??'')))?>"><?=avatar_html($person,'avatar-sm')?><span><b><?=e($person['display_name'])?><?=verified_badge($person)?></b><small>@<?=e($person['username'])?></small></span><button type="button" class="btn btn-small" data-channel-invite-user="<?=(int)$person['id']?>">Пригласить</button></article><?php endforeach;?>
      <?php if(!$inviteCandidates):?><p class="muted" data-channel-invite-empty>Все ваши коллеги уже состоят в канале.</p><?php endif;?>
     </div>
    </div>
   </section>
  </div>
 </div>
 <?php endif;?>
<?php else:?><div class="empty-state"><div class="logo-large">K</div><h2>Выберите переписку</h2><p>Используйте единый поиск слева.</p></div><?php endif;?>
</section>
