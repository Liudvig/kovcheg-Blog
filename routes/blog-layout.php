<?php

declare(strict_types=1);

use Kovcheg\Auth;
use Kovcheg\Csrf;
use Kovcheg\DB;
use Kovcheg\Blog\Layout;
use Kovcheg\Blog\Studio;

require_once BASE_PATH.'/app/BlogLayout.php';

$router->get('/studio/widgets', function () {
    Studio::require('site');
    $layoutId = max(0, (int)($_GET['layout'] ?? 0));
    $state = Layout::studioState($layoutId);
    $state['menus'] = DB::all('SELECT id,name,slug FROM navigation_menus WHERE is_active=1 ORDER BY name,id');
    Studio::render('widgets', ['studioSection'=>'widgets','studioTitle'=>'Виджеты и зоны'] + $state);
});

$router->post('/studio/widgets/create', function () {
    Studio::require('site');
    Csrf::validate();
    $type = (string)($_POST['widget_type'] ?? '');
    $definition = Layout::widgetType($type);
    if (!$definition) abort(422, 'Неизвестный тип виджета.');
    $settings = Layout::settingsFromInput($type, $_POST);
    $id = Layout::createWidget($type, (string)($_POST['title'] ?? $definition['label']), $settings, Auth::id());
    audit('blog.widget.create','site_widget',$id,['type'=>$type]);
    $_SESSION['flash_success'] = 'Виджет создан. Перетащите его в нужную зону.';
    redirect('/studio/widgets?layout='.(int)($_POST['layout_id'] ?? 0));
});

$router->post('/studio/widgets/{id}/update', function (array $params) {
    Studio::require('site');
    Csrf::validate();
    $id = (int)$params['id'];
    $widget = DB::one('SELECT widget_type FROM site_widget_instances WHERE id=?', [$id]);
    if (!$widget) abort(404, 'Виджет не найден.');
    $type = (string)$widget['widget_type'];
    Layout::updateWidget($id, (string)($_POST['title'] ?? ''), Layout::settingsFromInput($type, $_POST));
    audit('blog.widget.update','site_widget',$id,['type'=>$type]);
    $_SESSION['flash_success'] = 'Настройки виджета сохранены.';
    redirect('/studio/widgets?layout='.(int)($_POST['layout_id'] ?? 0));
});

$router->post('/studio/widgets/{id}/toggle', function (array $params) {
    Studio::require('site');
    Csrf::validate();
    $id = (int)$params['id'];
    Layout::toggleWidget($id);
    audit('blog.widget.toggle','site_widget',$id);
    $_SESSION['flash_success'] = 'Состояние виджета изменено.';
    redirect('/studio/widgets?layout='.(int)($_POST['layout_id'] ?? 0));
});

$router->post('/studio/widgets/{id}/duplicate', function (array $params) {
    Studio::require('site');
    Csrf::validate();
    $sourceId = (int)$params['id'];
    $id = Layout::duplicateWidget($sourceId, Auth::id());
    audit('blog.widget.duplicate','site_widget',$id,['source_id'=>$sourceId]);
    $_SESSION['flash_success'] = 'Создана копия виджета.';
    redirect('/studio/widgets?layout='.(int)($_POST['layout_id'] ?? 0));
});

$router->post('/studio/widgets/{id}/delete', function (array $params) {
    Studio::require('site');
    Csrf::validate();
    $id = (int)$params['id'];
    Layout::deleteWidget($id);
    audit('blog.widget.delete','site_widget',$id);
    $_SESSION['flash_success'] = 'Виджет удалён.';
    redirect('/studio/widgets?layout='.(int)($_POST['layout_id'] ?? 0));
});

$router->post('/studio/widgets/layout/save', function () {
    Studio::require('site');
    Csrf::validate();
    $layoutId = max(1, (int)($_POST['layout_id'] ?? 0));
    try {
        $placements = json_decode((string)($_POST['placements_json'] ?? '[]'), true, 512, JSON_THROW_ON_ERROR);
    } catch (Throwable) {
        abort(422, 'Схема размещения повреждена. Обновите страницу и повторите действие.');
    }
    if (!is_array($placements)) abort(422, 'Некорректная схема размещения.');
    Layout::savePlacements($layoutId, $placements, Auth::id());
    audit('blog.layout.save','site_layout',$layoutId,['placements'=>count($placements)]);
    $_SESSION['flash_success'] = 'Расположение виджетов опубликовано.';
    redirect('/studio/widgets?layout='.$layoutId);
});

$router->post('/studio/widgets/revisions/{id}/restore', function (array $params) {
    Studio::require('site');
    Csrf::validate();
    $layoutId = Layout::restoreRevision((int)$params['id'], Auth::id());
    audit('blog.layout.restore','site_layout',$layoutId,['revision_id'=>(int)$params['id']]);
    $_SESSION['flash_success'] = 'Схема восстановлена из ревизии.';
    redirect('/studio/widgets?layout='.$layoutId);
});