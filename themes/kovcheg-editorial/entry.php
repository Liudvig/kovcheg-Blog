<?php
use Kovcheg\Auth;
use Kovcheg\Blog\Blog;

$typeLabels = ['post' => 'Публикация', 'page' => 'Страница', 'portfolio' => 'Проект'];
$type = (string)($entry['type'] ?? 'post');
$published = (string)($entry['published_at'] ?: $entry['created_at']);
$reactionMap = [];
foreach ($reactions as $reaction) $reactionMap[(string)$reaction['reaction']] = (int)$reaction['total'];
$reactionOptions = ['👍' => 'Нравится', '❤️' => 'Люблю', '👏' => 'Браво', '🔥' => 'Огонь', '💡' => 'Полезно'];
$categories=$categories??[];$tags=$tags??[];$portfolioMeta=$portfolioMeta??[];$viewCount=(int)($viewCount??0);$relatedEntries=$relatedEntries??[];
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
      <span>Просмотров: <?=$viewCount?></span>
    </div>
    <?php if($categories||$tags):?><div class="entry-taxonomy"><?php foreach($categories as $category):?><a href="<?=e(app_url('/category/'.rawurlencode($category['slug'])))?>"><?=e($category['name'])?></a><?php endforeach;?><?php foreach($tags as $tag):?><a href="<?=e(app_url('/tag/'.rawurlencode($tag['slug'])))?>">#<?=e($tag['name'])?></a><?php endforeach;?></div><?php endif;?>
  </header>

  <?php if(!empty($entry['featured_image_path'])):?><figure class="single-entry__cover"><img src="<?=e(app_url('/storage/uploads/'.$entry['featured_image_path']))?>" alt="<?=e($entry['title'])?>"></figure><?php endif;?>

  <?php if($type==='portfolio'&&array_filter($portfolioMeta)):?><section class="portfolio-facts"><?php foreach(['client'=>'Проект','year'=>'Год','role'=>'Роль'] as $key=>$label):?><?php if(!empty($portfolioMeta[$key])):?><div><small><?=e($label)?></small><b><?=e($portfolioMeta[$key])?></b></div><?php endif;?><?php endforeach;?><?php if(!empty($portfolioMeta['project_url'])):?><div><small>Ссылка</small><a href="<?=e($portfolioMeta['project_url'])?>" rel="noopener noreferrer">Открыть проект ↗</a></div><?php endif;?></section><?php endif;?>

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
      <form method="post" action="<?=e(app_url('/content/'.(int)$entry['id'].'/reaction'))?>"><?=csrf_field()?><input type="hidden" name="reaction" value="<?=e($emoji)?>"><button type="submit" title="<?=e($label)?>"><span><?=$emoji?></span><b><?=($reactionMap[$emoji] ?? 0) ?: ''?></b></button></form>
      <?php else: ?><a href="<?=e(app_url('/login'))?>" title="Войдите, чтобы оставить реакцию"><span><?=$emoji?></span><b><?=($reactionMap[$emoji] ?? 0) ?: ''?></b></a><?php endif; ?>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<?php if (!empty($entry['comments_enabled'])): ?>
<section class="comments-section" id="comments">
  <header class="comments-section__header"><div><span class="eyebrow">ОБСУЖДЕНИЕ</span><h2>Комментарии <small><?=count($comments)?></small></h2></div></header>
  <?php if ($comments): ?><div class="comment-list">
    <?php foreach ($comments as $comment): ?><article class="comment-card" id="comment-<?=(int)$comment['id']?>"><?=avatar_html($comment, 'avatar-xs')?><div><header><a href="<?=e(app_url('/author/'.rawurlencode((string)$comment['author_username'])))?>"><?=e((string)$comment['author_name'])?></a><time><?=e(human_time((string)$comment['created_at']))?></time></header><p><?=nl2br(e((string)$comment['body']))?></p><?php if(Auth::check()&&Auth::id()!==(int)$comment['user_id']):?><details class="comment-report"><summary>Пожаловаться</summary><form method="post" action="<?=e(app_url('/content/comment/'.(int)$comment['id'].'/report'))?>"><?=csrf_field()?><select name="reason"><option>Спам</option><option>Оскорбление</option><option>Опасный или незаконный контент</option><option>Другое нарушение</option></select><textarea name="details" rows="2" maxlength="2000" placeholder="Комментарий для модератора"></textarea><button class="button button--light" type="submit">Отправить</button></form></details><?php endif;?></div></article><?php endforeach; ?>
  </div><?php else: ?><p class="comments-empty">Комментариев пока нет. Можно начать обсуждение первым.</p><?php endif; ?>
  <?php if (Auth::check()): ?><form class="comment-form" method="post" action="<?=e(app_url('/content/'.(int)$entry['id'].'/comment'))?>"><?=csrf_field()?><label for="comment-body">Ваш комментарий</label><textarea id="comment-body" name="body" minlength="2" maxlength="5000" rows="5" required placeholder="Напишите по существу, задайте вопрос или поделитесь опытом"></textarea><div><small><?=setting('comments_auto_approve', '0') === '1' || Blog::canModerate() ? 'Комментарий будет опубликован сразу.' : 'Комментарий может потребовать проверки модератором.'?></small><button class="button button--dark" type="submit">Отправить</button></div></form>
  <?php else: ?><div class="login-invite"><div><b>Присоединяйтесь к обсуждению</b><p>Зарегистрированные читатели могут оставлять комментарии и реакции.</p></div><a class="button button--dark" href="<?=e(app_url('/login'))?>">Войти</a><a class="button button--light" href="<?=e(app_url('/register'))?>">Регистрация</a></div><?php endif; ?>
</section>
<?php endif; ?>

<?php if($relatedEntries):?><section class="content-section related-section"><div class="section-heading"><div><span class="eyebrow">ПРОДОЛЖИТЬ ЧТЕНИЕ</span><h2>Другие материалы</h2></div></div><div class="entry-grid entry-grid--posts"><?php foreach($relatedEntries as $related):?><article class="entry-card"><div class="entry-card__meta"><span><?=e(date('d.m.Y',strtotime((string)$related['published_at'])))?></span></div><h3><a href="<?=e(Blog::entryUrl($related))?>"><?=e($related['title'])?></a></h3><p><?=e(Blog::excerpt($related,180))?></p><footer><a href="<?=e(Blog::entryUrl($related))?>">Открыть →</a></footer></article><?php endforeach;?></div></section><?php endif;?>
