<?php

declare(strict_types=1);

$root=dirname(__DIR__);
$functionsFile=$root.'/app/functions.php';
$source=@file_get_contents($functionsFile);
if(!is_string($source)||$source===''){
    fwrite(STDERR,"Cannot read app/functions.php\n");
    exit(1);
}

/** @return array<string,array{line:int,calls:array<string,true>}> */
function kovcheg_parse_functions(string $source): array
{
    $tokens=token_get_all($source);
    $definitions=[];
    $count=count($tokens);

    for($i=0;$i<$count;$i++){
        $token=$tokens[$i];
        if(!is_array($token)||$token[0]!==T_FUNCTION)continue;

        $j=$i+1;
        while($j<$count){
            $candidate=$tokens[$j];
            if(is_array($candidate)&&in_array($candidate[0],[T_WHITESPACE,T_AMPERSAND_FOLLOWED_BY_VAR_OR_VARARG,T_AMPERSAND_NOT_FOLLOWED_BY_VAR_OR_VARARG],true)){$j++;continue;}
            if($candidate==='&'){$j++;continue;}
            break;
        }
        if($j>=$count||!is_array($tokens[$j])||$tokens[$j][0]!==T_STRING)continue;
        $name=$tokens[$j][1];
        $functionLine=$tokens[$j][2];

        while($j<$count&&$tokens[$j]!=='{')$j++;
        if($j>=$count)continue;

        $depth=1;
        $calls=[];
        for($k=$j+1;$k<$count&&$depth>0;$k++){
            $current=$tokens[$k];
            if($current==='{'){$depth++;continue;}
            if($current==='}'){$depth--;continue;}
            if(!is_array($current)||$current[0]!==T_STRING)continue;

            $next=$k+1;
            while($next<$count&&is_array($tokens[$next])&&$tokens[$next][0]===T_WHITESPACE)$next++;
            if($next<$count&&$tokens[$next]==='('){
                $prev=$k-1;
                while($prev>=0&&is_array($tokens[$prev])&&$tokens[$prev][0]===T_WHITESPACE)$prev--;
                $previous=$prev>=0?$tokens[$prev]:null;
                if($previous==='->'||$previous==='::')continue;
                if(is_array($previous)&&in_array($previous[0],[T_FUNCTION,T_NEW],true))continue;
                $calls[$current[1]]=true;
            }
        }
        $definitions[$name]=['line'=>$functionLine,'calls'=>$calls];
        $i=max($i,$j);
    }
    return $definitions;
}

$definitions=kovcheg_parse_functions($source);
$definedNames=array_fill_keys(array_keys($definitions),true);

$externalRoots=[];
$scanFiles=[];
foreach(['index.php','cron.php','install.php'] as $file)if(is_file($root.'/'.$file))$scanFiles[]=$root.'/'.$file;
foreach(['app','routes','views','themes','modules','bin'] as $directory){
    $base=$root.'/'.$directory;
    if(!is_dir($base))continue;
    $iterator=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base,FilesystemIterator::SKIP_DOTS));
    foreach($iterator as $file){
        if(!$file->isFile()||strtolower($file->getExtension())!=='php')continue;
        $path=$file->getPathname();
        if(realpath($path)===realpath($functionsFile))continue;
        $scanFiles[]=$path;
    }
}
$scanFiles=array_values(array_unique($scanFiles));

foreach($scanFiles as $path){
    $data=@file_get_contents($path);
    if(!is_string($data))continue;
    $tokens=token_get_all($data);
    $count=count($tokens);
    for($i=0;$i<$count;$i++){
        $token=$tokens[$i];
        if(!is_array($token)||$token[0]!==T_STRING)continue;
        $name=$token[1];
        if(!isset($definedNames[$name]))continue;
        $next=$i+1;
        while($next<$count&&is_array($tokens[$next])&&$tokens[$next][0]===T_WHITESPACE)$next++;
        if($next>=$count||$tokens[$next]!=='(')continue;
        $prev=$i-1;
        while($prev>=0&&is_array($tokens[$prev])&&$tokens[$prev][0]===T_WHITESPACE)$prev--;
        $previous=$prev>=0?$tokens[$prev]:null;
        if($previous==='->'||$previous==='::')continue;
        if(is_array($previous)&&$previous[0]===T_FUNCTION)continue;
        $externalRoots[$name]=true;
    }
}

$reachable=[];
$queue=array_keys($externalRoots);
while($queue){
    $name=array_pop($queue);
    if(isset($reachable[$name])||!isset($definitions[$name]))continue;
    $reachable[$name]=true;
    foreach($definitions[$name]['calls'] as $called=>$_){
        if(isset($definitions[$called])&&!isset($reachable[$called]))$queue[]=$called;
    }
}

$unreachable=[];
foreach($definitions as $name=>$meta)if(!isset($reachable[$name]))$unreachable[$name]=$meta;
uksort($unreachable,static fn(string $a,string $b):int=>$unreachable[$a]['line']<=>$unreachable[$b]['line']);

$socialPattern='/^(?:chat|channel|message|wall|profile|colleague|follow|story|push|notification|user_notifications|direct_chat|users_blocked|block_user|unblock_user|feed_|reaction_|save_wall|send_push|web_push)/i';
$socialUnreachable=[];
$socialReachable=[];
foreach($unreachable as $name=>$meta)if(preg_match($socialPattern,$name))$socialUnreachable[$name]=$meta;
foreach($reachable as $name=>$_)if(preg_match($socialPattern,$name))$socialReachable[$name]=$definitions[$name];

$reachableOrdered=[];
foreach($reachable as $name=>$_)$reachableOrdered[$name]=$definitions[$name];
uksort($reachableOrdered,static fn(string $a,string $b):int=>$reachableOrdered[$a]['line']<=>$reachableOrdered[$b]['line']);
uksort($socialReachable,static fn(string $a,string $b):int=>$socialReachable[$a]['line']<=>$socialReachable[$b]['line']);

echo 'KOVCHEG function usage audit: '.count($definitions).' definitions, '.count($reachable).' reachable, '.count($unreachable).' unreachable, '.count($socialUnreachable)." unreachable social candidates, ".count($socialReachable)." reachable social candidates\n";

if($socialReachable||$socialUnreachable){
    foreach($socialReachable as $name=>$meta)fwrite(STDERR,'SOCIAL_REACHABLE line '.$meta['line'].': '.$name."\n");
    foreach($socialUnreachable as $name=>$meta)fwrite(STDERR,'SOCIAL_UNREACHABLE line '.$meta['line'].': '.$name."\n");
    fwrite(STDERR,"KOVCHEG function usage audit failed: social helper definitions remain in app/functions.php\n");
    exit(1);
}

echo "KOVCHEG function usage audit OK: no social helper definitions remain\n";
