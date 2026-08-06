<div class="page-head"><div><h1>Пресеты и демонстрационный сайт</h1><p>Быстрый старт для нового проекта. Существующие материалы и пользователи не удаляются.</p></div><a class="button" href="<?=e(app_url('/studio/appearance'))?>">Темы оформления</a></div>

<section class="panel" style="margin-bottom:20px;border-color:#9fc0ff;background:linear-gradient(135deg,#f7fbff,#fff)">
 <div style="display:grid;grid-template-columns:minmax(0,1fr) auto;gap:20px;align-items:center">
  <div><small style="display:block;color:#2271b1;font-weight:800;text-transform:uppercase;letter-spacing:.08em;margin-bottom:6px">Готовый пример</small><h2 style="margin:0 0 8px">Развернуть демонстрационный сайт KOVCHEG CMS</h2><p style="margin:0;color:var(--muted);max-width:760px">Будут созданы только отсутствующие рубрики, страницы, новость релиза и главное меню. Действующие материалы не перезаписываются. Индексация останется выключенной, пока вы не замените демонстрационные данные.</p></div>
  <form method="post" data-confirm="Подготовить демонстрационный сайт? Существующие материалы не будут удалены." action="<?=e(app_url('/studio/demo/install'))?>"><?=csrf_field()?><button class="button primary" type="submit">Создать демо-сайт</button></form>
 </div>
</section>

<div class="studio-flash" style="margin-bottom:18px">Обычный пресет изменяет название, позиционирование, описания и активную тему. Перед применением текущие значения сохраняются в истории базы.</div>
<div class="preset-grid">
<?php foreach($presets as $preset):?>
 <article class="preset-card">
  <div class="preset-card__icon"><?=e((string)($preset['icon']??'✦'))?></div>
  <h3><?=e((string)$preset['name'])?></h3>
  <p><?=e((string)($preset['description']??''))?></p>
  <small>Тема: <?=e((string)($preset['theme']??$preset['settings']['blog_theme']??'kovcheg-editorial'))?></small>
  <form method="post" data-confirm="Применить этот пресет к оформлению сайта?" action="<?=e(app_url('/studio/presets/'.rawurlencode((string)$preset['slug']).'/apply'))?>" style="margin-top:16px"><?=csrf_field()?><button class="button primary">Применить пресет</button></form>
 </article>
<?php endforeach;?>
</div>
