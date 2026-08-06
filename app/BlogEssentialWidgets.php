<?php

declare(strict_types=1);

namespace Kovcheg\Blog;

use DateTimeImmutable;
use Kovcheg\Auth;
use Kovcheg\DB;
use Throwable;

final class EssentialWidgets
{
    private static bool $booted=false;

    public static function boot(): void
    {
        if(self::$booted)return;
        self::$booted=true;

        $categoryOptions=['0'=>'Все рубрики'];
        $mediaOptions=['0'=>'Выберите файл'];
        try{
            foreach(DB::all('SELECT id,name FROM content_categories ORDER BY sort_order,name') as $row){
                $categoryOptions[(string)(int)$row['id']]=(string)$row['name'];
            }
            foreach(DB::all('SELECT id,original_name,mime_type FROM media_library ORDER BY id DESC LIMIT 250') as $row){
                $mediaOptions[(string)(int)$row['id']]='#'.(int)$row['id'].' · '.(string)$row['original_name'].' · '.(string)$row['mime_type'];
            }
        }catch(Throwable){
        }

        $showOn=['all'=>'На всём сайте','home'=>'Только на главной','page'=>'Только на страницах','post'=>'Только в записях','archive'=>'Только в рубриках и поиске'];

        Layout::registerWidget('core.auth-form',[
            'label'=>'Вход и регистрация',
            'description'=>'Кнопки или компактная форма входа. Для вошедшего пользователя показывает профиль и выход.',
            'defaults'=>['title'=>'Личный кабинет','mode'=>'buttons','show_registration'=>1,'show_on'=>'all'],
            'fields'=>[
                'title'=>['label'=>'Заголовок','type'=>'text','maxlength'=>180],
                'mode'=>['label'=>'Вид','type'=>'select','options'=>['buttons'=>'Кнопки','form'=>'Форма входа']],
                'show_registration'=>['label'=>'Показывать регистрацию','type'=>'checkbox'],
                'show_on'=>['label'=>'Где показывать','type'=>'select','options'=>$showOn],
            ],
            'render'=>static fn(array $settings,array $context):string=>self::renderAuth($settings,$context),
        ]);

        Layout::registerWidget('core.content-slider',[
            'label'=>'Слайдер записей',
            'description'=>'Большое изображение, заголовок и текст записей выбранной рубрики.',
            'defaults'=>['category_id'=>0,'limit'=>5,'show_excerpt'=>1,'autoplay'=>1,'show_on'=>'home'],
            'fields'=>[
                'category_id'=>['label'=>'Рубрика','type'=>'select','options'=>$categoryOptions],
                'limit'=>['label'=>'Количество слайдов','type'=>'number','min'=>2,'max'=>12],
                'show_excerpt'=>['label'=>'Показывать краткий текст','type'=>'checkbox'],
                'autoplay'=>['label'=>'Автоматически листать','type'=>'checkbox'],
                'show_on'=>['label'=>'Где показывать','type'=>'select','options'=>$showOn],
            ],
            'render'=>static fn(array $settings,array $context):string=>self::renderContentSlider($settings,$context),
        ]);

        Layout::registerWidget('core.media',[
            'label'=>'Фото, видео или аудио',
            'description'=>'Один файл из Медиатеки: изображение, видео или аудиозапись.',
            'defaults'=>['media_id'=>0,'caption'=>'','autoplay'=>0,'show_on'=>'all'],
            'fields'=>[
                'media_id'=>['label'=>'Файл из Медиатеки','type'=>'select','options'=>$mediaOptions],
                'caption'=>['label'=>'Подпись','type'=>'text','maxlength'=>300],
                'autoplay'=>['label'=>'Автовоспроизведение без звука','type'=>'checkbox'],
                'show_on'=>['label'=>'Где показывать','type'=>'select','options'=>$showOn],
            ],
            'render'=>static fn(array $settings,array $context):string=>self::renderMedia($settings,$context),
        ]);

        Layout::registerWidget('core.media-slider',[
            'label'=>'Слайдер фото и видео',
            'description'=>'Несколько файлов из Медиатеки. Укажите их ID через запятую, например: 12, 18, 25.',
            'defaults'=>['media_ids'=>'','autoplay'=>1,'show_on'=>'home'],
            'fields'=>[
                'media_ids'=>['label'=>'ID файлов через запятую','type'=>'textarea','maxlength'=>1000],
                'autoplay'=>['label'=>'Автоматически листать','type'=>'checkbox'],
                'show_on'=>['label'=>'Где показывать','type'=>'select','options'=>$showOn],
            ],
            'render'=>static fn(array $settings,array $context):string=>self::renderMediaSlider($settings,$context),
        ]);

        Layout::registerWidget('core.category-calendar',[
            'label'=>'Календарь записей',
            'description'=>'Календарь текущего месяца с днями, в которые опубликованы записи.',
            'defaults'=>['category_id'=>0,'title'=>'Календарь','show_on'=>'all'],
            'fields'=>[
                'title'=>['label'=>'Заголовок','type'=>'text','maxlength'=>180],
                'category_id'=>['label'=>'Рубрика','type'=>'select','options'=>$categoryOptions],
                'show_on'=>['label'=>'Где показывать','type'=>'select','options'=>$showOn],
            ],
            'render'=>static fn(array $settings,array $context):string=>self::renderCalendar($settings,$context),
        ]);

        \Kovcheg\Hooks::on('blog.layout.head',static function($html):string{
            return (string)$html.'<link rel="stylesheet" href="'.\e(\app_url('/assets/css/blog-essential-widgets.css?v='.rawurlencode(ASSET_REVISION))).'">';
        });
        \Kovcheg\Hooks::on('blog.layout.scripts',static function($html):string{
            return (string)$html.'<script src="'.\e(\app_url('/assets/js/blog-essential-widgets.js?v='.rawurlencode(ASSET_REVISION))).'" defer></script>';
        });
    }

