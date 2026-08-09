<?php

declare(strict_types=1);

use Kovcheg\Auth;
use Kovcheg\DB;

$router->get('/account', function (): void {
    Auth::requireLogin();

    $userId = Auth::id();
    $user = DB::one('SELECT * FROM users WHERE id=? LIMIT 1', [$userId]) ?? Auth::user() ?? [];

    $safeCount = static function (string $sql, array $params = []): int {
        try {
            return (int)(DB::one($sql, $params)['c'] ?? 0);
        } catch (Throwable $error) {
            log_error($error);
            return 0;
        }
    };

    $accountStats = [
        'materials'=>$safeCount(
            "SELECT COUNT(*) c FROM content_entries WHERE author_id=? AND type IN ('post','page') AND deleted_at IS NULL",
            [$userId]
        ),
        'comments'=>$safeCount(
            'SELECT COUNT(*) c FROM content_comments WHERE user_id=? AND deleted_at IS NULL',
            [$userId]
        ),
    ];

    $studioAllowed = class_exists(\Kovcheg\Blog\Studio::class)
        && (
            \Kovcheg\Blog\Studio::can('comments')
            || \Kovcheg\Blog\Studio::can('content')
            || \Kovcheg\Blog\Studio::can('site')
        );

    require BASE_PATH.'/views/account-shell.php';
});
