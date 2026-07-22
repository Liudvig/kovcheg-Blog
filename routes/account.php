<?php

declare(strict_types=1);

use Kovcheg\Auth;
use Kovcheg\DB;
use Kovcheg\View;

$router->get('/account', function () {
    Auth::requireLogin();

    $userId = Auth::id();
    $user = DB::one('SELECT * FROM users WHERE id=? LIMIT 1', [$userId]) ?? Auth::user() ?? [];

    $stats = [
        'posts' => (int)(DB::one('SELECT COUNT(*) c FROM profile_posts WHERE author_id=? AND deleted_at IS NULL', [$userId])['c'] ?? 0),
        'comments' => (int)(DB::one('SELECT COUNT(*) c FROM content_comments WHERE user_id=? AND deleted_at IS NULL', [$userId])['c'] ?? 0),
        'notifications' => function_exists('user_unread_count') ? user_unread_count($userId) : 0,
        'colleagues' => (int)(profile_counts($userId)['colleagues'] ?? 0),
    ];

    $studioAllowed = class_exists(\Kovcheg\Blog\Studio::class)
        && (\Kovcheg\Blog\Studio::can('comments') || \Kovcheg\Blog\Studio::can('content') || \Kovcheg\Blog\Studio::can('site'));

    View::render('account', [
        'title' => 'Личный кабинет',
        'user' => $user,
        'accountStats' => $stats,
        'studioAllowed' => $studioAllowed,
    ]);
});
