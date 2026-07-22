<?php $redirects=$redirects??[];?>
<div class="studio-page-heading"><div><span class="studio-kicker">SITE MANAGER 3.3</span><h1>SEO и индексация</h1><p>Управляйте поисковой выдачей, картой сайта, RSS и перенаправлениями.</p></div><div class="studio-page-links"><a class="button" href="<?=e(app_url('/sitemap.xml'))?>" target="_blank">Sitemap ↗</a><a class="button" href="<?=e(app_url('/feed.xml'))?>" target="_blank">RSS ↗</a></div></div>

<div class="studio-grid-two">
 <section class="studio-panel">
  <header><div><h2>Основные SEO-настройки</h2><p>Они используются там, где материал не имеет собственных значений.</p></div></header>
  <form method="post" action="<?=e(app_url('/studio/seo/settings'))?>" class="studio-form">
   <?=csrf_field()?>
   <label>Суффикс заголовка<input name="seo_title_suffix" maxlength="255" value="<?=e(setting('seo_title_suffix',setting('site_name','KOVCHEG Blog')))?>" placeholder="KOVCHEG Blog"></label>
   <label>Описание сайта<textarea name="seo_description" rows="4" maxlength="1000"><?=e(setting('seo_description','Авторский блог, проекты и портфолио.'))?></textarea></label>
   <label>Изображение по умолчанию<input name="seo_default_image" maxlength="1000" value="<?=e(setting('seo_default_image',''))?>" placeholder="Путь из медиатеки или URL"></label>
   <div class="studio-field-grid"><label>Google verification<input name="seo_google_verification" maxlength="255" value="<?=e(setting('seo_google_verification',''))?>"></label><label>Яндекс verification<input name="seo_yandex_verification" maxlength="255" value="<?=e(setting('seo_yandex_verification',''))?>"></label></div>
   <label>Дополнение robots.txt<textarea name="seo_robots_extra" rows="5" maxlength="5000" placeholder="Дополнительные директивы"><?=e(setting('seo_robots_extra',''))?></textarea></label>
   <div class="studio-check-list"><label><input type="checkbox" name="search_indexing" value="1" <?=setting('search_indexing','0')==='1'?'checked':''?>> Разрешить индексацию сайта</label><label><input type="checkbox" name="seo_sitemap_enabled" value="1" <?=setting('seo_sitemap_enabled','1')==='1'?'checked':''?>> Включить sitemap.xml</label><label><input type="checkbox" name="seo_feed_enabled" value="1" <?=setting('seo_feed_enabled','1')==='1'?'checked':''?>> Включить RSS</label></div>
   <button class="button primary" type="submit">Сохранить SEO</button>
  </form>
 </section>

 <section class="studio-panel">
  <header><div><h2>Системные адреса</h2><p>Эти страницы формируются автоматически.</p></div></header>
  <div class="seo-endpoints"><a href="<?=e(app_url('/robots.txt'))?>" target="_blank"><b>robots.txt</b><span>Правила индексации</span></a><a href="<?=e(app_url('/sitemap.xml'))?>" target="_blank"><b>sitemap.xml</b><span>Карта опубликованных материалов</span></a><a href="<?=e(app_url('/feed.xml'))?>" target="_blank"><b>feed.xml</b><span>RSS последних публикаций</span></a></div>
  <div class="studio-note"><b>Важно</b><p>Пока индексация выключена, сайт отдаёт запрет поисковым системам независимо от остальных настроек.</p></div>
 </section>
</div>

<section class="studio-panel studio-redirects">
 <header><div><h2>Редиректы</h2><p>Сохраняйте старые адреса после переименования страниц и переносов.</p></div></header>
 <form method="post" action="<?=e(app_url('/studio/seo/redirects'))?>" class="redirect-create-form"><?=csrf_field()?><label>Старый адрес<input name="source_path" placeholder="/old-page" required></label><label>Новый адрес<input name="target_url" placeholder="/page/new-page" required></label><label>Код<select name="status_code"><option value="301">301 — постоянно</option><option value="302">302 — временно</option></select></label><button class="button primary" type="submit">Добавить</button></form>
 <?php if($redirects):?><div class="studio-table-wrap"><table class="studio-table"><thead><tr><th>Откуда</th><th>Куда</th><th>Код</th><th>Переходы</th><th>Статус</th><th></th></tr></thead><tbody><?php foreach($redirects as $item):?><tr><td><code><?=e($item['source_path'])?></code></td><td><code><?=e($item['target_url'])?></code></td><td><?=(int)$item['status_code']?></td><td><?=(int)$item['hits']?></td><td><?=$item['is_enabled']?'Работает':'Выключен'?></td><td><div class="table-actions"><form method="post" action="<?=e(app_url('/studio/seo/redirects/'.(int)$item['id'].'/toggle'))?>"><?=csrf_field()?><input type="hidden" name="enabled" value="<?=$item['is_enabled']?0:1?>"><button type="submit"><?=$item['is_enabled']?'Выключить':'Включить'?></button></form><form method="post" action="<?=e(app_url('/studio/seo/redirects/'.(int)$item['id'].'/delete'))?>" onsubmit="return confirm('Удалить редирект?')"><?=csrf_field()?><button class="danger" type="submit">Удалить</button></form></div></td></tr><?php endforeach;?></tbody></table></div><?php else:?><div class="studio-empty">Редиректы пока не созданы.</div><?php endif;?>
</section>
