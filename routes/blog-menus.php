<?php

declare(strict_types=1);

use Kovcheg\Csrf;
use Kovcheg\DB;
use Kovcheg\Blog\Blog;
use Kovcheg\Blog\Studio;

require_once BASE_PATH.'/app/BlogStudio.php';

if (!function_exists('kovcheg_menu_locations')) {
    function kovcheg_menu_locations(): array
    {
        return [
            ''=>'Не назначено',
            'header'=>'Шапка сайта',
            'left'=>'Левая колонка',
            'right'=>'Правая колонка',
            'footer'=>'Подвал',
        ];
    }
}

if (!function_exists('kovcheg_menu_assign_location')) {
    function kovcheg_menu_assign_location(int $menuId, string $location): void
    {
        $locations = kovcheg_menu_locations();
        if (!array_key_exists($location, $locations)) $location = '';
        if ($location !== '') {
            DB::run('UPDATE navigation_menus SET location=NULL,updated_at=CURRENT_TIMESTAMP WHERE location=? AND id<>?', [$location,$menuId]);
        }
        DB::run('UPDATE navigation_menus SET location=?,updated_at=CURRENT_TIMESTAMP WHERE id=?', [$location !== '' ? $location : null,$menuId]);
    }
}

if (!function_exists('kovcheg_menu_safe_url')) {
    function kovcheg_menu_safe_url(string $url): string
    {
        $url = trim($url);
        if ($url === '') return '/';
        if (str_starts_with($url, '/')) return '/'.ltrim($url, '/');
        if (filter_var($url, FILTER_VALIDATE_URL) && preg_match('~^https?://~i', $url)) return $url;
        abort(422, 'Ссылка должна начинаться с /, http:// или https://.');
    }
}

