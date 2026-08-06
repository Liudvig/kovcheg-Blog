<?php

declare(strict_types=1);

use Kovcheg\Csrf;
use Kovcheg\DB;
use Kovcheg\Blog\Studio;

require_once BASE_PATH.'/app/BlogStudio.php';

$router->get('/studio/categories', function (): void {
    Studio::require('content');
    $categories = DB::all("SELECT c.*,
        (SELECT COUNT(*) FROM content_entry_categories ec
         JOIN content_entries e ON e.id=ec.entry_id
         WHERE ec.category_id=c.id AND e.type='post' AND e.deleted_at IS NULL) entry_count
        FROM content_categories c ORDER BY c.sort_order,c.name");
    Studio::render('categories', [
        'studioSection'=>'categories',
        'studioTitle'=>'Рубрики',
        'categories'=>$categories,
    ]);
});

$router->post('/studio/categories/save', function (): void {
    Studio::require('content');
    Csrf::validate();
    $id = max(0, (int)($_POST['id'] ?? 0));
    $name = mb_substr(trim((string)($_POST['name'] ?? '')), 0, 150);
    if ($name === '') abort(422, 'Введите название рубрики.');
    $slug = Studio::slugify((string)($_POST['slug'] ?? $name));
    if ($slug === '') abort(422, 'Не удалось сформировать адрес рубрики.');
    if (DB::one('SELECT id FROM content_categories WHERE slug=? AND id<>?', [$slug,$id])) {
        abort(422, 'Такой адрес рубрики уже используется.');
    }
    $description = mb_substr(trim((string)($_POST['description'] ?? '')), 0, 2000);
    if ($id > 0) {
        if (!DB::one('SELECT id FROM content_categories WHERE id=?', [$id])) abort(404, 'Рубрика не найдена.');
        DB::run('UPDATE content_categories SET name=?,slug=?,description=?,updated_at=CURRENT_TIMESTAMP WHERE id=?', [$name,$slug,$description ?: null,$id]);
    } else {
        $sort = (int)(DB::one('SELECT COALESCE(MAX(sort_order),-10)+10 next_sort FROM content_categories')['next_sort'] ?? 0);
        $id = DB::insert('INSERT INTO content_categories (name,slug,description,sort_order,created_at,updated_at) VALUES (?,?,?,?,CURRENT_TIMESTAMP,CURRENT_TIMESTAMP)', [$name,$slug,$description ?: null,$sort]);
    }
    audit('cms.category.save', 'content_category', $id);
    $_SESSION['flash_success'] = 'Рубрика сохранена.';
    redirect('/studio/categories');
});

$router->post('/studio/categories/{id}/delete', function (array $params): void {
    Studio::require('content');
    Csrf::validate();
    $id = (int)$params['id'];
    if (!DB::one('SELECT id FROM content_categories WHERE id=?', [$id])) abort(404, 'Рубрика не найдена.');
    DB::run('DELETE FROM content_categories WHERE id=?', [$id]);
    audit('cms.category.delete', 'content_category', $id);
    $_SESSION['flash_success'] = 'Рубрика удалена. Записи сохранены без неё.';
    redirect('/studio/categories');
});