    private static function visible(array $settings,array $context):bool
    {
        $show=(string)($settings['show_on']??'all');
        if($show==='all')return true;
        $pageType=(string)($context['page_type']??'default');
        if($show==='archive')return in_array($pageType,['archive','search','category'],true);
        return $show===$pageType;
    }

    private static function renderAuth(array $settings,array $context):string
    {
        if(!self::visible($settings,$context))return '';
        $title=trim((string)($settings['title']??'Личный кабинет'))?:'Личный кабинет';
        if(Auth::check()){
            $user=Auth::user()??[];
            return '<div class="widget-auth"><h2>'.\e($title).'</h2><a class="widget-auth__profile" href="'.\e(\app_url('/account')).'">'.\avatar_html($user,'avatar-xs').'<span><b>'.\e((string)($user['display_name']??'Профиль')).'</b><small>Открыть личный кабинет</small></span></a><form method="post" action="'.\e(\app_url('/logout')).'">'.\csrf_field().'<button type="submit">Выйти</button></form></div>';
        }
        $registration=(string)\setting('registration_mode','closed');
        $register=!empty($settings['show_registration'])&&$registration!=='closed';
        if((string)($settings['mode']??'buttons')==='form'){
            return '<div class="widget-auth"><h2>'.\e($title).'</h2><form class="widget-auth__form" method="post" action="'.\e(\app_url('/login')).'">'.\csrf_field().'<label><span>Email или ник</span><input name="login" autocomplete="username" required></label><label><span>Пароль</span><input type="password" name="password" autocomplete="current-password" required></label><button type="submit">Войти</button></form>'.($register?'<a class="widget-auth__register" href="'.\e(\app_url('/register')).'">'.($registration==='email_auto'?'Регистрация':'Подать заявку').'</a>':'').'</div>';
        }
        return '<div class="widget-auth"><h2>'.\e($title).'</h2><div class="widget-auth__buttons"><a class="primary" href="'.\e(\app_url('/login')).'">Войти</a>'.($register?'<a href="'.\e(\app_url('/register')).'">'.($registration==='email_auto'?'Регистрация':'Подать заявку').'</a>':'').'</div></div>';
    }

