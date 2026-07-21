<?php
use Kovcheg\Blog\Blog;
?>
<section class="archive-hero">
  <span class="eyebrow"><?=e($entryType === 'portfolio' ? 'РАБОТЫ И ПРОЕКТЫ' : 'АВТОРСКИЙ ЖУРНАЛ')?></span>
  <h1><?=e($archiveTitle)?></h1>
  <p><?=e($archiveDescription)?></p>
</section>

<section class="content-section archive-section">
  <?php if ($entries): ?>
  <div class="archive-list">
    <?php foreach ($entries as $index => $entry): ?>
      <article class="archive-item">
        <div class="archive-item__index"><?=str_pad((string)($index + 1), 2, '0', STR_PAD_LEFT)?></div>
        <div class="archive-item__body">
          <div class="entry-card__meta">
            <span><?=e(date('d.m.Y', strtotime((string)($entry['published_at'] ?: $entry['created_at']))))?></span>
            <a href="<?=e(app_url('/author/'.rawurlencode((string)$entry['author_username'])))?>"><?=e((string)$entry['author_name'])?></a>
          </div>
          <h2><a href="<?=e(Blog::entryUrl($entry))?>"><?=e((string)$entry['title'])?></a></h2>
          <p><?=e(Blog::excerpt($entry, 280))?></p>
          <footer><span>💬 <?=(int)$entry['comment_count']?></span><span>✦ <?=(int)$entry['reaction_count']?></span></footer>
        </div>
        <a class="archive-item__arrow" href="<?=e(Blog::entryUrl($entry))?>" aria-label="Открыть <?=e((string)$entry['title'])?>">↗</a>
      </article>
    <?php endforeach; ?>
  </div>
  <?php else: ?>
    <div class="empty-state">
      <h2>Материалов пока нет</h2>
      <p>Раздел уже готов и появится на сайте после первой публикации.</p>
    </div>
  <?php endif; ?>
</section>
