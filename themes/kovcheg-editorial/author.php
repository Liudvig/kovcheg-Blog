<?php
use Kovcheg\Blog\Blog;
?>
<section class="author-hero">
  <div class="author-hero__avatar"><?=avatar_html($author, 'avatar')?></div>
  <div>
    <span class="eyebrow">АВТОР</span>
    <h1><?=e((string)$author['display_name'])?><?=verified_badge($author)?></h1>
    <p class="author-hero__username">@<?=e((string)$author['username'])?></p>
    <?php if (!empty($author['status_text'])): ?><blockquote><?=e((string)$author['status_text'])?></blockquote><?php endif; ?>
    <p><?=e((string)($author['bio'] ?: 'Автор публикаций и проектов на KOVCHEG Blog.'))?></p>
  </div>
</section>

<section class="content-section">
  <header class="section-heading"><div><span class="eyebrow">МАТЕРИАЛЫ АВТОРА</span><h2>Публикации и проекты</h2></div></header>
  <?php if ($entries): ?>
  <div class="entry-grid entry-grid--posts">
    <?php foreach ($entries as $entry): ?>
      <article class="entry-card">
        <div class="entry-card__meta"><span><?=e(($entry['type'] ?? '') === 'portfolio' ? 'Портфолио' : 'Блог')?></span><span><?=e(date('d.m.Y', strtotime((string)($entry['published_at'] ?: $entry['created_at']))))?></span></div>
        <h3><a href="<?=e(Blog::entryUrl($entry))?>"><?=e((string)$entry['title'])?></a></h3>
        <p><?=e(Blog::excerpt($entry, 200))?></p>
        <footer><span>💬 <?=(int)$entry['comment_count']?></span><span>✦ <?=(int)$entry['reaction_count']?></span><a href="<?=e(Blog::entryUrl($entry))?>">Открыть →</a></footer>
      </article>
    <?php endforeach; ?>
  </div>
  <?php else: ?>
  <div class="empty-state"><h2>Материалов пока нет</h2><p>Автор ещё не опубликовал записи или работы портфолио.</p></div>
  <?php endif; ?>
</section>
