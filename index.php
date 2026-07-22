<?php

declare(strict_types=1);
require __DIR__.'/app/bootstrap.php';

$router = new \Kovcheg\Router();
// Security-sensitive media and report handlers are registered first.
require __DIR__.'/routes/blog-preflight.php';
// Growth 3.3 owns SEO endpoints, subscriptions, redirects and scheduled publishing tools.
require __DIR__.'/routes/blog-growth.php';
// Blog 3.2 overrides selected Studio editor, media and management routes.
require __DIR__.'/routes/blog-builder.php';
// Public content and the stable Studio fallback routes remain available.
require __DIR__.'/routes/blog.php';
require __DIR__.'/routes/blog-studio.php';
require __DIR__.'/routes/template-features.php';
require __DIR__.'/routes/web.php';
\Kovcheg\Hooks::fire('routes',$router);

$path=request_path();
if(!in_array($path,['/robots.txt','/sitemap.xml','/feed.xml'],true)){
    require_once __DIR__.'/app/BlogGrowth.php';
    if($redirect=\Kovcheg\Blog\Growth::redirectFor($path)){
        header('Location: '.app_url($redirect['target']),true,$redirect['code']);
        exit;
    }
}

try { $router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $path); }
catch (Throwable $e) { log_error($e); if(cfg('app.debug',false)) render_system_error(500,'Внутренняя ошибка',$e->getMessage()); render_system_error(500,'Внутренняя ошибка','Подробности записаны в журнал системы.'); }
