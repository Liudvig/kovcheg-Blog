<?php
use Kovcheg\Auth;
use Kovcheg\Blog\Blog;

$heroTitle = (string)setting('blog_home_title', 'Создаём будущее своими руками');
$heroText = (string)setting('blog_home_intro', 'Разработки, технологии, музыка, строительство и реальные проекты — без лишнего шума.');
?>
<section class="hero">
  <div class="hero__content">
    <span class="eyebrow">KOVCHEG BLOG</span>
    <h1><?=e($heroTitle)?></h1>
    <p><?=e($heroText)?></p>
    <div class="hero__actions">
      <a class="button button--accent" href="<?=e(app_url('/blog'))?>">Читать блог</a>
      <a class="button button--light" href="<?=e(app_url('/portfolio'))?>">Смотреть проекты</a>
    </div>
  </div>
  <aside class="hero__panel">
    <span>Сейчас в работе</span>
    <b><?=e(setting('blog_current_project', 'KOVCHEG — собственная платформа для сайтов, сообществ и приложений'))?></b>
    <p><?=e(setting('blog_current_project_note', 'Показываем путь разработки честно: идеи, решения, ошибки и результаты.'))?></p>
  </aside>
</section>

<section class="content-section">
  <header class="section-heading">
    <div><span class="eyebrow">НОВЫЕ МАТЕРИАЛЫ</span><h2>Последние записи</h2></div>
    <a href="<?=e(app_url('/blog'))?>">Все записи →</a>
  </header>

  <?php if ($posts): ?>
  <div class="entry-grid entry-grid--posts">
    <?php foreach ($posts as $index => $entry): ?>
      <article class="entry-card <?=$index===0?'entry-card--lead':''?>">
        <div class="entry-card__meta">
          <span><?=e(date('d.m.Y', strtotime((string)($entry['published_at'] ?: $entry['created_at']))))?></span>
          <span><?=e((string)$entry['author_name'])?></span>
        </div>
        <h3><a href="<?=e(Blog::entryUrl($entry))?>"><?=e((string)$entry['title'])?></a></h3>
        <p><?=e(Blog::excerpt($entry, $index===0 ? 320 : 190))?></p>
        <footer><span>💬 <?=(int)$entry['comment_count']?></span><span>✦ <?=(int)$entry['reaction_count']?></span><a href="<?=e(Blog::entryUrl($entry))?>">Читать →</a></footer>
      </article>
    <?php endforeach; ?>
  </div>
  <?php else: ?>
    <div class="empty-state">
      <span>Первый материал ещё не опубликован.</span>
      <h3>Здесь появится история создания проекта.</h3>
      <?php if (Auth::isAdmin()): ?><a class="button button--dark" href="<?=e(app_url('/admin'))?>">Перейти в управление</a><?php endif; ?>
    </div>
  <?php endif; ?>
</section>

<section class="content-section content-section--contrast">
  <header class="section-heading">
    <div><span class="eyebrow">ПОРТФОЛИО</span><h2>Проекты и результаты</h2></div>
    <a href="<?=e(app_url('/portfolio'))?>">Всё портфолио →</a>
  </header>

  <?php if ($portfolio): ?>
  <div class="portfolio-grid">
    <?php foreach ($portfolio as $entry): ?>
      <article class="portfolio-card">
        <span class="portfolio-card__number"><?=str_pad((string)$entry['id'], 2, '0', STR_PAD_LEFT)?></span>
        <h3><a href="<?=e(Blog::entryUrl($entry))?>"><?=e((string)$entry['title'])?></a></h3>
        <p><?=e(Blog::excerpt($entry, 170))?></p>
        <a class="portfolio-card__link" href="<?=e(Blog::entryUrl($entry))?>">Открыть проект →</a>
      </article>
    <?php endforeach; ?>
  </div>
  <?php else: ?>
    <div class="empty-state empty-state--dark"><h3>Раздел портфолио готов к наполнению.</h3><p>Здесь будут работы, релизы, объекты и другие результаты.</p></div>
  <?php endif; ?>
</section>

<section class="statement">
  <span class="eyebrow">О ПЛАТФОРМЕ</span>
  <blockquote>Сайт должен помогать человеку заявить о себе, а не заставлять его изучать программирование и десятки запутанных настроек.</blockquote>
  <p>KOVCHEG Blog создаётся как быстрый и понятный инструмент для автора, музыканта, строителя, художника, мастера и небольшой команды.</p>
</section>