$router->get('/studio/menus', function (): void {
    Studio::require('menus');
    $menus = DB::all('SELECT * FROM navigation_menus ORDER BY is_active DESC,name,id');
    $menuId = max(0, (int)($_GET['menu'] ?? ($menus[0]['id'] ?? 0)));
    $selectedMenu = $menuId > 0 ? DB::one('SELECT * FROM navigation_menus WHERE id=?', [$menuId]) : null;
    if (!$selectedMenu && $menus) {
        $selectedMenu = $menus[0];
        $menuId = (int)$selectedMenu['id'];
    }
    $items = $menuId > 0
        ? DB::all('SELECT * FROM navigation_items WHERE menu_id=? ORDER BY sort_order,id', [$menuId])
        : [];

    Studio::render('menus', [
        'studioSection'=>'menus',
        'studioTitle'=>'Меню',
        'menus'=>$menus,
        'menuId'=>$menuId,
        'selectedMenu'=>$selectedMenu,
        'items'=>$items,
        'locations'=>kovcheg_menu_locations(),
        'pages'=>DB::all("SELECT id,type,title,slug FROM content_entries WHERE type='page' AND status='published' AND visibility='public' AND deleted_at IS NULL ORDER BY title"),
        'categories'=>DB::all("SELECT c.id,c.name,c.slug,
            (SELECT COUNT(*) FROM content_entry_categories ec JOIN content_entries e ON e.id=ec.entry_id
             WHERE ec.category_id=c.id AND e.type='post' AND e.deleted_at IS NULL) post_count
            FROM content_categories c ORDER BY c.sort_order,c.name"),
    ]);
});

$router->post('/studio/menus/create', function (): void {
    Studio::require('menus');
    Csrf::validate();
    $name = mb_substr(trim((string)($_POST['name'] ?? '')), 0, 150);
    if ($name === '') abort(422, 'Введите название меню.');
    $base = Studio::slugify((string)($_POST['slug'] ?? $name));
    if ($base === '') $base = 'menu';
    $slug = $base;
    $number = 2;
    while (DB::one('SELECT id FROM navigation_menus WHERE slug=?', [$slug])) {
        $suffix = '-'.$number++;
        $slug = substr($base, 0, 100 - strlen($suffix)).$suffix;
    }
    $id = DB::insert(
        'INSERT INTO navigation_menus (name,slug,location,is_active,created_at,updated_at) VALUES (?,?,NULL,1,CURRENT_TIMESTAMP,CURRENT_TIMESTAMP)',
        [$name,$slug]
    );
    kovcheg_menu_assign_location($id, (string)($_POST['location'] ?? ''));
    audit('cms.menu.create', 'navigation_menu', $id, ['location'=>(string)($_POST['location'] ?? '')]);
    $_SESSION['flash_success'] = 'Меню создано. Добавьте пункты или разместите меню через виджет.';
    redirect('/studio/menus?menu='.$id);
});

$router->post('/studio/menus/{id}/update', function (array $params): void {
    Studio::require('menus');
    Csrf::validate();
    $id = (int)$params['id'];
    $menu = DB::one('SELECT * FROM navigation_menus WHERE id=?', [$id]);
    if (!$menu) abort(404, 'Меню не найдено.');
    $name = mb_substr(trim((string)($_POST['name'] ?? '')), 0, 150);
    if ($name === '') abort(422, 'Введите название меню.');
    $active = !empty($_POST['is_active']) ? 1 : 0;
    DB::run('UPDATE navigation_menus SET name=?,is_active=?,updated_at=CURRENT_TIMESTAMP WHERE id=?', [$name,$active,$id]);
    kovcheg_menu_assign_location($id, (string)($_POST['location'] ?? ''));
    audit('cms.menu.update', 'navigation_menu', $id, ['active'=>$active,'location'=>(string)($_POST['location'] ?? '')]);
    $_SESSION['flash_success'] = 'Настройки меню сохранены.';
    redirect('/studio/menus?menu='.$id);
});

$router->post('/studio/menus/{id}/delete', function (array $params): void {
    Studio::require('menus');
    Csrf::validate();
    $id = (int)$params['id'];
    if (!DB::one('SELECT id FROM navigation_menus WHERE id=?', [$id])) abort(404, 'Меню не найдено.');
    DB::run('DELETE FROM navigation_menus WHERE id=?', [$id]);
    audit('cms.menu.delete', 'navigation_menu', $id);
    $_SESSION['flash_success'] = 'Меню удалено. Виджеты с этим меню останутся пустыми до выбора другого меню.';
    redirect('/studio/menus');
});

$router->post('/studio/menus/item', function (): void {
    Studio::require('menus');
    Csrf::validate();
    $menuId = (int)($_POST['menu_id'] ?? 0);
    if (!DB::one('SELECT id FROM navigation_menus WHERE id=?', [$menuId])) abort(404, 'Меню не найдено.');

    $kind = in_array((string)($_POST['target_kind'] ?? ''), ['page','category','custom'], true)
        ? (string)$_POST['target_kind']
        : 'custom';
    $targetId = max(0, (int)($_POST['target_id'] ?? 0));
    $label = mb_substr(trim((string)($_POST['label'] ?? '')), 0, 150);
    $url = trim((string)($_POST['url'] ?? ''));
    $targetType = 'custom';
    $storedTarget = null;

    if ($kind === 'page' && $targetId > 0) {
        $page = DB::one("SELECT id,type,title,slug FROM content_entries WHERE id=? AND type='page' AND status='published' AND visibility='public' AND deleted_at IS NULL", [$targetId]);
        if (!$page) abort(404, 'Страница не найдена или ещё не опубликована.');
        if ($label === '') $label = (string)$page['title'];
        $url = Blog::entryUrl($page);
        $targetType = 'content';
        $storedTarget = $targetId;
    } elseif ($kind === 'category' && $targetId > 0) {
        $category = DB::one('SELECT id,name,slug FROM content_categories WHERE id=?', [$targetId]);
        if (!$category) abort(404, 'Рубрика не найдена.');
        if ($label === '') $label = (string)$category['name'];
        $url = app_url('/category/'.rawurlencode((string)$category['slug']));
        $targetType = 'category';
        $storedTarget = $targetId;
    } else {
        $url = kovcheg_menu_safe_url($url);
    }

    if ($label === '') abort(422, 'Введите подпись пункта.');
    $sort = (int)(DB::one('SELECT COALESCE(MAX(sort_order),-10)+10 next_sort FROM navigation_items WHERE menu_id=?', [$menuId])['next_sort'] ?? 0);
    $id = DB::insert(
        'INSERT INTO navigation_items (menu_id,parent_id,label,url,target_type,target_id,sort_order,is_enabled,created_at,updated_at) VALUES (?,NULL,?,?,?,?,?,1,CURRENT_TIMESTAMP,CURRENT_TIMESTAMP)',
        [$menuId,$label,$url,$targetType,$storedTarget,$sort]
    );
    audit('cms.menu.item.create', 'navigation_item', $id, ['menu_id'=>$menuId,'kind'=>$kind]);
    $_SESSION['flash_success'] = 'Пункт меню добавлен.';
    redirect('/studio/menus?menu='.$menuId);
});

$router->post('/studio/menus/item/{id}/update', function (array $params): void {
    Studio::require('menus');
    Csrf::validate();
    $id = (int)$params['id'];
    $item = DB::one('SELECT * FROM navigation_items WHERE id=?', [$id]);
    if (!$item) abort(404, 'Пункт меню не найден.');
    $label = mb_substr(trim((string)($_POST['label'] ?? '')), 0, 150);
    if ($label === '') abort(422, 'Введите название пункта.');
    $url = kovcheg_menu_safe_url((string)($_POST['url'] ?? '/'));
    $sort = max(-9999, min(9999, (int)($_POST['sort_order'] ?? 0)));
    $enabled = !empty($_POST['is_enabled']) ? 1 : 0;
    DB::run('UPDATE navigation_items SET label=?,url=?,sort_order=?,is_enabled=?,updated_at=CURRENT_TIMESTAMP WHERE id=?', [$label,$url,$sort,$enabled,$id]);
    audit('cms.menu.item.update', 'navigation_item', $id, ['menu_id'=>(int)$item['menu_id'],'enabled'=>$enabled]);
    $_SESSION['flash_success'] = 'Пункт меню сохранён.';
    redirect('/studio/menus?menu='.(int)$item['menu_id']);
});

$router->post('/studio/menus/item/{id}/delete', function (array $params): void {
    Studio::require('menus');
    Csrf::validate();
    $id = (int)$params['id'];
    $item = DB::one('SELECT menu_id FROM navigation_items WHERE id=?', [$id]);
    if (!$item) abort(404, 'Пункт меню не найден.');
    DB::run('DELETE FROM navigation_items WHERE id=?', [$id]);
    audit('cms.menu.item.delete', 'navigation_item', $id, ['menu_id'=>(int)$item['menu_id']]);
    $_SESSION['flash_success'] = 'Пункт меню удалён.';
    redirect('/studio/menus?menu='.(int)$item['menu_id']);
});
