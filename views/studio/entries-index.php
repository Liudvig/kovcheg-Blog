<?php
use Kovcheg\Blog\Blog;
$isPage=$entryType==='page';
$section=$isPage?'pages':'posts';
$singular=$isPage?'страницу':'запись';
$title=$isPage?'Страницы':'Записи';
$description=$isPage?'Постоянные страницы сайта: о проекте, контакты, документы и другие разделы.':'Записи блога. Распределяйте их по рубрикам, как в WordPress.';
?>
<div class="page-head">
 <div><h1><?=e($title)?></h1><p><?=e($description)?></p></div>
 <a class="button primary" href="<?=e(app_url('/studio/'.$section.'/new'))?>">+ Добавить <?=e($singular)?></a>
</div>
<form class="filters" method="get" action="<?=e(app_url('/studio/'.$section))?>">
 <select name="status"><option value="">Все статусы</option><?php foreach(['draft'=>'Черновики','published'=>'Опубликованные','scheduled'=>'Запланированные','private'=>'Личные'] as $key=>$label):?><option value="<?=e($key)?>" <?=$status===$key?'selected':''?>><?=e($label)?></option><?php endforeach;?></select>
 <input name="q" value="<?=e($search)?>" placeholder="Поиск по заголовку или адресу">
 <button class="button">Найти</button>
 <?php if($status!==''||$search!==''):?><a class="button" href="<?=e(app_url('/studio/'.$section))?>">Сбросить</a><?php endif;?>
</form>
<?php if($entries):?>
<table class="content-table wp-entry-table"><thead><tr><th>Заголовок</th><th>Автор</th><th>Статус</th><th>Дата</th><th></th></tr></thead><tbody>
<?php foreach($entries as $entry):$finalUrl=Blog::entryUrl($entry);$public=Blog::isPubliclyReadable($entry);?>
<tr>
 <td><b><?=e((string)$entry['title'])?></b><small><?=e(parse_url($finalUrl,PHP_URL_PATH)?:'/')?></small></td>
 <td><?=e((string)$entry['author_name'])?></td>
 <td><span class="status <?=e((string)$entry['status'])?>"><?=e((string)$entry['status'])?></span></td>
 <td><?=e((string)($entry['published_at']?:$entry['updated_at']))?></td>
 <td><div class="table-actions">
  <?php if($public):?><a class="button small" target="_blank" rel="noopener" href="<?=e($finalUrl)?>">Посмотреть</a><?php else:?><a class="button small" target="_blank" rel="noopener" href="<?=e(app_url('/studio/content/'.(int)$entry['id'].'/preview'))?>">Предпросмотр</a><?php endif;?>
  <a class="button small" href="<?=e(app_url('/studio/'.$section.'/'.(int)$entry['id'].'/edit'))?>">Изменить</a>
  <form method="post" action="<?=e(app_url('/studio/entries/'.(int)$entry['id'].'/duplicate'))?>"><?=csrf_field()?><button class="button small">Копировать</button></form>
  <form method="post" data-confirm="Переместить в корзину?" action="<?=e(app_url('/studio/entries/'.(int)$entry['id'].'/trash'))?>"><?=csrf_field()?><button class="button small danger">В корзину</button></form>
 </div></td>
</tr>
<?php endforeach;?>
</tbody></table>
<?php else:?><div class="empty-state"><?=$isPage?'Страниц пока нет.':'Записей пока нет.'?></div><?php endif;?>
