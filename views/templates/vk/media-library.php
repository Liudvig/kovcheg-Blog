<?php
$mediaType=$mediaType??'photo';
$owner=$owner??(\Kovcheg\Auth::user()??[]);
$isSelf=(bool)($isSelf??false);
$items=$items??[];$albums=$albums??[];$playlists=$playlists??[];
$activeAlbum=$activeAlbum??null;$activePlaylist=$activePlaylist??null;
$activeKey=match($mediaType){'video'=>'videos','audio'=>'music',default=>'photos'};
$heading=match($mediaType){'video'=>'Видео','audio'=>'Музыка',default=>'Фотографии'};
$ownerSuffix=$isSelf?'':' · '.($owner['display_name']??'Пользователь');
?>
<main class="vk-page two vk-media-library-page">
 <?=\Kovcheg\View::partial('site-sidebar',['active'=>$activeKey])?>
 <section class="vk-media-library-main">
  <header class="vk-card vk-media-library-head">
   <div><h1><?=e($heading.$ownerSuffix)?></h1><p><?php if($mediaType==='photo'):?>Фотоальбомы и отдельные фотографии<?php elseif($mediaType==='video'):?>Видеозаписи в компактной сетке<?php else:?>Треки и пользовательские плейлисты<?php endif;?></p></div>
   <a class="vk-btn" href="<?=e(app_url('/profile'))?>">Моя страница</a>
  </header>

  <?php if($isSelf):?>
  <section class="vk-card vk-media-manage">
   <?php if($mediaType==='photo'):?>
    <details class="vk-media-create-box" <?=$activeAlbum?'':'open'?>>
     <summary>Создать фотоальбом</summary>
     <form method="post" action="<?=e(app_url('/photos/album'))?>"><?=csrf_field()?><label>Название<input name="title" maxlength="190" required></label><label>Описание<textarea name="description" rows="2" maxlength="1000"></textarea></label><button class="vk-btn primary" type="submit">Создать альбом</button></form>
    </details>
    <form class="vk-media-auto-form" method="post" enctype="multipart/form-data" action="<?=e(app_url('/photos/upload'))?>" data-auto-multiupload><?=csrf_field()?>
     <label>Альбом<select name="album_id"><option value="0">Без альбома</option><?php foreach($albums as $album):?><option value="<?=(int)$album['id']?>" <?=($activeAlbum&&(int)$activeAlbum['id']===(int)$album['id'])?'selected':''?>><?=e($album['title'])?></option><?php endforeach;?></select></label>
     <label class="vk-media-auto-drop" data-auto-upload-drop tabindex="0"><input type="file" name="media[]" accept="image/jpeg,image/png,image/webp" multiple required hidden><span>▧</span><div><b>Добавить фотографии</b><small data-auto-upload-status>Нажмите или перетащите сразу несколько файлов</small></div></label>
    </form>
   <?php elseif($mediaType==='video'):?>
    <form class="vk-media-auto-form" method="post" enctype="multipart/form-data" action="<?=e(app_url('/videos/upload'))?>" data-auto-multiupload><?=csrf_field()?>
     <label>Общее название<input name="title" maxlength="190" placeholder="Можно оставить имена файлов"></label>
     <label class="vk-media-auto-drop" data-auto-upload-drop tabindex="0"><input type="file" name="media[]" accept="video/mp4,video/webm" multiple required hidden><span>▶</span><div><b>Добавить видео</b><small data-auto-upload-status>Нажмите или перетащите несколько MP4/WebM</small></div></label>
    </form>
   <?php else:?>
    <details class="vk-media-create-box"><summary>Создать плейлист</summary><form method="post" action="<?=e(app_url('/music/playlist'))?>"><?=csrf_field()?><label>Название<input name="title" maxlength="190" required></label><label>Описание<textarea name="description" rows="2" maxlength="1000"></textarea></label><button class="vk-btn primary" type="submit">Создать плейлист</button></form></details>
    <form class="vk-media-auto-form" method="post" enctype="multipart/form-data" action="<?=e(app_url('/music/upload'))?>" data-auto-multiupload><?=csrf_field()?>
     <label>Плейлист<select name="playlist_id"><option value="0">В общую медиатеку</option><?php foreach($playlists as $playlist):?><option value="<?=(int)$playlist['id']?>" <?=($activePlaylist&&(int)$activePlaylist['id']===(int)$playlist['id'])?'selected':''?>><?=e($playlist['title'])?></option><?php endforeach;?></select></label>
     <label class="vk-media-auto-drop" data-auto-upload-drop tabindex="0"><input type="file" name="media[]" accept="audio/mpeg,audio/mp4,audio/aac,audio/ogg,audio/wav,audio/flac,.mp3,.m4a,.aac,.ogg,.wav,.flac" multiple required hidden><span>♫</span><div><b>Добавить треки</b><small data-auto-upload-status>Нажмите или перетащите сразу несколько аудиофайлов</small></div></label>
    </form>
   <?php endif;?>
  </section>
  <?php endif;?>

  <?php if($mediaType==='audio'):?>
  <section class="vk-card vk-music-player" data-vk-full-player>
   <button type="button" data-vk-player-prev aria-label="Предыдущий трек">‹</button>
   <button type="button" data-vk-player-play aria-label="Воспроизведение">▶</button>
   <button type="button" data-vk-player-next aria-label="Следующий трек">›</button>
   <div class="vk-music-player-info"><b data-vk-player-title>Выберите трек</b><small><?=e($owner['display_name']??'Музыка')?></small></div>
   <span></span>
   <div class="vk-music-player-progress"><input type="range" min="0" max="1000" value="0" data-vk-player-progress aria-label="Позиция трека"><time data-vk-player-time>0:00 / 0:00</time><input type="range" min="0" max="100" value="75" data-vk-player-volume aria-label="Громкость"></div>
  </section>
  <?php endif;?>

  <?php if($mediaType==='photo'):?>
   <section class="vk-card vk-media-collection-strip"><a class="vk-media-collection <?=!$activeAlbum?'active':''?>" href="<?=e(app_url('/photos'))?>"><span class="vk-media-collection-cover empty">▧</span><b>Все фотографии</b><small><?=count($items)?> на странице</small></a><?php foreach($albums as $album):?><a class="vk-media-collection <?=($activeAlbum&&(int)$activeAlbum['id']===(int)$album['id'])?'active':''?>" href="<?=e(app_url('/photos?album='.(int)$album['id']))?>"><?php if($album['cover_url']!==''):?><img class="vk-media-collection-cover" src="<?=e($album['cover_url'])?>" alt=""><?php else:?><span class="vk-media-collection-cover empty">▧</span><?php endif;?><b><?=e($album['title'])?></b><small><?=(int)$album['item_count']?> фото</small></a><?php endforeach;?></section>
   <?php if($activeAlbum):?><section class="vk-card vk-media-current-collection"><div><h2><?=e($activeAlbum['title'])?></h2><?php if(!empty($activeAlbum['description'])):?><p><?=e($activeAlbum['description'])?></p><?php endif;?></div><?php if($isSelf):?><form method="post" action="<?=e(app_url('/photos/album/'.(int)$activeAlbum['id'].'/delete'))?>" onsubmit="return confirm('Удалить альбом? Фотографии останутся в общем разделе.')"><?=csrf_field()?><button class="vk-btn" type="submit">Удалить альбом</button></form><?php endif;?></section><?php endif;?>
   <section class="vk-photo-library-grid"><?php foreach($items as $item):?><article class="vk-photo-library-card"><button type="button" data-vk-photo-open="<?=e($item['url'])?>" data-vk-title="<?=e($item['title']?:$item['original_name'])?>"><img src="<?=e($item['url'])?>" alt="<?=e($item['title']?:'Фотография')?>" loading="lazy"></button><footer><b><?=e($item['title']?:$item['original_name'])?></b><?php if($isSelf):?><form method="post" action="<?=e(app_url('/media-library/'.(int)$item['id'].'/delete'))?>" onsubmit="return confirm('Удалить фотографию?')"><?=csrf_field()?><button type="submit" aria-label="Удалить">×</button></form><?php endif;?></footer></article><?php endforeach;?><?php if(!$items):?><div class="vk-card vk-media-library-empty">В этом разделе пока нет фотографий.</div><?php endif;?></section>
  <?php elseif($mediaType==='video'):?>
   <section class="vk-video-library-grid"><?php foreach($items as $item):?><article class="vk-video-library-card"><button type="button" data-vk-video-open="<?=e($item['url'])?>" data-vk-title="<?=e($item['title']?:$item['original_name'])?>"><video src="<?=e($item['url'])?>" muted preload="metadata" playsinline></video><span>▶</span></button><footer><div><b><?=e($item['title']?:$item['original_name'])?></b><small><?=e(format_bytes((int)$item['file_size']))?></small></div><?php if($isSelf):?><form method="post" action="<?=e(app_url('/media-library/'.(int)$item['id'].'/delete'))?>" onsubmit="return confirm('Удалить видео?')"><?=csrf_field()?><button type="submit" aria-label="Удалить">×</button></form><?php endif;?></footer></article><?php endforeach;?><?php if(!$items):?><div class="vk-card vk-media-library-empty">Видео пока не загружены.</div><?php endif;?></section>
  <?php else:?>
   <section class="vk-card vk-media-collection-strip vk-playlist-strip"><a class="vk-media-collection <?=!$activePlaylist?'active':''?>" href="<?=e(app_url('/music'))?>"><span class="vk-media-collection-cover empty">♫</span><b>Все треки</b><small><?=count($items)?> на странице</small></a><?php foreach($playlists as $playlist):?><a class="vk-media-collection <?=($activePlaylist&&(int)$activePlaylist['id']===(int)$playlist['id'])?'active':''?>" href="<?=e(app_url('/music?playlist='.(int)$playlist['id']))?>"><span class="vk-media-collection-cover empty">♫</span><b><?=e($playlist['title'])?></b><small><?=(int)$playlist['item_count']?> треков</small></a><?php endforeach;?></section>
   <?php if($activePlaylist):?><section class="vk-card vk-media-current-collection"><div><h2><?=e($activePlaylist['title'])?></h2><?php if(!empty($activePlaylist['description'])):?><p><?=e($activePlaylist['description'])?></p><?php endif;?></div><?php if($isSelf):?><form method="post" action="<?=e(app_url('/music/playlist/'.(int)$activePlaylist['id'].'/delete'))?>" onsubmit="return confirm('Удалить плейлист? Треки останутся в медиатеке.')"><?=csrf_field()?><button class="vk-btn" type="submit">Удалить плейлист</button></form><?php endif;?></section><?php endif;?>
   <section class="vk-card vk-track-library" data-vk-track-list><?php foreach($items as $item):?><article class="vk-track-row"><button class="vk-track-play" type="button" data-vk-track="<?=e($item['url'])?>" data-vk-title="<?=e($item['title']?:$item['original_name'])?>" data-vk-artist="<?=e($owner['display_name']??'')?>"><span>▶</span><div><b><?=e($item['title']?:$item['original_name'])?></b><small><?=e($owner['display_name']??'')?> · <?=e(format_bytes((int)$item['file_size']))?></small></div></button><?php if($isSelf):?><div class="vk-track-actions"><?php if($activePlaylist):?><form method="post" action="<?=e(app_url('/music/playlist/'.(int)$activePlaylist['id'].'/remove'))?>"><?=csrf_field()?><input type="hidden" name="item_id" value="<?=(int)$item['id']?>"><button type="submit" title="Убрать из плейлиста">−</button></form><?php elseif($playlists):?><form method="post" class="vk-track-playlist-add" data-vk-playlist-add><input type="hidden" name="_csrf" value="<?=e(\Kovcheg\Csrf::token())?>"><input type="hidden" name="item_id" value="<?=(int)$item['id']?>"><select data-vk-playlist-select><option value="">В плейлист…</option><?php foreach($playlists as $playlist):?><option value="<?=(int)$playlist['id']?>"><?=e($playlist['title'])?></option><?php endforeach;?></select></form><?php endif;?><form method="post" action="<?=e(app_url('/media-library/'.(int)$item['id'].'/delete'))?>" onsubmit="return confirm('Удалить трек?')"><?=csrf_field()?><button type="submit" title="Удалить">×</button></form></div><?php endif;?></article><?php endforeach;?><?php if(!$items):?><div class="vk-media-library-empty">Треков пока нет.</div><?php endif;?></section>
  <?php endif;?>
 </section>
</main>
