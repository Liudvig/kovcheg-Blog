<?php
$context=(string)($context??'');$commentId=(int)($commentId??0);$summary=$summary??comment_reaction_summary($context,$commentId,\Kovcheg\Auth::id());
$allowed=['👍','❤️','🔥','😂','👏','😮','😢','👎'];
?>
<div class="comment-reaction-wrap" data-comment-reaction-wrap data-comment-context="<?=e($context)?>" data-comment-id="<?=$commentId?>">
 <?php if(\Kovcheg\Auth::check()):?><button type="button" class="comment-react-trigger" data-comment-react-trigger aria-label="Реакция">♡</button><div class="comment-reaction-picker" data-comment-reaction-picker hidden><?php foreach($allowed as $emoji):?><button type="button" data-comment-react="<?=e($emoji)?>" class="<?=($summary['mine']??null)===$emoji?'active':''?>"><?=e($emoji)?></button><?php endforeach;?></div><?php endif;?>
 <div class="comment-reaction-summary" data-comment-reaction-summary <?=empty($summary['items'])?'hidden':''?>><?php foreach(($summary['items']??[]) as $item):?><button type="button" data-comment-react="<?=e($item['emoji'])?>" class="<?=!empty($item['mine'])?'mine':''?>"><?=e($item['emoji'])?> <span><?=(int)$item['count']?></span></button><?php endforeach;?></div>
</div>
