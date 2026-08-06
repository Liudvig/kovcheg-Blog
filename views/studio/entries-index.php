<?php
use Kovcheg\Blog\Blog;
?>
<div class="page-head page-head--pages">
 <div><h1>Страницы</h1><p>Все материалы сайта находятся здесь. Рубрики превращают нужные страницы в разделы «Новости», «Блог», «Документы» и любые другие.</p></div>
 <a class="button primary" href="<?=e(app_url('/studio/pages/new'))?>">+ Добавить страницу</a>
</div>
<form class="filters pages-filters" method="get" action="<?=e(app_url('/studio/pages'))?>">
 <select name="status"><option value="">Все статусы</option><?php foreach(['draft'=>'Черновики','published'=>'Опубликованные','scheduled'=>'Запланированные','private'=>'Личные'] as $key=>$label):?><option value="<?=e($key)?>" <?=$status===$key?'selected':''?>><?=e($label)?></option><?php endforeach;?></select>
 <input name="q" value="<?=e($search)?>" placeholder="Название или адрес страницы">
 <button class="button">Найти</button>
 <?php if($status!==''||$search!==''):?><a class="button" href="<?=e(app_url('/studio/pages'))?>">Сбросить</a><?php endif;?>
</form>
<?php if($entries):?>
<div class="pages-list" role="list">
<?php foreach($entries as $entry):$finalUrl=Blog::entryUrl($entry);$public=Blog::isPubliclyReadable($entry);?>
<article class="page-list-card" role="listitem">
 <div class="page-list-card__main">
  <div class="page-list-card__title-row"><h2><?=e((string)$entry['title'])?></h2><span class="status <?=e((string)$entry['status'])?>"><?=e((string)$entry['status'])?></span></div>
  <code><?=e(parse_url($finalUrl,PHP_URL_PATH)?:'/')?></code>
  <?php if(trim((string)($entry['excerpt']??''))!==''):?><p><?=e(utf8_substr((string)$entry['excerpt'],0,180))?></p><?php endif;?>
  <small>Автор: <?=e((string)$entry['author_name'])?> · Обновлено: <?=e((string)$entry['updated_at'])?></small>
 </div>
 <div class="page-list-card__actions">
  <?php if($public):?><a class="button small" target="_blank" rel="noopener" href="<?=e($finalUrl)?>">Открыть</a><?php else:?><a class="button small" target="_blank" rel="noopener" href="<?=e(app_url('/studio/content/'.(int)$entry['id'].'/preview'))?>">Предпросмотр</a><?php endif;?>
  <a class="button small primary" href="<?=e(app_url('/studio/pages/'.(int)$entry['id'].'/edit'))?>">Изменить</a>
  <form method="post" action="<?=e(app_url('/studio/entries/'.(int)$entry['id'].'/duplicate'))?>"><?=csrf_field()?><button class="button small">Копировать</button></form>
  <form method="post" data-confirm="Переместить страницу в корзину?" action="<?=e(app_url('/studio/entries/'.(int)$entry['id'].'/trash'))?>"><?=csrf_field()?><button class="button small danger">В корзину</button></form>
 </div>
</article>
<?php endforeach;?>
</div>
<?php else:?><div class="empty-state pages-empty"><h2>Страниц пока нет</h2><p>Создайте первую страницу. Позже её можно включить в любую рубрику и добавить в меню.</p><a class="button primary" href="<?=e(app_url('/studio/pages/new'))?>">Создать страницу</a></div><?php endif;?>
