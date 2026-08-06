<?php

declare(strict_types=1);

use Kovcheg\Auth;
use Kovcheg\DB;
use Kovcheg\Blog\Blog;

$posts = array_values($posts ?? []);

$coverUrl = static function (array $entry): string {
    $path = trim((string)($entry['featured_image_path'] ?? ''));
    if ($path === '') return '';
    $id = (int)(DB::one('SELECT id FROM media_library WHERE stored_path=? LIMIT 1', [$path])['id'] ?? 0);
    return $id > 0 ? app_url('/media/'.$id) : '';
};

$publishedDate = static function (array $entry): string {
    $value = (string)($entry['published_at'] ?: $entry['created_at']);
    return $value !== '' ? date('d.m.Y', strtotime($value)) : '';
};
?>
<link rel="stylesheet" href="<?=e($themeAsset('blog-compact.css').'?v='.rawurlencode(ASSET_REVISION))?>">
<section class="portal-blog-heading">
 <div>
  <span class="portal-kicker">БЛОГ</span>
  <h1><?=e(setting('site_name', 'KOVCHEG Blog'))?></h1>
  <p><?=e(setting('blog_description', 'Новости, статьи и обновления.'))?></p>
 </div>
 <?php if(Auth::isAdmin()):?><a class="portal-button" href="<?=e(app_url('/studio/posts/new'))?>">Добавить запись</a><?php endif;?>
</section>

<?php if($posts):?>
<section class="portal-blog-feed" aria-label="Последние записи">
 <?php foreach(array_slice($posts, 0, 20) as $entry):$cover=$coverUrl($entry);?>
 <article class="portal-post-card <?=$cover!==''?'has-cover':'without-cover'?>">
  <?php if($cover!==''):?>
  <a class="portal-post-card__cover" href="<?=e(Blog::entryUrl($entry))?>">
   <img src="<?=e($cover)?>" alt="<?=e((string)$entry['title'])?>" loading="lazy">
  </a>
  <?php endif;?>
  <div class="portal-post-card__body">
   <div class="portal-meta">
    <span><?=$publishedDate($entry)?></span>
    <a href="<?=e(app_url('/author/'.rawurlencode((string)$entry['author_username'])))?>"><?=e((string)$entry['author_name'])?></a>
   </div>
   <h2><a href="<?=e(Blog::entryUrl($entry))?>"><?=e((string)$entry['title'])?></a></h2>
   <p><?=e(Blog::excerpt($entry, 330))?></p>
   <footer>
    <a class="portal-read-more" href="<?=e(Blog::entryUrl($entry))?>">Читать далее</a>
    <span>💬 <?=(int)($entry['comment_count']??0)?></span>
   </footer>
  </div>
 </article>
 <?php endforeach;?>
</section>
<?php else:?>
<section class="portal-empty portal-empty--compact">
 <h2>Записей пока нет</h2>
 <p>Создайте первую запись и выберите для неё рубрику.</p>
 <?php if(Auth::isAdmin()):?><a class="portal-button" href="<?=e(app_url('/studio/posts/new'))?>">Добавить запись</a><?php endif;?>
</section>
<?php endif;?>
