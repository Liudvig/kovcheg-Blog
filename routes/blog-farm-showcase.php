<?php

declare(strict_types=1);

use Kovcheg\Auth;
use Kovcheg\Csrf;
use Kovcheg\Blog\Blog;
use Kovcheg\Blog\FarmShowcase;
use Kovcheg\Blog\Studio;
use Kovcheg\Blog\Studio32;

require_once BASE_PATH.'/app/FarmShowcase.php';
require_once BASE_PATH.'/app/BlogStudio32.php';

foreach (FarmShowcase::types() as $type=>$config) {
    $section = (string)$config['section'];
    $publicBase = (string)$config['public_base'];

    $router->get('/studio/'.$section, function () use ($type,$section,$config): void {
        Studio::require('content');
        Studio::render('farm-index', [
            'studioSection'=>$section,
            'studioTitle'=>(string)$config['plural'],
            'showcaseType'=>$type,
            'config'=>$config,
            'items'=>FarmShowcase::studioItems($type),
        ]);
    });

    $router->get('/studio/'.$section.'/new', function () use ($type,$section,$config): void {
        Studio::require('content');
        Studio::render('farm-editor', [
            'studioSection'=>$section,
            'studioTitle'=>'Новый '.mb_strtolower((string)$config['singular']),
            'showcaseType'=>$type,
            'config'=>$config,
            'item'=>null,
        ]);
    });

    $router->get('/studio/'.$section.'/{id}/edit', function (array $params) use ($type,$section,$config): void {
        Studio::require('content');
        $item = FarmShowcase::stored((int)$params['id'], $type);
        if (!$item) abort(404, 'Материал не найден.');
        Studio::render('farm-editor', [
            'studioSection'=>$section,
            'studioTitle'=>'Редактирование: '.(string)$item['title'],
            'showcaseType'=>$type,
            'config'=>$config,
            'item'=>$item,
        ]);
    });

    $router->get($publicBase, function () use ($type,$config): void {
        Blog::render('catalog', [
            'title'=>(string)$config['plural'],
            'description'=>'Актуальные предложения хозяйства.',
            'showcaseType'=>$type,
            'showcaseConfig'=>$config,
            'showcaseItems'=>FarmShowcase::publicItems($type, false, 100),
        ]);
    });

    $router->get($publicBase.'/{slug}', function (array $params) use ($type,$config): void {
        $item = FarmShowcase::publicBySlug($type, (string)($params['slug'] ?? ''));
        if (!$item) abort(404, 'Материал не найден.');
        Blog::render('catalog-item', [
            'title'=>(string)$item['title'],
            'description'=>(string)($item['excerpt'] ?? ''),
            'showcaseType'=>$type,
            'showcaseConfig'=>$config,
            'showcaseItem'=>$item,
        ]);
    });
}

$router->post('/studio/showcase/save', function (): void {
    Studio::require('content');
    Csrf::validate();
    $type = (string)($_POST['showcase_type'] ?? '');
    $config = FarmShowcase::config($type);
    $id = max(0, (int)($_POST['id'] ?? 0));
    $input = $_POST;
    $existing = $id > 0 ? FarmShowcase::stored($id, $type) : null;
    $input['featured_image_path'] = (string)($existing['featured_image_path'] ?? '');

    if (!empty($_FILES['featured_image']['name'])) {
        $media = Studio32::storeMedia($_FILES['featured_image'], Auth::id(), 0);
        $input['featured_image_path'] = (string)($media['stored_path'] ?? '');
    }

    $savedId = FarmShowcase::save($type, $input, Auth::id(), $id);
    $_SESSION['flash_success'] = (string)$config['singular'].' сохранён.';
    redirect('/studio/'.(string)$config['section'].'/'.$savedId.'/edit');
});

$router->post('/studio/showcase/{id}/trash', function (array $params): void {
    Studio::require('content');
    Csrf::validate();
    $type = (string)($_POST['showcase_type'] ?? '');
    $config = FarmShowcase::config($type);
    FarmShowcase::trash((int)$params['id'], $type);
    $_SESSION['flash_success'] = (string)$config['singular'].' перемещён в корзину.';
    redirect('/studio/'.(string)$config['section']);
});
