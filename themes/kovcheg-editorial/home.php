<?php
use Kovcheg\Auth;
use Kovcheg\DB;
use Kovcheg\Blog\Blog;
use Kovcheg\Blog\FarmShowcase;

$posts=array_values($posts??[]);
$heroTitle=(string)setting('blog_home_title',setting('site_name','KOVCHEG'));
$heroText=(string)setting('blog_home_intro',setting('blog_description','Новости, статьи и материалы сайта.'));
$coverUrl=static function(array $entry):string{
    $path=trim((string)($entry['featured_image_path']??''));
    if($path==='')return '';
    $id=(int)(DB::one('SELECT id FROM media_library WHERE stored_path=? LIMIT 1',[$path])['id']??0);
    return $id>0?app_url('/media/'.$id):'';
};
?>
<?=FarmShowcase::homeShowcaseHtml()?>

<section class="hero">
  <div class="hero__content">
    <span class="eyebrow"><?=e(setting('blog_tagline','KOVCHEG CMS'))?></span>
    <h1><?=e($heroTitle)?></h1>
    <p><?=e($heroText)?></p>
    <div class="hero__actions">
      <a class="button button--accent" href="<?=e(app_url('/catalog'))?>">Смотреть продукцию</a>
      <?php if(Auth::isAdmin()):?><a class="button button--light" href="<?=e(app_url('/studio/catalog/new'))?>">Добавить товар</a><?php endif;?>
    </div>
  </div>
  <aside class="hero__panel">
    <span>Живое хозяйство</span>
    <b>Продукты, поголовье и реальные проекты</b>
    <p>Главная страница показывает только то, что действительно опубликовано и отмечено для рекламы в KOVCHEG Studio.</p>
  </aside>
</section>

<section class="content-section">
  <header class="section-heading">
    <div><span class="eyebrow">ПУБЛИКАЦИИ</span><h2>Последние записи</h2></div>
    <?php if(Auth::isAdmin()):?><a href="<?=e(app_url('/studio/posts'))?>">Управление записями →</a><?php endif;?>
  </header>

  <?php if($posts):?>
  <div class="entry-grid entry-grid--posts">
    <?php foreach($posts as $index=>$entry):$cover=$coverUrl($entry);?>
      <article class="entry-card <?=$index===0?'entry-card--lead':''?>">
        <?php if($cover!==''):?><a class="entry-card__cover" href="<?=e(Blog::entryUrl($entry))?>"><img src="<?=e($cover)?>" alt="<?=e((string)$entry['title'])?>" loading="lazy"></a><?php endif;?>
        <div class="entry-card__meta"><span><?=e(date('d.m.Y',strtotime((string)($entry['published_at']?:$entry['created_at']))))?></span><span>Комментарии: <?=(int)($entry['comment_count']??0)?></span></div>
        <h3><a href="<?=e(Blog::entryUrl($entry))?>"><?=e((string)$entry['title'])?></a></h3>
        <p><?=e(Blog::excerpt($entry,$index===0?320:190))?></p>
        <footer><a href="<?=e(Blog::entryUrl($entry))?>">Читать →</a></footer>
      </article>
    <?php endforeach;?>
  </div>
  <?php else:?>
    <div class="empty-state"><span>Записей пока нет.</span><h3>Создайте первую запись и выберите для неё рубрику.</h3><?php if(Auth::isAdmin()):?><a class="button button--dark" href="<?=e(app_url('/studio/posts/new'))?>">Создать запись</a><?php endif;?></div>
  <?php endif;?>
</section>

<section class="statement">
  <span class="eyebrow">О СИСТЕМЕ</span>
  <blockquote>Каталог, поголовье и проекты теперь живут как реальные материалы сайта, а не демонстрационные карточки.</blockquote>
  <p>Рекламные слайдеры заполняются автоматически из опубликованных материалов, отмеченных в Studio.</p>
</section>
