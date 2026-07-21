<?php
$comment=$comment??[];$replies=$comment['replies']??[];$replyCount=count($replies);$collapsed=$replyCount>1;$canDelete=\Kovcheg\Auth::check()&&((int)($comment['user_id']??0)===\Kovcheg\Auth::id()||\Kovcheg\Auth::isAdmin());
?>
<article class="avatar-comment" data-avatar-comment-id="<?=(int)$comment['id']?>">
 <?=avatar_html($comment,'avatar-xs')?>
 <div class="avatar-comment-body">
  <header><a href="<?=e(user_public_url((string)$comment['username']))?>"><b><?=e($comment['display_name'])?><?=verified_badge($comment)?></b></a><time><?=e(human_time($comment['created_at']))?></time></header>
  <p><?=nl2br(e($comment['body']))?></p>
  <div class="avatar-comment-actions"><button type="button" class="avatar-comment-reply" data-avatar-comment-reply="<?=(int)$comment['id']?>" data-avatar-comment-author="<?=e($comment['display_name'])?>">Ответить</button><?=\Kovcheg\View::partial('comment-reactions',['context'=>'avatar','commentId'=>(int)$comment['id']])?><?php if($canDelete):?><button type="button" class="avatar-comment-delete" data-avatar-comment-delete="<?=(int)$comment['id']?>">Удалить</button><?php endif;?></div>
  <?php if($replyCount):?><div class="comment-branch" data-comment-branch data-collapsed="<?=$collapsed?'1':'0'?>"><?php if($collapsed):?><button type="button" class="comment-branch-toggle" data-comment-branch-toggle data-show-label="Показать <?=$replyCount?> ответов" data-hide-label="Скрыть ответы">Показать <?=$replyCount?> ответов</button><?php endif;?><div class="avatar-comment-replies comment-branch-children" data-avatar-comment-replies <?=$collapsed?'hidden':''?>><?php foreach($replies as $reply)echo \Kovcheg\View::partial('avatar-comment',['comment'=>$reply]);?></div></div><?php else:?><div class="avatar-comment-replies" data-avatar-comment-replies></div><?php endif;?>
 </div>
</article>
