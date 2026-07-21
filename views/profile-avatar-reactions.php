<?php
$avatarReactions=$avatarReactions??['items'=>[],'mine'=>null,'total'=>0];
$allowed=['❤️','🔥','👍','😍','👏','😊'];
?>
<div class="profile-photo-reactions" data-avatar-reactions data-profile-user-id="<?=(int)$profileUser['id']?>">
 <div class="profile-photo-reaction-summary">
  <?php foreach(array_slice($avatarReactions['items']??[],0,4) as $item):?><span><?=e($item['emoji'])?></span><?php endforeach;?>
  <b><?=((int)($avatarReactions['total']??0)>0)?(int)$avatarReactions['total']:'Реакция'?></b>
 </div>
 <?php if(\Kovcheg\Auth::check()&&!empty($profileUser['avatar_path'])):?><div class="profile-photo-reaction-picker"><?php foreach($allowed as $emoji):?><button type="button" class="<?=($avatarReactions['mine']??null)===$emoji?'active':''?>" data-avatar-react="<?=e($emoji)?>"><?=e($emoji)?></button><?php endforeach;?></div><?php endif;?>
</div>
