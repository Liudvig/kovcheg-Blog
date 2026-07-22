<div class="studio-page-head">
 <div><span class="eyebrow">ВНЕШНИЙ ВИД 3.5</span><h1>Виджеты и зоны</h1><p>Раздел временно не смог подготовить таблицы размещения.</p></div>
</div>
<section class="panel">
 <h2>Widget Engine не запущен</h2>
 <p><?=e((string)($layoutError??'Неизвестная ошибка.'))?></p>
 <p>Сайт продолжает работать. Обновите страницу после проверки базы или запустите штатные миграции.</p>
 <?php if(!empty($layoutDiagnostics['tables'])):?><div class="simple-list"><?php foreach($layoutDiagnostics['tables'] as $table=>$ready):?><article><div><b><code><?=e($table)?></code></b><small><?=$ready?'Таблица доступна':'Таблица отсутствует'?></small></div><span class="status <?=$ready?'published':'spam'?>"><?=$ready?'Готово':'Требуется восстановление'?></span></article><?php endforeach;?></div><?php endif;?>
 <div class="editor-actions" style="margin-top:18px"><a class="button primary" href="<?=e(app_url('/studio/widgets'))?>">Повторить восстановление</a><a class="button" href="<?=e(app_url('/studio'))?>">Вернуться в Studio</a></div>
</section>