<div class="page-head"><div><h1>Профессиональные пресеты</h1><p>Быстрый старт для конкретной профессии. Материалы и пользователи при применении не удаляются.</p></div><a class="button" href="<?=e(app_url('/studio/appearance'))?>">Темы оформления</a></div>
<div class="studio-flash" style="margin-bottom:18px">Пресет изменяет название, позиционирование, описания и активную тему. Перед применением текущие значения сохраняются в истории базы.</div>
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
