<?php
$profileUser=$profileUser??[];
$wallPosts=$wallPosts??[];
$canPostWall=!empty($canPostWall);
$isSelf=\Kovcheg\Auth::check()&&\Kovcheg\Auth::id()===(int)($profileUser['id']??0);
$current=\Kovcheg\Auth::user()??[];
?>
<section class="vk-wall wall-090" data-profile-wall>
 <?php if($canPostWall):?>
 <?=\Kovcheg\View::partial('wall-composer',['current'=>$current,'action'=>app_url('/profile/'.(int)$profileUser['id'].'/wall'),'placeholder'=>$isSelf?'Напишите что-нибудь…':'Написать на стене','composerContext'=>'profile','allowDeferred'=>$isSelf])?>
 <?php else:?><div class="wall-search-only"><?=\Kovcheg\View::partial('wall-composer',['current'=>$current,'action'=>'#','placeholder'=>'','composerContext'=>'readonly','canCreate'=>false])?></div><?php endif;?>
 <div class="vk-wall-feed wall-feed-090" data-wall-feed>
  <?php foreach($wallPosts as $post):
   $post['author_name']=$post['author_name']??$post['display_name']??'';
   $post['author_username']=$post['author_username']??$post['username']??'';
   $post['wall_name']=$post['wall_name']??$profileUser['display_name']??'';
   $post['wall_username']=$post['wall_username']??$profileUser['username']??'';
   echo \Kovcheg\View::partial('feed-post',['post'=>$post]);
  endforeach;?>
  <?php if(!$wallPosts):?><div class="vk-wall-empty empty-card-090" data-wall-empty>На стене пока нет записей.</div><?php endif;?>
 </div>
</section>
