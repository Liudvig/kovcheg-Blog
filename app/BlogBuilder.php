<?php

declare(strict_types=1);

namespace Kovcheg\Blog;

use Kovcheg\Auth;
use Kovcheg\DB;
use RuntimeException;
use Throwable;

final class Builder
{
    private const MAX_BLOCKS = 300;
    private const MAX_JSON_BYTES = 3_145_728;

    public static function types(): array
    {
        $types = [
            'paragraph' => 'Текст',
            'heading' => 'Заголовок',
            'image' => 'Изображение',
            'gallery' => 'Галерея',
            'quote' => 'Цитата',
            'list' => 'Список',
            'columns' => 'Колонки',
            'button' => 'Кнопка',
            'video' => 'Видео',
            'audio' => 'Аудио',
            'code' => 'Код',
            'separator' => 'Линия',
            'spacer' => 'Отступ',
            'hero' => 'Первый экран',
            'notice' => 'Выделенный блок',
            'stats' => 'Показатели',
            'timeline' => 'Этапы',
            'testimonial' => 'Отзыв',
            'cards' => 'Карточки',
            'contact' => 'Контакты',
        ];

        $extended = \Kovcheg\Hooks::fire('blog.builder.types', $types);
        return is_array($extended) ? $extended : $types;
    }

    public static function normalize(string $json): string
    {
        if (strlen($json) > self::MAX_JSON_BYTES) abort(413, 'Страница превышает допустимый размер редактора.');
        try { $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR); }
        catch (Throwable) { abort(422, 'Редактор передал повреждённые данные.'); }
        if (!is_array($decoded)) $decoded = [];

        $allowed = array_keys(self::types());
        $blocks = [];
        foreach (array_slice(array_values($decoded), 0, self::MAX_BLOCKS) as $block) {
            if (!is_array($block)) continue;
            $type = strtolower((string)($block['type'] ?? 'paragraph'));
            if (!in_array($type, $allowed, true)) continue;
            $id = preg_match('/^[a-zA-Z0-9_-]{3,80}$/', (string)($block['id'] ?? ''))
                ? (string)$block['id']
                : bin2hex(random_bytes(6));
            $data = is_array($block['data'] ?? null) ? $block['data'] : [];
            $blocks[] = ['id'=>$id,'type'=>$type,'data'=>self::normalizeData($type, $data)];
        }

        return json_encode($blocks, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }

    public static function render(string $json): string
    {
        try { $blocks = json_decode($json, true, 512, JSON_THROW_ON_ERROR); }
        catch (Throwable) { return ''; }
        if (!is_array($blocks)) return '';

        $html = [];
        foreach (array_slice($blocks, 0, self::MAX_BLOCKS) as $block) {
            if (!is_array($block)) continue;
            $type = (string)($block['type'] ?? 'paragraph');
            $data = is_array($block['data'] ?? null) ? $block['data'] : [];
            $rendered = self::renderBlock($type, $data);
            if ($rendered !== '') $html[] = $rendered;
        }
        return implode("\n", $html);
    }

    public static function patterns(): array
    {
        $patterns = self::builtInPatterns();
        try {
            foreach (DB::all('SELECT * FROM content_patterns ORDER BY is_system DESC,name,id') as $row) {
                $patterns[] = [
                    'id'=>(int)$row['id'],
                    'slug'=>(string)$row['slug'],
                    'name'=>(string)$row['name'],
                    'description'=>(string)($row['description'] ?? ''),
                    'blocks_json'=>(string)$row['blocks_json'],
                    'system'=>!empty($row['is_system']),
                    'owner_id'=>(int)($row['owner_id'] ?? 0),
                ];
            }
        } catch (Throwable) {}
        return $patterns;
    }

    public static function savePattern(string $name, string $description, string $blocksJson, int $userId): int
    {
        $name = mb_substr(trim($name), 0, 150);
        if (mb_strlen($name) < 2) abort(422, 'Введите название шаблона секций.');
        $normalized = self::normalize($blocksJson);
        if ($normalized === '[]') abort(422, 'Нельзя сохранить пустой шаблон.');
        $slug = Studio::slugify($name);
        if ($slug === '') $slug = 'pattern-'.date('Ymd-His');
        $base = $slug; $n = 2;
        while (DB::one('SELECT id FROM content_patterns WHERE slug=?', [$slug])) $slug = substr($base,0,140).'-'.$n++;
        return DB::insert(
            'INSERT INTO content_patterns (owner_id,name,slug,description,blocks_json,scope,is_system,created_at,updated_at) VALUES (?,?,?,?,?,\'site\',0,CURRENT_TIMESTAMP,CURRENT_TIMESTAMP)',
            [$userId,$name,$slug,mb_substr(trim($description),0,500)?:null,$normalized]
        );
    }

