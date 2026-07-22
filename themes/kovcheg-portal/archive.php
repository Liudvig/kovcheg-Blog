<?php

declare(strict_types=1);

use Kovcheg\DB;
use Kovcheg\Blog\Blog;

$entries = array_values($entries ?? []);
$entryType = (string)($entryType ?? 'post');
$coverUrl = static function (array $entry): string {
    $path = trim((string)($entry['featured_image_path'] ?? ''));
    if ($path === '') return '';
    $id = (int)(DB::one('SELECT id FROM media_library WHERE stored_path=? LIMIT 1', [$path])['id'] ?? 0);
    return $id > 0 ? app_url('/media/'.$id) : '';
};
$label = match ($entryType) {
    'portfolio' => 'ПРОЕКТЫ И РЕЗУЛЬТАТЫ',
    'category' => 'РУБРИКА',
    'tag' => 'ТЕГ',
    'search' => 'РЕЗУЛЬТАТЫ ПОИСКА',
    default => 'НОВОСТИ И ПУБЛИКАЦИИ',
};
?>

<header class="portal-archive-head">
  <span class="portal-kicker"><?=e($label)?></span>
  <h1><?=e((string)$archiveTitle)?></h1>
  <p><?=e((string)$archiveDescription)?></p>
</header>

<section class="portal-section portal-archive-section">
  <?php if($entries):?>
    <div class="portal-archive-grid">
      <?php foreach($entries as $index=>$entry):$cover=$coverUrl($entry);?>
        <article class="portal-archive-card <?=$index===0?'portal-archive-card--lead':''?>">
          <?php if($cover!==''):?><a class="portal-archive-card__cover" href="<?=e(Blog::entryUrl($entry))?>"><img src="<?=e($cover)?>" alt="" loading="lazy"></a><?php endif;?>
          <div class="portal-archive-card__body">
            <div class="portal-meta"><span><?=e(date('d.m.Y',strtotime((string)($entry['published_at']?:$entry['created_at']))))?></span><a href="<?=e(app_url('/author/'.rawurlencode((string)$entry['author_username'])))?>"><?=e((string)$entry['author_name'])?></a></div>
            <h2><a href="<?=e(Blog::entryUrl($entry))?>"><?=e((string)$entry['title'])?></a></h2>
            <p><?=e(Blog::excerpt($entry,$index===0?310:190))?></p>
            <footer><span>💬 <?=(int)($entry['comment_count']??0)?></span><span>✦ <?=(int)($entry['reaction_count']??0)?></span><a href="<?=e(Blog::entryUrl($entry))?>">Открыть →</a></footer>
          </div>
        </article>
      <?php endforeach;?>
    </div>
  <?php else:?>
    <div class="portal-empty"><h2>Материалов пока нет</h2><p><?=$entryType==='search'?'По вашему запросу ничего не найдено.':'Раздел готов и появится на сайте после первой публикации.'?></p></div>
  <?php endif;?>
</section>