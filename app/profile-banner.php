<?php

declare(strict_types=1);

use Kovcheg\DB;

if (!function_exists('profile_banner_path')) {
    function profile_banner_path(int $userId): string
    {
        if ($userId < 1) return '';
        $path = raw_user_setting($userId, 'profile_banner_path', '');
        if ($path === '' || str_contains($path, '..') || !str_starts_with($path, 'banners/'.$userId.'/')) return '';
        return $path;
    }
}

if (!function_exists('profile_banner_position')) {
    function profile_banner_position(int $userId): int
    {
        if ($userId < 1) return 50;
        return max(0, min(100, (int)raw_user_setting($userId, 'profile_banner_position', '50')));
    }
}

if (!function_exists('profile_banner_url')) {
    function profile_banner_url(int $userId): string
    {
        $path = profile_banner_path($userId);
        return $path === '' ? '' : app_url('/profile-banner.php?user='.$userId.'&v='.rawurlencode(substr(hash('sha256', $path), 0, 12)));
    }
}