    public static function deletePattern(int $id, int $userId, bool $isAdmin): void
    {
        $pattern = DB::one('SELECT owner_id,is_system FROM content_patterns WHERE id=?', [$id]);
        if (!$pattern) abort(404, 'Шаблон не найден.');
        if (!empty($pattern['is_system'])) abort(403, 'Системный шаблон удалить нельзя.');
        if (!$isAdmin && (int)$pattern['owner_id'] !== $userId) abort(403, 'Недостаточно прав.');
        DB::run('DELETE FROM content_patterns WHERE id=?', [$id]);
    }

    public static function builtInPatterns(): array
    {
        $items = [
            ['slug'=>'landing-intro','name'=>'Лендинг: первый экран','description'=>'Заголовок, описание, кнопка и показатели','blocks'=>[
                ['type'=>'hero','data'=>['eyebrow'=>'НОВЫЙ ПРОЕКТ','title'=>'Расскажите о своей работе','text'=>'Коротко объясните посетителю, кто вы и чем можете быть полезны.','button_text'=>'Связаться','button_url'=>'/page/contacts','align'=>'left']],
                ['type'=>'stats','data'=>['items'=>[['value'=>'10+','label'=>'лет опыта'],['value'=>'120','label'=>'проектов'],['value'=>'24/7','label'=>'на связи']]]],
            ]],
            ['slug'=>'portfolio-case','name'=>'Кейс портфолио','description'=>'Задача, процесс, результат и отзыв','blocks'=>[
                ['type'=>'heading','data'=>['text'=>'Задача','level'=>2]],
                ['type'=>'paragraph','data'=>['text'=>'Опишите исходную ситуацию и цель проекта.']],
                ['type'=>'gallery','data'=>['items'=>[],'columns'=>3]],
                ['type'=>'heading','data'=>['text'=>'Как проходила работа','level'=>2]],
                ['type'=>'timeline','data'=>['items'=>[['title'=>'Подготовка','text'=>'Исследование и планирование'],['title'=>'Реализация','text'=>'Основной этап работы'],['title'=>'Результат','text'=>'Что получил заказчик']]]],
                ['type'=>'testimonial','data'=>['text'=>'Добавьте отзыв клиента или зрителя.','name'=>'Имя клиента','role'=>'Заказчик']],
            ]],
            ['slug'=>'musician-release','name'=>'Музыкальный релиз','description'=>'Обложка, описание, аудио и ссылки','blocks'=>[
                ['type'=>'hero','data'=>['eyebrow'=>'НОВЫЙ РЕЛИЗ','title'=>'Название релиза','text'=>'История трека или альбома.','button_text'=>'Слушать','button_url'=>'#listen','align'=>'center']],
                ['type'=>'audio','data'=>['url'=>'','title'=>'Название композиции','caption'=>'Исполнитель']],
                ['type'=>'paragraph','data'=>['text'=>'Расскажите о создании релиза, участниках и идее.']],
                ['type'=>'contact','data'=>['title'=>'Букинг и сотрудничество','text'=>'Контактная информация для организаторов и партнёров.','email'=>'','phone'=>'','button_text'=>'Написать','button_url'=>'']],
            ]],
            ['slug'=>'service-page','name'=>'Страница услуги','description'=>'Описание, преимущества, этапы и контакты','blocks'=>[
                ['type'=>'hero','data'=>['eyebrow'=>'УСЛУГА','title'=>'Название услуги','text'=>'Понятное объяснение результата для клиента.','button_text'=>'Обсудить проект','button_url'=>'#contact','align'=>'left']],
                ['type'=>'cards','data'=>['items'=>[['title'=>'Качество','text'=>'Что отличает вашу работу'],['title'=>'Сроки','text'=>'Как организован процесс'],['title'=>'Гарантия','text'=>'Что получает заказчик']]]],
                ['type'=>'timeline','data'=>['items'=>[['title'=>'Заявка','text'=>'Обсуждаем задачу'],['title'=>'Расчёт','text'=>'Фиксируем объём и цену'],['title'=>'Работа','text'=>'Выполняем проект'],['title'=>'Сдача','text'=>'Передаём результат']]]],
                ['type'=>'contact','data'=>['title'=>'Обсудим задачу','text'=>'Оставьте удобный способ связи.','email'=>'','phone'=>'','button_text'=>'Связаться','button_url'=>'']],
            ]],
        ];

        return array_map(static function(array $item): array {
            return [
                'id'=>0,
                'slug'=>$item['slug'],
                'name'=>$item['name'],
                'description'=>$item['description'],
                'blocks_json'=>json_encode($item['blocks'], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),
                'system'=>true,
                'owner_id'=>0,
            ];
        }, $items);
    }

