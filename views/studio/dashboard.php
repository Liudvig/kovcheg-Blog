<?php
$stats=array_merge(['pages'=>0,'categories'=>0,'pending_comments'=>0,'views_30'=>0],$stats??[]);
?>
<div class="page-head page-head--pages">
 <div><h1>Обзор сайта</h1><p>Одна понятная система: страницы и рубрики.</p></div>
 <?php if(\Kovcheg\Blog\Studio::can('content')):?><a class="button primary" href="<?=e(app_url('/studio/pages/new'))?>">+ Добавить страницу</a><?php endif;?>
</div>
<div class="stats-grid pages-stats-grid">
 <a class="stat-card" href="<?=e(app_url('/studio/pages'))?>"><strong><?=(int)$stats['pages']?></strong><span>Страниц</span></a>
 <a class="stat-card" href="<?=e(app_url('/studio/categories'))?>"><strong><?=(int)$stats['categories']?></strong><span>Рубрик</span></a>
 <a class="stat-card" href="<?=e(app_url('/studio/comments?status=pending'))?>"><strong><?=(int)$stats['pending_comments']?></strong><span>Комментариев ждут проверки</span></a>
 <article class="stat-card"><strong><?=(int)$stats['views_30']?></strong><span>Просмотров за 30 дней</span></article>
</div>
<div class="dashboard-grid dashboard-grid--pages">
 <section class="panel"><div class="panel-title-row"><h2>Последние страницы</h2><a href="<?=e(app_url('/studio/pages'))?>">Все страницы</a></div><div class="simple-list">
 <?php foreach($recentEntries as $entry):?><article><div><b><?=e($entry['title'])?></b><small><?=e($entry['author_name'])?> · обновлено <?=e($entry['updated_at'])?></small></div><div><span class="status <?=e($entry['status'])?>"><?=e($entry['status'])?></span><?php if(\Kovcheg\Blog\Studio::can('content')):?><a class="button small" href="<?=e(app_url('/studio/pages/'.(int)$entry['id'].'/edit'))?>">Открыть</a><?php endif;?></div></article><?php endforeach;?>
 <?php if(!$recentEntries):?><div class="empty-state">Страниц ещё нет. Создайте первую страницу.</div><?php endif;?>
 </div></section>
 <section class="panel"><div class="panel-title-row"><h2>Свежие комментарии</h2><a href="<?=e(app_url('/studio/comments'))?>">Все комментарии</a></div><div class="simple-list">
 <?php foreach($recentComments as $comment):?><article><div><b><?=e($comment['author_name'])?></b><small><?=e(utf8_substr($comment['body'],0,100))?> · <?=e($comment['entry_title'])?></small></div><span class="status <?=e($comment['status'])?>"><?=e($comment['status'])?></span></article><?php endforeach;?>
 <?php if(!$recentComments):?><div class="empty-state">Комментариев пока нет.</div><?php endif;?>
 </div></section>
</div>
