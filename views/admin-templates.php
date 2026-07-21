<div class="page-head"><div><h1>Шаблоны</h1><p>Переключение внешнего вида без изменения ядра, данных и модулей.</p></div></div>
<div class="template-grid">
<?php $templates=[
 ['slug'=>'default','name'=>'Default','description'=>'Текущий современный тёмный шаблон KOVCHEG CMS.','class'=>''],
 ['slug'=>'vk','name'=>'VK Dark','description'=>'Тёмный графитовый KOVCHEG по утверждённому макету: лента, профиль, сообщения и коллеги.','class'=>'vk'],
];foreach($templates as $template):$isActive=$activeTemplate===$template['slug'];?>
 <article class="template-card <?=$isActive?'active':''?>"><header><div><h2><?=e($template['name'])?></h2><p><?=e($template['description'])?></p></div><?php if($isActive):?><span class="template-badge">Активен</span><?php endif;?></header>
  <div class="template-preview <?=e($template['class'])?>"><span class="preview-head"></span><span class="preview-side"></span><span class="preview-main"><i></i><i></i><i></i></span><span class="preview-right"></span></div>
  <form method="post" action="<?=e(app_url('/admin/templates/select'))?>"><?=csrf_field()?><input type="hidden" name="template" value="<?=e($template['slug'])?>"><button class="btn <?=$isActive?'':'btn-primary'?>" <?=$isActive?'disabled':''?>><?=$isActive?'Используется':'Включить '.$template['name']?></button></form>
 </article>
<?php endforeach;?>
</div>
