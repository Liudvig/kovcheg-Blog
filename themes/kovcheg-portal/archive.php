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

$labels = [
    'post' => ['Блог', 'Последние публикации и новости'],
    'portfolio' => ['Портфолио', 'Работы, проекты и результаты'],
    'page' => ['Страницы', 'Информационные материалы'],
    'search' => ['Результаты поиска', 'Найденные материалы'],
];
[$archiveTitle,$archiveLead] = $labels[$entryType] ?? $labels['post'];
?>
<link rel="stylesheet" href="<?=e($themeAsset('blog-compact.css').'?v='.rawurlencode(ASSET_REVISION))?>">
<section class="portal-blog-heading portal-blog-heading--archive">
 <div><span class="portal-kicker"><?=e(mb_strtoupper($archiveTitle))?></span><h1><?=e($archiveTitle)?></h1><p><?=e($archiveLead)?></p></div>
</section>

<section class="portal-blog-feed portal-blog-feed--archive">
<?php if($entries):?>
 <?php foreach($entries as $entry):$cover=$coverUrl($entry);?>
 <article class="portal-post-card <?=$cover!==''?'has-cover':'without-cover'?>">
  <?php if($cover!==''):?><a class="portal-post-card__cover" href="<?=e(Blog::entryUrl($entry))?>"><img src="<?=e($cover)?>" alt="<?=e((string)$entry['title'])?>" loading="lazy"></a><?php endif;?>
  <div class="portal-post-card__body">
   <div class="portal-meta"><span><?=e(date('d.m.Y',strtotime((string)($entry['published_at']?:$entry['created_at']))))?></span><a href="<?=e(app_url('/author/'.rawurlencode((string)$entry['author_username'])))?>"><?=e((string)$entry['author_name'])?></a></div>
   <h2><a href="<?=e(Blog::entryUrl($entry))?>"><?=e((string)$entry['title'])?></a></h2>
   <p><?=e(Blog::excerpt($entry,330))?></p>
   <footer><a class="portal-read-more" href="<?=e(Blog::entryUrl($entry))?>">Читать полностью</a><span>💬 <?=(int)($entry['comment_count']??0)?> · ✦ <?=(int)($entry['reaction_count']??0)?></span></footer>
  </div>
 </article>
 <?php endforeach;?>
<?php else:?>
 <div class="portal-empty portal-empty--compact"><h2>Материалов пока нет</h2><p><?=$entryType==='search'?'По вашему запросу ничего не найдено.':'Раздел появится после первой публикации.'?></p></div>
<?php endif;?>
</section>
