<?php

declare(strict_types=1);

$currentUri = (string)($_SERVER['REQUEST_URI'] ?? '/');
$currentPath = (string)(parse_url($currentUri, PHP_URL_PATH) ?: '/');

if ($currentPath === '/index.php' || $currentPath === 'index.php') {
    $routeCandidates = [
        (string)($_GET['__kovcheg_route'] ?? ''),
        (string)($_SERVER['HTTP_X_KOVCHEG_ORIGINAL_URI'] ?? ''),
        (string)($_SERVER['HTTP_X_ORIGINAL_URI'] ?? ''),
        (string)($_SERVER['HTTP_X_REWRITE_URL'] ?? ''),
        (string)($_SERVER['REDIRECT_URL'] ?? ''),
        (string)($_SERVER['UNENCODED_URL'] ?? ''),
        (string)($_SERVER['ORIG_PATH_INFO'] ?? ''),
    ];

    foreach ($routeCandidates as $routeCandidate) {
        $routeCandidate = trim($routeCandidate);
        if (
            $routeCandidate === ''
            || strlen($routeCandidate) > 8192
            || !str_starts_with($routeCandidate, '/')
            || preg_match('/[\r\n]/', $routeCandidate)
        ) {
            continue;
        }

        $routePath = (string)(parse_url($routeCandidate, PHP_URL_PATH) ?: '/');
        if ($routePath === '/index.php') {
            continue;
        }

        unset($_GET['__kovcheg_route'], $_REQUEST['__kovcheg_route']);

        $routeQuery = (string)(parse_url($routeCandidate, PHP_URL_QUERY) ?? '');
        if ($routeQuery === '' && $_GET !== []) {
            $routeQuery = http_build_query($_GET, '', '&', PHP_QUERY_RFC3986);
        }

        $_SERVER['REQUEST_URI'] = $routePath.($routeQuery !== '' ? '?'.$routeQuery : '');
        break;
    }
}

require __DIR__.'/app/bootstrap.php';

$router = new \Kovcheg\Router();
require __DIR__.'/routes/blog-preflight.php';
require __DIR__.'/routes/blog-growth.php';
require __DIR__.'/routes/blog-builder.php';
require __DIR__.'/routes/blog.php';
require __DIR__.'/routes/blog-studio.php';
require __DIR__.'/routes/template-features.php';
require __DIR__.'/routes/web.php';
\Kovcheg\Hooks::fire('routes',$router);

$path=request_path();
if(!in_array($path,['/robots.txt','/sitemap.xml','/feed.xml'],true)){
    require_once __DIR__.'/app/BlogGrowth.php';
    if($redirect=\Kovcheg\Blog\Growth::redirectFor($path)){
        $target=(string)$redirect['target'];
        if(!preg_match('~^https?://~i',$target))$target=app_url('/'.ltrim($target,'/'));
        header('Location: '.$target,true,(int)$redirect['code']);
        exit;
    }
}

try { $router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $path); }
catch (Throwable $e) { log_error($e); if(cfg('app.debug',false)) render_system_error(500,'Внутренняя ошибка',$e->getMessage()); render_system_error(500,'Внутренняя ошибка','Подробности записаны в журнал системы.'); }
