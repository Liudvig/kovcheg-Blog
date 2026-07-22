<?php

declare(strict_types=1);

namespace Kovcheg\PortalMediaWidgets;

use Kovcheg\Blog\Blog;
use Kovcheg\Blog\Layout;
use Kovcheg\DB;
use Kovcheg\Hooks;
use Throwable;

final class Widgets
{
    public static function boot(): void
    {
        Layout::registerWidget('portal.photo-carousel', [
            'label'=>'Карусель фотографий',
            'description'=>'Современная галерея с листанием, подписями и ссылками.',
            'module'=>'portal-media-widgets',
            'defaults'=>['title'=>'Фотографии','items'=>'','autoplay'=>1,'interval'=>5000,'height'=>420],
            'fields'=>[
                'title'=>['label'=>'Заголовок','type'=>'text','maxlength'=>180],
                'items'=>['label'=>'По строке: URL изображения | Подпись | Ссылка','type'=>'textarea','maxlength'=>30000],
                'autoplay'=>['label'=>'Автоматическое листание','type'=>'checkbox'],
                'interval'=>['label'=>'Интервал, мс','type'=>'number','min'=>2000,'max'=>30000],
                'height'=>['label'=>'Высота, px','type'=>'number','min'=>180,'max'=>900],
            ],
            'render'=>static fn(array $settings,array $context,array $instance):string=>self::photoCarousel($settings,$instance),
        ]);

        Layout::registerWidget('portal.video-carousel', [
            'label'=>'Карусель видео',
            'description'=>'YouTube, VK Видео, Rutube и Vimeo в безопасном слайдере.',
            'module'=>'portal-media-widgets',
            'defaults'=>['title'=>'Видео','items'=>'','autoplay'=>0,'interval'=>8000],
            'fields'=>[
                'title'=>['label'=>'Заголовок','type'=>'text','maxlength'=>180],
                'items'=>['label'=>'По строке: ссылка на видео | Название','type'=>'textarea','maxlength'=>30000],
                'autoplay'=>['label'=>'Автоматическое листание слайдов','type'=>'checkbox'],
                'interval'=>['label'=>'Интервал, мс','type'=>'number','min'=>3000,'max'=>30000],
            ],
            'render'=>static fn(array $settings,array $context,array $instance):string=>self::videoCarousel($settings,$instance),
        ]);

        Layout::registerWidget('portal.content-slider', [
            'label'=>'Слайдер контента',
            'description'=>'Автоматически показывает свежие статьи, страницы или проекты.',
            'module'=>'portal-media-widgets',
            'defaults'=>['title'=>'Рекомендуем','content_type'=>'post','limit'=>6,'show_excerpt'=>1,'autoplay'=>1,'interval'=>6000],
            'fields'=>[
                'title'=>['label'=>'Заголовок','type'=>'text','maxlength'=>180],
                'content_type'=>['label'=>'Тип материалов','type'=>'select','options'=>['post'=>'Статьи','portfolio'=>'Портфолио','page'=>'Страницы','all'=>'Все материалы']],
                'limit'=>['label'=>'Количество','type'=>'number','min'=>2,'max'=>20],
                'show_excerpt'=>['label'=>'Показывать описание','type'=>'checkbox'],
                'autoplay'=>['label'=>'Автоматическое листание','type'=>'checkbox'],
                'interval'=>['label'=>'Интервал, мс','type'=>'number','min'=>2000,'max'=>30000],
            ],
            'render'=>static fn(array $settings,array $context,array $instance):string=>self::contentSlider($settings,$instance),
        ]);

        Hooks::on('blog.layout.head', static fn(mixed $html):string=>(string)$html.'<link rel="stylesheet" href="'.\e(\app_url('/modules/portal-media-widgets/assets/widgets.css?v='.rawurlencode(APP_VERSION))).'">');
        Hooks::on('blog.layout.scripts', static fn(mixed $html):string=>(string)$html.'<script src="'.\e(\app_url('/modules/portal-media-widgets/assets/widgets.js?v='.rawurlencode(APP_VERSION))).'" defer></script>');
    }

    private static function photoCarousel(array $settings,array $instance): string
    {
        $slides=[];
        foreach(self::lines((string)($settings['items']??''),3) as [$url,$caption,$link]){
            $url=self::publicUrl($url);$link=self::publicUrl($link);
            if($url==='')continue;
            $image='<img src="'.\e($url).'" alt="'.\e($caption).'" loading="lazy">';
            $slides[]='<article class="pmw-slide pmw-photo-slide">'.($link!==''?'<a href="'.\e($link).'">'.$image.'</a>':$image).($caption!==''?'<div class="pmw-caption">'.\e($caption).'</div>':'').'</article>';
        }
        if(!$slides)return '';
        return self::shell((string)($settings['title']??'Фотографии'),$slides,$settings,$instance,'pmw-photo-carousel','--pmw-height:'.max(180,min(900,(int)($settings['height']??420))).'px');
    }

    private static function videoCarousel(array $settings,array $instance): string
    {
        $slides=[];
        foreach(self::lines((string)($settings['items']??''),2) as [$url,$title]){
            $embed=self::embedUrl($url);if($embed==='')continue;
            $slides[]='<article class="pmw-slide pmw-video-slide"><div class="pmw-video"><iframe src="'.\e($embed).'" title="'.\e($title?:'Видео').'" loading="lazy" allow="autoplay; encrypted-media; picture-in-picture" allowfullscreen></iframe></div>'.($title!==''?'<div class="pmw-caption">'.\e($title).'</div>':'').'</article>';
        }
        if(!$slides)return '';
        return self::shell((string)($settings['title']??'Видео'),$slides,$settings,$instance,'pmw-video-carousel','');
    }