    private static function normalizeData(string $type, array $data): array
    {
        $text = static fn(string $key, int $max=10000): string => mb_substr(trim((string)($data[$key] ?? '')), 0, $max);
        $url = static fn(string $key): string => self::safeUrl((string)($data[$key] ?? ''));
        $items = static fn(string $key, int $max=24): array => array_slice(array_values(array_filter((array)($data[$key] ?? []), 'is_array')), 0, $max);

        return match ($type) {
            'paragraph' => ['text'=>$text('text',30000)],
            'heading' => ['text'=>$text('text',500),'level'=>max(2,min(4,(int)($data['level']??2)))],
            'image' => ['url'=>$url('url'),'alt'=>$text('alt',300),'caption'=>$text('caption',500)],
            'gallery' => ['items'=>array_values(array_filter(array_map(static fn($v)=>self::safeUrl((string)$v),(array)($data['items']??[])))),'columns'=>max(2,min(4,(int)($data['columns']??3)))],
            'quote' => ['text'=>$text('text',10000),'caption'=>$text('caption',500)],
            'list' => ['items'=>array_slice(array_values(array_filter(array_map(static fn($v)=>mb_substr(trim((string)$v),0,1000),(array)($data['items']??[])))),0,100),'ordered'=>!empty($data['ordered'])],
            'columns' => ['columns'=>array_slice(array_map(static fn($v)=>mb_substr(trim((string)$v),0,10000),(array)($data['columns']??[])),0,4)],
            'button' => ['text'=>$text('text',150),'url'=>$url('url'),'style'=>in_array((string)($data['style']??''),['primary','outline','link'],true)?(string)$data['style']:'primary'],
            'video' => ['url'=>$url('url'),'caption'=>$text('caption',500)],
            'audio' => ['url'=>$url('url'),'title'=>$text('title',300),'caption'=>$text('caption',500)],
            'code' => ['text'=>$text('text',30000),'language'=>$text('language',40)],
            'separator' => [],
            'spacer' => ['size'=>max(16,min(240,(int)($data['size']??64)))],
            'hero' => ['eyebrow'=>$text('eyebrow',150),'title'=>$text('title',500),'text'=>$text('text',3000),'button_text'=>$text('button_text',150),'button_url'=>$url('button_url'),'image_url'=>$url('image_url'),'align'=>in_array((string)($data['align']??''),['left','center'],true)?(string)$data['align']:'left'],
            'notice' => ['title'=>$text('title',300),'text'=>$text('text',5000),'tone'=>in_array((string)($data['tone']??''),['info','success','warning','dark'],true)?(string)$data['tone']:'info'],
            'stats' => ['items'=>array_map(static fn($v)=>['value'=>mb_substr(trim((string)($v['value']??'')),0,80),'label'=>mb_substr(trim((string)($v['label']??'')),0,200)],$items('items',12))],
            'timeline' => ['items'=>array_map(static fn($v)=>['title'=>mb_substr(trim((string)($v['title']??'')),0,250),'text'=>mb_substr(trim((string)($v['text']??'')),0,3000)],$items('items',20))],
            'testimonial' => ['text'=>$text('text',5000),'name'=>$text('name',200),'role'=>$text('role',250),'avatar_url'=>$url('avatar_url')],
            'cards' => ['items'=>array_map(static fn($v)=>['title'=>mb_substr(trim((string)($v['title']??'')),0,250),'text'=>mb_substr(trim((string)($v['text']??'')),0,3000),'url'=>self::safeUrl((string)($v['url']??''))],$items('items',12))],
            'contact' => ['title'=>$text('title',300),'text'=>$text('text',3000),'email'=>filter_var($text('email',250),FILTER_VALIDATE_EMAIL)?$text('email',250):'','phone'=>$text('phone',100),'button_text'=>$text('button_text',150),'button_url'=>$url('button_url')],
            default => [],
        };
    }

