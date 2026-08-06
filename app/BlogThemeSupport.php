<?php

declare(strict_types=1);

namespace Kovcheg\Blog;

use Kovcheg\Auth;

final class ThemeSupport
{
    public static function menuHtml(string $location, string $orientation = 'auto', string $label = ''): string
    {
        $items = Blog::menu($location);
        if (!$items) return '';
        $orientation = in_array($orientation, ['auto','horizontal','vertical'], true) ? $orientation : 'auto';
        $id = 'location-menu-'.preg_replace('/[^a-z0-9-]+/i', '-', $location);
        $links = [];
        foreach ($items as $item) {
            $url = trim((string)($item['url'] ?? '/')) ?: '/';
            if (!preg_match('~^(?:https?:)?//~i', $url)) $url = app_url('/'.ltrim($url, '/'));
            $links[] = '<a href="'.e($url).'">'.e((string)($item['label'] ?? 'Раздел')).'</a>';
        }
        $aria = $label !== '' ? $label : 'Меню сайта';
        return '<div class="site-location-menu site-location-menu--'.e($orientation).'">'
            .'<button class="site-menu-button" type="button" aria-expanded="false" aria-controls="'.e($id).'" data-widget-menu-button>Меню</button>'
            .'<nav class="site-navigation site-navigation--'.e($orientation).'" id="'.e($id).'" aria-label="'.e($aria).'" data-widget-menu>'
            .implode('', $links)
            .'</nav></div>';
    }

    public static function accountHtml(): string
    {
        if (Auth::check()) {
            $user = Auth::user() ?? [];
            $studio = Studio::can('comments')
                ? '<a class="button button--quiet" href="'.e(app_url('/studio')).'">Studio</a>'
                : '';
            return '<div class="site-account"><a class="site-account__profile" href="'.e(app_url('/account')).'">'
                .avatar_html($user, 'avatar-xs').' <span>'.e((string)($user['display_name'] ?? 'Кабинет')).'</span></a>'.$studio.'</div>';
        }

        $registration = (string)setting('registration_mode', 'closed') !== 'closed'
            ? '<a class="button button--dark" href="'.e(app_url('/register')).'">Регистрация</a>'
            : '';
        return '<div class="site-account"><a href="'.e(app_url('/login')).'">Войти</a>'.$registration.'</div>';
    }
}
