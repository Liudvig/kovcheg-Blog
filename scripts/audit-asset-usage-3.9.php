<?php

declare(strict_types=1);

$root=dirname(__DIR__);
$candidates=[];
foreach(['assets/css','assets/js'] as $assetDir){
    $base=$root.'/'.$assetDir;
    if(!is_dir($base))continue;
    $iterator=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base,FilesystemIterator::SKIP_DOTS));
    foreach($iterator as $file){
        if(!$file->isFile())continue;
        $ext=strtolower(pathinfo($file->getFilename(),PATHINFO_EXTENSION));
        if(!in_array($ext,['css','js'],true))continue;
        $candidates[]=str_replace('\\','/',substr($file->getPathname(),strlen($root)+1));
    }
}
$candidates=array_values(array_unique($candidates));
sort($candidates,SORT_STRING);

$sources=[];
foreach(['app','routes','views','themes','modules','assets'] as $sourceDir){
    $base=$root.'/'.$sourceDir;
    if(!is_dir($base))continue;
    $iterator=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base,FilesystemIterator::SKIP_DOTS));
    foreach($iterator as $file){
        if(!$file->isFile())continue;
        $ext=strtolower(pathinfo($file->getFilename(),PATHINFO_EXTENSION));
        if(!in_array($ext,['php','css','js','json'],true))continue;
        $relative=str_replace('\\','/',substr($file->getPathname(),strlen($root)+1));
        $sources[$relative]=@file_get_contents($file->getPathname())?:'';
    }
}
foreach(['index.php','cron.php','service-worker.js','manifest.webmanifest'] as $file){
    $path=$root.'/'.$file;
    if(is_file($path))$sources[$file]=@file_get_contents($path)?:'';
}

$unreferenced=[];
$referenced=[];
foreach($candidates as $asset){
    $basename=basename($asset);
    $needle='/'.ltrim($asset,'/');
    $hits=[];
    foreach($sources as $relative=>$content){
        if($relative===$asset)continue;
        if(str_contains($content,$needle)||str_contains($content,$asset)||str_contains($content,$basename))$hits[]=$relative;
    }
    if($hits)$referenced[$asset]=$hits;
    else $unreferenced[]=$asset;
}

echo "KOVCHEG asset usage report: ".count($candidates)." assets, ".count($unreferenced)." unreferenced\n";
foreach($unreferenced as $asset)echo "UNREFERENCED: {$asset}\n";
