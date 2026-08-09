<?php

declare(strict_types=1);

$root=dirname(__DIR__);
$functionsPath=$root.'/app/functions.php';
$write=in_array('--write',$argv,true);
$source=@file_get_contents($functionsPath);
if(!is_string($source)||$source===''){fwrite(STDERR,"Cannot read app/functions.php\n");exit(1);}

function parse_global_functions_for_prune(string $source): array
{
    $tokens=token_get_all($source);$definitions=[];$count=count($tokens);
    for($i=0;$i<$count;$i++){
        $token=$tokens[$i];if(!is_array($token)||$token[0]!==T_FUNCTION)continue;
        $j=$i+1;
        while($j<$count){$candidate=$tokens[$j];if(is_array($candidate)&&in_array($candidate[0],[T_WHITESPACE,T_AMPERSAND_FOLLOWED_BY_VAR_OR_VARARG,T_AMPERSAND_NOT_FOLLOWED_BY_VAR_OR_VARARG],true)){$j++;continue;}if($candidate==='&'){$j++;continue;}break;}
        if($j>=$count||!is_array($tokens[$j])||$tokens[$j][0]!==T_STRING)continue;
        $name=$tokens[$j][1];$line=$tokens[$j][2];
        while($j<$count&&$tokens[$j]!=='{')$j++;if($j>=$count)continue;
        $depth=1;$calls=[];$endToken=$j+1;
        for($k=$j+1;$k<$count&&$depth>0;$k++){
            $current=$tokens[$k];$endToken=$k+1;
            if($current==='{'){$depth++;continue;}if($current==='}'){$depth--;continue;}
            if(!is_array($current)||$current[0]!==T_STRING)continue;
            $next=$k+1;while($next<$count&&is_array($tokens[$next])&&$tokens[$next][0]===T_WHITESPACE)$next++;
            if($next<$count&&$tokens[$next]==='('){$prev=$k-1;while($prev>=0&&is_array($tokens[$prev])&&$tokens[$prev][0]===T_WHITESPACE)$prev--;$previous=$prev>=0?$tokens[$prev]:null;if($previous==='->'||$previous==='::')continue;if(is_array($previous)&&in_array($previous[0],[T_FUNCTION,T_NEW],true))continue;$calls[$current[1]]=true;}
        }
        $definitions[$name]=['line'=>$line,'calls'=>$calls];$i=max($i,$j);
    }
    return $definitions;
}

$definitions=parse_global_functions_for_prune($source);$definedNames=array_fill_keys(array_keys($definitions),true);
$externalRoots=[];$scanFiles=[];
foreach(['index.php','cron.php','install.php'] as $file)if(is_file($root.'/'.$file))$scanFiles[]=$root.'/'.$file;
foreach(['app','routes','views','themes','modules','bin'] as $directory){$base=$root.'/'.$directory;if(!is_dir($base))continue;$iterator=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base,FilesystemIterator::SKIP_DOTS));foreach($iterator as $file){if(!$file->isFile()||strtolower($file->getExtension())!=='php')continue;$path=$file->getPathname();if(realpath($path)===realpath($functionsPath))continue;$scanFiles[]=$path;}}
$scanFiles=array_values(array_unique($scanFiles));
foreach($scanFiles as $path){$data=@file_get_contents($path);if(!is_string($data))continue;$tokens=token_get_all($data);$count=count($tokens);for($i=0;$i<$count;$i++){$token=$tokens[$i];if(!is_array($token)||$token[0]!==T_STRING)continue;$name=$token[1];if(!isset($definedNames[$name]))continue;$next=$i+1;while($next<$count&&is_array($tokens[$next])&&$tokens[$next][0]===T_WHITESPACE)$next++;if($next>=$count||$tokens[$next]!=='(')continue;$prev=$i-1;while($prev>=0&&is_array($tokens[$prev])&&$tokens[$prev][0]===T_WHITESPACE)$prev--;$previous=$prev>=0?$tokens[$prev]:null;if($previous==='->'||$previous==='::')continue;if(is_array($previous)&&$previous[0]===T_FUNCTION)continue;$externalRoots[$name]=true;}}
$reachable=[];$queue=array_keys($externalRoots);
while($queue){$name=array_pop($queue);if(isset($reachable[$name])||!isset($definitions[$name]))continue;$reachable[$name]=true;foreach($definitions[$name]['calls'] as $called=>$_)if(isset($definitions[$called])&&!isset($reachable[$called]))$queue[]=$called;}

