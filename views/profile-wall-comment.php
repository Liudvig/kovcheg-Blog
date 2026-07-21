<?php
$replies=$comment['replies']??[];$replyCount=count($replies);$collapsed=$replyCount>1;
?>
<article class="wall-comment <?=!empty($comment['parent_id'])?'wall-comment-reply':''?>" data-wall-comment="<?=(int)$comment['id']?>">
 <?=avatar_html(['id'=>$comment['user_id'],'display_name'=>$comment['display_name'],'avatar_path'=>$comment['avatar_path']??null],'avatar-xs')?>
 <div class="wall-comment-body">
  <header><a href="<?=e(user_public_url((string)$comment['username']))?>"><b><?=e($comment['display_name'])?></b><?=verified_badge($comment)?></a><time><?=e(human_time($comment['created_at']))?></time><?php if((int)$comment['user_id']===\Kovcheg\Auth::id()||\Kovcheg\Auth::isAdmin()):?><button type="button" data-wall-comment-delete="<?=(int)$comment['id']?>" aria-label="Удалить">×</button><?php endif;?></header>
  <?php if(trim((string)$comment['body'])!==''):?><p><?=safe_message_html((string)$comment['body'])?></p><?php endif;?>
  <footer><button type="button" data-wall-comment-reply="<?=(int)$comment['id']?>" data-wall-comment-author="<?=e($comment['display_name'])?>">Ответить</button><?=\Kovcheg\View::partial('comment-reactions',['context'=>'wall','commentId'=>(int)$comment['id']])?></footer>
  <?php if($replyCount):?><div class="comment-branch" data-comment-branch data-collapsed="<?=$collapsed?'1':'0'?>"><?php if($collapsed):?><button type="button" class="comment-branch-toggle" data-comment-branch-toggle data-show-label="Показать <?=$replyCount?> ответов" data-hide-label="Скрыть ответы">Показать <?=$replyCount?> ответов</button><?php endif;?><div class="wall-comment-replies comment-branch-children" data-wall-comment-replies="<?=(int)$comment['id']?>" <?=$collapsed?'hidden':''?>><?php foreach($replies as $reply)echo \Kovcheg\View::partial('profile-wall-comment',['comment'=>$reply]);?></div></div><?php else:?><div class="wall-comment-replies" data-wall-comment-replies="<?=(int)$comment['id']?>"></div><?php endif;?>
 </div>
</article>
