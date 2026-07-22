<?php

declare(strict_types=1);

use Kovcheg\Auth;
use Kovcheg\DB;

$router->get('/account', function () {
    Auth::requireLogin();

    $userId = Auth::id();
    $user = DB::one('SELECT * FROM users WHERE id=? LIMIT 1', [$userId]) ?? Auth::user() ?? [];

    $safeCount = static function (string $sql, array $params = []): int {
        try { return (int)(DB::one($sql, $params)['c'] ?? 0); }
        catch (Throwable $error) { log_error($error); return 0; }
    };

    $stats = [
        'posts' => $safeCount('SELECT COUNT(*) c FROM profile_posts WHERE author_id=? AND deleted_at IS NULL', [$userId]),
        'comments' => $safeCount('SELECT COUNT(*) c FROM content_comments WHERE user_id=? AND deleted_at IS NULL', [$userId]),
        'notifications' => function_exists('user_unread_count') ? user_unread_count($userId) : 0,
        'colleagues' => function_exists('profile_counts') ? (int)(profile_counts($userId)['colleagues'] ?? 0) : 0,
    ];

    $studioAllowed = class_exists(\Kovcheg\Blog\Studio::class)
        && (\Kovcheg\Blog\Studio::can('comments') || \Kovcheg\Blog\Studio::can('content') || \Kovcheg\Blog\Studio::can('site'));

    $accountStats = $stats;
    require BASE_PATH.'/views/account-shell.php';
});