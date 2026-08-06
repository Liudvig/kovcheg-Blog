<?php
use Kovcheg\Auth;
use Kovcheg\Blog\Blog;

$pages=array_values($pages??[]);
$categories=array_values($categories??[]);
$heroTitle=(string)setting('blog_home_title',setting('site_name','KOVCHEG'));
$heroText=(string)setting('blog_home_intro',setting('blog_description','Информация и разделы сайта.'));
?>
<section class="hero">
  <div class="hero__content">
    <span class="eyebrow">KOVCHEG CMS</span>
    <h1><?=e($heroTitle)?></h1>
    <p><?=e($heroText)?></p>
    <div class="hero__actions">
      <?php if($categories):?><a class="button button--accent" href="<?=e(app_url('/category/'.rawurlencode((string)$categories[0]['slug'])))?>">Открыть разделы</a><?php endif;?>
      <?php if(Auth::isAdmin()):?><a class="button button--light" href="<?=e(app_url('/studio/pages/new'))?>">Добавить страницу</a><?php endif;?>
    </div>
  </div>
  <aside class="hero__panel">
    <span>Структура сайта</span>
    <b>Страницы и рубрики</b>
    <p>Рубрику можно назвать «Новости», «Блог», «Документы», «Проекты» или как угодно.</p>
  </aside>
</section>

<?php if($categories):?>
<section class="content-section content-section--contrast">
  <header class="section-heading"><div><span class="eyebrow">РАЗДЕЛЫ</span><h2>Рубрики сайта</h2></div></header>
  <div class="portfolio-grid">
    <?php foreach($categories as $category):?>
      <article class="portfolio-card">
        <span class="portfolio-card__number"><?=(int)($category['page_count']??0)?></span>
        <h3><a href="<?=e(app_url('/category/'.rawurlencode((string)$category['slug'])))?>"><?=e((string)$category['name'])?></a></h3>
        <p><?=e((string)($category['description']?:'Страницы этого раздела.'))?></p>
        <a class="portfolio-card__link" href="<?=e(app_url('/category/'.rawurlencode((string)$category['slug'])))?>">Открыть раздел →</a>
      </article>
    <?php endforeach;?>
  </div>
</section>
<?php endif;?>

<section class="content-section">
  <header class="section-heading">
    <div><span class="eyebrow">МАТЕРИАЛЫ</span><h2>Последние страницы</h2></div>
    <?php if(Auth::isAdmin()):?><a href="<?=e(app_url('/studio/pages'))?>">Управление страницами →</a><?php endif;?>
  </header>

  <?php if($pages):?>
  <div class="entry-grid entry-grid--posts">
    <?php foreach($pages as $index=>$entry):?>
      <article class="entry-card <?=$index===0?'entry-card--lead':''?>">
        <div class="entry-card__meta"><span><?=e(date('d.m.Y',strtotime((string)($entry['updated_at']?:$entry['published_at']?:$entry['created_at']))))?></span></div>
        <h3><a href="<?=e(Blog::entryUrl($entry))?>"><?=e((string)$entry['title'])?></a></h3>
        <p><?=e(Blog::excerpt($entry,$index===0?320:190))?></p>
        <footer><a href="<?=e(Blog::entryUrl($entry))?>">Открыть →</a></footer>
      </article>
    <?php endforeach;?>
  </div>
  <?php else:?>
    <div class="empty-state"><span>Страниц пока нет.</span><h3>Создайте первую страницу сайта.</h3><?php if(Auth::isAdmin()):?><a class="button button--dark" href="<?=e(app_url('/studio/pages/new'))?>">Создать страницу</a><?php endif;?></div>
  <?php endif;?>
</section>

<section class="statement">
  <span class="eyebrow">О СИСТЕМЕ</span>
  <blockquote>Одна сущность для содержимого сайта: страница. Рубрики нужны только тогда, когда страницы требуется объединить в раздел.</blockquote>
  <p>KOVCHEG CMS остаётся быстрым и понятным инструментом без отдельного блога, записей и лишних типов материалов.</p>
</section>
