<?php

declare(strict_types=1);

use Kovcheg\Auth;
use Kovcheg\Csrf;
use Kovcheg\Blog\DemoSite;
use Kovcheg\Blog\Studio;

require_once BASE_PATH.'/app/BlogDemoSite.php';

$router->post('/studio/demo/install', function () {
    Studio::require('site');
    Csrf::validate();
    $result=DemoSite::install(Auth::id());
    $_SESSION['flash_success']='Демонстрационный сайт подготовлен. Создано материалов: '.(int)$result['entries'].', рубрик: '.(int)$result['categories'].', пунктов меню: '.(int)$result['menu_items'].'.';
    redirect('/studio/content');
});