$socialPattern='/^(?:chat|channel|message|wall|profile|colleague|follow|story|push|notification|user_notifications|direct_chat|users_blocked|block_user|unblock_user|feed_|reaction_|save_wall|send_push|web_push)/i';
$reachableSocial=[];$targets=[];
foreach($definitions as $name=>$meta){if(!preg_match($socialPattern,$name))continue;if(isset($reachable[$name]))$reachableSocial[$name]=$meta['line'];else $targets[$name]=$meta['line'];}
if($reachableSocial){foreach($reachableSocial as $name=>$line)fwrite(STDERR,"Refusing to prune reachable social function line {$line}: {$name}\n");exit(2);}
if(!$targets){echo "No unreachable social helpers remain.\n";exit(0);}

$tokens=token_get_all($source);$records=[];$offset=0;
foreach($tokens as $index=>$token){$text=is_array($token)?$token[1]:$token;$records[$index]=['token'=>$token,'text'=>$text,'offset'=>$offset];$offset+=strlen($text);}
$targetSet=array_fill_keys(array_keys($targets),true);$spans=[];$count=count($records);
for($i=0;$i<$count;$i++){
    $token=$records[$i]['token'];if(!is_array($token)||$token[0]!==T_FUNCTION)continue;
    $j=$i+1;while($j<$count){$candidate=$records[$j]['token'];$text=$records[$j]['text'];if(is_array($candidate)&&in_array($candidate[0],[T_WHITESPACE,T_AMPERSAND_FOLLOWED_BY_VAR_OR_VARARG,T_AMPERSAND_NOT_FOLLOWED_BY_VAR_OR_VARARG],true)){$j++;continue;}if($text==='&'){$j++;continue;}break;}
    if($j>=$count||!is_array($records[$j]['token'])||$records[$j]['token'][0]!==T_STRING)continue;$name=$records[$j]['token'][1];if(!isset($targetSet[$name]))continue;
    $body=$j;while($body<$count&&$records[$body]['text']!=='{')$body++;if($body>=$count){fwrite(STDERR,"Cannot find body for {$name}\n");exit(3);}
    $depth=1;$end=$body+1;while($end<$count&&$depth>0){$text=$records[$end]['text'];if($text==='{')$depth++;elseif($text==='}')$depth--;$end++;}if($depth!==0){fwrite(STDERR,"Unbalanced body for {$name}\n");exit(4);}
    $last=$end-1;$spans[]=['name'=>$name,'start'=>$records[$i]['offset'],'end'=>$records[$last]['offset']+strlen($records[$last]['text'])];
}
if(count($spans)!==count($targets)){fwrite(STDERR,"Target/span mismatch: ".count($targets)." targets, ".count($spans)." spans\n");exit(5);}
usort($spans,static fn(array $a,array $b):int=>$b['start']<=>$a['start']);$pruned=$source;
foreach($spans as $span)$pruned=substr_replace($pruned,'',$span['start'],$span['end']-$span['start']);
$pruned=preg_replace("/\n{4,}/","\n\n\n",$pruned)??$pruned;
echo 'Remaining social prune candidate: '.count($spans).' functions: '.implode(', ',array_keys($targets))."\n";
echo 'Size: '.strlen($source).' -> '.strlen($pruned)." bytes\n";
if($write){if(file_put_contents($functionsPath,$pruned,LOCK_EX)===false){fwrite(STDERR,"Cannot write app/functions.php\n");exit(6);}echo "Updated app/functions.php\n";}else echo "Dry run only.\n";
