<?php

declare(strict_types=1);

$root=dirname(__DIR__);
$errors=[];

$activeFiles=[
    'index.php',
    'cron.php',
    'app/bootstrap.php',
    'app/Blog.php',
    'app/BlogGrowth.php',
    'app/BlogLayout.php',
    'app/BlogLayoutRepair.php',
    'app/BlogModules.php',
    'app/BlogStudio.php',
    'app/BlogStudio32.php',
    'app/BlogThemeSupport.php',
    'app/ClassicEditor.php',
];

foreach(['routes','themes','modules','views'] as $directory){
    $base=$root.'/'.$directory;
    if(!is_dir($base))continue;
    $iterator=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base,FilesystemIterator::SKIP_DOTS));
    foreach($iterator as $file){
        if(!$file->isFile())continue;
        $ext=strtolower(pathinfo($file->getFilename(),PATHINFO_EXTENSION));
        if(!in_array($ext,['php','js','css','json'],true))continue;
        $activeFiles[]=str_replace('\\','/',substr($file->getPathname(),strlen($root)+1));
    }
}

$activeFiles=array_values(array_unique($activeFiles));
sort($activeFiles,SORT_STRING);

$forbiddenPatterns=[
    '/\b(?:FROM|JOIN|INTO|UPDATE|TABLE)\s+`?chats`?\b/i'=>'chats table',
    '/\b(?:FROM|JOIN|INTO|UPDATE|TABLE)\s+`?messages`?\b/i'=>'messages table',
    '/\bchat_members\b/i'=>'chat_members table',
    '/\bchat_events\b/i'=>'chat_events table',
    '/\bprofile_posts\b/i'=>'profile_posts table',
    '/\bprofile_post_[a-z_]+\b/i'=>'profile_post tables',
    '/\buser_follows\b/i'=>'user_follows table',
    '/\bcolleague_requests\b/i'=>'colleague_requests table',
    '/\bstories\b/i'=>'stories table',
    '/\bstory_(?:views|reactions)\b/i'=>'story tables',
    '/\bpush_subscriptions\b/i'=>'push_subscriptions table',
    '/\bpush_deliveries\b/i'=>'push_deliveries table',
    '/\bchat_(?:member|list_for_user|unread_count|public_url|avatar_html)\s*\(/i'=>'legacy chat helper',
    '/\b(?:channel_public_url|channel_post_public_url|message_public_url|wall_post_public_url)\s*\(/i'=>'legacy social URL helper',
];

foreach($activeFiles as $relative){
    $path=$root.'/'.$relative;
    $data=@file_get_contents($path);
    if(!is_string($data)){$errors[]='Не удалось прочитать active runtime file: '.$relative;continue;}
    foreach($forbiddenPatterns as $pattern=>$label){
        if(preg_match($pattern,$data)===1)$errors[]=$relative.' зависит от '.$label;
    }
}

// app/functions.php intentionally remains outside this scan for now: it contains
// both required CMS/system helpers and isolated historical helper definitions.
// The purpose of this audit is to prove that the active runtime no longer calls
// or queries the retired social subsystem before those definitions are removed.

if($errors){
    fwrite(STDERR,"KOVCHEG active runtime social-free audit failed:\n- ".implode("\n- ",array_values(array_unique($errors)))."\n");
    exit(1);
}

echo "KOVCHEG active runtime social-free audit OK (".count($activeFiles)." files checked)\n";
