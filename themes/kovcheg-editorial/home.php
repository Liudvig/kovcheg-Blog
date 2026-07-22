<?php
use Kovcheg\Auth;
use Kovcheg\Blog\Blog;

$homeSections=$homeSections??[];
if(!$homeSections){
 $homeSections=[
  ['section_type'=>'hero','title'=>'Первый экран','settings'=>['eyebrow'=>'KOVCHEG BLOG','title'=>(string)setting('blog_home_title','Создаём будущее своими руками'),'text'=>(string)setting('blog_home_intro','Разработки, технологии, музыка, строительство и реальные проекты — без лишнего шума.'),'primary_text'=>'Читать блог','primary_url'=>'/blog','secondary_text'=>'Смотреть проекты','secondary_url'=>'/portfolio'],'payload'=>[]],
  ['section_type'=>'latest_posts','title'=>'Последние записи','settings'=>['eyebrow'=>'НОВЫЕ МАТЕРИАЛЫ','button_text'=>'Все записи','button_url'=>'/blog'],'payload'=>['entries'=>$posts??[]]],
  ['section_type'=>'portfolio','title'=>'Проекты и результаты','settings'=>['eyebrow'=>'ПОРТФОЛИО','button_text'=>'Всё портфолио','button_url'=>'/portfolio'],'payload'=>['entries'=>$portfolio??[]]],
 ];
}
$safeLink=static function(string $url):string{if($url==='')return '#';return preg_match('~^https?://~i',$url)?$url:app_url('/'.ltrim($url,'/'));};
?>
<?php foreach($homeSections as $section):$type=(string)$section['section_type'];$s=(array)($section['settings']??[]);$payload=(array)($section['payload']??[]);?>
 <?php if($type==='hero'):?>
  <section class="hero home-section home-section--hero" <?php if(!empty($s['image_url'])):?>style="--home-hero-image:url('<?=e($s['image_url'])?>')"<?php endif;?>>
   <div class="hero__content"><span class="eyebrow"><?=e($s['eyebrow']??'KOVCHEG BLOG')?></span><h1><?=e($s['title']??$section['title'])?></h1><?php if(!empty($s['text'])):?><p><?=e($s['text'])?></p><?php endif;?>
    <div class="hero__actions"><?php if(!empty($s['primary_text'])):?><a class="button button--accent" href="<?=e($safeLink((string)($s['primary_url']??'/blog')))?>"><?=e($s['primary_text'])?></a><?php endif;?><?php if(!empty($s['secondary_text'])):?><a class="button button--light" href="<?=e($safeLink((string)($s['secondary_url']??'/portfolio')))?>"><?=e($s['secondary_text'])?></a><?php endif;?></div>
   </div>
   <aside class="hero__panel"><span>Сейчас в работе</span><b><?=e(setting('blog_current_project','KOVCHEG — собственная платформа для сайтов, сообществ и приложений'))?></b><p><?=e(setting('blog_current_project_note','Показываем путь разработки честно: идеи, решения, ошибки и результаты.'))?></p></aside>
  </section>
 <?php elseif($type==='latest_posts'):$entries=(array)($payload['entries']??[]);?>
  <section class="content-section home-section"><header class="section-heading"><div><span class="eyebrow"><?=e($s['eyebrow']??'НОВЫЕ МАТЕРИАЛЫ')?></span><h2><?=e($section['title']?:'Последние записи')?></h2></div><?php if(!empty($s['button_text'])):?><a href="<?=e($safeLink((string)($s['button_url']??'/blog')))?>"><?=e($s['button_text'])?> →</a><?php endif;?></header>
   <?php if($entries):?><div class="entry-grid entry-grid--posts"><?php foreach($entries as $index=>$entry):?><article class="entry-card <?=$index===0?'entry-card--lead':''?>"><div class="entry-card__meta"><span><?=e(date('d.m.Y',strtotime((string)($entry['published_at']?:$entry['created_at']))))?></span><span><?=e((string)$entry['author_name'])?></span></div><h3><a href="<?=e(Blog::entryUrl($entry))?>"><?=e((string)$entry['title'])?></a></h3><p><?=e(Blog::excerpt($entry,$index===0?320:190))?></p><footer><span>💬 <?=(int)$entry['comment_count']?></span><span>✦ <?=(int)$entry['reaction_count']?></span><a href="<?=e(Blog::entryUrl($entry))?>">Читать →</a></footer></article><?php endforeach;?></div><?php else:?><div class="empty-state"><span>Первый материал ещё не опубликован.</span><h3>Здесь появится история создания проекта.</h3><?php if(Auth::isAdmin()):?><a class="button button--dark" href="<?=e(app_url('/studio/content/new'))?>">Создать материал</a><?php endif;?></div><?php endif;?>
  </section>
 <?php elseif($type==='featured_post'&&!empty($payload['entry'])):$entry=$payload['entry'];?>
  <section class="content-section home-section home-featured"><span class="eyebrow"><?=e($s['eyebrow']??'ИЗБРАННОЕ')?></span><article><div><h2><?=e($entry['title'])?></h2><p><?=e(Blog::excerpt($entry,420))?></p><a class="button button--dark" href="<?=e(Blog::entryUrl($entry))?>"><?=e($s['button_text']??'Открыть материал')?> →</a></div></article></section>
 <?php elseif($type==='portfolio'):$entries=(array)($payload['entries']??[]);?>
  <section class="content-section content-section--contrast home-section"><header class="section-heading"><div><span class="eyebrow"><?=e($s['eyebrow']??'ПОРТФОЛИО')?></span><h2><?=e($section['title']?:'Проекты и результаты')?></h2></div><?php if(!empty($s['button_text'])):?><a href="<?=e($safeLink((string)($s['button_url']??'/portfolio')))?>"><?=e($s['button_text'])?> →</a><?php endif;?></header>
   <?php if($entries):?><div class="portfolio-grid"><?php foreach($entries as $entry):?><article class="portfolio-card"><span class="portfolio-card__number"><?=str_pad((string)$entry['id'],2,'0',STR_PAD_LEFT)?></span><h3><a href="<?=e(Blog::entryUrl($entry))?>"><?=e((string)$entry['title'])?></a></h3><p><?=e(Blog::excerpt($entry,170))?></p><a class="portfolio-card__link" href="<?=e(Blog::entryUrl($entry))?>">Открыть проект →</a></article><?php endforeach;?></div><?php else:?><div class="empty-state empty-state--dark"><h3>Раздел портфолио готов к наполнению.</h3><p>Здесь будут работы, релизы, объекты и другие результаты.</p></div><?php endif;?>
  </section>
 <?php elseif($type==='text'):?>
  <section class="statement home-section home-text align-<?=e($s['align']??'left')?>"><span class="eyebrow"><?=e($s['eyebrow']??'')?></span><?php if(!empty($s['heading'])):?><h2><?=e($s['heading'])?></h2><?php endif;?><p><?=nl2br(e($s['text']??''))?></p></section>
 <?php elseif($type==='stats'):?>
  <section class="content-section home-section home-stats"><span class="eyebrow"><?=e($s['eyebrow']??'В ЦИФРАХ')?></span><div class="home-stats__grid"><?php foreach((array)($s['items']??[]) as $item):?><div><b><?=e($item['value']??'')?></b><span><?=e($item['label']??'')?></span></div><?php endforeach;?></div></section>
 <?php elseif($type==='cta'):?>
  <section class="home-section home-cta tone-<?=e($s['tone']??'dark')?>"><span class="eyebrow"><?=e($s['eyebrow']??'')?></span><h2><?=e($s['heading']??$section['title'])?></h2><?php if(!empty($s['text'])):?><p><?=e($s['text'])?></p><?php endif;?><?php if(!empty($s['button_text'])):?><a class="button button--accent" href="<?=e($safeLink((string)($s['button_url']??'#')))?>"><?=e($s['button_text'])?></a><?php endif;?></section>
 <?php endif;?>
<?php endforeach;?>
