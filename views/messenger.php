<?php
$currentUser=\Kovcheg\Auth::user()??[];
?>
<main class="messenger" data-chat-id="<?=(int)($chat['id']??0)?>" data-event-cursor="<?=(int)$cursor?>" data-focus-message="<?=(int)($focusMessageId??0)?>">
 <?=\Kovcheg\View::partial('site-sidebar',['active'=>'messages'])?>
 <aside class="chat-list">
  <div class="list-head">
   <div class="chat-list-title"><b>Сообщения</b><small>Личные переписки</small></div>
   <div class="head-actions"><button class="icon-btn" data-modal="new-direct" title="Новое сообщение">✎</button></div>
  </div>
  <div class="chat-search unified-search"><span>⌕</span><input type="search" data-global-search placeholder="Поиск" autocomplete="off"><button type="button" data-search-clear hidden>×</button></div>
  <div class="global-search-results" data-global-search-results hidden></div>
  <div class="chat-items" data-chat-items>
   <?php foreach($chats as $item):?>
   <a class="chat-item <?=($chat&&$item['id']===$chat['id'])?'active':''?>" data-chat-item="<?=(int)$item['id']?>" data-chat-type="<?=e($item['type'])?>" data-pinned="<?=!empty($item['is_pinned'])?'1':'0'?>" data-muted="<?=!empty($item['is_muted'])?'1':'0'?>" href="<?=e(chat_public_url($item,\Kovcheg\Auth::id()))?>">
    <?=chat_avatar_html($item,$item['avatar_user']??null,'avatar')?>
    <span class="chat-meta"><b><?=e($item['display_title'])?><?=!empty($item['avatar_user'])?verified_badge($item['avatar_user']):''?></b><small data-chat-preview><?=e($item['last_body']?:($item['last_message_id']?'Вложение':'Нет сообщений'))?></small></span>
    <span class="chat-side"><time data-chat-time><?=e(human_time($item['last_at']))?></time><?php if((int)$item['unread']>0):?><em data-chat-unread><?=e($item['unread'])?></em><?php endif;?><?php if(!empty($item['is_muted'])):?><i class="muted-mark" title="Уведомления выключены">⌁</i><?php endif;?></span>
   </a>
   <?php endforeach;?>
  </div>
 </aside>
 <div class="column-resizer left-resizer" data-column-resizer="left" title="Изменить ширину списка"></div>
 <div class="conversation-shell" data-conversation-shell>
  <?=\Kovcheg\View::partial('conversation-shell',compact('chat','messages','members','stickers','currentMembership','permissions','invites','joinRequests'))?>
 </div>
</main>

<div class="message-context-menu" data-message-context hidden>
 <button type="button" data-message-context-action="reply">Ответить</button><button type="button" data-message-context-action="forward">Переслать</button><button type="button" data-message-context-action="important">Отметить как важное</button><button type="button" data-message-context-action="copy">Скопировать текст</button><button type="button" data-message-context-action="edit">Редактировать</button><button type="button" data-message-context-action="delete" class="danger">Удалить</button><button type="button" data-message-context-action="select">Выбрать</button>
</div>
<div class="chat-context-menu" data-chat-context hidden>
 <button type="button" data-chat-action="pin">Закрепить</button>
 <button type="button" data-chat-action="mute">Выключить уведомления</button>
 <button type="button" data-chat-action="archive">Архивировать</button>
 <button type="button" data-chat-action="clear">Очистить историю</button>
 <button type="button" class="danger" data-chat-action="delete">Удалить / покинуть</button>
</div>

<div class="modal modern-sheet" id="new-direct" hidden><div class="modal-card"><button class="modal-close" type="button">×</button><h2>Новое сообщение</h2><form data-open-direct class="user-picker-form"><?=csrf_field()?><input type="hidden" name="user_id" required><input type="search" data-user-search placeholder="Имя или @ник" autocomplete="off" autofocus><div class="contacts-quick direct-contacts" data-direct-contacts><p class="muted">Загрузка коллег…</p></div><div class="user-search-results large" data-user-results></div><button class="btn btn-primary" disabled data-user-submit>Открыть переписку</button></form></div></div>

<div class="modal modern-sheet" id="forward-message" hidden><div class="modal-card"><button class="modal-close" type="button">×</button><h2>Переслать сообщение</h2><form data-forward-form class="user-picker-form"><input type="hidden" name="message_id" value=""><input type="hidden" name="user_id" required><div class="contacts-quick" data-forward-contacts></div><button class="btn btn-primary" disabled data-user-submit>Переслать</button></form></div></div>
<div class="modal modern-sheet" id="forward-batch" hidden><div class="modal-card"><button class="modal-close" type="button">×</button><h2>Переслать выбранные сообщения</h2><p class="muted" data-forward-batch-count></p><form data-forward-batch-form class="user-picker-form"><input type="hidden" name="message_ids" value=""><input type="hidden" name="user_id" required><div class="contacts-quick" data-forward-contacts></div><button class="btn btn-primary" disabled data-user-submit>Переслать цепочку</button></form></div></div>
<div class="crop-modal" hidden data-chat-crop-modal><div class="crop-card"><h2>Обрезать изображение</h2><div class="crop-stage crop-square"><img alt="Предпросмотр" data-chat-crop-image></div><label>Масштаб<input type="range" min="1" max="3" step="0.01" value="1" data-chat-crop-scale></label><div class="button-row"><button type="button" class="btn" data-chat-crop-skip>Без обрезки</button><button type="button" class="btn btn-primary" data-chat-crop-apply>Обрезать</button></div></div></div>
<div class="media-lightbox" hidden data-lightbox><button type="button" data-lightbox-close>×</button><img alt="Просмотр изображения" data-lightbox-image><a class="btn" data-lightbox-download download>Скачать оригинал</a></div>
