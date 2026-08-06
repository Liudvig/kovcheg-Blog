<?php

declare(strict_types=1);

$root=dirname(__DIR__);
$errors=[];
$read=static function(string $path)use($root,&$errors):string{
    $content=@file_get_contents($root.'/'.$path);
    if(!is_string($content)){$errors[]='Не удалось прочитать '.$path;return '';}
    return $content;
};
$expect=static function(string $content,string $needle,string $message)use(&$errors):void{
    if(!str_contains($content,$needle))$errors[]=$message;
};
$reject=static function(string $content,string $needle,string $message)use(&$errors):void{
    if(str_contains($content,$needle))$errors[]=$message;
};

$bootstrap=$read('app/bootstrap.php');
$blog=$read('app/Blog.php');
$studio32=$read('app/BlogStudio32.php');
$routes=$read('routes/blog-wordpress-mode.php');
$compat=$read('routes/blog-wordpress-compat.php');
$entryRoutes=$read('routes/blog-entry-routing.php');
$preview=$read('routes/blog-ux-fixes.php');
$layout=$read('views/studio/layout.php');
$editor=$read('views/studio/wp-editor.php');
$pages=$read('views/studio/entries-index.php');
$categories=$read('views/studio/categories.php');
$home=$read('themes/kovcheg-portal/home.php');
$page=$read('themes/kovcheg-portal/page.php');
$archive=$read('themes/kovcheg-portal/archive.php');
$pageCss=$read('themes/kovcheg-portal/assets/page.css');
$studioCss=$read('assets/css/blog-studio-pages.css');
$migration=$read('migrations/20260806_page_category_core.sql');

$expect($bootstrap,"const APP_VERSION = '3.7.0';",'APP_VERSION не равен 3.7.0.');
$expect($bootstrap,"const ASSET_REVISION = '3.7.0-page-category-core';",'Неверная ревизия assets.');
$expect($blog,"WHERE e.type='page'",'Публичный слой не ограничен страницами.');
$expect($blog,"return app_url('/page/'",'Канонический URL страницы не настроен.');
$expect($blog,"['label' => 'Главная'",'Нет минимального меню по умолчанию.');
$reject($blog,"['label' => 'Блог'",'В меню по умолчанию остался Блог.');
$reject($blog,"['label' => 'Портфолио'",'В меню по умолчанию осталось Портфолио.');
$expect($studio32,"$type='page';",'Сохранение не фиксирует тип page.');
$expect($studio32,'self::syncCategories($id,(array)($input[\'category_ids\']??[]));','Рубрики не сохраняются у страниц.');
$expect($routes,"$router->get('/studio/pages'",'Нет списка страниц.');
$expect($routes,"$router->get('/studio/pages/new'",'Нет создания страницы.');
$expect($routes,"e.type='page'",'Рубрика не выбирает страницы.');
$expect($compat,"$router->get('/studio/posts'",'Нет совместимого перехода со старых Записей.');
$expect($compat,"$router->get('/blog'",'Старый адрес Блога не обработан.');
$expect($entryRoutes,"$router->get('/page/{slug}'",'Нет канонического маршрута страницы.');
$reject($entryRoutes,"$router->get('/blog/{slug}'",'Блог остался каноническим маршрутом.');
$expect($preview,"Blog::render('page'",'Предпросмотр не использует новый шаблон страницы.');

$expect($layout,"'pages'=>['Страницы'",'В Studio отсутствует раздел Страницы.');
$expect($layout,"'categories'=>['Рубрики'",'В Studio отсутствует раздел Рубрики.');
$reject($layout,"'posts'=>['Записи'",'В Studio остался раздел Записи.');
$reject($layout,'Добавить запись','В Studio осталась кнопка добавления записи.');
$expect($layout,'Добавить страницу','В Studio отсутствует кнопка добавления страницы.');
$expect($editor,'name="type" value="page"','Редактор не фиксирует тип Страница.');
$expect($editor,'Рубрика — это раздел сайта','В редакторе нет объяснения рубрик.');
$reject($editor,'Новая запись','В редакторе осталось понятие записи.');
$expect($pages,'Все материалы сайта находятся здесь','Список страниц не объясняет единую модель.');
$expect($categories,'Рубрика превращает группу страниц','Экран рубрик не объясняет новую модель.');

$expect($home,'site-home-rubrics','Главная не выводит рубрики.');
$expect($home,'site-home-pages','Главная не выводит страницы.');
$reject($home,'Добавить запись','На главной осталась запись.');
$expect($page,'site-page-breadcrumbs','У страницы нет хлебных крошек.');
$expect($page,'site-page-content','У страницы нет оформленного содержания.');
$expect($page,'site-page-rubrics','У страницы не выводятся рубрики.');
$expect($archive,'category-page-grid','Рубрика не выводит сетку страниц.');
$expect($pageCss,'.site-page-content','Нет стилей публичной страницы.');
$expect($studioCss,'.page-list-card','Нет стилей списка страниц Studio.');
$expect($migration,"WHERE type IN ('post', 'portfolio')",'Миграция не переводит старые типы в страницы.');
$expect($migration,"SET type = 'page'",'Миграция не устанавливает тип page.');

foreach([$pageCss,$studioCss,$read('themes/kovcheg-portal/assets/site-home.css'),$read('themes/kovcheg-portal/assets/category.css')] as $css){
    if(substr_count($css,'{')!==substr_count($css,'}'))$errors[]='Нарушен баланс CSS-скобок.';
}

if($errors){fwrite(STDERR,"Pages and Rubrics core audit failed:\n- ".implode("\n- ",$errors)."\n");exit(1);}
echo "Pages and Rubrics core audit OK\n";
