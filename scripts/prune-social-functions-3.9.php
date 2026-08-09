<?php

declare(strict_types=1);

$root=dirname(__DIR__);
$functionsPath=$root.'/app/functions.php';
$write=in_array('--write',$argv,true);

$targets=[
    'profile_post_reaction_counts',
    'profile_post_reaction_state',
    'profile_comment_reaction_summary',
    'profile_posts',
    'profile_counts',
    'profile_request_users',
    'profile_people',
    'profile_following',
    'profile_people_preview',
    'follow_user',
    'unfollow_user',
    'colleague_request',
    'colleague_decide',
    'colleague_remove',
    'feed_rows',
    'profile_posts_for_viewer',
    'profile_post_create',
    'profile_post_comment_create',
    'profile_post_comment_delete',
    'profile_post_delete',
    'profile_post_reaction_toggle',
    'profile_comment_reaction_toggle',
    'profile_post_viewer_reaction',
    'profile_post_thread',
    'chat_member',
    'require_chat_member',
    'require_chat_admin',
    'chat_title',
    'channel_public_url',
    'channel_post_public_url',
    'channel_avatar_url',
    'chat_avatar_html',
    'channel_can_post',
    'channel_can_manage',
    'message_moderation_permissions',
    'chat_list_for_user',
    'chat_unread_count',
    'message_preview',
    'message_payload',
    'message_row',
    'message_access',
    'message_action_capabilities',
    'message_render_payload',
    'notification_preview_for_user',
    'user_notifications',
    'notification_count',
    'notification_mark_read',
    'profile_right_blocks',
    'user_is_colleague',
    'message_public_url',
    'profile_posts_for_feed',
    'profile_create_post',
    'profile_follow',
    'send_push',
];
$targetSet=array_fill_keys($targets,true);

ob_start();
require __DIR__.'/report-function-usage-3.9.php';
$report=(string)ob_get_clean();

foreach($targets as $name){
    if(isset($reachable[$name])){
        fwrite(STDERR,"Refusing to prune reachable function: {$name}\n");
        exit(2);
    }
}

$source=@file_get_contents($functionsPath);
if(!is_string($source)||$source===''){
    fwrite(STDERR,"Cannot read app/functions.php\n");
    exit(3);
}

$tokens=token_get_all($source);
$records=[];
$offset=0;
foreach($tokens as $index=>$token){
    $text=is_array($token)?$token[1]:$token;
    $records[$index]=[
        'token'=>$token,
        'text'=>$text,
        'offset'=>$offset,
    ];
    $offset+=strlen($text);
}

$spans=[];
$count=count($records);
for($i=0;$i<$count;$i++){
    $token=$records[$i]['token'];
    if(!is_array($token)||$token[0]!==T_FUNCTION)continue;

    $j=$i+1;
    while($j<$count){
        $candidate=$records[$j]['token'];
        $text=$records[$j]['text'];
        if(is_array($candidate)&&in_array($candidate[0],[T_WHITESPACE,T_AMPERSAND_FOLLOWED_BY_VAR_OR_VARARG,T_AMPERSAND_NOT_FOLLOWED_BY_VAR_OR_VARARG],true)){$j++;continue;}
        if($text==='&'){$j++;continue;}
        break;
    }
    if($j>=$count||!is_array($records[$j]['token'])||$records[$j]['token'][0]!==T_STRING)continue;
    $name=$records[$j]['token'][1];
    if(!isset($targetSet[$name]))continue;

    $body=$j;
    while($body<$count&&$records[$body]['text']!=='{')$body++;
    if($body>=$count){
        fwrite(STDERR,"Cannot find body for {$name}\n");
        exit(4);
    }

    $depth=1;
    $end=$body+1;
    while($end<$count&&$depth>0){
        $text=$records[$end]['text'];
        if($text==='{')$depth++;
        elseif($text==='}')$depth--;
        $end++;
    }
    if($depth!==0){
        fwrite(STDERR,"Unbalanced body for {$name}\n");
        exit(5);
    }

    $startOffset=$records[$i]['offset'];
    $lastIndex=$end-1;
    $endOffset=$records[$lastIndex]['offset']+strlen($records[$lastIndex]['text']);
    $spans[]=['name'=>$name,'start'=>$startOffset,'end'=>$endOffset];
}

if(!$spans){
    echo $report;
    echo "No target social functions remain; nothing to prune.\n";
    exit(0);
}

$found=array_column($spans,'name');
$missing=array_values(array_diff($targets,$found));
if($missing){
    echo 'Already absent targets: '.implode(', ',$missing)."\n";
}

usort($spans,static fn(array $a,array $b):int=>$b['start']<=>$a['start']);
$pruned=$source;
foreach($spans as $span){
    $length=$span['end']-$span['start'];
    $pruned=substr_replace($pruned,"",$span['start'],$length);
}
$pruned=preg_replace("/\n{4,}/","\n\n\n",$pruned)??$pruned;

$tmp='/tmp/kovcheg-functions-pruned.php';
file_put_contents($tmp,$pruned,LOCK_EX);

echo $report;
echo 'Prune candidate removes '.count($spans).' functions: '.implode(', ',array_reverse(array_column($spans,'name')))."\n";
echo 'Size: '.strlen($source).' -> '.strlen($pruned)." bytes\n";

if($write){
    if(file_put_contents($functionsPath,$pruned,LOCK_EX)===false){
        fwrite(STDERR,"Cannot write app/functions.php\n");
        exit(6);
    }
    echo "Updated app/functions.php\n";
}else{
    echo "Dry run only; use --write to update app/functions.php\n";
}
