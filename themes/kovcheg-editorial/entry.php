<?php
use Kovcheg\Auth;
use Kovcheg\Blog\Blog;

$typeLabels = ['post' => 'Публикация', 'page' => 'Страница', 'portfolio' => 'Проект'];
$type = (string)($entry['type'] ?? 'post');
$published = (string)($entry['published_at'] ?: $entry['created_at']);
$reactionMap = [];
foreach ($reactions as $reaction) $reactionMap[(string)$reaction['reaction']] = (int)$reaction['total'];
$reactionOptions = ['👍' => 'Нравится', '❤️' => 'Люблю', '👏' => 'Браво', '🔥' => 'Огонь', '💡' => 'Полезно'];
?>
<article class="single-entry">
  <header class="single-entry__header">
    <a class="single-entry__back" href="<?=e($type === 'portfolio' ? app_url('/portfolio') : ($type === 'page' ? app_url('/') : app_url('/blog')))?>">← Назад</a>
    <span class="eyebrow"><?=e($typeLabels[$type] ?? 'Материал')?></span>
    <h1><?=e((string)$entry['title'])?></h1>
    <?php if (!empty($entry['excerpt'])): ?><p class="single-entry__lead"><?=e((string)$entry['excerpt'])?></p><?php endif; ?>
    <div class="single-entry__meta">
      <a href="<?=e(app_url('/author/'.rawurlencode((string)$entry['author_username'])))?>"><?=avatar_html($entry, 'avatar-xs')?> <span><?=e((string)$entry['author_name'])?></span></a>
      <time datetime="<?=e(date('c', strtotime($published)))?>"><?=e(date('d.m.Y', strtotime($published)))?></time>
    </div>
  </header>

  <div class="single-entry__body prose">
    <?php if (trim((string)($entry['content_html'] ?? '')) !== ''): ?>
      <?=$entry['content_html']?>
    <?php elseif (trim((string)($entry['excerpt'] ?? '')) !== ''): ?>
      <p><?=nl2br(e((string)$entry['excerpt']))?></p>
    <?php else: ?>
      <p>Содержание материала готовится.</p>
    <?php endif; ?>
  </div>

  <footer class="single-entry__footer">
    <div class="author-card">
      <?=avatar_html($entry, 'avatar')?>
      <div><span>Автор</span><h3><?=e((string)$entry['author_name'])?></h3><p><?=e((string)($entry['author_bio'] ?: 'Автор проекта и публикации.'))?></p><a href="<?=e(app_url('/author/'.rawurlencode((string)$entry['author_username'])))?>">Все материалы автора →</a></div>
    </div>
  </footer>
</article>

<?php if (!empty($entry['reactions_enabled'])): ?>
<section class="interaction-section" id="reactions">
  <header><span class="eyebrow">РЕАКЦИИ</span><h2>Как вам материал?</h2></header>
  <div class="reaction-row">
    <?php foreach ($reactionOptions as $emoji => $label): ?>
      <?php if (Auth::check()): ?>
      <form method="post" action="<?=e(app_url('/content/'.(int)$entry['id'].'/reaction'))?>">
        <?=csrf_field()?>
        <input type="hidden" name="reaction" value="<?=e($emoji)?>">
        <button type="submit" title="<?=e($label)?>"><span><?=$emoji?></span><b><?=($reactionMap[$emoji] ?? 0) ?: ''?></b></button>
      </form>
      <?php else: ?>
        <a href="<?=e(app_url('/login'))?>" title="Войдите, чтобы оставить реакцию"><span><?=$emoji?></span><b><?=($reactionMap[$emoji] ?? 0) ?: ''?></b></a>
      <?php endif; ?>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<?php if (!empty($entry['comments_enabled'])): ?>
<section class="comments-section" id="comments">
  <header class="comments-section__header"><div><span class="eyebrow">ОБСУЖДЕНИЕ</span><h2>Комментарии <small><?=count($comments)?></small></h2></div></header>

  <?php if ($comments): ?>
  <div class="comment-list">
    <?php foreach ($comments as $comment): ?>
      <article class="comment-card" id="comment-<?=(int)$comment['id']?>">
        <?=avatar_html($comment, 'avatar-xs')?>
        <div>
          <header><a href="<?=e(app_url('/author/'.rawurlencode((string)$comment['author_username'])))?>"><?=e((string)$comment['author_name'])?></a><time><?=e(human_time((string)$comment['created_at']))?></time></header>
          <p><?=nl2br(e((string)$comment['body']))?></p>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
  <?php else: ?>
    <p class="comments-empty">Комментариев пока нет. Можно начать обсуждение первым.</p>
  <?php endif; ?>

  <?php if (Auth::check()): ?>
  <form class="comment-form" method="post" action="<?=e(app_url('/content/'.(int)$entry['id'].'/comment'))?>">
    <?=csrf_field()?>
    <label for="comment-body">Ваш комментарий</label>
    <textarea id="comment-body" name="body" minlength="2" maxlength="5000" rows="5" required placeholder="Напишите по существу, задайте вопрос или поделитесь опытом"></textarea>
    <div><small><?=setting('comments_auto_approve', '0') === '1' || Blog::canModerate() ? 'Комментарий будет опубликован сразу.' : 'Первый комментарий может потребовать проверки модератором.'?></small><button class="button button--dark" type="submit">Отправить</button></div>
  </form>
  <?php else: ?>
    <div class="login-invite"><div><b>Присоединяйтесь к обсуждению</b><p>Зарегистрированные читатели могут оставлять комментарии и реакции.</p></div><a class="button button--dark" href="<?=e(app_url('/login'))?>">Войти</a><a class="button button--light" href="<?=e(app_url('/register'))?>">Регистрация</a></div>
  <?php endif; ?>
</section>
<?php endif; ?>
