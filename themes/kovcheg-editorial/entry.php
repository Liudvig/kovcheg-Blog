<?php
use Kovcheg\Auth;
use Kovcheg\DB;
use Kovcheg\Blog\Blog;

$type=(string)($entry['type']??'post')==='page'?'page':'post';
$typeLabel=$type==='page'?'Страница':'Запись';
$published=(string)($entry['published_at']?:$entry['created_at']);
$categories=$type==='post'?($categories??[]):[];
$viewCount=(int)($viewCount??0);
$relatedEntries=$relatedEntries??[];
$featuredMediaId=0;
if(!empty($entry['featured_image_path']))$featuredMediaId=(int)(DB::one('SELECT id FROM media_library WHERE stored_path=? LIMIT 1',[$entry['featured_image_path']])['id']??0);
?>
<article class="single-entry">
 <header class="single-entry__header">
  <a class="single-entry__back" href="<?=e($type==='page'?app_url('/'):app_url('/blog'))?>">← Назад</a>
  <span class="eyebrow"><?=e($typeLabel)?></span>
  <h1><?=e((string)$entry['title'])?></h1>
  <?php if($type==='post'&&!empty($entry['excerpt'])):?><p class="single-entry__lead"><?=e((string)$entry['excerpt'])?></p><?php endif;?>
  <div class="single-entry__meta">
   <a href="<?=e(app_url('/author/'.rawurlencode((string)$entry['author_username'])))?>"><?=avatar_html($entry,'avatar-xs')?> <span><?=e((string)$entry['author_name'])?></span></a>
   <time datetime="<?=e(date('c',strtotime($published)))?>"><?=e(date('d.m.Y',strtotime($published)))?></time>
   <span>Просмотров: <?=$viewCount?></span>
  </div>
  <?php if($categories):?><div class="entry-taxonomy"><?php foreach($categories as $category):?><a href="<?=e(app_url('/category/'.rawurlencode((string)$category['slug'])))?>"><?=e((string)$category['name'])?></a><?php endforeach;?></div><?php endif;?>
 </header>

 <?php if($featuredMediaId):?><figure class="single-entry__cover"><img src="<?=e(app_url('/media/'.$featuredMediaId))?>" alt="<?=e((string)$entry['title'])?>"></figure><?php endif;?>

 <div class="single-entry__body prose">
  <?php if(trim((string)($entry['content_html']??''))!==''):?>
   <?=$entry['content_html']?>
  <?php elseif(trim((string)($entry['excerpt']??''))!==''):?>
   <p><?=nl2br(e((string)$entry['excerpt']))?></p>
  <?php else:?><p>Содержание готовится.</p><?php endif;?>
 </div>

 <footer class="single-entry__footer">
  <div class="author-card"><?=avatar_html($entry,'avatar')?><div><span>Автор</span><h3><?=e((string)$entry['author_name'])?></h3><p><?=e((string)($entry['author_bio']?:'Автор записи.'))?></p><a href="<?=e(app_url('/author/'.rawurlencode((string)$entry['author_username'])))?>">Все записи автора →</a></div></div>
 </footer>
</article>

<?php if(!empty($entry['comments_enabled'])):?>
<section class="comments-section" id="comments">
 <header class="comments-section__header"><div><span class="eyebrow">КОММЕНТАРИИ</span><h2>Обсуждение <small><?=count($comments)?></small></h2></div></header>
 <?php if($comments):?><div class="comment-list"><?php foreach($comments as $comment):?><article class="comment-card" id="comment-<?=(int)$comment['id']?>"><?=avatar_html($comment,'avatar-xs')?><div><header><a href="<?=e(app_url('/author/'.rawurlencode((string)$comment['author_username'])))?>"><?=e((string)$comment['author_name'])?></a><time><?=e(human_time((string)$comment['created_at']))?></time></header><p><?=nl2br(e((string)$comment['body']))?></p></div></article><?php endforeach;?></div><?php else:?><p class="comments-empty">Комментариев пока нет.</p><?php endif;?>
 <?php if(Auth::check()):?><form class="comment-form" method="post" action="<?=e(app_url('/content/'.(int)$entry['id'].'/comment'))?>"><?=csrf_field()?><label for="comment-body">Комментарий</label><textarea id="comment-body" name="body" minlength="2" maxlength="5000" rows="5" required></textarea><div><small><?=setting('comments_auto_approve','0')==='1'||Blog::canModerate()?'Будет опубликован сразу.':'Может потребоваться проверка.'?></small><button class="button button--dark" type="submit">Отправить</button></div></form><?php else:?><div class="login-invite"><div><b>Войдите, чтобы оставить комментарий</b></div><a class="button button--dark" href="<?=e(app_url('/login'))?>">Войти</a></div><?php endif;?>
</section>
<?php endif;?>

<?php if($relatedEntries):?><section class="content-section related-section"><div class="section-heading"><div><span class="eyebrow">ЕЩЁ</span><h2><?=$type==='page'?'Другие страницы':'Другие записи'?></h2></div></div><div class="entry-grid entry-grid--posts"><?php foreach($relatedEntries as $related):?><article class="entry-card"><div class="entry-card__meta"><span><?=e(date('d.m.Y',strtotime((string)$related['published_at'])))?></span></div><h3><a href="<?=e(Blog::entryUrl($related))?>"><?=e((string)$related['title'])?></a></h3><p><?=e(Blog::excerpt($related,180))?></p><footer><a href="<?=e(Blog::entryUrl($related))?>">Открыть →</a></footer></article><?php endforeach;?></div></section><?php endif;?>
