<?php

declare(strict_types=1);

namespace Kovcheg\Blog;

use Kovcheg\Auth;
use Kovcheg\DB;

final class CmsWidgetOverrides
{
    private static bool $booted = false;

    public static function boot(): void
    {
        if (self::$booted) return;
        self::$booted = true;

        Layout::registerWidget('core.menu', [
            'label'=>'Меню',
            'description'=>'Любое созданное меню. Размещается в шапке, колонках, содержимом или подвале.',
            'defaults'=>['menu_id'=>0,'location'=>'header','orientation'=>'auto'],
            'fields'=>[
                'menu_id'=>['label'=>'Какое меню показывать','type'=>'menu'],
                'orientation'=>['label'=>'Ориентация','type'=>'select','options'=>[
                    'auto'=>'Автоматически',
                    'horizontal'=>'Горизонтально',
                    'vertical'=>'Вертикально',
                ]],
            ],
            'render'=>static fn(array $settings, array $context, array $instance): string => self::renderMenu($settings, $instance),
        ]);

        Layout::registerWidget('core.account', [
            'label'=>'Профиль и вход',
            'description'=>'Кнопки входа, регистрации, профиля и Studio. Регистрация скрывается, когда она закрыта.',
            'defaults'=>['show_registration'=>1],
            'fields'=>[
                'show_registration'=>['label'=>'Показывать регистрацию','type'=>'checkbox'],
            ],
            'render'=>static fn(array $settings): string => self::renderAccount($settings),
        ]);

        Layout::registerWidget('core.categories', [
            'label'=>'Рубрики записей',
            'description'=>'Необязательный список рубрик. Если сайт-портфолио не использует рубрики, просто не размещайте этот виджет.',
            'defaults'=>['title'=>'Рубрики','limit'=>20,'hide_empty'=>1],
            'fields'=>[
                'title'=>['label'=>'Заголовок','type'=>'text','maxlength'=>180],
                'limit'=>['label'=>'Количество','type'=>'number','min'=>1,'max'=>100],
                'hide_empty'=>['label'=>'Скрывать пустые рубрики','type'=>'checkbox'],
            ],
            'render'=>static fn(array $settings): string => self::renderCategories($settings),
        ]);
    }

    private static function renderMenu(array $settings, array $instance): string
    {
        $menuId = max(0, (int)($settings['menu_id'] ?? 0));
        $items = $menuId > 0
            ? Blog::menuById($menuId)
            : Blog::menu((string)($settings['location'] ?? 'header'));
        if (!$items) return '';

        $id = 'widget-menu-'.(int)($instance['widget_id'] ?? $instance['id'] ?? 0);
        $orientation = in_array((string)($settings['orientation'] ?? 'auto'), ['auto','horizontal','vertical'], true)
            ? (string)$settings['orientation']
            : 'auto';
        $links = [];
        foreach ($items as $item) {
            $url = trim((string)($item['url'] ?? '/')) ?: '/';
            if (!preg_match('~^(?:https?:)?//~i', $url)) $url = app_url('/'.ltrim($url, '/'));
            $links[] = '<a href="'.e($url).'">'.e((string)($item['label'] ?? 'Раздел')).'</a>';
        }

        return '<button class="site-menu-button" type="button" aria-expanded="false" aria-controls="'.e($id).'" data-widget-menu-button>Меню</button>'
            .'<nav class="site-navigation site-navigation--'.e($orientation).'" id="'.e($id).'" aria-label="'.e((string)($instance['title'] ?? 'Меню')).'" data-widget-menu>'
            .implode('', $links)
            .'</nav>';
    }

    private static function renderAccount(array $settings): string
    {
        if (Auth::check()) {
            $user = Auth::user() ?? [];
            $studio = Studio::can('comments')
                ? '<a class="button button--quiet" href="'.e(app_url('/studio')).'">Studio</a>'
                : '';
            return '<div class="site-account"><a class="site-account__profile" href="'.e(app_url('/account')).'">'
                .avatar_html($user, 'avatar-xs').' <span>'.e((string)($user['display_name'] ?? 'Профиль')).'</span></a>'.$studio.'</div>';
        }

        $registrationOpen = (string)setting('registration_mode', 'closed') !== 'closed';
        $registration = !empty($settings['show_registration']) && $registrationOpen
            ? '<a class="button button--dark" href="'.e(app_url('/register')).'">Регистрация</a>'
            : '';
        return '<div class="site-account"><a href="'.e(app_url('/login')).'">Войти</a>'.$registration.'</div>';
    }

    private static function renderCategories(array $settings): string
    {
        $limit = max(1, min(100, (int)($settings['limit'] ?? 20)));
        $having = !empty($settings['hide_empty']) ? ' HAVING total>0' : '';
        $rows = DB::all("SELECT c.id,c.name,c.slug,COUNT(e.id) total
            FROM content_categories c
            LEFT JOIN content_entry_categories ec ON ec.category_id=c.id
            LEFT JOIN content_entries e ON e.id=ec.entry_id
                AND e.type='post' AND e.status='published' AND e.visibility='public'
                AND e.deleted_at IS NULL AND (e.published_at IS NULL OR e.published_at<=CURRENT_TIMESTAMP)
            GROUP BY c.id,c.name,c.slug,c.sort_order{$having}
            ORDER BY c.sort_order,c.name LIMIT {$limit}");
        if (!$rows) return '';

        $items = [];
        foreach ($rows as $row) {
            $items[] = '<li><a href="'.e(app_url('/category/'.rawurlencode((string)$row['slug']))).'">'
                .e((string)$row['name']).'</a><span>'.(int)$row['total'].'</span></li>';
        }
        $title = trim((string)($settings['title'] ?? 'Рубрики')) ?: 'Рубрики';
        return '<div class="widget-list"><h2>'.e($title).'</h2><ul>'.implode('', $items).'</ul></div>';
    }
}
