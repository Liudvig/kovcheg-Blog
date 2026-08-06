<?php

declare(strict_types=1);

use Kovcheg\Auth;
use Kovcheg\DB;
use Kovcheg\Blog\Blog;
use Kovcheg\Blog\Studio;

$categories=array_values($categories??[]);
$relatedEntries=array_values($relatedEntries??[]);
$viewCount=(int)($viewCount??0);
$updated=(string)($entry['updated_at']?:$entry['published_at']?:$entry['created_at']);
$featuredMediaId=0;
if(!empty($entry['featured_image_path']))$featuredMediaId=(int)(DB::one('SELECT id FROM media_library WHERE stored_path=? LIMIT 1',[$entry['featured_image_path']])['id']??0);
$firstCategory=$categories[0]??null;
?>
<link rel="stylesheet" href="<?=e($themeAsset('page.css').'?v='.rawurlencode(ASSET_REVISION))?>">

<article class="site-page">
 <?php if(!empty($studioPreview)):?><div class="site-page-preview"><b>Предпросмотр</b><span>Эта страница ещё не доступна обычным посетителям.</span><a href="<?=e(app_url('/studio/pages/'.(int)$entry['id'].'/edit'))?>">Вернуться в редактор</a></div><?php endif;?>

 <nav class="site-page-breadcrumbs" aria-label="Хлебные крошки">
  <a href="<?=e(app_url('/'))?>">Главная</a>
  <?php if($firstCategory):?><span>›</span><a href="<?=e(app_url('/category/'.rawurlencode((string)$firstCategory['slug'])))?>"><?=e((string)$firstCategory['name'])?></a><?php endif;?>
  <span>›</span><span aria-current="page"><?=e((string)$entry['title'])?></span>
 </nav>

 <header class="site-page-header">
  <?php if($categories):?><div class="site-page-rubrics"><?php foreach($categories as $category):?><a href="<?=e(app_url('/category/'.rawurlencode((string)$category['slug'])))?>"><?=e((string)$category['name'])?></a><?php endforeach;?></div><?php endif;?>
  <div class="site-page-title-row">
   <div>
    <h1><?=e((string)$entry['title'])?></h1>
    <?php if(trim((string)($entry['excerpt']??''))!==''):?><p class="site-page-lead"><?=e((string)$entry['excerpt'])?></p><?php endif;?>
   </div>
   <?php if(Studio::can('content')):?><a class="site-page-edit" href="<?=e(app_url('/studio/pages/'.(int)$entry['id'].'/edit'))?>">Изменить</a><?php endif;?>
  </div>
  <div class="site-page-meta"><span>Обновлено <?=e(date('d.m.Y',strtotime($updated)))?></span><?php if($viewCount>0):?><span>Просмотров: <?=$viewCount?></span><?php endif;?></div>
 </header>

 <?php if($featuredMediaId):?><figure class="site-page-cover"><img src="<?=e(app_url('/media/'.$featuredMediaId))?>" alt="<?=e((string)$entry['title'])?>"></figure><?php endif;?>

 <div class="site-page-content prose">
  <?php if(trim((string)($entry['content_html']??''))!==''):?>
   <?=$entry['content_html']?>
  <?php elseif(trim((string)($entry['excerpt']??''))!==''):?>
   <p><?=nl2br(e((string)$entry['excerpt']))?></p>
  <?php else:?><p class="site-page-empty">Содержимое страницы пока не заполнено.</p><?php endif;?>
 </div>

 <?php if($categories):?><footer class="site-page-footer"><span>Разделы:</span><?php foreach($categories as $category):?><a href="<?=e(app_url('/category/'.rawurlencode((string)$category['slug'])))?>"><?=e((string)$category['name'])?></a><?php endforeach;?></footer><?php endif;?>
</article>

<?php if($relatedEntries):?>
<section class="site-page-related">
 <header><span>Продолжить просмотр</span><h2><?=$firstCategory?'Ещё в разделе «'.e((string)$firstCategory['name']).'»':'Другие страницы'?></h2></header>
 <div class="site-page-related-grid">
  <?php foreach($relatedEntries as $related):?><article><h3><a href="<?=e(Blog::entryUrl($related))?>"><?=e((string)$related['title'])?></a></h3><p><?=e(Blog::excerpt($related,150))?></p><a class="site-page-related-open" href="<?=e(Blog::entryUrl($related))?>">Открыть →</a></article><?php endforeach;?>
 </div>
</section>
<?php endif;?>

<?php if(!empty($entry['comments_enabled'])):?>
<section class="site-page-comments" id="comments">
 <header><span>Комментарии</span><h2>Обсуждение <small><?=count($comments)?></small></h2></header>
 <?php if($comments):?><div class="site-page-comment-list"><?php foreach($comments as $comment):?><article id="comment-<?=(int)$comment['id']?>"><?=avatar_html($comment,'avatar-xs')?><div><header><b><?=e((string)$comment['author_name'])?></b><time><?=e(human_time((string)$comment['created_at']))?></time></header><p><?=nl2br(e((string)$comment['body']))?></p></div></article><?php endforeach;?></div><?php else:?><p class="site-page-comments-empty">Комментариев пока нет.</p><?php endif;?>
 <?php if(Auth::check()):?><form class="site-page-comment-form" method="post" action="<?=e(app_url('/content/'.(int)$entry['id'].'/comment'))?>"><?=csrf_field()?><label for="comment-body">Комментарий</label><textarea id="comment-body" name="body" minlength="2" maxlength="5000" rows="5" required></textarea><div><small><?=setting('comments_auto_approve','0')==='1'||Blog::canModerate()?'Будет опубликован сразу.':'Может потребоваться проверка.'?></small><button type="submit">Отправить</button></div></form><?php else:?><div class="site-page-login"><span>Войдите, чтобы оставить комментарий.</span><a href="<?=e(app_url('/login'))?>">Войти</a></div><?php endif;?>
</section>
<?php endif;?>