    private static function contentSlider(array $settings,array $instance): string
    {
        $type=in_array((string)($settings['content_type']??'post'),['post','portfolio','page','all'],true)?(string)$settings['content_type']:'post';
        $limit=max(2,min(20,(int)($settings['limit']??6)));
        $where="e.status='published' AND e.deleted_at IS NULL AND e.visibility='public'";$params=[];
        if($type!=='all'){$where.=' AND e.type=?';$params[]=$type;}
        try{$rows=DB::all("SELECT e.*,u.display_name author_name FROM content_entries e JOIN users u ON u.id=e.author_id WHERE {$where} ORDER BY e.is_featured DESC,COALESCE(e.published_at,e.created_at) DESC,e.id DESC LIMIT {$limit}",$params);}catch(Throwable){$rows=[];}
        $slides=[];
        foreach($rows as $row){
            $cover=self::cover((string)($row['featured_image_path']??''));$url=Blog::entryUrl($row);$excerpt=!empty($settings['show_excerpt'])?Blog::excerpt($row,220):'';
            $slides[]='<article class="pmw-slide pmw-content-slide">'.($cover!==''?'<a class="pmw-content-cover" href="'.\e($url).'"><img src="'.\e($cover).'" alt="" loading="lazy"></a>':'').'<div class="pmw-content-body"><span>'.\e((string)($row['author_name']??'')).'</span><h3><a href="'.\e($url).'">'.\e((string)$row['title']).'</a></h3>'.($excerpt!==''?'<p>'.\e($excerpt).'</p>':'').'<a class="pmw-more" href="'.\e($url).'">Открыть →</a></div></article>';
        }
        if(!$slides)return '';
        return self::shell((string)($settings['title']??'Рекомендуем'),$slides,$settings,$instance,'pmw-content-carousel','');
    }

    private static function shell(string $title,array $slides,array $settings,array $instance,string $class,string $style):string
    {
        $id='pmw-'.(int)($instance['widget_id']??$instance['id']??random_int(1,999999));
        $autoplay=!empty($settings['autoplay'])?'1':'0';$interval=max(2000,min(30000,(int)($settings['interval']??6000)));
        $dots='';foreach($slides as $index=>$unused)$dots.='<button type="button" data-pmw-dot="'.$index.'" aria-label="Слайд '.($index+1).'"'.($index===0?' class="is-active"':'').'></button>';
        return '<section class="pmw-carousel '.$class.'" id="'.\e($id).'" data-pmw-carousel data-autoplay="'.$autoplay.'" data-interval="'.$interval.'" style="'.\e($style).'"><header><h2>'.\e($title).'</h2><div class="pmw-controls"><button type="button" data-pmw-prev aria-label="Предыдущий слайд">←</button><button type="button" data-pmw-next aria-label="Следующий слайд">→</button></div></header><div class="pmw-track" data-pmw-track>'.implode('',$slides).'</div><div class="pmw-dots">'.$dots.'</div></section>';
    }

    private static function lines(string $text,int $parts):array
    {
        $rows=[];foreach(preg_split('/\R/u',$text)?:[] as $line){$line=trim($line);if($line==='')continue;$row=array_map('trim',explode('|',$line,$parts));$rows[]=array_pad($row,$parts,'');if(count($rows)>=40)break;}return $rows;
    }

    private static function cover(string $path):string
    {
        if($path==='')return '';
        try{$id=(int)(DB::one('SELECT id FROM media_library WHERE stored_path=? LIMIT 1',[$path])['id']??0);}catch(Throwable){$id=0;}
        return $id>0?\app_url('/media/'.$id):'';
    }

    private static function publicUrl(string $url):string
    {
        $url=trim($url);if($url==='')return '';
        if(str_starts_with($url,'/'))return \app_url('/'.ltrim($url,'/'));
        return filter_var($url,FILTER_VALIDATE_URL)&&preg_match('~^https?://~i',$url)?$url:'';
    }

    private static function embedUrl(string $url):string
    {
        $url=trim($url);if($url==='')return '';
        if(preg_match('~(?:youtu\.be/|youtube(?:-nocookie)?\.com/(?:watch\?v=|embed/|shorts/))([A-Za-z0-9_-]{6,})~i',$url,$m))return 'https://www.youtube-nocookie.com/embed/'.$m[1].'?rel=0';
        if(preg_match('~(?:vimeo\.com/(?:video/)?)((?:\d){5,})~i',$url,$m))return 'https://player.vimeo.com/video/'.$m[1];
        if(preg_match('~rutube\.ru/(?:video|play/embed)/([A-Za-z0-9_-]+)~i',$url,$m))return 'https://rutube.ru/play/embed/'.$m[1];
        if(preg_match('~(?:vk\.com|vkvideo\.ru)/video(-?\d+)_(\d+)~i',$url,$m))return 'https://vk.com/video_ext.php?oid='.$m[1].'&id='.$m[2].'&hd=2';
        return '';
    }
}

Widgets::boot();