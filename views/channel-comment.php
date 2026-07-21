<?php
$replies=$comment['replies']??[];$rootId=(int)($rootId??($comment['thread_root_id']??0));$replyCount=count($replies);$collapsed=$replyCount>1;
?>
<div class="channel-comment-node" data-channel-comment-node="<?=(int)$comment['id']?>" data-channel-comment-author="<?=e($comment['display_name']??'')?>" data-channel-comment-root="<?=$rootId?>">
 <?=\Kovcheg\View::partial('message',['m'=>$comment,'threadMode'=>true])?>
 <div class="channel-comment-inline-actions"><button type="button" data-channel-comment-reply="<?=(int)$comment['id']?>" data-channel-comment-author="<?=e($comment['display_name']??'')?>">Ответить</button><?=\Kovcheg\View::partial('comment-reactions',['context'=>'channel','commentId'=>(int)$comment['id']])?></div>
 <?php if($replyCount):?><div class="comment-branch channel-comment-branch" data-comment-branch data-collapsed="<?=$collapsed?'1':'0'?>"><?php if($collapsed):?><button type="button" class="comment-branch-toggle" data-comment-branch-toggle data-show-label="Показать <?=$replyCount?> ответов" data-hide-label="Скрыть ответы">Показать <?=$replyCount?> ответов</button><?php endif;?><div class="channel-comment-children comment-branch-children" data-channel-comment-children="<?=(int)$comment['id']?>" <?=$collapsed?'hidden':''?>><?php foreach($replies as $reply)echo \Kovcheg\View::partial('channel-comment',['comment'=>$reply,'rootId'=>$rootId]);?></div></div><?php else:?><div class="channel-comment-children" data-channel-comment-children="<?=(int)$comment['id']?>"></div><?php endif;?>
</div>
