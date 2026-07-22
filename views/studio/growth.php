<?php
$scheduled=$scheduled??[];$redirects=$redirects??[];$subscriptions=$subscriptions??[];$publicationLog=$publicationLog??[];
?>
<div class="studio-page-head"><div><span class="eyebrow">ПРОДВИЖЕНИЕ</span><h1>SEO и рост</h1><p>Поисковая индексация, карта сайта, RSS, подписки, редиректы и публикация по расписанию.</p></div><div class="studio-actions"><a class="button" href="<?=e(app_url('/sitemap.xml'))?>" target="_blank">Sitemap</a><a class="button" href="<?=e(app_url('/feed.xml'))?>" target="_blank">RSS</a></div></div>

<div class="studio-grid two">
<section class="studio-card"><h2>Поисковые настройки</h2><form method="post" action="<?=e(app_url('/studio/growth/settings'))?>"><?=csrf_field()?>
<label>Название сайта для поисковиков<input name="seo_site_title" value="<?=e(setting('seo_site_title',''))?>" maxlength="255"></label>
<label>Описание по умолчанию<textarea name="seo_default_description" rows="4" maxlength="500"><?=e(setting('seo_default_description',''))?></textarea></label>
<label class="check"><input type="checkbox" name="seo_robots_index" value="1" <?=setting('seo_robots_index','1')==='1'?'checked':''?>> Разрешить индексацию сайта</label>
<label class="check"><input type="checkbox" name="seo_sitemap_enabled" value="1" <?=setting('seo_sitemap_enabled','1')==='1'?'checked':''?>> Включить sitemap.xml</label>
<label class="check"><input type="checkbox" name="seo_rss_enabled" value="1" <?=setting('seo_rss_enabled','1')==='1'?'checked':''?>> Включить RSS</label>
<label class="check"><input type="checkbox" name="subscriptions_enabled" value="1" <?=setting('subscriptions_enabled','1')==='1'?'checked':''?>> Включить подписку по email</label>
<button class="button primary">Сохранить настройки</button></form></section>

<section class="studio-card"><div class="card-head"><div><h2>Публикация по расписанию</h2><p>Материалы публикуются через cron-команду или вручную.</p></div><form method="post" action="<?=e(app_url('/studio/growth/publish-scheduled'))?>"><?=csrf_field()?><button class="button">Опубликовать готовые</button></form></div>
<?php if(!$scheduled):?><p class="empty">Запланированных материалов нет.</p><?php else:?><div class="table-wrap"><table><thead><tr><th>Материал</th><th>Тип</th><th>Дата</th></tr></thead><tbody><?php foreach($scheduled as $item):?><tr><td><a href="<?=e(app_url('/studio/content/'.(int)$item['id'].'/edit'))?>"><?=e($item['title'])?></a></td><td><?=e($item['type'])?></td><td><?=e($item['published_at'])?></td></tr><?php endforeach;?></tbody></table></div><?php endif;?></section>
</div>

<section class="studio-card"><div class="card-head"><div><h2>Редиректы</h2><p>Сохраняют позиции и посетителей после изменения адресов страниц.</p></div></div>
<form class="inline-form" method="post" action="<?=e(app_url('/studio/growth/redirects'))?>"><?=csrf_field()?>
<input name="source_path" placeholder="/старый-адрес" required><input name="target_path" placeholder="/новый-адрес" required><select name="status_code"><option value="301">301 навсегда</option><option value="302">302 временно</option><option value="307">307 временно</option><option value="308">308 навсегда</option></select><label class="check"><input type="checkbox" name="is_active" value="1" checked> Активен</label><button class="button primary">Добавить</button></form>
<?php if($redirects):?><div class="table-wrap"><table><thead><tr><th>Откуда</th><th>Куда</th><th>Код</th><th>Переходы</th><th></th></tr></thead><tbody><?php foreach($redirects as $item):?><tr><td><code><?=e($item['source_path'])?></code></td><td><code><?=e($item['target_path'])?></code></td><td><?=e((string)$item['status_code'])?></td><td><?=e((string)$item['hits'])?></td><td><form method="post" action="<?=e(app_url('/studio/growth/redirects/'.(int)$item['id'].'/delete'))?>"><?=csrf_field()?><button class="button danger small">Удалить</button></form></td></tr><?php endforeach;?></tbody></table></div><?php endif;?></section>

<div class="studio-grid two">
<section class="studio-card"><h2>Подписчики</h2><p class="metric"><b><?=count($subscriptions)?></b><span>последних записей</span></p><?php if($subscriptions):?><div class="table-wrap"><table><thead><tr><th>Email</th><th>Статус</th><th>Дата</th></tr></thead><tbody><?php foreach($subscriptions as $item):?><tr><td><?=e($item['email'])?></td><td><?=e($item['status'])?></td><td><?=e($item['created_at'])?></td></tr><?php endforeach;?></tbody></table></div><?php endif;?></section>
<section class="studio-card"><h2>Журнал публикаций</h2><?php if(!$publicationLog):?><p class="empty">Журнал пока пуст.</p><?php else:?><div class="activity-list"><?php foreach($publicationLog as $item):?><div><b><?=e($item['title'])?></b><span><?=e($item['action'])?> · <?=e($item['created_at'])?></span></div><?php endforeach;?></div><?php endif;?></section>
</div>