    private static function renderBlock(string $type, array $data): string
    {
        $text = trim((string)($data['text'] ?? ''));
        if ($type === 'paragraph') return $text!==''?'<p>'.nl2br(self::h($text)).'</p>':'';
        if ($type === 'heading') { $level=max(2,min(4,(int)($data['level']??2))); return $text!==''?'<h'.$level.'>'.self::h($text).'</h'.$level.'>:''; }
        if ($type === 'quote') return $text!==''?'<blockquote><p>'.nl2br(self::h($text)).'</p>'.(!empty($data['caption'])?'<cite>'.self::h((string)$data['caption']).'</cite>':'').'</blockquote>':'';
        if ($type === 'code') return $text!==''?'<pre><code data-language="'.self::h((string)($data['language']??'')).'">'.self::h($text).'</code></pre>':'';
        if ($type === 'separator') return '<hr>';
        if ($type === 'spacer') return '<div class="content-spacer" style="--space:'.max(16,min(240,(int)($data['size']??64))).'px" aria-hidden="true"></div>';
        if ($type === 'image') { $url=self::safeUrl((string)($data['url']??'')); return $url!==''?'<figure><img src="'.self::h($url).'" alt="'.self::h((string)($data['alt']??'')).'" loading="lazy">'.(!empty($data['caption'])?'<figcaption>'.self::h((string)$data['caption']).'</figcaption>':'').'</figure>':''; }
        if ($type === 'gallery') { $images=array_values(array_filter(array_map(static fn($v)=>self::safeUrl((string)$v),(array)($data['items']??[])))); if(!$images)return ''; $cells=array_map(static fn($url)=>'<figure><img src="'.self::h($url).'" alt="" loading="lazy"></figure>',$images); return '<div class="content-gallery columns-'.max(2,min(4,(int)($data['columns']??3))).'">'.implode('',$cells).'</div>'; }
        if ($type === 'button') { $url=self::safeUrl((string)($data['url']??'')); return $url!==''&&$text!==''?'<p class="content-button style-'.self::h((string)($data['style']??'primary')).'"><a href="'.self::h($url).'">'.self::h($text).'</a></p>':''; }
        if ($type === 'list') { $items=array_slice(array_filter(array_map('trim',(array)($data['items']??[]))),0,100); if(!$items)return ''; $tag=!empty($data['ordered'])?'ol':'ul'; return '<'.$tag.'><li>'.implode('</li><li>',array_map([self::class,'h'],$items)).'</li></'.$tag.'>'; }
        if ($type === 'columns') { $columns=array_slice((array)($data['columns']??[]),0,4); if(!$columns)return ''; return '<div class="content-columns columns-'.count($columns).'">'.implode('',array_map(static fn($v)=>'<div>'.nl2br(self::h((string)$v)).'</div>',$columns)).'</div>'; }
        if ($type === 'video') { $url=self::safeUrl((string)($data['url']??'')); if($url==='')return ''; $embed=self::videoEmbed($url); return '<figure class="content-video">'.($embed!==''?$embed:'<a href="'.self::h($url).'" rel="noopener noreferrer">Открыть видео</a>').(!empty($data['caption'])?'<figcaption>'.self::h((string)$data['caption']).'</figcaption>':'').'</figure>'; }
        if ($type === 'audio') { $url=self::safeUrl((string)($data['url']??'')); if($url==='')return ''; return '<figure class="content-audio">'.(!empty($data['title'])?'<h3>'.self::h((string)$data['title']).'</h3>':'').'<audio controls preload="metadata" src="'.self::h($url).'"></audio>'.(!empty($data['caption'])?'<figcaption>'.self::h((string)$data['caption']).'</figcaption>':'').'</figure>'; }
        if ($type === 'hero') { $image=self::safeUrl((string)($data['image_url']??'')); $style=$image!==''?' style="--hero-image:url(&quot;'.self::h($image).'&quot;)"':''; $button=self::safeUrl((string)($data['button_url']??'')); return '<section class="content-hero align-'.self::h((string)($data['align']??'left')).'"'.$style.'><div>'.(!empty($data['eyebrow'])?'<span>'.self::h((string)$data['eyebrow']).'</span>':'').(!empty($data['title'])?'<h2>'.self::h((string)$data['title']).'</h2>':'').(!empty($data['text'])?'<p>'.nl2br(self::h((string)$data['text'])).'</p>':'').($button!==''&&!empty($data['button_text'])?'<a href="'.self::h($button).'">'.self::h((string)$data['button_text']).'</a>':'').'</div></section>'; }
        if ($type === 'notice') return '<aside class="content-notice tone-'.self::h((string)($data['tone']??'info')).'">'.(!empty($data['title'])?'<h3>'.self::h((string)$data['title']).'</h3>':'').(!empty($data['text'])?'<p>'.nl2br(self::h((string)$data['text'])).'</p>':'').'</aside>';
        if ($type === 'stats') { $items=(array)($data['items']??[]); if(!$items)return ''; return '<div class="content-stats">'.implode('',array_map(static fn($v)=>'<div><b>'.self::h((string)($v['value']??'')).'</b><span>'.self::h((string)($v['label']??'')).'</span></div>',$items)).'</div>'; }
        if ($type === 'timeline') { $items=(array)($data['items']??[]); if(!$items)return ''; return '<div class="content-timeline">'.implode('',array_map(static fn($v)=>'<article><i></i><div><h3>'.self::h((string)($v['title']??'')).'</h3><p>'.nl2br(self::h((string)($v['text']??''))).'</p></div></article>',$items)).'</div>'; }
        if ($type === 'testimonial') { $avatar=self::safeUrl((string)($data['avatar_url']??'')); return $text!==''?'<figure class="content-testimonial"><blockquote>'.nl2br(self::h($text)).'</blockquote><figcaption>'.($avatar!==''?'<img src="'.self::h($avatar).'" alt="">':'').'<div><b>'.self::h((string)($data['name']??'')).'</b><span>'.self::h((string)($data['role']??'')).'</span></div></figcaption></figure>':''; }
        if ($type === 'cards') { $items=(array)($data['items']??[]); if(!$items)return ''; return '<div class="content-cards">'.implode('',array_map(static function($v){$url=self::safeUrl((string)($v['url']??''));$body='<h3>'.self::h((string)($v['title']??'')).'</h3><p>'.nl2br(self::h((string)($v['text']??''))).'</p>';return $url!==''?'<a href="'.self::h($url).'">'.$body.'</a>':'<article>'.$body.'</article>';},$items)).'</div>'; }
        if ($type === 'contact') { $button=self::safeUrl((string)($data['button_url']??'')); $email=(string)($data['email']??''); $phone=(string)($data['phone']??''); return '<section class="content-contact" id="contact"><div><h2>'.self::h((string)($data['title']??'Связаться')).'</h2><p>'.nl2br(self::h((string)($data['text']??''))).'</p></div><div>'.($email!==''?'<a href="mailto:'.self::h($email).'">'.self::h($email).'</a>':'').($phone!==''?'<a href="tel:'.self::h(preg_replace('/[^+0-9]/','',$phone)??'').'">'.self::h($phone).'</a>':'').($button!==''&&!empty($data['button_text'])?'<a class="contact-button" href="'.self::h($button).'">'.self::h((string)$data['button_text']).'</a>':'').'</div></section>'; }
        return '';
    }

    private static function videoEmbed(string $url): string
    {
        $host = strtolower((string)parse_url($url, PHP_URL_HOST));
        $path = (string)parse_url($url, PHP_URL_PATH);
        $query = []; parse_str((string)parse_url($url, PHP_URL_QUERY), $query);
        $src = '';
        if (str_contains($host,'youtube.com') && !empty($query['v'])) $src='https://www.youtube-nocookie.com/embed/'.rawurlencode((string)$query['v']);
        elseif ($host==='youtu.be' && trim($path,'/')!=='') $src='https://www.youtube-nocookie.com/embed/'.rawurlencode(trim($path,'/'));
        elseif (str_contains($host,'vimeo.com') && preg_match('~/([0-9]+)~',$path,$m)) $src='https://player.vimeo.com/video/'.$m[1];
        elseif (str_contains($host,'rutube.ru') && preg_match('~/video/([a-zA-Z0-9]+)~',$path,$m)) $src='https://rutube.ru/play/embed/'.$m[1];
        return $src!==''?'<iframe src="'.self::h($src).'" loading="lazy" allow="accelerometer; autoplay; encrypted-media; picture-in-picture" allowfullscreen></iframe>':'';
    }

    private static function safeUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '') return '';
        if (str_starts_with($url, '/')) return preg_match('~^/[a-zA-Z0-9_./?=&%#:+-]*$~',$url)?$url:'';
        return filter_var($url,FILTER_VALIDATE_URL)&&preg_match('~^https?://~i',$url)?$url:'';
    }

    public static function h(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