    private static function renderContentSlider(array $settings,array $context):string
    {
        if(!self::visible($settings,$context))return '';
        $limit=max(2,min(12,(int)($settings['limit']??5)));
        $categoryId=max(0,(int)($settings['category_id']??0));
        $params=[];
        $join='';$where='';
        if($categoryId>0){$join=' JOIN content_entry_categories ec ON ec.entry_id=e.id';$where=' AND ec.category_id=?';$params[]=$categoryId;}
        $rows=DB::all("SELECT DISTINCT e.id,e.type,e.title,e.slug,e.excerpt,e.content_html,e.featured_image_path,e.published_at,e.created_at,
            (SELECT id FROM media_library m WHERE m.stored_path=e.featured_image_path LIMIT 1) media_id
            FROM content_entries e{$join}
            WHERE e.type='post' AND e.status='published' AND e.visibility='public' AND e.deleted_at IS NULL
              AND (e.published_at IS NULL OR e.published_at<=CURRENT_TIMESTAMP){$where}
            ORDER BY e.is_featured DESC,e.published_at DESC,e.id DESC LIMIT {$limit}",$params);
        if(!$rows)return '';
        $slides=[];
        foreach($rows as $index=>$row){
            $mediaId=(int)($row['media_id']??0);
            $style=$mediaId>0?' style="background-image:linear-gradient(90deg,rgba(8,18,31,.88),rgba(8,18,31,.2)),url(\''.\e(\app_url('/media/'.$mediaId)).'\')"':'';
            $excerpt=!empty($settings['show_excerpt'])?'<p>'.\e(Blog::excerpt($row,220)).'</p>':'';
            $slides[]='<article class="essential-slide'.($index===0?' is-active':'').'" data-essential-slide'.$style.'><div><span>Запись</span><h2><a href="'.\e(Blog::entryUrl($row)).'">'.\e((string)$row['title']).'</a></h2>'.$excerpt.'<a class="essential-slide__open" href="'.\e(Blog::entryUrl($row)).'">Открыть →</a></div></article>';
        }
        return '<section class="essential-slider" data-essential-slider data-autoplay="'.(!empty($settings['autoplay'])?'1':'0').'"><div class="essential-slider__track">'.implode('',$slides).'</div><div class="essential-slider__controls"><button type="button" data-slider-prev aria-label="Назад">←</button><span data-slider-counter>1 / '.count($slides).'</span><button type="button" data-slider-next aria-label="Вперёд">→</button></div></section>';
    }

    private static function renderMedia(array $settings,array $context):string
    {
        if(!self::visible($settings,$context))return '';
        $row=self::media((int)($settings['media_id']??0));
        if(!$row)return '';
        return '<figure class="essential-media">'.self::mediaElement($row,!empty($settings['autoplay'])).(trim((string)($settings['caption']??''))!==''?'<figcaption>'.\e((string)$settings['caption']).'</figcaption>':'').'</figure>';
    }

    private static function renderMediaSlider(array $settings,array $context):string
    {
        if(!self::visible($settings,$context))return '';
        $ids=array_values(array_unique(array_filter(array_map('intval',preg_split('/[^0-9]+/',(string)($settings['media_ids']??''))?:[]),static fn(int $id):bool=>$id>0)));
        $ids=array_slice($ids,0,20);
        if(!$ids)return '';
        $slides=[];
        foreach($ids as $id){$row=self::media($id);if(!$row)continue;$slides[]='<article class="essential-media-slide" data-essential-slide>'.self::mediaElement($row,false).'<span>'.\e((string)($row['title']?:$row['original_name'])).'</span></article>';}
        if(!$slides)return '';
        return '<section class="essential-slider essential-slider--media" data-essential-slider data-autoplay="'.(!empty($settings['autoplay'])?'1':'0').'"><div class="essential-slider__track">'.implode('',$slides).'</div><div class="essential-slider__controls"><button type="button" data-slider-prev aria-label="Назад">←</button><span data-slider-counter>1 / '.count($slides).'</span><button type="button" data-slider-next aria-label="Вперёд">→</button></div></section>';
    }

    private static function renderCalendar(array $settings,array $context):string
    {
        if(!self::visible($settings,$context))return '';
        $month=new DateTimeImmutable('first day of this month 00:00:00');
        $next=$month->modify('+1 month');
        $categoryId=max(0,(int)($settings['category_id']??0));
        $join='';$where='';$params=[$month->format('Y-m-d H:i:s'),$next->format('Y-m-d H:i:s')];
        if($categoryId>0){$join=' JOIN content_entry_categories ec ON ec.entry_id=e.id';$where=' AND ec.category_id=?';$params[]=$categoryId;}
        $rows=DB::all("SELECT DAY(COALESCE(e.published_at,e.created_at)) day,COUNT(DISTINCT e.id) total
            FROM content_entries e{$join}
            WHERE e.type='post' AND e.status='published' AND e.visibility='public' AND e.deleted_at IS NULL
              AND COALESCE(e.published_at,e.created_at)>=? AND COALESCE(e.published_at,e.created_at)<?{$where}
            GROUP BY DAY(COALESCE(e.published_at,e.created_at))",$params);
        $counts=[];foreach($rows as $row)$counts[(int)$row['day']]=(int)$row['total'];
        $days=(int)$month->format('t');$offset=(int)$month->format('N')-1;$cells=[];
        foreach(['Пн','Вт','Ср','Чт','Пт','Сб','Вс'] as $label)$cells[]='<b>'.$label.'</b>';
        for($i=0;$i<$offset;$i++)$cells[]='<span class="is-empty"></span>';
        for($day=1;$day<=$days;$day++){
            $count=$counts[$day]??0;$date=$month->format('Y-m-').str_pad((string)$day,2,'0',STR_PAD_LEFT);
            $cells[]=$count>0?'<a href="'.\e(\app_url('/search?date='.$date)).'" title="Записей: '.$count.'"><span>'.$day.'</span><i>'.$count.'</i></a>':'<span><span>'.$day.'</span></span>';
        }
        $title=trim((string)($settings['title']??'Календарь'))?:'Календарь';
        $months=['01'=>'Январь','02'=>'Февраль','03'=>'Март','04'=>'Апрель','05'=>'Май','06'=>'Июнь','07'=>'Июль','08'=>'Август','09'=>'Сентябрь','10'=>'Октябрь','11'=>'Ноябрь','12'=>'Декабрь'];
        return '<section class="essential-calendar"><header><h2>'.\e($title).'</h2><span>'.\e($months[$month->format('m')]).' '.$month->format('Y').'</span></header><div class="essential-calendar__grid">'.implode('',$cells).'</div></section>';
    }

    private static function media(int $id):?array
    {
        if($id<1)return null;
        return DB::one('SELECT id,title,original_name,mime_type FROM media_library WHERE id=? LIMIT 1',[$id]);
    }

    private static function mediaElement(array $row,bool $autoplay):string
    {
        $url=\app_url('/media/'.(int)$row['id']);$mime=(string)$row['mime_type'];$title=(string)($row['title']?:$row['original_name']);
        if(str_starts_with($mime,'image/'))return '<img src="'.\e($url).'" alt="'.\e($title).'" loading="lazy">';
        if(str_starts_with($mime,'video/'))return '<video src="'.\e($url).'" controls playsinline preload="metadata"'.($autoplay?' autoplay muted loop':'').'></video>';
        if(str_starts_with($mime,'audio/'))return '<div class="essential-audio"><b>'.\e($title).'</b><audio src="'.\e($url).'" controls preload="metadata"'.($autoplay?' autoplay muted loop':'').'></audio></div>';
        return '<a class="essential-file" href="'.\e($url).'">Скачать '.\e($title).'</a>';
    }
}
