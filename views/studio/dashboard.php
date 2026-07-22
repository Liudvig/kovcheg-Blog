<?php $stats=array_merge(['entries'=>0,'posts'=>0,'pages'=>0,'portfolio'=>0,'pending_comments'=>0,'users'=>0,'views_30'=>0],$stats??[]);?>
<div class="page-head"><div><h1>Центр управления сайтом</h1><p>Публикации, страницы, портфолио и общение с читателями в одном месте.</p></div><?php if(\Kovcheg\Blog\Studio::can('content')):?><a class="button primary" href="<?=e(app_url('/studio/content/new'))?>">Создать материал</a><?php endif;?></div>
<div class="stats-grid">
 <article class="stat-card"><strong><?=(int)$stats['entries']?></strong><span>Всего материалов</span></article>
 <article class="stat-card"><strong><?=(int)$stats['posts']?></strong><span>Публикаций</span></article>
 <article class="stat-card"><strong><?=(int)$stats['pages']?></strong><span>Страниц</span></article>
 <article class="stat-card"><strong><?=(int)$stats['portfolio']?></strong><span>Работ портфолио</span></article>
 <article class="stat-card"><strong><?=(int)$stats['pending_comments']?></strong><span>Ждут модерации</span></article>
 <article class="stat-card"><strong><?=(int)$stats['views_30']?></strong><span>Просмотров за 30 дней</span></article>
</div>
<div class="dashboard-grid">
 <section class="panel"><h2>Последние материалы</h2><div class="simple-list">
 <?php foreach($recentEntries as $entry):?><article><div><b><?=e($entry['title'])?></b><small><?=e($entry['author_name'])?> · <?=e($entry['type'])?> · <?=e($entry['updated_at'])?></small></div><div><span class="status <?=e($entry['status'])?>"><?=e($entry['status'])?></span><?php if(\Kovcheg\Blog\Studio::can('content')):?><a class="button small" href="<?=e(app_url('/studio/content/'.(int)$entry['id'].'/edit'))?>">Открыть</a><?php endif;?></div></article><?php endforeach;?>
 <?php if(!$recentEntries):?><div class="empty-state">Материалов ещё нет. Создайте первую публикацию.</div><?php endif;?>
 </div></section>
 <section class="panel"><h2>Свежие комментарии</h2><div class="simple-list">
 <?php foreach($recentComments as $comment):?><article><div><b><?=e($comment['author_name'])?></b><small><?=e(utf8_substr($comment['body'],0,100))?> · <?=e($comment['entry_title'])?></small></div><span class="status <?=e($comment['status'])?>"><?=e($comment['status'])?></span></article><?php endforeach;?>
 <?php if(!$recentComments):?><div class="empty-state">Комментариев пока нет.</div><?php endif;?>
 </div></section>
</div>
