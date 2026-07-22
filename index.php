<?php

declare(strict_types=1);
require __DIR__.'/app/bootstrap.php';

$router = new \Kovcheg\Router();
// Security-sensitive media and report handlers are registered first.
require __DIR__.'/routes/blog-preflight.php';
// The publishing and Studio layers own public content URLs and the new panel
// while stable authentication, profiles and system services remain available.
require __DIR__.'/routes/blog.php';
require __DIR__.'/routes/blog-studio.php';
require __DIR__.'/routes/template-features.php';
require __DIR__.'/routes/web.php';
\Kovcheg\Hooks::fire('routes',$router);
try { $router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', request_path()); }
catch (Throwable $e) { log_error($e); if(cfg('app.debug',false)) render_system_error(500,'Внутренняя ошибка',$e->getMessage()); render_system_error(500,'Внутренняя ошибка','Подробности записаны в журнал системы.'); }
