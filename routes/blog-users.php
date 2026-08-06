<?php

declare(strict_types=1);

use Kovcheg\Auth;
use Kovcheg\Csrf;
use Kovcheg\DB;
use Kovcheg\Blog\Studio;

require_once BASE_PATH.'/app/BlogStudio.php';

if (!function_exists('kovcheg_studio_users_return')) {
    function kovcheg_studio_users_return(): never
    {
        $query = mb_substr(trim((string)($_POST['return_q'] ?? '')), 0, 120);
        redirect('/studio/users'.($query !== '' ? '?q='.rawurlencode($query) : ''));
    }
}

if (!function_exists('kovcheg_active_owner_count')) {
    function kovcheg_active_owner_count(): int
    {
        return (int)(DB::one(
            "SELECT COUNT(*) c FROM users
             WHERE role='owner' AND is_active=1 AND approval_status='approved'"
        )['c'] ?? 0);
    }
}

$router->get('/studio/users', function (): void {
    Studio::require('site');
    $search = mb_substr(trim((string)($_GET['q'] ?? '')), 0, 120);
    $where = '1=1';
    $params = [];

    if ($search !== '') {
        $like = '%'.$search.'%';
        $where = '(display_name LIKE ? OR username LIKE ? OR email LIKE ? OR first_name LIKE ? OR last_name LIKE ?)';
        $params = [$like,$like,$like,$like,$like];
    }

    $users = DB::all(
        "SELECT id,email,username,first_name,last_name,display_name,avatar_path,role,
                is_active,approval_status,created_at,last_seen_at
         FROM users
         WHERE {$where}
         ORDER BY
            CASE role
                WHEN 'owner' THEN 1
                WHEN 'admin' THEN 2
                WHEN 'editor' THEN 3
                WHEN 'moderator' THEN 4
                ELSE 5
            END,
            is_active DESC,display_name,id
         LIMIT 300",
        $params
    );

    Studio::render('users', [
        'studioSection'=>'users',
        'studioTitle'=>'Пользователи',
        'users'=>$users,
        'search'=>$search,
        'roleHistory'=>[],
    ]);
});

$router->post('/studio/users/{id}/role', function (array $params): void {
    Studio::require('site');
    Csrf::validate();

    $id = (int)$params['id'];
    $target = DB::one('SELECT id,display_name,role,is_active,approval_status FROM users WHERE id=?', [$id]);
    if (!$target) abort(404, 'Пользователь не найден.');

    $allowed = ['owner','admin','editor','moderator','user'];
    $newRole = (string)($_POST['role'] ?? 'user');
    if (!in_array($newRole, $allowed, true)) abort(422, 'Неизвестная роль.');

    $actorRole = Studio::role();
    $oldRole = (string)$target['role'];
    if (($oldRole === 'owner' || $newRole === 'owner') && $actorRole !== 'owner') {
        abort(403, 'Только владелец может назначать или изменять роль владельца.');
    }
    if ($id === Auth::id() && $oldRole === 'owner' && $newRole !== 'owner' && kovcheg_active_owner_count() <= 1) {
        abort(409, 'Нельзя снять роль у последнего активного владельца.');
    }
    if ($oldRole === 'owner' && $newRole !== 'owner' && !empty($target['is_active']) && (string)$target['approval_status'] === 'approved' && kovcheg_active_owner_count() <= 1) {
        abort(409, 'В системе должен остаться хотя бы один активный владелец.');
    }

    if ($newRole !== $oldRole) {
        DB::run('UPDATE users SET role=?,updated_at=CURRENT_TIMESTAMP WHERE id=?', [$newRole,$id]);
        audit('cms.user.role', 'user', $id, [
            'previous_role'=>$oldRole,
            'new_role'=>$newRole,
            'display_name'=>(string)$target['display_name'],
        ]);
        $_SESSION['flash_success'] = 'Роль пользователя изменена.';
    } else {
        $_SESSION['flash_success'] = 'Роль уже была выбрана.';
    }

    kovcheg_studio_users_return();
});

$router->post('/studio/users/{id}/status', function (array $params): void {
    Studio::require('site');
    Csrf::validate();

    $id = (int)$params['id'];
    $target = DB::one('SELECT id,display_name,role,is_active,approval_status FROM users WHERE id=?', [$id]);
    if (!$target) abort(404, 'Пользователь не найден.');
    if ($id === Auth::id()) abort(409, 'Нельзя заблокировать собственную учётную запись.');

    $active = !empty($_POST['active']) ? 1 : 0;
    if (
        $active === 0
        && (string)$target['role'] === 'owner'
        && !empty($target['is_active'])
        && (string)$target['approval_status'] === 'approved'
        && kovcheg_active_owner_count() <= 1
    ) {
        abort(409, 'Нельзя заблокировать последнего активного владельца.');
    }

    DB::run('UPDATE users SET is_active=?,updated_at=CURRENT_TIMESTAMP WHERE id=?', [$active,$id]);
    audit('cms.user.status', 'user', $id, [
        'active'=>$active,
        'display_name'=>(string)$target['display_name'],
    ]);
    $_SESSION['flash_success'] = $active ? 'Пользователь разблокирован.' : 'Пользователь заблокирован.';
    kovcheg_studio_users_return();
});
