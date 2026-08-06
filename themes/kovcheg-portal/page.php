<?php

declare(strict_types=1);

use Kovcheg\Auth;
use Kovcheg\DB;
use Kovcheg\Blog\Blog;
use Kovcheg\Blog\Studio;

$viewCount=(int)($viewCount??0);
$updated=(string)($entry['updated_at']?:$entry['published_at']?:$entry['created_at']);
$featuredMediaId=0;
if(!empty($entry['featured_image_path'])){
    $featuredMediaId=(int)(DB::one('SELECT id FROM media_library WHERE stored_path=? LIMIT 1',[$entry['featured_image_path']])['id']??0);
}
$excerpt=trim((string)($entry['excerpt']??''));
$contentHtml=trim((string)($entry['content_html']??''));
?>
<link rel="stylesheet" href="<?=e($themeAsset('page.css').'?v='.rawurlencode(ASSET_REVISION))?>">
<link rel="stylesheet" href="<?=e($themeAsset('document-compact-3.8.1.css').'?v='.rawurlencode(ASSET_REVISION))?>">

<article class="site-page">
 <h1 class="site-page-title-sr"><?=e((string)$entry['title'])?></h1>

 <?php if(!empty($studioPreview)):?>
  <div class="site-page-preview">
   <b>Предпросмотр</b>
   <span>Страница ещё не доступна обычным посетителям.</span>
   <a href="<?=e(app_url('/studio/pages/'.(int)$entry['id'].'/edit'))?>">Вернуться в редактор</a>
  </div>
 <?php endif;?>

 <nav class="site-page-breadcrumbs" aria-label="Навигация">
  <a href="<?=e(app_url('/'))?>">Главная</a>
  <span>›</span>
  <span aria-current="page">Страница</span>
 </nav>

 <header class="site-page-header site-page-header--compact">
  <div class="site-page-utility">
   <div class="site-page-meta">
    <span>Обновлено <?=e(date('d.m.Y',strtotime($updated)))?></span>
    <?php if($viewCount>0):?><span>Просмотров: <?=$viewCount?></span><?php endif;?>
   </div>
   <?php if(Studio::can('content')):?><a class="site-page-edit" href="<?=e(app_url('/studio/pages/'.(int)$entry['id'].'/edit'))?>">Изменить</a><?php endif;?>
  </div>
 </header>

 <?php if($featuredMediaId):?>
  <figure class="site-page-cover">
   <img src="<?=e(app_url('/media/'.$featuredMediaId))?>" alt="<?=e((string)$entry['title'])?>">
  </figure>
 <?php endif;?>

 <?php if($excerpt!=='' && $contentHtml!==''):?><p class="site-page-lead"><?=e($excerpt)?></p><?php endif;?>

 <div class="site-page-content prose">
  <?php if($contentHtml!==''):?>
   <?=$entry['content_html']?>
  <?php elseif($excerpt!==''):?>
   <p><?=nl2br(e($excerpt))?></p>
  <?php else:?>
   <p class="site-page-empty">Содержимое страницы пока не заполнено.</p>
  <?php endif;?>
 </div>
</article>

<?php if(!empty($entry['comments_enabled'])):?>
<section class="site-page-comments" id="comments">
 <header><span>Комментарии</span><h2>Обсуждение <small><?=count($comments)?></small></h2></header>
 <?php if($comments):?>
  <div class="site-page-comment-list">
   <?php foreach($comments as $comment):?>
    <article id="comment-<?=(int)$comment['id']?>">
     <?=avatar_html($comment,'avatar-xs')?>
     <div><header><b><?=e((string)$comment['author_name'])?></b><time><?=e(human_time((string)$comment['created_at']))?></time></header><p><?=nl2br(e((string)$comment['body']))?></p></div>
    </article>
   <?php endforeach;?>
  </div>
 <?php else:?><p class="site-page-comments-empty">Комментариев пока нет.</p><?php endif;?>

 <?php if(Auth::check()):?>
  <form class="site-page-comment-form" method="post" action="<?=e(app_url('/content/'.(int)$entry['id'].'/comment'))?>">
   <?=csrf_field()?>
   <label for="comment-body">Комментарий</label>
   <textarea id="comment-body" name="body" minlength="2" maxlength="5000" rows="4" required></textarea>
   <div><small><?=setting('comments_auto_approve','0')==='1'||Blog::canModerate()?'Будет опубликован сразу.':'Может потребоваться проверка.'?></small><button type="submit">Отправить</button></div>
  </form>
 <?php else:?><div class="site-page-login"><span>Войдите, чтобы оставить комментарий.</span><a href="<?=e(app_url('/login'))?>">Войти</a></div><?php endif;?>
</section>
<?php endif;?>
