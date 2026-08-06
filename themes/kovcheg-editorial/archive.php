<?php
use Kovcheg\Blog\Blog;
?>
<section class="archive-hero">
  <span class="eyebrow"><?=($entryType??'')==='search'?'ПОИСК':'РУБРИКА'?></span>
  <h1><?=e($archiveTitle)?></h1>
  <p><?=e($archiveDescription)?></p>
  <form class="archive-search" method="get" action="<?=e(app_url('/search'))?>"><input type="search" name="q" minlength="2" value="<?=e((string)($searchQuery??''))?>" placeholder="Поиск по страницам"><button class="button button--dark">Найти</button></form>
</section>

<section class="content-section archive-section">
  <?php if($entries):?>
  <div class="archive-list">
    <?php foreach($entries as $index=>$entry):?>
      <article class="archive-item">
        <div class="archive-item__index"><?=str_pad((string)($index+1),2,'0',STR_PAD_LEFT)?></div>
        <div class="archive-item__body">
          <div class="entry-card__meta"><span>Обновлено <?=e(date('d.m.Y',strtotime((string)($entry['updated_at']?:$entry['published_at']?:$entry['created_at']))))?></span></div>
          <h2><a href="<?=e(Blog::entryUrl($entry))?>"><?=e((string)$entry['title'])?></a></h2>
          <p><?=e(Blog::excerpt($entry,280))?></p>
        </div>
        <a class="archive-item__arrow" href="<?=e(Blog::entryUrl($entry))?>" aria-label="Открыть <?=e((string)$entry['title'])?>">↗</a>
      </article>
    <?php endforeach;?>
  </div>
  <?php else:?>
    <div class="empty-state"><h2><?=($entryType??'')==='search'?'Ничего не найдено':'В рубрике пока нет страниц'?></h2><p><?=($entryType??'')==='search'?'Попробуйте изменить запрос.':'Добавьте нужные страницы в эту рубрику через KOVCHEG Studio.'?></p></div>
  <?php endif;?>
</section>
