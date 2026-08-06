<?php
$stats=array_merge(['posts'=>0,'pages'=>0,'categories'=>0,'pending_comments'=>0,'views_30'=>0],$stats??[]);
?>
<div class="page-head">
 <div><h1>Обзор сайта</h1><p>Простое управление блогом: записи, рубрики и страницы.</p></div>
 <?php if(\Kovcheg\Blog\Studio::can('content')):?><a class="button primary" href="<?=e(app_url('/studio/posts/new'))?>">Добавить запись</a><?php endif;?>
</div>
<div class="stats-grid wp-stats-grid">
 <a class="stat-card" href="<?=e(app_url('/studio/posts'))?>"><strong><?=(int)$stats['posts']?></strong><span>Записей</span></a>
 <a class="stat-card" href="<?=e(app_url('/studio/categories'))?>"><strong><?=(int)$stats['categories']?></strong><span>Рубрик</span></a>
 <a class="stat-card" href="<?=e(app_url('/studio/pages'))?>"><strong><?=(int)$stats['pages']?></strong><span>Страниц</span></a>
 <a class="stat-card" href="<?=e(app_url('/studio/comments?status=pending'))?>"><strong><?=(int)$stats['pending_comments']?></strong><span>Ждут проверки</span></a>
 <article class="stat-card"><strong><?=(int)$stats['views_30']?></strong><span>Просмотров за 30 дней</span></article>
</div>
<div class="dashboard-grid">
 <section class="panel"><h2>Последние материалы</h2><div class="simple-list">
 <?php foreach($recentEntries as $entry):$section=(string)$entry['type']==='page'?'pages':'posts';?><article><div><b><?=e($entry['title'])?></b><small><?=e($entry['author_name'])?> · <?=((string)$entry['type']==='page'?'Страница':'Запись')?> · <?=e($entry['updated_at'])?></small></div><div><span class="status <?=e($entry['status'])?>"><?=e($entry['status'])?></span><?php if(\Kovcheg\Blog\Studio::can('content')):?><a class="button small" href="<?=e(app_url('/studio/'.$section.'/'.(int)$entry['id'].'/edit'))?>">Открыть</a><?php endif;?></div></article><?php endforeach;?>
 <?php if(!$recentEntries):?><div class="empty-state">Материалов ещё нет. Создайте первую запись.</div><?php endif;?>
 </div></section>
 <section class="panel"><h2>Свежие комментарии</h2><div class="simple-list">
 <?php foreach($recentComments as $comment):?><article><div><b><?=e($comment['author_name'])?></b><small><?=e(utf8_substr($comment['body'],0,100))?> · <?=e($comment['entry_title'])?></small></div><span class="status <?=e($comment['status'])?>"><?=e($comment['status'])?></span></article><?php endforeach;?>
 <?php if(!$recentComments):?><div class="empty-state">Комментариев пока нет.</div><?php endif;?>
 </div></section>
</div>
