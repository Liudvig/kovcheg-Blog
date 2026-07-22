<?php

declare(strict_types=1);

use Kovcheg\Auth;
use Kovcheg\DB;
use Kovcheg\Blog\Blog;

$posts = array_values($posts ?? []);
$portfolio = array_values($portfolio ?? []);
$lead = $posts[0] ?? null;
$secondary = array_slice($posts, 1, 4);
$stream = array_slice($posts, 5);

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

<section class="portal-masthead">
  <div>
    <span class="portal-kicker">ИНФОРМАЦИОННЫЙ ПОРТАЛ</span>
    <h1><?=e((string)setting('blog_home_title', setting('site_name','KOVCHEG Portal')))?></h1>
  </div>
  <p><?=e((string)setting('blog_home_intro','Новости, аналитика, проекты и авторские материалы в одном пространстве.'))?></p>
</section>

<?php if($lead):?>
<section class="portal-lead-grid">
  <article class="portal-lead-story <?=$coverUrl($lead)!==''?'has-cover':''?>">
    <?php if($coverUrl($lead)!==''):?><a class="portal-lead-story__cover" href="<?=e(Blog::entryUrl($lead))?>"><img src="<?=e($coverUrl($lead))?>" alt="<?=e((string)$lead['title'])?>" loading="eager"></a><?php endif;?>
    <div class="portal-lead-story__body">
      <div class="portal-meta"><span><?=$publishedDate($lead)?></span><span><?=e((string)$lead['author_name'])?></span></div>
      <h2><a href="<?=e(Blog::entryUrl($lead))?>"><?=e((string)$lead['title'])?></a></h2>
      <p><?=e(Blog::excerpt($lead,360))?></p>
      <footer><a class="portal-read-more" href="<?=e(Blog::entryUrl($lead))?>">Читать материал →</a><span>💬 <?=(int)($lead['comment_count']??0)?> · ✦ <?=(int)($lead['reaction_count']??0)?></span></footer>
    </div>
  </article>

  <div class="portal-secondary-news">
    <?php foreach($secondary as $entry):$cover=$coverUrl($entry);?>
      <article class="portal-news-card">
        <?php if($cover!==''):?><a class="portal-news-card__cover" href="<?=e(Blog::entryUrl($entry))?>"><img src="<?=e($cover)?>" alt="" loading="lazy"></a><?php endif;?>
        <div><div class="portal-meta"><span><?=$publishedDate($entry)?></span></div><h3><a href="<?=e(Blog::entryUrl($entry))?>"><?=e((string)$entry['title'])?></a></h3><p><?=e(Blog::excerpt($entry,145))?></p></div>
      </article>
    <?php endforeach;?>
  </div>
</section>
<?php else:?>
<section class="portal-empty">
  <span class="portal-kicker">ПУБЛИКАЦИИ</span>
  <h2>Портал готов к наполнению</h2>
  <p>После публикации первого материала здесь появится ведущая новость и лента.</p>
  <?php if(Auth::isAdmin()):?><a class="portal-button" href="<?=e(app_url('/studio/content/new'))?>">Создать материал</a><?php endif;?>
</section>
<?php endif;?>

<?php if($stream):?>
<section class="portal-section">
  <header class="portal-section__head"><div><span class="portal-kicker">СВЕЖЕЕ</span><h2>Последние публикации</h2></div><a href="<?=e(app_url('/blog'))?>">Все материалы →</a></header>
  <div class="portal-stream">
    <?php foreach($stream as $entry):$cover=$coverUrl($entry);?>
      <article class="portal-stream-item">
        <?php if($cover!==''):?><a class="portal-stream-item__cover" href="<?=e(Blog::entryUrl($entry))?>"><img src="<?=e($cover)?>" alt="" loading="lazy"></a><?php endif;?>
        <div><div class="portal-meta"><span><?=$publishedDate($entry)?></span><a href="<?=e(app_url('/author/'.rawurlencode((string)$entry['author_username'])))?>"><?=e((string)$entry['author_name'])?></a></div><h3><a href="<?=e(Blog::entryUrl($entry))?>"><?=e((string)$entry['title'])?></a></h3><p><?=e(Blog::excerpt($entry,210))?></p></div>
      </article>
    <?php endforeach;?>
  </div>
</section>
<?php endif;?>

<?php if($portfolio):?>
<section class="portal-section portal-section--projects">
  <header class="portal-section__head"><div><span class="portal-kicker">ПРОЕКТЫ</span><h2>Работы и результаты</h2></div><a href="<?=e(app_url('/portfolio'))?>">Всё портфолио →</a></header>
  <div class="portal-project-grid">
    <?php foreach($portfolio as $entry):?>
      <article><span><?=str_pad((string)$entry['id'],2,'0',STR_PAD_LEFT)?></span><h3><a href="<?=e(Blog::entryUrl($entry))?>"><?=e((string)$entry['title'])?></a></h3><p><?=e(Blog::excerpt($entry,150))?></p><a href="<?=e(Blog::entryUrl($entry))?>">Открыть →</a></article>
    <?php endforeach;?>
  </div>
</section>
<?php endif;?>